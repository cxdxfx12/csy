const { Client } = require('ssh2');
const c = new Client();
c.on('ready', () => {
  c.exec('cat /www/server/panel/vhost/rewrite/www.hbdxm.com.conf && echo ---EXT--- && cat /www/server/panel/vhost/nginx/extension/www.hbdxm.com/*.conf 2>/dev/null && echo ---PHP--- && cat /www/server/nginx/conf/enable-php-81.conf 2>/dev/null', (e, s) => {
    let o = ''; s.stderr.on('data', d => o += d); s.on('data', d => o += d);
    s.on('close', () => { console.log(o); c.end(); setTimeout(() => process.exit(0), 300); });
  });
}).connect({ host:'211.149.181.178', port:22000, username:'root', password:'cxdxfx12', readyTimeout:10000 });
