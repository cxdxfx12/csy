const { Client } = require('ssh2');
const c = new Client();
c.on('ready', () => {
  // 检查ThinkPHP运行时错误日志
  c.exec('find /www/wwwroot/www.hbdxm.com -name "*.log" -path "*/runtime/*" -mmin -30 2>/dev/null | head -5', (e, s) => {
    let out = '';
    s.on('data', d => out += d);
    s.on('close', () => {
      console.log('Log files:', out || 'none found');
      
      // 用curl测试API
      c.exec(`curl -s -X POST 'https://www.hbdxm.com/api/admin/decoration/applyAdd' \
        -H 'Content-Type: application/x-www-form-urlencoded' \
        -H 'Cookie: admin_token=$(cat /www/wwwroot/www.hbdxm.com/runtime/session/* 2>/dev/null | head -c 50 || echo "")' \
        -d 'community_id=1&room_id=101&owner_id=16&company_name=test&leader_name=test&leader_phone=13800138000&start_date=2026-06-05&end_date=2026-08-19&content=test&deposit_amount=3000&manage_fee=500&trash_fee=100&other_fee=50&id=0' 2>&1 | head -5`, (e2, s2) => {
          let out2 = '';
          s2.on('data', d => out2 += d);
          s2.on('close', () => console.log('Curl result:', out2));
          
          // 检查nginx error log
          c.exec("tail -5 /www/wwwlogs/hbdxm.com.error.log 2>/dev/null || echo 'no nginx err'", (e3, s3) => {
            let out3 = '';
            s3.on('data', d => out3 += d);
            s3.on('close', () => { console.log('Nginx err:', out3); c.end(); });
          });
        });
    });
  });
});
c.connect({ host: '211.149.181.178', port: 22000, username: 'root', password: 'cxdxfx12' });
