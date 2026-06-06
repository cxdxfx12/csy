const { Client } = require('ssh2');

const conn = new Client();
conn.on('ready', () => {
  console.log('SSH connected\n');
  // 检查 index-DGDBRA5M.js 中所有 DecorationApply 引用
  const cmd = `grep -o 'DecorationApply[^\"]*\"' /www/wwwroot/www.hbdxm.com/public/admin/assets/index-DGDBRA5M.js | sort -u`;
  conn.exec(cmd, (err, stream) => {
    if (err) { console.error('Exec err:', err.message); conn.end(); return; }
    let out = '';
    stream.on('data', d => out += d.toString());
    stream.stderr.on('data', d => process.stderr.write(d));
    stream.on('close', () => {
      console.log('All DecorationApply refs:', out);
      // 也检查是否有 .js 结尾的引用
      const cmd2 = `grep -oP 'DecorationApply[^\",]*' /www/wwwroot/www.hbdxm.com/public/admin/assets/index-DGDBRA5M.js | sort -u`;
      conn.exec(cmd2, (err2, stream2) => {
        let out2 = '';
        stream2.on('data', d => out2 += d.toString());
        stream2.on('close', () => {
          console.log('All DecorationApply refs (extended):', out2);
          conn.end(); 
        });
      });
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
