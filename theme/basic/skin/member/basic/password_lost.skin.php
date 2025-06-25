<?php
/*
 * 파일명: password_lost.skin.php
 * 위치: /skin/member/basic/password_lost.skin.php
 * 기능: 회원정보 찾기 스킨 (SMS 인증 Ajax 처리)
 * 작성일: 2024-12-29
 */

if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

// add_stylesheet('css 구문', 출력순서); 숫자가 작을 수록 먼저 출력됨
add_stylesheet('<link rel="stylesheet" href="'.$member_skin_url.'/style.css">', 0);

// SMS 설정 가져오기
$sms_config = get_sms_config();
$use_sms = (isset($sms_config['cf_use_sms']) ? $sms_config['cf_use_sms'] : $sms_config['cf_use_password']) && $sms_config['cf_use_password'];

if($config['cf_cert_use'] && ($config['cf_cert_simple'] || $config['cf_cert_ipin'] || $config['cf_cert_hp'])) { ?>
    <script src="<?php echo G5_JS_URL ?>/certify.js?v=<?php echo G5_JS_VER; ?>"></script>    
<?php } ?>

<!-- 회원정보 찾기 시작 { -->
<div id="find_info" class="new_win">
    <h1><?php echo $g5['title'] ?></h1>
    
    <div class="find_method_tabs">
        <ul>
            <li class="active"><a href="#find_email">이메일로 찾기</a></li>
            <?php if($use_sms) { ?>
            <li><a href="#find_sms">SMS 인증으로 찾기</a></li>
            <?php } ?>
            <?php if($config['cf_cert_use'] && $config['cf_cert_find']) { ?>
            <li><a href="#find_cert">본인인증으로 찾기</a></li>
            <?php } ?>
        </ul>
    </div>

    <!-- 이메일로 찾기 -->
    <div id="find_email" class="find_method active">
        <form name="fpasswordlost" action="<?php echo $action_url ?>" onsubmit="return fpasswordlost_submit(this);" method="post" autocomplete="off">
        <input type="hidden" name="cert_no" value="">
        <fieldset>
            <p class="info_text">
                회원가입 시 등록하신 이메일 주소를 입력해 주세요.<br>
                해당 이메일로 아이디와 비밀번호 재설정 링크를 보내드립니다.
            </p>
            
            <div class="frm_input_wrap">
                <label for="mb_email" class="sound_only">E-mail 주소<strong>필수</strong></label>
                <input type="text" name="mb_email" id="mb_email" required class="required frm_input email full_input" size="30" placeholder="E-mail 주소">
            </div>
            
            <?php echo captcha_html(); ?>
            
            <button type="submit" class="btn_submit full">인증메일 발송</button>
        </fieldset>
        </form>
    </div>

    <?php if($use_sms) { ?>
    <!-- SMS 인증으로 찾기 -->
    <div id="find_sms" class="find_method">
        <!-- STEP 1: 휴대폰 번호 입력 -->
        <div id="sms_step1" class="sms_step active">
            <form name="fsmspasswordlost" id="fsmspasswordlost" method="post">
            <fieldset>
                <p class="info_text">
                    회원가입 시 등록하신 휴대폰 번호를 입력해 주세요.<br>
                    SMS 인증 후 비밀번호를 재설정할 수 있습니다.
                </p>
                
                <div class="frm_input_wrap">
                    <label for="mb_hp" class="sound_only">휴대폰번호<strong>필수</strong></label>
                    <input type="tel" name="mb_hp" id="mb_hp" required class="required frm_input full_input" placeholder="휴대폰번호 (- 없이 입력)" maxlength="11">
                </div>
                
                <button type="button" id="btn_send_sms" class="btn_submit full">인증번호 발송</button>
            </fieldset>
            </form>
        </div>
        
        <!-- STEP 2: 인증번호 입력 -->
        <div id="sms_step2" class="sms_step">
            <form name="fsmsverify" id="fsmsverify" method="post">
            <input type="hidden" name="mb_hp_verify" id="mb_hp_verify" value="">
            <fieldset>
                <div class="verify_info">
                    <p class="phone_info">
                        <strong id="phone_display"></strong>로<br>
                        인증번호를 발송했습니다.
                    </p>
                    <p class="time_info">
                        인증번호 유효시간: <span id="timer" class="timer_text"></span>
                    </p>
                </div>
                
                <div class="frm_input_wrap">
                    <label for="auth_code" class="sound_only">인증번호</label>
                    <input type="text" name="auth_code" id="auth_code" required class="required frm_input full_input" placeholder="인증번호 6자리" maxlength="6">
                </div>
                
                <button type="button" id="btn_verify_sms" class="btn_submit full">인증 확인</button>
                
                <div class="verify_help">
                    <button type="button" id="btn_resend_sms" class="btn_text">인증번호 재발송</button>
                    <button type="button" id="btn_change_phone" class="btn_text">휴대폰 번호 변경</button>
                </div>
            </fieldset>
            </form>
        </div>
        
        <!-- STEP 3: 비밀번호 재설정 -->
        <div id="sms_step3" class="sms_step">
            <form name="fpasswordreset" id="fpasswordreset" method="post">
            <input type="hidden" name="mb_id" id="reset_mb_id" value="">
            <input type="hidden" name="reset_token" id="reset_token" value="">
            <fieldset>
                <p class="info_text">
                    회원님의 아이디: <strong id="user_mb_id"></strong><br>
                    새로운 비밀번호를 입력해주세요.
                </p>
                
                <div class="frm_input_wrap">
                    <label for="mb_password_new" class="sound_only">새 비밀번호<strong>필수</strong></label>
                    <input type="password" name="mb_password" id="mb_password_new" required class="required frm_input full_input" placeholder="새 비밀번호">
                </div>
                
                <div class="frm_input_wrap">
                    <label for="mb_password_re" class="sound_only">새 비밀번호 확인<strong>필수</strong></label>
                    <input type="password" name="mb_password_re" id="mb_password_re" required class="required frm_input full_input" placeholder="새 비밀번호 확인">
                </div>
                
                <button type="button" id="btn_reset_password" class="btn_submit full">비밀번호 변경</button>
            </fieldset>
            </form>
        </div>
    </div>
    <?php } ?>

    <?php if($config['cf_cert_use'] && $config['cf_cert_find']) { ?>
    <!-- 본인인증으로 찾기 -->
    <div id="find_cert" class="find_method">
        <div class="cert_info">
            <p class="info_text">
                본인인증을 통해 아이디 찾기와 비밀번호 재설정이 가능합니다.<br>
                본인인증 시 제공되는 정보는 해당 인증기관에서 직접 수집하며,<br>
                인증 이외의 용도로 이용 또는 저장하지 않습니다.
            </p>
            
            <div class="cert_btn_list">
                <?php if($config['cf_cert_simple']) { ?>
                <button type="button" id="win_sa_kakao_cert" class="btn_cert">
                    <span class="cert_icon kakao"></span>
                    카카오 간편인증
                </button>
                <?php } ?>
                
                <?php if($config['cf_cert_hp']) { ?>
                <button type="button" id="win_hp_cert" class="btn_cert">
                    <span class="cert_icon phone"></span>
                    휴대폰 본인인증
                </button>
                <?php } ?>
                
                <?php if($config['cf_cert_ipin']) { ?>
                <button type="button" id="win_ipin_cert" class="btn_cert">
                    <span class="cert_icon ipin"></span>
                    아이핀 본인인증
                </button>
                <?php } ?>
            </div>
        </div>
    </div>
    <?php } ?>
    
    <div class="win_btn">
        <a href="<?php echo G5_URL ?>" class="btn_close">홈으로</a>
    </div>
</div>

<!-- 로딩 오버레이 -->
<div id="loading_overlay" style="display:none;">
    <div class="loading_spinner"></div>
</div>

<style>
/* 회원정보 찾기 스타일 */
#find_info {
    width: 500px;
    margin: 0 auto;
}

