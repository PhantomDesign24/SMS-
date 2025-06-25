<?php
/*
 * 파일명: sms_send.php
 * 위치: /bbs/sms_send.php
 * 기능: SMS 발송 AJAX 처리
 * 작성일: 2024-12-28
 */

include_once('./_common.php');
include_once(G5_CAPTCHA_PATH.'/captcha.lib.php');

// AJAX 요청만 허용
if(!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
    die('잘못된 접근입니다.');
}

// JSON 헤더 설정
header('Content-Type: application/json; charset=utf-8');

// 응답 배열 초기화
$response = array(
    'success' => false,
    'message' => '',
    'need_captcha' => false
);

// POST 데이터 받기
$phone = isset($_POST['phone']) ? preg_replace('/[^0-9]/', '', $_POST['phone']) : '';
$type = isset($_POST['type']) ? clean_xss_tags($_POST['type']) : 'register';
$captcha_key = isset($_POST['captcha_key']) ? clean_xss_tags($_POST['captcha_key']) : '';

// SMS 설정 확인
$sms_config = get_sms_config();
if(!$sms_config) {
    $response['message'] = 'SMS 설정이 되어있지 않습니다.';
    die(json_encode($response));
}

// 사용 여부 확인
if($type == 'register' && !$sms_config['cf_use_register']) {
    $response['message'] = '회원가입 SMS 인증이 비활성화되어 있습니다.';
    die(json_encode($response));
}
if($type == 'password' && !$sms_config['cf_use_password']) {
    $response['message'] = '비밀번호 찾기 SMS 인증이 비활성화되어 있습니다.';
    die(json_encode($response));
}

// 전화번호 유효성 검사
if(!validate_phone_number($phone)) {
    $response['message'] = '올바른 휴대폰 번호 형식이 아닙니다.';
    die(json_encode($response));
}

// 해외번호 차단
if($sms_config['cf_block_foreign']) {
    if(!preg_match('/^01[016789]/', $phone)) {
        $response['message'] = '국내 휴대폰 번호만 사용 가능합니다.';
        die(json_encode($response));
    }
}

// 블랙리스트 체크
if(is_blacklisted_phone($phone)) {
    $response['message'] = '차단된 번호입니다.';
    die(json_encode($response));
}

// 캡차 체크
if(need_captcha($phone)) {
    $response['need_captcha'] = true;
    
    if(!$captcha_key || !chk_captcha()) {
        $response['message'] = '자동등록방지 코드가 올바르지 않습니다.';
        die(json_encode($response));
    }
}

// 발송 제한 체크
$limit_check = check_sms_limit($phone, $_SERVER['REMOTE_ADDR']);
if(!$limit_check['allowed']) {
    $response['message'] = $limit_check['message'];
    die(json_encode($response));
}

// 회원가입 시 이미 가입된 번호 체크
if($type == 'register') {
    $sql = "SELECT COUNT(*) as cnt FROM {$g5['member_table']} 
            WHERE mb_hp = '".sql_real_escape_string($phone)."' 
            AND mb_hp != ''";
    $row = sql_fetch($sql);
    
    if($row['cnt'] > 0) {
        $response['message'] = '이미 등록된 휴대폰 번호입니다.';
        die(json_encode($response));
    }
}

// 비밀번호 찾기 시 회원 존재 확인
if($type == 'password') {
    $mb_id = isset($_POST['mb_id']) ? clean_xss_tags($_POST['mb_id']) : '';
    
    $sql = "SELECT mb_id FROM {$g5['member_table']} 
            WHERE mb_id = '".sql_real_escape_string($mb_id)."' 
            AND mb_hp = '".sql_real_escape_string($phone)."'";
    $mb = sql_fetch($sql);
    
    if(!$mb) {
        $response['message'] = '일치하는 회원정보가 없습니다.';
        die(json_encode($response));
    }
}

// 인증번호 생성
$auth_code = generate_auth_code();

// 메시지 작성
$site_name = $config['cf_title'];
if($type == 'register') {
    $message = "[{$site_name}] 회원가입 인증번호는 {$auth_code} 입니다.";
} else {
    $message = "[{$site_name}] 비밀번호 찾기 인증번호는 {$auth_code} 입니다.";
}

// SMS 발송
$send_result = send_sms($phone, $message, $type);

if($send_result['success']) {
    // 인증번호 저장
    $auth_data = array(
        'type' => $type,
        'phone' => $phone,
        'auth_code' => $auth_code,
        'ip' => $_SERVER['REMOTE_ADDR']
    );
    
    if(save_auth_code($auth_data)) {
        // 발송 제한 카운트 업데이트
        update_sms_limit($phone, $_SERVER['REMOTE_ADDR']);
        
        $response['success'] = true;
        $response['message'] = '인증번호가 발송되었습니다.';
        
        // 유효시간 전달
        $response['timeout'] = $sms_config['cf_auth_timeout'];
    } else {
        $response['message'] = '인증번호 저장에 실패했습니다.';
    }
} else {
    $response['message'] = 'SMS 발송에 실패했습니다. ' . $send_result['message'];
}

// JSON 응답
echo json_encode($response);
?>