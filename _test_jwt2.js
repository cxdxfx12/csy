const https = require('https');
const token = process.argv[2];

function req(method, path, body) {
  return new Promise((resolve) => {
    const u = new URL(path, 'https://www.hbdxm.com');
    const r = https.request({
      hostname: u.hostname, port: 443, path: u.pathname + u.search, method,
      headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + token },
      rejectUnauthorized: false,
    }, (res) => {
      let d = '';
      res.on('data', c => d += c);
      res.on('end', () => {
        console.log(`${method} ${path} → ${res.statusCode}`);
        try { console.log(JSON.stringify(JSON.parse(d), null, 2)); }
        catch(e) { console.log(d); }
        resolve();
      });
    });
    r.on('error', e => { console.log('ERR:', e.message); resolve(); });
    if (body) r.write(body);
    r.end();
  });
}

async function main() {
  console.log('Token:', token.substring(0, 20) + '...');
  await req('GET', '/index.php/api/badge/counts');
  await req('GET', '/index.php/api/profile/info');
  await req('POST', '/index.php/api/claimProperty', JSON.stringify({ phone: '19171045360' }));
}

main();
