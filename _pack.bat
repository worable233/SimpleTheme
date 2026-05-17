@echo off
chcp 65001 >nul
title Simple Theme - 打包
cd /d "%~dp0"

echo ========================================
echo   Simple Theme - 打包
echo ========================================
echo.

:: ── 检查 dist ──
if not exist "dist\manifest.json" (
    echo   ⚠️  dist 目录不存在或缺少构建产物。
    echo   请先运行 _build.bat 进行完整构建。
    echo.
    choice /c YN /m "仅打包现有文件继续？"
    if errorlevel 2 exit /b
    echo.
)

:: ── 打包 ──
echo 正在打包...
echo.

powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0bin\package-theme.ps1"
if %errorlevel% neq 0 (
    echo.
    echo   ❌ 打包失败
    pause
    exit /b 1
)

echo.
echo ========================================
echo   可直接上传到 WordPress 后台安装
echo   或解压到 wp-content/themes/ 目录
echo ========================================
echo.

pause
