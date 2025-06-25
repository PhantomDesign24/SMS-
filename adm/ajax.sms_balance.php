<?php
/*
 * 파일명: ajax.sms_balance.php
 * 위치: /adm/ajax.sms_balance.php
 * 기능: SMS 잔액 실시간 조회 AJAX 처리
 * 작성일: 2024-12-28
 */

include_once('./_common.php');

header('Content-Type: application/json');

// ===================================
// 권한 확인
// ===================================
if(!$is_admin) {
    die(json_encode(array(
        'success' => false,
        'message' => '권한이 없습니다.'
    )));
}

// ===================================
// 응답 데이터 초기화
// ===================================
$result = array(
    'success' => false,
    'balance' => 0,
    'message' => ''
);

// ===================================
// SMS 설정 확인
// ===================================
$g5['sms_config_table'] = G5_TABLE_PREFIX.'sms_config';
$sms = sql_fetch("SELECT * FROM {$g5['sms_config_table']} LIMIT 1");

if(!$sms) {
    $result['message'] = 'SMS 설정이 없습니다.';
    die(json_encode($result));
}

// ===================================
// 서비스별 잔액 조회
// ===================================
if($sms['cf_service'] == 'icode') {
    // 아이코드 잔액 조회
    if($config['cf_sms_use'] == 'icode' && $config['cf_icode_id'] && $config['cf_icode_pw']) {
        
        // get_icode_userinfo 함수를 사용하여 잔액 조회
        if(function_exists('get_icode_userinfo')) {
            $userinfo = get_icode_userinfo($config['cf_icode_id'], $config['cf_icode_pw']);
            
            if($userinfo && isset($userinfo['coin'])) {
                // SMS5 테이블이 있으면 업데이트
                if(sql_num_rows(sql_query("SHOW TABLES LIKE '{$g5['sms5_config_table']}'", false))) {
                    // coin을 건수로 변환 (16원 = 1건 기준)
                    $sms_count = floor($userinfo['coin'] / 16);
                    sql_query("UPDATE {$g5['sms5_config_table']} SET cf_point = '{$sms_count}'");
                }
                
                $result['success'] = true;
                $result['balance'] = floor($userinfo['coin'] / 16); // 16원당 1건
                $result['message'] = '잔액 조회 성공';
            } else {
                $result['message'] = '아이코드 잔액 조회 실패';
            }
        } else {
            // get_icode_userinfo 함수가 없으면 DB에서 가져오기
            if(sql_num_rows(sql_query("SHOW TABLES LIKE '{$g5['sms5_config_table']}'", false))) {
                $sms5_config = sql_fetch("SELECT cf_point FROM {$g5['sms5_config_table']}");
                if($sms5_config) {
                    $result['success'] = true;
                    $result['balance'] = $sms5_config['cf_point'];
                    $result['message'] = '저장된 잔액을 표시합니다.';
                }
            } else {
                $result['message'] = 'SMS5가 설치되지 않았습니다.';
            }
        }
    } else {
        $result['message'] = '아이코드 설정이 필요합니다.';
    }
} else if($sms['cf_service'] == 'aligo') {
    // 알리고 잔액 조회
    if($sms['cf_aligo_key'] && $sms['cf_aligo_userid']) {
        if(file_exists(G5_PLUGIN_PATH.'/sms/aligo.php')) {
            include_once(G5_PLUGIN_PATH.'/sms/aligo.php');
            
            try {
                $sms_api = new aligo_sms($sms['cf_aligo_key'], $sms['cf_aligo_userid']);
                $balance_result = $sms_api->get_balance();
                
                if($balance_result['success']) {
                    $result['success'] = true;
                    $result['balance'] = $balance_result['balance'];
                    $result['message'] = '잔액 조회 성공';
                } else {
                    $result['message'] = $balance_result['message'] ?? '알리고 잔액 조회 실패';
                }
            } catch(Exception $e) {
                $result['message'] = '알리고 API 오류: ' . $e->getMessage();
            }
        } else {
            $result['message'] = '알리고 플러그인이 설치되지 않았습니다.';
        }
    } else {
        $result['message'] = '알리고 설정이 필요합니다.';
    }
} else {
    $result['message'] = '지원하지 않는 SMS 서비스입니다.';
}

// ===================================
// JSON 응답 출력
// ===================================
echo json_encode($result);
?>