<?php
/*
 * 파일명: ajax.password_reset.php
 * 위치: /bbs/ajax.password_reset.php
 * 기능: 비밀번호 재설정 (Ajax)
 * 작성일: 2024-12-29
 */

include_once('./_common.php');

header('Content-Type: application/json');

$response = array(
    'success' => false,
    'error' => ''
);

if ($is_member) {
    $response['error'] = '이미 로그인중입니다.';
    echo json_encode($response);
    exit;
}

$mb_id = isset($_POST['mb_id']) ? trim($_POST['mb_id']) : '';
$token = isset($_POST['token']) ? trim($_POST['token']) : '';
$mb_password = isset($_POST['mb_password']) ? trim($_POST['mb_password']) : '';
$mb_password_re = isset($_POST['mb_password_re']) ? trim($_POST['mb_password_re']) : '';

// 세션 확인
$sess_token = get_session('ss_password_reset_token');
$sess_mb_id = get_session('ss_password_reset_mb_id');

if(!$mb_id || !$token || $mb_id !== $sess_mb_id || $token !== $sess_token) {
    $response['error'] = '잘못된 접근입니다.';
    echo json_encode($response);
    exit;
}

// 토큰 확인
$sql = "SELECT mb_id FROM {$g5['member_table']} 
        WHERE mb_id = '".sql_real_escape_string($mb_id)."' 
        AND mb_lost_certify = '".sql_real_escape_string($token)."'";
$mb = sql_fetch($sql);

if(!$mb) {
    $response['error'] = '인증 정보가 올바르지 않습니다.';
    echo json_encode($response);
    exit;
}

// 비밀번호 확인
if(!$mb_password || !$mb_password_re) {
    $response['error'] = '비밀번호를 입력해주세요.';
    echo json_encode($response);
    exit;
}

if($mb_password !== $mb_password_re) {
    $response['error'] = '비밀번호가 일치하지 않습니다.';
    echo json_encode($response);
    exit;
}

if(strlen($mb_password) < 4) {
    $response['error'] = '비밀번호는 4자 이상 입력해주세요.';
    echo json_encode($response);
    exit;
}

// 비밀번호 암호화
$mb_password_hash = get_encrypt_string($mb_password);

// 비밀번호 업데이트
$sql = "UPDATE {$g5['member_table']} SET 
        mb_password = '".sql_real_escape_string($mb_password_hash)."',
        mb_lost_certify = ''
        WHERE mb_id = '".sql_real_escape_string($mb_id)."'";

if(sql_query($sql)) {
    // 세션 삭제
    set_session('ss_password_mb_hp', '');
    set_session('ss_password_reset_token', '');
    set_session('ss_password_reset_mb_id', '');
    set_session('ss_password_mb_id', '');
    
    $response['success'] = true;
} else {
    $response['error'] = '비밀번호 변경에 실패했습니다.';
}

echo json_encode($response);
?>