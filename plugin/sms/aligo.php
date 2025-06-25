<?php
/*
 * 파일명: aligo.php
 * 위치: /plugin/sms/aligo.php
 * 기능: 알리고 SMS API 연동 클래스 (최신 API 스펙 반영)
 * 작성일: 2024-12-28
 */

if (!defined('_GNUBOARD_')) exit;

class aligo_sms {
    private $api_key;
    private $user_id;
    private $sender_key;
    private $server_url = 'https://apis.aligo.in/send/';
    
    /**
     * 생성자
     * 
     * @param string $api_key API 키
     * @param string $user_id 유저 ID
     * @param string $sender_key 발신자 키 (카카오톡용)
     */
    public function __construct($api_key, $user_id, $sender_key = '') {
        $this->api_key = $api_key;
        $this->user_id = $user_id;
        $this->sender_key = $sender_key;
    }
    
    /**
     * SMS 발송
     * 
     * @param string $from 발신번호
     * @param string $to 수신번호
     * @param string $message 메시지
     * @return array 결과 배열
     */
    public function send($from, $to, $message) {
        $result = array(
            'success' => false,
            'message' => '',
            'code' => '',
            'msg_id' => ''
        );
        
        // 전화번호 형식 정리
        $from = preg_replace('/[^0-9]/', '', $from);
        $to = preg_replace('/[^0-9]/', '', $to);
        
        // 메시지 타입 자동 결정 (90바이트 기준)
        $msg_type = (strlen($message) > 90) ? 'LMS' : 'SMS';
        
        // 파라미터 설정
        $params = array(
            'key' => $this->api_key,
            'user_id' => $this->user_id,
            'sender' => $from,
            'receiver' => $to,
            'msg' => $message,
            'msg_type' => $msg_type,
            'testmode_yn' => 'N'
        );
        
        // LMS인 경우 제목 추가
        if($msg_type == 'LMS') {
            $params['title'] = mb_substr($message, 0, 20) . '...';
        }
        
        // cURL 호출
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->server_url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
        
        $response = curl_exec($ch);
        $curl_error = curl_error($ch);
        curl_close($ch);
        
        if($curl_error) {
            $result['message'] = '통신 오류: ' . $curl_error;
            return $result;
        }
        
        // JSON 응답 파싱
        $output = json_decode($response, true);
        
        if($output) {
            $result['code'] = isset($output['result_code']) ? $output['result_code'] : '';
            
            if($output['result_code'] == '1') {
                $result['success'] = true;
                $result['message'] = '발송 성공';
                $result['msg_id'] = isset($output['msg_id']) ? $output['msg_id'] : '';
            } else {
                $result['message'] = $this->get_error_message($output['result_code'], $output['message']);
            }
        } else {
            $result['message'] = '응답 파싱 오류';
        }
        
        return $result;
    }
    
    /**
     * 대량 SMS 발송
     * 
     * @param string $from 발신번호
     * @param array $receivers 수신자 배열 [['phone' => '번호', 'msg' => '메시지', 'name' => '이름'], ...]
     * @param string $msg_type SMS/LMS/MMS
     * @return array 결과 배열
     */
    public function send_mass($from, $receivers, $msg_type = 'SMS') {
        $result = array(
            'success' => false,
            'message' => '',
            'code' => '',
            'msg_id' => ''
        );
        
        if(count($receivers) > 500) {
            $result['message'] = '대량 발송은 최대 500건까지 가능합니다.';
            return $result;
        }
        
        // 전화번호 형식 정리
        $from = preg_replace('/[^0-9]/', '', $from);
        
        // 파라미터 설정
        $params = array(
            'key' => $this->api_key,
            'user_id' => $this->user_id,
            'sender' => $from,
            'msg_type' => $msg_type,
            'cnt' => count($receivers),
            'testmode_yn' => 'N'
        );
        
        // 수신자별 데이터 설정
        $i = 1;
        foreach($receivers as $receiver) {
            $phone = preg_replace('/[^0-9]/', '', $receiver['phone']);
            $params['rec_' . $i] = $phone;
            $params['msg_' . $i] = $receiver['msg'];
            $i++;
        }
        
        // cURL 호출
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://apis.aligo.in/send_mass/');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
        
        $response = curl_exec($ch);
        $curl_error = curl_error($ch);
        curl_close($ch);
        
        if($curl_error) {
            $result['message'] = '통신 오류: ' . $curl_error;
            return $result;
        }
        
        // JSON 응답 파싱
        $output = json_decode($response, true);
        
        if($output) {
            $result['code'] = isset($output['result_code']) ? $output['result_code'] : '';
            
            if($output['result_code'] == '1') {
                $result['success'] = true;
                $result['message'] = '발송 성공';
                $result['msg_id'] = isset($output['msg_id']) ? $output['msg_id'] : '';
                $result['success_cnt'] = isset($output['success_cnt']) ? $output['success_cnt'] : 0;
                $result['error_cnt'] = isset($output['error_cnt']) ? $output['error_cnt'] : 0;
            } else {
                $result['message'] = $this->get_error_message($output['result_code'], $output['message']);
            }
        } else {
            $result['message'] = '응답 파싱 오류';
        }
        
        return $result;
    }
    
