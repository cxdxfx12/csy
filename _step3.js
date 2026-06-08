const { NodeSSH } = require('node-ssh');
(async () => {
  const ssh = new NodeSSH();
  await ssh.connect({ host: '211.149.181.178', port: 22000, username: 'root', password: 'cxdxfx12' });
  
  // Try various GitHub mirrors/proxies
  const mirrors = [
    'https://ghproxy.net/https://github.com',
    'https://mirror.ghproxy.com/https://github.com', 
    'https://github.com.cnpmjs.org',
    'https://hub.fastgit.org',
  ];
  
  for (const url of mirrors) {
    const r = await ssh.execCommand(`curl -s --connect-timeout 5 -o /dev/null -w "%{http_code}" ${url} 2>&1`, { execOptions: { timeout: 10000 } });
    console.log(`${url}: ${r.stdout.trim()}`);
  }
  
  // Also try git protocol with various proxies
  const r2 = await ssh.execCommand('env | grep -i proxy 2>/dev/null; echo "---"; which proxychains4 proxychains 2>/dev/null; echo "---"; cat /root/.gitconfig 2>/dev/null || echo "no gitconfig"', { execOptions: { timeout: 5000 } });
  console.log(r2.stdout);
  
  ssh.dispose();
})();
