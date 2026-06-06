const { Client } = require('ssh2');

const conn = new Client();
conn.on('ready', () => {
  console.log('SSH connected\n');
  // 检查 index.html 指向哪个入口
  const cmd = `grep 'index-' /www/wwwroot/www.hbdxm.com/public/admin/index.html && echo '===IDX==='
  # 检查两个 DecorationApply 文件大小差异
  wc -c /www/wwwroot/www.hbdxm.com/public/admin/assets/DecorationApply-*.js
  echo '===SIZES==='
  # 搜索 DecorationApply-BCsCY-bG 中关于 room/select 的完整调用链
  grep -oP '.{0,80}room.{0,80}' /www/wwwroot/www.hbdxm.com/public/admin/assets/DecorationApply-BCsCY-bG.js | head -5`;
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
