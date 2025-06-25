<?php
/*
 * 파일명: sms_config.php
 * 위치: /extend/sms_config.php
 * 기능: SMS 인증 시스템 공통 함수 및 설정
 * 작성일: 2024-12-28
 * 수정일: 2024-12-29 (설정값 로드 수정)
 */

if (!defined('_GNUBOARD_')) exit;

// ===================================
// SMS 설정 로드
// ===================================
$g5_sms_config = array();

// 테이블 존재 확인
$sql = "SHOW TABLES LIKE 'g5_sms_config'";
$table_exists = sql_num_rows(sql_query($sql, false));

if($table_exists) {
    $sql = "SELECT * FROM g5_sms_config LIMIT 1";
    $result = sql_query($sql, false);
    if($result && $row = sql_fetch_array($result)) {
        $g5_sms_config = $row;
        
        // 기본값 설정 (DB에 값이 없는 경우)
        $g5_sms_config['cf_use_sms'] = isset($row['cf_use_sms']) ? $row['cf_use_sms'] : 0;
        $g5_sms_config['cf_use_register'] = isset($row['cf_use_register']) ? $row['cf_use_register'] : 0;
        $g5_sms_config['cf_use_password'] = isset($row['cf_use_password']) ? $row['cf_use_password'] : 0;
        $g5_sms_config['cf_find_use'] = isset($row['cf_use_password']) ? $row['cf_use_password'] : 0; // cf_find_use는 cf_use_password와 동일
    }
}

// ===================================
// SMS 공통 함수
// ===================================

/**
 * SMS 설정 가져오기
 * 
 * @return array SMS 설정 배열
 */
function get_sms_config() {
    global $g5_sms_config;
    return $g5_sms_config;
}

/**
 * 전화번호 형식 검증
 * 
 * @param string $phone 전화번호
 * @return boolean 유효 여부
 */
function validate_phone_number($phone) {
    // 숫자만 추출
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    // 한국 휴대폰 번호 형식 검증 (010, 011, 016, 017, 018, 019)
    if(preg_match('/^01[016789][0-9]{7,8}$/', $phone)) {
        return true;
    }
    
    return false;
}

/**
 * 전화번호 포맷팅
 * 
 * @param string $phone 전화번호
 * @return string 포맷된 전화번호
 */
function format_phone_number($phone) {
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    if(strlen($phone) == 11) {
        return substr($phone, 0, 3) . '-' . substr($phone, 3, 4) . '-' . substr($phone, 7);
    } else if(strlen($phone) == 10) {
        return substr($phone, 0, 3) . '-' . substr($phone, 3, 3) . '-' . substr($phone, 6);
    }
    
    return $phone;
}

/**
 * 인증번호 생성
 * 
 * @param int $length 인증번호 길이
 * @return string 인증번호
 */
function generate_auth_code($length = 6) {
    $code = '';
    for($i = 0; $i < $length; $i++) {
        $code .= mt_rand(0, 9);
    }
    return $code;
}

/**
 * 블랙리스트 체크
 * 
 * @param string $phone 전화번호
 * @return boolean 차단 여부
 */
function is_blacklisted_phone($phone) {
    global $g5_sms_config;
    
    if(!$g5_sms_config['cf_use_blacklist']) {
        return false;
    }
    
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    $sql = "SELECT COUNT(*) as cnt FROM g5_sms_blacklist WHERE sb_phone = '".sql_real_escape_string($phone)."'";
    $row = sql_fetch($sql);
    
    return ($row['cnt'] > 0);
}

/**
 * 발송 제한 체크
 * 
 * @param string $phone 전화번호
 * @param string $ip IP주소
 * @return array 결과 배열
 */
