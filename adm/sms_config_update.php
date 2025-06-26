<?php
/*
 * 파일명: sms_config_update.php
 * 위치: /adm/sms_config_update.php
 * 기능: SMS 설정 저장 (그누보드 기본 설정 연동)
 * 작성일: 2024-12-28
 * 수정일: 2025-01-27
 */

include_once('./_common.php');

if ($is_admin != 'super')
    alert('최고관리자만 접근 가능합니다.');

check_admin_token();

// ===================================
// 설정값 받기
// ===================================

// 기본 설정
$cf_use_sms = isset($_POST['cf_use_sms']) ? (int)$_POST['cf_use_sms'] : 0;
$cf_service = isset($_POST['cf_service']) ? clean_xss_tags($_POST['cf_service']) : 'icode';
$cf_phone = isset($_POST['cf_phone']) ? preg_replace('/[^0-9-]/', '', $_POST['cf_phone']) : '';

// 알리고 설정 (아이코드는 그누보드 기본 설정 사용)
$cf_aligo_key = isset($_POST['cf_aligo_key']) ? clean_xss_tags($_POST['cf_aligo_key']) : '';
$cf_aligo_userid = isset($_POST['cf_aligo_userid']) ? clean_xss_tags($_POST['cf_aligo_userid']) : '';

// 사용 설정
$cf_use_register = isset($_POST['cf_use_register']) ? (int)$_POST['cf_use_register'] : 0;
$cf_use_password = isset($_POST['cf_use_password']) ? (int)$_POST['cf_use_password'] : 0;

// 발송 제한 설정
$cf_daily_limit = isset($_POST['cf_daily_limit']) ? (int)$_POST['cf_daily_limit'] : 10;
$cf_hourly_limit = isset($_POST['cf_hourly_limit']) ? (int)$_POST['cf_hourly_limit'] : 5;
$cf_resend_delay = isset($_POST['cf_resend_delay']) ? (int)$_POST['cf_resend_delay'] : 60;
$cf_ip_daily_limit = isset($_POST['cf_ip_daily_limit']) ? (int)$_POST['cf_ip_daily_limit'] : 50;

// 인증 설정
$cf_auth_timeout = isset($_POST['cf_auth_timeout']) ? (int)$_POST['cf_auth_timeout'] : 180;
$cf_max_try = isset($_POST['cf_max_try']) ? (int)$_POST['cf_max_try'] : 5;

// 보안 설정
$cf_use_captcha = isset($_POST['cf_use_captcha']) ? (int)$_POST['cf_use_captcha'] : 0;
$cf_captcha_count = isset($_POST['cf_captcha_count']) ? (int)$_POST['cf_captcha_count'] : 3;
$cf_block_foreign = isset($_POST['cf_block_foreign']) ? (int)$_POST['cf_block_foreign'] : 1;
$cf_use_blacklist = isset($_POST['cf_use_blacklist']) ? (int)$_POST['cf_use_blacklist'] : 1;


// ===================================
// 유효성 검증
// ===================================

// 발신번호 검증
if(!$cf_phone) {
    alert('발신번호를 입력하세요.');
}

// 발신번호 형식 체크
$phoneNumbers = preg_replace('/[^0-9]/', '', $cf_phone);
if(!preg_match('/^0[0-9]{8,10}$/', $phoneNumbers)) {
    alert('올바른 발신번호 형식이 아닙니다.');
}

// 알리고 사용 시 필수값 체크
if($cf_service == 'aligo') {
    if(!$cf_aligo_key) {
        alert('알리고 API Key를 입력하세요.');
    }
    if(!$cf_aligo_userid) {
        alert('알리고 User ID를 입력하세요.');
    }
}

// ===================================
// 테이블 필드 확인 및 추가
// ===================================

// 기본 필드 확인
$field_check = array(
    // 발송 제한 관련
    'cf_resend_delay' => "ALTER TABLE g5_sms_config ADD `cf_resend_delay` int(11) DEFAULT '60' COMMENT '재발송 대기시간(초)'",
    'cf_ip_daily_limit' => "ALTER TABLE g5_sms_config ADD `cf_ip_daily_limit` int(11) DEFAULT '50' COMMENT 'IP당 일일 발송 제한'",
    
    // 인증 관련
    'cf_max_try' => "ALTER TABLE g5_sms_config ADD `cf_max_try` int(11) DEFAULT '5' COMMENT '최대 인증 시도 횟수'",
    
    // 보안 관련
    'cf_use_captcha' => "ALTER TABLE g5_sms_config ADD `cf_use_captcha` tinyint(4) DEFAULT '0' COMMENT '캡차 사용 여부'",
    'cf_captcha_count' => "ALTER TABLE g5_sms_config ADD `cf_captcha_count` int(11) DEFAULT '3' COMMENT '캡차 표시 기준 횟수'",
    'cf_block_foreign' => "ALTER TABLE g5_sms_config ADD `cf_block_foreign` tinyint(4) DEFAULT '1' COMMENT '해외번호 차단'",
    'cf_use_blacklist' => "ALTER TABLE g5_sms_config ADD `cf_use_blacklist` tinyint(4) DEFAULT '1' COMMENT '블랙리스트 사용'"
);

