const { Client } = require('ssh2');
const fs = require('fs');

const conn = new Client();
conn.on('ready', () => {
  console.log('SSH connected\n');
  // 先传 Python 脚本到服务器
  const script = fs.readFileSync('e:/ds/_add_debug.py', 'utf8');
  conn.exec('cat > /tmp/patch_room.py << \'PYEOF\'\n' + script + '\nPYEOF', (err, stream) => {
    if (err) { console.error('Upload err:', err.message); conn.end(); return; }
    stream.on('close', (code) => {
      // 执行
      conn.exec('python3 /tmp/patch_room.py', (err2, stream2) => {
        let out = '';
        stream2.on('data', d => out += d.toString());
        stream2.stderr.on('data', d => process.stderr.write(d));
        stream2.on('close', () => {
          console.log(out);
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
