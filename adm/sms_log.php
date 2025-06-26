<?php
/*
 * 파일명: sms_log.php
 * 위치: /adm/sms_log.php
 * 기능: SMS 발송 로그 관리 (상세 버전)
 * 작성일: 2024-12-28
 * 수정일: 2024-12-28
 */

$sub_menu = "900930";
include_once('./_common.php');

if ($is_admin != 'super')
    alert('최고관리자만 접근 가능합니다.');

// 테이블명 정의
$g5['sms_log_table'] = G5_TABLE_PREFIX.'sms_log';

// ===================================
// DB 테이블 존재 확인
// ===================================
$sql = " SHOW TABLES LIKE '{$g5['sms_log_table']}' ";
$result = sql_query($sql, false);
if(!sql_num_rows($result)) {
    alert('SMS 인증 시스템이 설치되지 않았습니다.\\n\\n설치 페이지로 이동합니다.', './sms_install.php');
}

$g5['title'] = 'SMS 발송 로그';
include_once('./admin.head.php');

// ===================================
// 검색 조건 처리
// ===================================
$sql_search = "";
$search_items = array();

// 검색어
if ($stx) {
    $search_items[] = " (sl_phone like '%{$stx}%' or mb_id like '%{$stx}%' or sl_message like '%{$stx}%') ";
}

// 발송 타입
if ($sfl) {
    $search_items[] = " sl_type = '{$sfl}' ";
}

// 발송 결과
if ($sl_result) {
    $search_items[] = " sl_result = '{$sl_result}' ";
}

// 날짜 검색
if ($fr_date && $to_date) {
    $search_items[] = " sl_datetime between '{$fr_date} 00:00:00' and '{$to_date} 23:59:59' ";
} else if ($fr_date && !$to_date) {
    $search_items[] = " sl_datetime >= '{$fr_date} 00:00:00' ";
} else if (!$fr_date && $to_date) {
    $search_items[] = " sl_datetime <= '{$to_date} 23:59:59' ";
}

if (count($search_items) > 0) {
    $sql_search = " where " . implode(" and ", $search_items);
}

// ===================================
// 통계 데이터 조회
// ===================================
$sql = " select 
            count(*) as total_cnt,
            sum(case when sl_result = 'success' then 1 else 0 end) as success_cnt,
            sum(case when sl_result = 'fail' then 1 else 0 end) as fail_cnt,
            sum(case when sl_datetime >= DATE_SUB(NOW(), INTERVAL 1 DAY) then 1 else 0 end) as today_cnt
         from {$g5['sms_log_table']}
         $sql_search ";
$stat = sql_fetch($sql);

// ===================================
// 페이징 처리
// ===================================
$rows = $config['cf_page_rows'];
$total_count = $stat['total_cnt'];
$total_page  = ceil($total_count / $rows);
if ($page < 1) $page = 1;
$from_record = ($page - 1) * $rows;

$listall = '<a href="'.$_SERVER['SCRIPT_NAME'].'" class="ov_listall">전체목록</a>';

// ===================================
// 리스트 조회
// ===================================
$sql = " select *
         from {$g5['sms_log_table']}
         $sql_search
         order by sl_id desc
         limit $from_record, $rows ";
$result = sql_query($sql);

$colspan = 11;
?>

