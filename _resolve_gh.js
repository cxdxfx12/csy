const dns = require('dns');
const https = require('https');

// Try different DNS servers to resolve github.com
const servers = ['8.8.8.8', '1.1.1.1', '208.67.222.222'];

async function resolveWithServer(server) {
  return new Promise((resolve) => {
    const resolver = new dns.Resolver();
    resolver.setServers([server]);
    resolver.resolve4('github.com', (err, addresses) => {
      if (err) resolve({ server, error: err.message });
      else resolve({ server, ips: addresses });
    });
  });
}

async function main() {
  const results = await Promise.all(servers.map(resolveWithServer));
  for (const r of results) {
    console.log(r.server + ':', r.ips || r.error);
  }

  // Also try to resolve via DoH (DNS over HTTPS) using cloudflare
  const req = https.get('https://cloudflare-dns.com/dns-query?name=github.com&type=A', {
    headers: { 'Accept': 'application/dns-json' },
    timeout: 10000
  }, (res) => {
    let data = '';
    res.on('data', c => data += c);
    res.on('end', () => {
      try {
        const json = JSON.parse(data);
        const ips = json.Answer?.map(a => a.data).filter(d => /^\d+\.\d+\.\d+\.\d+$/.test(d));
        console.log('DoH Cloudflare:', ips);
      } catch (e) {
        console.log('DoH parse error:', e.message);
      }
    });
  });
  req.on('error', e => console.log('DoH error:', e.message));
}

main();
