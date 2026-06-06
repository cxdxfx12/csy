const { Client } = require('ssh2');
const fs = require('fs');
const path = require('path');
const archiver = require('archiver');

const SSH_HOST = '211.149.181.178';
const SSH_PORT = 22000;
const SSH_USER = 'root';
const SSH_PASS = 'cxdxfx12';
const REMOTE_DIR = '/www/wwwroot/www.hbdxm.com/public/admin/';

const conn = new Client();

conn.on('ready', () => {
  console.log('[1/4] SSH connected');

  // 打包 admin/dist 目录
  const tarPath = path.join(__dirname, '_fe_deploy.tar.gz');
  const output = fs.createWriteStream(tarPath);
  const archive = archiver('tar', { gzip: true });

  output.on('close', () => {
    const fileSize = archive.pointer();
    console.log(`[2/4] Compressed: ${(fileSize / 1024).toFixed(0)} KB`);

    // 上传压缩包
    conn.sftp((err, sftp) => {
      if (err) { console.error('SFTP err:', err.message); conn.end(); return; }

      const remoteTar = '/tmp/_fe_deploy.tar.gz';
      const readStream = fs.createReadStream(tarPath);
      const writeStream = sftp.createWriteStream(remoteTar);

      writeStream.on('close', () => {
        console.log('[3/4] Uploaded');
        // 解压部署
        const cmd = [
          // 先清理旧的 assets 文件（保留 index.html）
          `find ${REMOTE_DIR}assets/ -name 'DecorationApply-*.js' -delete`,
          `find ${REMOTE_DIR}assets/ -name 'DecorationApply-*.css' -delete`,
          // 解压新文件
          `tar -xzf /tmp/_fe_deploy.tar.gz -C ${REMOTE_DIR}`,
          // 清理临时文件
          `rm -f /tmp/_fe_deploy.tar.gz`,
          // 列出现有 DecorationApply 文件
          `ls -la ${REMOTE_DIR}assets/DecorationApply-*.js ${REMOTE_DIR}assets/DecorationApply-*.css 2>/dev/null`,
        ].join(' && ');
        conn.exec(cmd, (err, stream) => {
          if (err) { console.error('Exec err:', err.message); conn.end(); fs.unlinkSync(tarPath); return; }
          let out = '';
          stream.on('data', d => out += d.toString());
          stream.stderr.on('data', d => process.stderr.write(d));
          stream.on('close', () => {
            console.log('[4/4] Deploy result:\n' + out);
            fs.unlinkSync(tarPath);
            conn.end();
          });
        });
      });

      readStream.pipe(writeStream);
    });
  });

  archive.on('error', (err) => { console.error('Archive err:', err.message); conn.end(); });
  archive.pipe(output);
  archive.directory('admin/dist', false);
  archive.finalize();
});

conn.on('error', (e) => console.error('SSH err:', e.message));
conn.connect({ host: SSH_HOST, port: SSH_PORT, username: SSH_USER, password: SSH_PASS, readyTimeout: 10000 });
