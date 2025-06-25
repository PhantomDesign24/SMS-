<?php
/*
 * 파일명: ajax.sms_verify.php
 * 위치: /bbs/ajax.sms_verify.php
 * 기능: SMS 인증번호 확인 (Ajax)
 * 작성일: 2024-12-29
 */

include_once('./_common.php');

header('Content-Type: application/json');

$response = array(
    'verified' => false,
    'error' => '',
    'mb_id' => '',
    'token' => ''
);

if ($is_member) {
    $response['error'] = '이미 로그인중입니다.';
    echo json_encode($response);
    exit;
}

$mb_hp = isset($_POST['mb_hp']) ? preg_replace('/[^0-9]/', '', $_POST['mb_hp']) : '';
$auth_code = isset($_POST['auth_code']) ? preg_replace('/[^0-9]/', '', $_POST['auth_code']) : '';
$type = isset($_POST['type']) ? clean_xss_tags($_POST['type']) : '';

// 세션 확인
$sess_mb_hp = get_session('ss_password_mb_hp');
$sess_mb_id = get_session('ss_password_mb_id');

if(!$mb_hp || $mb_hp !== $sess_mb_hp) {
    $response['error'] = '잘못된 접근입니다.';
    echo json_encode($response);
    exit;
}

if(!$auth_code) {
    $response['error'] = '인증번호를 입력해주세요.';
    echo json_encode($response);
    exit;
}

// 인증번호 확인
$verify_result = verify_auth_code($mb_hp, $auth_code, 'password');

if($verify_result['verified']) {
    // 회원 정보 조회
    $sql = "SELECT mb_id, mb_name FROM {$g5['member_table']} 
            WHERE mb_id = '".sql_real_escape_string($sess_mb_id)."' 
            AND mb_leave_date = ''";
    $mb = sql_fetch($sql);
    
    if($mb) {
        // 비밀번호 재설정 토큰 생성
        $token = md5(pack('V*', rand(), rand(), rand(), rand()));
        
        // 토큰 저장
        sql_query("UPDATE {$g5['member_table']} SET mb_lost_certify = '{$token}' WHERE mb_id = '{$mb['mb_id']}'");
        
        // 세션 설정
        set_session('ss_password_reset_token', $token);
        set_session('ss_password_reset_mb_id', $mb['mb_id']);
        
        $response['verified'] = true;
        $response['mb_id'] = $mb['mb_id'];
        $response['token'] = $token;
    } else {
        $response['error'] = '회원 정보를 찾을 수 없습니다.';
    }
} else {
    $response['error'] = $verify_result['message'];
}

echo json_encode($response);
?>