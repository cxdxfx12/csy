const { Client } = require('ssh2');

const conn = new Client();
conn.on('ready', () => {
  console.log('SSH connected\n');
  // 简单方式：在 select() 函数的 return 之前加上一行日志
  const cmd = `
cd /www/wwwroot/www.hbdxm.com
cp app/admin/controller/Room.php app/admin/controller/Room.php.bak

# 用 sed 在 "$this->success($list); }" 前插入日志行
sed -i '/return \\$this->success(\\$list);/i\\
        file_put_contents('"'"/tmp/room_debug.log"'"', '"'"[ '"'"' . date('"'"'Y-m-d H:i:s'"'"') . '"'"' ] SELECT cid='"'"' . \\$communityId . '"'"' count='"'"' . count(\\$list) . '"'"' first3='"'"' . json_encode(array_slice(\\$list->toArray(),0,3),JSON_UNESCAPED_UNICODE) . '"'"'\\\\n"'"', FILE_APPEND);
' app/admin/controller/Room.php

echo '===PATCHED==='
grep -A2 'file_put_contents.*room_debug' app/admin/controller/Room.php
`;

  conn.exec(cmd, (err, stream) => {
    if (err) { console.error('Exec err:', err.message); conn.end(); return; }
    let out = '';
    stream.on('data', d => out += d.toString());
    stream.stderr.on('data', d => process.stderr.write(d));
    stream.on('close', () => { console.log(out); conn.end(); });
  });
}).on('error', e => console.error('SSH err:', e.message))
  .connect({
    host: '211.149.181.178',
    port: 22000,
    username: 'root',
    password: 'cxdxfx12',
    readyTimeout: 10000,
  });
