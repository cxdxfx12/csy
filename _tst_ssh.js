const { Client } = require('ssh2');

const conn = new Client();
conn.on('ready', () => {
  console.log('SSH connected\n');
  // 测试 room/select API（服务器本地 curl，绕开验证码）
  const cmd = `curl -s 'http://127.0.0.1/api/admin/room/select?community_id=8' | head -c 500 && echo '' && echo '---' && curl -s 'http://127.0.0.1/api/admin/room/select?community_id=9' | head -c 500`;
  conn.exec(cmd, (err, stream) => {
    if (err) { console.error('Exec err:', err.message); conn.end(); return; }
    let out = '';
    stream.on('data', d => out += d.toString());
    stream.stderr.on('data', d => process.stderr.write(d));
    stream.on('close', () => {
      console.log(out);
      conn.end();
    });
  });
}).on('error', e => console.error('SSH err:', e.message))
  .connect({
    host: '211.149.181.178',
    port: 22000,
    username: 'root',
    password: 'cxdxfx12',
    readyTimeout: 10000,
  });
