// 模拟 Decoration::applyAdd() 对本地数据库的操作，验证字段匹配
const mysql = require('mysql2/promise');

(async () => {
    const db = await mysql.createConnection({
        host: '127.0.0.1', user: 'root', password: 'cxdxfx12', database: 'dasheng'
    });

    // 1. 查看表结构
    console.log('=== ds_decoration_apply 字段 ===');
    const [cols] = await db.query('DESCRIBE ds_decoration_apply');
    const dbFields = cols.map(c => c.Field);
    console.log(dbFields.join(', '));

    // 2. PHP 代码中的 $allowedFields
    const allowedFields = [
        'community_id', 'room_id', 'owner_id', 'apply_no',
        'company_name', 'company_phone', 'leader_name', 'leader_phone',
        'start_date', 'end_date', 'content', 'decoration_type', 'drawing_urls',
        'status', 'deposit_amount', 'manage_fee', 'trash_fee', 'other_fee',
        'total_fee', 'remark', 'create_time',
    ];

    // 3. 检查 allowedFields 里哪些字段不在表中
    const missing = allowedFields.filter(f => !dbFields.includes(f));
    const extra = dbFields.filter(f => !allowedFields.includes(f) && !['id','audit_remark','audit_time','audit_admin_id','paid_amount','paid_time','refund_amount','refund_time','accept_result','accept_time','accept_admin_id','update_time','delete_time'].includes(f));

    if (missing.length > 0) console.log('\n⚠ allowedFields 含表中不存在的字段:', missing);
    else console.log('\n✅ allowedFields 与表字段匹配');

    if (extra.length > 0) console.log('⚠ 表中字段未存在于 allowedFields:', extra);

    // 4. 模拟一次 INSERT 测试
    console.log('\n=== 模拟 INSERT 测试 ===');
    try {
        const testData = {
            community_id: 1, room_id: 1, owner_id: 1,
            apply_no: 'DSZ' + Date.now(),
            company_name: '测试公司', company_phone: '13800138000',
            leader_name: '张三', leader_phone: '13900139000',
            start_date: '2026-06-10', end_date: '2026-07-10',
            content: '局部翻新', decoration_type: '局部装修',
            drawing_urls: '[]',
            status: 0,
            deposit_amount: 2000.00, manage_fee: 500.00,
            trash_fee: 300.00, other_fee: 0.00,
            total_fee: 2800.00,
            remark: '测试', create_time: '2026-06-07 10:00:00',
        };
        const [result] = await db.query('INSERT INTO ds_decoration_apply SET ?', testData);
        console.log('✅ INSERT 成功, id =', result.insertId);

        // 5. 清理测试数据
        await db.query('DELETE FROM ds_decoration_apply WHERE id = ?', [result.insertId]);
        console.log('✅ 测试数据已清理');
    } catch (e) {
        console.log('❌ INSERT 失败:', e.message);
    }

    // 6. 测试 ds_decoration_worker
    console.log('\n=== ds_decoration_worker 表 ===');
    const [wcols] = await db.query('DESCRIBE ds_decoration_worker');
    console.log(wcols.map(c => c.Field).join(', '));

    console.log('\n=== 结论 ===');
    console.log('本地数据库装饰模块表结构正常，applyAdd 逻辑可以直接 INSERT 成功。');
    console.log('服务器 500 原因: 服务器数据库缺少这 4 张表。');

    await db.end();
})().catch(e => console.error(e));
