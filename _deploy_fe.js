const { execSync } = require('child_process');
const { Client } = require('ssh2');
const fs = require('fs');
const path = require('path');

// Step 1: Create a fresh zip from the build output
const adminDir = 'e:/ds/public/admin';
const zipFile = 'e:/ds/public/admin.zip';

// Remove old zip
if (fs.existsSync(zipFile)) {
  fs.rmSync(zipFile);
  console.log('Old zip removed');
}

// Create new zip using PowerShell Compress-Archive
console.log('Creating zip from build output...');
try {
  execSync(`powershell -Command "Compress-Archive -Path '${adminDir}/*' -DestinationPath '${zipFile}' -Force"`, {
    encoding: 'utf-8',
    timeout: 30000
  });
  const stats = fs.statSync(zipFile);
  console.log(`Zip created: ${(stats.size / 1024).toFixed(0)} KB`);
} catch (e) {
  console.log('Zip creation error:', e.message);
  process.exit(1);
}

// Step 2: Upload and deploy via SSH2
const conn = new Client();
conn.on('ready', () => {
  const remoteZip = '/tmp/admin-dist.zip';
  const targetDir = '/www/wwwroot/www.hbdxm.com/public/admin';

  console.log('Step 1: Uploading zip...');
  conn.sftp((err, sftp) => {
    if (err) { console.log('SFTP ERR:', err); conn.end(); return; }
    const rs = fs.createReadStream(zipFile);
    const ws = sftp.createWriteStream(remoteZip);
    rs.pipe(ws);
    ws.on('close', () => {
      const size = fs.statSync(zipFile).size;
      console.log(`Zip uploaded (${(size/1024).toFixed(0)} KB)`);
      console.log('Step 2: Extract and deploy...');
      conn.exec(`rm -rf ${targetDir}/assets && rm -f ${targetDir}/index.html && unzip -o ${remoteZip} -d ${targetDir} && rm ${remoteZip} && echo "DEPLOY OK"`, (e2, s2) => {
        let d = ''; s2.on('data', dd => d += dd.toString());
        s2.stderr.on('data', dd => console.log('STDERR:', dd.toString()));
        s2.on('close', () => {
          console.log(d.trim());
          // Verify deployment
          conn.exec(`ls -la ${targetDir}/assets/Device-*.js && echo "---" && grep -o "deviceList[^;]\\{0,30\\}" ${targetDir}/assets/Device-*.js | head -3`, (e3, s3) => {
            let v = ''; s3.on('data', dd => v += dd.toString());
            s3.stderr.on('data', dd => v += dd.toString());
            s3.on('close', () => { console.log('Verification:', v); conn.end(); });
          });
        });
      });
    });
  });
});
conn.on('error', e => console.log('ERR:', e.message));
conn.connect({ host: '211.149.181.178', port: 22000, username: 'root', password: 'cxdxfx12' });
