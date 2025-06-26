<?php
/*
 * 파일명: sms_config_update.php
 * 위치: /adm/sms_config_update.php
 * 기능: SMS 설정 저장 (비용 설정 추가)
 * 작성일: 2024-12-28
 * 수정일: 2024-12-29
 */

include_once('./_common.php');

if ($is_admin != 'super')
    alert('최고관리자만 접근 가능합니다.');

check_admin_token();

// 설정값 받기
$cf_use_sms = isset($_POST['cf_use_sms']) ? (int)$_POST['cf_use_sms'] : 0;
$cf_service = isset($_POST['cf_service']) ? clean_xss_tags($_POST['cf_service']) : 'icode';
$cf_phone = isset($_POST['cf_phone']) ? preg_replace('/[^0-9-]/', '', $_POST['cf_phone']) : '';
$cf_icode_id = isset($_POST['cf_icode_id']) ? clean_xss_tags($_POST['cf_icode_id']) : '';
$cf_icode_pw = isset($_POST['cf_icode_pw']) ? clean_xss_tags($_POST['cf_icode_pw']) : '';
$cf_icode_server_ip = isset($_POST['cf_icode_server_ip']) ? clean_xss_tags($_POST['cf_icode_server_ip']) : '211.172.232.124';
$cf_icode_server_port = isset($_POST['cf_icode_server_port']) ? clean_xss_tags($_POST['cf_icode_server_port']) : '7296';
$cf_aligo_key = isset($_POST['cf_aligo_key']) ? clean_xss_tags($_POST['cf_aligo_key']) : '';
$cf_aligo_userid = isset($_POST['cf_aligo_userid']) ? clean_xss_tags($_POST['cf_aligo_userid']) : '';
$cf_use_register = isset($_POST['cf_use_register']) ? (int)$_POST['cf_use_register'] : 0;
$cf_use_password = isset($_POST['cf_use_password']) ? (int)$_POST['cf_use_password'] : 0;
$cf_daily_limit = isset($_POST['cf_daily_limit']) ? (int)$_POST['cf_daily_limit'] : 5;
$cf_hourly_limit = isset($_POST['cf_hourly_limit']) ? (int)$_POST['cf_hourly_limit'] : 3;
$cf_auth_timeout = isset($_POST['cf_auth_timeout']) ? (int)$_POST['cf_auth_timeout'] : 180;

// 비용 설정 추가
$cf_cost_type = isset($_POST['cf_cost_type']) ? clean_xss_tags($_POST['cf_cost_type']) : 'count';
$cf_cost_per_sms = isset($_POST['cf_cost_per_sms']) ? (float)$_POST['cf_cost_per_sms'] : 0;
$cf_remaining_sms = isset($_POST['cf_remaining_sms']) ? (int)$_POST['cf_remaining_sms'] : 0;
$cf_monthly_cost = isset($_POST['cf_monthly_cost']) ? (float)$_POST['cf_monthly_cost'] : 0;

// 기존 설정 확인
$sql = "SELECT COUNT(*) as cnt FROM g5_sms_config";
$row = sql_fetch($sql);

if($row['cnt'] > 0) {
    // 업데이트
    $sql = " UPDATE g5_sms_config
             SET cf_use_sms = '{$cf_use_sms}',
                 cf_service = '{$cf_service}',
                 cf_phone = '{$cf_phone}',
                 cf_icode_id = '{$cf_icode_id}',
                 cf_icode_pw = '{$cf_icode_pw}',
                 cf_icode_server_ip = '{$cf_icode_server_ip}',
                 cf_icode_server_port = '{$cf_icode_server_port}',
                 cf_aligo_key = '{$cf_aligo_key}',
                 cf_aligo_userid = '{$cf_aligo_userid}',
                 cf_use_register = '{$cf_use_register}',
                 cf_use_password = '{$cf_use_password}',
                 cf_daily_limit = '{$cf_daily_limit}',
                 cf_hourly_limit = '{$cf_hourly_limit}',
                 cf_auth_timeout = '{$cf_auth_timeout}',
                 cf_cost_type = '{$cf_cost_type}',
                 cf_cost_per_sms = '{$cf_cost_per_sms}',
                 cf_remaining_sms = '{$cf_remaining_sms}',
                 cf_monthly_cost = '{$cf_monthly_cost}' ";
} else {
    // 삽입
    $sql = " INSERT INTO g5_sms_config
             SET cf_use_sms = '{$cf_use_sms}',
                 cf_service = '{$cf_service}',
                 cf_phone = '{$cf_phone}',
                 cf_icode_id = '{$cf_icode_id}',
                 cf_icode_pw = '{$cf_icode_pw}',
                 cf_icode_server_ip = '{$cf_icode_server_ip}',
                 cf_icode_server_port = '{$cf_icode_server_port}',
                 cf_aligo_key = '{$cf_aligo_key}',
                 cf_aligo_userid = '{$cf_aligo_userid}',
                 cf_use_register = '{$cf_use_register}',
                 cf_use_password = '{$cf_use_password}',
                 cf_daily_limit = '{$cf_daily_limit}',
                 cf_hourly_limit = '{$cf_hourly_limit}',
                 cf_auth_timeout = '{$cf_auth_timeout}',
                 cf_cost_type = '{$cf_cost_type}',
                 cf_cost_per_sms = '{$cf_cost_per_sms}',
                 cf_remaining_sms = '{$cf_remaining_sms}',
                 cf_monthly_cost = '{$cf_monthly_cost}' ";
}

// 필드 존재 확인 후 추가
$field_check = array(
    'cf_cost_type' => "ALTER TABLE g5_sms_config ADD `cf_cost_type` varchar(20) DEFAULT 'count' COMMENT '비용 타입(count/monthly)'",
    'cf_cost_per_sms' => "ALTER TABLE g5_sms_config ADD `cf_cost_per_sms` decimal(10,2) DEFAULT '0.00' COMMENT '건당 비용'",
    'cf_remaining_sms' => "ALTER TABLE g5_sms_config ADD `cf_remaining_sms` int(11) DEFAULT '0' COMMENT '남은 건수'",
    'cf_monthly_cost' => "ALTER TABLE g5_sms_config ADD `cf_monthly_cost` decimal(10,2) DEFAULT '0.00' COMMENT '월 정액 비용'"
);

foreach($field_check as $field => $add_query) {
    $check_sql = "SHOW COLUMNS FROM g5_sms_config LIKE '{$field}'";
    $check_result = sql_query($check_sql, false);
    if(!sql_num_rows($check_result)) {
        sql_query($add_query, false);
    }
}

// 쿼리 실행
sql_query($sql);

// SMS 사용시 잔액 확인 (아이코드)
if($cf_use_sms && $cf_service == 'icode' && $cf_icode_id && $cf_icode_pw) {
    include_once(G5_LIB_PATH.'/icode.sms.lib.php');
    
    $SMS = new SMS;
    $SMS->SMS_con($cf_icode_server_ip, $cf_icode_id, $cf_icode_pw, $cf_icode_server_port);
    $userinfo = $SMS->Get_SMS_Info();
    
    if($userinfo) {
        // 잔액 정보 업데이트
        $remaining = isset($userinfo['coin']) ? (int)$userinfo['coin'] : 0;
        if($remaining > 0) {
            sql_query("UPDATE g5_sms_config SET cf_remaining_sms = '{$remaining}'");
        }
    }
}

goto_url('./sms_config.php?saved=1');
?>