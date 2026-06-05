// Detailed claim API test
const https = require('https');

function post(url, body, headers = {}) {
  return new Promise((resolve, reject) => {
    const u = new URL(url);
    const data = JSON.stringify(body);
    const opts = {
      hostname: u.hostname, port: 443, path: u.pathname,
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Content-Length': Buffer.byteLength(data), ...headers },
      rejectUnauthorized: false, timeout: 15000
    };
    const req = https.request(opts, (res) => {
      let buf = [];
      res.on('data', c => buf.push(c));
      res.on('end', () => {
        const raw = Buffer.concat(buf).toString('utf8');
        console.log('HTTP', res.statusCode, 'Content-Type:', res.headers['content-type']);
        console.log('Raw body (first 800 chars):', raw.substring(0, 800));
        // Check for non-JSON prefix
        if (raw.trim()[0] !== '{') {
          console.log('WARNING: Response does not start with {');
          console.log('First 100 chars:', raw.substring(0, 100));
        }
        try {
          resolve({ status: res.statusCode, data: JSON.parse(raw) });
        } catch {
          resolve({ status: res.statusCode, raw });
        }
      });
    });
    req.on('error', e => { console.error('NETWORK ERROR:', e.message); reject(e); });
    req.write(data);
    req.end();
  });
}

async function main() {
  // Test 1: No token - should return "请先登录" as valid JSON
  console.log('=== Test 1: claimProperty without token ===');
  await post('https://www.hbdxm.com/index.php/api/claimProperty', { phone: '19171045360' });

  // Test 2: Invalid token - should return "身份验证失败"
  console.log('\n=== Test 2: claimProperty with invalid token ===');
  await post('https://www.hbdxm.com/index.php/api/claimProperty', { phone: '19171045360' }, 
    { Authorization: 'Bearer INVALID_TOKEN_12345' });

  // Test 3: Empty body
  console.log('\n=== Test 3: claimProperty with empty body ===');
  await post('https://www.hbdxm.com/index.php/api/claimProperty', {});

  // Test 4: Test with bad content type
  console.log('\n=== Test 4: claimProperty with form-urlencoded ===');
  const u4 = new URL('https://www.hbdxm.com/index.php/api/claimProperty');
  const body4 = 'phone=19171045360';
  const opts4 = {
    hostname: u4.hostname, port: 443, path: u4.pathname,
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'Content-Length': Buffer.byteLength(body4) },
    rejectUnauthorized: false, timeout: 15000
  };
  await new Promise((resolve, reject) => {
    const req = https.request(opts4, (res) => {
      let buf = [];
      res.on('data', c => buf.push(c));
      res.on('end', () => {
        const raw = Buffer.concat(buf).toString('utf8');
        console.log('HTTP', res.statusCode);
        console.log('Body:', raw.substring(0, 500));
        resolve();
      });
    });
    req.on('error', reject);
    req.write(body4);
    req.end();
  });

  // Test 5: Try to get a real token via phone login
  console.log('\n=== Test 5: Try phone login with real owner ===');
  const r5 = await post('https://www.hbdxm.com/index.php/api/login', { phone: '19171045360', password: '' });
  
  // Test 6: Try wechatLogin (POST)
  console.log('\n=== Test 6: Try wechatLogin ===');
  await post('https://www.hbdxm.com/index.php/api/wechatLogin', { code: 'test', community_id: 12 });
}

main().catch(e => console.error('FATAL:', e));
