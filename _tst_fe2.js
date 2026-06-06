const { Client } = require('ssh2');

const conn = new Client();
conn.on('ready', () => {
  console.log('SSH connected\n');
  // 检查服务器上 DecorationApply JS 中是否包含 loadRoomsAndOwners / community_id
  const cmd = `grep -c 'loadRoomsAndOwners\|community_id' /www/wwwroot/www.hbdxm.com/public/admin/assets/DecorationApply-BCsCY-bG.js && echo '===V1_HAS_MATCH===' && grep -c 'loadRoomsAndOwners\|community_id' /www/wwwroot/www.hbdxm.com/public/admin/assets/DecorationApply-DGAfHq_Q.js && echo '===V2_HAS_MATCH==='`;
  conn.exec(cmd, (err, stream) => {
    if (err) { console.error('Exec err:', err.message); conn.end(); return; }
    let out = '';
    stream.on('data', d => out += d.toString());
    stream.stderr.on('data', d => process.stderr.write(d));
    stream.on('close', () => {
      console.log('grep results:', out);
      // Check main index JS to see which chunk it loads
      const cmd2 = `grep -o 'DecorationApply-[A-Za-z0-9_-]*' /www/wwwroot/www.hbdxm.com/public/admin/assets/index-DGDBRA5M.js 2>/dev/null | head -5 && echo '===INDEX===';
      ls -la /www/wwwroot/www.hbdxm.com/public/admin/assets/DecorationApply-*.js`;
      conn.exec(cmd2, (err2, stream2) => {
        let out2 = '';
        stream2.on('data', d => out2 += d.toString());
        stream2.on('close', () => { console.log('\nMain index refs:', out2); conn.end(); });
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
