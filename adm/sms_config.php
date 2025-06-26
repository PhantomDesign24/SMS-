<?php
/*
 * 파일명: sms_config.php
 * 위치: /adm/sms_config.php
 * 기능: SMS 인증 시스템 관리자 설정 페이지
 * 작성일: 2024-12-28
 * 수정일: 2024-12-28
 */

$sub_menu = "900920";
include_once('./_common.php');

if ($is_admin != 'super')
    alert('최고관리자만 접근 가능합니다.');

// 테이블명 정의
$g5['sms_config_table'] = G5_TABLE_PREFIX.'sms_config';

// ===================================
// DB 테이블 존재 확인
// ===================================
$sql = " SHOW TABLES LIKE '{$g5['sms_config_table']}' ";
$result = sql_query($sql, false);
if(!sql_num_rows($result)) {
    alert('SMS 인증 시스템이 설치되지 않았습니다.\\n\\n설치 페이지로 이동합니다.', './sms_install.php');
}

$g5['title'] = 'SMS 인증 설정';
include_once('./admin.head.php');

// SMS 설정 불러오기
$sql = " select * from {$g5['sms_config_table']} limit 1 ";
$sms = sql_fetch($sql);

if(!$sms) {
    sql_query(" insert into {$g5['sms_config_table']} set cf_service = 'icode' ");
    $sms = sql_fetch($sql);
}

// ===================================
// 잔액 조회
// ===================================
$balance_info = array(
    'success' => false,
    'balance' => '조회 중...',
    'api_type' => ''
);

// SMS5 사용 확인
if($sms['cf_service'] == 'icode') {
    // 그누보드 기본 설정 확인
    if($config['cf_sms_use'] == 'icode' && $config['cf_icode_id'] && $config['cf_icode_pw']) {
        
        // get_icode_userinfo 함수로 잔액 조회
        if(function_exists('get_icode_userinfo')) {
            $userinfo = get_icode_userinfo($config['cf_icode_id'], $config['cf_icode_pw']);
            
            if($userinfo && isset($userinfo['coin'])) {
                $balance_info['success'] = true;
                // 16원당 1건으로 계산
                $sms_count = floor($userinfo['coin'] / 16);
                $balance_info['balance'] = number_format($sms_count).'건';
                
                // 토큰키 사용 여부 표시
                if(isset($config['cf_icode_token_key']) && $config['cf_icode_token_key']) {
                    $balance_info['api_type'] = 'JSON API (토큰)';
                } else {
                    $balance_info['api_type'] = '구버전 API';
                }
            } else {
                $balance_info['balance'] = '조회 실패';
            }
        } else {
            // SMS5 테이블에서 조회
            if(sql_num_rows(sql_query("SHOW TABLES LIKE '{$g5['sms5_config_table']}'", false))) {
                $sms5_config = sql_fetch("select * from {$g5['sms5_config_table']}");
                if($sms5_config && isset($sms5_config['cf_point'])) {
                    $balance_info['success'] = true;
                    $balance_info['balance'] = number_format($sms5_config['cf_point']).'건';
                    
                    // 토큰키 사용 여부 표시
                    if(isset($config['cf_icode_token_key']) && $config['cf_icode_token_key']) {
                        $balance_info['api_type'] = 'JSON API (토큰)';
                    } else {
                        $balance_info['api_type'] = '구버전 API';
                    }
                } else {
                    $balance_info['balance'] = '설정 필요';
                }
            } else {
                $balance_info['balance'] = 'SMS5 미설치';
            }
        }
    } else {
        $balance_info['balance'] = '설정 필요';
    }
} else if($sms['cf_service'] == 'aligo' && $sms['cf_aligo_key'] && $sms['cf_aligo_userid']) {
    if(file_exists(G5_PLUGIN_PATH.'/sms/aligo.php')) {
        include_once(G5_PLUGIN_PATH.'/sms/aligo.php');
        $sms_api = new aligo_sms($sms['cf_aligo_key'], $sms['cf_aligo_userid']);
        $balance_result = $sms_api->get_balance();
        if($balance_result['success']) {
            $balance_info = $balance_result;
        } else {
            $balance_info['balance'] = '조회 실패';
        }
    } else {
        $balance_info['balance'] = '알리고 플러그인 미설치';
    }
}
?>

<div class="local_desc01 local_desc">
    <p>SMS 인증 시스템의 기본 설정을 관리합니다.</p>
    <p><a href="./sms_service_guide.php" class="btn btn_02" target="_blank">📖 SMS 서비스 가입 가이드</a></p>
</div>

<form name="fsmsconfig" id="fsmsconfig" action="./sms_config_update.php" method="post" onsubmit="return fsmsconfig_submit(this);">
<input type="hidden" name="token" value="">

