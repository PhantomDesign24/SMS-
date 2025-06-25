/*
 * 파일명: bot.js
 * 위치: /bot/bot.js
 * 기능: 디스코드 실시간 메시지 감지 및 웹 전송
 * 작성일: 2025-06-25
 */

const { Client, GatewayIntentBits } = require('discord.js');
const axios = require('axios');
const config = require('./config.json');

const client = new Client({
    intents: [GatewayIntentBits.Guilds, GatewayIntentBits.GuildMessages, GatewayIntentBits.MessageContent]
});

client.on('ready', () => {
    console.log(`봇 로그인 완료: ${client.user.tag}`);
});

client.on('messageCreate', (message) => {
    if (message.author.bot) return;

    axios.post(config.phpApiUrl, {
        nickname: message.author.username,
        message: message.content
    }).catch(console.error);
});

client.login(config.token);
