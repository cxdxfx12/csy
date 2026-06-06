// SSH 执行远程 MySQL 创建装修表
const { execSync } = require('child_process');
const fs = require('fs');
const path = require('path');

const HOST = '211.149.181.178';
const PORT = '22000';
const USER = 'root';
const sqlContent = fs.readFileSync(path.join(__dirname, '_decoration_module.sql'), 'utf8');

function run(cmd, input) {
  console.log('>>>', cmd.substring(0, 100));
  try {
    const opts = { encoding: 'utf8', timeout: 20000, stdio: 'pipe' };
    if (input) opts.input = input;
    const r = execSync(cmd, opts);
    console.log(r);
    return r;
  } catch (e) {
    console.log('STDERR:', (e.stderr || '').substring(0, 500));
    console.log('STDOUT:', (e.stdout || '').substring(0, 500));
    return '';
  }
}

// 先看看表在不在
console.log('=== 检查表是否存在 ===');
run(`ssh -p ${PORT} -o StrictHostKeyChecking=no -o ConnectTimeout=10 ${USER}@${HOST} "mysql -uroot -pcxdxfx12 dasheng -e 'SHOW TABLES LIKE \\\"ds_decoration%\\\";'"`);

// 执行 SQL 创建表
console.log('\n=== 执行建表 SQL ===');
run(`ssh -p ${PORT} -o StrictHostKeyChecking=no -o ConnectTimeout=10 ${USER}@${HOST} "mysql -uroot -pcxdxfx12 dasheng"`, sqlContent);

// 验证
console.log('\n=== 验证结果 ===');
run(`ssh -p ${PORT} -o StrictHostKeyChecking=no -o ConnectTimeout=10 ${USER}@${HOST} "mysql -uroot -pcxdxfx12 dasheng -e 'SHOW TABLES LIKE \\\"ds_decoration%\\\"; SELECT \\\"MENUS\\\" as _; SELECT id,name FROM ds_menu WHERE id BETWEEN 200 AND 204;'"`);

console.log('\n=== DONE ===');
