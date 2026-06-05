const { NodeSSH } = require('node-ssh');
const path = require('path');
const fs = require('fs');

const ssh = new NodeSSH();

const config = {
  host: '211.149.181.178',
  port: 22,
  username: 'root',
  password: 'cxdxfx12',
};

// 本地 public 目录
const localPublic = path.join(__dirname, 'public');

// 所有需要上传的文件（相对 public/）
const files = [
  'owner.html',
  'staff.html',
  'manager.html',
  'assets/owner-wsvGkWrd.js',
  'assets/staff-Cn5g9Yxy.js',
  'assets/manager-BDosXLxi.js',
  'assets/GlobalToast-D2uP8LqL.js',
  'assets/pinia-D6b2HoYy.js',
  'assets/GlobalToast-C359LiT5.css',
  'assets/owner-D3LvmrpX.css',
  'assets/staff-0tWMjREl.css',
  'assets/manager-DkVrqPKw.css',
];

async function main() {
  console.log('=== 连接服务器 ===');
  await ssh.connect(config);
  console.log('✓ SSH 连接成功\n');

  // 查找 web 根目录
  console.log('=== 查找 Web 根目录 ===');
  let webRoot = null;
  
  const candidates = [
    '/www/wwwroot/www.hbdxm.com',
    '/www/wwwroot/hbdxm.com',
    '/www/wwwroot/default',
    '/var/www/html',
    '/www/server/nginx/html',
  ];

  for (const candidate of candidates) {
    const result = await ssh.execCommand(`test -f ${candidate}/owner.html && echo "FOUND"`);
    if (result.stdout.includes('FOUND')) {
      webRoot = candidate;
      console.log(`✓ 找到 Web 根目录: ${webRoot}`);
      break;
    }
  }

  if (!webRoot) {
    // 尝试搜索
    console.log('  搜索中...');
    const result = await ssh.execCommand('find /www /home /var -name "owner.html" -type f 2>/dev/null | head -5');
    console.log('  搜索结果:', result.stdout);
    if (result.stdout.trim()) {
      webRoot = path.dirname(result.stdout.trim().split('\n')[0]);
      console.log(`✓ 推断 Web 根目录: ${webRoot}`);
    }
  }

  if (!webRoot) {
    console.error('✗ 找不到 Web 根目录!');
    ssh.dispose();
    process.exit(1);
  }

  // 上传文件
  console.log('\n=== 上传文件 ===');
  let uploaded = 0;
  let failed = 0;

  for (const file of files) {
    const localPath = path.join(localPublic, file);
    const remotePath = path.join(webRoot, file).replace(/\\/g, '/');

    if (!fs.existsSync(localPath)) {
      console.log(`✗ 跳过(本地不存在): ${file}`);
      failed++;
      continue;
    }

    try {
      // 确保远程目录存在
      const remoteDir = path.dirname(remotePath).replace(/\\/g, '/');
      await ssh.execCommand(`mkdir -p ${remoteDir}`);

      await ssh.putFile(localPath, remotePath);
      const stat = fs.statSync(localPath);
      const sizeKB = (stat.size / 1024).toFixed(1);
      console.log(`✓ ${file} (${sizeKB}KB)`);
      uploaded++;
    } catch (err) {
      console.log(`✗ ${file}: ${err.message}`);
      failed++;
    }
  }

  console.log(`\n=== 完成 ===`);
  console.log(`成功: ${uploaded}, 失败: ${failed}`);

  ssh.dispose();
}

main().catch(err => {
  console.error('错误:', err.message);
  ssh.dispose();
  process.exit(1);
});
