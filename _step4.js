const { NodeSSH } = require('node-ssh');
(async () => {
  const ssh = new NodeSSH();
  await ssh.connect({ host: '211.149.181.178', port: 22000, username: 'root', password: 'cxdxfx12' });
  
  // Try SSH to GitHub (port 22 and 443)
  const tests = [
    'timeout 5 bash -c "echo | nc github.com 22" 2>&1 && echo SSH22_OK || echo SSH22_FAIL',
    'timeout 5 bash -c "echo | nc ssh.github.com 443" 2>&1 && echo SSH443_OK || echo SSH443_FAIL',
    'timeout 5 bash -c "echo | nc github.com 443" 2>&1 && echo HTTPS_OK || echo HTTPS_FAIL',
    'which nc ncat socat 2>/dev/null',
    // Try via GitHub API
    'curl -s --connect-timeout 5 https://api.github.com 2>&1 | head -3',
    // Check if there's clash/v2ray on the server
    'ps aux | grep -E "clash|v2ray|xray|trojan|ss-local|shadowsocks" | grep -v grep',
    'ls /etc/v2ray/ /usr/local/etc/v2ray/ /root/clash/ 2>/dev/null',
    'netstat -tlnp 2>/dev/null | grep -E "1080|7890|10808|10809|1081" || ss -tlnp | grep -E "1080|7890|10808|10809|1081"',
  ];
  
  for (const cmd of tests) {
    const r = await ssh.execCommand(cmd, { execOptions: { timeout: 10000 } });
    if (r.stdout.trim()) console.log(r.stdout.trim());
    if (r.stderr.trim()) console.log('ERR:', r.stderr.trim());
  }
  
  ssh.dispose();
})();
