// Quick check: try to reach github API from local
const https = require('https');
const req = https.get('https://api.github.com', { timeout: 8000, headers: { 'User-Agent': 'node' } }, (res) => {
  let data = '';
  res.on('data', c => data += c);
  res.on('end', () => console.log('Status:', res.statusCode, data.substring(0, 100)));
});
req.on('error', e => console.log('Error:', e.message));
req.on('timeout', () => { console.log('Timeout'); req.destroy(); });