function check_sms_limit($phone, $ip) {
    global $g5_sms_config;
    
    $phone = preg_replace('/[^0-9]/', '', $phone);
    $today = date('Y-m-d');
    $now = new DateTime('now', new DateTimeZone('Asia/Seoul'));
    $current_time = $now->format('Y-m-d H:i:s');
    
    // 오늘 발송 정보 조회
    $sql = "SELECT * FROM g5_sms_limit 
            WHERE sl_phone = '".sql_real_escape_string($phone)."' 
            AND sl_date = '".sql_real_escape_string($today)."'";
    $limit = sql_fetch($sql);
    
    $result = array(
        'allowed' => true,
        'message' => ''
    );
    
    // 재발송 대기시간 체크
    if($limit && $limit['sl_last_send']) {
        $last_send = new DateTime($limit['sl_last_send']);
        $diff = $now->getTimestamp() - $last_send->getTimestamp();
        
        if($diff < $g5_sms_config['cf_resend_delay']) {
            $wait = $g5_sms_config['cf_resend_delay'] - $diff;
            $result['allowed'] = false;
            $result['message'] = $wait . '초 후에 재발송 가능합니다.';
            return $result;
        }
    }
    
    // 일일 발송 제한 체크
    if($limit && $limit['sl_daily_count'] >= $g5_sms_config['cf_daily_limit']) {
        $result['allowed'] = false;
        $result['message'] = '일일 발송 한도를 초과했습니다.';
        return $result;
    }
    
    // 시간당 발송 제한 체크
    if($limit && $limit['sl_last_send']) {
        $last_send = new DateTime($limit['sl_last_send']);
        $hourly_diff = $now->getTimestamp() - $last_send->getTimestamp();
        
        if($hourly_diff < 3600 && $limit['sl_hourly_count'] >= $g5_sms_config['cf_hourly_limit']) {
            $result['allowed'] = false;
            $result['message'] = '시간당 발송 한도를 초과했습니다.';
            return $result;
        }
    }
    
    // IP별 일일 발송 제한 체크
    $sql = "SELECT SUM(sl_ip_count) as total FROM g5_sms_limit 
            WHERE sl_ip = '".sql_real_escape_string($ip)."' 
            AND sl_date = '".sql_real_escape_string($today)."'";
    $ip_limit = sql_fetch($sql);
    
    if($ip_limit && $ip_limit['total'] >= $g5_sms_config['cf_ip_daily_limit']) {
        $result['allowed'] = false;
        $result['message'] = 'IP당 일일 발송 한도를 초과했습니다.';
        return $result;
    }
    
    return $result;
}

/**
 * 발송 제한 카운트 업데이트
 * 
 * @param string $phone 전화번호
 * @param string $ip IP주소
 */
function update_sms_limit($phone, $ip) {
    $phone = preg_replace('/[^0-9]/', '', $phone);
    $today = date('Y-m-d');
    $now = new DateTime('now', new DateTimeZone('Asia/Seoul'));
    $current_time = $now->format('Y-m-d H:i:s');
    
    // 기존 데이터 조회
    $sql = "SELECT * FROM g5_sms_limit 
            WHERE sl_phone = '".sql_real_escape_string($phone)."' 
            AND sl_date = '".sql_real_escape_string($today)."'";
    $limit = sql_fetch($sql);
    
    if($limit) {
        // 시간당 카운트 리셋 체크
        $last_send = new DateTime($limit['sl_last_send']);
        $diff = $now->getTimestamp() - $last_send->getTimestamp();
        $hourly_count = ($diff >= 3600) ? 1 : $limit['sl_hourly_count'] + 1;
        
        $sql = "UPDATE g5_sms_limit SET 
                sl_daily_count = sl_daily_count + 1,
                sl_hourly_count = '".sql_real_escape_string($hourly_count)."',
                sl_last_send = '".sql_real_escape_string($current_time)."',
                sl_ip = '".sql_real_escape_string($ip)."',
                sl_ip_count = sl_ip_count + 1
                WHERE sl_phone = '".sql_real_escape_string($phone)."' 
                AND sl_date = '".sql_real_escape_string($today)."'";
        sql_query($sql);
    } else {
        $sql = "INSERT INTO g5_sms_limit SET
                sl_phone = '".sql_real_escape_string($phone)."',
                sl_date = '".sql_real_escape_string($today)."',
                sl_daily_count = 1,
                sl_hourly_count = 1,
                sl_last_send = '".sql_real_escape_string($current_time)."',
                sl_ip = '".sql_real_escape_string($ip)."',
                sl_ip_count = 1";
        sql_query($sql);
    }
}

