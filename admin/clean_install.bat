@echo off
title 重装依赖
set PATH=C:\Users\ccc\AppData\Local\Temp\node-v20.18.0-win-x64;%PATH%
cd /d e:\ds\admin
echo 清理旧依赖...
rmdir /s /q node_modules 2>NUL
del package-lock.json 2>NUL
echo 设置淘宝镜像...
call npm config set registry https://registry.npmmirror.com
echo 重新安装（约3分钟）...
call npm install --no-fund --no-audit
if exist node_modules\rollup (
    echo 安装完成！
    timeout /t 3 /nobreak >nul
    start "" cmd /c "cd /d e:\ds\admin && npm run dev"
) else (
    echo 安装失败
    pause
)
