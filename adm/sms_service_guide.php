<?php
/*
 * 파일명: sms_service_guide.php
 * 위치: /adm/sms_service_guide.php
 * 기능: SMS 서비스 가입 및 설정 가이드
 * 작성일: 2024-12-28
 */

include_once('./_common.php');

// 관리자만 접근 가능
if(!$is_admin) {
    alert('관리자만 접근 가능합니다.', G5_URL);
}

$g5['title'] = 'SMS 서비스 가입 가이드';
include_once(G5_PATH.'/head.sub.php');
?>

<style>
body {
    font-family: 'Malgun Gothic', sans-serif;
    line-height: 1.6;
    background-color: #f5f5f5;
    padding: 20px;
}
.guide-container {
    max-width: 1000px;
    margin: 0 auto;
    background: white;
    padding: 30px;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}
.guide-header {
    text-align: center;
    margin-bottom: 30px;
}
.guide-header h1 {
    color: #333;
    border-bottom: 3px solid #007cba;
    padding-bottom: 10px;
    display: inline-block;
}
.service-section {
    margin: 30px 0;
    padding: 20px;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    background-color: #fafafa;
}
.service-section h2 {
    color: #007cba;
    margin-bottom: 20px;
}
.step-box {
    background-color: #e7f3ff;
    padding: 15px;
    margin: 10px 0;
    border-left: 4px solid #007cba;
    border-radius: 4px;
}
.step-number {
    display: inline-block;
    width: 30px;
    height: 30px;
    background-color: #007cba;
    color: white;
    text-align: center;
    line-height: 30px;
    border-radius: 50%;
    margin-right: 10px;
    font-weight: bold;
}
.alert-box {
    padding: 15px;
    margin: 15px 0;
    border-radius: 5px;
}
.alert-warning {
    background-color: #fff3cd;
    border: 1px solid #ffeaa7;
    color: #856404;
}
.alert-info {
    background-color: #d1ecf1;
    border: 1px solid #bee5eb;
    color: #0c5460;
}
.alert-success {
    background-color: #d4edda;
    border: 1px solid #c3e6cb;
    color: #155724;
}
.price-table {
    width: 100%;
    margin: 15px 0;
    border-collapse: collapse;
}
.price-table th,
.price-table td {
    border: 1px solid #ddd;
    padding: 10px;
    text-align: left;
}
.price-table th {
    background-color: #f2f2f2;
    font-weight: bold;
}
.btn-guide {
    display: inline-block;
    padding: 10px 20px;
    background-color: #007cba;
    color: white;
    text-decoration: none;
    border-radius: 5px;
    margin: 5px;
}
.btn-guide:hover {
    background-color: #005a87;
}
</style>

