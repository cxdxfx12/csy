const { Client } = require('ssh2');
const c = new Client();
c.on('ready', () => {
  // Test the real API endpoint with curl  
  c.exec(`curl -s 'http://127.0.0.1/api/admin/room/select?community_id=9' -H 'Authorization: Bearer $(mysql -uroot -pcxdxfx12 dasheng -N -B -e "SELECT token FROM ds_admin WHERE id=1 LIMIT 1")' 2>/dev/null | head -c 300`, (e, s) => {
    let o = ''; s.stderr.on('data', d => o += d); s.on('data', d => o += d);
    s.on('close', () => {
      console.log('=== community_id=9 ===');
      console.log(o);
      
      c.exec(`curl -s 'http://127.0.0.1/api/admin/room/select?community_id=8' -H 'Authorization: Bearer $(mysql -uroot -pcxdxfx12 dasheng -N -B -e "SELECT token FROM ds_admin WHERE id=1 LIMIT 1")' 2>/dev/null | head -c 300`, (e2, s2) => {
        let o2 = ''; s2.stderr.on('data', d => o2 += d); s2.on('data', d => o2 += d);
        s2.on('close', () => {
          console.log('\n=== community_id=8 ===');
          console.log(o2);
          c.end();
          setTimeout(() => process.exit(0), 300);
        });
      });
    });
  });
}).connect({ host:'211.149.181.178', port:22000, username:'root', password:'cxdxfx12', readyTimeout:10000 });
