<?php
/*
 * 파일명: sms_log_stats.php
 * 위치: /adm/sms_log_stats.php
 * 기능: SMS 발송 통계
 * 작성일: 2024-12-28
 * 수정일: 2024-12-29 (jQuery UI 및 Canvas 스타일 추가)
 */

$sub_menu = "900940";
include_once('./_common.php');

if ($is_admin != 'super')
    alert('최고관리자만 접근 가능합니다.');

$g5['title'] = 'SMS 발송 통계';
include_once('./admin.head.php');

$g5['sms_log_table'] = G5_TABLE_PREFIX.'sms_log';

// ===================================
// 기간 설정
// ===================================
if (!$fr_date) $fr_date = date("Y-m-d", strtotime("-30 days"));
if (!$to_date) $to_date = date("Y-m-d");


// ===================================
// 전체 통계
// ===================================
$sql = "SELECT 
            COUNT(*) as total_cnt,
            SUM(CASE WHEN sl_result = 'success' THEN 1 ELSE 0 END) as success_cnt,
            SUM(CASE WHEN sl_result = 'fail' THEN 1 ELSE 0 END) as fail_cnt,
            SUM(sl_cost) as total_cost
        FROM {$g5['sms_log_table']}
        WHERE sl_datetime BETWEEN '{$fr_date} 00:00:00' AND '{$to_date} 23:59:59'";
$total_stat = sql_fetch($sql);

$success_rate = $total_stat['total_cnt'] > 0 ? round(($total_stat['success_cnt'] / $total_stat['total_cnt']) * 100, 1) : 0;

// ===================================
// 일별 통계
// ===================================
$sql = "SELECT 
            DATE(sl_datetime) as date,
            COUNT(*) as cnt,
            SUM(CASE WHEN sl_result = 'success' THEN 1 ELSE 0 END) as success_cnt,
            SUM(CASE WHEN sl_result = 'fail' THEN 1 ELSE 0 END) as fail_cnt
        FROM {$g5['sms_log_table']}
        WHERE sl_datetime BETWEEN '{$fr_date} 00:00:00' AND '{$to_date} 23:59:59'
        GROUP BY DATE(sl_datetime)
        ORDER BY date DESC";
$daily_result = sql_query($sql);

// ===================================
// 타입별 통계
// ===================================
$sql = "SELECT 
            sl_type,
            COUNT(*) as cnt,
            SUM(CASE WHEN sl_result = 'success' THEN 1 ELSE 0 END) as success_cnt,
            SUM(CASE WHEN sl_result = 'fail' THEN 1 ELSE 0 END) as fail_cnt
        FROM {$g5['sms_log_table']}
        WHERE sl_datetime BETWEEN '{$fr_date} 00:00:00' AND '{$to_date} 23:59:59'
        GROUP BY sl_type
        ORDER BY cnt DESC";
$type_result = sql_query($sql);

// ===================================
// 에러 통계
// ===================================
$sql = "SELECT 
            sl_error_code,
            COUNT(*) as cnt
        FROM {$g5['sms_log_table']}
        WHERE sl_result = 'fail' 
        AND sl_error_code != ''
        AND sl_datetime BETWEEN '{$fr_date} 00:00:00' AND '{$to_date} 23:59:59'
        GROUP BY sl_error_code
        ORDER BY cnt DESC
        LIMIT 10";
$error_result = sql_query($sql);

// ===================================
// 시간대별 통계
// ===================================
$sql = "SELECT 
            HOUR(sl_datetime) as hour,
            COUNT(*) as cnt,
            SUM(CASE WHEN sl_result = 'success' THEN 1 ELSE 0 END) as success_cnt
        FROM {$g5['sms_log_table']}
        WHERE sl_datetime BETWEEN '{$fr_date} 00:00:00' AND '{$to_date} 23:59:59'
        GROUP BY HOUR(sl_datetime)
        ORDER BY hour";
$hourly_result = sql_query($sql);

// 차트 데이터 준비
$hourly_labels = array();
$hourly_data = array();
$hourly_success = array();

for ($i = 0; $i < 24; $i++) {
    $hourly_labels[] = $i . '시';
    $hourly_data[$i] = 0;
    $hourly_success[$i] = 0;
}

while ($row = sql_fetch_array($hourly_result)) {
    $hourly_data[$row['hour']] = $row['cnt'];
    $hourly_success[$row['hour']] = $row['success_cnt'];
}
?>

<!-- jQuery UI CSS -->
<link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
<!-- jQuery UI JS -->
<script src="//code.jquery.com/ui/1.12.1/jquery-ui.js"></script>

