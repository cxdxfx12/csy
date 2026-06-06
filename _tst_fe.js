const { Client } = require('ssh2');

const conn = new Client();
conn.on('ready', () => {
  console.log('SSH connected\n');
  // 找到 DecorationApply 的 JS 文件并在其中搜索 apiGet 和 community_id
  const cmd = `ls -t /www/wwwroot/www.hbdxm.com/public/admin/assets/DecorationApply*.js 2>/dev/null && echo '===FILE===' && grep -o 'apiGet.*room/select.*community_id[^)]*' /www/wwwroot/www.hbdxm.com/public/admin/assets/DecorationApply*.js 2>/dev/null | head -10`;
  conn.exec(cmd, (err, stream) => {
    if (err) { console.error('Exec err:', err.message); conn.end(); return; }
    let out = '';
    stream.on('data', d => out += d.toString());
    stream.stderr.on('data', d => process.stderr.write(d));
    stream.on('close', () => { console.log(out); conn.end(); });
  });
}).on('error', e => console.error('SSH err:', e.message))
  .connect({
    host: '211.149.181.178',
    port: 22000,
    username: 'root',
    password: 'cxdxfx12',
    readyTimeout: 10000,
  });
