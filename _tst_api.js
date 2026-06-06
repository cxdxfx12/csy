const https = require('https');

// 先登录拿 token
function req(path, token) {
  return new Promise((resolve, reject) => {
    const opts = {
      hostname: 'www.hbdxm.com',
      path: path,
      headers: token ? { 'Authorization': 'Bearer ' + token, 'Accept': 'application/json' } : { 'Accept': 'application/json' },
    };
    https.get(opts, res => {
      let d = '';
      res.on('data', c => d += c);
      res.on('end', () => resolve({ status: res.statusCode, data: JSON.parse(d) }));
    }).on('error', reject);
  });
}

async function main() {
  // 登录
  const loginData = JSON.stringify({ username: 'admin', password: 'cxdxfx12' });
  const token = await new Promise((resolve, reject) => {
    const opts = {
      hostname: 'www.hbdxm.com',
      path: '/api/admin/login',
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Content-Length': loginData.length },
    };
    const req = https.request(opts, res => {
      let d = '';
      res.on('data', c => d += c);
      res.on('end', () => {
        try { resolve(JSON.parse(d).data.token); } catch(e) { reject(d); }
      });
    });
    req.on('error', reject);
    req.write(loginData);
    req.end();
  });

  console.log('Token:', token?.substring(0, 20) + '...');

  // 测试不同 community_id 的 room/select
  for (const cid of [8, 9, 10, 11, 12, 13]) {
    const r = await req('/api/admin/room/select?community_id=' + cid, token);
    const count = r.data?.data?.length ?? r.data?.length ?? 0;
    const first3 = (r.data?.data ?? r.data ?? []).slice(0, 3).map(x => `${x.id}:${x.room_number}(cid:${x.community_id})`);
    console.log(`\ncid=${cid} → ${count} rooms, first3:`, JSON.stringify(first3));
  }
}

main().catch(e => console.error('ERR:', e.message || e));
