const { NodeSSH } = require('node-ssh');
(async () => {
  const ssh = new NodeSSH();
  await ssh.connect({ host: '211.149.181.178', port: 22000, username: 'root', password: 'cxdxfx12' });
  let r = await ssh.execCommand('ls /root/csy/.git/HEAD 2>/dev/null && echo YES || echo NO');
  console.log(r.stdout.trim());
  r = await ssh.execCommand('timeout 10 curl -s -o /dev/null -w "%{http_code}" https://github.com 2>&1');
  console.log('GH:', r.stdout.trim());
  ssh.dispose();
})();
