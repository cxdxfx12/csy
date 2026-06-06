const { Client } = require('ssh2');
const c = new Client();
c.on('ready', () => {
  // 检查编译后的submitForm逻辑
  c.exec(`grep -o 'applyAdd\\|applyEdit\\|Number(.*deposit' /www/wwwroot/www.hbdxm.com/public/admin/assets/DecorationApply-CvxgRv56.js | head -10`, (e, s) => {
    let out = '';
    s.on('data', d => out += d);
    s.on('close', () => console.log('JS checks:', out || 'NOT FOUND'));
    
    // 测试API是否能通
    c.exec(`cd /www/wwwroot/www.hbdxm.com && php -r "
require 'think/start.php';
echo 'PHP OK';
" 2>&1 | head -3`, (e2, s2) => {
      let out2 = '';
      s2.on('data', d => out2 += d);
      s2.on('close', () => console.log('PHP test:', out2));
      
      // 查看最近的PHP错误日志
      c.exec('tail -20 /www/wwwlogs/php_error.log 2>/dev/null || tail -20 /var/log/php-fpm/error.log 2>/dev/null || echo "no php log found"', (e3, s3) => {
        let out3 = '';
        s3.on('data', d => out3 += d);
        s3.on('close', () => { console.log('PHP log:', out3); c.end(); });
      });
    });
  });
});
c.connect({ host: '211.149.181.178', port: 22000, username: 'root', password: 'cxdxfx12' });