<section>
    <h2 class="h2_frm">기본 설정</h2>
    <?php echo $pg_anchor ?? ''; ?>
    
    <div class="tbl_frm01 tbl_wrap">
        <table>
        <caption>SMS 기본 설정</caption>
        <colgroup>
            <col class="grid_4">
            <col>
        </colgroup>
        <tbody>
        <tr>
            <th scope="row"><label for="cf_service">SMS 서비스 선택</label></th>
            <td>
                <select name="cf_service" id="cf_service" onchange="change_service(this.value)">
                    <option value="icode" <?php echo get_selected($sms['cf_service'], 'icode'); ?>>아이코드</option>
                    <option value="aligo" <?php echo get_selected($sms['cf_service'], 'aligo'); ?>>알리고</option>
                </select>
                <span class="frm_info">
                    잔액: <span id="sms_balance"><?php echo $balance_info['balance']; ?></span>
                    <?php if(isset($balance_info['api_type']) && $balance_info['api_type']) { ?>
                        (<?php echo $balance_info['api_type']; ?>)
                    <?php } ?>
                    <button type="button" class="btn btn_01" onclick="update_sms_balance()">새로고침</button>
                </span>
            </td>
        </tr>
        
        <!-- 아이코드 설정 -->
        <tr class="service_icode" style="display:none">
            <th scope="row" colspan="2" style="text-align:center; background-color:#f8f9fa;">
                <?php if($config['cf_sms_use'] == 'icode' && $config['cf_icode_id'] && $config['cf_icode_pw']) { ?>
                <div style="padding:10px; color:#0066cc;">
                    <strong>※ 그누보드 기본 SMS 설정이 적용됩니다.</strong><br>
                    <small>ID: <?php echo $config['cf_icode_id']; ?></small><br>
                    <?php if(isset($config['cf_icode_token_key']) && $config['cf_icode_token_key']) { ?>
                    <small style="color:green;">토큰키: 설정됨 (JSON API)</small>
                    <?php } else { ?>
                    <small style="color:orange;">토큰키: 미설정 (구버전 API)</small>
                    <?php } ?>
                    <br><small>환경설정 → 기본환경설정 → SMS에서 수정 가능</small>
                </div>
                <?php } else { ?>
                <div style="padding:10px; color:#ff6666;">
                    <strong>※ 그누보드 SMS 설정이 필요합니다.</strong><br>
                    <small>환경설정 → 기본환경설정 → SMS에서 설정해주세요.</small>
                </div>
                <?php } ?>
            </th>
        </tr>
        
        <!-- 알리고 설정 -->
        <tr class="service_aligo" style="display:none">
            <th scope="row"><label for="cf_aligo_key">알리고 API Key <strong class="sound_only">필수</strong></label></th>
            <td>
                <input type="text" name="cf_aligo_key" value="<?php echo isset($sms['cf_aligo_key']) ? $sms['cf_aligo_key'] : ''; ?>" id="cf_aligo_key" class="frm_input" size="50">
                <span class="frm_info">알리고에서 발급받은 API Key</span>
            </td>
        </tr>
        <tr class="service_aligo" style="display:none">
            <th scope="row"><label for="cf_aligo_userid">알리고 User ID <strong class="sound_only">필수</strong></label></th>
            <td>
                <input type="text" name="cf_aligo_userid" value="<?php echo isset($sms['cf_aligo_userid']) ? $sms['cf_aligo_userid'] : ''; ?>" id="cf_aligo_userid" class="frm_input" size="20">
                <span class="frm_info">알리고 로그인 아이디</span>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="cf_phone">발신번호 <strong class="sound_only">필수</strong></label></th>
            <td>
                <input type="text" name="cf_phone" value="<?php echo isset($sms['cf_phone']) ? $sms['cf_phone'] : ''; ?>" id="cf_phone" required class="required frm_input" size="20">
                <span class="frm_info">사전 등록된 발신번호 (예: 02-123-4567, 010-1234-5678)</span>
            </td>
        </tr>
        </tbody>
        </table>
    </div>
</section>

