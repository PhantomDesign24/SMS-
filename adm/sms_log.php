<?php
/*
 * 파일명: sms_log.php
 * 위치: /adm/sms_log.php
 * 기능: SMS 발송 로그 관리
 * 작성일: 2024-12-28
 */

$sub_menu = "900200";
include_once('./_common.php');

auth_check($auth[$sub_menu], 'r');

// ===================================
// DB 테이블 존재 확인
// ===================================
$sql = "SHOW TABLES LIKE 'g5_sms_log'";
$result = sql_query($sql, false);
if(!sql_num_rows($result)) {
    // 테이블이 없으면 설치 페이지로 이동
    alert('SMS 인증 시스템이 설치되지 않았습니다.\\n\\n설치 페이지로 이동합니다.', './sms_install.php');
}

$g5['title'] = 'SMS 발송 로그';
include_once('./admin.head.php');

// 검색 조건
$sql_search = "";

if($stx) {
    $sql_search .= " and (sl_phone like '%".sql_real_escape_string($stx)."%' or mb_id like '%".sql_real_escape_string($stx)."%') ";
}

if($sfl) {
    if($sfl == 'success') {
        $sql_search .= " and sl_result = 'success' ";
    } else if($sfl == 'fail') {
        $sql_search .= " and sl_result = 'fail' ";
    } else if($sfl == 'register') {
        $sql_search .= " and sl_type = 'register' ";
    } else if($sfl == 'password') {
        $sql_search .= " and sl_type = 'password' ";
    }
}

// 날짜 검색
if($fr_date && $to_date) {
    $sql_search .= " and sl_datetime between '".sql_real_escape_string($fr_date)." 00:00:00' and '".sql_real_escape_string($to_date)." 23:59:59' ";
} else if($fr_date && !$to_date) {
    $sql_search .= " and sl_datetime >= '".sql_real_escape_string($fr_date)." 00:00:00' ";
} else if(!$fr_date && $to_date) {
    $sql_search .= " and sl_datetime <= '".sql_real_escape_string($to_date)." 23:59:59' ";
}

$sql_common = " from g5_sms_log ";
$sql_where = " where 1=1 $sql_search ";

// 전체 카운트
$sql = " select count(*) as cnt $sql_common $sql_where ";
$row = sql_fetch($sql);
$total_count = $row['cnt'];

$rows = $config['cf_page_rows'];
$total_page  = ceil($total_count / $rows);
if ($page < 1) $page = 1;
$from_record = ($page - 1) * $rows;

// 목록 조회
$sql = " select * $sql_common $sql_where order by sl_id desc limit $from_record, $rows ";
$result = sql_query($sql);

// 통계
$sql_stat = " select 
                count(*) as total_cnt,
                sum(case when sl_result='success' then 1 else 0 end) as success_cnt,
                sum(case when sl_result='fail' then 1 else 0 end) as fail_cnt,
                sum(case when sl_type='register' then 1 else 0 end) as register_cnt,
                sum(case when sl_type='password' then 1 else 0 end) as password_cnt
            from g5_sms_log 
            where 1=1 $sql_search ";
$stat = sql_fetch($sql_stat);

$listall = '<a href="'.$_SERVER['SCRIPT_NAME'].'" class="ov_listall">전체목록</a>';

$qstr .= "&amp;sfl=$sfl&amp;stx=$stx&amp;fr_date=$fr_date&amp;to_date=$to_date";
?>

<div class="local_ov01 local_ov">
    <?php echo $listall; ?>
    <span class="btn_ov01">
        <span class="ov_txt">전체</span>
        <span class="ov_num"><?php echo number_format($total_count); ?>건</span>
    </span>
    <span class="btn_ov01">
        <span class="ov_txt">성공</span>
        <span class="ov_num"><?php echo number_format($stat['success_cnt']); ?>건</span>
    </span>
    <span class="btn_ov01">
        <span class="ov_txt">실패</span>
        <span class="ov_num"><?php echo number_format($stat['fail_cnt']); ?>건</span>
    </span>
</div>

<form id="fsearch" name="fsearch" class="local_sch01 local_sch" method="get">
<div class="sch_last">
    <strong>기간검색</strong>
    <input type="text" name="fr_date" value="<?php echo $fr_date; ?>" id="fr_date" class="frm_input" size="10" maxlength="10"> ~
    <input type="text" name="to_date" value="<?php echo $to_date; ?>" id="to_date" class="frm_input" size="10" maxlength="10">
    <button type="button" onclick="javascript:set_date('오늘');">오늘</button>
    <button type="button" onclick="javascript:set_date('어제');">어제</button>
    <button type="button" onclick="javascript:set_date('이번주');">이번주</button>
    <button type="button" onclick="javascript:set_date('이번달');">이번달</button>
    
    <select name="sfl" id="sfl">
        <option value="">전체</option>
        <option value="success" <?php echo get_selected($sfl, 'success'); ?>>성공</option>
        <option value="fail" <?php echo get_selected($sfl, 'fail'); ?>>실패</option>
        <option value="register" <?php echo get_selected($sfl, 'register'); ?>>회원가입</option>
        <option value="password" <?php echo get_selected($sfl, 'password'); ?>>비밀번호찾기</option>
    </select>
    
    <label for="stx" class="sound_only">검색어</label>
    <input type="text" name="stx" value="<?php echo $stx; ?>" id="stx" class="frm_input" placeholder="전화번호, 아이디">
    <input type="submit" value="검색" class="btn_submit">
