@echo off
chcp 65001 >nul
echo 启动大圣物业管理系统开发服务器...
echo 地址: http://127.0.0.1:8000
echo 后台: http://127.0.0.1:8000/admin/login.html
echo.
php -S 127.0.0.1:8000 -t "%~dp0public" "%~dp0public/router.php"
pause