<section>
    <h2 class="h2_frm">사용 설정</h2>
    
    <div class="tbl_frm01 tbl_wrap">
        <table>
        <caption>SMS 사용 설정</caption>
        <colgroup>
            <col class="grid_4">
            <col>
        </colgroup>
        <tbody>
        <tr>
            <th scope="row">SMS 사용</th>
            <td>
                <label><input type="checkbox" name="cf_use_sms" value="1" <?php echo ($sms['cf_use_sms'] ?? 0) ? 'checked' : ''; ?>> SMS 인증 기능 사용</label>
                <p class="info">체크하면 SMS 인증 기능을 사용합니다.</p>
            </td>
        </tr>
        <tr>
            <th scope="row">회원가입 SMS 인증</th>
            <td>
                <label for="cf_use_register_1"><input type="radio" name="cf_use_register" value="1" id="cf_use_register_1" <?php echo get_checked($sms['cf_use_register'] ?? 0, 1); ?>> 사용</label>
                <label for="cf_use_register_0"><input type="radio" name="cf_use_register" value="0" id="cf_use_register_0" <?php echo get_checked($sms['cf_use_register'] ?? 0, 0); ?>> 사용안함</label>
            </td>
        </tr>
        <tr>
            <th scope="row">비밀번호 찾기 SMS 인증</th>
            <td>
                <label for="cf_use_password_1"><input type="radio" name="cf_use_password" value="1" id="cf_use_password_1" <?php echo get_checked($sms['cf_use_password'] ?? 0, 1); ?>> 사용</label>
                <label for="cf_use_password_0"><input type="radio" name="cf_use_password" value="0" id="cf_use_password_0" <?php echo get_checked($sms['cf_use_password'] ?? 0, 0); ?>> 사용안함</label>
            </td>
        </tr>
        </tbody>
        </table>
    </div>
</section>

<section>
    <h2 class="h2_frm">발송 제한 설정</h2>
    
    <div class="tbl_frm01 tbl_wrap">
        <table>
        <caption>발송 제한 설정</caption>
        <colgroup>
            <col class="grid_4">
            <col>
        </colgroup>
        <tbody>
        <tr>
            <th scope="row"><label for="cf_daily_limit">일일 발송 제한</label></th>
            <td>
                <input type="number" name="cf_daily_limit" value="<?php echo $sms['cf_daily_limit'] ?? 10; ?>" id="cf_daily_limit" class="frm_input" size="5" min="1" max="100"> 회
                <span class="frm_info">동일 번호로 하루에 발송 가능한 최대 횟수</span>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="cf_hourly_limit">시간당 발송 제한</label></th>
            <td>
                <input type="number" name="cf_hourly_limit" value="<?php echo $sms['cf_hourly_limit'] ?? 5; ?>" id="cf_hourly_limit" class="frm_input" size="5" min="1" max="50"> 회
                <span class="frm_info">동일 번호로 1시간 내 발송 가능한 최대 횟수</span>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="cf_resend_delay">재발송 대기시간</label></th>
            <td>
                <input type="number" name="cf_resend_delay" value="<?php echo $sms['cf_resend_delay'] ?? 60; ?>" id="cf_resend_delay" class="frm_input" size="5" min="30" max="600"> 초
                <span class="frm_info">동일 번호로 재발송 시 최소 대기시간</span>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="cf_ip_daily_limit">IP당 일일 발송 제한</label></th>
            <td>
                <input type="number" name="cf_ip_daily_limit" value="<?php echo $sms['cf_ip_daily_limit'] ?? 50; ?>" id="cf_ip_daily_limit" class="frm_input" size="5" min="1" max="200"> 회
                <span class="frm_info">동일 IP에서 하루에 발송 가능한 최대 횟수</span>
            </td>
        </tr>
        </tbody>
        </table>
    </div>
</section>

<section>
    <h2 class="h2_frm">인증 설정</h2>
    
    <div class="tbl_frm01 tbl_wrap">
        <table>
        <caption>인증 설정</caption>
        <colgroup>
            <col class="grid_4">
            <col>
        </colgroup>
        <tbody>
        <tr>
            <th scope="row"><label for="cf_auth_timeout">인증번호 유효시간</label></th>
            <td>
                <input type="number" name="cf_auth_timeout" value="<?php echo $sms['cf_auth_timeout'] ?? 180; ?>" id="cf_auth_timeout" class="frm_input" size="5" min="60" max="600"> 초
                <span class="frm_info">인증번호 입력 제한시간 (60~600초)</span>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="cf_max_try">최대 인증 시도 횟수</label></th>
            <td>
                <input type="number" name="cf_max_try" value="<?php echo $sms['cf_max_try'] ?? 5; ?>" id="cf_max_try" class="frm_input" size="5" min="3" max="10"> 회
                <span class="frm_info">인증번호 입력 실패 허용 횟수</span>
            </td>
        </tr>
        </tbody>
        </table>
    </div>
</section>

