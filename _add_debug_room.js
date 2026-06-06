const { Client } = require('ssh2');

const conn = new Client();
conn.on('ready', () => {
  console.log('SSH connected\n');
  // 直接修改服务器 Room.php 的 select() 方法加日志
  const phpPatch = `
    public function select()
    {
        $communityId = $this->request->param('community_id', 0);
        $buildingId  = $this->request->param('building_id', 0);
        // DEBUG
        file_put_contents('/tmp/room_select.log', date('H:i:s') . ' community_id=' . $communityId . ' ip=' . ($_SERVER['REMOTE_ADDR'] ?? '') . '\\n', FILE_APPEND);
        $list = Db::name('room')->alias('r')
            ->leftJoin('owner_room ocr', 'ocr.room_id = r.id AND ocr.delete_time IS NULL')
            ->leftJoin('owner o', 'o.id = ocr.owner_id AND o.delete_time IS NULL')
            ->whereNull('r.delete_time')
            ->where(function($q) use ($communityId, $buildingId) {
                if ($communityId) $q->where('r.community_id', '=', intval($communityId));
                if ($buildingId)  $q->where('r.building_id', '=', intval($buildingId));
                file_put_contents('/tmp/room_select.log', '  WHERE community_id=' . intval($communityId) . ' building_id=' . intval($buildingId) . '\\n', FILE_APPEND);
            })
            ->field('r.id, r.room_number, r.building_name, r.unit, r.floor, r.area, o.realname as owner_name, ocr.owner_id')
            ->group('r.id')
            ->select();
        file_put_contents('/tmp/room_select.log', '  RESULT count=' . count($list) . ' sample=' . json_encode(array_slice($list->toArray(), 0, 3)) . '\\n', FILE_APPEND);
        return $this->success($list);
    }
  `;
  // Use sed to replace the function
  const cmd = `cat > /tmp/patch.txt << 'EOF'
    public function select()
    {
        \\$communityId = \\$this->request->param('community_id', 0);
        \\$buildingId  = \\$this->request->param('building_id', 0);
        file_put_contents('/tmp/room_select.log', date('H:i:s') . ' community_id=' . \\$communityId . ' ip=' . (\\$_SERVER['REMOTE_ADDR'] ?? '') . \"\\n\", FILE_APPEND);
        \\$list = Db::name('room')->alias('r')
            ->leftJoin('owner_room ocr', 'ocr.room_id = r.id AND ocr.delete_time IS NULL')
            ->leftJoin('owner o', 'o.id = ocr.owner_id AND o.delete_time IS NULL')
            ->whereNull('r.delete_time')
            ->where(function(\\$q) use (\\$communityId, \\$buildingId) {
                if (\\$communityId) \\$q->where('r.community_id', '=', intval(\\$communityId));
                if (\\$buildingId)  \\$q->where('r.building_id', '=', intval(\\$buildingId));
            })
            ->field('r.id, r.room_number, r.building_name, r.unit, r.floor, r.area, o.realname as owner_name, ocr.owner_id')
            ->group('r.id')
            ->select();
        file_put_contents('/tmp/room_select.log', '  count=' . count(\\$list) . \"\\n\", FILE_APPEND);
        return \\$this->success(\\$list);
    }
EOF
# 备份
cp /www/wwwroot/www.hbdxm.com/app/admin/controller/Room.php /tmp/Room.php.bak
# Replace from 'public function select()' to 'return $this->success($list); }'
python3 -c "
import re
content = open('/www/wwwroot/www.hbdxm.com/app/admin/controller/Room.php').read()
old = re.search(r'public function select\\(\\)[^{]*\\{.*?return \\$this->success\\(\\$list\\);\\s*\\}', content, re.DOTALL)
if old:
    print('Found old select() at pos', old.start(), 'to', old.end())
    print('Old:', old.group()[:200])
else:
    print('NOT FOUND old select')
" 2>&1`;
  conn.exec(cmd, (err, stream) => {
    if (err) { console.error('Exec err:', err.message); conn.end(); return; }
    let out = '';
    stream.on('data', d => out += d.toString());
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
