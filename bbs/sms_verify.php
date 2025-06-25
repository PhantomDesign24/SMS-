<?php
/*
 * 파일명: sms_verify.php
 * 위치: /bbs/sms_verify.php
 * 기능: SMS 인증번호 확인 AJAX 처리
 * 작성일: 2024-12-28
 */

include_once('./_common.php');

// AJAX 요청만 허용
if(!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
    die('잘못된 접근입니다.');
}

// JSON 헤더 설정
header('Content-Type: application/json; charset=utf-8');

// 응답 배열 초기화
$response = array(
    'success' => false,
    'message' => ''
);

// POST 데이터 받기
$phone = isset($_POST['phone']) ? preg_replace('/[^0-9]/', '', $_POST['phone']) : '';
$auth_code = isset($_POST['auth_code']) ? clean_xss_tags($_POST['auth_code']) : '';
$type = isset($_POST['type']) ? clean_xss_tags($_POST['type']) : 'register';

// 입력값 검증
if(!$phone || !$auth_code) {
    $response['message'] = '필수 입력값이 누락되었습니다.';
    die(json_encode($response));
}

// 전화번호 유효성 검사
if(!validate_phone_number($phone)) {
    $response['message'] = '올바른 휴대폰 번호 형식이 아닙니다.';
    die(json_encode($response));
}

// 인증번호 확인
$verify_result = verify_auth_code($phone, $auth_code, $type);

if($verify_result['verified']) {
    $response['success'] = true;
    $response['message'] = $verify_result['message'];
    
    // 세션에 인증 정보 저장
    set_session('ss_sms_verified_'.$type, $phone);
    set_session('ss_sms_verified_time_'.$type, time());
} else {
    $response['message'] = $verify_result['message'];
}

// JSON 응답
echo json_encode($response);
?>