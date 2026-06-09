const http = require('http');

// 先模拟登录获取cookie
const postData = JSON.stringify({ username: 'admin', password: 'cxdxfx12' });

const loginReq = http.request({
  hostname: '127.0.0.1', port: 80, path: '/admin/login', method: 'POST',
  headers: { 'Content-Type': 'application/json', 'Content-Length': Buffer.byteLength(postData) }
}, (res) => {
  let body = '';
  res.on('data', d => body += d);
  res.on('end', () => {
    console.log('登录响应:', body.substring(0, 200));
    const cookies = res.headers['set-cookie'];
    const cookieStr = cookies ? cookies.join('; ') : '';
    
    // 测试三个API
    const apis = [
      '/admin/equipment/elevatorList?page=1&limit=15',
      '/admin/equipment/elevatorFaultList?page=1&limit=15',
      '/admin/equipment/elevatorInspectionList?page=1&limit=15',
    ];
    
    let apiIdx = 0;
    const testApi = () => {
      if (apiIdx >= apis.length) return;
      const path = apis[apiIdx++];
      http.get({
        hostname: '127.0.0.1', port: 80, path: path,
        headers: cookieStr ? { 'Cookie': cookieStr } : {}
      }, (res2) => {
        let b = '';
        res2.on('data', d => b += d);
        res2.on('end', () => {
          console.log(`\n${path} => ${b.substring(0, 500)}`);
          testApi();
        });
      }).on('error', e => { console.error('Error:', e.message); testApi(); });
    };
    testApi();
  });
});

loginReq.on('error', e => console.error('Login error:', e.message));
loginReq.write(postData);
loginReq.end();
