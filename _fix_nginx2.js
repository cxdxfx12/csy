const { NodeSSH } = require('node-ssh');

(async () => {
  const ssh = new NodeSSH();
  await ssh.connect({ host: '211.149.181.178', port: 22000, username: 'root', password: 'cxdxfx12' });
  
  // Read current config
  const r = await ssh.execCommand('cat /www/server/panel/vhost/nginx/www.hbdxm.com.conf');
  const conf = r.stdout;
  
  // The problem: the global `location ~ .*\.(js|css)?$` block at the bottom
  // catches all JS/CSS requests with expires 12h, overriding the more specific
  // admin/assets location block.
  
  // We need to:
  // 1. Make index.html no-cache work (use exact match = /admin/index.html)
  // 2. The admin/assets location already has its own expires, but the global one overrides it
  
  // Fix: Modify the global JS/CSS block to exclude /admin/assets/
  // And ensure the index.html no-cache rule works
  
  const newConf = conf
    // Fix the global JS/CSS block to not cache index.html and not override admin/assets
    .replace(
      /location ~ \.\*\\\.\(js\|css\)\?\$[\s\S]*?{[\s\S]*?}/,
      `location ~ ^(?!/admin/assets/).*\\.(js|css)?$ {
        expires      12h;
        error_log /dev/null;
        access_log /dev/null;
    }`
    )
    // Also ensure admin index.html has no-cache (rewrite the block properly)
    .replace(
      /# Admin index\.html 禁止缓存[\s\S]*?location = \/admin\/index\.html[\s\S]*?}/,
      `# Admin index.html 禁止缓存（确保用户总是获取最新版本）
    location = /admin/index.html {
        add_header Cache-Control "no-cache, no-store, must-revalidate" always;
        add_header Pragma "no-cache" always;
        add_header Expires "0" always;
    }`
    );
  
  // Write the new config
  await ssh.execCommand(`cat > /www/server/panel/vhost/nginx/www.hbdxm.com.conf << 'NGINXEOF'
${newConf}
NGINXEOF`);
  
  // Test and reload
  const r2 = await ssh.execCommand('nginx -t 2>&1');
  console.log('Nginx test:', r2.stdout || r2.stderr);
  
  if (r2.stdout.includes('ok') || r2.stderr.includes('ok')) {
    const r3 = await ssh.execCommand('nginx -s reload 2>&1');
    console.log('Nginx reload:', r3.stdout || r3.stderr || 'OK');
  } else {
    console.log('Config error, reverting...');
    await ssh.execCommand('cp /www/server/panel/vhost/nginx/www.hbdxm.com.conf.bak2 /www/server/panel/vhost/nginx/www.hbdxm.com.conf && nginx -s reload');
  }
  
  ssh.dispose();
})();
