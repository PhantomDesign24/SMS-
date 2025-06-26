<?php
/*
 * 파일명: register_form.skin.php
 * 위치: /theme/basic/skin/member/basic/
 * 기능: 회원가입/정보수정 폼 스킨
 * 작성일: 2025-01-15
 * 수정일: 2025-01-15
 */

if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

// add_stylesheet('css 구문', 출력순서); 숫자가 작을 수록 먼저 출력됨
add_stylesheet('<link rel="stylesheet" href="'.$member_skin_url.'/style.css">', 0);
add_javascript('<script src="'.G5_JS_URL.'/jquery.register_form.js"></script>', 0);
if ($config['cf_cert_use'] && ($config['cf_cert_simple'] || $config['cf_cert_ipin'] || $config['cf_cert_hp']))
    add_javascript('<script src="'.G5_JS_URL.'/certify.js?v='.G5_JS_VER.'"></script>', 0);
?>

<!-- Font Awesome 비동기 로드 -->
<link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
<noscript><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"></noscript>

<style>
/* =================================== 
 * 회원가입 폼 전용 스타일
 * =================================== */

/* 입력 그룹 스타일 */
.register_input_group {
    position: relative;
    margin-bottom: 15px;
}

.register_input_group .input_icon {
    position: absolute;
    left: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: #3a8afd;
    font-size: 18px;
    z-index: 1;
}

.register_input_group .frm_input {
    padding-left: 45px;
    height: 50px;
    border: 1px solid #ddd;
    border-radius: 8px;
    transition: all 0.3s ease;
    font-size: 15px;
}

.register_input_group .frm_input:focus {
    border-color: #3a8afd;
    box-shadow: 0 0 0 3px rgba(58, 138, 253, 0.1);
    outline: none;
}

.register_input_group .frm_input::placeholder {
    color: #999;
    font-size: 14px;
}

/* 필수 표시 */
.required_star {
    color: #dc3545;
    font-weight: bold;
    margin-left: 3px;
}

/* 휴대폰 인증 스타일 */
.phone_cert_wrap {
    display: flex;
    gap: 10px;
    align-items: center;
}

.phone_cert_wrap .frm_input {
    flex: 1;
}

.phone_cert_wrap .register_input_group {
    flex: 1;
    margin-bottom: 0;
}

.btn_phone_cert {
    padding: 0 20px;
    height: 50px;
    background: #3a8afd;
    color: #fff;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 500;
    transition: all 0.3s;
    white-space: nowrap;
}

.btn_phone_cert:hover {
    background: #2968d6;
}

.btn_phone_cert:disabled {
    background: #ccc;
    cursor: not-allowed;
}

/* 인증번호 입력 영역 */
.cert_number_wrap {
    display: none;
    margin-top: 10px;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 8px;
    border: 1px solid #e9ecef;
}

.cert_number_wrap.active {
    display: block;
}

.cert_timer {
    color: #dc3545;
    font-weight: bold;
    margin-left: 10px;
}

/* SMS 인증 완료 표시 */
.sms_verified_msg {
    display: none;
    margin-top: 10px;
    padding: 15px;
    background: #d4edda;
    border: 1px solid #c3e6cb;
    color: #155724;
    border-radius: 8px;
    font-size: 14px;
    text-align: center;
    animation: fadeInUp 0.5s ease;
}

