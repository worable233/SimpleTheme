<h1 align="center">Simple Theme</h1>

<p align="center">Vue 3 SPA × WordPress REST API — 轻量、现代、SEO 友好的 WordPress 主题。</p>

<p align="center">
  <a href="https://github.com/worable233/SimpleTheme/releases"><img src="https://img.shields.io/github/v/release/worable233/SimpleTheme?style=flat-square" alt="Release"></a>
  <a href="https://github.com/worable233/SimpleTheme/actions/workflows/build.yml"><img src="https://img.shields.io/github/actions/workflow/status/worable233/SimpleTheme/build.yml?style=flat-square" alt="Build"></a>
  <a href="./LICENSE"><img src="https://img.shields.io/github/license/worable233/SimpleTheme?style=flat-square" alt="MIT 许可证"></a>
  <img src="https://img.shields.io/badge/WordPress-6.0%2B-21759B?style=flat-square&logo=wordpress&logoColor=white" alt="WordPress 6.0+">
  <img src="https://img.shields.io/badge/PHP-7.3%2B-777BB4?style=flat-square&logo=php&logoColor=white" alt="PHP 7.3+">
  <img src="https://img.shields.io/badge/Vue-3-4FC08D?style=flat-square&logo=vuedotjs&logoColor=white" alt="Vue 3">
</p>

<p align="center">
  <strong>简体中文</strong> ·
  <a href="https://www.worable.top/">在线预览</a> ·
  <a href="https://github.com/worable233/SimpleTheme/releases">下载主题</a> ·
  <a href="./docs/config-and-troubleshooting.md">配置说明</a> ·
  <a href="./docs/rest-api.md">REST API</a> ·
  <a href="#参与开发">参与开发</a>
</p>

---

> [!TIP]
> 前台是 Vue 3 单页应用，但首屏 HTML 由 PHP 直出：同一份语义化标记既喂给爬虫和社交分享，也作为前端接管前与离线时的兜底。做 SEO 不需要额外插件。

主题面向个人博客：文章、页面、分类、标签、日期归档走同一套路由；评论、说说、归档、友链都是内置页面，不需要装插件才能用。

## 从这里开始

