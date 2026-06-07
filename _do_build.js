const { execSync } = require('child_process');
const fs = require('fs');
const path = require('path');

const adminDir = 'e:/ds/admin';
const distDir = path.join(adminDir, 'dist');

// 清理旧的 dist
if (fs.existsSync(distDir)) {
  fs.rmSync(distDir, { recursive: true, force: true });
  console.log('Old dist removed');
}

console.log('Starting vite build...');
try {
  const result = execSync('node node_modules/vite/bin/vite.js build', {
    cwd: adminDir,
    encoding: 'utf-8',
    timeout: 180000,
    stdio: ['pipe', 'pipe', 'pipe']
  });
  console.log(result);
} catch (e) {
  console.log('STDOUT:', e.stdout?.slice(-500));
  console.log('STDERR:', e.stderr?.slice(-500));
  console.log('EXIT CODE:', e.status);
}

const indexExists = fs.existsSync(path.join(distDir, 'index.html'));
console.log('Build complete. index.html exists:', indexExists);
if (indexExists) {
  const files = fs.readdirSync(path.join(distDir, 'assets'));
  console.log('Asset count:', files.length);
}
