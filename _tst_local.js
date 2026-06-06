const mysql = require('mysql2/promise');

async function main() {
  const conn = await mysql.createConnection({
    host: '127.0.0.1', user: 'root', password: 'cxdxfx12', database: 'dasheng'
  });

  // room 表 community_id 分布
  const [rows] = await conn.execute(`
    SELECT community_id, COUNT(*) as cnt, GROUP_CONCAT(id ORDER BY id SEPARATOR ',') as room_ids
    FROM ds_room WHERE delete_time IS NULL
    GROUP BY community_id ORDER BY community_id
  `);
  console.log('=== Room distribution by community_id ===');
  rows.forEach(r => console.log(`  cid=${r.community_id}: ${r.cnt} rooms, ids=[${r.room_ids}]`));

  // 也看 owner_room 表
  const [rows2] = await conn.execute(`
    SELECT r.community_id, COUNT(DISTINCT r.id) as room_cnt
    FROM ds_room r
    LEFT JOIN ds_owner_room o ON o.room_id = r.id AND o.delete_time IS NULL
    WHERE r.delete_time IS NULL
    GROUP BY r.community_id ORDER BY r.community_id
  `);
  console.log('\n=== Room+OwnerRoom join by community_id ===');
  rows2.forEach(r => console.log(`  cid=${r.community_id}: ${r.room_cnt} rooms (with owner_room join)`));

  await conn.end();
}

main().catch(e => console.error('ERR:', e.message));
