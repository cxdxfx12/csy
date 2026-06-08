const ssh2 = require('ssh2');
const fs = require('fs');
const path = require('path');

const conn = new ssh2.Client();

conn.on('ready', () => {
  console.log('SSH connected');
  
  // Create bundle on local first
  const { execSync } = require('child_process');
  const bundlePath = path.join(__dirname, '_repo.bundle');
  
  try {
    execSync('git bundle create ' + bundlePath + ' origin/master..master', {
      cwd: 'e:\\ds',
      stdio: 'pipe'
    });
    console.log('Bundle created, size:', fs.statSync(bundlePath).size);
  } catch(e) {
    // If bundle fails, try full bundle
    try {
      execSync('git bundle create ' + bundlePath + ' --all', {
        cwd: 'e:\\ds',
        stdio: 'pipe'
      });
      console.log('Full bundle created, size:', fs.statSync(bundlePath).size);
    } catch(e2) {
      console.error('Bundle creation failed:', e2.message);
      conn.end();
      process.exit(1);
    }
  }

  // SFTP upload the bundle
  conn.sftp((err, sftp) => {
    if (err) { console.error('SFTP error:', err); conn.end(); return; }
    
    const remotePath = '/tmp/repo.bundle';
    const ws = sftp.createWriteStream(remotePath);
    const rs = fs.createReadStream(bundlePath);
    
    ws.on('close', () => {
      console.log('Bundle uploaded');
      
      // Now run git commands on the server
      const cmds = [
        'cd /tmp && rm -rf csy_mirror',
        'git clone https://github.com/cxdxfx12/csy.git /tmp/csy_mirror 2>&1 || echo "CLONE_MAY_FAIL"',
        'cd /tmp/csy_mirror && git bundle verify /tmp/repo.bundle 2>&1 || true',
        'cd /tmp/csy_mirror && git pull /tmp/repo.bundle master 2>&1 || git fetch /tmp/repo.bundle master:refs/remotes/bundle/master 2>&1',
        'cd /tmp/csy_mirror && git log --oneline -5 2>&1',
      ];
      
      function runCmd(idx) {
        if (idx >= cmds.length) {
          console.log('All setup commands done');
          // Try push
          conn.exec('cd /tmp/csy_mirror && git push origin master 2>&1', (err, stream) => {
            if (err) { console.error('Push exec error:', err); conn.end(); return; }
            let out = '';
            stream.on('data', d => out += d);
            stream.stderr.on('data', d => out += d);
            stream.on('close', () => {
              console.log('Push result:', out);
              conn.end();
            });
          });
          return;
        }
        console.log('Running:', cmds[idx]);
        conn.exec(cmds[idx], (err, stream) => {
          if (err) { console.error('Cmd error:', err); runCmd(idx+1); return; }
          let out = '';
          stream.on('data', d => { out += d; process.stdout.write(d); });
          stream.stderr.on('data', d => { out += d; process.stdout.write(d); });
          stream.on('close', () => { runCmd(idx+1); });
        });
      }
      runCmd(0);
    });
    
    rs.on('error', e => console.error('Read error:', e));
    ws.on('error', e => console.error('Write error:', e));
    rs.pipe(ws);
  });
});

conn.on('error', e => console.error('SSH error:', e));

conn.connect({
  host: '211.149.181.178',
  port: 22000,
  username: 'root',
  password: 'cxdxfx12',
  readyTimeout: 30000
});