    /**
     * 잔액 조회
     * 
     * @return array 결과 배열
     */
    public function get_balance() {
        $result = array(
            'success' => false,
            'balance' => 0,
            'sms_count' => 0,
            'lms_count' => 0,
            'mms_count' => 0,
            'message' => ''
        );
        
        $params = array(
            'key' => $this->api_key,
            'user_id' => $this->user_id
        );
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://apis.aligo.in/remain/');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
        
        $response = curl_exec($ch);
        $curl_error = curl_error($ch);
        curl_close($ch);
        
        if($curl_error) {
            $result['message'] = '통신 오류: ' . $curl_error;
            return $result;
        }
        
        $output = json_decode($response, true);
        
        if($output && $output['result_code'] == '1') {
            $result['success'] = true;
            $result['sms_count'] = intval($output['SMS_CNT']);
            $result['lms_count'] = intval($output['LMS_CNT']);
            $result['mms_count'] = intval($output['MMS_CNT']);
            $result['message'] = '조회 성공';
            
            // 대략적인 잔액 계산 (SMS 기준)
            $result['balance'] = $result['sms_count'];
        } else {
            $result['message'] = isset($output['message']) ? $output['message'] : '잔액 조회 실패';
        }
        
        return $result;
    }
    
    /**
     * 발송 내역 조회
     * 
     * @param array $params 조회 파라미터
     * @return array 결과 배열
     */
    public function get_list($params = array()) {
        $result = array(
            'success' => false,
            'list' => array(),
            'message' => ''
        );
        
        $default_params = array(
            'key' => $this->api_key,
            'user_id' => $this->user_id,
            'page' => 1,
            'page_size' => 30,
            'start_date' => date('Ymd', strtotime('-7 days')),
            'limit_day' => 7
        );
        
        $params = array_merge($default_params, $params);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://apis.aligo.in/list/');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
        
        $response = curl_exec($ch);
        $curl_error = curl_error($ch);
        curl_close($ch);
        
        if($curl_error) {
            $result['message'] = '통신 오류: ' . $curl_error;
            return $result;
        }
        
        $output = json_decode($response, true);
        
        if($output && $output['result_code'] == '1') {
            $result['success'] = true;
            $result['list'] = isset($output['list']) ? $output['list'] : array();
            $result['next_yn'] = isset($output['next_yn']) ? $output['next_yn'] : 'N';
            $result['message'] = '조회 성공';
        } else {
            $result['message'] = isset($output['message']) ? $output['message'] : '내역 조회 실패';
        }
        
        return $result;
    }
    
