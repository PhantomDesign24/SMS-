<?php
/*
 * 파일명: sms_config.php
 * 위치: /extend/sms_config.php
 * 기능: SMS 인증 시스템 공통 함수 및 설정
 * 작성일: 2024-12-28
 * 수정일: 2024-12-29 (상세 로그 기능 추가)
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
        
        // cf_use_sms 필드가 없는 경우를 위한 처리
        if(!isset($g5_sms_config['cf_use_sms'])) {
            // cf_use_register 또는 cf_use_password가 1이면 SMS 사용으로 간주
            $g5_sms_config['cf_use_sms'] = ($row['cf_use_register'] || $row['cf_use_password']) ? 1 : 0;
        }
        
        // cf_find_use는 cf_use_password와 동일
        $g5_sms_config['cf_find_use'] = $row['cf_use_password'];
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
 * 발송 제한 체크 (상세 로그 포함)
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
    
    // 차단 상태 확인 (sl_block_until 필드가 있는 경우)
    $block_field_exists = sql_fetch("SHOW COLUMNS FROM g5_sms_limit LIKE 'sl_block_until'");
    if($block_field_exists) {
        $sql = "SELECT * FROM g5_sms_limit 
                WHERE sl_phone = '".sql_real_escape_string($phone)."' 
                AND sl_date = '".sql_real_escape_string($today)."'
                AND sl_block_until > '".sql_real_escape_string($current_time)."'";
        $blocked = sql_fetch($sql);
        
        if($blocked) {
            // 차단 시도 로그
            insert_sms_log(array(
                'type' => 'blocked',
                'phone' => $phone,
                'send_number' => $g5_sms_config['cf_phone'],
                'message' => '차단된 상태에서 발송 시도',
                'result' => 'fail',
                'error_code' => 'TEMP_BLOCKED',
                'api_response' => json_encode(array(
                    'block_until' => $blocked['sl_block_until'],
                    'block_reason' => $blocked['sl_block_reason']
                )),
                'ip' => $ip
            ));
            
            return array(
                'allowed' => false,
                'message' => '일시적으로 차단된 번호입니다. '.substr($blocked['sl_block_until'], 0, 16).' 이후 재시도하세요.',
                'code' => 'TEMP_BLOCKED'
            );
        }
    }
    
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
        // 일일 제한 로그
        insert_sms_log(array(
            'type' => 'limit',
            'phone' => $phone,
            'send_number' => $g5_sms_config['cf_phone'],
            'message' => '일일 발송 제한 초과',
            'result' => 'fail',
            'error_code' => 'DAILY_LIMIT',
            'api_response' => json_encode(array(
                'daily_count' => $limit['sl_daily_count'],
                'daily_limit' => $g5_sms_config['cf_daily_limit']
            )),
            'ip' => $ip
        ));
        
        $result['allowed'] = false;
        $result['message'] = '일일 발송 한도를 초과했습니다.';
        $result['code'] = 'DAILY_LIMIT';
        return $result;
    }
    
    // 시간당 발송 제한 체크
    if($limit && $limit['sl_last_send']) {
        $last_send = new DateTime($limit['sl_last_send']);
        $hourly_diff = $now->getTimestamp() - $last_send->getTimestamp();
        
        if($hourly_diff < 3600 && $limit['sl_hourly_count'] >= $g5_sms_config['cf_hourly_limit']) {
            // 시간당 제한 로그
            insert_sms_log(array(
                'type' => 'limit',
                'phone' => $phone,
                'send_number' => $g5_sms_config['cf_phone'],
                'message' => '시간당 발송 제한 초과',
                'result' => 'fail',
                'error_code' => 'HOURLY_LIMIT',
                'api_response' => json_encode(array(
                    'hourly_count' => $limit['sl_hourly_count'],
                    'hourly_limit' => $g5_sms_config['cf_hourly_limit'],
                    'wait_seconds' => 3600 - $hourly_diff
                )),
                'ip' => $ip
            ));
            
            $result['allowed'] = false;
            $result['message'] = '시간당 발송 한도를 초과했습니다.';
            $result['code'] = 'HOURLY_LIMIT';
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
 * SMS 발송 로그 기록 (상세 버전)
 * 
 * @param array $data 로그 데이터
 */
