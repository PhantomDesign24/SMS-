<?php
/*
 * 파일명: sms_log.php
 * 위치: /adm/sms_log.php
 * 기능: SMS 발송 로그 관리
 * 작성일: 2024-12-28
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

// 검색 조건
$sql_search = "";

$search_sql = "";
if ($stx) {
    $search_sql = " (sl_phone like '%$stx%' or mb_id like '%$stx%') ";
}

if ($search_sql) {
    $sql_search = " where $search_sql ";
}

$sql = " select count(*) as cnt
         from {$g5['sms_log_table']}
         $sql_search ";
$row = sql_fetch($sql);
$total_count = $row['cnt'];

$rows = $config['cf_page_rows'];
$total_page  = ceil($total_count / $rows);  // 전체 페이지 계산
if ($page < 1) $page = 1; // 페이지가 없으면 첫 페이지 (1 페이지)
$from_record = ($page - 1) * $rows; // 시작 열을 구함

$listall = '<a href="'.$_SERVER['SCRIPT_NAME'].'" class="ov_listall">전체목록</a>';

$sql = " select *
         from {$g5['sms_log_table']}
         $sql_search
         order by sl_id desc
         limit $from_record, $rows ";
$result = sql_query($sql);

$colspan = 8;
?>

<div class="local_ov01 local_ov">
    <?php echo $listall ?>
    <span class="btn_ov01"><span class="ov_txt">전체</span><span class="ov_num"> <?php echo number_format($total_count) ?>건</span></span>
</div>

<form id="fsearch" name="fsearch" class="local_sch01 local_sch" method="get">
<label for="stx" class="sound_only">검색어<strong class="sound_only"> 필수</strong></label>
<input type="text" name="stx" value="<?php echo $stx ?>" id="stx" class="frm_input">
<input type="submit" class="btn_submit" value="검색">
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
        <td class="td_left"><?php echo $row['sl_ip']; ?></td>
    </tr>
    <?php
    }
    
    if ($i == 0)
        echo "<tr><td colspan=\"".$colspan."\" class=\"empty_table\">자료가 없습니다.</td></tr>";
    ?>
    </tbody>
    </table>
</div>

<?php echo get_paging(G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'], $page, $total_page, '?'.$qstr.'&amp;page='); ?>

<div class="btn_fixed_top">
    <a href="./sms_config.php" class="btn btn_02">SMS설정</a>
</div>

<?php
include_once('./admin.tail.php');
?>