// 测试 claimProperty API
const https = require('https');

function post(url, body, headers = {}) {
  return new Promise((resolve, reject) => {
    const u = new URL(url);
    const data = JSON.stringify(body);
    const opts = {
      hostname: u.hostname,
      port: u.port || 443,
      path: u.pathname,
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Content-Length': Buffer.byteLength(data), ...headers },
      rejectUnauthorized: false,
      timeout: 15000
    };
    const req = https.request(opts, (res) => {
      let d = '';
      res.on('data', c => d += c);
      res.on('end', () => {
        console.log('HTTP', res.statusCode, 'Content-Type:', res.headers['content-type']);
        console.log('BODY:', d.substring(0, 500));
        try {
          resolve({ status: res.statusCode, data: JSON.parse(d) });
        } catch {
          resolve({ status: res.statusCode, data: d });
        }
      });
    });
    req.on('error', e => { console.error('NETWORK ERROR:', e.message); reject(e); });
    req.on('timeout', () => { req.destroy(); console.error('TIMEOUT'); reject(new Error('timeout')); });
    req.write(data);
    req.end();
  });
}

async function main() {
  const BASE = 'https://www.hbdxm.com/index.php/api';
  const phone = '19171045360';

  // Test 1: Claim API without token
  console.log('=== Test 1: Claim without token ===');
  try {
    const r1 = await post(`${BASE}/claimProperty`, { phone });
    console.log('Result:', JSON.stringify(r1));
  } catch (e) {}

  // Test 2: Login with test account or use a real auth
  console.log('\n=== Test 2: Try to login ===');
  try {
    const r2 = await post(`${BASE}/login`, { phone: 'test001', password: 'test001' });
    console.log('Result:', JSON.stringify(r2));
  } catch (e) {}

  // Test 3: Check if Login endpoint works at all
  console.log('\n=== Test 3: Login endpoint test ===');
  try {
    const r3 = await post(`${BASE}/login`, { phone: '13800138001', password: 'pass123' });
    console.log('Result:', JSON.stringify(r3));
  } catch (e) {}

  // Test 4: Check if the claimProperty route works
  console.log('\n=== Test 4: Verify claim route directly ===');
  try {
    const r4 = await post(`${BASE}/claimProperty`, { phone: '19171045360' });
    console.log('Result:', JSON.stringify(r4.data, null, 2));
  } catch (e) {}
}

main().catch(e => console.error('FATAL:', e));