function insert_sms_log($data) {
    $now = new DateTime('now', new DateTimeZone('Asia/Seoul'));
    $current_time = $now->format('Y-m-d H:i:s');
    
    // 기본값 설정
    $data['type'] = isset($data['type']) ? $data['type'] : 'unknown';
    $data['mb_id'] = isset($data['mb_id']) ? $data['mb_id'] : '';
    $data['phone'] = isset($data['phone']) ? $data['phone'] : '';
    $data['send_number'] = isset($data['send_number']) ? $data['send_number'] : '';
    $data['message'] = isset($data['message']) ? $data['message'] : '';
    $data['result'] = isset($data['result']) ? $data['result'] : 'fail';
    $data['error_code'] = isset($data['error_code']) ? $data['error_code'] : '';
    $data['api_response'] = isset($data['api_response']) ? $data['api_response'] : '';
    $data['retry_count'] = isset($data['retry_count']) ? (int)$data['retry_count'] : 0;
    $data['ip'] = isset($data['ip']) ? $data['ip'] : $_SERVER['REMOTE_ADDR'];
    $data['send_datetime'] = isset($data['send_datetime']) ? $data['send_datetime'] : null;
    $data['cost'] = isset($data['cost']) ? $data['cost'] : calculate_sms_cost();
    
    // 비밀번호 찾기인 경우 세션에서 회원 ID 확인
    if($data['type'] == 'password' && !$data['mb_id']) {
        if(isset($_SESSION['ss_password_mb_id'])) {
            $data['mb_id'] = $_SESSION['ss_password_mb_id'];
        }
    }
    
    $sql = "INSERT INTO g5_sms_log SET
            sl_type = '".sql_real_escape_string($data['type'])."',
            mb_id = '".sql_real_escape_string($data['mb_id'])."',
            sl_phone = '".sql_real_escape_string($data['phone'])."',
            sl_message = '".sql_real_escape_string($data['message'])."',
            sl_result = '".sql_real_escape_string($data['result'])."',
            sl_ip = '".sql_real_escape_string($data['ip'])."',
            sl_datetime = '".sql_real_escape_string($current_time)."'";
    
    // 상세 로그 필드 추가 (테이블에 필드가 있는 경우에만)
    $check_fields = array(
        'sl_send_number' => $data['send_number'],
        'sl_error_code' => $data['error_code'],
        'sl_api_response' => $data['api_response'],
        'sl_retry_count' => $data['retry_count'],
        'sl_cost' => $data['cost']
    );
    
    foreach($check_fields as $field => $value) {
        $field_exists = sql_fetch("SHOW COLUMNS FROM g5_sms_log LIKE '{$field}'");
        if($field_exists) {
            $sql .= ", {$field} = '".sql_real_escape_string($value)."'";
        }
    }
    
    if($data['send_datetime']) {
        $field_exists = sql_fetch("SHOW COLUMNS FROM g5_sms_log LIKE 'sl_send_datetime'");
        if($field_exists) {
            $sql .= ", sl_send_datetime = '".sql_real_escape_string($data['send_datetime'])."'";
        }
    }
    
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
    
    // 상세 필드 추가 (필드가 있는 경우)
    $field_exists = sql_fetch("SHOW COLUMNS FROM g5_sms_auth LIKE 'sa_user_agent'");
    if($field_exists) {
        $sql .= ", sa_user_agent = '".sql_real_escape_string($_SERVER['HTTP_USER_AGENT'])."'";
    }
    
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
                sa_verified = 1";
        
        // 인증 완료 시간 필드가 있는 경우
        $field_exists = sql_fetch("SHOW COLUMNS FROM g5_sms_auth LIKE 'sa_verified_datetime'");
        if($field_exists) {
            $sql .= ", sa_verified_datetime = '".sql_real_escape_string($current_time)."'";
        }
        
        $sql .= " WHERE sa_id = '".sql_real_escape_string($auth['sa_id'])."'";
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
 * SMS 발송 함수 (상세 로그 포함)
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
    
    // 발송 시작 시간
    $start_time = microtime(true);
    
    // 블랙리스트 확인
    if(is_blacklisted_phone($phone)) {
        // 차단 번호 발송 시도 로그
        insert_sms_log(array(
            'type' => $type,
            'mb_id' => isset($member['mb_id']) ? $member['mb_id'] : '',
            'phone' => $phone,
            'send_number' => $g5_sms_config['cf_phone'],
            'message' => $message,
            'result' => 'fail',
            'error_code' => 'BLOCKED',
            'api_response' => json_encode(array('message' => '블랙리스트 차단')),
            'ip' => $_SERVER['REMOTE_ADDR']
        ));
        
        $result['message'] = '차단된 전화번호입니다.';
        $result['code'] = 'BLOCKED';
        return $result;
    }
    
    // SMS 서비스별 발송 처리
    if($g5_sms_config['cf_service'] == 'icode') {
        // 그누보드5 기본 SMS 기능 사용
        $sms_send_result = false;
        
        // 1. SMS5 플러그인 확인
        if(defined('G5_SMS5_USE') && G5_SMS5_USE && file_exists(G5_PLUGIN_PATH.'/sms5/sms5.lib.php')) {
            include_once(G5_PLUGIN_PATH.'/sms5/sms5.lib.php');
            
            $sms5 = new SMS5;
            
            // 토큰키 설정 확인
            if(isset($config['cf_icode_token_key']) && $config['cf_icode_token_key']) {
                $sms5->icode_key = $config['cf_icode_token_key'];
                $sms5->socket_host = '211.233.85.196';  // JSON API 서버
                $sms5->socket_port = 9201;               // JSON API 포트 (수정됨)
            }
            
            $sms5->Init();
            
            $send_hp = preg_replace('/[^0-9]/', '', $g5_sms_config['cf_phone']);
            $recv_hp = preg_replace('/[^0-9]/', '', $phone);
            
            // SMS5 발송 (Add2 메서드 사용)
            $strDest = array();
            $strDest[] = array('bk_hp' => $recv_hp);
            
            $sms5->Add2($strDest, $send_hp, '', '', $message, '', 1);
            $sms5->Send();
            
            // 발송 종료 시간
            $end_time = microtime(true);
            $duration = round(($end_time - $start_time) * 1000); // 밀리초
            
            if(count($sms5->Result) > 0) {
                $res = $sms5->Result[0];
                if(strpos($res, 'Error') === false) {
                    $result['success'] = true;
                    $result['message'] = '발송 성공';
                    
                    // 성공 로그
                    insert_sms_log(array(
                        'type' => $type,
                        'mb_id' => isset($member['mb_id']) ? $member['mb_id'] : '',
                        'phone' => $phone,
                        'send_number' => $send_hp,
                        'message' => $message,
                        'result' => 'success',
                        'error_code' => '',
                        'api_response' => json_encode(array(
                            'Result' => $sms5->Result,
                            'duration_ms' => $duration
                        )),
                        'send_datetime' => date('Y-m-d H:i:s'),
                        'retry_count' => 0,
                        'cost' => calculate_sms_cost(),
                        'ip' => $_SERVER['REMOTE_ADDR']
                    ));
                } else {
                    $result['success'] = false;
                    $result['message'] = '발송 실패: ' . $res;
                    
                    $error_code = '';
                    if(strpos($res, 'Error') !== false) {
                        $error_code = substr($res, 6, 2);
                    }
                    
                    // 실패 로그
                    insert_sms_log(array(
                        'type' => $type,
                        'mb_id' => isset($member['mb_id']) ? $member['mb_id'] : '',
                        'phone' => $phone,
                        'send_number' => $send_hp,
                        'message' => $message,
                        'result' => 'fail',
                        'error_code' => $error_code,
                        'api_response' => json_encode(array(
                            'Result' => $sms5->Result,
                            'error' => $res,
                            'duration_ms' => $duration
                        )),
                        'retry_count' => 0,
                        'ip' => $_SERVER['REMOTE_ADDR']
                    ));
                }
            } else {
                $result['success'] = false;
                $result['message'] = '발송 실패';
            }
            $sms_send_result = true;
        }
        // 2. 구버전 아이코드 라이브러리 확인
        else if(file_exists(G5_LIB_PATH.'/icode.sms.lib.php')) {
            include_once(G5_LIB_PATH.'/icode.sms.lib.php');
            
            // 아이코드 설정 확인
            $icode_id = '';
            $icode_pw = '';
            $icode_server_ip = '211.172.232.124';
            $icode_server_port = 7295;
            
            // 1순위: 그누보드 기본 설정
            if(!empty($config['cf_icode_id']) && !empty($config['cf_icode_pw'])) {
                $icode_id = $config['cf_icode_id'];
                $icode_pw = $config['cf_icode_pw'];
                if(!empty($config['cf_icode_server_ip'])) {
                    $icode_server_ip = $config['cf_icode_server_ip'];
                }
                if(!empty($config['cf_icode_server_port'])) {
                    $icode_server_port = (int)$config['cf_icode_server_port'];
                }
            }
            // 2순위: SMS 인증 설정
            else if(!empty($g5_sms_config['cf_icode_id']) && !empty($g5_sms_config['cf_icode_pw'])) {
                $icode_id = $g5_sms_config['cf_icode_id'];
                $icode_pw = $g5_sms_config['cf_icode_pw'];
            }
            
            if(!$icode_id || !$icode_pw) {
                $result['message'] = '아이코드 설정이 되어있지 않습니다.';
                return $result;
            }
            
            // 발신번호 설정
            $send_number = $g5_sms_config['cf_phone'];
            if(!$send_number && isset($config['cf_phone'])) {
                $send_number = $config['cf_phone']; // 기본 설정에서 가져오기
            }
            
            // SMS5 설정에서도 확인
            if(!$send_number && isset($sms5) && isset($sms5['cf_phone'])) {
                $send_number = $sms5['cf_phone'];
            }
            
            if(!$send_number) {
                $result['message'] = '발신번호가 설정되지 않았습니다.';
                return $result;
            }
            
            // 발송
            $recv_number = str_replace("-", "", $phone);  // 수신번호 (- 제거)
            $send_number_formatted = str_replace("-", "", $send_number);  // 발신번호 (- 제거)
            
            try {
                $SMS = new SMS;
                $SMS->SMS_con($icode_server_ip, $icode_id, $icode_pw, $icode_server_port);
                
                // 메시지 인코딩
                $encoded_msg = iconv("utf-8", "euc-kr", stripslashes($message));
                
                // Add 메서드 호출
                $SMS->Add($recv_number, $send_number_formatted, $icode_id, $encoded_msg, "");
                
                // Send 전 상태 로그
                $debug_log = "=== SMS 발송 시도 ===\n";
                $debug_log .= "시간: " . date('Y-m-d H:i:s') . "\n";
                $debug_log .= "아이디: " . $icode_id . "\n";
                $debug_log .= "서버: " . $icode_server_ip . ":" . $icode_server_port . "\n";
                $debug_log .= "수신: " . $recv_number . "\n";
                $debug_log .= "발신: " . $send_number_formatted . "\n";
                $debug_log .= "메시지: " . $message . "\n";
                
                // Send 실행
                $SMS->Send();
                
                // 발송 종료 시간
                $end_time = microtime(true);
                $duration = round(($end_time - $start_time) * 1000); // 밀리초
                
                // 결과 확인
                $debug_log .= "Result: " . print_r($SMS->Result, true) . "\n";
                $debug_log .= "success_cnt: " . (isset($SMS->success_cnt) ? $SMS->success_cnt : '0') . "\n";
                $debug_log .= "fail_cnt: " . (isset($SMS->fail_cnt) ? $SMS->fail_cnt : '0') . "\n";
                $debug_log .= "============================\n\n";
                
                @file_put_contents(G5_DATA_PATH.'/sms_debug.log', $debug_log, FILE_APPEND);
                
                // API 응답 구성
                $api_response = array(
                    'Result' => $SMS->Result,
                    'success_cnt' => isset($SMS->success_cnt) ? $SMS->success_cnt : 0,
                    'fail_cnt' => isset($SMS->fail_cnt) ? $SMS->fail_cnt : 0,
                    'duration_ms' => $duration
                );
                
                // 성공 판단
                if(isset($SMS->Result) && is_array($SMS->Result)) {
                    foreach($SMS->Result as $key => $res) {
                        if(strpos($res, 'Error') === false) {
                            $result['success'] = true;
                            $result['message'] = '발송 성공';
                            
                            // 성공 로그
                            insert_sms_log(array(
                                'type' => $type,
                                'mb_id' => isset($member['mb_id']) ? $member['mb_id'] : '',
                                'phone' => $phone,
                                'send_number' => $send_number_formatted,
                                'message' => $message,
                                'result' => 'success',
                                'error_code' => '',
                                'api_response' => json_encode($api_response),
                                'send_datetime' => date('Y-m-d H:i:s'),
                                'retry_count' => 0,
                                'cost' => calculate_sms_cost(),
                                'ip' => $_SERVER['REMOTE_ADDR']
                            ));
                            break;
                        } else {
                            // 에러 처리
                            $error_code = substr($res, 6, 2);
                            $error_msg = get_sms_error_message($error_code);
                            
                            $result['success'] = false;
                            $result['message'] = $error_msg;
                            $result['code'] = $error_code;
                            
                            // 실패 로그
                            insert_sms_log(array(
                                'type' => $type,
                                'mb_id' => isset($member['mb_id']) ? $member['mb_id'] : '',
                                'phone' => $phone,
                                'send_number' => $send_number_formatted,
                                'message' => $message,
                                'result' => 'fail',
                                'error_code' => $error_code,
                                'api_response' => json_encode($api_response),
                                'retry_count' => 0,
                                'ip' => $_SERVER['REMOTE_ADDR']
                            ));
                        }
                    }
                } else {
                    $result['success'] = true;
                    $result['message'] = '발송 성공';
                    
                    // 성공 로그
                    insert_sms_log(array(
                        'type' => $type,
                        'mb_id' => isset($member['mb_id']) ? $member['mb_id'] : '',
                        'phone' => $phone,
                        'send_number' => $send_number_formatted,
                        'message' => $message,
                        'result' => 'success',
                        'error_code' => '',
                        'api_response' => json_encode($api_response),
                        'send_datetime' => date('Y-m-d H:i:s'),
                        'retry_count' => 0,
                        'cost' => calculate_sms_cost(),
                        'ip' => $_SERVER['REMOTE_ADDR']
                    ));
                }
                
            } catch(Exception $e) {
                $result['success'] = false;
                $result['message'] = '발송 실패: ' . $e->getMessage();
                
                // 예외 로그
                insert_sms_log(array(
                    'type' => $type,
                    'mb_id' => isset($member['mb_id']) ? $member['mb_id'] : '',
                    'phone' => $phone,
                    'send_number' => $send_number_formatted,
                    'message' => $message,
                    'result' => 'fail',
                    'error_code' => 'EXCEPTION',
                    'api_response' => json_encode(array('error' => $e->getMessage())),
                    'retry_count' => 0,
                    'ip' => $_SERVER['REMOTE_ADDR']
                ));
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
            $aligo_result = $sms->send($g5_sms_config['cf_phone'], $phone, $message);
            
            // 발송 종료 시간
            $end_time = microtime(true);
            $duration = round(($end_time - $start_time) * 1000); // 밀리초
            
            if($aligo_result['success']) {
                $result = $aligo_result;
                
                // 성공 로그
                insert_sms_log(array(
                    'type' => $type,
                    'mb_id' => isset($member['mb_id']) ? $member['mb_id'] : '',
                    'phone' => $phone,
                    'send_number' => $g5_sms_config['cf_phone'],
                    'message' => $message,
                    'result' => 'success',
                    'error_code' => '',
                    'api_response' => json_encode(array_merge($aligo_result, array('duration_ms' => $duration))),
                    'send_datetime' => date('Y-m-d H:i:s'),
                    'retry_count' => 0,
                    'cost' => calculate_sms_cost(),
                    'ip' => $_SERVER['REMOTE_ADDR']
                ));
            } else {
                $result = $aligo_result;
                
                // 실패 로그
                insert_sms_log(array(
                    'type' => $type,
                    'mb_id' => isset($member['mb_id']) ? $member['mb_id'] : '',
                    'phone' => $phone,
                    'send_number' => $g5_sms_config['cf_phone'],
                    'message' => $message,
                    'result' => 'fail',
                    'error_code' => 'ALIGO_FAIL',
                    'api_response' => json_encode(array_merge($aligo_result, array('duration_ms' => $duration))),
                    'retry_count' => 0,
                    'ip' => $_SERVER['REMOTE_ADDR']
                ));
            }
        } else {
            $result['message'] = '알리고 API 파일이 없습니다.';
            return $result;
        }
    }
    
    // 성공시 발송 제한 업데이트
    if($result['success']) {
        update_sms_limit($phone, $_SERVER['REMOTE_ADDR']);
    }
    
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

/**
 * SMS 잔액 조회
 * 
 * @return array 잔액 정보
 */
function get_sms_balance() {
    global $config, $g5_sms_config;
    
    $balance_info = array(
        'success' => false,
        'balance' => 0,
        'message' => ''
    );
    
    if($g5_sms_config['cf_service'] == 'icode') {
        // 아이코드 사용
        if($config['cf_sms_use'] == 'icode' && $config['cf_icode_id'] && $config['cf_icode_pw']) {
            // get_icode_userinfo 함수 사용
            if(function_exists('get_icode_userinfo')) {
                $userinfo = get_icode_userinfo($config['cf_icode_id'], $config['cf_icode_pw']);
                
                if($userinfo && isset($userinfo['coin'])) {
                    $balance_info['success'] = true;
                    $balance_info['balance'] = floor($userinfo['coin'] / 16); // 16원당 1건
                    $balance_info['message'] = '잔액 조회 성공';
                } else {
                    $balance_info['message'] = '아이코드 잔액 조회 실패';
                }
            } else {
                $balance_info['message'] = 'get_icode_userinfo 함수가 없습니다.';
            }
        } else {
            $balance_info['message'] = '아이코드 설정이 필요합니다.';
        }
    } else if($g5_sms_config['cf_service'] == 'aligo') {
        // 알리고 사용
        if($g5_sms_config['cf_aligo_key'] && $g5_sms_config['cf_aligo_userid']) {
            if(file_exists(G5_PATH.'/plugin/sms/aligo.php')) {
                include_once(G5_PATH.'/plugin/sms/aligo.php');
                $sms_api = new aligo_sms($g5_sms_config['cf_aligo_key'], $g5_sms_config['cf_aligo_userid']);
                $balance_info = $sms_api->get_balance();
            } else {
                $balance_info['message'] = '알리고 플러그인이 설치되지 않았습니다.';
            }
        } else {
            $balance_info['message'] = '알리고 설정이 필요합니다.';
        }
    }
    
    return $balance_info;
}

/**
 * SMS 비용 계산
 */
function calculate_sms_cost() {
    global $g5_sms_config;
    
    if(isset($g5_sms_config['cf_cost_type']) && $g5_sms_config['cf_cost_type'] == 'monthly') {
        return 0; // 정액제는 건당 비용 0
    } else if(isset($g5_sms_config['cf_cost_per_sms'])) {
        return $g5_sms_config['cf_cost_per_sms'];
    }
    
    return 0;
}

/**
 * SMS 에러 코드 메시지 반환
 */
function get_sms_error_message($code) {
    $messages = array(
        '02' => '형식이 잘못되어 전송이 실패하였습니다.',
        '23' => '데이터를 다시 확인해 주시기바랍니다.',
        '97' => '잔여코인이 부족합니다.',
        '98' => '사용기간이 만료되었습니다.',
        '99' => '인증 받지 못하였습니다. 계정을 다시 확인해 주세요.',
        'BLOCKED' => '차단된 번호입니다.',
        'DAILY_LIMIT' => '일일 발송 제한을 초과했습니다.',
        'HOURLY_LIMIT' => '시간당 발송 제한을 초과했습니다.',
        'TEMP_BLOCKED' => '일시적으로 차단되었습니다.',
    );
    
    return isset($messages[$code]) ? $messages[$code] : '알 수 없는 오류로 전송이 실패하였습니다.';
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