/**
 * SMS 발송 로그 기록
 * 
 * @param array $data 로그 데이터
 */
function insert_sms_log($data) {
    $now = new DateTime('now', new DateTimeZone('Asia/Seoul'));
    $current_time = $now->format('Y-m-d H:i:s');
    
    $sql = "INSERT INTO g5_sms_log SET
            sl_type = '".sql_real_escape_string($data['type'])."',
            mb_id = '".sql_real_escape_string($data['mb_id'])."',
            sl_phone = '".sql_real_escape_string($data['phone'])."',
            sl_message = '".sql_real_escape_string($data['message'])."',
            sl_result = '".sql_real_escape_string($data['result'])."',
            sl_ip = '".sql_real_escape_string($data['ip'])."',
            sl_datetime = '".sql_real_escape_string($current_time)."'";
    sql_query($sql);
}

/**
 * 인증번호 저장
 * 
 * @param array $data 인증 데이터
 * @return boolean 성공 여부
 */
function save_auth_code($data) {
    global $g5_sms_config;
    
    $now = new DateTime('now', new DateTimeZone('Asia/Seoul'));
    $current_time = $now->format('Y-m-d H:i:s');
    
    $expire = clone $now;
    $expire->add(new DateInterval('PT'.$g5_sms_config['cf_auth_timeout'].'S'));
    $expire_time = $expire->format('Y-m-d H:i:s');
    
    // 기존 미인증 데이터 삭제
    $sql = "DELETE FROM g5_sms_auth 
            WHERE sa_phone = '".sql_real_escape_string($data['phone'])."' 
            AND sa_verified = 0";
    sql_query($sql);
    
    // 새 인증번호 저장
    $sql = "INSERT INTO g5_sms_auth SET
            sa_type = '".sql_real_escape_string($data['type'])."',
            sa_phone = '".sql_real_escape_string($data['phone'])."',
            sa_auth_code = '".sql_real_escape_string($data['auth_code'])."',
            sa_ip = '".sql_real_escape_string($data['ip'])."',
            sa_try_count = 0,
            sa_verified = 0,
            sa_datetime = '".sql_real_escape_string($current_time)."',
            sa_expire_datetime = '".sql_real_escape_string($expire_time)."'";
    
    return sql_query($sql);
}

/**
 * 인증번호 확인
 * 
 * @param string $phone 전화번호
 * @param string $code 인증번호
 * @param string $type 인증 타입
 * @return array 결과 배열
 */
