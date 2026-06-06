const { Client } = require('ssh2');

const conn = new Client();
conn.on('ready', () => {
  console.log('SSH connected\n');
  const cmd = `grep -i 'DecorationApply' /www/wwwroot/www.hbdxm.com/public/admin/index.html`;
  conn.exec(cmd, (err, stream) => {
    if (err) { console.error('Exec err:', err.message); conn.end(); return; }
    let out = '';
    stream.on('data', d => out += d.toString());
    stream.stderr.on('data', d => process.stderr.write(d));
    stream.on('close', () => {
      console.log('Loaded:', out || 'Not found in index.html (可能是动态加载)');
      // 也查 main 或入口 JS 引用
      const cmd2 = `grep -o 'assets/[^\"]*\\.js' /www/wwwroot/www.hbdxm.com/public/admin/index.html | head -5`;
      conn.exec(cmd2, (err2, stream2) => {
        let out2 = '';
        stream2.on('data', d => out2 += d.toString());
        stream2.on('close', () => { console.log('JS refs:', out2); conn.end(); });
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
