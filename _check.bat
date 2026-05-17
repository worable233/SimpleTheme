cd /d "C:\Users\worable\Documents\code\随便写的丢这里\wp-content\themes\simple-theme"
call npx vue-tsc --noEmit 2>&1
echo.
echo === Done ===
exit /b %errorlevel%