<style>
/* SMS 로그 전용 스타일 */
.sms_log_stats { margin: 20px 0; }
.sms_log_stats .stat_box { 
    display: inline-block; 
    padding: 15px 30px; 
    margin-right: 10px;
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 5px;
}
.sms_log_stats .stat_box strong { 
    display: block; 
    font-size: 24px; 
    color: #333;
    margin-top: 5px;
}
.sms_log_stats .success { color: #28a745; }
.sms_log_stats .fail { color: #dc3545; }
.sms_log_stats .today { color: #007bff; }

.search_form { 
    background: #f8f9fa; 
    padding: 20px; 
    border-radius: 5px;
    margin: 20px 0;
}
.search_form table { width: 100%; }
.search_form th { 
    width: 120px; 
    padding: 10px;
    text-align: left;
    font-weight: bold;
}
.search_form td { padding: 5px 10px; }

.log_detail { 
    background: #f8f9fa; 
    padding: 5px 10px; 
    border-radius: 3px;
    font-size: 11px;
    line-height: 1.4;
}
.api_response {
    max-width: 300px;
    max-height: 100px;
    overflow: auto;
    background: #fff;
    border: 1px solid #ddd;
    padding: 5px;
    margin-top: 5px;
    font-size: 11px;
}
</style>

<!-- 통계 정보 -->
<div class="sms_log_stats">
    <div class="stat_box">
        <span>전체 발송</span>
        <strong><?php echo number_format($stat['total_cnt']); ?>건</strong>
    </div>
    <div class="stat_box">
        <span>성공</span>
        <strong class="success"><?php echo number_format($stat['success_cnt']); ?>건</strong>
    </div>
    <div class="stat_box">
        <span>실패</span>
        <strong class="fail"><?php echo number_format($stat['fail_cnt']); ?>건</strong>
    </div>
    <div class="stat_box">
        <span>최근 24시간</span>
        <strong class="today"><?php echo number_format($stat['today_cnt']); ?>건</strong>
    </div>
</div>

<!-- 검색 폼 -->
<form id="fsearch" name="fsearch" class="search_form" method="get">
<table>
<tbody>
<tr>
    <th scope="row">검색어</th>
    <td>
        <input type="text" name="stx" value="<?php echo $stx ?>" id="stx" class="frm_input" size="30" placeholder="전화번호, 회원ID, 메시지">
    </td>
    <th scope="row">발송타입</th>
    <td>
        <select name="sfl">
            <option value="">전체</option>
            <option value="register" <?php echo get_selected($sfl, 'register'); ?>>회원가입</option>
            <option value="password" <?php echo get_selected($sfl, 'password'); ?>>비밀번호찾기</option>
            <option value="manual" <?php echo get_selected($sfl, 'manual'); ?>>수동발송</option>
            <option value="test" <?php echo get_selected($sfl, 'test'); ?>>테스트</option>
        </select>
    </td>
</tr>
<tr>
    <th scope="row">발송결과</th>
    <td>
        <select name="sl_result">
            <option value="">전체</option>
            <option value="success" <?php echo get_selected($sl_result, 'success'); ?>>성공</option>
            <option value="fail" <?php echo get_selected($sl_result, 'fail'); ?>>실패</option>
        </select>
    </td>
    <th scope="row">기간검색</th>
    <td>
        <input type="text" name="fr_date" value="<?php echo $fr_date ?>" id="fr_date" class="frm_input" size="11" maxlength="10">
        <label for="fr_date" class="sound_only">시작일</label>
        ~
        <input type="text" name="to_date" value="<?php echo $to_date ?>" id="to_date" class="frm_input" size="11" maxlength="10">
        <label for="to_date" class="sound_only">종료일</label>
    </td>
</tr>
</tbody>
</table>
<div class="btn_confirm01 btn_confirm">
    <input type="submit" value="검색" class="btn_submit">
    <input type="button" value="초기화" class="btn_frmline" onclick="location.href='<?php echo $_SERVER['SCRIPT_NAME']; ?>'">
</div>
</form>

<div class="local_ov01 local_ov">
    <?php echo $listall ?>
    <span class="btn_ov01"><span class="ov_txt">검색결과</span><span class="ov_num"> <?php echo number_format($total_count) ?>건</span></span>
    <div style="float:right;">
        <a href="./sms_log_stats.php" class="btn btn_02">통계 보기</a>
        <a href="./sms_log_export.php?<?php echo http_build_query($_GET); ?>" class="btn btn_02">Excel 다운로드</a>
    </div>
</div>

<div class="tbl_head01 tbl_wrap">
    <table>
    <caption><?php echo $g5['title']; ?> 목록</caption>
    <thead>
    <tr>
        <th scope="col">번호</th>
        <th scope="col">발송일시</th>
        <th scope="col">타입</th>
        <th scope="col">회원ID</th>
        <th scope="col">수신번호</th>
        <th scope="col">발신번호</th>
        <th scope="col">메시지</th>
        <th scope="col">결과</th>
        <th scope="col">API응답</th>
        <th scope="col">재시도</th>
        <th scope="col">IP</th>
    </tr>
    </thead>
    <tbody>
    <?php
    for ($i=0; $row=sql_fetch_array($result); $i++) {
        $num = $total_count - ($page - 1) * $rows - $i;
        
        // 타입별 표시
        $type_text = '';
        $type_class = '';
        switch($row['sl_type']) {
            case 'register': 
                $type_text = '회원가입'; 
                $type_class = 'txt_active';
                break;
            case 'password': 
                $type_text = '비밀번호찾기'; 
                $type_class = 'txt_done';
                break;
            case 'manual': 
                $type_text = '수동발송'; 
                $type_class = '';
                break;
            case 'test': 
                $type_text = '테스트'; 
                $type_class = 'txt_rdy';
                break;
            default: 
                $type_text = $row['sl_type']; 
                break;
        }
        
        // 결과 표시
        $result_class = ($row['sl_result'] == 'success') ? 'txt_true' : 'txt_false';
        $result_text = ($row['sl_result'] == 'success') ? '성공' : '실패';
        
        // API 응답 파싱
        $api_response = '';
        $error_code = '';
        $error_msg = '';
        
        if($row['sl_api_response']) {
            $api_data = @unserialize($row['sl_api_response']);
            if(!$api_data) {
                $api_data = @json_decode($row['sl_api_response'], true);
            }
            
            if(is_array($api_data)) {
                if(isset($api_data['code'])) $error_code = $api_data['code'];
                if(isset($api_data['message'])) $error_msg = $api_data['message'];
                if(isset($api_data['error'])) $error_msg = $api_data['error'];
            } else {
                $api_response = $row['sl_api_response'];
            }
        }
        
        $bg_class = ($i % 2) ? 'bg1' : 'bg0';
    ?>
    <tr class="<?php echo $bg_class; ?>">
        <td class="td_num"><?php echo $num; ?></td>
        <td class="td_datetime"><?php echo substr($row['sl_datetime'], 0, 16); ?></td>
        <td class="td_category <?php echo $type_class; ?>"><?php echo $type_text; ?></td>
        <td class="td_id">
            <?php if($row['mb_id']) { ?>
                <a href="<?php echo G5_ADMIN_URL; ?>/member_form.php?w=u&mb_id=<?php echo $row['mb_id']; ?>"><?php echo $row['mb_id']; ?></a>
            <?php } else { ?>
                <span style="color:#999;">비회원</span>
            <?php } ?>
        </td>
        <td class="td_tel"><?php echo $row['sl_phone']; ?></td>
        <td class="td_tel"><?php echo $row['sl_send_number'] ? $row['sl_send_number'] : '-'; ?></td>
        <td class="td_left">
            <div style="max-width:200px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                <?php echo htmlspecialchars($row['sl_message']); ?>
            </div>
        </td>
        <td class="td_boolean">
            <span class="<?php echo $result_class; ?>"><?php echo $result_text; ?></span>
            <?php if($row['sl_error_code']) { ?>
            <div class="log_detail">
                코드: <?php echo $row['sl_error_code']; ?>
            </div>
            <?php } ?>
        </td>
        <td class="td_left">
            <?php if($error_code || $error_msg || $api_response) { ?>
            <div class="api_response">
                <?php if($error_code) echo "코드: {$error_code}<br>"; ?>
                <?php if($error_msg) echo "메시지: {$error_msg}<br>"; ?>
                <?php if($api_response) echo htmlspecialchars(substr($api_response, 0, 100)); ?>
            </div>
            <?php } else { ?>
                -
            <?php } ?>
        </td>
        <td class="td_num"><?php echo $row['sl_retry_count'] > 0 ? $row['sl_retry_count'].'회' : '0회'; ?></td>
        <td class="td_left"><?php echo $row['sl_ip']; ?></td>
    </tr>
    <?php
    }
    
    if ($i == 0)
        echo '<tr><td colspan="'.$colspan.'" class="empty_table">자료가 없습니다.</td></tr>';
    ?>
    </tbody>
    </table>
</div>

<?php echo get_paging(G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'], $page, $total_page, '?'.$qstr.'&amp;page='); ?>

<script>
$(function(){
    $("#fr_date, #to_date").datepicker({ changeMonth: true, changeYear: true, dateFormat: "yy-mm-dd", showButtonPanel: true, yearRange: "c-99:c+99", maxDate: "+0d" });
});
</script>

<?php
include_once('./admin.tail.php');
?>