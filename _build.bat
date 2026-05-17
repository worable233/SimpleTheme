@echo off
chcp 65001 >nul
title Simple Theme - 一键构建打包
cd /d "%~dp0"

echo ========================================
echo   Simple Theme - 一键构建打包
echo ========================================
echo.

:: ── Step 1: 检查 Node.js ──
echo [1/6] 检查 Node.js...
where node >nul 2>&1
if %errorlevel% neq 0 (
    echo   ❌ Node.js 未安装！请先安装 Node.js v18+
    echo   https://nodejs.org/
    pause
    exit /b 1
)
for /f "tokens=1" %%i in ('node -v') do set NODE_VER=%%i
echo   ✅ Node.js %NODE_VER%
echo.

:: ── Step 2: 安装依赖 ──
echo [2/6] 检查 node_modules...
if not exist "node_modules" (
    echo   ⚠️  依赖未安装，正在安装...
    call npm install
    if %errorlevel% neq 0 (
        echo   ❌ npm install 失败
        pause
        exit /b 1
    )
    echo   ✅ 依赖安装完成
) else (
    echo   ✅ node_modules 已存在
)
echo.

:: ── Step 3: 配置环境 ──
echo [3/6] 配置环境...
if not exist ".env" (
    if exist ".env.docker" (
        copy ".env.docker" ".env" >nul
        echo   ✅ 已从 .env.docker 创建 .env
    )
) else (
    echo   ✅ .env 已存在（跳过）
)
echo.

:: ── Step 4: 运行构建 ──
echo [4/6] 运行构建...
echo   类型检查 + Vite 构建...
call npm run build
if %errorlevel% neq 0 (
    echo   ❌ 构建失败，请检查上方错误信息
    pause
    exit /b 1
)
echo   ✅ 构建成功
echo.

:: ── Step 5: 打包主题 ──
echo [5/6] 打包主题...

powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0bin\package-theme.ps1"
if %errorlevel% neq 0 (
    echo   ❌ 打包失败，请检查错误信息
    pause
    exit /b 1
)
echo.

:: ── Step 6: 完成 ──
echo [6/6] 全部完成！
echo.
echo ========================================
echo   ✅ 构建打包成功！
echo ========================================
echo.
echo   输出文件：
for %%z in ("Simple-Theme-v*.zip") do (
    echo     %%z  (%%~zz KB)
)
echo.
echo   可直接上传到 WordPress 后台安装或手动解压到
echo   wp-content/themes/ 目录。
echo.

pause
