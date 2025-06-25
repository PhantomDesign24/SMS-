<?php
/*
 * 파일명: sms_blacklist.php
 * 위치: /adm/sms_blacklist.php
 * 기능: SMS 차단번호 관리
 * 작성일: 2024-12-28
 */

$sub_menu = "900940";
include_once('./_common.php');

if ($is_admin != 'super')
    alert('최고관리자만 접근 가능합니다.');

// 테이블명 정의
$g5['sms_blacklist_table'] = G5_TABLE_PREFIX.'sms_blacklist';

// ===================================
// DB 테이블 존재 확인
// ===================================
$sql = " SHOW TABLES LIKE '{$g5['sms_blacklist_table']}' ";
$result = sql_query($sql, false);
if(!sql_num_rows($result)) {
    alert('SMS 인증 시스템이 설치되지 않았습니다.\\n\\n설치 페이지로 이동합니다.', './sms_install.php');
}

$g5['title'] = 'SMS 차단번호 관리';
include_once('./admin.head.php');

// 차단번호 추가
if($w == 'u' && $sb_phone) {
    check_admin_token();
    
    $phone = preg_replace('/[^0-9]/', '', $sb_phone);
    $reason = clean_xss_tags($sb_reason);
    
    if(validate_phone_number($phone)) {
        // 중복 체크
        $sql = " select count(*) as cnt from {$g5['sms_blacklist_table']} where sb_phone = '".sql_real_escape_string($phone)."' ";
        $row = sql_fetch($sql);
        
        if($row['cnt'] == 0) {
            $sql = " insert into {$g5['sms_blacklist_table']} set
                    sb_phone = '".sql_real_escape_string($phone)."',
                    sb_reason = '".sql_real_escape_string($reason)."',
                    sb_datetime = '".G5_TIME_YMDHIS."' ";
            sql_query($sql);
            
            alert('차단번호가 추가되었습니다.', './sms_blacklist.php');
        } else {
            alert('이미 등록된 번호입니다.');
        }
    } else {
        alert('올바른 전화번호 형식이 아닙니다.');
    }
}

// 차단번호 삭제
if($w == 'd' && $sb_id) {
    check_admin_token();
    
    $sql = " delete from {$g5['sms_blacklist_table']} where sb_id = '".sql_real_escape_string($sb_id)."' ";
    sql_query($sql);
    
    goto_url('./sms_blacklist.php?'.$qstr);
}

// 선택 삭제
if($act_button == '선택삭제') {
    check_admin_token();
    
    for($i=0; $i<count($chk); $i++) {
        $k = $chk[$i];
        $sb_id = $sb_id_array[$k];
        
        $sql = " delete from {$g5['sms_blacklist_table']} where sb_id = '".sql_real_escape_string($sb_id)."' ";
        sql_query($sql);
    }
    
    goto_url('./sms_blacklist.php?'.$qstr);
}

// 검색 조건
$sql_search = "";

$search_sql = "";
if ($stx) {
    $search_sql = " (sb_phone like '%$stx%' or sb_reason like '%$stx%') ";
}

if ($search_sql) {
    $sql_search = " where $search_sql ";
}

$sql = " select count(*) as cnt
         from {$g5['sms_blacklist_table']}
         $sql_search ";
$row = sql_fetch($sql);
$total_count = $row['cnt'];

$rows = $config['cf_page_rows'];
$total_page  = ceil($total_count / $rows);  // 전체 페이지 계산
if ($page < 1) $page = 1; // 페이지가 없으면 첫 페이지 (1 페이지)
$from_record = ($page - 1) * $rows; // 시작 열을 구함

$listall = '<a href="'.$_SERVER['SCRIPT_NAME'].'" class="ov_listall">전체목록</a>';

$sql = " select *
         from {$g5['sms_blacklist_table']}
         $sql_search
         order by sb_id desc
         limit $from_record, $rows ";
$result = sql_query($sql);

$colspan = 6;

$admin_token = get_admin_token();
?>

<div class="local_ov01 local_ov">
    <?php echo $listall ?>
    <span class="btn_ov01"><span class="ov_txt">전체 차단번호</span><span class="ov_num"> <?php echo number_format($total_count) ?>건</span></span>
</div>

<form id="fsearch" name="fsearch" class="local_sch01 local_sch" method="get">
<label for="stx" class="sound_only">검색어<strong class="sound_only"> 필수</strong></label>
<input type="text" name="stx" value="<?php echo $stx ?>" id="stx" class="frm_input" placeholder="전화번호, 차단사유">
<input type="submit" class="btn_submit" value="검색">
</form>

<div class="local_desc01 local_desc">
    <p>
        차단된 번호는 SMS 인증을 받을 수 없습니다.<br>
        전화번호는 하이픈(-) 없이 숫자만 입력하세요.
    </p>
</div>

<form name="fblacklist" id="fblacklist" action="./sms_blacklist.php" method="post">
<input type="hidden" name="page" value="<?php echo $page; ?>">
<input type="hidden" name="stx" value="<?php echo $stx; ?>">
<input type="hidden" name="token" value="<?php echo $admin_token ?>">

