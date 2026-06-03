@echo off
title 安装缺失依赖
set PATH=C:\Users\ccc\AppData\Local\Temp\node-v20.18.0-win-x64;%PATH%
cd /d e:\ds\admin
echo 安装缺失的Vite和插件...
call npm install vite @vitejs/plugin-vue vue-tsc unplugin-auto-import unplugin-vue-components --save-dev --no-fund --no-audit
echo.
if exist node_modules\vite\bin\vite.js (
    echo 安装成功!
    echo 启动: start-vue.bat
) else (
    echo 还有问题, 按任意键查看错误
    pause
)
