const { Client } = require('ssh2');

const conn = new Client();
conn.on('ready', () => {
  console.log('SSH connected\n');
  // 查看 DecorationApply JS 内容（搜索 room, select, load, community）
  const cmd = `grep -o '.room\\|select\\|loadRooms\\|load\\|watch\\|community' /www/wwwroot/www.hbdxm.com/public/admin/assets/DecorationApply-BCsCY-bG.js | sort | uniq -c | sort -rn | head -20`;
  conn.exec(cmd, (err, stream) => {
    if (err) { console.error('Exec err:', err.message); conn.end(); return; }
    let out = '';
    stream.on('data', d => out += d.toString());
    stream.stderr.on('data', d => process.stderr.write(d));
    stream.on('close', () => {
      console.log('Keyword counts:', out);
      // 看文件前 500 字符
      const cmd2 = `head -c 500 /www/wwwroot/www.hbdxm.com/public/admin/assets/DecorationApply-BCsCY-bG.js`;
      conn.exec(cmd2, (err2, stream2) => {
        let out2 = '';
        stream2.on('data', d => out2 += d.toString());
        stream2.on('close', () => { console.log('\nFirst 500 chars:', out2); conn.end(); });
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
