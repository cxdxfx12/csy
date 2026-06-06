const net = require('net');
const { execSync } = require('child_process');

const HOST = '211.149.181.178';
const PORT = 22000;

// TCP 连接测试
console.log(`尝试连接 ${HOST}:${PORT}...`);
const client = net.createConnection({ host: HOST, port: PORT, timeout: 10000 }, () => {
    console.log('[OK] TCP 连接成功');
});

let data = '';
client.on('data', (chunk) => {
    data += chunk.toString();
    console.log('SSH Banner:', data.trim());
    client.end();
});

client.on('error', (err) => {
    console.log('连接失败:', err.message);
});

client.on('close', () => {
    console.log('连接关闭');
});

setTimeout(() => {
    console.log('超时');
    client.destroy();
    process.exit(0);
}, 12000);
