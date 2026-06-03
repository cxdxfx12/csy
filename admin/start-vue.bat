@echo off
title 大圣物业管理系统 - Vue3 开发服务器
set PATH=C:\Users\ccc\AppData\Local\Temp\node-v20.18.0-win-x64;%PATH%
cd /d e:\ds\admin
echo 启动 Vue3 开发服务器...
echo 访问地址: http://localhost:3000
echo.
call npm run dev
pause
