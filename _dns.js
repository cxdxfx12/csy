const dns = require('dns');
const https = require('https');

// Resolve github.com
dns.resolve4('github.com', (err, addresses) => {
  if (err) console.log('DNS error:', err.message);
  else console.log('github.com IPs:', addresses);
});

// Try connecting to github.com on port 443
const req = https.get('https://github.com', { timeout: 10000 }, (res) => {
  console.log('GitHub Status:', res.statusCode);
  res.destroy();
});
req.on('error', e => console.log('GitHub Error:', e.message));
req.on('timeout', () => { console.log('GitHub Timeout'); req.destroy(); });

// Also try api.github.com
const req2 = https.get('https://api.github.com', { timeout: 10000, headers: { 'User-Agent': 'node' } }, (res) => {
  console.log('API Status:', res.statusCode);
  res.destroy();
});
req2.on('error', e => console.log('API Error:', e.message));
