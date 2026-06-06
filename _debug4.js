// 用本地数据库生成有效token，然后测试服务器API
const mysql = require('mysql2/promise');
const { Client } = require('ssh2');
const crypto = require('crypto');

async function main() {
  // 1. 连接本地数据库获取用户信息
  const conn = await mysql.createConnection({ host:'127.0.0.1', user:'root', password:'cxdxfx12', database:'dasheng' });
  const [rows] = await conn.execute("SELECT id,username,salt FROM ds_admin WHERE username='admin' LIMIT 1");
  console.log('Admin user:', rows[0].id, rows[0].username);
  
  // 2. 生成JWT
  const auth_key = 'JUD6FCtZsqrmVXc2apev4TRn3O8gAhxbSlH9wfPN';
  const header = Buffer.from(JSON.stringify({alg:'HS256',typ:'JWT'})).toString('base64url');
  const payload = Buffer.from(JSON.stringify({
    admin_id: rows[0].id,
    exp: Math.floor(Date.now()/1000) + 3600,
    iat: Math.floor(Date.now()/1000)
  })).toString('base64url');
  const sig = crypto.createHmac('sha256', auth_key).update(header+'.'+payload).digest('base64url');
  const token = header+'.'+payload+'.'+sig;
  console.log('Token generated:', token.substring(0,50)+'...');
  await conn.end();

  // 3. SSH到服务器测试API
  const c = new Client();
  await new Promise((resolve, reject) => {
    c.on('ready', resolve);
    c.on('error', reject);
    c.connect({ host:'211.149.181.178', port:22000, username:'root', password:'cxdxfx12' });
  });

  // 测试applyAdd - 先获取一个有效的room_id和community_id
  const testCmd = `curl -s -X POST 'https://www.hbdxm.com/api/admin/decoration/applyAdd' \
    -H 'Content-Type: application/json' \
    -H 'Authorization: Bearer ${token}' \
    -d '{"community_id":1,"room_id":101,"owner_id":16,"company_name":"test","leader_name":"test","leader_phone":"13800138000","start_date":"2026-06-05","end_date":"2026-08-19","content":"test content","deposit_amount":"3000","manage_fee":"500","trash_fee":"100","other_fee":"50"}'`;
  
  c.exec(testCmd, (e, s) => {
    let out = '';
    s.on('data', d => out += d);
    s.on('close', () => {
      console.log('\n=== API Test Result ===');
      console.log(out);
      c.end();
    });
  });
}
main().catch(console.error);
