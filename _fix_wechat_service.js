const { Client } = require('ssh2');
const fs = require('fs');
const path = require('path');
const conn = new Client();
const cfg = { host: '211.149.181.178', port: 22000, username: 'root', password: 'cxdxfx12' };

conn.on('ready', () => {
  console.log('SSH connected.');
  // Upload fix script
  const localScript = path.join(__dirname, '_fix_autoload.php');
  const remoteScript = '/tmp/_fix_autoload.php';
  conn.sftp((err, sftp) => {
    if (err) { console.error(err); conn.end(); return; }
    sftp.fastPut(localScript, remoteScript, (err) => {
      if (err) { console.error(err); conn.end(); return; }
      console.log('Script uploaded to ' + remoteScript);
      sftp.end();
      // Execute
      console.log('Executing fix script...');
      conn.exec('php ' + remoteScript, (err2, stream) => {
        if (err2) { console.error(err2); conn.end(); return; }
        stream.on('data', d => process.stdout.write(d.toString()));
        stream.stderr.on('data', d => process.stderr.write(d.toString()));
        stream.on('close', () => {
          // Clean up
          conn.exec('rm -f ' + remoteScript, (err3, stream3) => {
            if (!stream3) return;
            stream3.on('close', () => {
              console.log('\nCleanup done.');
              conn.end();
            });
          });
        });
      });
    });
  });
}).connect(cfg);

conn.on('error', e => { console.error(e.message); process.exit(1); });
