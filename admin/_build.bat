@echo off
cd /d e:\ds\admin
echo Building admin frontend...
call "C:\Program Files\nodejs\npm.cmd" run build
echo.
echo Copying static images...
if not exist "..\public\admin\assets\images" mkdir "..\public\admin\assets\images"
copy /Y "src\assets\images\welcome-girl.jpg" "..\public\admin\assets\images\" >nul
copy /Y "src\assets\images\monkey-ico.png" "..\public\admin\assets\images\monkey-icon.png" >nul
echo Static images copied.
echo.
echo Build complete!
pause
