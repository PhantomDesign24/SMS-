<!DOCTYPE html>
<html>
<head>
    <title>채팅방</title>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
</head>
<body>
    <h1>실시간 채팅방</h1>
    <div id="chat-box" style="border:1px solid #ddd; height:300px; overflow:auto;"></div>
    <input type="text" id="nickname" placeholder="닉네임">
    <input type="text" id="message" placeholder="메시지">
    <button id="send">전송</button>

    <script>
    function loadChat() {
        $.get('list.php', function(data) {
            let html = '';
            JSON.parse(data).forEach(function(row) {
                html += `<div>[${row.source}] <b>${row.nickname}</b>: ${row.message}</div>`;
            });
            $('#chat-box').html(html).scrollTop(99999);
        });
    }

    $('#send').on('click', function() {
        $.post('send.php', {
            nickname: $('#nickname').val(),
            message: $('#message').val()
        }, function() {
            $('#message').val('');
            loadChat();
        });
    });

    setInterval(loadChat, 3000);
    loadChat();
    </script>
</body>
</html>
