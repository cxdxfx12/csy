const { Client } = require('ssh2');

const conn = new Client();
conn.on('ready', () => {
  console.log('SSH connected\n');
  const cmd = `cat /www/server/panel/vhost/rewrite/www.hbdxm.com.conf && echo '===REWRITE_END===' && cat /www/server/panel/vhost/nginx/extension/www.hbdxm.com/*.conf 2>/dev/null && echo '===EXT_END===' && cat /www/server/panel/vhost/nginx/enable-php-81.conf 2>/dev/null && echo '===PHP_END==='`;
  conn.exec(cmd, (err, stream) => {
    if (err) { console.error('Exec err:', err.message); conn.end(); return; }
    let out = '';
    stream.on('data', d => out += d.toString());
    stream.stderr.on('data', d => process.stderr.write(d));
    stream.on('close', () => { console.log(out.substring(0, 5000)); conn.end(); });
  });
}).on('error', e => console.error('SSH err:', e.message))
  .connect({
    host: '211.149.181.178',
    port: 22000,
    username: 'root',
    password: 'cxdxfx12',
    readyTimeout: 10000,
  });
