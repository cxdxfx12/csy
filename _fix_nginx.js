const { NodeSSH } = require('node-ssh');

(async () => {
  const ssh = new NodeSSH();
  await ssh.connect({ host: '211.149.181.178', port: 22000, username: 'root', password: 'cxdxfx12' });
  
  // Fix: Add no-cache for index.html and adjust SPA location
  const patchCmd = `cat > /tmp/nginx_patch.sh << 'SCRIPT'
#!/bin/bash
CONF="/www/server/panel/vhost/nginx/www.hbdxm.com.conf"

# Backup
cp "$CONF" "$CONF.bak2"

# Use Python to patch the config
python3 << 'PY'
import re

with open("/www/server/panel/vhost/nginx/www.hbdxm.com.conf", "r") as f:
    conf = f.read()

# Add index.html no-cache rule before the admin SPA fallback
# Find the admin SPA fallback location block
old_block = """    # Admin 静态资源直接返回，不走 SPA 回退
    location ~ ^/admin/assets/.*\\.(js|css|svg|png|jpg|jpeg|gif|webp|ico|woff|woff2|ttf)$ {
        expires 12h;
    }
        # Admin SPA fallback
    location /admin {
        try_files $uri $uri/ /admin/index.html;
    }"""

new_block = """    # Admin index.html 禁止缓存（确保用户总是获取最新版本）
    location = /admin/index.html {
        add_header Cache-Control "no-cache, no-store, must-revalidate";
        add_header Pragma "no-cache";
        add_header Expires "0";
    }
    # Admin 静态资源直接返回，不走 SPA 回退
    location ~ ^/admin/assets/.*\\.(js|css|svg|png|jpg|jpeg|gif|webp|ico|woff|woff2|ttf)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
    }
    # Admin SPA fallback
    location /admin {
        try_files $uri $uri/ /admin/index.html;
    }"""

conf = conf.replace(old_block, new_block)

with open("/www/server/panel/vhost/nginx/www.hbdxm.com.conf", "w") as f:
    f.write(conf)

print("Config patched successfully")
PY

SCRIPT
bash /tmp/nginx_patch.sh`;

  const r1 = await ssh.execCommand(patchCmd);
  console.log('Patch result:', r1.stdout || r1.stderr);
  
  // Test nginx config
  const r2 = await ssh.execCommand('nginx -t 2>&1');
  console.log('Nginx test:', r2.stdout || r2.stderr);
  
  // Reload nginx
  const r3 = await ssh.execCommand('nginx -s reload 2>&1');
  console.log('Nginx reload:', r3.stdout || r3.stderr);
  
  // Verify the change
  const r4 = await ssh.execCommand('grep -A3 "index.html" /www/server/panel/vhost/nginx/www.hbdxm.com.conf | head -10');
  console.log('New config:', r4.stdout);
  
  ssh.dispose();
})();
