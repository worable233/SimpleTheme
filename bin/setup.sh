#!/usr/bin/env bash
# Simple Theme - Docker 开发环境一键安装脚本
set -e

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
PROJECT_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"
cd "$PROJECT_DIR"

echo "========================================"
echo "  Simple Theme - Docker Dev Setup"
echo "========================================"
echo ""

# Step 1: Check Docker
echo "[1/3] 检查 Docker..."
if ! command -v docker &> /dev/null; then
  echo "  ❌ Docker 未安装！"
  echo "  请先安装 Docker Desktop："
  echo "  https://www.docker.com/products/docker-desktop/"
  exit 1
fi
echo "  ✅ Docker 已安装"

# Step 2: Configure .env
echo "[2/3] 配置 .env（Docker 模式）..."
cp .env.docker .env
echo "  ✅ 已切换为 Docker 模式"

# Step 3: Start containers
echo "[3/3] 启动 Docker 容器..."
docker compose up -d

echo ""
echo "========================================"
echo "  环境就绪！"
echo "========================================"
echo ""
echo "  WordPress:  http://localhost:8080"
echo "  phpMyAdmin: http://localhost:8081"
echo "  Vite 开发:  npm run dev"
echo ""
echo "  首次使用请先在浏览器打开 http://localhost:8080"
echo "  完成 WordPress 安装后，进入后台激活本主题。"
echo "  然后运行 npm run dev 启动前端开发服务器。"
echo ""
