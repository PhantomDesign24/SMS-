<?php
/*
 * 파일명: sms_config_update.php
 * 위치: /adm/sms_config_update.php
 * 기능: SMS 설정 저장 처리
 * 작성일: 2024-12-28
 */

$sub_menu = "900920";
include_once('./_common.php');

check_demo();

if ($is_admin != 'super')
    alert('최고관리자만 접근 가능합니다.');

check_admin_token();

// 테이블명 정의
$g5['sms_config_table'] = G5_TABLE_PREFIX.'sms_config';

// 데이터 정리
$cf_service = isset($_POST['cf_service']) ? clean_xss_tags($_POST['cf_service']) : 'icode';
$cf_icode_id = isset($_POST['cf_icode_id']) ? clean_xss_tags($_POST['cf_icode_id']) : '';
$cf_icode_pw = isset($_POST['cf_icode_pw']) ? clean_xss_tags($_POST['cf_icode_pw']) : '';
$cf_aligo_key = isset($_POST['cf_aligo_key']) ? clean_xss_tags($_POST['cf_aligo_key']) : '';
$cf_aligo_userid = isset($_POST['cf_aligo_userid']) ? clean_xss_tags($_POST['cf_aligo_userid']) : '';
$cf_phone = isset($_POST['cf_phone']) ? preg_replace('/[^0-9]/', '', $_POST['cf_phone']) : '';
$cf_daily_limit = isset($_POST['cf_daily_limit']) ? (int)$_POST['cf_daily_limit'] : 5;
$cf_hourly_limit = isset($_POST['cf_hourly_limit']) ? (int)$_POST['cf_hourly_limit'] : 3;
$cf_resend_delay = isset($_POST['cf_resend_delay']) ? (int)$_POST['cf_resend_delay'] : 60;
$cf_ip_daily_limit = isset($_POST['cf_ip_daily_limit']) ? (int)$_POST['cf_ip_daily_limit'] : 20;
$cf_auth_timeout = isset($_POST['cf_auth_timeout']) ? (int)$_POST['cf_auth_timeout'] : 180;
$cf_max_try = isset($_POST['cf_max_try']) ? (int)$_POST['cf_max_try'] : 5;
$cf_use_captcha = isset($_POST['cf_use_captcha']) ? (int)$_POST['cf_use_captcha'] : 0;
$cf_captcha_count = isset($_POST['cf_captcha_count']) ? (int)$_POST['cf_captcha_count'] : 3;
$cf_block_foreign = isset($_POST['cf_block_foreign']) ? (int)$_POST['cf_block_foreign'] : 1;
$cf_use_blacklist = isset($_POST['cf_use_blacklist']) ? (int)$_POST['cf_use_blacklist'] : 1;
$cf_use_register = isset($_POST['cf_use_register']) ? (int)$_POST['cf_use_register'] : 1;
$cf_use_password = isset($_POST['cf_use_password']) ? (int)$_POST['cf_use_password'] : 1;

// 유효성 검사
if($cf_daily_limit < 1 || $cf_daily_limit > 100) $cf_daily_limit = 5;
if($cf_hourly_limit < 1 || $cf_hourly_limit > 50) $cf_hourly_limit = 3;
if($cf_resend_delay < 30 || $cf_resend_delay > 600) $cf_resend_delay = 60;
if($cf_ip_daily_limit < 1 || $cf_ip_daily_limit > 200) $cf_ip_daily_limit = 20;
if($cf_auth_timeout < 60 || $cf_auth_timeout > 600) $cf_auth_timeout = 180;
if($cf_max_try < 3 || $cf_max_try > 10) $cf_max_try = 5;
if($cf_captcha_count < 1 || $cf_captcha_count > 10) $cf_captcha_count = 3;

// 기존 설정 확인
$sql = " select count(*) as cnt from {$g5['sms_config_table']} ";
$row = sql_fetch($sql);

if($row['cnt'] > 0) {
    // 업데이트
    $sql = " update {$g5['sms_config_table']} set
            cf_service = '".sql_real_escape_string($cf_service)."',
            cf_icode_id = '".sql_real_escape_string($cf_icode_id)."',
            cf_icode_pw = '".sql_real_escape_string($cf_icode_pw)."',
            cf_aligo_key = '".sql_real_escape_string($cf_aligo_key)."',
            cf_aligo_userid = '".sql_real_escape_string($cf_aligo_userid)."',
            cf_phone = '".sql_real_escape_string($cf_phone)."',
            cf_daily_limit = '".sql_real_escape_string($cf_daily_limit)."',
            cf_hourly_limit = '".sql_real_escape_string($cf_hourly_limit)."',
            cf_resend_delay = '".sql_real_escape_string($cf_resend_delay)."',
            cf_ip_daily_limit = '".sql_real_escape_string($cf_ip_daily_limit)."',
            cf_auth_timeout = '".sql_real_escape_string($cf_auth_timeout)."',
            cf_max_try = '".sql_real_escape_string($cf_max_try)."',
            cf_use_captcha = '".sql_real_escape_string($cf_use_captcha)."',
            cf_captcha_count = '".sql_real_escape_string($cf_captcha_count)."',
            cf_block_foreign = '".sql_real_escape_string($cf_block_foreign)."',
            cf_use_blacklist = '".sql_real_escape_string($cf_use_blacklist)."',
            cf_use_register = '".sql_real_escape_string($cf_use_register)."',
            cf_use_password = '".sql_real_escape_string($cf_use_password)."'
            ";
} else {
    // 삽입
    $sql = " insert into {$g5['sms_config_table']} set
            cf_service = '".sql_real_escape_string($cf_service)."',
            cf_icode_id = '".sql_real_escape_string($cf_icode_id)."',
            cf_icode_pw = '".sql_real_escape_string($cf_icode_pw)."',
            cf_aligo_key = '".sql_real_escape_string($cf_aligo_key)."',
            cf_aligo_userid = '".sql_real_escape_string($cf_aligo_userid)."',
            cf_phone = '".sql_real_escape_string($cf_phone)."',
            cf_daily_limit = '".sql_real_escape_string($cf_daily_limit)."',
            cf_hourly_limit = '".sql_real_escape_string($cf_hourly_limit)."',
            cf_resend_delay = '".sql_real_escape_string($cf_resend_delay)."',
            cf_ip_daily_limit = '".sql_real_escape_string($cf_ip_daily_limit)."',
            cf_auth_timeout = '".sql_real_escape_string($cf_auth_timeout)."',
            cf_max_try = '".sql_real_escape_string($cf_max_try)."',
            cf_use_captcha = '".sql_real_escape_string($cf_use_captcha)."',
            cf_captcha_count = '".sql_real_escape_string($cf_captcha_count)."',
            cf_block_foreign = '".sql_real_escape_string($cf_block_foreign)."',
            cf_use_blacklist = '".sql_real_escape_string($cf_use_blacklist)."',
            cf_use_register = '".sql_real_escape_string($cf_use_register)."',
            cf_use_password = '".sql_real_escape_string($cf_use_password)."'
            ";
}

sql_query($sql);

goto_url('./sms_config.php', false);
?>