function verify_auth_code($phone, $code, $type) {
    global $g5_sms_config;
    
    $phone = preg_replace('/[^0-9]/', '', $phone);
    $now = new DateTime('now', new DateTimeZone('Asia/Seoul'));
    $current_time = $now->format('Y-m-d H:i:s');
    
    $result = array(
        'verified' => false,
        'message' => ''
    );
    
    // 인증 정보 조회
    $sql = "SELECT * FROM g5_sms_auth 
            WHERE sa_phone = '".sql_real_escape_string($phone)."' 
            AND sa_type = '".sql_real_escape_string($type)."'
            AND sa_verified = 0
            AND sa_expire_datetime > '".sql_real_escape_string($current_time)."'
            ORDER BY sa_id DESC LIMIT 1";
    $auth = sql_fetch($sql);
    
    if(!$auth) {
        $result['message'] = '인증 정보가 없거나 만료되었습니다.';
        return $result;
    }
    
    // 시도 횟수 체크
    if($auth['sa_try_count'] >= $g5_sms_config['cf_max_try']) {
        $result['message'] = '인증 시도 횟수를 초과했습니다.';
        return $result;
    }
    
    // 시도 횟수 증가
    $sql = "UPDATE g5_sms_auth SET 
            sa_try_count = sa_try_count + 1 
            WHERE sa_id = '".sql_real_escape_string($auth['sa_id'])."'";
    sql_query($sql);
    
    // 인증번호 확인
    if($auth['sa_auth_code'] === $code) {
        $sql = "UPDATE g5_sms_auth SET 
                sa_verified = 1 
                WHERE sa_id = '".sql_real_escape_string($auth['sa_id'])."'";
        sql_query($sql);
        
        $result['verified'] = true;
        $result['message'] = '인증이 완료되었습니다.';
    } else {
        $remain = $g5_sms_config['cf_max_try'] - ($auth['sa_try_count'] + 1);
        $result['message'] = '인증번호가 일치하지 않습니다. (남은 횟수: ' . $remain . '회)';
    }
    
    return $result;
}

/**
 * 인증 완료 체크
 * 
 * @param string $phone 전화번호
 * @param string $type 인증 타입
 * @return boolean 인증 여부
 */
function is_verified_phone($phone, $type) {
    $phone = preg_replace('/[^0-9]/', '', $phone);
    $now = new DateTime('now', new DateTimeZone('Asia/Seoul'));
    $current_time = $now->format('Y-m-d H:i:s');
    
    // 30분 이내 인증 완료 데이터 확인
    $expire = clone $now;
    $expire->sub(new DateInterval('PT1800S')); // 30분
    $expire_time = $expire->format('Y-m-d H:i:s');
    
    $sql = "SELECT COUNT(*) as cnt FROM g5_sms_auth 
            WHERE sa_phone = '".sql_real_escape_string($phone)."' 
            AND sa_type = '".sql_real_escape_string($type)."'
            AND sa_verified = 1
            AND sa_datetime > '".sql_real_escape_string($expire_time)."'";
    $row = sql_fetch($sql);
    
    return ($row['cnt'] > 0);
}

/**
 * SMS 발송 함수
 * 
 * @param string $phone 수신번호
 * @param string $message 메시지
 * @param string $type 발송 타입
 * @return array 결과 배열
 */
