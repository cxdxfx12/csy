const { NodeSSH } = require('node-ssh');
(async () => {
  const ssh = new NodeSSH();
  await ssh.connect({ host: '211.149.181.178', port: 22000, username: 'root', password: 'cxdxfx12' });
  const r = await ssh.execCommand('git clone --depth 1 https://github.com/cxdxfx12/csy.git /tmp/csy 2>&1 && echo CLONE_OK || echo CLONE_FAIL', { execOptions: { timeout: 30000 } });
  console.log(r.stdout);
  console.log(r.stderr);
  ssh.dispose();
})();
