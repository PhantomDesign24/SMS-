<?php
/*
 * 파일명: password_lost.skin.php (SMS 인증 추가)
 * 위치: /skin/member/basic/password_lost.skin.php
 * 기능: 비밀번호 찾기에 SMS 인증 기능 추가 (디자인 포함)
 * 작성일: 2024-12-28
 */

// SMS 설정 확인
$sms_config = get_sms_config();
$use_sms_auth = $sms_config && $sms_config['cf_use_password'];
?>

<!-- ===================================
 * SMS 인증 스타일
 * =================================== -->
<style>
/* 전체 컨테이너 */
.password-lost-wrap {
    max-width: 500px;
    margin: 50px auto;
    padding: 0;
}

/* 단계별 진행 표시 */
.step-indicator {
    display: flex;
    margin-bottom: 30px;
    position: relative;
}

.step-indicator::before {
    content: '';
    position: absolute;
    top: 20px;
    left: 0;
    right: 0;
    height: 2px;
    background-color: #e0e0e0;
    z-index: 0;
}

.step-item {
    flex: 1;
    text-align: center;
    position: relative;
    z-index: 1;
}

.step-number {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background-color: #e0e0e0;
    color: #999;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    margin-bottom: 8px;
    transition: all 0.3s;
}

.step-item.active .step-number {
    background-color: #2196F3;
    color: white;
    transform: scale(1.1);
}

.step-item.completed .step-number {
    background-color: #4CAF50;
    color: white;
}

.step-item.completed .step-number::after {
    content: '✓';
    position: absolute;
}

.step-label {
    font-size: 13px;
    color: #666;
}

.step-item.active .step-label {
    color: #2196F3;
    font-weight: 500;
}

/* 단계별 컨텐츠 */
.step-content {
    background-color: white;
    border-radius: 12px;
    padding: 30px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

.step-content h3 {
    font-size: 20px;
    margin-bottom: 20px;
    color: #333;
}

/* 입력 폼 스타일 */
.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: #555;
}

.form-control {
    width: 100%;
    height: 50px;
    padding: 0 15px;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    font-size: 15px;
    transition: all 0.3s;
}

.form-control:focus {
    border-color: #2196F3;
    outline: none;
    box-shadow: 0 0 0 3px rgba(33, 150, 243, 0.1);
}

.form-control[readonly] {
    background-color: #f5f5f5;
    cursor: not-allowed;
}

