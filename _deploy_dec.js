const { Client } = require('ssh2');
const fs = require('fs');

const conn = new Client();
conn.on('ready', () => {
    conn.sftp((err, sftp) => {
        if (err) { console.log('SFTP ERR:', err); conn.end(); return; }
        const content = fs.readFileSync('e:/ds/app/admin/controller/Decoration.php', 'utf8');
        const writeStream = sftp.createWriteStream('/www/wwwroot/www.hbdxm.com/app/admin/controller/Decoration.php');
        writeStream.write(content);
        writeStream.end();
        writeStream.on('close', () => {
            console.log('Decoration.php uploaded');
            conn.exec('php -l /www/wwwroot/www.hbdxm.com/app/admin/controller/Decoration.php', (e, s) => {
                let d = ''; s.on('data', dd => d += dd);
                s.on('close', () => { console.log(d.trim()); conn.end(); });
            });
        });
    });
});
conn.on('error', e => console.log('ERR:', e.message));
conn.connect({ host: '211.149.181.178', port: 22000, username: 'root', password: 'cxdxfx12' });
