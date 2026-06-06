const { Client } = require('ssh2');

const conn = new Client();
conn.on('ready', () => {
  console.log('SSH connected\n');
  // 看服务器 Room.php 的 select() 方法
  const cmd = `grep -n 'function select\|community_id\|param\|$request->param\|$this->request->param' /www/wwwroot/www.hbdxm.com/app/admin/controller/Room.php`;
  conn.exec(cmd, (err, stream) => {
    if (err) { console.error('Exec err:', err.message); conn.end(); return; }
    let out = '';
    stream.on('data', d => out += d.toString());
    stream.stderr.on('data', d => process.stderr.write(d));
    stream.on('close', () => {
      console.log(out);
      // Also try PHP CLI to test query directly
      const cmd2 = `cat > /tmp/test_room.php << 'PHPEOF'
<?php
require '/www/wwwroot/www.hbdxm.com/vendor/autoload.php';
\\think\\facade\\Db::setConfig([
    'default' => 'mysql',
    'connections' => [
        'mysql' => [
            'type' => 'mysql',
            'hostname' => '127.0.0.1',
            'database' => 'dasheng',
            'username' => 'root',
            'password' => 'cxdxfx12',
            'hostport' => '3306',
            'prefix' => 'ds_',
            'charset' => 'utf8mb4',
        ]
    ]
]);
\\$db = \\think\\facade\\Db::name('room');
foreach ([8, 9, 10, 11] as \\$cid) {
    \\$sql = \\$db->alias('r')
        ->leftJoin('owner_room ocr', 'ocr.room_id = r.id AND ocr.delete_time IS NULL')
        ->leftJoin('owner o', 'o.id = ocr.owner_id AND o.delete_time IS NULL')
        ->whereNull('r.delete_time')
        ->where('r.community_id', '=', \\$cid)
        ->field('r.id, r.room_number')
        ->group('r.id')
        ->limit(3)
        ->fetchSql(true)
        ->select();
    echo "cid=\\$cid SQL: \\$sql\\n\\n";
}
PHPEOF
php /tmp/test_room.php 2>&1`;
      conn.exec(cmd2, (err2, stream2) => {
        let out2 = '';
        stream2.on('data', d => out2 += d.toString());
        stream2.stderr.on('data', d => process.stderr.write(d));
        stream2.on('close', () => { console.log('\n===SQL TEST===\n' + out2); conn.end(); });
      });
    });
  });
}).on('error', e => console.error('SSH err:', e.message))
  .connect({
    host: '211.149.181.178',
    port: 22000,
    username: 'root',
    password: 'cxdxfx12',
    readyTimeout: 15000,
  });