| 你要做什么 | 从这里开始 |
| --- | --- |
| 给博客装上这个主题 | 从 [Releases](https://github.com/worable233/SimpleTheme/releases) 下载 `Simple-Theme-vX.Y.Z.zip`，后台 **外观 → 主题 → 添加新主题 → 上传主题**，启用后到 **设置 → 固定链接** 保存一次。 |
| 调首页、配色、评论、邮件 | 后台 **设置 → Simple Theme**：Vue 3 设置面板，改完即生效；主题色基于 Material Design 3 动态配色，自动派生深浅两套色板。 |
| 某个设置不生效、页面报错 | 看 [配置与故障排查](./docs/config-and-troubleshooting.md)。 |
| 用脚本或自己的前端取数据 | 看 [REST API](./docs/rest-api.md)，主题命名空间为 `simple-theme/v1`。 |
| 改代码、发新版 | 阅读[参与开发](#参与开发)，推送 `v*` 标签后由 GitHub Actions 构建并发布 Release。 |

> [!WARNING]
> 主题会把服务端直出的 HTML 作为首屏与离线兜底保留在页面中（`#st-static`），资源文件名带内容哈希。升级主题后请清一次页面缓存 / CDN 缓存，否则可能仍在使用旧版本的静态副本。

## 主要功能

### 内容与阅读

| 能力 | 说明 |
| --- | --- |
| 单页导航 | 文章、页面、分类、标签、日期归档统一由 REST 解析路径，切换无整页刷新。 |
| 服务端直出 | 首页、列表、存档、单篇与 `/categories/<slug>/` 都由 PHP 直出与前端同构的标记；SEO 元信息、Open Graph、JSON-LD 集中在服务端生成。 |
| 正文增强 | 标题锚点、目录、阅读进度、Prism 代码高亮、图片懒加载与灯箱、正文图标内联为 SVG。 |
| 内置页面 | `/shuoshuo`、`/about`、`/archives`、`/links` 由主题提供，接口缺失时也有兜底视图。 |
| 区块兼容 | 适配 Gutenberg 常用区块，并兼容 Sakurairo 的 showcard、notice 等区块数据。 |

### 评论与互动

| 能力 | 说明 |
| --- | --- |
| 评论体系 | 嵌套回复、点赞、Markdown、表情选择器、私密评论与邮件通知。 |
| 人机验证 | 后端按 ALTCHA v2 协议出题（`sha256(salt + number)`），前端在 Worker 里求解，点「发表评论」后以主题风格浮层呈现，通过即自动发送。 |
| 站点互动 | 公告（弹窗 / 胶囊两种形态）、Cookie 同意、站内搜索、一言、访问统计。 |
| 邮件 | SMTP 发送、邮件模板跟随站点色板、异步发送队列与发送记录。 |

### 外观与布局

| 能力 | 说明 |
| --- | --- |
| 主题模式 | 自动 / 浅色 / 深色三态，默认跟随系统并实时响应系统切换。 |
| 响应式 | 桌面左侧图标导航 + 右侧小工具栏；移动端折叠为抽屉，列表与详情之间有推进转场。 |
| 侧边栏 | 小工具可排序、按需启用（个人资料、一言、站点信息、自定义内容）。 |
| 后台 | 设置面板是独立 Vue 3 应用，登录页与后台侧栏样式与前台统一。 |

### 性能与工程

| 能力 | 说明 |
| --- | --- |
| 首屏体积 | 图标数据分层：首屏只带核心图标，其余按需拉取；表单验证码求解放在 Worker 线程。 |
| 请求数 | 游客跳过鉴权请求，导航与站点信息合并，悬停预取真正命中缓存，文章页少一个往返。 |
| 缓存 | 接口 LRU 缓存 + Service Worker 预缓存页面、接口与图片；离线时回落到静态副本。 |
| 图标动效 | 自研轻量形变引擎（多条子路径独立渲染），主题切换图标平滑过渡，无残点或整帧闪烁。 |

## 安装

从 [Releases](https://github.com/worable233/SimpleTheme/releases) 下载最新的 `Simple-Theme-vX.Y.Z.zip`，后台 **外观 → 主题 → 添加新主题 → 上传主题**，安装并启用。

运行环境：

- WordPress 6.0 或更高版本
- PHP 7.3 或更高版本（含 8.x）

启用后建议到 **设置 → 固定链接** 保存一次，让文章与归档链接按当前规则重新生成。若要用评论验证码、SMTP 邮件，先在 **设置 → Simple Theme** 里打开对应开关。

## 参与开发

构建需要 Node.js `^20.19.0 || >=22.12.0`。WordPress 后端可以是本地站点、Docker，也可以是线上测试站。

```bash
npm install        # 安装依赖
npm run dev        # Vite 开发服务器（需要 WordPress 后端）
npm run build      # 类型检查 + 构建到 dist/
npm run build-only # 跳过类型检查，只构建
npm run lint       # oxlint + eslint（自动修复）
npm run package    # 构建并生成主题 ZIP
```

`npm run dev` 的前端需要能访问 WordPress 的 REST API：在 `.env` 里设置 `VITE_WP_SITE_URL`；没有后端时设 `VITE_USE_MOCK=true` 用内置假数据开发。`npm run dev:docker` 会用 `.env.docker` 覆盖配置并连到 Docker 里的 WordPress。

如果本地用 [WordPress Studio](https://developer.wordpress.com/studio/) 并把本仓库软链为当前主题，站点读的是 `dist/`，改完跑 `npm run build-only` 刷新即可看到效果。

## 项目结构

```text
src/        Vue 3 前台与后台设置面板：views、components、composables、ui 组件库、lib、styles、blocks
inc/        PHP 集成：REST、SEO、评论、SMTP 与邮件队列、本地头像、小工具、主题选项
templates/  服务端直出的标记（首屏内容与静态存档兜底）
assets/     不经过构建的静态资源（登录页样式等）
bin/        构建辅助脚本：图标生成、资源拷贝、打包
docs/       配置说明与 REST API 文档
dist/       构建产物，由 WordPress 按 Vite manifest 加载
```

前端拿到路径后先走主题的 `resolve-url` 接口，再决定渲染文章、页面、列表还是内置页面；`/shuoshuo`、`/about`、`/archives`、`/links` 是保留路径，其余交给 WordPress 的固定链接规则。

## 与 WordPress 的关系

主题只读取 WordPress 数据，不改核心、不写死表结构：评论读写用原生 `wp/v2/comments`，其他能力集中在 `simple-theme/v1`；PHP 侧保持 7.3 兼容，不依赖必须安装的插件。唯一的额外数据表是邮件发送队列，卸载时保留，方便排查历史记录。

推送 `v*` 标签会触发 [build.yml](./.github/workflows/build.yml)，自动构建并发布带 ZIP 的 Release；本地也可以 `npm run package` 手动打包。

## 致谢

- [Sakurairo](https://github.com/mirai-mamori/Sakurairo)（GPL-2.0）：部分区块与数据兼容实现参考了其做法
- [iEmo](https://github.com/kannafay/iEmo)（MIT）：视觉设计灵感来源
- [PrismJS](https://github.com/PrismJS/prism)（MIT）：代码高亮
- [unDraw](https://undraw.co/)（开放许可）：插画素材
- [Tabler Icons](https://github.com/tabler/tabler-icons)（MIT）：界面图标

## 许可证

[MIT](./LICENSE)。

构建产物中分发若干第三方组件与素材，其版权与许可见 [THIRD-PARTY-NOTICES.md](./THIRD-PARTY-NOTICES.md)。