foreach($field_check as $field => $add_query) {
    $check_sql = "SHOW COLUMNS FROM g5_sms_config LIKE '{$field}'";
    $check_result = sql_query($check_sql, false);
    if(!sql_num_rows($check_result)) {
        sql_query($add_query, false);
    }
}

// ===================================
// 설정 저장
// ===================================

// 기존 설정 확인
$sql = "SELECT COUNT(*) as cnt FROM g5_sms_config";
$row = sql_fetch($sql);

if($row['cnt'] > 0) {
    // 업데이트
    $sql = " UPDATE g5_sms_config
             SET cf_use_sms = '{$cf_use_sms}',
                 cf_service = '{$cf_service}',
                 cf_phone = '{$cf_phone}',
                 cf_aligo_key = '{$cf_aligo_key}',
                 cf_aligo_userid = '{$cf_aligo_userid}',
                 cf_use_register = '{$cf_use_register}',
                 cf_use_password = '{$cf_use_password}',
                 cf_daily_limit = '{$cf_daily_limit}',
                 cf_hourly_limit = '{$cf_hourly_limit}',
                 cf_resend_delay = '{$cf_resend_delay}',
                 cf_ip_daily_limit = '{$cf_ip_daily_limit}',
                 cf_auth_timeout = '{$cf_auth_timeout}',
                 cf_max_try = '{$cf_max_try}',
                 cf_use_captcha = '{$cf_use_captcha}',
                 cf_captcha_count = '{$cf_captcha_count}',
                 cf_block_foreign = '{$cf_block_foreign}',
                 cf_use_blacklist = '{$cf_use_blacklist}' ";
} else {
    // 삽입
    $sql = " INSERT INTO g5_sms_config
             SET cf_use_sms = '{$cf_use_sms}',
                 cf_service = '{$cf_service}',
                 cf_phone = '{$cf_phone}',
                 cf_aligo_key = '{$cf_aligo_key}',
                 cf_aligo_userid = '{$cf_aligo_userid}',
                 cf_use_register = '{$cf_use_register}',
                 cf_use_password = '{$cf_use_password}',
                 cf_daily_limit = '{$cf_daily_limit}',
                 cf_hourly_limit = '{$cf_hourly_limit}',
                 cf_resend_delay = '{$cf_resend_delay}',
                 cf_ip_daily_limit = '{$cf_ip_daily_limit}',
                 cf_auth_timeout = '{$cf_auth_timeout}',
                 cf_max_try = '{$cf_max_try}',
                 cf_use_captcha = '{$cf_use_captcha}',
                 cf_captcha_count = '{$cf_captcha_count}',
                 cf_block_foreign = '{$cf_block_foreign}',
                 cf_use_blacklist = '{$cf_use_blacklist}',
                 cf_cost_type = '{$cf_cost_type}',
                 cf_cost_per_sms = '{$cf_cost_per_sms}',
                 cf_remaining_sms = '{$cf_remaining_sms}',
                 cf_monthly_cost = '{$cf_monthly_cost}' ";
}

// 쿼리 실행
sql_query($sql);

// ===================================
// 잔액 자동 업데이트 (아이코드 사용 시)
// ===================================
if($cf_use_sms && $cf_service == 'icode') {
    // 그누보드 기본 설정에서 아이코드 정보 가져오기
    if($config['cf_sms_use'] == 'icode' && $config['cf_icode_id'] && $config['cf_icode_pw']) {
        
        // JSON API (토큰) 사용 여부 확인
        if(isset($config['cf_icode_token_key']) && $config['cf_icode_token_key']) {
            // JSON API로 잔액 조회
            $url = 'https://sms.gabia.com/api/credit/v1/remains';
            $headers = array(
                'Content-Type: application/json',
                'Authorization: Bearer ' . $config['cf_icode_token_key']
            );
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if($http_code == 200) {
                $result = json_decode($response, true);
                if(isset($result['data']['sms_count'])) {
                    $remaining = (int)$result['data']['sms_count'];
                    sql_query("UPDATE g5_sms_config SET cf_remaining_sms = '{$remaining}'");
                }
            }
            
        } else if(function_exists('get_icode_userinfo')) {
            // 구버전 API로 잔액 조회
            $userinfo = get_icode_userinfo($config['cf_icode_id'], $config['cf_icode_pw']);
            
            if($userinfo && isset($userinfo['coin'])) {
                $remaining = floor($userinfo['coin'] / 16); // 16원당 1건
                sql_query("UPDATE g5_sms_config SET cf_remaining_sms = '{$remaining}'");
            }
        }
    }
}

// ===================================
// 알리고 잔액 업데이트
// ===================================
if($cf_use_sms && $cf_service == 'aligo' && $cf_aligo_key && $cf_aligo_userid) {
    if(file_exists(G5_PLUGIN_PATH.'/sms/aligo.php')) {
        include_once(G5_PLUGIN_PATH.'/sms/aligo.php');
        
        try {
            $sms_api = new aligo_sms($cf_aligo_key, $cf_aligo_userid);
            $balance_result = $sms_api->get_balance();
            
            if($balance_result['success'] && isset($balance_result['balance'])) {
                sql_query("UPDATE g5_sms_config SET cf_remaining_sms = '".(int)$balance_result['balance']."'");
            }
        } catch(Exception $e) {
            // 오류 무시
        }
    }
}

// ===================================
// 완료 후 이동
// ===================================
goto_url('./sms_config.php?saved=1');