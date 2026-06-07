const { Client } = require('ssh2');
const conn = new Client();
conn.on('ready', () => {
  conn.exec(`cat /www/server/panel/vhost/nginx/www.hbdxm.com.conf 2>/dev/null || cat /www/server/nginx/conf/vhost/www.hbdxm.com.conf 2>/dev/null || ls /www/server/panel/vhost/nginx/*.conf 2>/dev/null`, (err, stream) => {
    let o = '';
    stream.on('data', d => o += d.toString());
    stream.on('close', () => {
      console.log(o.substring(0, 3000));
      conn.end();
    });
  });
}).connect({ host: '211.149.181.178', port: 22000, username: 'root', password: 'cxdxfx12', readyTimeout: 10000 });
