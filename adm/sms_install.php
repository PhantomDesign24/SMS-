<?php
/*
 * 파일명: sms_install.php
 * 위치: /adm/sms_install.php
 * 기능: SMS 인증 시스템 자동 설치
 * 작성일: 2024-12-28
 * 수정일: 2024-12-29
 */

$sub_menu = "900000";
include_once('./_common.php');

auth_check($auth[$sub_menu], 'r');

$g5['title'] = 'SMS 인증 시스템 설치';
include_once('./admin.head.php');
?>

<style>
.install-wrap {
    max-width: 800px;
    margin: 50px auto;
    padding: 30px;
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 5px;
}
.install-header {
    text-align: center;
    margin-bottom: 30px;
}
.install-header h1 {
    font-size: 24px;
    margin-bottom: 10px;
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
.btn {
    display: inline-block;
    padding: 10px 30px;
    background: #333;
    color: #fff !important;
    text-decoration: none;
    border-radius: 3px;
    margin: 0 5px;
}
.btn:hover {
    background: #555;
}
.btn.btn-primary {
    background: #007bff;
	color:#fff;
}
.btn.btn-primary:hover {
    background: #0056b3;
}
.update-notice {
    background: #fff3cd;
    border: 1px solid #ffeaa7;
    color: #856404;
    padding: 15px;
    border-radius: 5px;
    margin: 20px 0;
}
.update-notice h4 {
    margin-top: 0;
}
</style>

<div class="install-wrap">
    <div class="install-header">
        <h1>SMS 인증 시스템 설치</h1>
        <p>그누보드5 SMS 인증 시스템을 설치합니다.</p>
    </div>

<?php
if($_GET['step'] == 'install') {
    $errors = array();
    $success = array();
    $updates = array();
    
    echo '<div class="install-step">';
    echo '<h3>설치 진행 중...</h3>';
    
    // ===================================
    // 1. 테이블 생성
    // ===================================
    echo '<h4>1. 데이터베이스 테이블 생성</h4>';
    
    // SMS 설정 테이블
    $sql = "CREATE TABLE IF NOT EXISTS `g5_sms_config` (
        `cf_service` varchar(20) NOT NULL DEFAULT 'icode' COMMENT '사용 서비스(icode/aligo)',
        `cf_use_sms` tinyint(4) DEFAULT 1 COMMENT 'SMS 사용 여부',
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
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8";
    
    if(sql_query($sql, false)) {
        // 기본값 입력
        $sql = "SELECT COUNT(*) as cnt FROM g5_sms_config";
        $row = sql_fetch($sql);
        if($row['cnt'] == 0) {
            $sql = "INSERT INTO g5_sms_config SET 
                    cf_service = 'icode',
                    cf_use_sms = 1";
            sql_query($sql);
        }
        echo '<div class="result success">✓ SMS 설정 테이블 생성 완료</div>';
    } else {
        echo '<div class="result error">✗ SMS 설정 테이블 생성 실패</div>';
        $errors[] = 'SMS 설정 테이블';
    }
    
    // SMS 발송 로그 테이블 (상세 필드 추가)
	$sql = "CREATE TABLE IF NOT EXISTS `g5_sms_log` (
		`sl_id` int(11) NOT NULL AUTO_INCREMENT,
		`sl_type` varchar(20) DEFAULT NULL COMMENT '발송 타입(register/password)',
		`mb_id` varchar(20) DEFAULT NULL COMMENT '회원아이디',
		`sl_phone` varchar(20) DEFAULT NULL COMMENT '수신번호',
		`sl_send_number` varchar(20) DEFAULT NULL COMMENT '발신번호',
		`sl_message` text COMMENT '메시지 내용',
		`sl_result` varchar(10) DEFAULT NULL COMMENT '발송결과(success/fail)',
		`sl_error_code` varchar(10) DEFAULT NULL COMMENT '에러코드',
		`sl_api_response` text COMMENT 'API 응답 전체',
		`sl_retry_count` int(11) DEFAULT '0' COMMENT '재시도 횟수',
		`sl_ip` varchar(50) DEFAULT NULL COMMENT 'IP주소',
		`sl_datetime` datetime NOT NULL COMMENT '발송일시',
		`sl_send_datetime` datetime DEFAULT NULL COMMENT '실제발송일시',
		`sl_carrier` varchar(20) DEFAULT NULL COMMENT '통신사',
		PRIMARY KEY (`sl_id`),
		KEY `idx_phone` (`sl_phone`),
		KEY `idx_datetime` (`sl_datetime`),
		KEY `idx_type` (`sl_type`),
		KEY `idx_result` (`sl_result`),
		KEY `idx_mb_id` (`mb_id`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8";
    
    if(sql_query($sql, false)) {
        echo '<div class="result success">✓ SMS 발송 로그 테이블 생성 완료</div>';
    } else {
        echo '<div class="result error">✗ SMS 발송 로그 테이블 생성 실패</div>';
        $errors[] = 'SMS 발송 로그 테이블';
    }
    
    // SMS 인증 테이블
    $sql = "CREATE TABLE IF NOT EXISTS `g5_sms_auth` (
        `sa_id` int(11) NOT NULL AUTO_INCREMENT,
        `sa_type` varchar(20) DEFAULT NULL COMMENT '인증 타입(register/password)',
        `sa_phone` varchar(20) DEFAULT NULL COMMENT '인증 전화번호',
        `sa_auth_code` varchar(10) DEFAULT NULL COMMENT '인증번호',
        `sa_ip` varchar(50) DEFAULT NULL COMMENT 'IP주소',
        `sa_try_count` int(11) DEFAULT 0 COMMENT '시도 횟수',
        `sa_verified` tinyint(4) DEFAULT 0 COMMENT '인증 완료 여부',
        `sa_datetime` datetime NOT NULL COMMENT '생성일시',
        `sa_expire_datetime` datetime NOT NULL COMMENT '만료일시',
        `sa_verified_datetime` datetime DEFAULT NULL COMMENT '인증완료일시',
        `sa_user_agent` text COMMENT '사용자 에이전트',
        PRIMARY KEY (`sa_id`),
        KEY `idx_phone_code` (`sa_phone`, `sa_auth_code`),
        KEY `idx_datetime` (`sa_datetime`),
        KEY `idx_expire` (`sa_expire_datetime`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8";
    
    if(sql_query($sql, false)) {
        echo '<div class="result success">✓ SMS 인증 테이블 생성 완료</div>';
    } else {
        echo '<div class="result error">✗ SMS 인증 테이블 생성 실패</div>';
        $errors[] = 'SMS 인증 테이블';
    }
    
    // 차단 번호 테이블
    $sql = "CREATE TABLE IF NOT EXISTS `g5_sms_blacklist` (
        `sb_id` int(11) NOT NULL AUTO_INCREMENT,
        `sb_phone` varchar(20) DEFAULT NULL COMMENT '차단 전화번호',
        `sb_reason` varchar(255) DEFAULT NULL COMMENT '차단 사유',
        `sb_datetime` datetime NOT NULL COMMENT '차단일시',
        `sb_admin_id` varchar(20) DEFAULT NULL COMMENT '처리 관리자',
        PRIMARY KEY (`sb_id`),
        KEY `idx_phone` (`sb_phone`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8";
    
    if(sql_query($sql, false)) {
        echo '<div class="result success">✓ 차단번호 테이블 생성 완료</div>';
    } else {
        echo '<div class="result error">✗ 차단번호 테이블 생성 실패</div>';
        $errors[] = '차단번호 테이블';
    }
    
    // 발송 제한 테이블
    $sql = "CREATE TABLE IF NOT EXISTS `g5_sms_limit` (
        `sl_phone` varchar(20) NOT NULL COMMENT '전화번호',
        `sl_date` date NOT NULL COMMENT '날짜',
        `sl_daily_count` int(11) DEFAULT 0 COMMENT '일일 발송 횟수',
        `sl_hourly_count` int(11) DEFAULT 0 COMMENT '시간당 발송 횟수',
        `sl_last_send` datetime DEFAULT NULL COMMENT '마지막 발송 시간',
        `sl_ip` varchar(50) DEFAULT NULL COMMENT 'IP주소',
        `sl_ip_count` int(11) DEFAULT 0 COMMENT 'IP 발송 횟수',
        `sl_block_until` datetime DEFAULT NULL COMMENT '차단 해제 시간',
        `sl_block_reason` varchar(100) DEFAULT NULL COMMENT '차단 사유',
        PRIMARY KEY (`sl_phone`, `sl_date`),
        KEY `idx_ip_date` (`sl_ip`, `sl_date`),
        KEY `idx_block` (`sl_block_until`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8";
    
    if(sql_query($sql, false)) {
        echo '<div class="result success">✓ 발송제한 테이블 생성 완료</div>';
    } else {
        echo '<div class="result error">✗ 발송제한 테이블 생성 실패</div>';
        $errors[] = '발송제한 테이블';
    }
    
    // ===================================
    // 2. 기존 테이블 업데이트
    // ===================================
    echo '<h4>2. 테이블 구조 업데이트</h4>';
    
    // 기존 테이블 필드 추가 (신규 추가 부분)
    $update_queries = array(
        array(
            'table' => 'g5_sms_log',
            'field' => 'sl_error_code',
            'query' => "ALTER TABLE g5_sms_log ADD `sl_error_code` varchar(10) DEFAULT NULL COMMENT '에러코드' AFTER `sl_result`"
        ),
        array(
            'table' => 'g5_sms_log',
            'field' => 'sl_api_response',
            'query' => "ALTER TABLE g5_sms_log ADD `sl_api_response` text COMMENT 'API 응답 전체' AFTER `sl_error_code`"
        ),
        array(
            'table' => 'g5_sms_log',
            'field' => 'sl_retry_count',
            'query' => "ALTER TABLE g5_sms_log ADD `sl_retry_count` int(11) DEFAULT '0' COMMENT '재시도 횟수' AFTER `sl_api_response`"
        ),
        array(
            'table' => 'g5_sms_log',
            'field' => 'sl_send_datetime',
            'query' => "ALTER TABLE g5_sms_log ADD `sl_send_datetime` datetime DEFAULT NULL COMMENT '실제발송일시' AFTER `sl_datetime`"
        ),
        array(
            'table' => 'g5_sms_log',
            'field' => 'sl_carrier',
            'query' => "ALTER TABLE g5_sms_log ADD `sl_carrier` varchar(20) DEFAULT NULL COMMENT '통신사' AFTER `sl_send_datetime`"
        ),
        array(
            'table' => 'g5_sms_log',
            'field' => 'sl_cost',
            'query' => "ALTER TABLE g5_sms_log ADD `sl_cost` decimal(10,2) DEFAULT '0.00' COMMENT '발송비용' AFTER `sl_carrier`"
        ),
        // g5_sms_auth 테이블 업데이트
        array(
            'table' => 'g5_sms_auth',
            'field' => 'sa_verified_datetime',
            'query' => "ALTER TABLE g5_sms_auth ADD `sa_verified_datetime` datetime DEFAULT NULL COMMENT '인증완료일시' AFTER `sa_expire_datetime`"
        ),
        array(
            'table' => 'g5_sms_auth',
            'field' => 'sa_user_agent',
            'query' => "ALTER TABLE g5_sms_auth ADD `sa_user_agent` text COMMENT '사용자 에이전트' AFTER `sa_verified_datetime`"
        ),
        // g5_sms_blacklist 테이블 업데이트
        array(
            'table' => 'g5_sms_blacklist',
            'field' => 'sb_admin_id',
            'query' => "ALTER TABLE g5_sms_blacklist ADD `sb_admin_id` varchar(20) DEFAULT NULL COMMENT '처리 관리자' AFTER `sb_datetime`"
        ),
        // g5_sms_limit 테이블 업데이트
        array(
            'table' => 'g5_sms_limit',
            'field' => 'sl_block_until',
            'query' => "ALTER TABLE g5_sms_limit ADD `sl_block_until` datetime DEFAULT NULL COMMENT '차단 해제 시간' AFTER `sl_ip_count`"
        ),
        array(
            'table' => 'g5_sms_limit',
            'field' => 'sl_block_reason',
            'query' => "ALTER TABLE g5_sms_limit ADD `sl_block_reason` varchar(100) DEFAULT NULL COMMENT '차단 사유' AFTER `sl_block_until`"
        )
    );
    
    foreach($update_queries as $update) {
        $sql = "SHOW COLUMNS FROM {$update['table']} LIKE '{$update['field']}'";
        $result = sql_query($sql, false);
        if(!sql_num_rows($result)) {
            if(sql_query($update['query'], false)) {
                echo '<div class="result success">✓ ' . $update['table'] . '.' . $update['field'] . ' 필드 추가 완료</div>';
                $updates[] = $update['table'] . '.' . $update['field'];
            } else {
                echo '<div class="result error">✗ ' . $update['table'] . '.' . $update['field'] . ' 필드 추가 실패</div>';
            }
        } else {
            echo '<div class="result info">ⓘ ' . $update['table'] . '.' . $update['field'] . ' 필드가 이미 존재합니다</div>';
        }
    }
    
    // 인덱스 추가
    $index_queries = array(
        array(
            'table' => 'g5_sms_log',
            'index' => 'idx_type',
            'query' => "ALTER TABLE g5_sms_log ADD INDEX `idx_type` (`sl_type`)"
        ),
        array(
            'table' => 'g5_sms_log',
            'index' => 'idx_result',
            'query' => "ALTER TABLE g5_sms_log ADD INDEX `idx_result` (`sl_result`)"
        ),
        array(
            'table' => 'g5_sms_log',
            'index' => 'idx_mb_id',
            'query' => "ALTER TABLE g5_sms_log ADD INDEX `idx_mb_id` (`mb_id`)"
        ),
        array(
            'table' => 'g5_sms_auth',
            'index' => 'idx_datetime',
            'query' => "ALTER TABLE g5_sms_auth ADD INDEX `idx_datetime` (`sa_datetime`)"
        ),
        array(
            'table' => 'g5_sms_auth',
            'index' => 'idx_expire',
            'query' => "ALTER TABLE g5_sms_auth ADD INDEX `idx_expire` (`sa_expire_datetime`)"
        ),
        array(
            'table' => 'g5_sms_limit',
            'index' => 'idx_block',
            'query' => "ALTER TABLE g5_sms_limit ADD INDEX `idx_block` (`sl_block_until`)"
        )
    );
    
    foreach($index_queries as $index) {
        $sql = "SHOW INDEX FROM {$index['table']} WHERE Key_name = '{$index['index']}'";
        $result = sql_query($sql, false);
        if(!sql_num_rows($result)) {
            if(sql_query($index['query'], false)) {
                echo '<div class="result success">✓ ' . $index['table'] . '.' . $index['index'] . ' 인덱스 추가 완료</div>';
            }
        }
    }
    
    // cf_use_sms 필드가 없으면 추가 (기존 코드)
    $sql = "SHOW COLUMNS FROM g5_sms_config LIKE 'cf_use_sms'";
    $result = sql_query($sql, false);
    if(!sql_num_rows($result)) {
        $sql = "ALTER TABLE g5_sms_config 
                ADD COLUMN `cf_use_sms` tinyint(4) DEFAULT 1 COMMENT 'SMS 사용 여부' AFTER `cf_service`";
        if(sql_query($sql, false)) {
            echo '<div class="result success">✓ cf_use_sms 필드 추가 완료</div>';
        } else {
            echo '<div class="result error">✗ cf_use_sms 필드 추가 실패</div>';
        }
    } else {
        echo '<div class="result info">ⓘ cf_use_sms 필드가 이미 존재합니다</div>';
    }
    
    // ===================================
    // 3. 파일 존재 확인
    // ===================================
    echo '<h4>3. 필수 파일 확인</h4>';
    
    $required_files = array(
        '/extend/sms_config.php' => 'SMS 설정 라이브러리',
        '/plugin/sms/aligo.php' => '알리고 API (선택)',
        '/bbs/sms_send.php' => 'SMS 발송 처리',
        '/bbs/sms_verify.php' => 'SMS 인증 처리',
        '/adm/sms_config.php' => '관리자 설정',
        '/adm/sms_config_update.php' => '설정 저장',
        '/adm/sms_log.php' => '발송 로그',
        '/adm/sms_blacklist.php' => '차단번호 관리',
        '/adm/sms_log_export.php' => '로그 내보내기 (신규)',
        '/adm/sms_log_stats.php' => '발송 통계 (신규)'
    );
    
    $file_errors = array();
    foreach($required_files as $file => $desc) {
        $optional = strpos($desc, '(선택)') !== false;
        $new_file = strpos($desc, '(신규)') !== false;
        
        if(file_exists(G5_PATH.$file)) {
            if($new_file) {
                echo '<div class="result success">✓ ' . $desc . ' (' . $file . ') - <strong>신규 파일</strong></div>';
            } else {
                echo '<div class="result success">✓ ' . $desc . ' (' . $file . ')</div>';
            }
        } else {
            if($optional) {
                echo '<div class="result info">ⓘ ' . $desc . ' (' . $file . ') - 선택 파일</div>';
            } else if($new_file) {
                echo '<div class="result info">ⓘ ' . $desc . ' (' . $file . ') - 신규 파일 (선택)</div>';
            } else {
                echo '<div class="result error">✗ ' . $desc . ' (' . $file . ') - 파일이 없습니다</div>';
                $file_errors[] = $file;
            }
        }
    }
    
    // ===================================
    // 4. 디렉토리 권한 확인
    // ===================================
    echo '<h4>4. 디렉토리 권한 확인</h4>';
    
    $check_dirs = array(
        '/extend/' => '확장 디렉토리',
        '/plugin/sms/' => 'SMS 플러그인 디렉토리'
    );
    
    foreach($check_dirs as $dir => $desc) {
        if(is_dir(G5_PATH.$dir)) {
            if(is_writable(G5_PATH.$dir)) {
                echo '<div class="result success">✓ ' . $desc . ' 쓰기 가능</div>';
            } else {
                echo '<div class="result info">ⓘ ' . $desc . ' 쓰기 권한 없음 (설치에는 문제없음)</div>';
            }
        } else {
            echo '<div class="result info">ⓘ ' . $desc . ' 디렉토리 없음</div>';
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
        
        if(count($updates) > 0) {
            echo '<div class="update-notice">';
            echo '<h4>새로 추가된 기능:</h4>';
            echo '<ul>';
            echo '<li>상세 로그 수집 (API 응답, 에러코드, 재시도 등)</li>';
            echo '<li>발송 통계 및 분석 기능</li>';
            echo '<li>로그 Excel 내보내기</li>';
            echo '<li>차단 시간 설정 기능</li>';
            echo '</ul>';
            echo '</div>';
        }
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
    if(count($errors) == 0) {
        echo '<a href="'.G5_ADMIN_URL.'/sms_config.php" class="btn btn-primary">SMS 설정하기</a>';
        echo '<a href="'.G5_ADMIN_URL.'/sms_log.php" class="btn">발송 로그</a>';
    } else {
        echo '<a href="'.$_SERVER['PHP_SELF'].'" class="btn">다시 확인</a>';
    }
    echo '<a href="'.G5_ADMIN_URL.'" class="btn">관리자 메인</a>';
    echo '</div>';
    
} else {
    // 설치 전 안내
    ?>
    <div class="install-step">
        <h3>설치 전 확인사항</h3>
        <ul>
            <li>모든 파일이 정확한 위치에 업로드되었는지 확인하세요.</li>
            <li>데이터베이스 백업을 권장합니다.</li>
            <li>PHP 5.6 이상 버전이 필요합니다. (현재: <?php echo PHP_VERSION; ?>)</li>
        </ul>
    </div>
    
    <div class="install-step">
        <h3>설치될 항목</h3>
        <ul>
            <li>데이터베이스 테이블 5개 생성</li>
            <li>관리자 메뉴 추가</li>
            <li>SMS 인증 기능 활성화</li>
            <li class="text-primary"><strong>[신규] 상세 로그 수집 기능</strong></li>
            <li class="text-primary"><strong>[신규] 발송 통계 분석</strong></li>
        </ul>
    </div>
    
    <div class="btn-area">
        <a href="?step=install" class="btn btn-primary" onclick="return confirm('SMS 인증 시스템을 설치하시겠습니까?');">설치 시작</a>
        <a href="<?php echo G5_ADMIN_URL; ?>" class="btn">취소</a>
    </div>
    <?php
}
?>
</div>

<?php
include_once('./admin.tail.php');
?>