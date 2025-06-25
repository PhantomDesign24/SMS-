<?php
/*
 * 파일명: ajax.sms_send.php
 * 위치: /bbs/ajax.sms_send.php
 * 기능: SMS 인증번호 발송 (Ajax)
 * 작성일: 2024-12-29
 */

include_once('./_common.php');

// ===================================
// 초기 설정
// ===================================
header('Content-Type: application/json');

$response = array(
    'success' => false,
    'error' => '',
    'timeout' => 180
);

// ===================================
// 로그인 상태 확인
// ===================================
if ($is_member) {
    $response['error'] = '이미 로그인중입니다.';
    echo json_encode($response);
    exit;
}

// ===================================
// SMS 설정 확인
// ===================================
$sms_config = get_sms_config();

if(!$sms_config || !isset($sms_config['cf_use_password']) || !$sms_config['cf_use_password']) {
    $response['error'] = 'SMS 인증 기능을 사용할 수 없습니다.';
    echo json_encode($response);
    exit;
}

// ===================================
// 입력값 검증
// ===================================
$type = isset($_POST['type']) ? clean_xss_tags($_POST['type']) : '';
$mb_hp = isset($_POST['mb_hp']) ? preg_replace('/[^0-9]/', '', $_POST['mb_hp']) : '';

// 타입 검증
if($type !== 'password') {
    $response['error'] = '잘못된 요청입니다.';
    echo json_encode($response);
    exit;
}

if(!$mb_hp) {
    $response['error'] = '휴대폰 번호를 입력해주세요.';
    echo json_encode($response);
    exit;
}

// ===================================
// 전화번호 유효성 검증
// ===================================
if(!validate_phone_number($mb_hp)) {
    $response['error'] = '올바른 휴대폰 번호를 입력해주세요.';
    echo json_encode($response);
    exit;
}

// ===================================
// 해외번호 차단 체크
// ===================================
if(isset($sms_config['cf_block_foreign']) && $sms_config['cf_block_foreign'] && !preg_match('/^01[016789]/', $mb_hp)) {
    $response['error'] = '국내 휴대폰 번호만 사용 가능합니다.';
    echo json_encode($response);
    exit;
}

// ===================================
// 회원 정보 확인
// ===================================
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

// ===================================
// 이메일 인증 확인 (선택사항)
// ===================================
if($config['cf_use_email_certify'] && !$mb['mb_email_certify']) {
    $response['error'] = '이메일 인증을 완료해주세요.';
    echo json_encode($response);
    exit;
}

// ===================================
// 블랙리스트 체크
// ===================================
if(is_blacklisted_phone($mb_hp)) {
    $response['error'] = '차단된 번호입니다. 관리자에게 문의하세요.';
    echo json_encode($response);
    exit;
}

// ===================================
// 발송 제한 체크
// ===================================
$limit_check = check_sms_limit($mb_hp, $_SERVER['REMOTE_ADDR']);
if(!$limit_check['allowed']) {
    $response['error'] = $limit_check['message'];
    echo json_encode($response);
    exit;
}

// ===================================
// 최근 발송 이력 확인 (중복 방지)
// ===================================
$recent_check_time = date('Y-m-d H:i:s', time() - 60); // 1분 이내
$sql = "SELECT COUNT(*) as cnt FROM g5_sms_auth 
        WHERE sa_phone = '".sql_real_escape_string($mb_hp)."' 
        AND sa_type = 'password' 
        AND sa_datetime > '".sql_real_escape_string($recent_check_time)."'";
$recent = sql_fetch($sql);

if($recent && $recent['cnt'] > 0) {
    $response['error'] = '잠시 후 다시 시도해주세요.';
    echo json_encode($response);
    exit;
}

// ===================================
// 인증번호 생성
// ===================================
$auth_code = generate_auth_code(6);

// ===================================
// 메시지 작성
// ===================================
$site_name = isset($config['cf_title']) ? $config['cf_title'] : '사이트';
$message = "[{$site_name}] 비밀번호 찾기 인증번호는 {$auth_code} 입니다.";

// ===================================
// SMS 발송
// ===================================
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
    set_session('ss_password_mb_name', $mb['mb_name']);
    set_session('ss_password_time', time());
    
    $response['success'] = true;
    $response['timeout'] = isset($sms_config['cf_auth_timeout']) ? $sms_config['cf_auth_timeout'] : 180;
} else {
    // 디버깅 정보 추가 (임시)
    $debug_info = '';
    if(file_exists(G5_DATA_PATH.'/sms_debug.log')) {
        $debug_info = file_get_contents(G5_DATA_PATH.'/sms_debug.log');
        $debug_lines = explode("\n", $debug_info);
        // 마지막 로그 항목만 가져오기
        $last_log = array_slice($debug_lines, -15, 15);
        $debug_info = implode("\n", $last_log);
    }
    
    $response['error'] = 'SMS 발송에 실패했습니다. ' . (isset($result['message']) ? $result['message'] : '');
    $response['debug'] = $debug_info; // 디버깅용 (나중에 제거)
}

// ===================================
// 응답 전송
// ===================================
echo json_encode($response);
?>