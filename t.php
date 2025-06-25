<?php
include_once('./_common.php');

if(!$is_admin) die('관리자만 접근 가능');

include_once(G5_LIB_PATH.'/icode.sms.lib.php');

// 발신번호 테스트 목록
$test_cases = array(
    array('name' => '하이픈 포함', 'number' => '02-381-5552'),
    array('name' => '하이픈 제거 (9자리)', 'number' => '023815552'),
    array('name' => '10자리', 'number' => '0238155552'),
    array('name' => '휴대폰 형식', 'number' => '010-1234-5678'),  // 본인 번호로 변경
);

$recv_hp_mb = "010-8013-6380"; // 받는 전화번호 (하이픈 포함)

echo "<style>
table { border-collapse: collapse; margin: 20px; }
th, td { border: 1px solid #ddd; padding: 10px; }
th { background: #f5f5f5; }
.success { color: green; font-weight: bold; }
.fail { color: red; font-weight: bold; }
</style>";

echo "<h1>아이코드 SMS 발신번호 테스트</h1>";
echo "<p>서버: {$config['cf_icode_server_ip']}:{$config['cf_icode_server_port']}</p>";
echo "<p>ID: {$config['cf_icode_id']}</p>";
echo "<hr>";

echo "<table>";
echo "<tr><th>테스트</th><th>발신번호</th><th>처리된 번호</th><th>결과</th><th>상세</th></tr>";

foreach($test_cases as $test) {
    $send_hp_mb = $test['number'];
    $send_hp = str_replace("-", "", $send_hp_mb); // - 제거
    $recv_hp = str_replace("-", "", $recv_hp_mb); // - 제거
    
    $send_number = "$send_hp";
    $recv_number = "$recv_hp";
    
    $sms_content = "테스트 " . date('H:i:s');
    
    // SMS 발송
    $SMS = new SMS;
    $SMS->SMS_con($config['cf_icode_server_ip'], $config['cf_icode_id'], $config['cf_icode_pw'], $config['cf_icode_server_port']);
    $SMS->Add($recv_number, $send_number, $config['cf_icode_id'], iconv("utf-8", "euc-kr", stripslashes($sms_content)), "");
    $SMS->Send();
    
    // 결과 확인
    $result_detail = "";
    $status = "알수없음";
    $status_class = "";
    
    if(isset($SMS->Result) && is_array($SMS->Result)) {
        $result_detail = print_r($SMS->Result, true);
        
        foreach($SMS->Result as $key => $res) {
            if(strpos($res, 'Error(96)') !== false) {
                $status = "발신번호 미등록";
                $status_class = "fail";
            } else if(strpos($res, 'Error') !== false) {
                $status = "기타 오류";
                $status_class = "fail";
            } else {
                $status = "성공";
                $status_class = "success";
            }
        }
    }
    
    echo "<tr>";
    echo "<td>{$test['name']}</td>";
    echo "<td>{$send_hp_mb}</td>";
    echo "<td>{$send_number}</td>";
    echo "<td class='{$status_class}'>{$status}</td>";
    echo "<td><pre style='margin:0; font-size:11px;'>{$result_detail}</pre></td>";
    echo "</tr>";
}

echo "</table>";

echo "<hr>";
echo "<h3>💡 해결 방법</h3>";
echo "<ol>";
echo "<li><strong>성공</strong>으로 표시된 발신번호 형식을 사용하세요.</li>";
echo "<li>모두 실패한다면 아이코드에 등록된 정확한 발신번호를 확인하세요.</li>";
echo "<li>SMS 인증 설정에서 성공한 형식으로 발신번호를 수정하세요.</li>";
echo "</ol>";

// 추가 디버그 정보
echo "<hr>";
echo "<h3>현재 설정</h3>";
echo "<ul>";
echo "<li>SMS 인증 발신번호: " . (isset($g5_sms_config['cf_phone']) ? $g5_sms_config['cf_phone'] : '미설정') . "</li>";
echo "<li>SMS5 발신번호: ";
if(sql_num_rows(sql_query("SHOW TABLES LIKE '{$g5['sms5_config_table']}'", false))) {
    $sms5_cfg = sql_fetch("select cf_phone from {$g5['sms5_config_table']}");
    echo $sms5_cfg['cf_phone'] ?? '미설정';
} else {
    echo "SMS5 미설치";
}
echo "</li>";
echo "<li>기본환경설정 회신번호: " . ($config['cf_phone'] ?? '미설정') . "</li>";
echo "</ul>";
?>