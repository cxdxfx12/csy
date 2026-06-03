@echo off
title npm install - 大圣物业管理系统
set PATH=C:\Users\ccc\AppData\Local\Temp\node-v20.18.0-win-x64;%PATH%
cd /d e:\ds\admin
echo 正在安装依赖包，请耐心等待...
echo 时间: %date% %time%
echo.
call npm install --no-fund --no-audit
echo.
echo ================================
if exist node_modules (
    echo 安装成功! ✔
    echo.
    echo 启动开发服务器: npm run dev
    echo 按任意键关闭本窗口...
) else (
    echo 安装失败, 查看上面错误信息
)
pause >nul
