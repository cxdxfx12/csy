const { Client } = require('ssh2');

const conn = new Client();
conn.on('ready', () => {
  console.log('SSH connected\n');
  // 提取 DecorationApply-BCsCY-bG.js 中 room/select 相关的完整片段
  const cmd = `grep -oP '.{0,150}room/select.{0,200}' /www/wwwroot/www.hbdxm.com/public/admin/assets/DecorationApply-BCsCY-bG.js`;
  conn.exec(cmd, (err, stream) => {
    if (err) { console.error('Exec err:', err.message); conn.end(); return; }
    let out = '';
    stream.on('data', d => out += d.toString());
    stream.on('close', () => {
      console.log('room/select context:', out);
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