#find_info h1 {
    text-align: center;
    margin-bottom: 30px;
    font-size: 24px;
}

/* 탭 스타일 */
.find_method_tabs {
    margin-bottom: 30px;
}

.find_method_tabs ul {
    display: flex;
    list-style: none;
    margin: 0;
    padding: 0;
    border-bottom: 2px solid #ddd;
}

.find_method_tabs li {
    flex: 1;
}

.find_method_tabs a {
    display: block;
    padding: 15px;
    text-align: center;
    color: #666;
    text-decoration: none;
    border-bottom: 2px solid transparent;
    margin-bottom: -2px;
    transition: all 0.3s;
}

.find_method_tabs li.active a {
    color: #333;
    font-weight: bold;
    border-bottom-color: #333;
}

/* 컨텐츠 영역 */
.find_method {
    display: none;
    padding: 30px;
    background: #f9f9f9;
    border-radius: 5px;
}

.find_method.active {
    display: block;
}

.info_text {
    margin-bottom: 20px;
    line-height: 1.6;
    color: #666;
}

/* SMS 단계별 화면 */
.sms_step {
    display: none;
}

.sms_step.active {
    display: block;
}

/* 입력 필드 */
.frm_input_wrap {
    margin-bottom: 15px;
}

.frm_input {
    width: 100%;
    height: 50px;
    padding: 0 15px;
    border: 1px solid #ddd;
    border-radius: 3px;
    font-size: 14px;
}

