const https = require('https');
const dns = require('dns');

// Check if ssh.github.com resolves and is reachable on 443
const resolver = new dns.Resolver();
resolver.setServers(['8.8.8.8']);

resolver.resolve4('ssh.github.com', (err, addresses) => {
  console.log('ssh.github.com:', err ? err.message : addresses);
});

resolver.resolve4('api.github.com', (err, addresses) => {
  console.log('api.github.com:', err ? err.message : addresses);
});

// Test connecting to ssh.github.com:443
const sock = require('net').connect(443, 'ssh.github.com', () => {
  console.log('ssh.github.com:443 connected!');
  sock.destroy();
});
sock.setTimeout(8000);
sock.on('timeout', () => { console.log('ssh.github.com:443 timeout'); sock.destroy(); });
sock.on('error', (e) => { console.log('ssh.github.com:443 error:', e.message); });
