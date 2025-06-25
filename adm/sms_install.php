<?php
/*
 * 파일명: sms_install.php
 * 위치: /adm/sms_install.php
 * 기능: SMS 인증 시스템 자동 설치
 * 작성일: 2024-12-28
 */

$sub_menu = "900920";
include_once('./_common.php');

// 관리자만 접근 가능
if ($is_admin != 'super')
    die('관리자만 접근 가능합니다.');

$g5['title'] = 'SMS 인증 시스템 설치';
include_once('./admin.head.php');

// 테이블명 정의
$g5['sms_config_table'] = G5_TABLE_PREFIX.'sms_config';
$g5['sms_log_table'] = G5_TABLE_PREFIX.'sms_log';
$g5['sms_auth_table'] = G5_TABLE_PREFIX.'sms_auth';
$g5['sms_blacklist_table'] = G5_TABLE_PREFIX.'sms_blacklist';
$g5['sms_limit_table'] = G5_TABLE_PREFIX.'sms_limit';
?>

<style>
.install-wrap {
    max-width: 800px;
    margin: 20px auto;
}
.install-step {
    margin: 20px 0;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 5px;
}
.install-step h3 {
    margin-bottom: 10px;
    color: #333;
}
.result {
    margin: 10px 0;
    padding: 10px;
    border-radius: 3px;
}
.result.success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}
.result.error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}
.result.info {
    background: #d1ecf1;
    color: #0c5460;
    border: 1px solid #bee5eb;
}
.btn-area {
    text-align: center;
    margin-top: 30px;
}
</style>

<div class="install-wrap">