    /**
     * 전송 결과 상세 조회
     * 
     * @param string $msg_id 메시지 ID
     * @param int $page 페이지
     * @param int $page_size 페이지 크기
     * @return array 결과 배열
     */
    public function get_detail($msg_id, $page = 1, $page_size = 30) {
        $result = array(
            'success' => false,
            'list' => array(),
            'message' => ''
        );
        
        $params = array(
            'key' => $this->api_key,
            'user_id' => $this->user_id,
            'mid' => $msg_id,
            'page' => $page,
            'page_size' => $page_size
        );
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://apis.aligo.in/sms_list/');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
        
        $response = curl_exec($ch);
        $curl_error = curl_error($ch);
        curl_close($ch);
        
        if($curl_error) {
            $result['message'] = '통신 오류: ' . $curl_error;
            return $result;
        }
        
        $output = json_decode($response, true);
        
        if($output && $output['result_code'] == '1') {
            $result['success'] = true;
            $result['list'] = isset($output['list']) ? $output['list'] : array();
            $result['next_yn'] = isset($output['next_yn']) ? $output['next_yn'] : 'N';
            $result['message'] = '조회 성공';
        } else {
            $result['message'] = isset($output['message']) ? $output['message'] : '상세 조회 실패';
        }
        
        return $result;
    }
    
    /**
     * 예약 취소
     * 
     * @param string $msg_id 메시지 ID
     * @return array 결과 배열
     */
    public function cancel($msg_id) {
        $result = array(
            'success' => false,
            'message' => '',
            'cancel_date' => ''
        );
        
        $params = array(
            'key' => $this->api_key,
            'user_id' => $this->user_id,
            'mid' => $msg_id
        );
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://apis.aligo.in/cancel/');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
        
        $response = curl_exec($ch);
        $curl_error = curl_error($ch);
        curl_close($ch);
        
        if($curl_error) {
            $result['message'] = '통신 오류: ' . $curl_error;
            return $result;
        }
        
        $output = json_decode($response, true);
        
        if($output && $output['result_code'] == '1') {
            $result['success'] = true;
            $result['message'] = '취소 완료';
            $result['cancel_date'] = isset($output['cancel_date']) ? $output['cancel_date'] : '';
        } else {
            $result['message'] = isset($output['message']) ? $output['message'] : '취소 실패';
        }
        
        return $result;
    }
    
    /**
     * 에러 메시지 반환
     * 
     * @param string $code 에러 코드
     * @param string $message API 메시지
     * @return string 에러 메시지
     */
    private function get_error_message($code, $message = '') {
        // API에서 제공하는 메시지가 있으면 우선 사용
        if($message) {
            return $message;
        }
        
        // 에러 코드별 메시지
        $errors = array(
            '-101' => '인증 오류입니다.',
            '-102' => '잔액이 부족합니다.',
            '-103' => '회원아이디가 존재하지 않습니다.',
            '-104' => '임시차단된 아이디입니다.',
            '-105' => '팝업차단중입니다. 팝업창 허용 또는 메인창에서 다시 시도해주세요.',
            '-201' => '전화번호 형식이 올바르지 않습니다.',
            '-202' => '메시지 내용이 없습니다.',
            '-203' => '메시지 내용이 너무 깁니다.',
            '-204' => '보내는 번호가 등록되지 않았습니다.',
            '-205' => '등록되지 않은 발신번호입니다.',
            '-206' => '예약시간이 잘못되었습니다.',
            '-207' => '예약시간은 최소 10분 이후부터 가능합니다.',
            '-301' => '이미지 파일이 없습니다.',
            '-302' => '이미지 파일 크기가 너무 큽니다.',
            '-303' => '지원하지 않는 이미지 형식입니다.',
            '-304' => '이미지 업로드 실패',
            '-401' => '전송 타입이 잘못되었습니다.',
            '-402' => '일일 전송량을 초과하였습니다.',
            '-501' => '수신번호가 없습니다.',
            '-502' => '수신번호가 너무 많습니다.',
            '-601' => '웹훅 전송 실패 (연결실패)',
            '-602' => '웹훅 전송 실패 (수신거부)',
            '-701' => '테스트모드에서는 수신번호 10개만 가능합니다.',
            '-801' => '요청하신 건이 존재하지 않습니다.',
            '-802' => '전송취소 권한이 없습니다.',
            '-803' => '이미 전송되었습니다.',
            '-804' => '서버 오류',
            '-805' => '발송 5분전까지만 취소가 가능합니다.',
            '-806' => '이미 취소되었습니다.',
            '-900' => '데이터베이스 오류',
            '-901' => '시스템 오류',
            '-902' => '네트워크 오류',
            '-903' => '서비스 점검중'
        );
        
        return isset($errors[$code]) ? $errors[$code] : '알 수 없는 오류 (코드: '.$code.')';
    }
}
?>