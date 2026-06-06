const { Client } = require('ssh2');

const conn = new Client();
conn.on('ready', () => {
  console.log('SSH connected\n');
  const cids = [8, 9, 10, 11];
  const queries = cids.map(cid => 
    `echo "--- cid=${cid} ---" && mysql -u root -p'cxdxfx12' dasheng -N -e "SELECT COUNT(*) FROM ds_room r LEFT JOIN ds_owner_room ocr ON ocr.room_id = r.id AND ocr.delete_time IS NULL LEFT JOIN ds_owner o ON o.id = ocr.owner_id AND o.delete_time IS NULL WHERE r.delete_time IS NULL AND r.community_id = ${cid} GROUP BY r.id"`
  ).join(' && ');
  conn.exec(queries, (err, stream) => {
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