<div class="tbl_head01 tbl_wrap">
    <table>
    <caption><?php echo $g5['title']; ?> 목록</caption>
    <thead>
    <tr>
        <th scope="col">
            <label for="chkall" class="sound_only">차단번호 전체</label>
            <input type="checkbox" name="chkall" value="1" id="chkall" onclick="check_all(this.form)">
        </th>
        <th scope="col">번호</th>
        <th scope="col">전화번호</th>
        <th scope="col">차단사유</th>
        <th scope="col">차단일시</th>
        <th scope="col">관리</th>
    </tr>
    </thead>
    <tbody>
    <?php
    for ($i=0; $row=sql_fetch_array($result); $i++) {
        $num = $total_count - ($page - 1) * $rows - $i;
        $bg = 'bg'.($i%2);
    ?>
    <tr class="<?php echo $bg; ?>">
        <td class="td_chk">
            <input type="hidden" name="sb_id[<?php echo $i; ?>]" value="<?php echo $row['sb_id']; ?>" id="sb_id_<?php echo $i; ?>">
            <label for="chk_<?php echo $i; ?>" class="sound_only"><?php echo format_phone_number($row['sb_phone']); ?></label>
            <input type="checkbox" name="chk[]" value="<?php echo $i; ?>" id="chk_<?php echo $i; ?>">
        </td>
        <td class="td_num"><?php echo $num; ?></td>
        <td class="td_tel"><?php echo format_phone_number($row['sb_phone']); ?></td>
        <td class="td_left"><?php echo get_text($row['sb_reason']); ?></td>
        <td class="td_datetime"><?php echo $row['sb_datetime']; ?></td>
        <td class="td_mng">
            <a href="<?php echo $_SERVER['SCRIPT_NAME']; ?>?w=d&amp;sb_id=<?php echo $row['sb_id']; ?>&amp;<?php echo $qstr; ?>&amp;token=<?php echo $admin_token ?>" onclick="return confirm('정말 삭제하시겠습니까?');" class="btn btn_03">삭제</a>
        </td>
    </tr>
    <?php
    }
    
    if ($i == 0)
        echo '<tr><td colspan="'.$colspan.'" class="empty_table">차단된 번호가 없습니다.</td></tr>';
    ?>
    </tbody>
    </table>
</div>

<div class="btn_list01 btn_list">
    <input type="submit" name="act_button" value="선택삭제" onclick="document.pressed=this.value" class="btn btn_02">
</div>

</form>

<?php echo get_paging(G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'], $page, $total_page, '?'.$qstr.'&amp;page='); ?>

<section>
    <h2 class="h2_frm">차단번호 추가</h2>
    
    <form name="fblacklistadd" action="./sms_blacklist.php" method="post" onsubmit="return fblacklistadd_submit(this);">
    <input type="hidden" name="w" value="u">
    <input type="hidden" name="token" value="<?php echo $admin_token ?>">
    
    <div class="tbl_frm01 tbl_wrap">
        <table>
        <caption>차단번호 추가</caption>
        <colgroup>
            <col class="grid_4">
            <col>
        </colgroup>
        <tbody>
        <tr>
            <th scope="row"><label for="sb_phone">전화번호<strong class="sound_only">필수</strong></label></th>
            <td>
                <input type="text" name="sb_phone" id="sb_phone" required class="required frm_input" size="15" maxlength="11" placeholder="01012345678">
                <span class="frm_info">하이픈(-) 없이 숫자만 입력</span>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="sb_reason">차단사유</label></th>
            <td>
                <input type="text" name="sb_reason" id="sb_reason" class="frm_input" size="50" placeholder="스팸 발송 등">
            </td>
        </tr>
        </tbody>
        </table>
    </div>
    
    <div class="btn_confirm01 btn_confirm">
        <input type="submit" value="추가" class="btn_submit btn">
    </div>
    </form>
</section>

<script>
function fblacklistadd_submit(f) {
    if(!f.sb_phone.value) {
        alert('전화번호를 입력하세요.');
        f.sb_phone.focus();
        return false;
    }
    
    var phone = f.sb_phone.value.replace(/[^0-9]/g, '');
    if(!/^01[016789][0-9]{7,8}$/.test(phone)) {
        alert('올바른 휴대폰 번호 형식이 아닙니다.');
        f.sb_phone.focus();
        return false;
    }
    
    if(!f.sb_reason.value) {
        if(!confirm('차단사유를 입력하지 않았습니다.\n\n그대로 진행하시겠습니까?')) {
            f.sb_reason.focus();
            return false;
        }
    }
    
    return true;
}

$(function() {
    // 전화번호 입력 시 숫자만
    $('#sb_phone').on('input', function() {
        $(this).val($(this).val().replace(/[^0-9]/g, ''));
    });
});
</script>

<?php
include_once('./admin.tail.php');
?>