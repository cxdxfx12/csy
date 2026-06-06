const { Client } = require('ssh2');

const conn = new Client();
conn.on('ready', () => {
  console.log('SSH connected');
  // 直接查 room 表的 community_id 分布
  conn.exec(`mysql -u root -pcxdxfx12 dasheng -e "
    SELECT community_id, COUNT(*) as cnt, GROUP_CONCAT(id ORDER BY id SEPARATOR ',') as room_ids
    FROM ds_room WHERE delete_time IS NULL
    GROUP BY community_id ORDER BY community_id;
  "`, (err, stream) => {
    if (err) { console.error('Exec err:', err.message); conn.end(); return; }
    let out = '';
    stream.on('data', d => out += d.toString());
    stream.stderr.on('data', d => process.stderr.write(d));
    stream.on('close', () => { console.log(out); conn.end(); });
  });
}).on('error', e => console.error('SSH err:', e.message))
  .connect({
    host: '211.149.181.178',
    port: 22,
    username: 'root',
    password: 'Dsxj@2024#',
    readyTimeout: 10000,
  });

setTimeout(() => { console.log('Timeout'); process.exit(1); }, 15000);
