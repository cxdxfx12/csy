const { Client } = require('ssh2');
const fs = require('fs');
const path = require('path');

const conn = new Client();
const cfg = {
  host: '211.149.181.178', port: 22000,
  username: 'root', password: 'cxdxfx12'
};
const remoteRoot = '/www/wwwroot/www.hbdxm.com/public';

const adminAssetsDir = 'e:/ds/public/admin/assets';
const remoteAssetsDir = remoteRoot + '/admin/assets';

function getLocalFiles(dir) {
  const files = [];
  if (!fs.existsSync(dir)) return files;
  const entries = fs.readdirSync(dir, { withFileTypes: true });
  for (const e of entries) {
    const full = path.join(dir, e.name);
    if (e.isFile()) {
      files.push({ local: full, remote: remoteAssetsDir + '/' + e.name, data: fs.readFileSync(full) });
    }
  }
  return files;
}

conn.on('ready', () => {
  console.log('SSH connected');

  // 读取所有文件数据（含 index.html + admin/assets/*）
  const allFiles = [];
  if (fs.existsSync('e:/ds/public/admin/index.html')) {
    allFiles.push({ local: 'e:/ds/public/admin/index.html', remote: remoteRoot + '/admin/index.html', data: fs.readFileSync('e:/ds/public/admin/index.html') });
  }
  allFiles.push(...getLocalFiles(adminAssetsDir));

  console.log(`Total files to upload: ${allFiles.length}`);

  conn.sftp((err, sftp) => {
    if (err) { console.error('SFTP error:', err.message); conn.end(); return; }

    let idx = 0;
    function next() {
      if (idx >= allFiles.length) {
        console.log('All files uploaded!');
        conn.end();
        return;
      }
      const f = allFiles[idx++];
      console.log(`[${idx}/${allFiles.length}] ${path.basename(f.local)}`);
      const ws = sftp.createWriteStream(f.remote);
      ws.write(f.data);
      ws.end();
      ws.on('close', () => next());
      ws.on('error', (err) => { console.error('Write error:', f.remote, err.message); next(); });
    }
    next();
  });
}).on('error', (err) => console.error('SSH error:', err.message))
  .connect(cfg);