</div>
</form>

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
        <th scope="col">메시지</th>
        <th scope="col">결과</th>
        <th scope="col">IP</th>
    </tr>
    </thead>
    <tbody>
    <?php
    for ($i=0; $row=sql_fetch_array($result); $i++) {
        $num = $total_count - ($page - 1) * $rows - $i;
        
        $type_text = '';
        switch($row['sl_type']) {
            case 'register': $type_text = '회원가입'; break;
            case 'password': $type_text = '비밀번호찾기'; break;
            default: $type_text = '기타'; break;
        }
        
        $result_class = ($row['sl_result'] == 'success') ? 'txt_true' : 'txt_false';
        $result_text = ($row['sl_result'] == 'success') ? '성공' : '실패';
        
        $bg = 'bg'.($i%2);
    ?>
    <tr class="<?php echo $bg; ?>">
        <td class="td_num"><?php echo $num; ?></td>
        <td class="td_datetime"><?php echo $row['sl_datetime']; ?></td>
        <td class="td_category"><?php echo $type_text; ?></td>
        <td class="td_mbid"><?php echo get_text($row['mb_id']); ?></td>
        <td class="td_tel"><?php echo format_phone_number($row['sl_phone']); ?></td>
        <td class="td_left"><?php echo get_text($row['sl_message']); ?></td>
        <td class="td_boolean"><span class="<?php echo $result_class; ?>"><?php echo $result_text; ?></span></td>
        <td class="td_ip"><?php echo $row['sl_ip']; ?></td>
    </tr>
    <?php
    }
    
    if ($i == 0) {
        echo '<tr><td colspan="8" class="empty_table">자료가 없습니다.</td></tr>';
    }
    ?>
    </tbody>
    </table>
</div>

<?php echo get_paging(G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'], $page, $total_page, $_SERVER['SCRIPT_NAME'].'?'.$qstr.'&amp;page='); ?>

<div class="btn_fixed_top">
    <a href="./sms_config.php" class="btn btn_02">SMS설정</a>
</div>

<script>
$(function(){
    $("#fr_date, #to_date").datepicker({ changeMonth: true, changeYear: true, dateFormat: "yy-mm-dd", showButtonPanel: true, yearRange: "c-99:c+99" });
});

function set_date(today) {
    <?php
    $date_term = date('w', G5_SERVER_TIME);
    $week_term = $date_term + 7;
    $last_term = strtotime(date('Y-m-01', G5_SERVER_TIME));
    ?>
    if (today == "오늘") {
        document.getElementById("fr_date").value = "<?php echo G5_TIME_YMD; ?>";
        document.getElementById("to_date").value = "<?php echo G5_TIME_YMD; ?>";
    } else if (today == "어제") {
        document.getElementById("fr_date").value = "<?php echo date('Y-m-d', G5_SERVER_TIME - 86400); ?>";
        document.getElementById("to_date").value = "<?php echo date('Y-m-d', G5_SERVER_TIME - 86400); ?>";
    } else if (today == "이번주") {
        document.getElementById("fr_date").value = "<?php echo date('Y-m-d', strtotime('-'.$date_term.' days', G5_SERVER_TIME)); ?>";
        document.getElementById("to_date").value = "<?php echo date('Y-m-d', G5_SERVER_TIME); ?>";
    } else if (today == "이번달") {
        document.getElementById("fr_date").value = "<?php echo date('Y-m-01', G5_SERVER_TIME); ?>";
        document.getElementById("to_date").value = "<?php echo date('Y-m-d', G5_SERVER_TIME); ?>";
    } else if (today == "지난주") {
        document.getElementById("fr_date").value = "<?php echo date('Y-m-d', strtotime('-'.$week_term.' days', G5_SERVER_TIME)); ?>";
        document.getElementById("to_date").value = "<?php echo date('Y-m-d', strtotime('-'.($week_term - 6).' days', G5_SERVER_TIME)); ?>";
    } else if (today == "지난달") {
        document.getElementById("fr_date").value = "<?php echo date('Y-m-01', strtotime('-1 Month', $last_term)); ?>";
        document.getElementById("to_date").value = "<?php echo date('Y-m-t', strtotime('-1 Month', $last_term)); ?>";
    }
}
</script>

<?php
include_once('./admin.tail.php');
?>