<div class="guide-container">
    <div class="guide-header">
        <h1>SMS 서비스 가입 및 설정 가이드</h1>
    </div>
    
    <div class="alert-box alert-info">
        <strong>📌 시작하기 전에</strong><br>
        SMS 인증 서비스를 사용하려면 아이코드 또는 알리고 중 하나를 선택하여 가입하고,<br>
        발신번호를 사전등록해야 합니다. (법적 의무사항)
    </div>

    <!-- 아이코드 섹션 -->
    <div class="service-section">
        <h2>1. 아이코드 (iCode) 서비스</h2>
        
        <h3>가입 절차</h3>
        <div class="step-box">
            <span class="step-number">1</span>
            <strong>홈페이지 접속</strong><br>
            <a href="https://www.icodekorea.com" target="_blank" class="btn-guide">아이코드 바로가기</a>
        </div>
        
        <div class="step-box">
            <span class="step-number">2</span>
            <strong>회원가입</strong><br>
            필수정보: 아이디, 비밀번호, 회사명, 담당자명, 연락처, 이메일
        </div>
        
        <div class="step-box">
            <span class="step-number">3</span>
            <strong>발신번호 등록</strong><br>
            마이페이지 → 발신번호 관리 → 번호 등록 및 인증
        </div>
        
        <div class="step-box">
            <span class="step-number">4</span>
            <strong>충전하기</strong><br>
            최소 충전금액: 10,000원
        </div>
        
        <h3>요금 안내</h3>
        <table class="price-table">
            <tr>
                <th>서비스</th>
                <th>단가</th>
                <th>설명</th>
            </tr>
            <tr>
                <td>SMS (단문)</td>
                <td>약 16~20원</td>
                <td>90바이트 이하</td>
            </tr>
            <tr>
                <td>LMS (장문)</td>
                <td>약 50~60원</td>
                <td>2,000바이트 이하</td>
            </tr>
            <tr>
                <td>MMS (그림)</td>
                <td>약 100~200원</td>
                <td>이미지 첨부</td>
            </tr>
        </table>
        
        <div class="alert-box alert-success">
            💡 <strong>Tip:</strong> 아이코드는 그누보드5 기본 설정과 연동됩니다.<br>
            관리자 → 환경설정 → 기본환경설정에서도 설정 가능합니다.
        </div>
    </div>

    <!-- 알리고 섹션 -->
    <div class="service-section">
        <h2>2. 알리고 (Aligo) 서비스</h2>
        
        <h3>가입 절차</h3>
        <div class="step-box">
            <span class="step-number">1</span>
            <strong>홈페이지 접속</strong><br>
            <a href="https://smartsms.aligo.in" target="_blank" class="btn-guide">알리고 바로가기</a>
        </div>
        
        <div class="step-box">
            <span class="step-number">2</span>
            <strong>회원가입</strong><br>
            필수정보: 아이디, 비밀번호, 업체명, 대표자명, 휴대폰(인증), 이메일
        </div>
        
        <div class="step-box">
            <span class="step-number">3</span>
            <strong>발신번호 등록</strong><br>
            발신번호 관리 → 번호 등록 → ARS 인증
        </div>
        
        <div class="step-box">
            <span class="step-number">4</span>
            <strong>API Key 발급</strong><br>
            설정 → API Key 관리 → API Key 발급<br>
            <span style="color: red;">※ API Key는 한 번만 표시되므로 반드시 저장!</span>
        </div>
        
        <div class="step-box">
            <span class="step-number">5</span>
            <strong>충전하기</strong><br>
            충전/정산 → 충전하기 → 금액 입력
        </div>
        
        <h3>요금 안내</h3>
        <table class="price-table">
            <tr>
                <th>서비스</th>
                <th>단가</th>
                <th>설명</th>
            </tr>
            <tr>
                <td>SMS (단문)</td>
                <td>약 15~18원</td>
                <td>90바이트 이하</td>
            </tr>
            <tr>
                <td>LMS (장문)</td>
                <td>약 45~55원</td>
                <td>2,000바이트 이하</td>
            </tr>
            <tr>
                <td>MMS (그림)</td>
                <td>약 100~150원</td>
                <td>이미지 첨부</td>
            </tr>
        </table>
        
        <div class="alert-box alert-info">
            💡 <strong>알리고 API 정보:</strong><br>
            • API Key: 32자리 인증키<br>
            • User ID: 알리고 로그인 아이디
        </div>
    </div>

    <!-- 설정 방법 -->
    <div class="service-section">
        <h2>3. 그누보드 설정 방법</h2>
        
        <div class="step-box">
            <span class="step-number">1</span>
            <strong>SMS 설정 페이지 접속</strong><br>
            관리자 → SMS관리 → SMS설정
        </div>
        
        <div class="step-box">
            <span class="step-number">2</span>
            <strong>서비스 선택</strong><br>
            아이코드 또는 알리고 중 선택
        </div>
        
        <div class="step-box">
            <span class="step-number">3</span>
            <strong>인증 정보 입력</strong><br>
            <table class="price-table">
                <tr>
                    <th>서비스</th>
                    <th>필요 정보</th>
                </tr>
                <tr>
                    <td>아이코드</td>
                    <td>아이디, 비밀번호, 발신번호</td>
                </tr>
                <tr>
                    <td>알리고</td>
                    <td>API Key, User ID, 발신번호</td>
                </tr>
            </table>
        </div>
        
        <div class="step-box">
            <span class="step-number">4</span>
            <strong>기타 설정</strong><br>
            • 일일 발송 제한: 5회 (권장)<br>
            • 재발송 대기시간: 60초<br>
            • 인증번호 유효시간: 180초
        </div>
    </div>

    <!-- 문의처 -->
    <div class="service-section">
        <h2>4. 고객센터 안내</h2>
        
        <table class="price-table">
            <tr>
                <th>서비스</th>
                <th>전화번호</th>
                <th>운영시간</th>
            </tr>
            <tr>
                <td>아이코드</td>
                <td>1544-5680</td>
                <td>평일 09:00~18:00</td>
            </tr>
            <tr>
                <td>알리고</td>
                <td>02-547-8806</td>
                <td>평일 09:30~18:30</td>
            </tr>
        </table>
    </div>
    
    <div style="text-align: center; margin-top: 30px;">
        <button onclick="window.close();" class="btn-guide">창 닫기</button>
    </div>
</div>

<?php
include_once(G5_PATH.'/tail.sub.php');
?>