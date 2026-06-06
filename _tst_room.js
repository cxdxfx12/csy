const { Client } = require('ssh2');
const c = new Client();
c.on('ready', () => {
  // Test the actual API endpoint with different community_id values
  c.exec('cd /www/wwwroot/www.hbdxm.com && php -r \'$_GET["community_id"]=1; $_SERVER["REQUEST_METHOD"]="GET"; require "public/index.php";\' 2>&1 | head -5', (e, s) => {
    let o = ''; s.stderr.on('data', d => o += d); s.on('data', d => o += d);
    s.on('close', () => { 
      console.log('=== community_id=1 ===');
      console.log(o.substring(0, 500));
      
      // Check DB directly
      c.exec('mysql -uroot -pcxdxfx12 dasheng -e "SELECT id, room_number, community_id, building_name FROM ds_room WHERE delete_time IS NULL ORDER BY community_id LIMIT 20"', (e2, s2) => {
        let o2 = ''; s2.stderr.on('data', d => o2 += d); s2.on('data', d => o2 += d);
        s2.on('close', () => {
          console.log('\n=== DB rooms ===');
          console.log(o2);
          c.end();
          setTimeout(() => process.exit(0), 300);
        });
      });
    });
  });
}).connect({ host:'211.149.181.178', port:22000, username:'root', password:'cxdxfx12', readyTimeout:10000 });
