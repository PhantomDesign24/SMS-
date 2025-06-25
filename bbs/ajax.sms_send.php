<?php
// =================================== 
// ajax.sms_send.php 수정 버전
// 비밀번호 찾기 + 회원가입 통합
// =================================== 

include_once('./_common.php');

header('Content-Type: application/json');

$response = array(
    'success' => false,
    'error' => '',
    'timeout' => 180
);

// 로그인 상태 확인
if ($is_member) {
    $response['error'] = '이미 로그인중입니다.';
    echo json_encode($response);
    exit;
}

// SMS 설정 확인
$sms_config = get_sms_config();

// 입력값 검증
$type = isset($_POST['type']) ? clean_xss_tags($_POST['type']) : '';
$mb_hp = isset($_POST['mb_hp']) ? preg_replace('/[^0-9]/', '', $_POST['mb_hp']) : '';
$hp = isset($_POST['hp']) ? preg_replace('/[^0-9]/', '', $_POST['hp']) : '';

// hp 파라미터 통합
if(!$mb_hp && $hp) {
    $mb_hp = $hp;
}

if(!$mb_hp) {
    $response['error'] = '휴대폰 번호를 입력해주세요.';
    echo json_encode($response);
    exit;
}

// 타입별 처리
if($type === 'password') {
    // 기존 비밀번호 찾기 로직
    if(!$sms_config || !isset($sms_config['cf_use_password']) || !$sms_config['cf_use_password']) {
        $response['error'] = 'SMS 인증 기능을 사용할 수 없습니다.';
        echo json_encode($response);
        exit;
    }
    
    // 회원 정보 확인
    $sql = "SELECT mb_id, mb_name, mb_email, mb_hp, mb_datetime, mb_email_certify 
            FROM {$g5['member_table']} 
            WHERE REPLACE(mb_hp, '-', '') = '".sql_real_escape_string($mb_hp)."' 
            AND mb_leave_date = '' 
            AND mb_intercept_date = ''
            LIMIT 1";
    $mb = sql_fetch($sql);
    
    if(!$mb) {
        $response['error'] = '해당 휴대폰 번호로 가입된 회원이 없습니다.';
        echo json_encode($response);
        exit;
    }
    
    // 이메일 인증 확인 (선택사항)
    if($config['cf_use_email_certify'] && !$mb['mb_email_certify']) {
        $response['error'] = '이메일 인증을 완료해주세요.';
        echo json_encode($response);
        exit;
    }
    
} else if($type === 'register') {
    // 회원가입 처리
    if(!$sms_config || !isset($sms_config['cf_use_register']) || !$sms_config['cf_use_register']) {
        $response['error'] = 'SMS 인증 기능을 사용할 수 없습니다.';
        echo json_encode($response);
        exit;
    }
    
    // 이미 가입된 번호인지 확인
    $sql = "SELECT COUNT(*) as cnt FROM {$g5['member_table']} 
            WHERE REPLACE(mb_hp, '-', '') = '".sql_real_escape_string($mb_hp)."'";
    $row = sql_fetch($sql);
    
    if($row['cnt'] > 0) {
        $response['error'] = '이미 등록된 휴대폰 번호입니다.';
        echo json_encode($response);
        exit;
    }
} else {
    $response['error'] = '잘못된 요청입니다.';
    echo json_encode($response);
    exit;
}

// 전화번호 유효성 검증
if(!preg_match('/^01[0-9]{8,9}$/', $mb_hp)) {
    $response['error'] = '올바른 휴대폰 번호를 입력해주세요.';
    echo json_encode($response);
    exit;
}

// 인증번호 생성
$auth_code = sprintf("%06d", rand(0, 999999));

// 메시지 작성
$site_name = isset($config['cf_title']) ? $config['cf_title'] : '사이트';
if($type === 'password') {
    $message = "[{$site_name}] 비밀번호 찾기 인증번호는 {$auth_code} 입니다.";
} else {
    $message = "[{$site_name}] 회원가입 인증번호는 {$auth_code} 입니다.";
}

// SMS 발송 (실제 발송 로직이 있다면)
if(function_exists('send_sms')) {
    $result = send_sms($mb_hp, $message, $type);
    if(!$result['success']) {
        $response['error'] = 'SMS 발송에 실패했습니다.';
        echo json_encode($response);
        exit;
    }
}

// 세션 저장
if($type === 'password') {
    set_session('ss_password_mb_hp', $mb_hp);
    set_session('ss_password_mb_id', $mb['mb_id']);
    set_session('ss_password_mb_name', $mb['mb_name']);
    set_session('ss_password_time', time());
    
    // 인증번호 저장 (verify_auth_code 함수용)
    if(function_exists('save_auth_code')) {
        $auth_data = array(
            'type' => 'password',
            'phone' => $mb_hp,
            'auth_code' => $auth_code,
            'ip' => $_SERVER['REMOTE_ADDR']
        );
        save_auth_code($auth_data);
    }
} else {
    // 회원가입용 세션
    set_session('ss_register_mb_hp', $mb_hp);
    set_session('ss_register_auth_code', $auth_code);
    set_session('ss_register_time', time());
}

$response['success'] = true;
$response['timeout'] = isset($sms_config['cf_auth_timeout']) ? $sms_config['cf_auth_timeout'] : 180;

// 테스트용 - 실제 운영시 제거
$response['debug_auth_code'] = $auth_code;

echo json_encode($response);
?>