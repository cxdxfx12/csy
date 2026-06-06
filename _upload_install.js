/**
 * 上传文件并安装数据库 - 使用原生 ssh2 库
 */
const { Client } = require('ssh2');
const fs = require('fs');
const path = require('path');

const HOST = '211.149.181.178';
const PORT = 22000;
const USER = 'root';
const PASS = 'hbyscycxdxfx12';
const SITE_ROOT = '/www/wwwroot/www.hbdxm.com';

const conn = new Client();

conn.on('ready', () => {
    console.log('[OK] SSH 连接成功');

    // 上传文件列表
    const files = [
        {
            local: 'e:/ds/app/admin/controller/Decoration.php',
            remote: SITE_ROOT + '/app/admin/controller/Decoration.php',
            name: 'Decoration.php',
        },
        {
            local: 'e:/ds/public/_install_decoration.php',
            remote: SITE_ROOT + '/public/_install_decoration.php',
            name: '_install_decoration.php',
        },
    ];

    let uploaded = 0;

    function uploadNext() {
        if (uploaded >= files.length) {
            // 全部上传完毕，执行安装
            console.log('\n[3] 执行数据库安装...');
            conn.exec(
                'cd ' + SITE_ROOT + '/public && php _install_decoration.php 2>&1',
                (err, stream) => {
                    if (err) { console.log('exec err:', err); conn.end(); return; }
                    let out = '';
                    stream.on('data', (d) => { out += d.toString(); });
                    stream.stderr.on('data', (d) => { out += d.toString(); });
                    stream.on('close', (code) => {
                        console.log(out || '(empty)');
                        console.log('退出码:', code);

                        // 验证表
                        console.log('\n[4] 验证数据库表...');
                        conn.exec(
                            'mysql -uroot -pcxdxfx12 dasheng -e "SHOW TABLES LIKE \'ds_decoration%\';" 2>&1',
                            (err2, stream2) => {
                                if (err2) { console.log(err2); conn.end(); return; }
                                let out2 = '';
                                stream2.on('data', (d) => { out2 += d.toString(); });
                                stream2.stderr.on('data', (d) => { out2 += d.toString(); });
                                stream2.on('close', () => {
                                    console.log(out2 || '(empty)');
                                    console.log('\n[DONE] 全部完成！');
                                    conn.end();
                                });
                            }
                        );
                    });
                }
            );
            return;
        }

        const file = files[uploaded];
        console.log(`\n[${uploaded + 2}] 上传 ${file.name}...`);
        
        conn.sftp((err, sftp) => {
            if (err) { console.log('SFTP err:', err); conn.end(); return; }
            sftp.fastPut(file.local, file.remote, (err2) => {
                if (err2) {
                    console.log('  上传失败:', err2.message);
                } else {
                    console.log('  [OK] ' + file.remote);
                }
                uploaded++;
                uploadNext();
            });
        });
    }

    uploadNext();
});

conn.on('error', (err) => {
    console.error('[FATAL]', err.message);
    process.exit(1);
});

conn.connect({
    host: HOST,
    port: PORT,
    username: USER,
    password: PASS,
    readyTimeout: 15000,
    tryKeyboard: true,
    algorithms: {
        kex: [
            'ecdh-sha2-nistp256',
            'ecdh-sha2-nistp384',
            'ecdh-sha2-nistp521',
            'diffie-hellman-group-exchange-sha256',
            'diffie-hellman-group14-sha256',
            'diffie-hellman-group14-sha1',
            'diffie-hellman-group-exchange-sha1',
            'diffie-hellman-group1-sha1',
        ],
        cipher: [
            'aes128-ctr',
            'aes192-ctr',
            'aes256-ctr',
            'aes128-gcm@openssh.com',
            'aes256-gcm@openssh.com',
            'aes128-cbc',
            'aes192-cbc',
            'aes256-cbc',
            '3des-cbc',
        ],
        serverHostKey: [
            'ssh-rsa',
            'ssh-dss',
            'ecdsa-sha2-nistp256',
            'ecdsa-sha2-nistp384',
            'ecdsa-sha2-nistp521',
            'ssh-ed25519',
        ],
    },
});
