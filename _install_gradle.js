const https = require('https');
const http = require('http');
const fs = require('fs');
const path = require('path');
const { execSync } = require('child_process');

const ZIP_PATH = 'C:\\gdl-8.2.1.zip';
const GRADLE_DIR = 'C:\\gradle';
const MIRRORS = [
    'https://mirrors.huaweicloud.com/gradle/gradle-8.2.1-all.zip',
    'https://mirrors.cloud.tencent.com/gradle/gradle-8.2.1-all.zip',
];

function download(url, dest) {
    return new Promise((resolve, reject) => {
        console.log(`  下载: ${url}`);
        const file = fs.createWriteStream(dest);
        const proto = url.startsWith('https') ? https : http;
        const req = proto.get(url, { timeout: 120000 }, (res) => {
            if (res.statusCode >= 300 && res.statusCode < 400 && res.headers.location) {
                file.close(); try { fs.unlinkSync(dest); } catch(_) {}
                return download(res.headers.location, dest).then(resolve, reject);
            }
            if (res.statusCode !== 200) {
                file.close(); try { fs.unlinkSync(dest); } catch(_) {}
                return reject(new Error('HTTP ' + res.statusCode));
            }
            const total = parseInt(res.headers['content-length'] || '0', 10);
            let downloaded = 0;
            let lastPct = -1;
            res.on('data', (chunk) => {
                downloaded += chunk.length;
                if (total > 0) {
                    const pct = Math.round(downloaded / total * 100);
                    if (pct > lastPct) {
                        process.stdout.write('\r  进度: ' + pct + '% (' + Math.round(downloaded/1024/1024) + 'MB)');
                        lastPct = pct;
                    }
                }
            });
            res.pipe(file);
            file.on('finish', () => { file.close(); console.log('\r  下载完成!                    '); resolve(); });
        });
        req.on('timeout', () => { req.destroy(); file.close(); try { fs.unlinkSync(dest); } catch(_) {} reject(new Error('timeout')); });
        req.on('error', (e) => { file.close(); try { fs.unlinkSync(dest); } catch(_) {} reject(e); });
    });
}

async function main() {
    console.log('=== 安装 Gradle 8.2.1 ===\n');

    if (fs.existsSync(path.join(GRADLE_DIR, 'bin', 'gradle.bat'))) {
        console.log('Gradle 已安装。');
        return;
    }

    // Download
    if (!fs.existsSync(ZIP_PATH)) {
        console.log('[1/2] 下载 (约184MB, 请耐心等待)...');
        let ok = false;
        for (const url of MIRRORS) {
            try {
                await download(url, ZIP_PATH);
                ok = true;
                break;
            } catch(e) {
                console.log('\n  失败: ' + e.message);
            }
        }
        if (!ok) { console.error('所有镜像下载失败！'); process.exit(1); }
    } else {
        console.log('[1/2] 已有下载文件，跳过。');
    }

    // Extract using tar (Node.js built-in on Windows 10+)
    console.log('\n[2/2] 解压...');
    if (fs.existsSync(GRADLE_DIR)) {
        fs.rmSync(GRADLE_DIR, { recursive: true, force: true });
    }
    const tmpDir = 'C:\\gradle-tmp';
    if (fs.existsSync(tmpDir)) fs.rmSync(tmpDir, { recursive: true, force: true });
    fs.mkdirSync(tmpDir, { recursive: true });
    
    // Use 7zip if available, else PowerShell
    try {
        execSync('tar -xf "' + ZIP_PATH + '" -C "' + tmpDir + '"', { stdio: 'pipe', timeout: 120000 });
    } catch(e) {
        console.log('tar失败, 尝试PowerShell...');
        try { execSync('7z x "' + ZIP_PATH + '" -o"' + tmpDir + '" -y', { stdio: 'pipe', timeout: 120000 }); } 
        catch(e2) { execSync('powershell -Command "Expand-Archive -Path \'' + ZIP_PATH + '\' -DestinationPath \'' + tmpDir + '\' -Force"', { stdio: 'pipe', timeout: 120000 }); }
    }
    
    // Find and move the actual gradle dir
    const entries = fs.readdirSync(tmpDir);
    for (const e of entries) {
        const full = path.join(tmpDir, e);
        if (fs.statSync(full).isDirectory() && e.startsWith('gradle-')) {
            fs.renameSync(full, GRADLE_DIR);
            break;
        }
    }
    fs.rmSync(tmpDir, { recursive: true, force: true });
    
    if (!fs.existsSync(path.join(GRADLE_DIR, 'bin', 'gradle.bat'))) {
        console.error('解压后未找到 gradle.bat！');
        process.exit(1);
    }
    
    console.log('Gradle 安装完成! ' + GRADLE_DIR);
}

main().catch(e => { console.error('错误: ' + e.message); process.exit(1); });