<?php
if($w == 'u') {
    $errors = array();
    $success = array();
    
    echo '<div class="install-step">';
    echo '<h3>설치 진행 중...</h3>';
    
    // ===================================
    // 1. 테이블 생성
    // ===================================
    echo '<h4>1. 데이터베이스 테이블 생성</h4>';
    
    // SMS 설정 테이블
    $sql = " CREATE TABLE IF NOT EXISTS `{$g5['sms_config_table']}` (
        `cf_service` varchar(20) NOT NULL DEFAULT 'icode' COMMENT '사용 서비스(icode/aligo)',
        `cf_icode_id` varchar(100) DEFAULT NULL COMMENT '아이코드 아이디',
        `cf_icode_pw` varchar(100) DEFAULT NULL COMMENT '아이코드 패스워드',
        `cf_aligo_key` varchar(100) DEFAULT NULL COMMENT '알리고 API키',
        `cf_aligo_userid` varchar(100) DEFAULT NULL COMMENT '알리고 유저아이디',
        `cf_phone` varchar(20) DEFAULT NULL COMMENT '발신번호',
        `cf_daily_limit` int(11) DEFAULT 5 COMMENT '일일 발송 제한',
        `cf_hourly_limit` int(11) DEFAULT 3 COMMENT '시간당 발송 제한',
        `cf_resend_delay` int(11) DEFAULT 60 COMMENT '재발송 대기시간(초)',
        `cf_ip_daily_limit` int(11) DEFAULT 20 COMMENT 'IP당 일일 발송 제한',
        `cf_auth_timeout` int(11) DEFAULT 180 COMMENT '인증번호 유효시간(초)',
        `cf_max_try` int(11) DEFAULT 5 COMMENT '최대 인증 시도 횟수',
        `cf_use_captcha` tinyint(4) DEFAULT 0 COMMENT '캡차 사용 여부',
        `cf_captcha_count` int(11) DEFAULT 3 COMMENT '캡차 표시 기준 횟수',
        `cf_block_foreign` tinyint(4) DEFAULT 1 COMMENT '해외번호 차단',
        `cf_use_blacklist` tinyint(4) DEFAULT 1 COMMENT '블랙리스트 사용',
        `cf_use_register` tinyint(4) DEFAULT 1 COMMENT '회원가입시 사용',
        `cf_use_password` tinyint(4) DEFAULT 1 COMMENT '비밀번호찾기 사용'
    ) ";
    
    if(sql_query($sql, false)) {
        // 기본값 입력
        $sql = " select count(*) as cnt from {$g5['sms_config_table']} ";
        $row = sql_fetch($sql);
        if($row['cnt'] == 0) {
            $sql = " insert into {$g5['sms_config_table']} set cf_service = 'icode' ";
            sql_query($sql);
        }
        echo '<div class="result success">✓ SMS 설정 테이블 생성 완료</div>';
    } else {
        echo '<div class="result error">✗ SMS 설정 테이블 생성 실패</div>';
        $errors[] = 'SMS 설정 테이블';
    }
    
    // SMS 발송 로그 테이블
    $sql = " CREATE TABLE IF NOT EXISTS `{$g5['sms_log_table']}` (
        `sl_id` int(11) NOT NULL AUTO_INCREMENT,
        `sl_type` varchar(20) DEFAULT NULL COMMENT '발송 타입(register/password)',
        `mb_id` varchar(20) DEFAULT NULL COMMENT '회원아이디',
        `sl_phone` varchar(20) DEFAULT NULL COMMENT '수신번호',
        `sl_message` text COMMENT '메시지 내용',
        `sl_result` varchar(10) DEFAULT NULL COMMENT '발송결과(success/fail)',
        `sl_ip` varchar(50) DEFAULT NULL COMMENT 'IP주소',
        `sl_datetime` datetime NOT NULL COMMENT '발송일시',
        PRIMARY KEY (`sl_id`),
        KEY `idx_phone` (`sl_phone`),
        KEY `idx_datetime` (`sl_datetime`)
    ) ";
    
    if(sql_query($sql, false)) {
        echo '<div class="result success">✓ SMS 발송 로그 테이블 생성 완료</div>';
    } else {
        echo '<div class="result error">✗ SMS 발송 로그 테이블 생성 실패</div>';
        $errors[] = 'SMS 발송 로그 테이블';
    }
    
    // SMS 인증 테이블
    $sql = " CREATE TABLE IF NOT EXISTS `{$g5['sms_auth_table']}` (
        `sa_id` int(11) NOT NULL AUTO_INCREMENT,
        `sa_type` varchar(20) DEFAULT NULL COMMENT '인증 타입(register/password)',
        `sa_phone` varchar(20) DEFAULT NULL COMMENT '인증 전화번호',
        `sa_auth_code` varchar(10) DEFAULT NULL COMMENT '인증번호',
        `sa_ip` varchar(50) DEFAULT NULL COMMENT 'IP주소',
        `sa_try_count` int(11) DEFAULT 0 COMMENT '시도 횟수',
        `sa_verified` tinyint(4) DEFAULT 0 COMMENT '인증 완료 여부',
        `sa_datetime` datetime NOT NULL COMMENT '생성일시',
        `sa_expire_datetime` datetime NOT NULL COMMENT '만료일시',
        PRIMARY KEY (`sa_id`),
        KEY `idx_phone_code` (`sa_phone`, `sa_auth_code`)
    ) ";
    
    if(sql_query($sql, false)) {
        echo '<div class="result success">✓ SMS 인증 테이블 생성 완료</div>';
    } else {
        echo '<div class="result error">✗ SMS 인증 테이블 생성 실패</div>';
        $errors[] = 'SMS 인증 테이블';
    }
    
    // 차단 번호 테이블
    $sql = " CREATE TABLE IF NOT EXISTS `{$g5['sms_blacklist_table']}` (
        `sb_id` int(11) NOT NULL AUTO_INCREMENT,
        `sb_phone` varchar(20) DEFAULT NULL COMMENT '차단 전화번호',
        `sb_reason` varchar(255) DEFAULT NULL COMMENT '차단 사유',
        `sb_datetime` datetime NOT NULL COMMENT '차단일시',
        PRIMARY KEY (`sb_id`),
        KEY `idx_phone` (`sb_phone`)
    ) ";
    
    if(sql_query($sql, false)) {
        echo '<div class="result success">✓ 차단번호 테이블 생성 완료</div>';
    } else {
        echo '<div class="result error">✗ 차단번호 테이블 생성 실패</div>';
        $errors[] = '차단번호 테이블';
    }
    
    // 발송 제한 테이블
    $sql = " CREATE TABLE IF NOT EXISTS `{$g5['sms_limit_table']}` (
        `sl_phone` varchar(20) NOT NULL COMMENT '전화번호',
        `sl_date` date NOT NULL COMMENT '날짜',
        `sl_daily_count` int(11) DEFAULT 0 COMMENT '일일 발송 횟수',
        `sl_hourly_count` int(11) DEFAULT 0 COMMENT '시간당 발송 횟수',
        `sl_last_send` datetime DEFAULT NULL COMMENT '마지막 발송 시간',
        `sl_ip` varchar(50) DEFAULT NULL COMMENT 'IP주소',
        `sl_ip_count` int(11) DEFAULT 0 COMMENT 'IP 발송 횟수',
        PRIMARY KEY (`sl_phone`, `sl_date`),
        KEY `idx_ip_date` (`sl_ip`, `sl_date`)
    ) ";
    
    if(sql_query($sql, false)) {
        echo '<div class="result success">✓ 발송제한 테이블 생성 완료</div>';
    } else {
        echo '<div class="result error">✗ 발송제한 테이블 생성 실패</div>';
        $errors[] = '발송제한 테이블';
    }
    
    // ===================================
    // 2. 파일 존재 확인
    // ===================================
    echo '<h4>2. 필수 파일 확인</h4>';
    
    $required_files = array(
        '/extend/sms_config.php' => 'SMS 설정 라이브러리',
        '/plugin/sms/aligo.php' => '알리고 API',
        '/bbs/sms_send.php' => 'SMS 발송 처리',
        '/bbs/sms_verify.php' => 'SMS 인증 처리',
        '/adm/sms_config.php' => '관리자 설정',
        '/adm/sms_config_update.php' => '설정 저장',
        '/adm/sms_log.php' => '발송 로그',
        '/adm/sms_blacklist.php' => '차단번호 관리'
    );
    
    $file_errors = array();
    foreach($required_files as $file => $desc) {
        if(file_exists(G5_PATH.$file)) {
            echo '<div class="result success">✓ ' . $desc . ' (' . $file . ')</div>';
        } else {
            echo '<div class="result error">✗ ' . $desc . ' (' . $file . ') - 파일이 없습니다</div>';
            $file_errors[] = $file;
        }
    }
    
    echo '</div>';
    
    // ===================================
    // 설치 결과
    // ===================================
    echo '<div class="install-step">';
    echo '<h3>설치 결과</h3>';
    
    if(count($errors) == 0 && count($file_errors) == 0) {
        echo '<div class="result success" style="font-size: 16px; font-weight: bold;">✓ SMS 인증 시스템 설치가 완료되었습니다!</div>';
        echo '<div class="result info">이제 관리자 페이지에서 SMS 설정을 진행해주세요.</div>';
    } else {
        echo '<div class="result error" style="font-size: 16px; font-weight: bold;">✗ 설치 중 일부 오류가 발생했습니다.</div>';
        if(count($errors) > 0) {
            echo '<div class="result error">데이터베이스 오류: ' . implode(', ', $errors) . '</div>';
        }
        if(count($file_errors) > 0) {
            echo '<div class="result error">누락된 파일을 업로드해주세요.</div>';
        }
    }
    
    echo '</div>';
    
    echo '<div class="btn-area">';
    if(count($errors) == 0 && count($file_errors) == 0) {
        echo '<a href="'.G5_ADMIN_URL.'/sms_config.php" class="btn btn_01">SMS 설정하기</a>';
    } else {
        echo '<a href="'.$_SERVER['PHP_SELF'].'" class="btn btn_02">다시 확인</a>';
    }
    echo '<a href="'.G5_ADMIN_URL.'" class="btn btn_02">관리자 메인</a>';
    echo '</div>';
    
} else {
    // 설치 전 안내
    ?>
    <div class="install-step">
        <h3>설치 전 확인사항</h3>
        <ul>
            <li>모든 파일이 정확한 위치에 업로드되었는지 확인하세요.</li>
            <li>데이터베이스 백업을 권장합니다.</li>
            <li>PHP <?php echo phpversion(); ?> 사용 중</li>
        </ul>
    </div>
    
    <div class="install-step">
        <h3>설치될 항목</h3>
        <ul>
            <li>데이터베이스 테이블 5개 생성</li>
            <li>SMS 인증 기능 활성화</li>
        </ul>
    </div>
    
    <div class="btn-area">
        <a href="?w=u" class="btn btn_01" onclick="return confirm('SMS 인증 시스템을 설치하시겠습니까?');">설치 시작</a>
        <a href="<?php echo G5_ADMIN_URL; ?>" class="btn btn_02">취소</a>
    </div>
    <?php
}
?>
</div>

<?php
include_once('./admin.tail.php');
?>