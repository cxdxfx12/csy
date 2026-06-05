@echo off
set "PATH=C:\Users\ccc\.codebuddy\runtimes\node-20.18.0\node-v20.18.0-win-x64;%PATH%"
cd /d "e:\ds\admin"
echo ========================================
echo   Compiling Dasheng Admin Frontend...
echo ========================================
call npm run build
echo.
echo ========================================
echo   Build Complete!
echo   Output: e:\ds\admin\dist\
echo ========================================
pause
