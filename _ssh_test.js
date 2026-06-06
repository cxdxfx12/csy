const { Client } = require('ssh2');
const conn = new Client();
conn.on('ready', () => { console.log('OK'); conn.end(); });
conn.on('error', (e) => { console.log('ERR:' + e.message); process.exit(1); });
conn.connect({ host: '211.149.181.178', port: 22000, username: 'root', password: 'hbyscycxdxfx12', readyTimeout: 8000, tryKeyboard: true });