.frm_input:focus {
    border-color: #333;
    outline: none;
}

/* 버튼 스타일 */
.btn_submit {
    height: 50px;
    padding: 0 30px;
    background: #333;
    color: #fff;
    border: none;
    border-radius: 3px;
    font-size: 16px;
    font-weight: bold;
    cursor: pointer;
    transition: background 0.3s;
}

.btn_submit:hover {
    background: #555;
}

.btn_submit.full {
    width: 100%;
}

.btn_submit:disabled {
    background: #ccc;
    cursor: not-allowed;
}

/* 인증번호 화면 */
.verify_info {
    text-align: center;
    margin-bottom: 30px;
    padding: 20px;
    background: #fff;
    border-radius: 5px;
}

.phone_info {
    font-size: 16px;
    line-height: 1.6;
    margin-bottom: 10px;
}

.phone_info strong {
    color: #333;
    font-size: 18px;
}

.time_info {
    color: #666;
    font-size: 14px;
}

.timer_text {
    color: #ff0000;
    font-weight: bold;
    font-size: 16px;
}

/* 도움말 버튼 */
.verify_help {
    text-align: center;
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid #ddd;
}

.btn_text {
    background: none;
    border: none;
    color: #666;
    text-decoration: underline;
    cursor: pointer;
    font-size: 14px;
    margin: 0 10px;
}

.btn_text:hover {
    color: #333;
}

/* 본인인증 버튼 */
.cert_btn_list {
    margin-top: 20px;
}

.btn_cert {
    display: flex;
    align-items: center;
    width: 100%;
    height: 50px;
    margin-bottom: 10px;
    padding: 0 20px;
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 3px;
    font-size: 15px;
    cursor: pointer;
    transition: all 0.3s;
}

.btn_cert:hover {
    background: #f5f5f5;
    border-color: #333;
}

.cert_icon {
    display: inline-block;
    width: 24px;
    height: 24px;
    margin-right: 10px;
    background-size: contain;
}

/* 캡차 */
#captcha {
    margin: 20px 0;
    padding: 20px;
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 3px;
    text-align: center;
}

/* 하단 버튼 */
.win_btn {
    margin-top: 30px;
    text-align: center;
}

.btn_close {
    display: inline-block;
    padding: 10px 30px;
    background: #666;
    color: #fff;
    border-radius: 3px;
    text-decoration: none;
}

.btn_close:hover {
    background: #555;
}

/* 로딩 오버레이 */
#loading_overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
}

.loading_spinner {
    width: 50px;
    height: 50px;
    border: 5px solid #f3f3f3;
    border-top: 5px solid #333;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* 알림 메시지 */
.alert_msg {
    padding: 15px;
    margin-bottom: 20px;
    border-radius: 3px;
    text-align: center;
}

.alert_msg.success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.alert_msg.error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}
</style>

<script>
var g5_bbs_url = "<?php echo G5_BBS_URL ?>";
var sms_timer = null;
var time_left = 0;

