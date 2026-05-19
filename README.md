# Simple Theme

**Vue 3 + OAT UI + WordPress REST API 驱动的轻量 WordPress 主题**

一款基于 Vue 3 单页应用架构的 WordPress 主题，前端渲染完全由 Vue 3 处理，通过 WordPress REST API 获取数据。使用 [OAT UI](https://github.com/knadh/oat.css) 作为 CSS 工具库，支持自定义主题色、明暗切换、响应式布局。
目前正在开发ver2.0.0版本，待测试完成后再发布。

## 特性

- Vue 3 SPA 前端，TypeScript 编写
- WordPress REST API 数据驱动
- 自定义主题色
- 明/暗模式切换，localStorage 持久化
- 响应式布局，移动端滑动侧栏
- 自定义文章类型「说说」
- 文章目录（TOC）自动生成
- 评论系统（嵌套回复、点赞、IP 归属地显示）
- SVG 插画（unDraw）
- 评论 IP 归属地查询：[api2.upk.com.cn](https://api2.upk.com.cn)（回退：[ip-api.com](http://ip-api.com)）
- 样式参考 [iEmo](https://github.com/kannafay/iEmo)
- 数据兼容 [Sakurairo](https://github.com/mirai-mamori/Sakurairo)
- **SEO 兼容** — WordPress 原生模板向爬虫输出静态 HTML，完整兼容 Yoast、Rank Math 等 SEO 插件

## 构建流程

```bash
# 1. 安装依赖
npm install

# 2. 复制插画 SVG 到 public/ 目录
npm run copy-illustrations    # 仅复制主题实际使用的插画（14个SVG）

# 3. 构建前端资源
npm run build                 # type-check → vite build
# 产物输出到 dist/ 目录，包含编译后的 JS/CSS/插画

# 4. 打包为 ZIP（发布用）
# PowerShell:
.\bin\package-theme.ps1
# 或 Node.js:
node bin\package-theme.mjs
# 输出: Simple-Theme-v2.0.0.zip（不含开发文件）
```

`npm run build` 会自动执行 `node bin/copy-illustrations.mjs`，仅复制主题实际使用的 14 个 SVG 插画到 `public/illustrations/`，避免将 undraw-svg 包中全部 1500+ 个 SVGs 打包。

## 开发环境

### Docker（推荐）

一键启动本地 WordPress 开发环境：

```bash
npm run docker:up       # 启动 WordPress + MySQL + phpMyAdmin
npm run dev:docker      # 切换到 Docker 环境配置并启动 Vite 开发服务器
npm run docker:down     # 停止容器
```

- WordPress: http://localhost:8080
- phpMyAdmin: http://localhost:8081
- MySQL: 端口 3307（user/pass: `wordpress`）
- Vite 开发服务器: http://localhost:5173

### 纯前端开发（Mock 模式）

```bash
set VITE_USE_MOCK=true   # 或写入 .env 文件
npm run dev               # 无需 WordPress，使用 Mock 数据
```

### Vite 开发代理

Vite 开发模式下自动代理 API 请求：

- `/wp-json/*` → WordPress 后端
- `/wp-content/uploads/*` → WordPress 上传目录

## 技术栈

| 层级        | 技术                                                 |
| ----------- | ---------------------------------------------------- |
| 前端框架    | Vue 3 + TypeScript + Vue Router                      |
| 构建工具    | Vite 6                                               |
| CSS 框架    | [OAT UI](https://github.com/knadh/oat.css)（v0.5.x） |
| HTTP 客户端 | Axios                                                |
| 后端        | WordPress REST API + 自定义 REST 路由                |
| 插画        | [unDraw](https://undraw.co/)（SVG，仅打包使用的）    |
| 语法高亮    | Prism.js                                             |
| 包管理      | npm                                                  |

## 目录结构

```
simple-theme/
├── src/                  # Vue 3 前端源码
│   ├── components/       # 组件
│   ├── composables/      # 组合式函数
│   ├── lib/              # 工具库（WordPress API 客户端等）
│   ├── styles/           # 全局样式
│   ├── types/            # TypeScript 类型
│   ├── views/            # 页面视图
│   ├── App.vue           # 根组件
│   ├── main.ts           # 入口
│   └── router/           # 路由配置
├── admin/                # WordPress 后台设置页面
├── bin/                  # 构建/打包脚本
├── inc/                  # PHP 功能模块
├── public/               # 静态资源（插画 SVGs 等）
├── dist/                 # Vite 构建产物
├── functions.php         # WordPress 主题功能
├── style.css             # WordPress 主题标识
├── theme.json            # WordPress 主题配置
└── vite.config.ts        # Vite 配置
```

## 路由架构

主题使用 Vue Router 的 catch-all 路由，所有非首页路径统一由 `ContentView` 处理：

1. `ContentView` 调用 WordPress REST API 解析 URL
2. 根据返回类型（post/page/term/404）渲染对应模板
3. 特殊路径（`/shuoshuo`、`/about`、`/archives`、`/links`）在 WordPress 返回 404 时回退到 Vue 内置页面

## SEO 兼容性

本主题虽然是 Vue SPA，但通过**爬虫检测 + WordPress 原生模板回退**机制，确保所有主流搜索引擎都能正确索引文章内容。

### 工作原理

```
爬虫请求 → WordPress 解析 URL
  ↓
template_include 钩子（优先级 99）
  ├─ User-Agent 匹配爬虫 → 输出 templates/crawler-fallback.php
  │    ├─ wp_head()      ← SEO 插件输出 title/meta/OG/JSON-LD
  │    ├─ the_content()  ← 文章正文全文
  │    └─ wp_footer()    ← 插件脚本
  │
  └─ User-Agent 非爬虫 → Vue SPA 正常加载
```

### 支持情况

| 功能                                    | 状态                                 |
| --------------------------------------- | ------------------------------------ |
| 搜索引擎索引（Google、Bing、Baidu 等）  | ✅ 全文静态 HTML                     |
| Yoast SEO / Rank Math / SEOPress 等插件 | ✅ 完整兼容                          |
| `robots.txt` & `sitemap.xml`            | ✅ WordPress 核心 + SEO 插件原生处理 |
| Open Graph / Twitter Card               | ✅ 由已安装的 SEO 插件输出           |
| 结构化数据 (JSON-LD)                    | ✅ 由已安装的 SEO 插件输出           |
| 页面标题 (`<title>`)                    | ✅ `wp_head()` 传递，SEO 插件控制    |
| `canonical` URL                         | ✅ 由已安装的 SEO 插件输出           |

### 爬虫识别范围

- **搜索引擎**：Googlebot, Bingbot, Baiduspider, YandexBot, DuckDuckBot, Yahoo Slurp, Sogou, 360Spider, Bytespider (字节跳动), PetalBot (华为), CocCocBot, SeznamBot
- **社交/工具**：Facebot, Twitterbot, Applebot, DiscordBot, SlackBot, TelegramBot, WhatsApp, Prerender
- **SEO 分析**：AhrefsBot, SemrushBot, Majestic-12, RogerBot (Moz), Semantic Scholar

可通过 `simple_theme_crawler_patterns` filter 扩展爬虫列表。

## 文档

- [配置文档 & 错误排查](docs/config-and-troubleshooting.md) — IP 归属地、首页文章数量、常见错误排查等

## 许可证

[CC BY-NC-ND 4.0](https://creativecommons.org/licenses/by-nc-nd/4.0/)

**署名-非商业性使用-禁止演绎** — 您可以复制、分发本主题，但不得修改或用于商业用途。

Copyright © 2026 [worable](https://github.com/worable233)
