const ftp = require('basic-ftp');
const path = require('path');
const fs = require('fs');

const localPublic = path.join(__dirname, 'public');
const toUpload = [
  'owner.html', 'staff.html', 'manager.html',
  'assets/owner-wsvGkWrd.js', 'assets/staff-Cn5g9Yxy.js', 'assets/manager-BDosXLxi.js',
  'assets/GlobalToast-D2uP8LqL.js', 'assets/pinia-D6b2HoYy.js',
  'assets/GlobalToast-C359LiT5.css', 'assets/owner-D3LvmrpX.css',
  'assets/staff-0tWMjREl.css', 'assets/manager-DkVrqPKw.css',
];

async function main() {
  const client = new ftp.Client();
  client.ftp.verbose = true;

  try {
    console.log('=== 连接 FTP 服务器 ===');
    await client.access({
      host: '211.149.181.178',
      port: 21,
      user: 'root',
      password: 'cxdxfx12',
      secure: false,
    });
    console.log('✓ FTP 连接成功\n');

    // 列出根目录，查找 web 根目录
    console.log('=== 查找 Web 根目录 ===');
    let webRoot = null;
    
    // 试试常见的路径
    const dirsToCheck = ['/www/wwwroot/www.hbdxm.com', '/www/wwwroot/hbdxm.com',
      '/www/wwwroot/default', '/www/server/nginx/html', '/var/www/html'];

    for (const dir of dirsToCheck) {
      try {
        await client.send('CWD ' + dir);
        const list = await client.list();
        const hasOwner = list.some(f => f.name === 'owner.html');
        if (hasOwner) {
          webRoot = dir;
          console.log('✓ 找到 Web 根目录: ' + webRoot);
          break;
        }
      } catch (e) {
        // 目录不存在，继续
      }
    }

    if (!webRoot) {
      // 列出根目录看看有什么
      console.log('  根目录内容:');
      const rootList = await client.list();
      rootList.forEach(f => console.log('    ' + (f.isDirectory ? '[DIR]' : '[FILE]') + ' ' + f.name));
      
      // 尝试搜索
      for (const f of rootList) {
        if (f.isDirectory) {
          try {
            const subList = await client.list(f.name);
            for (const sf of subList) {
              if (sf.isDirectory && (sf.name === 'wwwroot' || sf.name === 'www')) {
                console.log('  发现目录: /' + f.name + '/' + sf.name);
              }
            }
          } catch(e) {}
        }
      }
      
      console.log('✗ 未能自动定位 Web 根目录');
      client.close();
      return;
    }

    // 上传文件
    console.log('\n=== 上传文件 ===');
    let ok = 0, fail = 0;

    for (const file of toUpload) {
      const localPath = path.join(localPublic, file);
      if (!fs.existsSync(localPath)) {
        console.log('✗ 跳过(本地不存在): ' + file);
        fail++;
        continue;
      }

      try {
        // 确保远程目录存在
        const remoteDir = path.posix.dirname(file);
        if (remoteDir !== '.') {
          await client.ensureDir(webRoot + '/' + remoteDir);
        }
        await client.cd(webRoot);
        
        const stat = fs.statSync(localPath);
        const sizeKB = (stat.size / 1024).toFixed(1);
        await client.uploadFrom(localPath, file);
        console.log('✓ ' + file + ' (' + sizeKB + 'KB)');
        ok++;
      } catch (err) {
        console.log('✗ ' + file + ': ' + err.message);
        fail++;
      }
    }

    console.log('\n=== 完成 ===');
    console.log('成功: ' + ok + ', 失败: ' + fail);
  } catch (err) {
    console.error('错误:', err.message);
  }
  client.close();
}

main();
