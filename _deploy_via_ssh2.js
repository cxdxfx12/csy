// 用 ssh2 的 keyboard-interactive 认证尝试
const { Client } = require('ssh2');
const fs = require('fs');

const conn = new Client();
let authTried = false;

conn.on('keyboard-interactive', (name, instructions, lang, prompts, finish) => {
    // 处理 keyboard-interactive 认证
    finish(['hbyscycxdxfx12']);
});

conn.on('ready', () => {
    console.log('[OK] SSH 连接成功!');
    
    // 1. 验证服务器数据库是否有 decoration 表
    console.log('[1] 检查服务器数据库表...');
    conn.exec('mysql -uroot -pcxdxfx12 dasheng -e "SHOW TABLES LIKE \'ds_decoration%\';" 2>&1', (err, stream) => {
        if (err) { console.log('exec err:', err); conn.end(); return; }
        stream.on('data', d => process.stdout.write(d.toString()));
        stream.stderr.on('data', d => process.stdout.write(d.toString()));
        stream.on('close', () => {
            // 2. 上传文件
            console.log('\n[2] 上传文件...');
            conn.sftp((err, sftp) => {
                if (err) { console.log('sftp err:', err); conn.end(); return; }
                
                const files = [
                    ['e:/ds/app/admin/controller/Decoration.php', '/www/wwwroot/www.hbdxm.com/app/admin/controller/Decoration.php'],
                    ['e:/ds/public/_install_decoration.php', '/www/wwwroot/www.hbdxm.com/public/_install_decoration.php'],
                ];
                
                let done = 0;
                files.forEach(([local, remote]) => {
                    const name = local.split('/').pop();
                    sftp.fastPut(local, remote, (err) => {
                        if (err) console.log(`  ❌ ${name}: ${err.message}`);
                        else console.log(`  ✅ ${name} -> ${remote}`);
                        done++;
                        if (done === files.length) {
                            // 3. 执行建表 + 验证
                            console.log('\n[3] 执行 _install_decoration.php...');
                            conn.exec('cd /www/wwwroot/www.hbdxm.com/public && php _install_decoration.php 2>&1', (err, stream) => {
                                if (err) { console.log(err); conn.end(); return; }
                                stream.on('data', d => process.stdout.write(d.toString()));
                                stream.stderr.on('data', d => process.stdout.write(d.toString()));
                                stream.on('close', () => {
                                    console.log('\n[4] 再次验证表...');
                                    conn.exec('mysql -uroot -pcxdxfx12 dasheng -e "SHOW TABLES LIKE \'ds_decoration%\';" 2>&1', (err, stream) => {
                                        if (err) { console.log(err); conn.end(); return; }
                                        stream.on('data', d => process.stdout.write(d.toString()));
                                        stream.stderr.on('data', d => process.stdout.write(d.toString()));
                                        stream.on('close', () => {
                                            console.log('\n[DONE]');
                                            conn.end();
                                        });
                                    });
                                });
                            });
                        }
                    });
                });
            });
        });
    });
});

conn.on('error', (err) => {
    console.error('[FATAL]', err.message, '(level:', err.level, ')');
    process.exit(1);
});

conn.connect({
    host: '211.149.181.178',
    port: 22000,
    username: 'root',
    password: 'hbyscycxdxfx12',
    readyTimeout: 20000,
    tryKeyboard: true,
});
