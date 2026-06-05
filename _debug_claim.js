const https = require('https');

// 用正确的JWT密钥生成owner token
const crypto = require('crypto');
function base64url(str) { return Buffer.from(str).toString('base64url'); }
function sign(data, key) {
  return crypto.createHmac('sha256', key).update(data).digest('base64url');
}

const jwtKey = 'd5F8kL2mN9pQ3rT7wX1yA4bC6eH0jV5u';
const now = Math.floor(Date.now() / 1000);
const header = base64url(JSON.stringify({ alg: 'HS256', typ: 'JWT' }));
const payload = base64url(JSON.stringify({
  iss: 'dasheng-pms', aud: 'dasheng-pms-client',
  iat: now - 60, nbf: now - 60, exp: now + 86400,
  sub: 1, type: 'owner'
}));
const token = header + '.' + payload + '.' + sign(header + '.' + payload, jwtKey);

console.log('Token:', token.substring(0, 50) + '...\n');

// 测试1: 用 application/json content-type
function testReq(opts) {
  return new Promise((resolve) => {
    const req = https.request(opts, res => {
      const ct = res.headers['content-type'] || '';
      let body = '';
      res.on('data', c => body += c);
      res.on('end', () => {
        resolve({ status: res.statusCode, contentType: ct, body: body.substring(0, 500) });
      });
    });
    req.on('error', e => resolve({ error: e.message }));
    if (opts.body) req.write(opts.body);
    req.end();
  });
}

async function main() {
  // Test with JSON content-type
  console.log('=== Test 1: application/json ===');
  const r1 = await testReq({
    hostname: 'www.hbdxm.com', port: 443, path: '/index.php/api/claimProperty',
    method: 'POST', rejectUnauthorized: false,
    headers: {
      'Content-Type': 'application/json',
      'Authorization': 'Bearer ' + token
    },
    body: JSON.stringify({ phone: '19171045360' })
  });
  console.log('Status:', r1.status);
  console.log('Content-Type:', r1.contentType);
  console.log('Body:', r1.body);
  console.log();

  // Test 2: what if owner is not found?
  console.log('=== Test 2: invalid phone ===');
  const r2 = await testReq({
    hostname: 'www.hbdxm.com', port: 443, path: '/index.php/api/claimProperty',
    method: 'POST', rejectUnauthorized: false,
    headers: {
      'Content-Type': 'application/json',
      'Authorization': 'Bearer ' + token
    },
    body: JSON.stringify({ phone: '13800138000' })
  });
  console.log('Status:', r2.status);
  console.log('Content-Type:', r2.contentType);
  console.log('Body:', r2.body);
  console.log();

  // Test 3: no auth token
  console.log('=== Test 3: no token ===');
  const r3 = await testReq({
    hostname: 'www.hbdxm.com', port: 443, path: '/index.php/api/claimProperty',
    method: 'POST', rejectUnauthorized: false,
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ phone: '19171045360' })
  });
  console.log('Status:', r3.status);
  console.log('Content-Type:', r3.contentType);
  console.log('Body:', r3.body);
  console.log();

  // Test 4: Check if the issue is with nginx redirect
  console.log('=== Test 4: without /index.php ===');
  const r4 = await testReq({
    hostname: 'www.hbdxm.com', port: 443, path: '/api/claimProperty',
    method: 'POST', rejectUnauthorized: false,
    headers: {
      'Content-Type': 'application/json',
      'Authorization': 'Bearer ' + token
    },
    body: JSON.stringify({ phone: '19171045360' })
  });
  console.log('Status:', r4.status);
  console.log('Content-Type:', r4.contentType);
  console.log('Body:', r4.body);

  // Test 5: Check if content-type without charset suffix matters
  console.log('\n=== Test 5: check exact content-type ===');
  const req = https.request({
    hostname: 'www.hbdxm.com', port: 443, path: '/index.php/api/claimProperty',
    method: 'POST', rejectUnauthorized: false,
    headers: {
      'Content-Type': 'application/json',
      'Authorization': 'Bearer ' + token
    }
  }, res => {
    console.log('Status:', res.statusCode);
    console.log('All headers:', JSON.stringify(res.headers, null, 2));
    let body = '';
    res.on('data', c => body += c);
    res.on('end', () => console.log('Body (first 300):', body.substring(0, 300)));
  });
  req.on('error', e => console.log('Error:', e.message));
  req.write(JSON.stringify({ phone: '19171045360' }));
  req.end();
}

main();
