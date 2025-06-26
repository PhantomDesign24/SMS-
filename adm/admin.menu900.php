<?php
/*
 * 파일명: admin.menu900.php
 * 위치: /adm/admin.menu900.php
 * 기능: SMS 관리 메뉴
 * 작성일: 2024-12-28
 * 수정일: 2024-12-29
 */
if (!defined('_GNUBOARD_')) exit;

// SMS5 플러그인이 있는 경우 기존 메뉴와 통합
if(defined('G5_SMS5_PATH') && is_dir(G5_SMS5_PATH)) {
    $menu['menu900'] = array (
        array('900000', 'SMS 관리', G5_SMS5_ADMIN_URL.'/config.php', 'sms5'),
        array('900100', 'SMS 기본설정', G5_SMS5_ADMIN_URL.'/config.php', 'sms5_config'),
        array('900200', '문자 보내기', G5_SMS5_ADMIN_URL.'/sms_write.php', 'sms5_write'),
        array('900300', '전송내역-건별', G5_SMS5_ADMIN_URL.'/history_list.php', 'sms5_history'),
        array('900400', '전송내역-번호별', G5_SMS5_ADMIN_URL.'/history_num.php', 'sms5_history_num'),
        array('900500', '휴대폰번호 그룹', G5_SMS5_ADMIN_URL.'/num_group_list.php', 'sms5_group'),
        array('900600', '휴대폰번호 관리', G5_SMS5_ADMIN_URL.'/num_book.php', 'sms5_num_book'),
        array('900700', '휴대폰번호 파일', G5_SMS5_ADMIN_URL.'/num_book_file.php', 'sms5_num_book_file'),
        array('900800', '이모티콘 그룹', G5_SMS5_ADMIN_URL.'/form_group.php', 'sms5_form_group'),
        array('900900', '이모티콘 관리', G5_SMS5_ADMIN_URL.'/form_list.php', 'sms5_form_list'),
        array('', '', '', ''),  // 구분선
        array('900910', '<b>【SMS 인증】</b>', '', ''),  // 섹션 타이틀
        array('900920', '├ 인증설정', G5_ADMIN_URL.'/sms_config.php', 'sms_auth_config'),
        array('900930', '├ 발송로그', G5_ADMIN_URL.'/sms_log.php', 'sms_auth_log'),
        array('900940', '├ 발송통계', G5_ADMIN_URL.'/sms_log_stats.php', 'sms_log_stats'),
        array('900950', '└ 차단번호', G5_ADMIN_URL.'/sms_blacklist.php', 'sms_auth_blacklist')
    );
} else {
    // SMS5가 없는 경우 SMS 인증만 표시
    $menu['menu900'] = array (
        array('900000', 'SMS 관리', G5_ADMIN_URL.'/sms_config.php', 'sms_auth'),
        array('900100', 'SMS 인증설정', G5_ADMIN_URL.'/sms_config.php', 'sms_auth_config'),
        array('900200', 'SMS 발송로그', G5_ADMIN_URL.'/sms_log.php', 'sms_auth_log'),
        array('900300', 'SMS 발송통계', G5_ADMIN_URL.'/sms_log_stats.php', 'sms_log_stats'),
        array('900400', 'SMS 차단번호', G5_ADMIN_URL.'/sms_blacklist.php', 'sms_auth_blacklist')
    );
}
?>