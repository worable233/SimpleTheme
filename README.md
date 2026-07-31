# Simple Theme

> Vue 3 SPA × WordPress REST API — 轻量、现代、SEO 友好的 WordPress 主题

[![Release](https://img.shields.io/github/v/release/worable233/SimpleTheme?style=flat-square)](https://github.com/worable233/SimpleTheme/releases)
[![Build](https://img.shields.io/github/actions/workflow/status/worable233/SimpleTheme/build.yml?style=flat-square)](https://github.com/worable233/SimpleTheme/actions)
[![License](https://img.shields.io/badge/license-CC%20BY--NC--ND%204.0-red?style=flat-square)](https://creativecommons.org/licenses/by-nc-nd/4.0/)
[![Vue 3](https://img.shields.io/badge/Vue-3.x-4FC08D?style=flat-square&logo=vuedotjs&logoColor=white)](https://vuejs.org/)
[![WordPress](https://img.shields.io/badge/WordPress-6.0%2B-21759B?style=flat-square&logo=wordpress&logoColor=white)](https://wordpress.org/)

前端由 Vue 3 完全渲染，数据经 WordPress REST API 驱动；服务端同时直出静态 HTML，搜索引擎与无 JS 环境零损失。

**[在线预览](https://www.worable.top/)** · **[下载安装](https://github.com/worable233/SimpleTheme/releases)** · **[配置文档](docs/config-and-troubleshooting.md)** · **[REST API](docs/rest-api.md)**

## 特性

- ⚡ **单页应用** — Vue Router 无刷新导航，内存 LRU + Transients 双层缓存，精准骨架屏
- 🎨 **现代 UI** — Tailwind CSS 4，响应式双栏布局，浅色/深色模式，自定义主题色
- 💬 **完整评论** — 嵌套回复、点赞、Markdown、表情包、ALTCHA 验证码、IP 归属地
- 🔍 **SEO 友好** — 服务端直出完整 HTML + OG/JSON-LD，兼容 Yoast / Rank Math
- 🧩 **区块适配** — Gutenberg 核心区块全量样式适配，无缝兼容 Sakurairo 区块与数据
- 🛠 **后台美化** — WordPress 后台 + 登录页 UI 重制，Vue 3 设置面板，明暗随前端同步
- ✉️ **邮件通知** — SMTP + 异步队列，多套 HTML 模板
- 📦 **开箱即用** — 公告弹窗、Cookie 合规、站点统计、一言、Prism 高亮、unDraw 插画

## 安装

从 [Releases](https://github.com/worable233/SimpleTheme/releases) 下载 `Simple-Theme-vX.Y.Z.zip`，
后台 **外观 → 主题 → 上传主题** 安装启用即可。

要求：WordPress ≥ 6.0 · PHP ≥ 7.3

## 开发

```bash
npm install        # 安装依赖（Node ≥ 20.19）
npm run dev        # Vite HMR 开发（需 WordPress 后端）
npm run build      # 类型检查 + 构建 → dist/
npm run package    # 构建并打包主题 ZIP
```

推送 `v*` 标签会由 GitHub Actions 自动构建并发布 Release。

## 架构

```
src/    Vue 3 前端（views / components / composables / styles）+ 后台设置面板 SPA
inc/    PHP 后端 —— core/ 资产与 SEO · rest/ API 端点 · admin/ 选项注册
dist/   构建产物（由 PHP 按 manifest 注入）
```

路由采用 catch-all：任意 URL 经 REST 解析出类型（文章/页面/归档/标签/日期），由对应视图渲染；`/about`、`/archives` 等特殊页面内置于前端。

## 致谢

- [Sakurairo](https://github.com/mirai-mamori/Sakurairo)（GPL-2.0）— 参考其实现完成区块与数据兼容
- [iEmo](https://github.com/kannafay/iEmo)（MIT）— v2 视觉设计的重要灵感来源，未使用其代码

## 许可证

[CC BY-NC-ND 4.0](https://creativecommons.org/licenses/by-nc-nd/4.0/) — 署名 · 非商业使用 · 禁止演绎

## Star History

<a href="https://www.star-history.com/?repos=worable233%2FSimpleTheme&type=date&legend=top-left">
 <picture>
   <source media="(prefers-color-scheme: dark)" srcset="https://api.star-history.com/chart?repos=worable233/SimpleTheme&type=date&theme=dark&legend=top-left" />
   <source media="(prefers-color-scheme: light)" srcset="https://api.star-history.com/chart?repos=worable233/SimpleTheme&type=date&legend=top-left" />
   <img alt="Star History Chart" src="https://api.star-history.com/chart?repos=worable233/SimpleTheme&type=date&legend=top-left" />
 </picture>
</a>
