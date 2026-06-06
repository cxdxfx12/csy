const { Client } = require('ssh2');
const fs = require('fs');

const conn = new Client();
conn.on('ready', () => {
    const localZip = 'e:/ds/public/admin.zip';
    const remoteZip = '/tmp/admin-dist.zip';
    const targetDir = '/www/wwwroot/www.hbdxm.com/public/admin';

    console.log('Step 1: Uploading zip...');
    conn.sftp((err, sftp) => {
        if (err) { console.log('SFTP ERR:', err); conn.end(); return; }
        const rs = fs.createReadStream(localZip);
        const ws = sftp.createWriteStream(remoteZip);
        rs.pipe(ws);
        ws.on('close', () => {
            console.log('Zip uploaded (1.2MB)');
            console.log('Step 2: Extract and deploy...');
            conn.exec(`rm -rf ${targetDir}/assets && unzip -o ${remoteZip} -d ${targetDir} && rm ${remoteZip} && echo "DEPLOY OK"`, (e2, s2) => {
                let d = ''; s2.on('data', dd => d += dd.toString());
                s2.stderr.on('data', dd => console.log('STDERR:', dd.toString()));
                s2.on('close', () => {
                    console.log(d.trim());
                    conn.end();
                });
            });
        });
    });
});
conn.on('error', e => console.log('ERR:', e.message));
conn.connect({ host: '211.149.181.178', port: 22000, username: 'root', password: 'cxdxfx12' });
