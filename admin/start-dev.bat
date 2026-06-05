@echo off
chcp 65001 >nul
title 大圣物业 - 开发服务器

:: Node.js 路径
set NODE_PATH=C:\Users\ccc\AppData\Local\Temp\node-v20.18.0-win-x64
set PATH=%NODE_PATH%;%PATH%

echo ========================================
echo    ? 大圣物业管理系统 - 开发服务器
echo ========================================
echo.
echo   PHP API 服务: http://127.0.0.1:8000
echo   后台管理面板: http://127.0.0.1:3000
echo.

:: 先杀掉可能残留的进程
for /f "tokens=5" %%a in ('netstat -ano ^| findstr ":8000" ^| findstr "LISTENING"') do (
    taskkill /F /PID %%a >nul 2>&1
)
for /f "tokens=5" %%a in ('netstat -ano ^| findstr ":3000" ^| findstr "LISTENING"') do (
    taskkill /F /PID %%a >nul 2>&1
)

timeout /t 1 /nobreak >nul

:: 新建标签页启动 PHP
start "PHP-API" /min cmd /c "echo PHP API 服务已启动 (8000端口) && cd /d %~dp0.. && php -S 127.0.0.1:8000 -t public"

:: 启动 Vite 开发服务器（前台，方便看日志）
echo [启动] Vite 开发服务器...
cd /d %~dp0
call npm run dev

pause
