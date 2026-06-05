// 测试 owner claim API 全链路
const https = require('https');

function request(method, path, opts = {}) {
  return new Promise((resolve, reject) => {
    const url = new URL(path, 'https://www.hbdxm.com');
    const options = {
      hostname: url.hostname,
      port: 443,
      path: url.pathname + url.search,
      method,
      headers: { 'Content-Type': 'application/json', ...opts.headers },
      rejectUnauthorized: false,
    };
    const req = https.request(options, (res) => {
      let body = '';
      res.on('data', d => body += d);
      res.on('end', () => {
        console.log(`=== ${method} ${path} ===`);
        console.log(`HTTP ${res.statusCode} ${res.statusMessage}`);
        console.log(`Headers:`, JSON.stringify(res.headers));
        console.log(`Body:`, body);
        console.log('');
        try {
          resolve({ status: res.statusCode, headers: res.headers, data: JSON.parse(body) });
        } catch (e) {
          resolve({ status: res.statusCode, headers: res.headers, data: body });
        }
      });
    });
    req.on('error', (e) => { console.error('Request error:', e.message); reject(e); });
    if (opts.body) req.write(opts.body);
    req.end();
  });
}

async function main() {
  console.log('========== 1. 无Token测试claimProperty ==========');
  await request('POST', '/index.php/api/claimProperty', {
    body: JSON.stringify({ phone: '19171045360' }),
  });

  console.log('========== 2. 空Token测试claimProperty ==========');
  await request('POST', '/index.php/api/claimProperty', {
    body: JSON.stringify({ phone: '19171045360' }),
    headers: { 'Authorization': 'Bearer ' },
  });

  console.log('========== 3. 测试OPTIONS预检请求 ==========');
  await request('OPTIONS', '/index.php/api/claimProperty', {
    headers: {
      'Origin': 'https://www.hbdxm.com',
      'Access-Control-Request-Method': 'POST',
      'Access-Control-Request-Headers': 'content-type,authorization',
    },
  });

  console.log('========== 4. 无效Token测试claimProperty ==========');
  await request('POST', '/index.php/api/claimProperty', {
    body: JSON.stringify({ phone: '19171045360' }),
    headers: { 'Authorization': 'Bearer invalid_token_12345' },
  });

  console.log('========== 5. 测试badge/counts（对比正常API） ==========');
  await request('GET', '/index.php/api/badge/counts', {
    headers: { 'Authorization': 'Bearer invalid_token_12345' },
  });

  console.log('========== 6. 直接GET测试claimProperty（可能的路由问题） ==========');
  await request('GET', '/index.php/api/claimProperty');
}

main().catch(console.error);
