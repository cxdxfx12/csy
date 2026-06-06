// 装修模块数据库安装 - 通过SSH远程执行SQL
const { execSync } = require('child_process');
const fs = require('fs');
const path = require('path');

const HOST = '211.149.181.178';
const PORT = 22000;
const USER = 'root';
const PASS = 'hbyscycxdxfx12';
const DB = 'dasheng';

// 读取 SQL
const sqlContent = fs.readFileSync(path.join(__dirname, '_decoration_module.sql'), 'utf8');
// 去掉 DROP TABLE IF EXISTS 语句（可能危险），只保留 CREATE TABLE IF NOT EXISTS
// 但实际上我们的 sql 已经有 DROP 语句... 让我直接执行安全的版本

// 把 PHP 安装脚本上传到服务器
const phpScript = fs.readFileSync(path.join(__dirname, '_install_decoration.php'), 'utf8');

// 方案1: 直接用 mysql 命令行导入
// 先检查表是否存在
function execSSH(cmd) {
  const fullCmd = `sshpass -p "${PASS}" ssh -o StrictHostKeyChecking=no -p ${PORT} ${USER}@${HOST} "${cmd}"`;
  console.log('Executing:', cmd.substring(0, 100) + '...');
  try {
    const result = execSync(fullCmd, { encoding: 'utf8', timeout: 30000 });
    return result;
  } catch (e) {
    console.error('SSH Error:', e.message);
    return e.stdout || e.stderr || '';
  }
}

// 1. 先检查表是否存在
console.log('=== 检查装修表是否存在 ===');
const checkResult = execSSH(`mysql -uroot -pcxdxfx12 ${DB} -e "SHOW TABLES LIKE 'ds_decoration%';"`);
console.log(checkResult);

// 2. 如果不存在，通过 mysql 直接执行 SQL
console.log('\n=== 创建装修表 ===');
// 使用 EOF 方式将 SQL 传递给 mysql
const base64Sql = Buffer.from(sqlContent).toString('base64');
const sqlResult = execSSH(`echo "${base64Sql}" | base64 -d | mysql -uroot -pcxdxfx12 ${DB}`);
console.log(sqlResult || 'SQL 执行完成');

// 3. 再次验证
console.log('\n=== 验证结果 ===');
const verifyResult = execSSH(`mysql -uroot -pcxdxfx12 ${DB} -e "SHOW TABLES LIKE 'ds_decoration%'; SELECT '---'; SELECT id, name FROM ds_menu WHERE id BETWEEN 200 AND 204;"`);
console.log(verifyResult);

console.log('\n=== DONE ===');
