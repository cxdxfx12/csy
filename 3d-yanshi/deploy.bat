@echo off
chcp 65001 >nul
echo ============================================
echo  部署 3D 数字孪生 v5 + 官网链接
echo ============================================
set PWD=cxdxfx12
set SERVER=root@211.149.181.178
set DST=/www/wwwroot/www.hbdxm.com/public/3d-yanshi/

echo.
echo [1/4] 上传 main.js
scp -o StrictHostKeyChecking=no -P 22000 "e:\ds\3d-yanshi\main.js" %SERVER%:%DST%main.js

echo.
echo [2/4] 上传 index.html
scp -o StrictHostKeyChecking=no -P 22000 "e:\ds\3d-yanshi\index.html" %SERVER%:%DST%index.html

echo.
echo [3/4] 上传 style.css
scp -o StrictHostKeyChecking=no -P 22000 "e:\ds\3d-yanshi\style.css" %SERVER%:%DST%style.css

echo.
echo [4/4] 上传官网首页
scp -o StrictHostKeyChecking=no -P 22000 "e:\ds\qh\index.html" %SERVER%:/www/wwwroot/www.hbdxm.com/public/qh/index.html

echo.
echo ============================================
echo  全部上传完成！
echo  https://www.hbdxm.com/3d-yanshi/
echo  https://www.hbdxm.com/qh/
echo ============================================
pause
