const { Client } = require('ssh2');

const conn = new Client();
conn.on('ready', () => {
  console.log('SSH connected\n');
  // 备份 + 修改
  const cmd = `
cp /www/wwwroot/www.hbdxm.com/app/admin/controller/Room.php /tmp/Room.php.bak
head -136 /www/wwwroot/www.hbdxm.com/app/admin/controller/Room.php > /tmp/Room.head.php
tail -n +154 /www/wwwroot/www.hbdxm.com/app/admin/controller/Room.php > /tmp/Room.tail.php
cat /tmp/Room.head.php > /www/wwwroot/www.hbdxm.com/app/admin/controller/Room.php
cat >> /www/wwwroot/www.hbdxm.com/app/admin/controller/Room.php << 'FIXEOF'
    {
        $communityId = $this->request->param('community_id', 0);
        $buildingId  = $this->request->param('building_id', 0);
        // DEBUG LOG
        file_put_contents(
            '/tmp/room_sel.log',
            date('H:i:s') . ' cid=' . $communityId . ' bid=' . $buildingId . ' ip=' . ($_SERVER['REMOTE_ADDR'] ?? 'cli') . "\n",
            FILE_APPEND
        );
        $query = Db::name('room')->alias('r')
            ->leftJoin('owner_room ocr', 'ocr.room_id = r.id AND ocr.delete_time IS NULL')
            ->leftJoin('owner o', 'o.id = ocr.owner_id AND o.delete_time IS NULL')
            ->whereNull('r.delete_time')
            ->where(function($q) use ($communityId, $buildingId) {
                if ($communityId) $q->where('r.community_id', '=', intval($communityId));
                if ($buildingId)  $q->where('r.building_id', '=', intval($buildingId));
            })
            ->field('r.id, r.room_number, r.building_name, r.unit, r.floor, r.area, o.realname as owner_name, ocr.owner_id')
            ->group('r.id');
        // DEBUG: get SQL
        $sql = $query->fetchSql(true)->select();
        file_put_contents('/tmp/room_sel.log', '  SQL=' . $sql . "\n", FILE_APPEND);
        
        $list = $query->select();
        file_put_contents('/tmp/room_sel.log', '  count=' . count($list) . ' first3=' . json_encode(array_slice($list->toArray(), 0, 3), JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND);
        return $this->success($list);
    }
FIXEOF
cat /tmp/Room.tail.php >> /www/wwwroot/www.hbdxm.com/app/admin/controller/Room.php
# 清除旧日志
rm -f /tmp/room_sel.log
# 验证
grep -c 'room_sel.log' /www/wwwroot/www.hbdxm.com/app/admin/controller/Room.php && echo " OK: log lines found"
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
