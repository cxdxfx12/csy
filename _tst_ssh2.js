const { Client } = require('ssh2');

const conn = new Client();
conn.on('ready', () => {
  console.log('SSH connected\n');
  const cmd = `grep -n 'location.*api\|root\|server_name\|fastcgi_pass\|index' /www/server/panel/vhost/nginx/www.hbdxm.com.conf 2>/dev/null | head -30 && echo '===CONF_END===' && ls /www/wwwroot/ 2>/dev/null && echo '===DIR_END==='`;
  conn.exec(cmd, (err, stream) => {
    if (err) { console.error('Exec err:', err.message); conn.end(); return; }
    let out = '';
    stream.on('data', d => out += d.toString());
    stream.stderr.on('data', d => process.stderr.write(d));
    stream.on('close', () => { console.log(out); conn.end(); });
  });
}).on('error', e => console.error('SSH err:', e.message))
  .connect({
    host: '211.149.181.178',
    port: 22000,
    username: 'root',
    password: 'cxdxfx12',
    readyTimeout: 10000,
  });
