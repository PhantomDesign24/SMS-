<?php
/*
 * 파일명: sms_log_export.php
 * 위치: /adm/sms_log_export.php
 * 기능: SMS 발송 로그 내보내기
 * 작성일: 2024-12-28
 */

include_once('./_common.php');

if ($is_admin != 'super')
    alert('최고관리자만 접근 가능합니다.');

$g5['sms_log_table'] = G5_TABLE_PREFIX.'sms_log';

// ===================================
// 검색 조건 처리 (sms_log.php와 동일)
// ===================================
$sql_search = "";
$search_items = array();

if ($stx) {
    $search_items[] = " (sl_phone like '%{$stx}%' or mb_id like '%{$stx}%' or sl_message like '%{$stx}%') ";
}

if ($sfl) {
    $search_items[] = " sl_type = '{$sfl}' ";
}

if ($sl_result) {
    $search_items[] = " sl_result = '{$sl_result}' ";
}

if ($fr_date && $to_date) {
    $search_items[] = " sl_datetime between '{$fr_date} 00:00:00' and '{$to_date} 23:59:59' ";
} else if ($fr_date && !$to_date) {
    $search_items[] = " sl_datetime >= '{$fr_date} 00:00:00' ";
} else if (!$fr_date && $to_date) {
    $search_items[] = " sl_datetime <= '{$to_date} 23:59:59' ";
}

if (count($search_items) > 0) {
    $sql_search = " where " . implode(" and ", $search_items);
}

// ===================================
// 데이터 조회
// ===================================
$sql = " select * from {$g5['sms_log_table']} $sql_search order by sl_id desc ";
$result = sql_query($sql);

// ===================================
// Excel 헤더 설정
// ===================================
$filename = "sms_log_" . date("Ymd") . ".xls";

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=$filename");
header("Cache-Control: max-age=0");

// UTF-8 BOM
echo "\xEF\xBB\xBF";
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
table { border-collapse: collapse; width: 100%; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
th { background-color: #f2f2f2; }
.success { color: green; }
.fail { color: red; }
</style>
</head>
<body>
<table>
<thead>
<tr>
    <th>번호</th>
    <th>발송일시</th>
    <th>타입</th>
    <th>회원ID</th>
    <th>수신번호</th>
    <th>발신번호</th>
    <th>메시지</th>
    <th>결과</th>
    <th>에러코드</th>
    <th>API응답</th>
    <th>IP</th>
    <th>발송시간</th>
    <th>통신사</th>
    <th>비용</th>
</tr>
</thead>
<tbody>
<?php
$num = 1;
while ($row = sql_fetch_array($result)) {
    // 타입별 표시
    $type_text = '';
    switch($row['sl_type']) {
        case 'register': $type_text = '회원가입'; break;
        case 'password': $type_text = '비밀번호찾기'; break;
        case 'manual': $type_text = '수동발송'; break;
        case 'test': $type_text = '테스트'; break;
        default: $type_text = $row['sl_type']; break;
    }
    
    // 결과 표시
    $result_text = ($row['sl_result'] == 'success') ? '성공' : '실패';
    $result_class = ($row['sl_result'] == 'success') ? 'success' : 'fail';
    
    // API 응답 파싱
    $api_response_text = '';
    if($row['sl_api_response']) {
        $api_data = @unserialize($row['sl_api_response']);
        if(!$api_data) {
            $api_data = @json_decode($row['sl_api_response'], true);
        }
        
        if(is_array($api_data)) {
            $api_response_text = json_encode($api_data, JSON_UNESCAPED_UNICODE);
        } else {
            $api_response_text = $row['sl_api_response'];
        }
    }
?>
<tr>
    <td><?php echo $num++; ?></td>
    <td><?php echo $row['sl_datetime']; ?></td>
    <td><?php echo $type_text; ?></td>
    <td><?php echo $row['mb_id'] ? $row['mb_id'] : '비회원'; ?></td>
    <td><?php echo $row['sl_phone']; ?></td>
    <td><?php echo $row['sl_send_number']; ?></td>
    <td><?php echo htmlspecialchars($row['sl_message']); ?></td>
    <td class="<?php echo $result_class; ?>"><?php echo $result_text; ?></td>
    <td><?php echo $row['sl_error_code']; ?></td>
    <td><?php echo htmlspecialchars($api_response_text); ?></td>
    <td><?php echo $row['sl_ip']; ?></td>
    <td><?php echo $row['sl_send_datetime']; ?></td>
    <td><?php echo $row['sl_carrier']; ?></td>
    <td><?php echo $row['sl_cost']; ?></td>
</tr>
<?php } ?>
</tbody>
</table>
</body>
</html>