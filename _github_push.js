const { NodeSSH } = require('node-ssh');

(async () => {
  const ssh = new NodeSSH();
  await ssh.connect({ host: '211.149.181.178', port: 22000, username: 'root', password: 'cxdxfx12' });

  // Step 1: Check if server repo exists
  let r = await ssh.execCommand('ls /root/csy/.git/HEAD 2>/dev/null && echo YES || echo NO');
  console.log('Repo exists:', r.stdout.trim());

  if (r.stdout.trim().includes('NO')) {
    console.log('Cloning...');
    r = await ssh.execCommand('cd /root && git clone https://github.com/cxdxfx12/csy.git 2>&1', { execOptions: { timeout: 60000 } });
    console.log(r.stdout || r.stderr);
  }

  // Step 2: Check server can reach GitHub
  r = await ssh.execCommand('timeout 10 curl -s -o /dev/null -w "%{http_code}" https://github.com 2>&1');
  console.log('GitHub HTTP status:', r.stdout.trim());

  // Step 3: Upload bundle
  console.log('Uploading bundle...');
  await ssh.putFile('e:\\ds\\_repo.bundle', '/root/csy/_repo.bundle');
  console.log('Bundle uploaded.');

  // Step 4: Pull from bundle
  r = await ssh.execCommand('cd /root/csy && git bundle verify _repo.bundle 2>&1');
  console.log('Verify:', r.stdout || r.stderr);

  r = await ssh.execCommand('cd /root/csy && git pull _repo.bundle master 2>&1');
  console.log('Pull:', r.stdout || r.stderr);

  // Step 5: Push to GitHub
  console.log('Pushing to GitHub...');
  r = await ssh.execCommand('cd /root/csy && git push origin master 2>&1', { execOptions: { timeout: 120000 } });
  console.log('Push out:', r.stdout);
  console.log('Push err:', r.stderr);

  // Cleanup
  await ssh.execCommand('rm -f /root/csy/_repo.bundle');
  ssh.dispose();
  console.log('DONE');
})().catch(e => console.error(e.message || e));
