const { Client } = require('ssh2');
const c = new Client();
c.on('ready', () => {
  // Get a valid token first
  c.exec('mysql -uroot -pcxdxfx12 dasheng -N -B -e "SELECT token FROM ds_admin WHERE id=1 LIMIT 1"', (e, s) => {
    let token = ''; s.on('data', d => token += d.toString().trim());
    s.on('close', () => {
      console.log('Token:', token.substring(0, 30) + '...');
      
      // Test with valid token
      c.exec(`curl -s 'http://127.0.0.1/api/admin/room/select?community_id=9' -H 'Authorization: Bearer ${token}' 2>/dev/null | head -c 500`, (e2, s2) => {
        let o2 = ''; s2.stderr.on('data', d => o2 += d); s2.on('data', d => o2 += d);
        s2.on('close', () => {
          console.log('=== community_id=9 ===');
          console.log(o2);

          c.exec(`curl -s 'http://127.0.0.1/api/admin/room/select?community_id=10' -H 'Authorization: Bearer ${token}' 2>/dev/null | head -c 300`, (e3, s3) => {
            let o3 = ''; s3.stderr.on('data', d => o3 += d); s3.on('data', d => o3 += d);
            s3.on('close', () => {
              console.log('=== community_id=10 ===');
              console.log(o3);
              c.end();
              setTimeout(() => process.exit(0), 300);
            });
          });
        });
      });
    });
  });
}).connect({ host:'211.149.181.178', port:22000, username:'root', password:'cxdxfx12', readyTimeout:10000 });
