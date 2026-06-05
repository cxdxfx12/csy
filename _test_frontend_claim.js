// 模拟前端完整流程：获取OAuth链接 → 模拟微信回调 → 获取token → 测试claim
const https = require('https');

function request(method, path, opts = {}) {
  return new Promise((resolve, reject) => {
    const url = new URL(path, 'https://www.hbdxm.com');
    const options = {
      hostname: url.hostname, port: 443,
      path: url.pathname + url.search, method,
      headers: { 'Content-Type': 'application/json', ...opts.headers },
      rejectUnauthorized: false,
    };
    const req = https.request(options, (res) => {
      let body = '';
      res.on('data', d => body += d);
      res.on('end', () => {
        try { resolve({ status: res.statusCode, headers: res.headers, data: JSON.parse(body) }); }
        catch (e) { resolve({ status: res.statusCode, headers: res.headers, data: body }); }
      });
    });
    req.on('error', (e) => { console.error('Error:', e.message); reject(e); });
    if (opts.body) req.write(opts.body);
    req.end();
  });
}

async function main() {
  console.log('=== 步骤1: 获取OAuth入口链接 ===');
  const oauth = await request('GET', '/index.php/api/wechatOAuth?json=1&redirect=/owner.html%23/login&community_id=12');
  console.log('OAuth JSON:', JSON.stringify(oauth.data));
  
  console.log('\n=== 步骤2: 查看是否有可用的已登录用户（通过badge/counts API测试） ===');
  // 无法获取有效token，我们无法完整测试
  // 但我们可以验证无token和有token的错误响应
  
  console.log('\n=== 步骤3: 验证claimProperty的Content-Type ===');
  const r1 = await request('POST', '/index.php/api/claimProperty', {
    body: JSON.stringify({ phone: '19171045360' }),
  });
  console.log('Status:', r1.status);
  console.log('Content-Type:', r1.headers['content-type']);
  console.log('Data type:', typeof r1.data);
  console.log('Data:', JSON.stringify(r1.data));

  console.log('\n=== 步骤4: 检查是否有HTTP到HTTPS重定向 ===');
  const http = require('http');
  await new Promise((resolve) => {
    const req = http.get('http://www.hbdxm.com/index.php/api/claimProperty', (res) => {
      console.log('HTTP request status:', res.statusCode);
      console.log('Location:', res.headers.location || 'none');
      resolve();
    });
    req.on('error', (e) => { console.log('HTTP error:', e.message); resolve(); });
  });

  console.log('\n=== 步骤5: 检查JS文件是否存在 ===');
  const jsCheck = await request('GET', '/assets/owner-Dch8eONu.js', { headers: { 'Content-Type': 'text/plain' } });
  console.log('JS file status:', jsCheck.status);
  console.log('JS file size:', typeof jsCheck.data === 'string' ? jsCheck.data.length : 'N/A');
  if (typeof jsCheck.data === 'string') {
    // 搜索claimProperty关键词
    const hasClaim = jsCheck.data.includes('claimProperty');
    const hasNetworkError = jsCheck.data.includes('网络请求失败');
    const hasPhone = jsCheck.data.includes('19171045360');
    console.log('Contains claimProperty:', hasClaim);
    console.log('Contains 网络请求失败:', hasNetworkError);
    // 搜索API baseURL
    const hasApiApi = jsCheck.data.includes('/api/api');
    console.log('Contains /api/api:', hasApiApi);
    // 搜索createApi函数
    const hasCreateApi = jsCheck.data.includes('createApi');
    console.log('Contains createApi:', hasCreateApi);
  }
}

main().catch(console.error);
