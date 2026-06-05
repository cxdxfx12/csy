// 测试所有"问题"API接口
const http = require('http');
const https = require('https');

const HOST = 'www.hbdxm.com';
const TOKEN = process.argv[2];

if (!TOKEN) {
  console.log('用法: node _test_apis.js <owner_token>');
  process.exit(1);
}

const endpoints = [
  '/repair/list',
  '/complaint/list', 
  '/vehicle/list',
  '/visitor/list',
  '/activity/list',
  '/activity/mySignups',
];

async function callApi(path) {
  return new Promise((resolve, reject) => {
    const opts = {
      hostname: HOST,
      port: 443,
      path: '/index.php/api' + path,
      method: 'GET',
      headers: {
        'Authorization': 'Bearer ' + TOKEN,
        'Content-Type': 'application/json',
        'User-Agent': 'Mozilla/5.0 (Linux; Android 13; M2007J3SC) AppleWebKit/537.36 MicroMessenger/8.0.37',
      },
      rejectUnauthorized: false,
    };
    const req = https.request(opts, (res) => {
      let body = '';
      res.on('data', d => body += d);
      res.on('end', () => {
        const ct = res.headers['content-type'] || '';
        const preview = body.substring(0, 200);
        console.log(`  ${path.padEnd(25)} ${res.statusCode} ${ct.padEnd(45)} ${preview.replace(/\n/g,' ')}`);
        resolve({ status: res.statusCode, ct, body });
      });
    });
    req.on('error', e => { console.log(`  ${path.padEnd(25)} ERROR: ${e.message}`); resolve(null); });
    req.end();
  });
}

async function main() {
  console.log('\n=== 测试 API 响应 ===');
  for (const ep of endpoints) {
    await callApi(ep);
  }
}

main();