$(function() {
    // 탭 전환
    $('.find_method_tabs a').click(function(e) {
        e.preventDefault();
        var target = $(this).attr('href');
        
        $('.find_method_tabs li').removeClass('active');
        $(this).parent().addClass('active');
        
        $('.find_method').removeClass('active');
        $(target).addClass('active');
    });
    
    // 휴대폰 번호 자동 포맷
    $('#mb_hp').on('input', function() {
        this.value = this.value.replace(/[^0-9]/g, '');
    });
    
    // 인증번호 입력 제한
    $('#auth_code').on('input', function() {
        this.value = this.value.replace(/[^0-9]/g, '');
    });
    
    <?php if($use_sms) { ?>
    // SMS 인증번호 발송
    $('#btn_send_sms').click(function() {
        var hp = $('#mb_hp').val();
        
        if(!hp) {
            alert('휴대폰 번호를 입력해주세요.');
            $('#mb_hp').focus();
            return;
        }
        
        if(!/^01[0-9]{8,9}$/.test(hp)) {
            alert('올바른 휴대폰 번호를 입력해주세요.');
            $('#mb_hp').focus();
            return;
        }
        
        showLoading();
        
        $.ajax({
            url: g5_bbs_url + '/ajax.sms_send.php',
            type: 'POST',
            data: {
                'type': 'password',
                'mb_hp': hp
            },
            dataType: 'json',
            success: function(response) {
                hideLoading();
                
                if(response.error) {
                    alert(response.error);
                    return;
                }
                
                if(response.success) {
                    $('#mb_hp_verify').val(hp);
                    $('#phone_display').text(formatPhone(hp));
                    
                    // 타이머 시작
                    startTimer(response.timeout || 180);
                    
                    // 단계 전환
                    $('#sms_step1').removeClass('active');
                    $('#sms_step2').addClass('active');
                    
                    showMessage('인증번호가 발송되었습니다.', 'success');
                }
            },
            error: function() {
                hideLoading();
                alert('통신 오류가 발생했습니다. 다시 시도해주세요.');
            }
        });
    });
    
    // 인증번호 확인
    $('#btn_verify_sms').click(function() {
        var hp = $('#mb_hp_verify').val();
        var code = $('#auth_code').val();
        
        if(!code) {
            alert('인증번호를 입력해주세요.');
            $('#auth_code').focus();
            return;
        }
        
        if(code.length !== 6) {
            alert('인증번호 6자리를 입력해주세요.');
            $('#auth_code').focus();
            return;
        }
        
        showLoading();
        
        $.ajax({
            url: g5_bbs_url + '/ajax.sms_verify.php',
            type: 'POST',
            data: {
                'type': 'password',
                'mb_hp': hp,
                'auth_code': code
            },
            dataType: 'json',
            success: function(response) {
                hideLoading();
                
                if(response.error) {
                    alert(response.error);
                    return;
                }
                
                if(response.verified) {
                    // 타이머 정지
                    if(sms_timer) {
                        clearInterval(sms_timer);
                    }
                    
                    // 회원 정보 표시
                    $('#user_mb_id').text(response.mb_id);
                    $('#reset_mb_id').val(response.mb_id);
                    $('#reset_token').val(response.token);
                    
                    // 단계 전환
                    $('#sms_step2').removeClass('active');
                    $('#sms_step3').addClass('active');
                    
                    showMessage('인증이 완료되었습니다.', 'success');
                }
            },
            error: function() {
                hideLoading();
                alert('통신 오류가 발생했습니다. 다시 시도해주세요.');
            }
        });
    });
    
    // 비밀번호 재설정
    $('#btn_reset_password').click(function() {
        var mb_id = $('#reset_mb_id').val();
        var token = $('#reset_token').val();
        var pw1 = $('#mb_password_new').val();
        var pw2 = $('#mb_password_re').val();
        
        if(!pw1 || !pw2) {
            alert('새 비밀번호를 입력해주세요.');
            return;
        }
        
        if(pw1 !== pw2) {
            alert('비밀번호가 일치하지 않습니다.');
            $('#mb_password_re').focus();
            return;
        }
        
        if(pw1.length < 4) {
            alert('비밀번호는 4자 이상 입력해주세요.');
            $('#mb_password_new').focus();
            return;
        }
        
        showLoading();
        
        $.ajax({
            url: g5_bbs_url + '/ajax.password_reset.php',
            type: 'POST',
            data: {
                'mb_id': mb_id,
                'token': token,
                'mb_password': pw1,
                'mb_password_re': pw2
            },
            dataType: 'json',
            success: function(response) {
                hideLoading();
                
                if(response.error) {
                    alert(response.error);
                    return;
                }
                
                if(response.success) {
                    alert('비밀번호가 변경되었습니다.\n\n새로운 비밀번호로 로그인해주세요.');
                    location.href = g5_bbs_url + '/login.php';
                }
            },
            error: function() {
                hideLoading();
                alert('통신 오류가 발생했습니다. 다시 시도해주세요.');
            }
        });
    });
    
    // 인증번호 재발송
    $('#btn_resend_sms').click(function() {
        if(confirm('인증번호를 재발송하시겠습니까?')) {
            var hp = $('#mb_hp_verify').val();
            $('#mb_hp').val(hp);
            $('#btn_send_sms').click();
        }
    });
    
    // 휴대폰 번호 변경
    $('#btn_change_phone').click(function() {
        if(confirm('휴대폰 번호를 변경하시겠습니까?')) {
            // 타이머 정지
            if(sms_timer) {
                clearInterval(sms_timer);
            }
            
            // 단계 전환
            $('#sms_step2').removeClass('active');
            $('#sms_step1').addClass('active');
            
            // 입력값 초기화
            $('#auth_code').val('');
        }
    });
    <?php } ?>
    
    <?php if($config['cf_cert_use'] && $config['cf_cert_simple']) { ?>
    // 카카오 간편인증
    $("#win_sa_kakao_cert").click(function() {
        certify_win_open('sa-kakao', '<?php echo G5_OKNAME_URL; ?>/sa_kakao_cert.php');
    });
    <?php } ?>
    
    <?php if($config['cf_cert_use'] && $config['cf_cert_ipin']) { ?>
    // 아이핀 인증
    $("#win_ipin_cert").click(function() {
        certify_win_open('kcb-ipin', '<?php echo G5_OKNAME_URL; ?>/ipin1.php');
    });
    <?php } ?>
    
    <?php if($config['cf_cert_use'] && $config['cf_cert_hp']) { ?>
    // 휴대폰 인증
    $("#win_hp_cert").click(function() {
        <?php
        switch($config['cf_cert_hp']) {
            case 'kcb':
                $cert_url = G5_OKNAME_URL.'/hpcert1.php';
                $cert_type = 'kcb-hp';
                break;
            case 'kcp':
                $cert_url = G5_KCPCERT_URL.'/kcpcert_form.php';
                $cert_type = 'kcp-hp';
                break;
            case 'lg':
                $cert_url = G5_LGXPAY_URL.'/AuthOnlyReq.php';
                $cert_type = 'lg-hp';
                break;
            default:
                echo 'alert("기본환경설정에서 휴대폰 본인확인 설정을 해주십시오");';
                echo 'return false;';
                break;
        }
        ?>
        certify_win_open('<?php echo $cert_type; ?>', '<?php echo $cert_url; ?>');
    });
    <?php } ?>
});

