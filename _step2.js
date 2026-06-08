const { NodeSSH } = require('node-ssh');
(async () => {
  const ssh = new NodeSSH();
  await ssh.connect({ host: '211.149.181.178', port: 22000, username: 'root', password: 'cxdxfx12' });
  
  // Quick DNS check and curl test
  let r = await ssh.execCommand('curl -s --connect-timeout 5 https://github.com -o /dev/null -w "%{http_code}" 2>&1', { execOptions: { timeout: 10000 } });
  console.log('GH HTTP:', r.stdout.trim() || r.stderr.trim());
  
  r = await ssh.execCommand('cat /etc/resolv.conf | head -3', { execOptions: { timeout: 5000 } });
  console.log('DNS:', r.stdout.trim());
  
  ssh.dispose();
})();
