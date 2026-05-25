# Simple Theme

> **v2.0.0 正在开发中** — 从 v1 到 v2 的完整重写，功能逐步完善中，欢迎反馈。

Vue 3 SPA × WordPress REST API — 轻量、现代、SEO 友好的 WordPress 主题。

前端由 Vue 3 完全渲染，数据通过 WordPress REST API 驱动。内置爬虫检测，对搜索引擎输出完整静态 HTML，兼容 Yoast / Rank Math 等 SEO 插件。

## 特性

| 类别 | 功能 |
|------|------|
| 前端 | Vue 3 + TypeScript + Vue Router，Vite 8 构建 |
| 布局 | 响应式双栏，移动端滑动侧栏，明/暗模式 |
| 主题 | Material Design 3 配色，自定义主题色 |
| 后台美化 | 完整 WordPress 后台 + 登录页 UI 重制，明暗模式与前端同步，边栏品牌标识注入 |
| 缓存系统 | 双重智能缓存：前端内存 LRU（API 响应纳秒级命中）+ 后端 Transients（站点统计/ALTCHA/IP 归属地），分层 TTL 策略 |
| SEO | 爬虫检测 → 静态 HTML 回退 |
| 评论 | 嵌套回复、点赞、Markdown、表情包(Bilibili/恐龙/贴吧)、ALTCHA 验证码、IP 归属地、浏览器信息 |
| 邮件 | SMTP 配置 + 异步邮件通知 |
| 公告 | 弹窗/胶囊两种模式，关联页面内容，自定义按钮 |
| 合规 | Cookie 同意弹窗 |
| 扩展 | 说说自定义文章类型、SVG 插画(unDraw)、Prism.js 语法高亮、站点统计 |
| 兼容 | 兼容 Sakurairo 主题数据格式 |

## 快速开始

```bash
npm install                # 安装依赖
npm run dev                # 开发（Vite HMR，需 WordPress 后端）
set VITE_USE_MOCK=true && npm run dev   # Mock 模式，无需 WordPress
npm run build              # 构建生产资源 → dist/
node bin/package-theme.mjs # 打包 ZIP
```

## 主题设置

**WordPress 后台 → 设置 → Simple Theme**（Vue 3 管理面板）

- **首页布局** — Hero 区域、文章数、说说数
- **主题色** — Material Design 3 主色
- **评论** — Captcha、Markdown、图片上传、隐私选项
- **邮件** — SMTP 配置、通知开关
- **公告** — 弹窗/胶囊、关联页面、自定义按钮
- **Cookie** — 启用/关闭、自定义文案
- **其他** — 技术栈标签、社交链接、ICP 备案

## 技术栈

Vue 3 · TypeScript · Vue Router · Vite 8 · OAT UI · Axios · Parsedown · ALTCHA · Prism.js · Boxicons · unDraw

## 目录结构

```
simple-theme/
├── src/               Vue 3 前端（components / composables / views / styles / types）
├── inc/               PHP 后端
│   ├── core/          资产加载、爬虫检测、SEO、SMTP、认证、安装
│   ├── rest/          REST API 端点（文章/评论/站点信息/导航）
│   └── admin/         选项注册与校验
├── admin/             管理面板 SPA（独立构建）
├── bin/               构建/打包脚本（Node.js + PowerShell）
├── public/            静态资源（表情包、插画）
├── docs/              文档
├── functions.php      主题入口
├── style.css          主题标识
├── theme.json         WordPress 配置
└── vite.config.ts     Vite 配置
```

## 路由架构

Vue Router catch-all 路由，统一由 `ContentView` 处理：调用 WordPress REST API 解析当前 URL → 根据返回类型（post/page/term/404）渲染对应视图。特殊页面（`/shuoshuo`、`/about`、`/archives`、`/links`）在 API 返回 404 时自动回退到 Vue 内置页面。

## SEO

`template_include` 钩子拦截爬虫请求：匹配 User-Agent → 输出 `wp_head` + `the_content` + `wp_footer` 完整 HTML；否则正常加载 SPA。

支持的爬虫：Googlebot · Bingbot · Baiduspider · YandexBot · DuckDuckBot · Sogou · 360Spider · Bytespider · PetalBot · Facebot · Twitterbot · Applebot · DiscordBot · SlackBot · TelegramBot · AhrefsBot · SemrushBot。可通过 `simple_theme_crawler_patterns` filter 扩展。

## 文档

- [配置 & 错误排查](docs/config-and-troubleshooting.md)

## 许可证

[CC BY-NC-ND 4.0](https://creativecommons.org/licenses/by-nc-nd/4.0/) — 署名-非商业使用-禁止演绎。

Copyright © 2026 [worable](https://github.com/worable233)
