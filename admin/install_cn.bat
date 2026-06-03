@echo off
title npm install (国内镜像)
set PATH=C:\Users\ccc\AppData\Local\Temp\node-v20.18.0-win-x64;%PATH%
cd /d e:\ds\admin
echo 设置淘宝镜像加速...
call npm config set registry https://registry.npmmirror.com
echo 正在安装依赖，约2-3分钟...
call npm install --no-fund --no-audit
echo.
if exist node_modules\vue (
    echo ✅ 安装成功!
    echo.
    echo 启动方式: npm run dev
    echo 或双击: start-vue.bat
) else (
    echo ❌ 安装可能失败，请查看上方错误信息
    pause
)
