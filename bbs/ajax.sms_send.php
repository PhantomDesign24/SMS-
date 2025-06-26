<?php
/*
 * 파일명: ajax.sms_send.php
 * 위치: /bbs/ajax.sms_send.php
 * 기능: SMS 인증번호 발송 처리 (Ajax)
 * 작성일: 2025-01-27
 * 수정일: 2025-01-27 (발송 제한 및 보안 기능 완성, 회원ID 로그 추가)
 */

include_once('./_common.php');

header('Content-Type: application/json');

$response = array(
    'success' => false,
    'error' => '',
    'timeout' => 180,
    'need_captcha' => false
);

// 로그인 상태 확인
if ($is_member) {
    $response['error'] = '이미 로그인중입니다.';
    echo json_encode($response);
    exit;
}

// SMS 설정 확인
$sms_config = get_sms_config();
if(!$sms_config || !isset($sms_config['cf_use_sms']) || !$sms_config['cf_use_sms']) {
    $response['error'] = 'SMS 인증 기능을 사용할 수 없습니다.';
    echo json_encode($response);
    exit;
}

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

// 전화번호 유효성 검증
if(!validate_phone_number($mb_hp)) {
    $response['error'] = '올바른 휴대폰 번호를 입력해주세요.';
    echo json_encode($response);
    exit;
}

// 블랙리스트 체크
if(isset($sms_config['cf_use_blacklist']) && $sms_config['cf_use_blacklist'] && is_blacklisted_phone($mb_hp)) {
    $response['error'] = '차단된 번호입니다. 관리자에게 문의해주세요.';
    echo json_encode($response);
    exit;
}

// 발송 제한 체크
if(function_exists('check_sms_limit')) {
    $limit_check = check_sms_limit($mb_hp, $_SERVER['REMOTE_ADDR']);
    if(!$limit_check['allowed']) {
        $response['error'] = $limit_check['message'];
        echo json_encode($response);
        exit;
    }
}

// 캡차 체크
if(isset($sms_config['cf_use_captcha']) && $sms_config['cf_use_captcha'] && function_exists('need_captcha') && need_captcha($mb_hp)) {
    // 캡차 입력 확인
    if(!isset($_POST['g-recaptcha-response']) && !isset($_POST['captcha_key'])) {
        $response['error'] = '자동등록방지 코드를 입력해주세요.';
        $response['need_captcha'] = true;
        echo json_encode($response);
        exit;
    }
    
    // 캡차 검증
    if(!chk_captcha()) {
        $response['error'] = '자동등록방지 코드가 틀렸습니다.';
        $response['need_captcha'] = true;
        echo json_encode($response);
        exit;
    }
}

// 타입별 처리
$mb = array();
if($type === 'password') {
    // 비밀번호 찾기
    if(!isset($sms_config['cf_use_password']) || !$sms_config['cf_use_password']) {
        $response['error'] = '비밀번호 찾기 SMS 인증을 사용하지 않습니다.';
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
    // 회원가입
    if(!isset($sms_config['cf_use_register']) || !$sms_config['cf_use_register']) {
        $response['error'] = '회원가입 SMS 인증을 사용하지 않습니다.';
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

// 인증번호 생성
$auth_code = generate_auth_code(6);

// 메시지 작성
$site_name = isset($config['cf_title']) ? $config['cf_title'] : '사이트';
if($type === 'password') {
    $message = "[{$site_name}] 비밀번호 찾기 인증번호는 {$auth_code} 입니다.";
} else {
    $message = "[{$site_name}] 회원가입 인증번호는 {$auth_code} 입니다.";
}

// SMS 발송
if(function_exists('send_sms')) {
    // 비밀번호 찾기인 경우 회원 ID를 전역 변수로 설정 (로그 기록용)
    if($type === 'password' && isset($mb['mb_id'])) {
        $GLOBALS['temp_mb_id'] = $mb['mb_id'];
    }
    
    $result = send_sms($mb_hp, $message, $type);
    
    if($result['success']) {
        // 발송 성공
        
        // 발송 제한 정보 업데이트
        if(function_exists('update_sms_limit')) {
            update_sms_limit($mb_hp, $_SERVER['REMOTE_ADDR']);
        }
        
        // 인증번호 저장
        if($type === 'password') {
            // 비밀번호 찾기용 세션
            set_session('ss_password_mb_hp', $mb_hp);
            set_session('ss_password_mb_id', $mb['mb_id']);
            set_session('ss_password_mb_name', $mb['mb_name']);
            set_session('ss_password_time', time());
            
            // 인증번호 DB 저장
            if(function_exists('save_auth_code')) {
                save_auth_code(array(
                    'type' => 'password',
                    'phone' => $mb_hp,
                    'auth_code' => $auth_code,
                    'ip' => $_SERVER['REMOTE_ADDR']
                ));
            }
        } else {
            // 회원가입용 세션
            set_session('ss_register_mb_hp', $mb_hp);
            set_session('ss_register_auth_code', $auth_code);
            set_session('ss_register_time', time());
            
            // 인증번호 DB 저장
            if(function_exists('save_auth_code')) {
                save_auth_code(array(
                    'type' => 'register',
                    'phone' => $mb_hp,
                    'auth_code' => $auth_code,
                    'ip' => $_SERVER['REMOTE_ADDR']
                ));
            }
        }
        
        $response['success'] = true;
        $response['timeout'] = isset($sms_config['cf_auth_timeout']) ? $sms_config['cf_auth_timeout'] : 180;
        $response['message'] = '인증번호가 발송되었습니다.';
        
        // 캡차 필요 여부 확인
        if(function_exists('need_captcha')) {
            $response['need_captcha'] = need_captcha($mb_hp);
        }
        
    } else {
        // 발송 실패
        $response['error'] = 'SMS 발송에 실패했습니다. ' . (isset($result['message']) ? $result['message'] : '');
    }
} else {
    // send_sms 함수가 없는 경우 (개발/테스트 환경)
    // 세션에만 저장하고 성공 처리
    if($type === 'password') {
        set_session('ss_password_mb_hp', $mb_hp);
        set_session('ss_password_mb_id', $mb['mb_id']);
        set_session('ss_password_mb_name', $mb['mb_name']);
        set_session('ss_password_time', time());
        
        if(function_exists('save_auth_code')) {
            save_auth_code(array(
                'type' => 'password',
                'phone' => $mb_hp,
                'auth_code' => $auth_code,
                'ip' => $_SERVER['REMOTE_ADDR']
            ));
        }
    } else {
        set_session('ss_register_mb_hp', $mb_hp);
        set_session('ss_register_auth_code', $auth_code);
        set_session('ss_register_time', time());
        
        if(function_exists('save_auth_code')) {
            save_auth_code(array(
                'type' => 'register',
                'phone' => $mb_hp,
                'auth_code' => $auth_code,
                'ip' => $_SERVER['REMOTE_ADDR']
            ));
        }
    }
    
    $response['success'] = true;
    $response['timeout'] = isset($sms_config['cf_auth_timeout']) ? $sms_config['cf_auth_timeout'] : 180;
    $response['message'] = '인증번호가 발송되었습니다.';
    
    // 개발 환경에서 인증번호 표시 (보안 주의!)
    if(defined('G5_DISPLAY_SQL_ERROR') && G5_DISPLAY_SQL_ERROR) {
        $response['debug_auth_code'] = $auth_code;
    }
}

echo json_encode($response);
?>