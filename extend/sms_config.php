<?php
/*
 * 파일명: sms_config.php
 * 위치: /extend/sms_config.php
 * 기능: SMS 인증 시스템 공통 함수 및 설정
 * 작성일: 2024-12-28
 */

if (!defined('_GNUBOARD_')) exit;

// ===================================
// SMS 설정 로드
// ===================================
$g5_sms_config = array();

// 테이블 존재 확인
$sql = " SHOW TABLES LIKE '{$g5['sms_config_table']}' ";
$table_exists = sql_num_rows(sql_query($sql, false));

if($table_exists) {
    $sql = " select * from {$g5['sms_config_table']} limit 1 ";
    $result = sql_query($sql, false);
    if($result && $row = sql_fetch_array($result)) {
        $g5_sms_config = $row;
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
    global $g5_sms_config, $g5;
    
    if(!$g5_sms_config['cf_use_blacklist']) {
        return false;
    }
    
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    $sql = " select count(*) as cnt from {$g5['sms_blacklist_table']} where sb_phone = '".sql_real_escape_string($phone)."' ";
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
    global $g5_sms_config, $g5;
    
    $phone = preg_replace('/[^0-9]/', '', $phone);
    $today = G5_TIME_YMD;
    $current_time = G5_TIME_YMDHIS;
    
    // 오늘 발송 정보 조회
    $sql = " select * from {$g5['sms_limit_table']} 
            where sl_phone = '".sql_real_escape_string($phone)."' 
              and sl_date = '".sql_real_escape_string($today)."' ";
    $limit = sql_fetch($sql);
    
    $result = array(
        'allowed' => true,
        'message' => ''
    );
    
    // 재발송 대기시간 체크
    if($limit && $limit['sl_last_send']) {
        $last_time = strtotime($limit['sl_last_send']);
        $current = strtotime($current_time);
        $diff = $current - $last_time;
        
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
        $last_time = strtotime($limit['sl_last_send']);
        $current = strtotime($current_time);
        $hourly_diff = $current - $last_time;
        
        if($hourly_diff < 3600 && $limit['sl_hourly_count'] >= $g5_sms_config['cf_hourly_limit']) {
            $result['allowed'] = false;
            $result['message'] = '시간당 발송 한도를 초과했습니다.';
            return $result;
        }
    }
    
    // IP별 일일 발송 제한 체크
    $sql = " select sum(sl_ip_count) as total from {$g5['sms_limit_table']} 
            where sl_ip = '".sql_real_escape_string($ip)."' 
              and sl_date = '".sql_real_escape_string($today)."' ";
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
    global $g5;
    
    $phone = preg_replace('/[^0-9]/', '', $phone);
    $today = G5_TIME_YMD;
    $current_time = G5_TIME_YMDHIS;
    
    // 기존 데이터 조회
    $sql = " select * from {$g5['sms_limit_table']} 
            where sl_phone = '".sql_real_escape_string($phone)."' 
              and sl_date = '".sql_real_escape_string($today)."' ";
    $limit = sql_fetch($sql);
    
    if($limit) {
        // 시간당 카운트 리셋 체크
        $last_time = strtotime($limit['sl_last_send']);
        $current = strtotime($current_time);
        $diff = $current - $last_time;
        $hourly_count = ($diff >= 3600) ? 1 : $limit['sl_hourly_count'] + 1;
        
        $sql = " update {$g5['sms_limit_table']} set 
                sl_daily_count = sl_daily_count + 1,
                sl_hourly_count = '".sql_real_escape_string($hourly_count)."',
                sl_last_send = '".sql_real_escape_string($current_time)."',
                sl_ip = '".sql_real_escape_string($ip)."',
                sl_ip_count = sl_ip_count + 1
                where sl_phone = '".sql_real_escape_string($phone)."' 
                  and sl_date = '".sql_real_escape_string($today)."' ";
        sql_query($sql);
    } else {
        $sql = " insert into {$g5['sms_limit_table']} set
                sl_phone = '".sql_real_escape_string($phone)."',
                sl_date = '".sql_real_escape_string($today)."',
                sl_daily_count = 1,
                sl_hourly_count = 1,
                sl_last_send = '".sql_real_escape_string($current_time)."',
                sl_ip = '".sql_real_escape_string($ip)."',
                sl_ip_count = 1 ";
        sql_query($sql);
    }
}

/**
 * SMS 발송 로그 기록
 * 
 * @param array $data 로그 데이터
 */
function insert_sms_log($data) {
    global $g5;
    
    $current_time = G5_TIME_YMDHIS;
    
    $sql = " insert into {$g5['sms_log_table']} set
            sl_type = '".sql_real_escape_string($data['type'])."',
            mb_id = '".sql_real_escape_string($data['mb_id'])."',
            sl_phone = '".sql_real_escape_string($data['phone'])."',
            sl_message = '".sql_real_escape_string($data['message'])."',
            sl_result = '".sql_real_escape_string($data['result'])."',
            sl_ip = '".sql_real_escape_string($data['ip'])."',
            sl_datetime = '".sql_real_escape_string($current_time)."' ";
    sql_query($sql);
}

/**
 * 인증번호 저장
 * 
 * @param array $data 인증 데이터
 * @return boolean 성공 여부
 */
function save_auth_code($data) {
    global $g5_sms_config, $g5;
    
    $current_time = G5_TIME_YMDHIS;
    $expire_time = date('Y-m-d H:i:s', strtotime($current_time) + $g5_sms_config['cf_auth_timeout']);
    
    // 기존 미인증 데이터 삭제
    $sql = " delete from {$g5['sms_auth_table']} 
            where sa_phone = '".sql_real_escape_string($data['phone'])."' 
              and sa_verified = 0 ";
    sql_query($sql);
    
    // 새 인증번호 저장
    $sql = " insert into {$g5['sms_auth_table']} set
            sa_type = '".sql_real_escape_string($data['type'])."',
            sa_phone = '".sql_real_escape_string($data['phone'])."',
            sa_auth_code = '".sql_real_escape_string($data['auth_code'])."',
            sa_ip = '".sql_real_escape_string($data['ip'])."',
            sa_try_count = 0,
            sa_verified = 0,
            sa_datetime = '".sql_real_escape_string($current_time)."',
            sa_expire_datetime = '".sql_real_escape_string($expire_time)."' ";
    
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
    global $g5_sms_config, $g5;
    
    $phone = preg_replace('/[^0-9]/', '', $phone);
    $current_time = G5_TIME_YMDHIS;
    
    $result = array(
        'verified' => false,
        'message' => ''
    );
    
    // 인증 정보 조회
    $sql = " select * from {$g5['sms_auth_table']} 
            where sa_phone = '".sql_real_escape_string($phone)."' 
              and sa_type = '".sql_real_escape_string($type)."'
              and sa_verified = 0
              and sa_expire_datetime > '".sql_real_escape_string($current_time)."'
            order by sa_id desc limit 1 ";
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
    $sql = " update {$g5['sms_auth_table']} set 
            sa_try_count = sa_try_count + 1 
            where sa_id = '".sql_real_escape_string($auth['sa_id'])."' ";
    sql_query($sql);
    
    // 인증번호 확인
    if($auth['sa_auth_code'] === $code) {
        $sql = " update {$g5['sms_auth_table']} set 
                sa_verified = 1 
                where sa_id = '".sql_real_escape_string($auth['sa_id'])."' ";
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
    global $g5;
    
    $phone = preg_replace('/[^0-9]/', '', $phone);
    $current_time = G5_TIME_YMDHIS;
    
    // 30분 이내 인증 완료 데이터 확인
    $expire_time = date('Y-m-d H:i:s', strtotime($current_time) - 1800);
    
    $sql = " select count(*) as cnt from {$g5['sms_auth_table']} 
            where sa_phone = '".sql_real_escape_string($phone)."' 
              and sa_type = '".sql_real_escape_string($type)."'
              and sa_verified = 1
              and sa_datetime > '".sql_real_escape_string($expire_time)."' ";
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
        
        // 1. SMS 문자전송 사용
        if($config['cf_sms_use'] == 'icode' && file_exists(G5_SMS5_PATH.'/sms5.lib.php')) {
            include_once(G5_SMS5_PATH.'/sms5.lib.php');
            
            // SMS 설정값 불러오기
            $sms5 = sql_fetch("select * from {$g5['sms5_config_table']}");
            
            if($sms5['cf_phone']) {
                $SMS = new SMS5();
                $SMS->SMS_con($config['cf_icode_server_ip'], $config['cf_icode_id'], $config['cf_icode_pw'], $config['cf_icode_server_port']);
                
                $recv_list = preg_replace('/[^0-9]/', '', $phone);
                $send_list = preg_replace('/[^0-9]/', '', $g5_sms_config['cf_phone']);
                
                $SMS->Add($recv_list, $send_list, $config['cf_icode_id'], iconv("utf-8", "euc-kr", stripslashes($message)), "");
                $SMS->Send();
                $SMS->Init();
                
                $result['success'] = true;
                $result['message'] = '발송 완료';
            } else {
                $result['message'] = 'SMS 기본설정에서 전화번호를 설정해주세요.';
            }
        }
        // 2. 구버전 라이브러리 확인
        else if(file_exists(G5_LIB_PATH.'/icode.sms.lib.php')) {
            include_once(G5_LIB_PATH.'/icode.sms.lib.php');
            
            // 아이코드 설정 확인
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
            
            $recv_list = preg_replace('/[^0-9]/', '', $phone);
            $send_list = array(preg_replace('/[^0-9]/', '', $g5_sms_config['cf_phone']));
            
            $SMS->Add($recv_list, $send_list, $config['cf_icode_id'], iconv("utf-8", "euc-kr", stripslashes($message)), "");
            $SMS->Send();
            
            $result['success'] = true;
            $result['message'] = '발송 완료';
        } else {
            $result['message'] = 'SMS 모듈이 설치되어 있지 않습니다.';
        }
    } else {
        // 알리고 사용
        if(file_exists(G5_PLUGIN_PATH.'/sms/aligo.php')) {
            include_once(G5_PLUGIN_PATH.'/sms/aligo.php');
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
    global $g5_sms_config, $g5;
    
    if(!$g5_sms_config['cf_use_captcha']) {
        return false;
    }
    
    $phone = preg_replace('/[^0-9]/', '', $phone);
    $today = G5_TIME_YMD;
    
    $sql = " select sl_daily_count from {$g5['sms_limit_table']} 
            where sl_phone = '".sql_real_escape_string($phone)."' 
              and sl_date = '".sql_real_escape_string($today)."' ";
    $limit = sql_fetch($sql);
    
    if($limit && $limit['sl_daily_count'] >= $g5_sms_config['cf_captcha_count']) {
        return true;
    }
    
    return false;
}

// 테이블명 정의
$g5['sms_config_table'] = G5_TABLE_PREFIX.'sms_config';
$g5['sms_log_table'] = G5_TABLE_PREFIX.'sms_log';
$g5['sms_auth_table'] = G5_TABLE_PREFIX.'sms_auth';
$g5['sms_blacklist_table'] = G5_TABLE_PREFIX.'sms_blacklist';
$g5['sms_limit_table'] = G5_TABLE_PREFIX.'sms_limit';
?>