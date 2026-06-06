@echo off
cd /d "%~dp0"
PATH=C:\Program Files\nodejs;%PATH%

:menu
cls
echo =========================================
echo    管理后台自动化测试
echo =========================================
echo.
echo   [1] 登录 (首次需手动输入验证码)
echo   [2] 运行全部测试 (无界面)
echo   [3] 运行全部测试 (有界面，可观看)
echo   [4] 查看测试报告
echo   [5] 端到端三端联调测试
echo   [6] 端到端 (有界面，可观看)
echo.
set /p choice=请选择 (1-6): 

if "%choice%"=="1" goto login
if "%choice%"=="2" goto test
if "%choice%"=="3" goto headed
if "%choice%"=="4" goto report
if "%choice%"=="5" goto e2e
if "%choice%"=="6" goto e2e-headed
goto menu

:login
echo 正在打开登录页...
call npx.cmd playwright test --project=setup --headed
pause
goto menu

:test
echo 正在运行全部测试...
call npx.cmd playwright test --project=chromium
echo.
echo 测试完成！按任意键返回菜单...
pause >nul
goto menu

:headed
echo 正在以有界面模式运行测试...
call npx.cmd playwright test --project=chromium --headed
echo.
echo 测试完成！按任意键返回菜单...
pause >nul
goto menu

:report
echo 正在打开测试报告...
call npx.cmd playwright show-report test-results/report
pause >nul
goto menu

:e2e
echo.
echo =========================================
echo   端到端三端联调测试
echo   业主→后台→员工→返回业主
echo =========================================
echo.
call npx.cmd playwright test e2e-repair-flow --project=chromium
echo.
echo 测试完成！按任意键返回菜单...
pause >nul
goto menu

:e2e-headed
echo.
echo =========================================
echo   端到端三端联调测试 (可视化)
echo   业主→后台→员工→返回业主
echo =========================================
echo.
call npx.cmd playwright test e2e-repair-flow --project=chromium --headed
echo.
echo 测试完成！按任意键返回菜单...
pause >nul
goto menu
