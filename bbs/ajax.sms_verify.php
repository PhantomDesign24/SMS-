<?php
// =================================== 
// ajax.sms_verify.php 수정 버전
// 비밀번호 찾기 + 회원가입 통합
// =================================== 

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

// 입력값 받기
$mb_hp = isset($_POST['mb_hp']) ? preg_replace('/[^0-9]/', '', $_POST['mb_hp']) : '';
$hp = isset($_POST['hp']) ? preg_replace('/[^0-9]/', '', $_POST['hp']) : '';
$auth_code = isset($_POST['auth_code']) ? preg_replace('/[^0-9]/', '', $_POST['auth_code']) : '';
$cert_number = isset($_POST['cert_number']) ? preg_replace('/[^0-9]/', '', $_POST['cert_number']) : '';
$type = isset($_POST['type']) ? clean_xss_tags($_POST['type']) : '';

// 파라미터 통합
if(!$mb_hp && $hp) {
    $mb_hp = $hp;
}
if(!$auth_code && $cert_number) {
    $auth_code = $cert_number;
}

if(!$mb_hp || !$auth_code) {
    $response['error'] = '필수 정보가 누락되었습니다.';
    echo json_encode($response);
    exit;
}

// 타입별 처리
if($type === 'register') {
    // 회원가입 인증 처리
    $sess_mb_hp = get_session('ss_register_mb_hp');
    $sess_auth_code = get_session('ss_register_auth_code');
    $sess_time = get_session('ss_register_time');
    
    if(!$sess_mb_hp || $mb_hp !== $sess_mb_hp) {
        $response['error'] = '잘못된 접근입니다.';
        echo json_encode($response);
        exit;
    }
    
    // 시간 초과 확인 (3분)
    if(time() - $sess_time > 180) {
        $response['error'] = '인증시간이 초과되었습니다.';
        echo json_encode($response);
        exit;
    }
    
    // 인증번호 확인
    if($auth_code == $sess_auth_code) {
        $response['verified'] = true;
        
        // 인증 완료 세션
        set_session('ss_hp_certified', $mb_hp);
        set_session('ss_hp_certified_time', time());
        
        // 사용한 인증 정보 삭제
        set_session('ss_register_mb_hp', '');
        set_session('ss_register_auth_code', '');
        set_session('ss_register_time', '');
    } else {
        $response['error'] = '인증번호가 일치하지 않습니다.';
    }
    
} else {
    // 비밀번호 찾기 처리 (기존 로직)
    $sess_mb_hp = get_session('ss_password_mb_hp');
    $sess_mb_id = get_session('ss_password_mb_id');
    
    if(!$mb_hp || $mb_hp !== $sess_mb_hp) {
        $response['error'] = '잘못된 접근입니다.';
        echo json_encode($response);
        exit;
    }
    
    // 인증번호 확인
    if(function_exists('verify_auth_code')) {
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
    } else {
        $response['error'] = '인증 기능을 사용할 수 없습니다.';
    }
}

echo json_encode($response);
?>