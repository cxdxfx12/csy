const https = require('https');
const fs = require('fs');
const path = require('path');
const { execSync } = require('child_process');

const JDK_DIR = 'C:\\jdk17';
const ZIP_PATH = 'C:\\jdk17.zip';

// Try multiple download sources
const SOURCES = [
    {
        name: 'Tsinghua Mirror',
        url: 'https://mirrors.tuna.tsinghua.edu.cn/Adoptium/17/jdk/x64/windows/OpenJDK17U-jdk_x64_windows_hotspot_17.0.14_7.zip',
    },
    {
        name: 'Huawei Cloud',
        getUrl: async () => {
            return new Promise((resolve, reject) => {
                const u = 'https://mirrors.huaweicloud.com/openjdk/17/';
                https.get(u, (res) => {
                    let data = '';
                    res.on('data', c => data += c);
                    res.on('end', () => {
                        const m = data.match(/href="(openjdk-17[^"]*windows[^"]*\.zip)"/i);
                        if (m) resolve(u + m[1]);
                        else reject(new Error('No JDK17 zip found on Huawei'));
                    });
                }).on('error', reject);
            });
        }
    },
    {
        name: 'Adoptium API',
        getUrl: async () => {
            return new Promise((resolve, reject) => {
                const u = 'https://api.adoptium.net/v3/assets/latest/17/hotspot?vendor=eclipse';
                https.get(u, { headers: { 'Accept': 'application/json' } }, (res) => {
                    let data = '';
                    res.on('data', c => data += c);
                    res.on('end', () => {
                        try {
                            const json = JSON.parse(data);
                            for (const bin of json) {
                                const pkg = bin.binaries.find(b => b.os === 'windows' && b.architecture === 'x64' && b.image_type === 'jdk');
                                if (pkg) return resolve(pkg.package.link);
                            }
                            reject(new Error('No matching JDK17 found'));
                        } catch(e) { reject(e); }
                    });
                }).on('error', reject);
            });
        }
    },
];

function download(url, dest) {
    return new Promise((resolve, reject) => {
        console.log(`  下载: ${url.substring(0, 80)}...`);
        const file = fs.createWriteStream(dest);
        const req = https.get(url, { timeout: 120000 }, (res) => {
            if (res.statusCode >= 300 && res.statusCode < 400 && res.headers.location) {
                file.close(); try { fs.unlinkSync(dest); } catch(_) {}
                return download(res.headers.location, dest).then(resolve, reject);
            }
            if (res.statusCode !== 200) {
                file.close(); try { fs.unlinkSync(dest); } catch(_) {}
                return reject(new Error('HTTP ' + res.statusCode));
            }
            const total = parseInt(res.headers['content-length'] || '0', 10);
            let downloaded = 0, lastPct = -1;
            res.on('data', (chunk) => {
                downloaded += chunk.length;
                if (total > 0) {
                    const pct = Math.round(downloaded / total * 100);
                    if (pct > lastPct) { process.stdout.write('\r  进度: ' + pct + '% (' + Math.round(downloaded/1024/1024) + 'MB)'); lastPct = pct; }
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
    console.log('=== 安装 JDK 17 ===\n');

    if (fs.existsSync(path.join(JDK_DIR, 'bin', 'java.exe'))) {
        console.log('JDK 17 已安装，验证版本:');
        const out = execSync('"' + path.join(JDK_DIR, 'bin', 'java') + '" -version 2>&1', { encoding: 'utf8' });
        console.log(out.trim());
        return;
    }

    // Download
    if (!fs.existsSync(ZIP_PATH)) {
        console.log('[1/2] 下载 JDK 17 (约180MB)...');
        let ok = false;
        for (const src of SOURCES) {
            try {
                let url = src.url;
                if (!url && src.getUrl) url = await src.getUrl();
                console.log(`  尝试: ${src.name}`);
                await download(url, ZIP_PATH);
                ok = true; break;
            } catch(e) {
                console.log(`\n  ${src.name} 失败: ${e.message}`);
            }
        }
        if (!ok) { console.error('所有源下载失败！'); process.exit(1); }
    } else {
        console.log('[1/2] 已有下载文件，跳过。');
    }

    // Extract
    console.log('\n[2/2] 解压...');
    if (fs.existsSync(JDK_DIR)) fs.rmSync(JDK_DIR, { recursive: true, force: true });
    const tmp = 'C:\\jdk17-tmp';
    if (fs.existsSync(tmp)) fs.rmSync(tmp, { recursive: true, force: true });
    fs.mkdirSync(tmp, { recursive: true });

    try { execSync('tar -xf "' + ZIP_PATH + '" -C "' + tmp + '"', { stdio: 'pipe', timeout: 120000 }); }
    catch(e) { execSync('powershell -Command "Expand-Archive -Path \'' + ZIP_PATH + '\' -DestinationPath \'' + tmp + '\' -Force"', { stdio: 'pipe', timeout: 120000 }); }

    // Find the actual JDK dir
    function findJdk(dir, depth) {
        if (depth > 3) return null;
        const entries = fs.readdirSync(dir);
        for (const e of entries) {
            const full = path.join(dir, e);
            if (!fs.statSync(full).isDirectory()) continue;
            if (fs.existsSync(path.join(full, 'bin', 'java.exe'))) return full;
            const found = findJdk(full, depth + 1);
            if (found) return found;
        }
        return null;
    }
    const jdkDir = findJdk(tmp, 0);
    if (jdkDir) { fs.renameSync(jdkDir, JDK_DIR); }
    else { console.error('解压后未找到 JDK！'); process.exit(1); }
    fs.rmSync(tmp, { recursive: true, force: true });

    console.log('JDK 17 安装完成: ' + JDK_DIR);
    execSync('"' + path.join(JDK_DIR, 'bin', 'java') + '" -version 2>&1', { stdio: 'inherit' });
}

main().catch(e => { console.error('错误: ' + e.message); process.exit(1); });