.sms_verified_msg i {
    color: #28a745;
    margin-right: 5px;
    font-size: 16px;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* 주소 검색 스타일 */
.address_search_wrap {
    display: flex;
    gap: 10px;
    margin-bottom: 10px;
}

.address_search_wrap .frm_input {
    width: 150px;
}

/* 툴팁 개선 */
.tooltip {
    position: absolute;
    background: #333;
    color: #fff;
    padding: 10px 15px;
    border-radius: 6px;
    font-size: 13px;
    line-height: 1.5;
    max-width: 300px;
    z-index: 1000;
    display: none;
    box-shadow: 0 2px 10px rgba(0,0,0,0.2);
}

.tooltip:before {
    content: "";
    position: absolute;
    top: -6px;
    left: 20px;
    width: 0;
    height: 0;
    border-left: 6px solid transparent;
    border-right: 6px solid transparent;
    border-bottom: 6px solid #333;
}

/* 체크박스 커스텀 */
.custom_checkbox {
    display: flex;
    align-items: center;
    margin-bottom: 15px;
}

.custom_checkbox input[type="checkbox"] {
    display: none;
}

.custom_checkbox label {
    display: flex;
    align-items: center;
    cursor: pointer;
    font-size: 14px;
}

.custom_checkbox label:before {
    content: "";
    display: inline-block;
    width: 20px;
    height: 20px;
    border: 2px solid #ddd;
    border-radius: 4px;
    margin-right: 10px;
    transition: all 0.3s;
}

.custom_checkbox input[type="checkbox"]:checked + label:before {
    background: #3a8afd;
    border-color: #3a8afd;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='white'%3E%3Cpath d='M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z'/%3E%3C/svg%3E");
    background-size: 16px;
    background-position: center;
    background-repeat: no-repeat;
}

/* 섹션 타이틀 개선 */
.register_form_inner h2 {
    padding: 20px;
    background: #f8f9fa;
    border-bottom: 2px solid #3a8afd;
    font-size: 18px;
    font-weight: 600;
    color: #333;
}

/* 버튼 스타일 개선 */
.btn_confirm {
    display: flex;
    gap: 10px;
    justify-content: center;
    margin-top: 30px;
}

.btn_confirm .btn_submit,
.btn_confirm .btn_close {
    padding: 15px 40px;
    font-size: 16px;
    font-weight: 600;
    border-radius: 8px;
    transition: all 0.3s;
    cursor: pointer;
}

.btn_confirm .btn_submit {
    background: #3a8afd;
    color: #fff;
    border: none;
}

.btn_confirm .btn_submit:hover {
    background: #2968d6;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(58, 138, 253, 0.3);
}

.btn_confirm .btn_close {
    background: #fff;
    color: #666;
    border: 1px solid #ddd;
}

.btn_confirm .btn_close:hover {
    background: #f8f9fa;
}
</style>

<!-- 회원정보 입력/수정 시작 { -->
<div class="register">
	<form id="fregisterform" name="fregisterform" action="<?php echo $register_action_url ?>" onsubmit="return fregisterform_submit(this);" method="post" enctype="multipart/form-data" autocomplete="off">
	<input type="hidden" name="w" value="<?php echo $w ?>">
	<input type="hidden" name="url" value="<?php echo $urlencode ?>">
	<input type="hidden" name="agree" value="<?php echo $agree ?>">
	<input type="hidden" name="agree2" value="<?php echo $agree2 ?>">
	<input type="hidden" name="cert_type" value="<?php echo $member['mb_certify']; ?>">
	<input type="hidden" name="cert_no" value="">
	<?php if (isset($member['mb_sex'])) {  ?><input type="hidden" name="mb_sex" value="<?php echo $member['mb_sex'] ?>"><?php }  ?>
	<?php if (isset($member['mb_nick_date']) && $member['mb_nick_date'] > date("Y-m-d", G5_SERVER_TIME - ($config['cf_nick_modify'] * 86400))) { // 닉네임수정일이 지나지 않았다면  ?>
	<input type="hidden" name="mb_nick_default" value="<?php echo get_text($member['mb_nick']) ?>">
	<input type="hidden" name="mb_nick" value="<?php echo get_text($member['mb_nick']) ?>">
	<?php }  ?>
	
	<div id="register_form" class="form_01">   
	    <!-- =================================== 
	     * 사이트 이용정보 입력
	     * =================================== -->
	    <div class="register_form_inner">
	        <h2>사이트 이용정보 입력</h2>
	        <ul>
	            <!-- 아이디 입력 -->
	            <li>
	                <label for="reg_mb_id">
	                	아이디<span class="required_star">*</span>
	                	<button type="button" class="tooltip_icon"><i class="fa fa-question-circle-o" aria-hidden="true"></i><span class="sound_only">설명보기</span></button>
						<span class="tooltip">영문자, 숫자, _ 만 입력 가능. 최소 3자이상 입력하세요.</span>
	                </label>
	                <div class="register_input_group">
	                    <i class="fas fa-user input_icon"></i>
	                    <input type="text" name="mb_id" value="<?php echo $member['mb_id'] ?>" id="reg_mb_id" <?php echo $required ?> <?php echo $readonly ?> class="frm_input full_input <?php echo $required ?> <?php echo $readonly ?>" minlength="3" maxlength="20" placeholder="아이디를 입력하세요">
	                </div>
	                <span id="msg_mb_id"></span>
	            </li>
	            
	            <!-- 비밀번호 입력 -->
	            <li class="half_input left_input margin_input">
	                <label for="reg_mb_password">비밀번호<span class="required_star">*</span></label>
	                <div class="register_input_group">
	                    <i class="fas fa-lock input_icon"></i>
	                    <input type="password" name="mb_password" id="reg_mb_password" <?php echo $required ?> class="frm_input full_input <?php echo $required ?>" minlength="3" maxlength="20" placeholder="비밀번호를 입력하세요">
	                </div>
				</li>
				
				<!-- 비밀번호 확인 -->
	            <li class="half_input left_input">
	                <label for="reg_mb_password_re">비밀번호 확인<span class="required_star">*</span></label>
	                <div class="register_input_group">
	                    <i class="fas fa-lock input_icon"></i>
	                    <input type="password" name="mb_password_re" id="reg_mb_password_re" <?php echo $required ?> class="frm_input full_input <?php echo $required ?>" minlength="3" maxlength="20" placeholder="비밀번호를 다시 입력하세요">
	                </div>
	            </li>
	        </ul>
	    </div>
	
	    <!-- =================================== 
	     * 개인정보 입력
	     * =================================== -->
	    <div class="tbl_frm01 tbl_wrap register_form_inner">
	        <h2>개인정보 입력</h2>
	        <ul>
				<li>
                    <?php 
					$desc_name = '';
					$desc_phone = '';
					if ($config['cf_cert_use']) {
                        $desc_name = '<span class="cert_desc"> 본인확인 시 자동입력</span>';
                        $desc_phone = '<span class="cert_desc"> 본인확인 시 자동입력</span>';
    
                        if (!$config['cf_cert_simple'] && !$config['cf_cert_hp'] && $config['cf_cert_ipin']) {
                            $desc_phone = '';
                        }
	                    if ($config['cf_cert_simple']) {
                            echo '<button type="button" id="win_sa_kakao_cert" class="btn_frmline win_sa_cert" data-type="">간편인증</button>'.PHP_EOL;
						}
						if ($config['cf_cert_hp'])
							echo '<button type="button" id="win_hp_cert" class="btn_frmline">휴대폰 본인확인</button>'.PHP_EOL;
						if ($config['cf_cert_ipin'])
							echo '<button type="button" id="win_ipin_cert" class="btn_frmline">아이핀 본인확인</button>'.PHP_EOL;
	
                        echo '<span class="cert_req">(필수)</span>';
	                    echo '<noscript>본인확인을 위해서는 자바스크립트 사용이 가능해야합니다.</noscript>'.PHP_EOL;
	                }
	                ?>
	                <?php
	                if ($config['cf_cert_use'] && $member['mb_certify']) {
						switch  ($member['mb_certify']) {
							case "simple": 
								$mb_cert = "간편인증";
								break;
							case "ipin": 
								$mb_cert = "아이핀";
								break;
							case "hp": 
								$mb_cert = "휴대폰";
								break;
						}                 
	                ?>
	                <div id="msg_certify">
	                    <strong><?php echo $mb_cert; ?> 본인확인</strong><?php if ($member['mb_adult']) { ?> 및 <strong>성인인증</strong><?php } ?> 완료
	                </div>
				<?php } ?>
				</li>
				
				<!-- 이름 입력 -->
	            <li>
	                <label for="reg_mb_name">이름<span class="required_star">*</span><?php echo $desc_name ?></label>
	                <div class="register_input_group">
	                    <i class="fas fa-id-card input_icon"></i>
	                    <input type="text" id="reg_mb_name" name="mb_name" value="<?php echo get_text($member['mb_name']) ?>" <?php echo $required ?> <?php echo $readonly; ?> class="frm_input full_input <?php echo $required ?> <?php echo $name_readonly ?>" size="10" placeholder="실명을 입력하세요">
	                </div>
	            </li>
	            
	            <!-- 닉네임 입력 -->
	            <?php if ($req_nick) {  ?>
	            <li>
	                <label for="reg_mb_nick">
	                	닉네임<span class="required_star">*</span>
	                	<button type="button" class="tooltip_icon"><i class="fa fa-question-circle-o" aria-hidden="true"></i><span class="sound_only">설명보기</span></button>
						<span class="tooltip">공백없이 한글,영문,숫자만 입력 가능 (한글2자, 영문4자 이상)<br> 닉네임을 바꾸시면 앞으로 <?php echo (int)$config['cf_nick_modify'] ?>일 이내에는 변경 할 수 없습니다.</span>
	                </label>
	                <div class="register_input_group">
	                    <i class="fas fa-user-tag input_icon"></i>
	                    <input type="hidden" name="mb_nick_default" value="<?php echo isset($member['mb_nick'])?get_text($member['mb_nick']):''; ?>">
	                    <input type="text" name="mb_nick" value="<?php echo isset($member['mb_nick'])?get_text($member['mb_nick']):''; ?>" id="reg_mb_nick" required class="frm_input required nospace full_input" size="10" maxlength="20" placeholder="닉네임을 입력하세요">
	                </div>
	                <span id="msg_mb_nick"></span>	                
	            </li>
	            <?php }  ?>
	
	            <!-- 이메일 입력 -->
	            <li>
	                <label for="reg_mb_email">E-mail<span class="required_star">*</span>
	                <?php if ($config['cf_use_email_certify']) {  ?>
	                <button type="button" class="tooltip_icon"><i class="fa fa-question-circle-o" aria-hidden="true"></i><span class="sound_only">설명보기</span></button>
					<span class="tooltip">
	                    <?php if ($w=='') { echo "E-mail 로 발송된 내용을 확인한 후 인증하셔야 회원가입이 완료됩니다."; }  ?>
	                    <?php if ($w=='u') { echo "E-mail 주소를 변경하시면 다시 인증하셔야 합니다."; }  ?>
	                </span>
	                <?php }  ?>
					</label>
					<div class="register_input_group">
	                    <i class="fas fa-envelope input_icon"></i>
	                    <input type="hidden" name="old_email" value="<?php echo $member['mb_email'] ?>">
	                    <input type="text" name="mb_email" value="<?php echo isset($member['mb_email'])?$member['mb_email']:''; ?>" id="reg_mb_email" required class="frm_input email full_input required" size="70" maxlength="100" placeholder="이메일 주소를 입력하세요">
	                </div>
	            </li>
	
	            <!-- 홈페이지 -->
	            <?php if ($config['cf_use_homepage']) {  ?>
	            <li>
	                <label for="reg_mb_homepage">홈페이지<?php if ($config['cf_req_homepage']){ ?><span class="required_star">*</span><?php } ?></label>
	                <div class="register_input_group">
	                    <i class="fas fa-globe input_icon"></i>
	                    <input type="text" name="mb_homepage" value="<?php echo get_text($member['mb_homepage']) ?>" id="reg_mb_homepage" <?php echo $config['cf_req_homepage']?"required":""; ?> class="frm_input full_input <?php echo $config['cf_req_homepage']?"required":""; ?>" size="70" maxlength="255" placeholder="홈페이지 URL을 입력하세요">
	                </div>
	            </li>
	            <?php }  ?>
	
	            <!-- 전화번호 -->
	            <?php if ($config['cf_use_tel']) {  ?>
	            <li>
	                <label for="reg_mb_tel">전화번호<?php if ($config['cf_req_tel']) { ?><span class="required_star">*</span><?php } ?></label>
	                <div class="register_input_group">
	                    <i class="fas fa-phone input_icon"></i>
	                    <input type="text" name="mb_tel" value="<?php echo get_text($member['mb_tel']) ?>" id="reg_mb_tel" <?php echo $config['cf_req_tel']?"required":""; ?> class="frm_input full_input <?php echo $config['cf_req_tel']?"required":""; ?>" maxlength="20" placeholder="전화번호를 입력하세요">
	                </div>
	            </li>
	            <?php }  ?>
				
				<!-- 휴대폰번호 with SMS 인증 -->
				<?php if ($config['cf_use_hp'] || ($config["cf_cert_use"] && ($config['cf_cert_hp'] || $config['cf_cert_simple']))) {  ?>
				<li>
					<label for="reg_mb_hp">휴대폰번호<?php if (!empty($hp_required)) { ?><span class="required_star">*</span><?php } ?><?php echo $desc_phone ?></label>
					
					<div class="phone_cert_wrap">
						<div class="register_input_group" style="flex: 1; margin-bottom: 0;">
							<i class="fas fa-mobile-alt input_icon"></i>
							<input type="text" name="mb_hp" value="<?php echo get_text($member['mb_hp']) ?>" id="reg_mb_hp" <?php echo $hp_required; ?> <?php echo $hp_readonly; ?> class="frm_input full_input <?php echo $hp_required; ?> <?php echo $hp_readonly; ?>" maxlength="20" placeholder="휴대폰번호를 입력하세요">
						</div>
						<?php 
						// SMS 인증 설정 확인 후 버튼 표시
						if ($w == '' && !$hp_readonly && function_exists('get_sms_config')) { 
							$sms_config = get_sms_config();
							if($sms_config && isset($sms_config['cf_use_register']) && $sms_config['cf_use_register']) {
						?>
						<button type="button" id="btn_send_sms" class="btn_phone_cert">인증번호 발송</button>
						<?php 
							}
						} 
						?>
					</div>
					
					<?php 
					// 인증번호 입력 영역도 같은 조건 적용
					if ($w == '' && !$hp_readonly && function_exists('get_sms_config')) { 
						$sms_config = get_sms_config();
						if($sms_config && isset($sms_config['cf_use_register']) && $sms_config['cf_use_register']) {
					?>
					<div id="cert_number_wrap" class="cert_number_wrap">
						<div class="phone_cert_wrap">
							<div class="register_input_group" style="flex: 1; margin-bottom: 0;">
								<i class="fas fa-key input_icon"></i>
								<input type="text" name="cert_number" id="cert_number" class="frm_input full_input" maxlength="6" placeholder="인증번호 6자리를 입력하세요">
							</div>
							<button type="button" id="btn_cert_confirm" class="btn_phone_cert">인증확인</button>
							<span id="cert_timer" class="cert_timer"></span>
						</div>
						<div id="cert_msg" style="margin-top: 10px; font-size: 13px;"></div>
					</div>
					
					<div id="sms_verified_msg" class="sms_verified_msg">
						<i class="fas fa-check-circle"></i> 휴대폰 인증이 완료되었습니다.
					</div>
					<?php 
						}
					} 
					?>
					
					<?php if ($config['cf_cert_use'] && ($config['cf_cert_hp'] || $config['cf_cert_simple'])) { ?>
					<input type="hidden" name="old_mb_hp" value="<?php echo get_text($member['mb_hp']) ?>">
					<?php } ?>
				</li>
				<?php }  ?>
	            <!-- 주소 -->
	            <?php if ($config['cf_use_addr']) { ?>
	            <li>
	            	<label>주소<?php if ($config['cf_req_addr']) { ?><span class="required_star">*</span><?php }  ?></label>
	                
	                <div class="address_search_wrap">
	                    <div class="register_input_group" style="margin-bottom: 0;">
	                        <i class="fas fa-map-marker-alt input_icon"></i>
	                        <input type="text" name="mb_zip" value="<?php echo $member['mb_zip1'].$member['mb_zip2']; ?>" id="reg_mb_zip" <?php echo $config['cf_req_addr']?"required":""; ?> class="frm_input <?php echo $config['cf_req_addr']?"required":""; ?>" size="5" maxlength="6" placeholder="우편번호">
	                    </div>
	                    <button type="button" class="btn_phone_cert" onclick="win_zip('fregisterform', 'mb_zip', 'mb_addr1', 'mb_addr2', 'mb_addr3', 'mb_addr_jibeon');">주소 검색</button>
	                </div>
	                
	                <div class="register_input_group">
	                    <i class="fas fa-home input_icon"></i>
	                    <input type="text" name="mb_addr1" value="<?php echo get_text($member['mb_addr1']) ?>" id="reg_mb_addr1" <?php echo $config['cf_req_addr']?"required":""; ?> class="frm_input frm_address full_input <?php echo $config['cf_req_addr']?"required":""; ?>" size="50" placeholder="기본주소">
	                </div>
	                
	                <div class="register_input_group">
	                    <i class="fas fa-building input_icon"></i>
	                    <input type="text" name="mb_addr2" value="<?php echo get_text($member['mb_addr2']) ?>" id="reg_mb_addr2" class="frm_input frm_address full_input" size="50" placeholder="상세주소">
	                </div>
	                
	                <div class="register_input_group">
	                    <i class="fas fa-info-circle input_icon"></i>
	                    <input type="text" name="mb_addr3" value="<?php echo get_text($member['mb_addr3']) ?>" id="reg_mb_addr3" class="frm_input frm_address full_input" size="50" readonly="readonly" placeholder="참고항목">
	                </div>
	                <input type="hidden" name="mb_addr_jibeon" value="<?php echo get_text($member['mb_addr_jibeon']); ?>">
	            </li>
	            <?php }  ?>
	        </ul>
	    </div>
	
	    <!-- =================================== 
	     * 기타 개인설정
	     * =================================== -->
	    <div class="tbl_frm01 tbl_wrap register_form_inner">
	        <h2>기타 개인설정</h2>
	        <ul>
	            <!-- 서명 -->
	            <?php if ($config['cf_use_signature']) {  ?>
	            <li>
	                <label for="reg_mb_signature">서명<?php if ($config['cf_req_signature']){ ?><span class="required_star">*</span><?php } ?></label>
	                <textarea name="mb_signature" id="reg_mb_signature" <?php echo $config['cf_req_signature']?"required":""; ?> class="<?php echo $config['cf_req_signature']?"required":""; ?>" placeholder="서명을 입력하세요"><?php echo $member['mb_signature'] ?></textarea>
	            </li>
	            <?php }  ?>
	
	            <!-- 자기소개 -->
	            <?php if ($config['cf_use_profile']) {  ?>
	            <li>
	                <label for="reg_mb_profile">자기소개</label>
	                <textarea name="mb_profile" id="reg_mb_profile" <?php echo $config['cf_req_profile']?"required":""; ?> class="<?php echo $config['cf_req_profile']?"required":""; ?>" placeholder="자기소개를 입력해주세요"><?php echo $member['mb_profile'] ?></textarea>
	            </li>
	            <?php }  ?>
	
	            <!-- 회원아이콘 -->
	            <?php if ($config['cf_use_member_icon'] && $member['mb_level'] >= $config['cf_icon_level']) {  ?>
	            <li>
	                <label for="reg_mb_icon" class="frm_label">
	                	회원아이콘
	                	<button type="button" class="tooltip_icon"><i class="fa fa-question-circle-o" aria-hidden="true"></i><span class="sound_only">설명보기</span></button>
	                	<span class="tooltip">이미지 크기는 가로 <?php echo $config['cf_member_icon_width'] ?>픽셀, 세로 <?php echo $config['cf_member_icon_height'] ?>픽셀 이하로 해주세요.<br>
gif, jpg, png파일만 가능하며 용량 <?php echo number_format($config['cf_member_icon_size']) ?>바이트 이하만 등록됩니다.</span>
	                </label>
	                <input type="file" name="mb_icon" id="reg_mb_icon">
	
	                <?php if ($w == 'u' && file_exists($mb_icon_path)) {  ?>
	                <img src="<?php echo $mb_icon_url ?>" alt="회원아이콘">
	                <input type="checkbox" name="del_mb_icon" value="1" id="del_mb_icon">
	                <label for="del_mb_icon" class="inline">삭제</label>
	                <?php }  ?>
	            </li>
	            <?php }  ?>
	
	            <!-- 회원이미지 -->
	            <?php if ($member['mb_level'] >= $config['cf_icon_level'] && $config['cf_member_img_size'] && $config['cf_member_img_width'] && $config['cf_member_img_height']) {  ?>
	            <li class="reg_mb_img_file">
	                <label for="reg_mb_img" class="frm_label">
	                	회원이미지
	                	<button type="button" class="tooltip_icon"><i class="fa fa-question-circle-o" aria-hidden="true"></i><span class="sound_only">설명보기</span></button>
	                	<span class="tooltip">이미지 크기는 가로 <?php echo $config['cf_member_img_width'] ?>픽셀, 세로 <?php echo $config['cf_member_img_height'] ?>픽셀 이하로 해주세요.<br>
	                    gif, jpg, png파일만 가능하며 용량 <?php echo number_format($config['cf_member_img_size']) ?>바이트 이하만 등록됩니다.</span>
	                </label>
	                <input type="file" name="mb_img" id="reg_mb_img">
	
	                <?php if ($w == 'u' && file_exists($mb_img_path)) {  ?>
	                <img src="<?php echo $mb_img_url ?>" alt="회원이미지">
	                <input type="checkbox" name="del_mb_img" value="1" id="del_mb_img">
	                <label for="del_mb_img" class="inline">삭제</label>
	                <?php }  ?>
	            </li>
	            <?php } ?>
	            
	            <!-- 메일링서비스 -->
	            <li class="custom_checkbox">
		        	<input type="checkbox" name="mb_mailling" value="1" id="reg_mb_mailling" <?php echo ($w=='' || $member['mb_mailling'])?'checked':''; ?> class="selec_chk">
		            <label for="reg_mb_mailling">정보 메일을 받겠습니다.</label>
		        </li>
	
				<!-- SMS 수신여부 -->
				<?php if ($config['cf_use_hp']) { ?>
		        <li class="custom_checkbox">
		            <input type="checkbox" name="mb_sms" value="1" id="reg_mb_sms" <?php echo ($w=='' || $member['mb_sms'])?'checked':''; ?> class="selec_chk">
		        	<label for="reg_mb_sms">휴대폰 문자메세지를 받겠습니다.</label>
		        </li>
		        <?php } ?>
	
		        <!-- 정보공개 -->
		        <?php if (isset($member['mb_open_date']) && $member['mb_open_date'] <= date("Y-m-d", G5_SERVER_TIME - ($config['cf_open_modify'] * 86400)) || empty($member['mb_open_date'])) { // 정보공개 수정일이 지났다면 수정가능 ?>
		        <li class="custom_checkbox">
		            <input type="checkbox" name="mb_open" value="1" id="reg_mb_open" <?php echo ($w=='' || $member['mb_open'])?'checked':''; ?> class="selec_chk">
		      		<label for="reg_mb_open">다른분들이 나의 정보를 볼 수 있도록 합니다.</label>
		            <input type="hidden" name="mb_open_default" value="<?php echo $member['mb_open'] ?>"> 
		        </li>		        
		        <?php } else { ?>
	            <li>
	                정보공개
	                <input type="hidden" name="mb_open" value="<?php echo $member['mb_open'] ?>">
	                <button type="button" class="tooltip_icon"><i class="fa fa-question-circle-o" aria-hidden="true"></i><span class="sound_only">설명보기</span></button>
	                <span class="tooltip">
	                    정보공개는 수정후 <?php echo (int)$config['cf_open_modify'] ?>일 이내, <?php echo date("Y년 m월 j일", isset($member['mb_open_date']) ? strtotime("{$member['mb_open_date']} 00:00:00")+$config['cf_open_modify']*86400:G5_SERVER_TIME+$config['cf_open_modify']*86400); ?> 까지는 변경이 안됩니다.<br>
	                    이렇게 하는 이유는 잦은 정보공개 수정으로 인하여 쪽지를 보낸 후 받지 않는 경우를 막기 위해서 입니다.
	                </span>
	            </li>
	            <?php }  ?>
	
	            <?php
	            //회원정보 수정인 경우 소셜 계정 출력
	            if( $w == 'u' && function_exists('social_member_provider_manage') ){
	                social_member_provider_manage();
	            }
	            ?>
	            
	            <!-- 추천인 -->
	            <?php if ($w == "" && $config['cf_use_recommend']) {  ?>
	            <li>
	                <label for="reg_mb_recommend" class="sound_only">추천인아이디</label>
	                <div class="register_input_group">
	                    <i class="fas fa-user-plus input_icon"></i>
	                    <input type="text" name="mb_recommend" id="reg_mb_recommend" class="frm_input" placeholder="추천인 아이디를 입력하세요">
	                </div>
	            </li>
	            <?php }  ?>
	
	            <!-- 자동등록방지 -->
	            <li class="is_captcha_use">
	                자동등록방지
	                <?php echo captcha_html(); ?>
	            </li>
	        </ul>
	    </div>
	</div>
	
	<div class="btn_confirm">
	    <a href="<?php echo G5_URL ?>" class="btn_close">취소</a>
	    <button type="submit" id="btn_submit" class="btn_submit" accesskey="s"><?php echo $w==''?'회원가입':'정보수정'; ?></button>
	</div>
	</form>
</div>

<script>
// SMS 인증 관련 변수
var sms_timer = null;
var sms_time = 180; // 3분
var is_sms_verified = false;

$(function() {
    $("#reg_zip_find").css("display", "inline-block");
    
    // SMS 인증번호 발송
    $("#btn_send_sms").click(function() {
        var hp = $("#reg_mb_hp").val();
        
        if(!hp) {
            alert("휴대폰번호를 입력해주세요.");
            $("#reg_mb_hp").focus();
            return false;
        }
        
        // 휴대폰번호 유효성 검사
        var regPhone = /^01([0|1|6|7|8|9])-?([0-9]{3,4})-?([0-9]{4})$/;
        if(!regPhone.test(hp)) {
            alert("올바른 휴대폰번호를 입력해주세요.");
            $("#reg_mb_hp").focus();
            return false;
        }
        
        // 하이픈 제거
        hp = hp.replace(/-/g, '');
        
        $.ajax({
            type: "POST",
            url: g5_bbs_url + "/ajax.sms_send.php",
            data: {
                "hp": hp,
                "type": "register"  // 중요: type을 register로 설정
            },
            dataType: "json",
            async: true,
            cache: false,
            success: function(data) {
                if(data.error) {
                    alert(data.error);
                    return false;
                }
                
                if(data.success) {
                    $("#cert_number_wrap").addClass("active");
                    $("#btn_send_sms").text("재발송").prop("disabled", true);
                    
                    // 테스트용 - 인증번호 표시
                    if(data.debug_auth_code) {
                        $("#cert_msg").html('<span style="color: #3a8afd;">인증번호가 발송되었습니다. (테스트: ' + data.debug_auth_code + ')</span>');
                    } else {
                        $("#cert_msg").html('<span style="color: #3a8afd;">인증번호가 발송되었습니다.</span>');
                    }
                    
                    // 타이머 시작
                    startTimer();
                    
                    // 30초 후 재발송 버튼 활성화
                    setTimeout(function() {
                        $("#btn_send_sms").prop("disabled", false);
                    }, 30000);
                }
            },
            error: function(xhr, status, error) {
                alert("인증번호 발송 중 오류가 발생했습니다.");
                console.log(error);
            }
        });
    });
    
    // 인증번호 확인
    $("#btn_cert_confirm").click(function() {
        var cert_number = $("#cert_number").val();
        var hp = $("#reg_mb_hp").val().replace(/-/g, '');
        
        if(!cert_number) {
            alert("인증번호를 입력해주세요.");
            $("#cert_number").focus();
            return false;
        }
        
        $.ajax({
            type: "POST",
            url: g5_bbs_url + "/ajax.sms_verify.php",
            data: {
                "hp": hp,
                "cert_number": cert_number,
                "type": "register"  // 중요: type을 register로 설정
            },
            dataType: "json",
            async: true,
            cache: false,
            success: function(data) {
                console.log("인증 응답:", data); // 디버깅용
                
                if(data.error) {
                    alert(data.error);
                    $("#cert_msg").html('<span style="color: #dc3545;">' + data.error + '</span>');
                    return false;
                }
                
                if(data.verified) {
                    clearInterval(sms_timer);
                    $("#cert_timer").text("");
                    $("#cert_msg").html('');
                    
                    // 인증번호 입력 영역 숨기기
                    $("#cert_number_wrap").slideUp(300, function() {
                        // 인증 완료 메시지 표시
                        $("#sms_verified_msg").slideDown(300);
                    });
                    
                    // 휴대폰 번호 필드 수정 불가
                    $("#reg_mb_hp").attr("readonly", true).css({
                        "background-color": "#f8f9fa",
                        "color": "#495057"
                    });
                    
                    // 재발송 버튼 숨기기
                    $("#btn_send_sms").fadeOut(300);
                    
                    // 인증 완료 플래그
                    is_sms_verified = true;
                    
                    // 추가 시각적 효과
                    $("#reg_mb_hp").parent().css("border-color", "#28a745");
                }
            },
            error: function(xhr, status, error) {
                alert("인증 확인 중 오류가 발생했습니다.");
                console.log("Error:", error);
            }
        });
    });
    
    // 타이머 함수
    function startTimer() {
        sms_time = 180;
        clearInterval(sms_timer);
        
        sms_timer = setInterval(function() {
            var minutes = Math.floor(sms_time / 60);
            var seconds = sms_time % 60;
            
            $("#cert_timer").text(minutes + ":" + (seconds < 10 ? "0" : "") + seconds);
            
            if(sms_time <= 0) {
                clearInterval(sms_timer);
                $("#cert_timer").text("시간만료");
                $("#cert_msg").html('<span style="color: #dc3545;">인증시간이 만료되었습니다. 재발송해주세요.</span>');
                $("#btn_send_sms").prop("disabled", false);
            }
            
            sms_time--;
        }, 1000);
    }
    
    // 기존 본인인증 코드들...
    var pageTypeParam = "pageType=register";
    
	<?php if($config['cf_cert_use'] && $config['cf_cert_simple']) { ?>
	// 이니시스 간편인증
	var url = "<?php echo G5_INICERT_URL; ?>/ini_request.php";
	var type = "";    
    var params = "";
    var request_url = "";
	$(".win_sa_cert").click(function() {
		if(!cert_confirm()) return false;
		type = $(this).data("type");
		params = "?directAgency=" + type + "&" + pageTypeParam;
        request_url = url + params;
        call_sa(request_url);
	});
    <?php } ?>
    
    <?php if($config['cf_cert_use'] && $config['cf_cert_ipin']) { ?>
    // 아이핀인증
    var params = "";
    $("#win_ipin_cert").click(function() {
		if(!cert_confirm()) return false;
        params = "?" + pageTypeParam;
        var url = "<?php echo G5_OKNAME_URL; ?>/ipin1.php"+params;
        certify_win_open('kcb-ipin', url);
        return;
    });
    <?php } ?>
    
    <?php if($config['cf_cert_use'] && $config['cf_cert_hp']) { ?>
    // 휴대폰인증
    var params = "";
    $("#win_hp_cert").click(function() {
		if(!cert_confirm()) return false;
        params = "?" + pageTypeParam;
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
        
        certify_win_open("<?php echo $cert_type; ?>", "<?php echo $cert_url; ?>"+params);
        return;
    });
    <?php } ?>
});

// submit 최종 폼체크
function fregisterform_submit(f)
{
    // SMS 인증 확인 (회원가입시만)
    <?php if($w == '') { ?>
    var hp = f.mb_hp.value.replace(/-/g, '');
    if(hp && hp.length >= 10) {
        <?php if(function_exists('get_sms_config')) { 
            $sms_config = get_sms_config();
            if($sms_config && isset($sms_config['cf_use_register']) && $sms_config['cf_use_register']) {
        ?>
        if(!is_sms_verified && !f.cert_no.value) {
            alert("휴대폰 인증을 완료해주세요.");
            return false;
        }
        <?php } } ?>
    }
    <?php } ?>
    
    // 회원아이디 검사
    if (f.w.value == "") {
        var msg = reg_mb_id_check();
        if (msg) {
            alert(msg);
            f.mb_id.select();
            return false;
        }
    }
    
    if (f.w.value == "") {
        if (f.mb_password.value.length < 3) {
            alert("비밀번호를 3글자 이상 입력하십시오.");
            f.mb_password.focus();
            return false;
        }
    }
    
    if (f.mb_password.value != f.mb_password_re.value) {
        alert("비밀번호가 같지 않습니다.");
        f.mb_password_re.focus();
        return false;
    }
    
    if (f.mb_password.value.length > 0) {
        if (f.mb_password_re.value.length < 3) {
            alert("비밀번호를 3글자 이상 입력하십시오.");
            f.mb_password_re.focus();
            return false;
        }
    }
    
    // 이름 검사
    if (f.w.value=="") {
        if (f.mb_name.value.length < 1) {
            alert("이름을 입력하십시오.");
            f.mb_name.focus();
            return false;
        }
    }
    
    <?php if($w == '' && $config['cf_cert_use'] && $config['cf_cert_req']) { ?>
    // 본인확인 체크
    if(f.cert_no.value=="") {
        alert("회원가입을 위해서는 본인확인을 해주셔야 합니다.");
        return false;
    }
    <?php } ?>
    
    // 닉네임 검사
    if ((f.w.value == "") || (f.w.value == "u" && f.mb_nick.defaultValue != f.mb_nick.value)) {
        var msg = reg_mb_nick_check();
        if (msg) {
            alert(msg);
            f.reg_mb_nick.select();
            return false;
        }
    }
    
    // E-mail 검사
    if ((f.w.value == "") || (f.w.value == "u" && f.mb_email.defaultValue != f.mb_email.value)) {
        var msg = reg_mb_email_check();
        if (msg) {
            alert(msg);
            f.reg_mb_email.select();
            return false;
        }
    }
    
    <?php if (($config['cf_use_hp'] || $config['cf_cert_hp']) && $config['cf_req_hp']) {  ?>
    // 휴대폰번호 체크
    var msg = reg_mb_hp_check();
    if (msg) {
        alert(msg);
        f.reg_mb_hp.select();
        return false;
    }
    <?php } ?>
    
    if (typeof f.mb_icon != "undefined") {
        if (f.mb_icon.value) {
            if (!f.mb_icon.value.toLowerCase().match(/.(gif|jpe?g|png)$/i)) {
                alert("회원아이콘이 이미지 파일이 아닙니다.");
                f.mb_icon.focus();
                return false;
            }
        }
    }
    
    if (typeof f.mb_img != "undefined") {
        if (f.mb_img.value) {
            if (!f.mb_img.value.toLowerCase().match(/.(gif|jpe?g|png)$/i)) {
                alert("회원이미지가 이미지 파일이 아닙니다.");
                f.mb_img.focus();
                return false;
            }
        }
    }
    
    if (typeof(f.mb_recommend) != "undefined" && f.mb_recommend.value) {
        if (f.mb_id.value == f.mb_recommend.value) {
            alert("본인을 추천할 수 없습니다.");
            f.mb_recommend.focus();
            return false;
        }
        
        var msg = reg_mb_recommend_check();
        if (msg) {
            alert(msg);
            f.mb_recommend.select();
            return false;
        }
    }
    
    <?php echo chk_captcha_js();  ?>
    
    document.getElementById("btn_submit").disabled = "disabled";
    
    return true;
}

// 툴팁
jQuery(function($){
    $(document).on("click", ".tooltip_icon", function(e){
        $(this).next(".tooltip").fadeIn(400).css("display","inline-block");
    }).on("mouseout", ".tooltip_icon", function(e){
        $(this).next(".tooltip").fadeOut();
    });
});
</script>

<!-- } 회원정보 입력/수정 끝 -->