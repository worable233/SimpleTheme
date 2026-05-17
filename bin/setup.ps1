# Simple Theme - Docker 开发环境一键安装脚本
# 以管理员身份运行 PowerShell，然后执行：
# Set-ExecutionPolicy -Scope Process -ExecutionPolicy Bypass
# .\bin\setup.ps1

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  Simple Theme - Docker Dev Setup" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# 检查 Docker
Write-Host "[1/4] 检查 Docker Desktop..." -ForegroundColor Yellow
$dockerPath = Get-Command docker -ErrorAction SilentlyContinue
if (-not $dockerPath) {
    Write-Host "  ❌ Docker 未安装！" -ForegroundColor Red
    Write-Host "  请先安装 Docker Desktop：" -ForegroundColor Red
    Write-Host "  https://www.docker.com/products/docker-desktop/" -ForegroundColor Red
    Write-Host ""
    Write-Host "  安装完成后，打开 Docker Desktop 并等待引擎启动。" -ForegroundColor Yellow
    exit 1
}
Write-Host "  ✅ Docker 已安装" -ForegroundColor Green

# 检查 Docker 是否在运行
Write-Host "[2/4] 检查 Docker 引擎..." -ForegroundColor Yellow
$dockerRunning = docker info --format "{{.ServerVersion}}" 2>$null
if (-not $dockerRunning) {
    Write-Host "  ⚠️  Docker 引擎未运行，尝试启动..." -ForegroundColor Yellow
    Write-Host "  请手动打开 Docker Desktop，等待其启动完成。" -ForegroundColor Yellow
    exit 1
}
Write-Host "  ✅ Docker 引擎运行中 (v$dockerRunning)" -ForegroundColor Green

# 切换到项目目录
$projectRoot = Split-Path -Parent (Split-Path -Parent $MyInvocation.MyCommand.Path)
Set-Location $projectRoot
Write-Host "  项目目录: $projectRoot" -ForegroundColor Gray

# 配置 .env
Write-Host "[3/4] 配置 .env（Docker 模式）..." -ForegroundColor Yellow
Copy-Item ".env.docker" ".env" -Force
Write-Host "  ✅ 已切换为 Docker 模式" -ForegroundColor Green

# 启动 Docker 容器
Write-Host "[4/4] 启动 Docker 容器..." -ForegroundColor Yellow
docker compose up -d

if ($LASTEXITCODE -eq 0) {
    Write-Host "  ✅ Docker 容器已启动" -ForegroundColor Green
    Write-Host ""
    Write-Host "========================================" -ForegroundColor Cyan
    Write-Host "  环境就绪！" -ForegroundColor Green
    Write-Host "========================================" -ForegroundColor Cyan
    Write-Host ""
    Write-Host "  WordPress:  http://localhost:8080" -ForegroundColor White
    Write-Host "  phpMyAdmin: http://localhost:8081" -ForegroundColor White
    Write-Host "  Vite 开发:  npm run dev" -ForegroundColor White
    Write-Host ""
    Write-Host "  首次使用请先在浏览器打开 http://localhost:8080" -ForegroundColor Yellow
    Write-Host "  完成 WordPress 安装后，进入后台激活本主题。" -ForegroundColor Yellow
    Write-Host "  然后运行 npm run dev 启动前端开发服务器。" -ForegroundColor Yellow
    Write-Host ""
} else {
    Write-Host "  ❌ 启动失败，请检查 Docker 日志：" -ForegroundColor Red
    Write-Host "  docker compose logs" -ForegroundColor Red
}