<section>
    <h2 class="h2_frm">보안 설정</h2>
    
    <div class="tbl_frm01 tbl_wrap">
        <table>
        <caption>보안 설정</caption>
        <colgroup>
            <col class="grid_4">
            <col>
        </colgroup>
        <tbody>
        <tr>
            <th scope="row">캡차 사용</th>
            <td>
                <label for="cf_use_captcha_1"><input type="radio" name="cf_use_captcha" value="1" id="cf_use_captcha_1" <?php echo get_checked($sms['cf_use_captcha'] ?? 0, 1); ?>> 사용</label>
                <label for="cf_use_captcha_0"><input type="radio" name="cf_use_captcha" value="0" id="cf_use_captcha_0" <?php echo get_checked($sms['cf_use_captcha'] ?? 0, 0); ?>> 사용안함</label>
                <span class="frm_info">일정 횟수 이상 요청 시 캡차 표시</span>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="cf_captcha_count">캡차 표시 기준</label></th>
            <td>
                <input type="number" name="cf_captcha_count" value="<?php echo $sms['cf_captcha_count'] ?? 3; ?>" id="cf_captcha_count" class="frm_input" size="5" min="1" max="10"> 회
                <span class="frm_info">일일 발송 횟수가 이 값 이상일 때 캡차 표시</span>
            </td>
        </tr>
        <tr>
            <th scope="row">해외번호 차단</th>
            <td>
                <label for="cf_block_foreign_1"><input type="radio" name="cf_block_foreign" value="1" id="cf_block_foreign_1" <?php echo get_checked($sms['cf_block_foreign'] ?? 1, 1); ?>> 차단</label>
                <label for="cf_block_foreign_0"><input type="radio" name="cf_block_foreign" value="0" id="cf_block_foreign_0" <?php echo get_checked($sms['cf_block_foreign'] ?? 1, 0); ?>> 허용</label>
                <span class="frm_info">국내 휴대폰 번호만 허용 (010, 011, 016, 017, 018, 019)</span>
            </td>
        </tr>
        <tr>
            <th scope="row">블랙리스트 사용</th>
            <td>
                <label for="cf_use_blacklist_1"><input type="radio" name="cf_use_blacklist" value="1" id="cf_use_blacklist_1" <?php echo get_checked($sms['cf_use_blacklist'] ?? 1, 1); ?>> 사용</label>
                <label for="cf_use_blacklist_0"><input type="radio" name="cf_use_blacklist" value="0" id="cf_use_blacklist_0" <?php echo get_checked($sms['cf_use_blacklist'] ?? 1, 0); ?>> 사용안함</label>
                <span class="frm_info">차단된 번호 관리 기능 사용</span>
            </td>
        </tr>
        </tbody>
        </table>
    </div>
</section>

<div class="btn_fixed_top">
    <a href="./sms_log.php" class="btn btn_02">발송내역</a>
    <a href="./sms_blacklist.php" class="btn btn_02">차단번호</a>
    <input type="submit" value="확인" class="btn_submit btn" accesskey="s">
</div>

</form>

<script>
function change_service(service) {
    if(service == 'icode') {
        $('.service_icode').show();
        $('.service_aligo').hide();
    } else {
        $('.service_icode').hide();
        $('.service_aligo').show();
    }
}

function fsmsconfig_submit(f) {
    var service = f.cf_service.value;
    
    if(service == 'aligo') {
        if(!f.cf_aligo_key.value) {
            alert('알리고 API Key를 입력하세요.');
            f.cf_aligo_key.focus();
            return false;
        }
        if(!f.cf_aligo_userid.value) {
            alert('알리고 User ID를 입력하세요.');
            f.cf_aligo_userid.focus();
            return false;
        }
    }
    
    if(!f.cf_phone.value) {
        alert('발신번호를 입력하세요.');
        f.cf_phone.focus();
        return false;
    }
    
    // 발신번호 형식 체크 - 하이픈 허용
    var phone = f.cf_phone.value;
    // 하이픈이 있든 없든 숫자만 추출해서 검증
    var phoneNumbers = phone.replace(/[^0-9]/g, '');
    if(!/^0[0-9]{8,10}$/.test(phoneNumbers)) {
        alert('올바른 발신번호 형식이 아닙니다.');
        f.cf_phone.focus();
        return false;
    }
    
    return true;
}

// 페이지 로드 시 현재 선택된 서비스에 맞게 표시
$(function() {
    change_service('<?php echo $sms['cf_service']; ?>');
});

// 잔액 새로고침
function update_sms_balance() {
    $.ajax({
        url: './ajax.sms_balance.php',
        type: 'GET',
        dataType: 'json',
        beforeSend: function() {
            $('#sms_balance').text('조회 중...');
        },
        success: function(data) {
            if(data.success) {
                $('#sms_balance').text(number_format(data.balance) + '건');
                alert('잔액이 갱신되었습니다.');
            } else {
                $('#sms_balance').text('조회 실패');
                alert(data.message || '잔액 조회에 실패했습니다.');
            }
        },
        error: function() {
            $('#sms_balance').text('오류');
            alert('잔액 조회 중 오류가 발생했습니다.');
        }
    });
}

function number_format(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}
</script>

<?php
include_once('./admin.tail.php');
?>