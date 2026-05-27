# Simple Theme

[![GitHub stars](https://img.shields.io/github/stars/worable233/SimpleTheme?style=social)](https://github.com/worable233/SimpleTheme)

> **v2.0.0 正在开发中** — 从 v1 到 v2 的完整重写，功能逐步完善中，欢迎测试反馈。
> 预览版请前往 [releases](https://github.com/worable233/SimpleTheme/releases)下载。

Vue 3 SPA × WordPress REST API — 轻量、现代、SEO 友好的 WordPress 主题。

前端由 Vue 3 完全渲染，数据通过 WordPress REST API 驱动。内置后台美化，爬虫检测，对搜索引擎输出完整静态 HTML，兼容 Yoast / Rank Math 等 SEO 插件。

## 特性

> 其中大部分功能已开发完善，但仍有部分功能还在开发中...

| 类别     | 功能                                                                                                          |
| -------- | ------------------------------------------------------------------------------------------------------------- |
| 前端     | Vue 3 + TypeScript + Vue Router                                                                               |
| 布局     | 响应式双栏，移动端滑动侧栏，浅色/深色模式                                                                     |
| 主题     | 参考[iEmo](https://github.com/kannafay/iEmo)，自定义主题色                                                    |
| 后台美化 | 完整 WordPress 后台 + 登录页 UI 重制，明暗模式与前端同步，边栏标识注入                                        |
| 缓存系统 | 双重智能缓存：前端内存 LRU（API 响应纳秒级命中）+ 后端 Transients（站点统计/ALTCHA/IP 归属地），分层 TTL 策略 |
| SEO      | 智能检测爬虫，提升访问速度，多重SEO优化                                                                       |
| 评论     | 嵌套回复、点赞、Markdown、表情包(Bilibili/恐龙/贴吧)、ALTCHA 验证码、IP 归属地、浏览器信息                    |
| 邮件     | SMTP 实现 + 异步邮件通知队列                                                                                  |
| 公告     | 弹窗/胶囊多模式，关联页面内容，自定义按钮                                                                     |
| 合规     | Cookie 弹窗                                                                                                   |
| 其他     | unDraw 插画、Prism.js 语法高亮、站点统计、一言等                                                              |
| 兼容     | 无缝兼容 Sakurairo 主题的区块及数据                                                                           |

## 快速开始

```bash
npm install                # 安装依赖
npm run dev                # 开发（Vite HMR，需 WordPress 后端）
npm run build              # 构建生产资源 → dist/
npm run package            # 打包 ZIP
```

## 主题设置

**WordPress 后台 → 设置 → Simple Theme**（也是 Vue 3 开发的管理面板）

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

## 文档&预览

> 其实文档还没写()再等等吧

- [配置 & 错误排查](docs/config-and-troubleshooting.md)
- [主题预览](https://www.worable.top/)

## 致谢

- [Sakurairo](https://github.com/mirai-mamori/Sakurairo) - GPL-2.0 license - 参考其代码以实现对Sakurairo区块及数据的完整兼容
- [iEmo](https://github.com/kannafay/iEmo) - MIT license - v2设计大幅借鉴此主题

## 许可证

[CC BY-NC-ND 4.0](https://creativecommons.org/licenses/by-nc-nd/4.0/) — 署名-非商业使用-禁止演绎。

Copyright © 2026 [worable](https://github.com/worable233)

## Star History

<a href="https://www.star-history.com/?repos=worable233%2FSimpleTheme&type=date&legend=top-left">
 <picture>
   <source media="(prefers-color-scheme: dark)" srcset="https://api.star-history.com/chart?repos=worable233/SimpleTheme&type=date&theme=dark&legend=top-left" />
   <source media="(prefers-color-scheme: light)" srcset="https://api.star-history.com/chart?repos=worable233/SimpleTheme&type=date&legend=top-left" />
   <img alt="Star History Chart" src="https://api.star-history.com/chart?repos=worable233/SimpleTheme&type=date&legend=top-left" />
 </picture>
</a>
