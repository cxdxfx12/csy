// 通过 mysql 查本地 php 环境、OPcache 状态，并验证 Decoration.php 语法
const mysql = require('mysql2/promise');
const fs = require('fs');
const { execSync } = require('child_process');

(async () => {
    // 检查完整的 Decoration.php 有没有明显语法问题
    const content = fs.readFileSync('e:/ds/app/admin/controller/Decoration.php', 'utf8');
    
    // 检查 try-catch 括号匹配
    const lines = content.split('\n');
    
    let braceDepth = 0;
    let errors = [];
    let applyAddStart = -1;
    let applyAddEnd = -1;
    
    for (let i = 0; i < lines.length; i++) {
        const line = lines[i];
        if (line.includes('function applyAdd')) applyAddStart = i + 1;
        if (applyAddStart > 0 && applyAddEnd < 0 && line.trim().startsWith('public function ') && i+1 > applyAddStart) {
            applyAddEnd = i;
        }
        
        for (const ch of line) {
            if (ch === '{') braceDepth++;
            if (ch === '}') braceDepth--;
        }
    }
    if (applyAddEnd < 0) applyAddEnd = lines.length;
    
    console.log('applyAdd 方法: 行 ' + applyAddStart + ' ~ ' + applyAddEnd);
    console.log('文件 { } 深度:', braceDepth, (braceDepth === 0 ? '✅ 匹配' : '❌ 不匹配!'));
    
    // 打印 applyAdd 内容
    console.log('\n=== applyAdd 方法 ===');
    for (let i = applyAddStart - 1; i < Math.min(applyAddEnd, applyAddStart + 70); i++) {
        console.log(String(i+1).padStart(4) + ': ' + lines[i]);
    }
    
    console.log('\n=== 检查 PDOException 残留 ===');
    if (content.includes('PDOException')) {
        console.log('❌ 还有 PDOException 引用!');
        const idx = content.indexOf('PDOException');
        console.log('位置: 字符 ' + idx);
    } else {
        console.log('✅ 无 PDOException 残留');
    }
    
    // 检查语法问题 - 第58行附近的 try
    console.log('\n=== 第58行附近原文 ===');
    for (let i = 55; i < 70; i++) {
        console.log(String(i+1).padStart(4) + ': ' + lines[i]);
    }
    
})().catch(e => console.error(e));
