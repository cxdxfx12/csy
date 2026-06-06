const { Client } = require('ssh2');

const conn = new Client();
conn.on('ready', () => {
  console.log('SSH connected\n');
  const cmd = `curl -s -H 'Host: www.hbdxm.com' 'http://127.0.0.1/api/admin/room/select?community_id=8' | python3 -m json.tool 2>/dev/null | head -30 && echo '===CID8_END===' && curl -s -H 'Host: www.hbdxm.com' 'http://127.0.0.1/api/admin/room/select?community_id=9' | python3 -m json.tool 2>/dev/null | head -30`;
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
