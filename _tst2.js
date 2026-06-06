const { Client } = require('ssh2');
const c = new Client();
c.on('ready', () => {
  c.exec('mysql -uroot -pcxdxfx12 dasheng -e "SELECT id, name, status FROM ds_community LIMIT 20"', (e, s) => {
    let o = ''; s.stderr.on('data', d => o += d); s.on('data', d => o += d);
    s.on('close', () => { console.log(o); 
      c.exec('mysql -uroot -pcxdxfx12 dasheng -e "SELECT community_id, COUNT(*) as cnt FROM ds_room WHERE delete_time IS NULL GROUP BY community_id"', (e2, s2) => {
        let o2 = ''; s2.stderr.on('data', d => o2 += d); s2.on('data', d => o2 += d);
        s2.on('close', () => { console.log(o2); c.end(); setTimeout(() => process.exit(0), 300); });
      });
    });
  });
}).connect({ host:'211.149.181.178', port:22000, username:'root', password:'cxdxfx12', readyTimeout:10000 });