// 타이머 시작
function startTimer(seconds) {
    time_left = seconds;
    updateTimer();
    
    sms_timer = setInterval(function() {
        time_left--;
        updateTimer();
        
        if(time_left <= 0) {
            clearInterval(sms_timer);
            alert('인증 시간이 만료되었습니다.\n다시 시도해주세요.');
            $('#btn_change_phone').click();
        }
    }, 1000);
}

// 타이머 업데이트
function updateTimer() {
    var minutes = Math.floor(time_left / 60);
    var seconds = time_left % 60;
    var display = (minutes < 10 ? '0' : '') + minutes + ':' + (seconds < 10 ? '0' : '') + seconds;
    $('#timer').text(display);
}

// 전화번호 포맷
function formatPhone(phone) {
    if(phone.length == 11) {
        return phone.substr(0, 3) + '-' + phone.substr(3, 4) + '-' + phone.substr(7);
    } else if(phone.length == 10) {
        return phone.substr(0, 3) + '-' + phone.substr(3, 3) + '-' + phone.substr(6);
    }
    return phone;
}

// 로딩 표시
function showLoading() {
    $('#loading_overlay').show();
}

// 로딩 숨김
function hideLoading() {
    $('#loading_overlay').hide();
}

// 메시지 표시
function showMessage(msg, type) {
    var html = '<div class="alert_msg ' + type + '">' + msg + '</div>';
    $('.sms_step.active .info_text').after(html);
    
    setTimeout(function() {
        $('.alert_msg').fadeOut(function() {
            $(this).remove();
        });
    }, 3000);
}

function fpasswordlost_submit(f) {
    <?php echo chk_captcha_js(); ?>
    return true;
}
</script>
<!-- } 회원정보 찾기 끝 -->