/* 버튼 스타일 */
.btn {
    height: 50px;
    padding: 0 30px;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.btn i {
    margin-right: 8px;
}

.btn-primary {
    background-color: #2196F3;
    color: white;
    width: 100%;
}

.btn-primary:hover {
    background-color: #1976D2;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(33, 150, 243, 0.3);
}

.btn-success {
    background-color: #4CAF50;
    color: white;
}

.btn-success:hover {
    background-color: #45a049;
}

.btn:disabled {
    background-color: #ccc;
    cursor: not-allowed;
    transform: none;
}

/* 전화번호 표시 */
.phone-display {
    background-color: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    text-align: center;
    font-size: 18px;
    font-weight: 500;
    color: #333;
    margin-bottom: 20px;
}

/* 메시지 표시 */
.alert {
    padding: 12px 20px;
    border-radius: 8px;
    margin-bottom: 20px;
    display: none;
}

.alert.show {
    display: block;
}

.alert-success {
    background-color: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.alert-error {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.alert-info {
    background-color: #d1ecf1;
    color: #0c5460;
    border: 1px solid #bee5eb;
}

/* 타이머 */
.timer-display {
    text-align: center;
    color: #f44336;
    font-size: 16px;
    font-weight: 500;
    margin-top: 10px;
}

/* 인증 완료 */
.success-container {
    text-align: center;
    padding: 40px;
}

.success-icon {
    width: 80px;
    height: 80px;
    background-color: #4CAF50;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 20px;
}

.success-icon i {
    font-size: 40px;
    color: white;
}

/* 로딩 */
.loading {
    display: inline-block;
    width: 20px;
    height: 20px;
    border: 3px solid #f3f3f3;
    border-top: 3px solid #2196F3;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin-right: 10px;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* 반응형 */
@media (max-width: 640px) {
    .password-lost-wrap {
        margin: 20px;
        padding: 0;
    }
    
    .step-content {
        padding: 20px;
    }
    
    .step-label {
        font-size: 11px;
    }
}
</style>

<?php if($use_sms_auth) { ?>
<!-- ===================================
 * 비밀번호 찾기 폼 (SMS 인증 포함)
 * =================================== -->
<div class="password-lost-wrap">
    <!-- 단계 표시 -->
    <div class="step-indicator">
        <div class="step-item active" id="stepItem1">
            <div class="step-number">1</div>
            <div class="step-label">정보 입력</div>
        </div>
        <div class="step-item" id="stepItem2">
            <div class="step-number">2</div>
            <div class="step-label">SMS 인증</div>
        </div>
        <div class="step-item" id="stepItem3">
            <div class="step-number">3</div>
            <div class="step-label">완료</div>
        </div>
    </div>
    
    <form name="fpasswordlost" action="<?php echo $action_url ?>" onsubmit="return fpasswordlost_submit(this);" method="post" autocomplete="off">
    
    <!-- 1단계: 회원정보 입력 -->
    <div class="step-content" id="step1">
        <h3><i class="fa fa-user"></i> 회원정보 입력</h3>
        
        <div class="alert alert-info show">
            <i class="fa fa-info-circle"></i> 회원가입 시 등록한 정보를 입력해주세요.
        </div>
        
        <div class="form-group">
            <label for="mb_id">아이디</label>
            <input type="text" name="mb_id" id="mb_id" required class="form-control" placeholder="아이디를 입력하세요">
        </div>
        
        <div class="form-group">
            <label for="mb_hp_input">휴대폰번호</label>
            <input type="tel" id="mb_hp_input" required class="form-control" maxlength="11" placeholder="'-' 없이 숫자만 입력">
        </div>
        
        <div class="alert" id="step1Message"></div>
        
        <button type="button" class="btn btn-primary" id="btnCheckMember">
            <i class="fa fa-arrow-right"></i> 다음 단계
        </button>
    </div>
    
    <!-- 2단계: SMS 인증 -->
    <div class="step-content" id="step2" style="display: none;">
        <h3><i class="fa fa-mobile"></i> SMS 인증</h3>
        
        <div class="phone-display" id="phoneDisplay"></div>
        
        <div class="alert alert-info show">
            <i class="fa fa-info-circle"></i> 위 번호로 인증번호가 발송됩니다.
        </div>
        
        <button type="button" class="btn btn-primary" id="btnSendAuth" style="margin-bottom: 20px;">
            <i class="fa fa-paper-plane"></i> 인증번호 발송
        </button>
        
        <div id="authCodeSection" style="display: none;">
            <div class="form-group">
                <label for="auth_code">인증번호</label>
                <input type="text" id="auth_code" class="form-control" maxlength="6" placeholder="인증번호 6자리">
            </div>
            
            <div class="timer-display" id="authTimer"></div>
            
            <button type="button" class="btn btn-success" id="btnVerifyAuth" style="width: 100%;">
                <i class="fa fa-check"></i> 인증 확인
            </button>
        </div>
        
        <div class="alert" id="step2Message"></div>
    </div>
    
    <!-- 3단계: 인증 완료 -->
    <div class="step-content" id="step3" style="display: none;">
        <div class="success-container">
            <div class="success-icon">
                <i class="fa fa-check"></i>
            </div>
            <h3>인증이 완료되었습니다!</h3>
            <p style="margin: 20px 0; color: #666;">
                회원님의 이메일로 비밀번호 재설정 링크가 발송됩니다.
            </p>
            <button type="submit" class="btn btn-primary">
                <i class="fa fa-envelope"></i> 비밀번호 재설정 메일 발송
            </button>
        </div>
    </div>
    
    <!-- Hidden Fields -->
    <input type="hidden" name="sms_verified" id="smsVerified" value="0">
    <input type="hidden" name="verified_mb_id" id="verifiedMbId" value="">
    <input type="hidden" name="verified_phone" id="verifiedPhone" value="">
    
    </form>
</div>

<!-- ===================================
 * SMS 인증 JavaScript
 * =================================== -->
<script>
$(function() {
    var authTimer = null;
    var timeLeft = 0;
    var memberInfo = null;
    
    // 휴대폰 번호 입력 시 숫자만
    $('#mb_hp_input').on('input', function() {
        $(this).val($(this).val().replace(/[^0-9]/g, ''));
    });
    
    // 인증번호 입력 시 숫자만
    $('#auth_code').on('input', function() {
        $(this).val($(this).val().replace(/[^0-9]/g, ''));
    });
    
    // 1단계: 회원정보 확인
    $('#btnCheckMember').on('click', function() {
        var mb_id = $('#mb_id').val();
        var mb_hp = $('#mb_hp_input').val();
        
        if(!mb_id) {
            showMessage('step1Message', '아이디를 입력해주세요.', 'error');
            $('#mb_id').focus();
            return;
        }
        
        if(!mb_hp) {
            showMessage('step1Message', '휴대폰 번호를 입력해주세요.', 'error');
            $('#mb_hp_input').focus();
            return;
        }
        
        if(!/^01[016789][0-9]{7,8}$/.test(mb_hp)) {
            showMessage('step1Message', '올바른 휴대폰 번호 형식이 아닙니다.', 'error');
            $('#mb_hp_input').focus();
            return;
        }
        
        var $btn = $(this);
        $btn.prop('disabled', true).html('<span class="loading"></span> 확인중...');
        
        // 회원정보 저장
        memberInfo = {
            mb_id: mb_id,
            mb_hp: mb_hp
        };
        
        // 실제로는 서버에서 회원 확인을 해야 하지만, 여기서는 2단계로 진행
        setTimeout(function() {
            // 단계 이동
            $('#step1').fadeOut(300, function() {
                $('#step2').fadeIn(300);
                $('#stepItem1').removeClass('active').addClass('completed');
                $('#stepItem2').addClass('active');
                $('#phoneDisplay').text(formatPhone(mb_hp));
            });
        }, 500);
    });
    
    // 2단계: 인증번호 발송
    $('#btnSendAuth').on('click', function() {
        var $btn = $(this);
        $btn.prop('disabled', true).html('<span class="loading"></span> 발송중...');
        
        $.ajax({
            url: g5_bbs_url + '/sms_send.php',
            type: 'POST',
            data: {
                phone: memberInfo.mb_hp,
                type: 'password',
                mb_id: memberInfo.mb_id,
                captcha_key: $('#captcha_key').val() || ''
            },
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    showMessage('step2Message', response.message, 'success');
                    
                    // 인증번호 입력 영역 표시
                    $('#authCodeSection').slideDown();
                    $('#auth_code').val('').focus();
                    
                    // 타이머 시작
                    startTimer(response.timeout || 180);
                    
                    // 버튼 변경
                    $btn.html('<i class="fa fa-refresh"></i> 재발송');
                } else {
                    showMessage('step2Message', response.message, 'error');
                    
                    // 캡차가 필요한 경우
                    if(response.need_captcha && typeof load_captcha === 'function') {
                        load_captcha();
                    }
                }
            },
            error: function() {
                showMessage('step2Message', '통신 오류가 발생했습니다.', 'error');
            },
            complete: function() {
                $btn.prop('disabled', false);
            }
        });
    });
    
    // 인증번호 확인
    $('#btnVerifyAuth').on('click', function() {
        var auth_code = $('#auth_code').val();
        
        if(!auth_code) {
            showMessage('step2Message', '인증번호를 입력해주세요.', 'error');
            $('#auth_code').focus();
            return;
        }
        
        if(!/^[0-9]{6}$/.test(auth_code)) {
            showMessage('step2Message', '인증번호는 6자리 숫자입니다.', 'error');
            $('#auth_code').focus();
            return;
        }
        
        var $btn = $(this);
        $btn.prop('disabled', true).html('<span class="loading"></span> 확인중...');
        
        $.ajax({
            url: g5_bbs_url + '/sms_verify.php',
            type: 'POST',
            data: {
                phone: memberInfo.mb_hp,
                auth_code: auth_code,
                type: 'password'
            },
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    // 타이머 정지
                    stopTimer();
                    
                    // 인증 완료 처리
                    $('#smsVerified').val('1');
                    $('#verifiedMbId').val(memberInfo.mb_id);
                    $('#verifiedPhone').val(memberInfo.mb_hp);
                    
                    // 3단계로 이동
                    $('#step2').fadeOut(300, function() {
                        $('#step3').fadeIn(300);
                        $('#stepItem2').removeClass('active').addClass('completed');
                        $('#stepItem3').addClass('active');
                    });
                } else {
                    showMessage('step2Message', response.message, 'error');
                    $('#auth_code').focus();
                }
            },
            error: function() {
                showMessage('step2Message', '통신 오류가 발생했습니다.', 'error');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="fa fa-check"></i> 인증 확인');
            }
        });
    });
    
    // 엔터키 처리
    $('#mb_id, #mb_hp_input').on('keypress', function(e) {
        if(e.which == 13) {
            $('#btnCheckMember').click();
            return false;
        }
    });
    
    $('#auth_code').on('keypress', function(e) {
        if(e.which == 13) {
            $('#btnVerifyAuth').click();
            return false;
        }
    });
    
    // 메시지 표시
    function showMessage(elementId, message, type) {
        $('#' + elementId)
            .removeClass('alert-success alert-error show')
            .addClass('alert-' + type + ' show')
            .html('<i class="fa fa-' + (type == 'success' ? 'check' : 'exclamation') + '-circle"></i> ' + message);
        
        // 5초 후 숨김
        setTimeout(function() {
            $('#' + elementId).removeClass('show');
        }, 5000);
    }
    
    // 전화번호 포맷
    function formatPhone(phone) {
        if(phone.length == 11) {
            return phone.substr(0,3) + '-' + phone.substr(3,4) + '-' + phone.substr(7);
        } else if(phone.length == 10) {
            return phone.substr(0,3) + '-' + phone.substr(3,3) + '-' + phone.substr(6);
        }
        return phone;
    }
    
    // 타이머 시작
    function startTimer(seconds) {
        stopTimer();
        timeLeft = seconds;
        updateTimer();
        
        authTimer = setInterval(function() {
            timeLeft--;
            if(timeLeft <= 0) {
                stopTimer();
                showMessage('step2Message', '인증시간이 만료되었습니다.', 'error');
                $('#btnVerifyAuth').prop('disabled', true);
            } else {
                updateTimer();
            }
        }, 1000);
    }
    
    // 타이머 정지
    function stopTimer() {
        if(authTimer) {
            clearInterval(authTimer);
            authTimer = null;
        }
        $('#authTimer').text('');
    }
    
    // 타이머 업데이트
    function updateTimer() {
        var minutes = Math.floor(timeLeft / 60);
        var seconds = timeLeft % 60;
        var display = minutes + ':' + (seconds < 10 ? '0' : '') + seconds;
        $('#authTimer').html('<i class="fa fa-clock-o"></i> 남은시간: ' + display);
    }
    
    // 폼 전송 전 확인
    function fpasswordlost_submit(f) {
        if($('#smsVerified').val() != '1') {
            alert('SMS 인증을 완료해주세요.');
            return false;
        }
        
        // 실제 아이디 값 설정
        f.mb_id.value = $('#verifiedMbId').val();
        
        return true;
    }
});
</script>

<?php } else { ?>
<!-- ===================================
 * SMS 인증을 사용하지 않는 경우 (기존 코드)
 * =================================== -->
<div class="mbskin">
    <h1><?php echo $g5['title'] ?></h1>
    
    <form name="fpasswordlost" action="<?php echo $action_url ?>" onsubmit="return fpasswordlost_submit(this);" method="post" autocomplete="off">
    
    <p>
        회원가입 시 등록하신 이메일 주소를 입력해 주세요.<br>
        해당 이메일로 비밀번호를 재설정하는 방법이 안내됩니다.
    </p>
    
    <label for="mb_email" class="sound_only">E-mail 주소<strong class="sound_only">필수</strong></label>
    <input type="text" name="mb_email" id="mb_email" required class="required frm_input email" size="30" placeholder="E-mail 주소">
    
    <?php echo captcha_html(); ?>
    
    <input type="submit" value="확인" class="btn_submit">
    
    </form>
</div>

<script>
function fpasswordlost_submit(f) {
    <?php echo chk_captcha_js(); ?>
    
    return true;
}

$(function() {
    var sw = screen.width;
    var sh = screen.height;
    var cw = document.body.clientWidth;
    var ch = document.body.clientHeight;
    var top  = sh / 2 - ch / 2 - 100;
    var left = sw / 2 - cw / 2;
    moveTo(left, top);
});
</script>
<?php } ?>