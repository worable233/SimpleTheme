# Simple Theme

Simple Theme 是一个面向个人博客的 WordPress 主题。前台使用 Vue 3 和 WordPress REST API，后台保留 WordPress 的管理流程，并提供独立的主题设置面板。

[![Release](https://img.shields.io/github/v/release/worable233/SimpleTheme?style=flat-square)](https://github.com/worable233/SimpleTheme/releases)
[![Build](https://img.shields.io/github/actions/workflow/status/worable233/SimpleTheme/build.yml?style=flat-square)](https://github.com/worable233/SimpleTheme/actions)
[![License](https://img.shields.io/badge/license-CC%20BY--NC--ND%204.0-red?style=flat-square)](https://creativecommons.org/licenses/by-nc-nd/4.0/)
[![Vue 3](https://img.shields.io/badge/Vue-3.x-4FC08D?style=flat-square&logo=vuedotjs&logoColor=white)](https://vuejs.org/)
[![WordPress](https://img.shields.io/badge/WordPress-6.0%2B-21759B?style=flat-square&logo=wordpress&logoColor=white)](https://wordpress.org/)

[在线预览](https://www.worable.top/) · [下载主题](https://github.com/worable233/SimpleTheme/releases) · [配置说明](docs/config-and-troubleshooting.md) · [REST API](docs/rest-api.md)

## 主要功能

- Vue 3 单页导航，文章、页面、分类、标签和日期归档统一处理
- 响应式布局，支持浅色/深色模式和自定义主题色
- 文章评论、嵌套回复、点赞、Markdown、表情和可选验证码
- 服务端输出基础 HTML，兼顾搜索引擎、无 JavaScript 环境和社交分享信息
- 适配 Gutenberg 常用区块，并兼容部分 Sakurairo 区块数据
- 可配置的侧边栏小工具、公告、Cookie 提示、站点统计和一言
- SMTP 邮件通知与异步发送队列
- Prism 代码高亮、头像代理和本地头像支持
- WordPress 后台主题设置页与登录页样式统一

## 安装

从 [Releases](https://github.com/worable233/SimpleTheme/releases) 下载 `Simple-Theme-vX.Y.Z.zip`，然后在 WordPress 后台打开 **外观 → 主题 → 添加新主题 → 上传主题**，安装并启用即可。

运行环境：

- WordPress 6.0 或更高版本
- PHP 7.3 或更高版本

启用主题后，建议到 **设置 → 固定链接** 保存一次，确保文章和归档链接正常工作。

## 本地开发

```bash
npm install        # 安装依赖（Node.js ≥ 20.19）
npm run dev        # 启动 Vite 开发服务器，需要 WordPress 后端
npm run build      # 类型检查并构建到 dist/
npm run package    # 构建并生成主题 ZIP
```

项目使用 Vite 构建前台和后台资源。推送 `v*` 标签后，GitHub Actions 会自动构建并发布 Release。

## 项目结构

```text
src/    Vue 3 前台、组件、组合式函数、样式和后台设置面板
inc/    PHP 集成代码：主题功能、REST API、SEO、后台选项和小工具
dist/   构建产物，由 WordPress 根据 Vite manifest 加载
```

前端通过 REST API 解析当前路径，再决定显示文章、页面、分类、标签或日期归档。`/about`、`/archives`、`/links` 和 `/shuoshuo` 是主题内置页面；其他路径仍按 WordPress 的固定链接规则解析。

## 致谢

- [Sakurairo](https://github.com/mirai-mamori/Sakurairo)（GPL-2.0）：参考其部分区块和数据兼容实现
- [iEmo](https://github.com/kannafay/iEmo)（MIT）：提供视觉设计方面的参考，未使用其代码

## 许可证

[CC BY-NC-ND 4.0](https://creativecommons.org/licenses/by-nc-nd/4.0/) — 署名、非商业使用、禁止演绎
