<?php
// 大圣物业管理系统 - 安装向导
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>大圣物业管理系统 - 安装向导</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="https://cdn.bootcdn.net/ajax/libs/layui/2.9.8/css/layui.css">
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{background:#f7f8fc;padding:30px;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI','PingFang SC','Microsoft YaHei',sans-serif;}
.install-box{max-width:800px;margin:0 auto;background:#fff;border-radius:16px;box-shadow:0 25px 50px rgba(0,0,0,0.06);overflow:hidden;}
.install-header{background:linear-gradient(135deg,#2b6cb0,#2c5282);color:#fff;padding:36px 30px;text-align:center;}
.install-header h1{font-size:26px;margin-bottom:4px;font-weight:700;}
.install-header p{opacity:0.8;font-size:14px;}
.install-body{padding:32px;}
.install-footer{text-align:center;padding:20px;border-top:1px solid #e2e8f0;color:#a0aec0;font-size:12px;}
.step{margin-bottom:28px;}
.step h3{font-size:16px;font-weight:600;color:#1a202c;margin-bottom:16px;padding-left:14px;border-left:4px solid #3182ce;}
.success-icon{display:inline-block;width:64px;height:64px;background:rgba(56,161,105,0.12);border-radius:50%;line-height:64px;text-align:center;font-size:32px;color:#38a169;margin-bottom:12px;}
.layui-btn{background:linear-gradient(135deg,#2b6cb0,#3182ce);border-radius:8px;height:42px;line-height:42px;padding:0 24px;font-size:15px;border:none;transition:all 0.3s;}
.layui-btn:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(43,108,176,0.25)!important;}
</style>
</head>
<body>
<div class="install-box">
    <div class="install-header">
        <div style="font-size:52px;margin-bottom:8px;">🏢</div>
        <h1>大圣物业管理系统</h1>
        <p>杭州喵喵至家网络有限公司 · 安装向导</p>
    </div>
    <div class="install-body">
        <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
        <?php
        $host = $_POST['host'] ?? '127.0.0.1';
        $port = $_POST['port'] ?? '3306';
        $user = $_POST['user'] ?? 'root';
        $pass = $_POST['pass'] ?? '';
        $dbname = $_POST['dbname'] ?? 'dasheng';
        $adminUser = $_POST['admin_user'] ?? 'admin';
        $adminPass = $_POST['admin_pass'] ?? 'admin123';
        $prefix = $_POST['prefix'] ?? 'ds_';

        try {
            $pdo = new PDO("mysql:host={$host};port={$port};charset=utf8mb4", $user, $pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // 创建数据库
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbname}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE `{$dbname}`");

            // 导入SQL
            $sql = file_get_contents(__DIR__ . '/../database/dasheng.sql');
            if (empty($sql)) throw new Exception('数据库SQL文件不存在');

            // 替换表前缀
            $sql = str_replace('CREATE TABLE IF NOT EXISTS `ds_', "CREATE TABLE IF NOT EXISTS `{$prefix}", $sql);
            $sql = str_replace("INSERT INTO `ds_", "INSERT INTO `{$prefix}", $sql);

            // 分句执行
            $statements = explode(';', $sql);
            foreach ($statements as $stmt) {
                $stmt = trim($stmt);
                if (!empty($stmt)) {
                    try {
                        $pdo->exec($stmt);
                    } catch (PDOException $e) {
                        // 跳过已存在的表
                    }
                }
            }

            // 更新管理员密码
            $authKey = 'JUD6FCtZsqrmVXc2apev4TRn3O8gAhxbSlH9wfPN';
            $encryptedPass = md5(md5($adminPass) . $authKey);
            $stmt = $pdo->prepare("UPDATE `{$prefix}admin_user` SET `username` = ?, `password` = ? WHERE `id` = 1");
            $stmt->execute([$adminUser, $encryptedPass]);

            // 写入 .env
            $envContent = "APP_DEBUG = false\nAPP_ENV = production\n\n[DATABASE]\nTYPE = mysql\nHOSTNAME = {$host}\nDATABASE = {$dbname}\nUSERNAME = {$user}\nPASSWORD = {$pass}\nHOSTPORT = {$port}\nCHARSET = utf8mb4\nPREFIX = {$prefix}\nDEBUG = false\n\n[JWT]\nKEY = ds_" . md5(uniqid()) . "\nISS = dasheng-pms\nAUD = dasheng-pms-client\nEXP = 86400\n";
            file_put_contents(__DIR__ . '/../.env', $envContent);

            echo '<div style="text-align:center;padding:20px 0;"><div class="success-icon">✓</div><h2 style="color:#5fb878;">安装成功！</h2>';
            echo '<p style="color:#666;margin:10px 0;">系统已成功安装，您可以使用以下信息登录：</p>';
            echo '<div style="background:#f5f6fa;border-radius:8px;padding:15px;display:inline-block;text-align:left;margin:15px auto;">';
            echo '<p><strong>管理后台：</strong><a href="/admin/login.html" target="_blank">/admin/login.html</a></p>';
            echo '<p><strong>用户名：</strong>' . htmlspecialchars($adminUser) . '</p>';
            echo '<p><strong>密码：</strong>' . htmlspecialchars($adminPass) . '</p>';
            echo '</div></div>';

        } catch (Exception $e) {
            echo '<div style="text-align:center;padding:20px 0;"><div style="display:inline-block;width:60px;height:60px;background:rgba(255,87,34,0.15);border-radius:50%;line-height:60px;font-size:32px;color:#ff5722;margin-bottom:10px;">✗</div>';
            echo '<h2 style="color:#ff5722;">安装失败</h2>';
            echo '<p style="color:#666;">' . htmlspecialchars($e->getMessage()) . '</p>';
            echo '<a href="install.php" class="layui-btn" style="margin-top:15px;">重新安装</a></div>';
        }
        ?>
        <?php else: ?>
        <div class="step">
            <h3>📋 数据库配置</h3>
            <form method="post" class="layui-form">
                <div class="layui-row layui-col-space15">
                    <div class="layui-col-md6">
                        <label class="layui-form-label">数据库主机</label>
                        <div class="layui-input-block"><input type="text" name="host" value="127.0.0.1" class="layui-input" required></div>
                    </div>
                    <div class="layui-col-md3">
                        <label class="layui-form-label">端口</label>
                        <div class="layui-input-block"><input type="text" name="port" value="3306" class="layui-input" required></div>
                    </div>
                    <div class="layui-col-md3">
                        <label class="layui-form-label">表前缀</label>
                        <div class="layui-input-block"><input type="text" name="prefix" value="ds_" class="layui-input" required></div>
                    </div>
                </div>
                <div class="layui-row layui-col-space15" style="margin-top:10px;">
                    <div class="layui-col-md6">
                        <label class="layui-form-label">数据库用户名</label>
                        <div class="layui-input-block"><input type="text" name="user" value="root" class="layui-input" required></div>
                    </div>
                    <div class="layui-col-md6">
                        <label class="layui-form-label">数据库密码</label>
                        <div class="layui-input-block"><input type="password" name="pass" class="layui-input"></div>
                    </div>
                </div>
                <div class="layui-row layui-col-space15" style="margin-top:10px;">
                    <div class="layui-col-md12">
                        <label class="layui-form-label">数据库名</label>
                        <div class="layui-input-block"><input type="text" name="dbname" value="dasheng" class="layui-input" required></div>
                    </div>
                </div>
        </div>
        <div class="step">
            <h3>🔐 管理员配置</h3>
            <div class="layui-row layui-col-space15">
                <div class="layui-col-md6">
                    <label class="layui-form-label">管理员账号</label>
                    <div class="layui-input-block"><input type="text" name="admin_user" value="admin" class="layui-input" required></div>
                </div>
                <div class="layui-col-md6">
                    <label class="layui-form-label">管理员密码</label>
                    <div class="layui-input-block"><input type="password" name="admin_pass" value="admin123" class="layui-input" required minlength="6"></div>
                </div>
            </div>
        </div>
        <div style="text-align:center;margin-top:24px;">
            <button class="layui-btn" style="padding:0 60px;height:48px;line-height:48px;font-size:16px;border-radius:10px;">🚀 开始安装</button>
        </div>
        </form>
        <?php endif; ?>
    </div>
    <div class="install-footer">© 2026 杭州喵喵至家网络有限公司 · 大圣物业管理系统 v1.0.0</div>
</div>
<script src="https://cdn.bootcdn.net/ajax/libs/layui/2.9.8/layui.js"></script>
</body>
</html>
