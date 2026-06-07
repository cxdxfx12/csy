const { Client } = require('ssh2');
const conn = new Client();

conn.on('ready', () => {
  const dir = '/www/wwwroot/www.hbdxm.com';
  const cmd = 'cd ' + dir + ' && COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --optimize-autoloader 2>&1';
  
  conn.exec(cmd, (err, stream) => {
    if (err) { console.error(err); conn.end(); return; }
    stream.on('data', d => process.stdout.write(d.toString()));
    stream.stderr.on('data', d => process.stderr.write(d.toString()));
    stream.on('close', (code) => {
      console.log('\nExit code:', code);
      // Verify
      conn.exec('grep -i firebase ' + dir + '/vendor/composer/autoload_psr4.php', (e2, s2) => {
        let out = '';
        s2.on('data', d => out += d.toString());
        s2.on('close', () => {
          console.log('Firebase in autoload:', out.trim() || 'NOT FOUND');
          conn.end();
        });
      });
    });
  });
});

conn.on('error', e => { console.error(e.message); process.exit(1); });
conn.connect({ host: '211.149.181.178', port: 22000, username: 'root', password: 'cxdxfx12', readyTimeout: 30000 });
