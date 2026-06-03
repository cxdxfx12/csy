@echo off
chcp 65001 >nul
title 大圣物业管理系统

echo ========================================
echo    🐒 大圣物业管理系统 - 启动向导
echo    杭州喵喵至家网络有限公司
echo ========================================
echo.

if not exist "vendor\autoload.php" (
    echo [INFO] 正在检查 Composer 依赖...
    where composer >nul 2>&1
    if errorlevel 1 (
        echo [WARN] Composer 未安装，请先安装 Composer
        echo [WARN] 下载地址: https://getcomposer.org/download/
        pause
        exit /b
    )
    echo [INFO] 正在安装依赖，请稍候...
    call composer install --no-interaction
    if errorlevel 1 (
        echo [ERROR] 依赖安装失败
        pause
        exit /b
    )
)

if not exist ".env" (
    echo [INFO] .env 文件不存在，复制 .env.example...
    copy .env.example .env >nul
    echo [INFO] 请编辑 .env 文件配置数据库信息
)

echo [INFO] 检查数据库...
php -r "
    \$env = parse_ini_file('.env', true);
    \$db = \$env['DATABASE'];
    try {
        \$pdo = new PDO('mysql:host='.\$db['HOSTNAME'].';port='.\$db['HOSTPORT'].';charset=utf8mb4', \$db['USERNAME'], \$db['PASSWORD']);
        \$pdo->exec('CREATE DATABASE IF NOT EXISTS `'.\$db['DATABASE'].'` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        echo \"[OK] 数据库 '{\$db['DATABASE']}' 已就绪\n\";
    } catch(Exception \$e) {
        echo \"[WARN] 数据库连接失败: \" . \$e->getMessage() . \"\n\";
    }
"

echo.
echo ========================================
echo    🚀 启动 PHP 内置开发服务器
echo    地址: http://127.0.0.1:8000
echo    后台: http://127.0.0.1:8000/admin/login.html
echo    安装: http://127.0.0.1:8000/install.php
echo ========================================
echo.

php -S 127.0.0.1:8000 -t public
pause
