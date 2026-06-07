const { Client } = require('ssh2');
const fs = require('fs');
const conn = new Client();
conn.on('ready', () => {
  conn.sftp((err, sftp) => {
    if (err) { console.log('SFTP ERR:', err); conn.end(); return; }
    const files = [
      ['e:/ds/app/admin/controller/AdminUser.php', '/www/wwwroot/www.hbdxm.com/app/admin/controller/AdminUser.php'],
      ['e:/ds/route/admin.php', '/www/wwwroot/www.hbdxm.com/route/admin.php'],
    ];
    let i = 0;
    function uploadNext() {
      if (i >= files.length) { console.log('ALL SYNCED'); conn.end(); return; }
      const [local, remote] = files[i++];
      const rs = fs.createReadStream(local);
      const ws = sftp.createWriteStream(remote);
      rs.pipe(ws);
      ws.on('close', () => { console.log('Synced:', remote); uploadNext(); });
      ws.on('error', e => { console.log('ERR:', remote, e.message); uploadNext(); });
    }
    uploadNext();
  });
});
conn.connect({ host: '211.149.181.178', port: 22000, username: 'root', password: 'cxdxfx12' });