<style>
/* 통계 페이지 스타일 */
.stat_container { margin: 20px 0; }
.stat_box_wrap { display: flex; gap: 20px; margin-bottom: 30px; }
.stat_box { 
    flex: 1;
    background: #fff; 
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    text-align: center;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
.stat_box h3 { 
    margin: 0 0 10px 0; 
    font-size: 14px;
    color: #666;
}
.stat_box .value { 
    font-size: 32px; 
    font-weight: bold;
    color: #333;
}
.stat_box.primary { background: #007bff; color: #fff; }
.stat_box.primary h3 { color: #fff; }
.stat_box.primary .value { color: #fff; }
.stat_box.success { background: #28a745; color: #fff; }
.stat_box.success h3 { color: #fff; }
.stat_box.success .value { color: #fff; }
.stat_box.danger { background: #dc3545; color: #fff; }
.stat_box.danger h3 { color: #fff; }
.stat_box.danger .value { color: #fff; }

.chart_container { 
    background: #fff; 
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 30px;
}
.chart_container h3 { 
    margin: 0 0 20px 0;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

/* Canvas 요소에 대한 max-height 설정 */
canvas {
    max-height: 400px !important;
    width: auto !important;
}

.table_container { 
    background: #fff; 
    border: 1px solid #ddd;
    border-radius: 8px;
    overflow: hidden;
    margin-bottom: 30px;
}
.table_container h3 { 
    margin: 0;
    padding: 15px 20px;
    background: #f8f9fa;
    border-bottom: 1px solid #ddd;
}

.search_form { 
    background: #f8f9fa; 
    padding: 15px; 
    border-radius: 5px;
    margin-bottom: 20px;
}

/* Datepicker 스타일 커스터마이징 */
.ui-datepicker {
    font-size: 14px;
}
.ui-datepicker td {
    font-size: 13px;
}
</style>

<!-- 검색 폼 -->
<form class="search_form" method="get">
    <label>기간 선택</label>
    <input type="text" name="fr_date" value="<?php echo $fr_date ?>" id="fr_date" class="frm_input" size="11" maxlength="10" readonly>
    ~
    <input type="text" name="to_date" value="<?php echo $to_date ?>" id="to_date" class="frm_input" size="11" maxlength="10" readonly>
    <input type="submit" value="조회" class="btn btn_submit">
    <a href="./sms_log.php" class="btn btn_02">로그 목록</a>
    <a href="./sms_log_export.php?<?php echo http_build_query($_GET); ?>" class="btn btn_02">Excel 다운로드</a>
</form>

<!-- 전체 통계 -->
<div class="stat_box_wrap">
    <div class="stat_box primary">
        <h3>전체 발송</h3>
        <div class="value"><?php echo number_format($total_stat['total_cnt']); ?></div>
    </div>
    <div class="stat_box success">
        <h3>성공</h3>
        <div class="value"><?php echo number_format($total_stat['success_cnt']); ?></div>
    </div>
    <div class="stat_box danger">
        <h3>실패</h3>
        <div class="value"><?php echo number_format($total_stat['fail_cnt']); ?></div>
    </div>
    <div class="stat_box">
        <h3>성공률</h3>
        <div class="value"><?php echo $success_rate; ?>%</div>
    </div>
</div>

<!-- 시간대별 차트 -->
<div class="chart_container">
    <h3>시간대별 발송 현황</h3>
    <canvas id="hourlyChart" height="100"></canvas>
</div>

<!-- 일별 통계 테이블 -->
<div class="table_container">
    <h3>일별 발송 통계</h3>
    <div class="tbl_head01 tbl_wrap">
        <table>
        <thead>
        <tr>
            <th>날짜</th>
            <th>전체</th>
            <th>성공</th>
            <th>실패</th>
            <th>성공률</th>
        </tr>
        </thead>
        <tbody>
        <?php 
        while ($row = sql_fetch_array($daily_result)) {
            $daily_rate = $row['cnt'] > 0 ? round(($row['success_cnt'] / $row['cnt']) * 100, 1) : 0;
        ?>
        <tr>
            <td class="td_datetime"><?php echo $row['date']; ?></td>
            <td class="td_num"><?php echo number_format($row['cnt']); ?></td>
            <td class="td_num txt_true"><?php echo number_format($row['success_cnt']); ?></td>
            <td class="td_num txt_false"><?php echo number_format($row['fail_cnt']); ?></td>
            <td class="td_num"><?php echo $daily_rate; ?>%</td>
        </tr>
        <?php } ?>
        </tbody>
        </table>
    </div>
</div>

<!-- 타입별 통계 -->
<div class="table_container">
    <h3>타입별 발송 통계</h3>
    <div class="tbl_head01 tbl_wrap">
        <table>
        <thead>
        <tr>
            <th>타입</th>
            <th>전체</th>
            <th>성공</th>
            <th>실패</th>
            <th>성공률</th>
        </tr>
        </thead>
        <tbody>
        <?php 
        while ($row = sql_fetch_array($type_result)) {
            $type_rate = $row['cnt'] > 0 ? round(($row['success_cnt'] / $row['cnt']) * 100, 1) : 0;
            
            $type_text = '';
            switch($row['sl_type']) {
                case 'register': $type_text = '회원가입'; break;
                case 'password': $type_text = '비밀번호찾기'; break;
                case 'manual': $type_text = '수동발송'; break;
                case 'test': $type_text = '테스트'; break;
                default: $type_text = $row['sl_type']; break;
            }
        ?>
        <tr>
            <td class="td_category"><?php echo $type_text; ?></td>
            <td class="td_num"><?php echo number_format($row['cnt']); ?></td>
            <td class="td_num txt_true"><?php echo number_format($row['success_cnt']); ?></td>
            <td class="td_num txt_false"><?php echo number_format($row['fail_cnt']); ?></td>
            <td class="td_num"><?php echo $type_rate; ?>%</td>
        </tr>
        <?php } ?>
        </tbody>
        </table>
    </div>
</div>

<!-- 에러 통계 -->
<div class="table_container">
    <h3>주요 에러 코드</h3>
    <div class="tbl_head01 tbl_wrap">
        <table>
        <thead>
        <tr>
            <th>에러코드</th>
            <th>설명</th>
            <th>발생건수</th>
        </tr>
        </thead>
        <tbody>
        <?php 
        while ($row = sql_fetch_array($error_result)) {
            $error_msg = '';
            switch($row['sl_error_code']) {
                case '02': $error_msg = '형식 오류'; break;
                case '23': $error_msg = '데이터 오류'; break;
                case '97': $error_msg = '잔여코인 부족'; break;
                case '98': $error_msg = '사용기간 만료'; break;
                case '99': $error_msg = '인증 실패'; break;
                case 'BLOCKED': $error_msg = '차단된 번호'; break;
                case 'DAILY_LIMIT': $error_msg = '일일 제한 초과'; break;
                case 'HOURLY_LIMIT': $error_msg = '시간당 제한 초과'; break;
                default: $error_msg = '기타 오류'; break;
            }
        ?>
        <tr>
            <td class="td_category"><?php echo $row['sl_error_code']; ?></td>
            <td class="td_left"><?php echo $error_msg; ?></td>
            <td class="td_num"><?php echo number_format($row['cnt']); ?></td>
        </tr>
        <?php } ?>
        </tbody>
        </table>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
$(function(){
    // jQuery UI Datepicker 한국어 설정
    $.datepicker.setDefaults({
        dateFormat: 'yy-mm-dd',
        prevText: '이전 달',
        nextText: '다음 달',
        monthNames: ['1월','2월','3월','4월','5월','6월','7월','8월','9월','10월','11월','12월'],
        monthNamesShort: ['1월','2월','3월','4월','5월','6월','7월','8월','9월','10월','11월','12월'],
        dayNames: ['일','월','화','수','목','금','토'],
        dayNamesShort: ['일','월','화','수','목','금','토'],
        dayNamesMin: ['일','월','화','수','목','금','토'],
        showMonthAfterYear: true,
        yearSuffix: '년'
    });
    
    // 날짜 선택기
    $("#fr_date, #to_date").datepicker({ 
        changeMonth: true, 
        changeYear: true, 
        dateFormat: "yy-mm-dd", 
        showButtonPanel: true, 
        yearRange: "c-99:c+99", 
        maxDate: "+0d" 
    });
    
    // 시간대별 차트
    const ctx = document.getElementById('hourlyChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($hourly_labels); ?>,
            datasets: [{
                label: '전체 발송',
                data: <?php echo json_encode(array_values($hourly_data)); ?>,
                borderColor: 'rgb(75, 192, 192)',
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                tension: 0.1
            }, {
                label: '성공',
                data: <?php echo json_encode(array_values($hourly_success)); ?>,
                borderColor: 'rgb(54, 162, 235)',
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
});
</script>

<?php
include_once('./admin.tail.php');
?>