function send_sms($phone, $message, $type = 'etc') {
    global $g5_sms_config, $member, $config, $g5;
    
    $result = array(
        'success' => false,
        'message' => ''
    );
    
    // SMS 서비스별 발송 처리
    if($g5_sms_config['cf_service'] == 'icode') {
        // 그누보드5 기본 SMS 기능 사용
        $sms_send_result = false;
        
        // 1. SMS5 플러그인 확인 (최신)
        if(defined('G5_SMS5_USE') && G5_SMS5_USE && file_exists(G5_PLUGIN_PATH.'/sms5/sms5.lib.php')) {
            include_once(G5_PLUGIN_PATH.'/sms5/sms5.lib.php');
            
            $sms5 = new SMS5;
            $send_hp = $g5_sms_config['cf_phone'];
            $recv_hp = $phone;
            
            // SMS5 발송
            $sms5->send($send_hp, $recv_hp, $message);
            
            if($sms5->result == 1) {
                $result['success'] = true;
                $result['message'] = '발송 성공';
            } else {
                $result['success'] = false;
                $result['message'] = '발송 실패';
            }
            $sms_send_result = true;
        }
        // 2. 구버전 아이코드 라이브러리 확인
        else if(file_exists(G5_LIB_PATH.'/icode.sms.lib.php')) {
            include_once(G5_LIB_PATH.'/icode.sms.lib.php');
            
            // 아이코드 설정 확인 (기본 설정 우선, 없으면 SMS 인증 설정 사용)
            $icode_id = $config['cf_icode_id'] ? $config['cf_icode_id'] : $g5_sms_config['cf_icode_id'];
            $icode_pw = $config['cf_icode_pw'] ? $config['cf_icode_pw'] : $g5_sms_config['cf_icode_pw'];
            $icode_server_ip = $config['cf_icode_server_ip'] ? $config['cf_icode_server_ip'] : '211.172.232.124';
            $icode_server_port = $config['cf_icode_server_port'] ? $config['cf_icode_server_port'] : '7295';
            
            if(!$icode_id || !$icode_pw) {
                $result['message'] = '아이코드 설정이 되어있지 않습니다.';
                return $result;
            }
            
            $SMS = new SMS;
            $SMS->SMS_con($icode_server_ip, $icode_id, $icode_pw, $icode_server_port);
            
            // 발송
            $recv_list = $phone;
			$send_list = $g5_sms_config['cf_phone']; // 배열 아님, 문자로만

            
            $SMS->Add($recv_list, $send_list, $g5_sms_config['cf_phone'], '', '', $message, '', 1);
            $SMS->Send();
            
            // 결과 확인
            if($SMS->result) {
                $result['success'] = true;
                $result['message'] = '발송 성공';
            } else {
                $result['success'] = false;
                $result['message'] = '발송 실패';
            }
            $sms_send_result = true;
        }
        
        if(!$sms_send_result) {
            $result['message'] = '아이코드 SMS 라이브러리가 없습니다. SMS5 플러그인을 설치하거나 아이코드 라이브러리를 확인하세요.';
            return $result;
        }
    } else {
        // 알리고 사용
        if(file_exists(G5_PATH.'/plugin/sms/aligo.php')) {
            include_once(G5_PATH.'/plugin/sms/aligo.php');
            $sms = new aligo_sms($g5_sms_config['cf_aligo_key'], $g5_sms_config['cf_aligo_userid']);
            $result = $sms->send($g5_sms_config['cf_phone'], $phone, $message);
        } else {
            $result['message'] = '알리고 API 파일이 없습니다.';
            return $result;
        }
    }
    
    // 발송 로그 기록
    $log_data = array(
        'type' => $type,
        'mb_id' => $member['mb_id'] ? $member['mb_id'] : '',
        'phone' => $phone,
        'message' => $message,
        'result' => $result['success'] ? 'success' : 'fail',
        'ip' => $_SERVER['REMOTE_ADDR']
    );
    insert_sms_log($log_data);
    
    return $result;
}

/**
 * 캡차 표시 여부 확인
 * 
 * @param string $phone 전화번호
 * @return boolean 캡차 표시 여부
 */
function need_captcha($phone) {
    global $g5_sms_config;
    
    if(!$g5_sms_config['cf_use_captcha']) {
        return false;
    }
    
    $phone = preg_replace('/[^0-9]/', '', $phone);
    $today = date('Y-m-d');
    
    $sql = "SELECT sl_daily_count FROM g5_sms_limit 
            WHERE sl_phone = '".sql_real_escape_string($phone)."' 
            AND sl_date = '".sql_real_escape_string($today)."'";
    $limit = sql_fetch($sql);
    
    if($limit && $limit['sl_daily_count'] >= $g5_sms_config['cf_captcha_count']) {
        return true;
    }
    
    return false;
}

// 디버깅용 (임시)
if(!function_exists('sms_config_debug')) {
    function sms_config_debug() {
        global $g5_sms_config;
        echo "<!-- SMS Config Debug\n";
        echo "cf_use_sms: " . (isset($g5_sms_config['cf_use_sms']) ? $g5_sms_config['cf_use_sms'] : 'not set') . "\n";
        echo "cf_use_password: " . (isset($g5_sms_config['cf_use_password']) ? $g5_sms_config['cf_use_password'] : 'not set') . "\n";
        echo "cf_use_register: " . (isset($g5_sms_config['cf_use_register']) ? $g5_sms_config['cf_use_register'] : 'not set') . "\n";
        echo "-->\n";
    }
}
?>