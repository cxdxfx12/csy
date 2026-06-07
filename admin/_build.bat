@echo off
cd /d e:\ds\admin
echo Building admin frontend...
call "C:\Program Files\nodejs\npm.cmd" run build
echo.
echo Build complete! Check admin/dist/ for output.
pause
