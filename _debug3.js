const { Client } = require('ssh2');
const c = new Client();
c.on('ready', () => {
  // 查找runtime目录和日志
  c.exec('find /www/wwwroot/www.hbdxm.com/runtime -type f -name "*.log" 2>/dev/null | head -10; echo "---"; ls /www/wwwroot/www.hbdxm.com/runtime/log/ 2>/dev/null || echo "no log dir"', (e, s) => {
    let out = '';
    s.on('data', d => out += d);
    s.on('close', () => console.log('Runtime:', out));
    
    // 简单curl测试
    c.exec("curl -sv 'https://www.hbdxm.com/api/admin/decoration/applyList?page=1&limit=1' -H 'Cookie: admin_token=test' 2>&1 | tail -15", (e2, s2) => {
      let out2 = '';
      s2.on('data', d => out2 += d);
      s2.on('close', () => { console.log('Curl:', out2); c.end(); });
    });
  });
});
c.connect({ host: '211.149.181.178', port: 22000, username: 'root', password: 'cxdxfx12' });
