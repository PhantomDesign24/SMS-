<?php
/*
 * 파일명: ajax.sms_send.php
 * 위치: /bbs/ajax.sms_send.php
 * 기능: SMS 인증번호 발송 (Ajax)
 * 작성일: 2024-12-29
 */

include_once('./_common.php');

header('Content-Type: application/json');

$response = array(
    'success' => false,
    'error' => '',
    'timeout' => 180
);

if ($is_member) {
    $response['error'] = '이미 로그인중입니다.';
    echo json_encode($response);
    exit;
}

// SMS 설정 확인
$sms_config = get_sms_config();
if(!$sms_config['cf_use_sms'] || !$sms_config['cf_use_password']) {
    $response['error'] = 'SMS 인증 기능을 사용할 수 없습니다.';
    echo json_encode($response);
    exit;
}

$type = isset($_POST['type']) ? clean_xss_tags($_POST['type']) : '';
$mb_hp = isset($_POST['mb_hp']) ? preg_replace('/[^0-9]/', '', $_POST['mb_hp']) : '';

if(!$mb_hp) {
    $response['error'] = '휴대폰 번호를 입력해주세요.';
    echo json_encode($response);
    exit;
}

// 전화번호 유효성 검증
if(!validate_phone_number($mb_hp)) {
    $response['error'] = '올바른 휴대폰 번호를 입력해주세요.';
    echo json_encode($response);
    exit;
}

// 회원 정보 확인
$sql = "SELECT mb_id, mb_name, mb_email, mb_hp FROM {$g5['member_table']} 
        WHERE REPLACE(mb_hp, '-', '') = '".sql_real_escape_string($mb_hp)."' 
        AND mb_leave_date = ''";
$mb = sql_fetch($sql);

if(!$mb) {
    $response['error'] = '해당 휴대폰 번호로 가입된 회원이 없습니다.';
    echo json_encode($response);
    exit;
}

// 블랙리스트 체크
if(is_blacklisted_phone($mb_hp)) {
    $response['error'] = '차단된 번호입니다. 관리자에게 문의하세요.';
    echo json_encode($response);
    exit;
}

// 발송 제한 체크
$limit_check = check_sms_limit($mb_hp, $_SERVER['REMOTE_ADDR']);
if(!$limit_check['allowed']) {
    $response['error'] = $limit_check['message'];
    echo json_encode($response);
    exit;
}

// 인증번호 생성
$auth_code = generate_auth_code(6);

// 메시지 작성
$site_name = $config['cf_title'];
$message = "[{$site_name}] 비밀번호 찾기 인증번호는 {$auth_code} 입니다.";

// SMS 발송
$result = send_sms($mb_hp, $message, 'password');

if($result['success']) {
    // 발송 제한 업데이트
    update_sms_limit($mb_hp, $_SERVER['REMOTE_ADDR']);
    
    // 인증번호 저장
    $auth_data = array(
        'type' => 'password',
        'phone' => $mb_hp,
        'auth_code' => $auth_code,
        'ip' => $_SERVER['REMOTE_ADDR']
    );
    save_auth_code($auth_data);
    
    // 세션 저장
    set_session('ss_password_mb_hp', $mb_hp);
    set_session('ss_password_mb_id', $mb['mb_id']);
    
    $response['success'] = true;
    $response['timeout'] = $sms_config['cf_auth_timeout'];
} else {
    $response['error'] = 'SMS 발송에 실패했습니다. ' . $result['message'];
}

echo json_encode($response);
?>