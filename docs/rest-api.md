# SimpleTheme REST API 文档

API 版本：`simple-theme/v1`

所有端点都以 `/wp-json/simple-theme/v1/` 为前缀。  
公共端点无需认证；管理端点需要 `manage_options` 权限（需在请求头中传递 `X-WP-Nonce`）。

---

## 目录

| # | 端点 | 方法 | 用途 | 需认证 |
|---|------|------|------|--------|
| 1 | [/site-info](#1-site-info) | GET | 站点信息（主题配置 + 统计） | 否 |
| 2 | [/about](#2-about) | GET | 关于页面数据 | 否 |
| 3 | [/navigation/{location}](#3-navigationlocation) | GET | 导航菜单 | 否 |
| 4 | [/resolve-url](#4-resolve-url) | POST | 路径 → 内容类型映射 | 否 |
| 5 | [/collection](#5-collection) | GET | 文章/说说列表 | 否 |
| 6 | [/home-posts](#6-home-posts) | GET | 分类过滤的分页查询 | 否 |
| 7 | [/track-view](#7-track-view) | POST | 记录文章阅读数 | 否 |
| 8 | [/comments/{post_id}](#8-commentspost_id) | GET | 获取评论列表 | 否 |
| 9 | [/comments](#9-comments) | POST | 提交评论 | 否 |
| 10 | [/comment-captcha](#10-comment-captcha) | GET | 获取评论验证码 | 否 |
| 11 | [/comment-like](#11-comment-like) | POST | 点赞评论 | 否 |
| 12 | [/comment-edit](#12-comment-edit) | POST | 编辑评论 | 视配置 |
| 13 | [/comment-history/{id}](#13-comment-historyid) | GET | 评论编辑历史 | 视配置 |
| 14 | [/comment-pin](#14-comment-pin) | POST | 置顶/取消置顶评论 | 需权限 |
| 15 | [/links](#15-links) | GET | 友情链接 | 否 |
| 16 | [/avatar-proxy](#16-avatar-proxy) | GET | 头像代理（Gravatar） | 否 |
| 17 | [/illustration/{name}](#17-illustrationname) | GET | 插图 SVG 文件 | 否 |
| 18 | [/settings](#18-settings) | GET/POST | 主题设置（管理后台） | 需权限 |

---

## 1. `/site-info`

**方法：** `GET`  
**权限：** `__return_true`（公开）

返回站点名称、描述、主题配置、Hero 区域、评论设置、统计信息等。

### 示例请求

```
GET /wp-json/simple-theme/v1/site-info
```

### 示例响应

```json
{
  "name": "我的站点",
  "description": "在这里发布文章、页面与说说内容。",
  "url": "https://example.com/",
  "siteIcon": "https://example.com/wp-content/uploads/icon.png",
  "hero": {
    "image": "",
    "showAvatar": true,
    "avatar": "https://example.com/avatar.jpg",
    "subtitle": "开发者 / 设计师"
  },
  "comments": {
    "requireNameEmail": true,
    "registrationOnly": false,
    "showEmailField": true,
    "showUrlField": true,
    "showCookiesOptIn": true,
    "captchaEnabled": true,
    "showPrivateOption": true,
    "showMarkdownOption": true
  },
  "stats": {
    "postCount": 25,
    "shuoshuoCount": 2,
    "categoryCount": 4,
    "tagCount": 12,
    "totalWordCount": 27746,
    "commentCount": 17,
    "registeredDate": "2024-01-13T09:00:00+08:00",
    "lastActivityDate": "2026-04-15T10:00:00+08:00"
  },
  "collections": {
    "postsTitle": "最新文章",
    "postsSubtitle": "整理过的长文、笔记与项目更新。",
    "shuoshuoTitle": "最近说说",
    "shuoshuoSubtitle": "",
    "showShuoshuoSection": true,
    "homePostCount": 6,
    "homeShuoshuoCount": 3,
    "shuoshuoPageSize": 12
  },
  "socialLinks": [
    { "label": "GitHub", "url": "https://github.com/", "icon": "bx bxl-github" }
  ],
  "theme": {
    "primaryColor": "#333333",
    "bodyFont": "...",
    "radius": "medium",
    "shadow": "small",
    "cardMeta": {
      "showCategory": true,
      "showPublishDate": true,
      "showModifiedDate": false,
      "showCommentCount": true,
      "showViewCount": true,
      "showReadingTime": true,
      "showWordCount": false
    }
  },
  "loginUrl": "https://example.com/wp-login.php",
  "icp": "沪ICP备2024XXXXXX号",
  "icpGov": "沪公网安备 310XXXXXXXXXX号",
  "endNote": "好像就这么多",
  "currentUser": null
}
```

### 字段说明

| 字段 | 类型 | 说明 |
|------|------|------|
| `name` | string | 站点名称 |
| `description` | string | 站点副标题 |
| `url` | string | 首页 URL |
| `siteIcon` | string | 站点图标 URL |
| `hero` | object | Hero 区域配置 |
| `hero.image` | string | 封面图 URL |
| `hero.showAvatar` | boolean | 是否显示头像 |
| `hero.avatar` | string | 头像 URL |
| `hero.subtitle` | string | 座右铭/副标题 |
| `theme` | object | 主题外观配置 |
| `comments` | object | 评论表单配置 |
| `collections` | object | 首页集合配置 |
| `stats` | object | 站点统计（见下表） |
| `socialLinks` | array | 社交链接列表 |
| `loginUrl` | string | 登录 URL |
| `icp` | string | ICP 备案号 |
| `icpGov` | string | 公安备案号 |
| `endNote` | string | 文章列表末尾备注 |
| `currentUser` | object\|null | 当前登录用户信息 |

#### stats 子字段

| 字段 | 类型 | 说明 |
|------|------|------|
| `postCount` | number | 已发布文章数 |
| `shuoshuoCount` | number | 说说数 |
| `categoryCount` | number | 分类数 |
| `tagCount` | number | 标签数 |
| `totalWordCount` | number | 总字数 |
| `commentCount` | number | 已批准评论数 |
| `registeredDate` | string | 最早文章发布日期（ISO 8601） |
| `lastActivityDate` | string | 最近文章修改日期（ISO 8601） |

> 统计结果缓存 1 小时（transient `simple_theme_site_stats_v2`）。

---

## 2. `/about`

**方法：** `GET`  
**权限：** `__return_true`（公开）

返回关于页面数据。数据存储在主题设置的 `about_info` JSON 字段中。

### 示例请求

```
GET /wp-json/simple-theme/v1/about
```

### 示例响应

```json
{
  "avatar": "https://example.com/avatar.jpg",
  "subtitleLines": ["永恒理想的筑梦师", "内心世界的漫游者"],
  "identityTags": ["计算机爱好者", "博主"],
  "greeting": "你好，很高兴认识你👋",
  "sloganBlock": "在\n不断探索中\n学习·生活·创作",
  "skills": ["Java", "Vue", "TypeScript"],
  "timeline": [
    { "period": "2020 — 2024", "title": "大学", "subtitle": "酿酒工程", "image": "" }
  ],
  "mbtiType": "INFP-T",
  "mbtiLabel": "调停者",
  "games": [
    { "name": "原神", "icon": "", "uid": "UID: 173516657" }
  ],
  "animeTitle": "时光代理人",
  "animeTagline": "不论过去，不问将来",
  "musicArtists": "JUSF周存、HOYO-MIX",
  "musicUrl": "https://music.example.com",
  "location": "中国，北京",
  "birthYear": 2003,
  "education": "大学专业",
  "occupation": "岗位",
  "sponsorTotal": "¥ 1180.44",
  "sponsorList": [
    { "name": "用户", "amount": "¥ 20" }
  ],
  "sponsorUrl": "/sponsor",
  "donationWechatQr": "",
  "donationAlipayQr": "",
  "donationTotal": "总金额：¥ 1180.44"
}
```

> 如果未设置 `about_info`，返回空对象 `{}`。  
> 前端 `AboutView.vue` 同时会尝试加载 WordPress 页面 slug 为 `about` 的内容。

---

## 3. `/navigation/{location}`

**方法：** `GET`  
**权限：** `__return_true`（公开）

获取指定位置的导航菜单。

### 路径参数

| 参数 | 类型 | 说明 | 可选值 |
|------|------|------|--------|
| `location` | string | 菜单位置标识 | `primary` / `footer` |

### 示例请求

```
GET /wp-json/simple-theme/v1/navigation/primary
```

### 示例响应

```json
{
  "items": [
    {
      "id": 1,
      "title": "首页",
      "url": "https://example.com/",
      "path": "/",
      "target": "_self",
      "description": "",
      "icon": "home",
      "children": []
    }
  ]
}
```

### 字段说明

| 字段 | 类型 | 说明 |
|------|------|------|
| `items` | array | 菜单项列表（树形结构） |
| `items[].id` | number | 菜单项 ID |
| `items[].title` | string | 显示文字 |
| `items[].url` | string | 完整 URL |
| `items[].path` | string | 内部路径 |
| `items[].target` | string | 链接目标 |
| `items[].description` | string | 描述 |
| `items[].icon` | string | 图标 CSS class（来自 `_menu_item_icon` 元数据） |
| `items[].children` | array | 子菜单项 |

---

## 4. `/resolve-url`

**方法：** `POST`  
**权限：** `__return_true`（公开）

根据路径解析内容类型和 REST URL。前端路由守卫在每次导航时调用。

### 请求体

```json
{
  "path": "/hello-world/"
}
```

### 响应 — 成功

```json
{
  "type": "post",
  "restUrl": "https://example.com/wp-json/wp/v2/posts/123?_embed=1",
  "id": 123
}
```

### 响应类型 `type` 取值

| 值 | 说明 |
|------|------|
| `post` | 文章 |
| `page` | 页面 |
| `shuoshuo` | 说说 |
| `category` | 分类归档 |
| `tag` | 标签归档 |
| `error` | 解析失败 |

### 响应 — 错误

```json
{
  "type": "404",
  "message": "页面未找到"
}
```

> 前端也会用 WP REST API 的 `wp/v2/posts` / `wp/v2/pages` 做 fallback 解析。

---

## 5. `/collection`

**方法：** `GET`  
**权限：** `__return_true`（公开）

获取首页文章/说说集合，支持分页和类型过滤。

### 查询参数

| 参数 | 类型 | 必填 | 默认值 | 说明 |
|------|------|------|--------|------|
| `type` | string | 否 | `post` | 内容类型：`post` / `shuoshuo` / `page` |
| `page` | number | 否 | `1` | 页码 |
| `limit` | number | 否 | 主题设置值 | 每页数量（最多 50） |
| `taxonomy` | string | 否 | - | 分类法名称（如 `category` / `post_tag`） |
| `termId` | number | 否 | - | 分类法术语 ID |

### 示例请求

```
GET /wp-json/simple-theme/v1/collection?type=post&page=1&limit=6
```

### 示例响应

```json
{
  "items": [
    {
      "id": 1,
      "title": { "rendered": "你好，世界" },
      "slug": "hello-world",
      "type": "post",
      "link": "https://example.com/hello-world/",
      "date": "2025-01-15 10:00:00",
      "modified": "2025-01-15 10:00:00",
      "featuredImage": null,
      "excerpt": { "rendered": "<p>这是我的第一篇文章</p>" },
      "categories": ["未分类"],
      "tags": [],
      "comment_status": "open",
      "comment_count": 3,
      "viewCount": 128,
      "wordCount": 350,
      "readingTime": 2
    }
  ],
  "total": 25,
  "totalPages": 5,
  "page": 1,
  "perPage": 6,
  "showShuoshuoSection": true,
  "shuoshuoPosts": [...]
}
```

### 字段说明（顶层）

| 字段 | 类型 | 说明 |
|------|------|------|
| `items` | array | 格式化后的文章/说说列表 |
| `shuoshuoPosts` | array | （仅首页）说说列表 |
| `total` | number | 总数 |
| `totalPages` | number | 总页数 |
| `page` | number | 当前页码 |
| `perPage` | number | 每页数量 |
| `showShuoshuoSection` | boolean | 是否显示说说区 |

> 前端有 fallback 逻辑：如果 404 则回退到 WP 原生 REST API `wp/v2/posts` / `wp/v2/pages` / `wp/v2/shuoshuo`。

---

## 6. `/home-posts`

**方法：** `GET`  
**权限：** `__return_true`（公开）

分类过滤的分页查询（与 `/collection` 共享回调函数，当 `taxonomy` + `termId` 参数存在时自动路由到此处）。

### 查询参数

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| `type` | string | 否 | 内容类型 |
| `page` | number | 否 | 页码 |
| `limit` | number | 否 | 每页数量 |
| `taxonomy` | string | 是 | 分类法名称 |
| `termId` | number | 是 | 术语 ID |

### 示例响应

```json
{
  "items": [...],
  "total": 12,
  "totalPages": 2,
  "page": 1,
  "perPage": 10
}
```

---

## 7. `/track-view`

**方法：** `POST`  
**权限：** `__return_true`（公开）

记录文章阅读次数（+1）。

### 请求体

```json
{
  "postId": 123
}
```

### 响应

```json
{
  "viewCount": 129
}
```

> 阅读次数存储在 `postmeta` 的 `views` 字段。

---

## 8. `/comments/{post_id}`

**方法：** `GET`  
**权限：** `__return_true`（公开）

获取指定文章的评论列表（树形结构）。

### 路径参数

| 参数 | 类型 | 说明 |
|------|------|------|
| `post_id` | number | 文章 ID |

### 查询参数

| 参数 | 类型 | 默认值 | 说明 |
|------|------|--------|------|
| `page` | number | `1` | 页码 |
| `perPage` | number | `20` | 每页数量（5–50） |
| `client_id` | string | - | 客户端 ID（用于识别当前用户的评论） |

### 示例响应

```json
{
  "items": [
    {
      "id": 1,
      "parent": 0,
      "date": "2025-01-15 10:00:00",
      "authorName": "访客",
      "authorEmail": "guest@example.com",
      "authorUrl": "",
      "avatar": "https://q1.qlogo.cn/g?b=qq&nk=12345&s=100",
      "content": { "rendered": "<p>好文章！</p>" },
      "likes": 3,
      "isPinned": true,
      "isPrivate": false,
      "canEdit": false,
      "canPin": false,
      "useMarkdown": false,
      "qqAvatar": "",
      "children": [...]
    }
  ],
  "total": 17,
  "page": 1,
  "perPage": 20,
  "totalPages": 1
}
```

### 字段说明

| 字段 | 类型 | 说明 |
|------|------|------|
| `isPinned` | boolean | 是否置顶 |
| `isPrivate` | boolean | 是否私密评论 |
| `canEdit` | boolean | 当前用户能否编辑 |
| `canPin` | boolean | 当前用户能否置顶 |
| `likes` | number | 点赞数 |
| `avatar` | string | 头像 URL（QQ 优先 → Gravatar → Avatar Proxy） |
| `qqAvatar` | string | QQ 头像 URL（单独字段） |

---

## 9. `/comments`

**方法：** `POST`  
**权限：** `__return_true`（公开）

提交评论。

### 请求体

```json
{
  "post": 123,
  "parent": 0,
  "author_name": "访客",
  "author_email": "guest@example.com",
  "author_url": "",
  "content": "好文章！",
  "client_id": "abc123",
  "captchaSeed": "验证码种子",
  "captchaAnswer": 42,
  "isPrivate": false,
  "mailNotify": true,
  "useMarkdown": false
}
```

### 响应（201 Created）

```json
{
  "item": { "id": 100, ... }
}
```

### 字段说明

| 字段 | 类型 | 必填 | 说明 |
|------|------|------|------|
| `post` | number | 是 | 文章 ID |
| `parent` | number | 否 | 父评论 ID |
| `author_name` | string | 是 | 显示名称 |
| `author_email` | string | 否 | 邮箱 |
| `author_url` | string | 否 | 网站 URL |
| `content` | string | 是 | 评论内容（HTML） |
| `client_id` | string | 是 | 客户端识别 ID |
| `captchaSeed` | string | 视配置 | 验证码种子 |
| `captchaAnswer` | number | 视配置 | 验证码答案 |
| `isPrivate` | boolean | 否 | 是否私密 |
| `mailNotify` | boolean | 否 | 是否邮件通知 |
| `useMarkdown` | boolean | 否 | 是否使用 Markdown |

---

## 10. `/comment-captcha`

**方法：** `GET`  
**权限：** `__return_true`（公开）

获取评论验证码（简单算术题）。

### 示例响应

```json
{
  "seed": "abc123def456",
  "question": "7 × 8 = ?"
}
```

> 答案存储在 transient `st_captcha_{seed}` 中，有效期 10 分钟，验证后自动删除。

---

## 11. `/comment-like`

**方法：** `POST`  
**权限：** `__return_true`（公开）

点赞评论（IP 去重，同一 IP 只能点赞一次）。

### 请求体

```json
{
  "commentId": 1
}
```

### 响应

```json
{
  "likes": 4
}
```

> 如果已点过赞，返回 403 `{"error": "已经点过赞了"}`。

---

## 12. `/comment-edit`

**方法：** `POST`  
**权限：** `simple_theme_user_can_edit_comment()`（cookie 验证或登录用户）

编辑评论。

### 请求体

```json
{
  "commentId": 1,
  "content": "修改后的内容"
}
```

### 响应

```json
{
  "item": { "id": 1, ... }
}
```

> 编辑前的旧内容会自动保存到编辑历史。编辑权限由 cookie `comment_author_email_{hash}` 或当前登录用户 ID 判定。

---

## 13. `/comment-history/{id}`

**方法：** `GET`  
**权限：** `__return_true`（公开）

获取评论编辑历史。

### 路径参数

| 参数 | 类型 | 说明 |
|------|------|------|
| `id` | number | 评论 ID |

### 示例响应

```json
{
  "history": [
    { "content": "旧内容", "time": "2025-01-15 10:30:00" }
  ]
}
```

---

## 14. `/comment-pin`

**方法：** `POST`  
**权限：** `moderate_comments`（管理员/编辑）

置顶或取消置顶评论。

### 请求体

```json
{
  "commentId": 1,
  "pin": true
}
```

### 响应

```json
{
  "pinned": true,
  "id": 1
}
```

---

## 15. `/links`

**方法：** `GET`  
**权限：** `__return_true`（公开）

获取友情链接（按链接分类分组）。

### 示例响应

```json
[
  {
    "id": 1,
    "name": "推荐博客",
    "slug": "recommended",
    "description": "优秀个人博客推荐",
    "links": [
      {
        "id": 1,
        "name": "示例博客",
        "url": "https://example.com",
        "description": "一个示例博客",
        "image": "",
        "target": "_blank",
        "rating": 5,
        "notes": "常驻"
      }
    ]
  }
]
```

> 未分配分类的链接归入 `"name": "未分类"` 组（`id: 0`）。

---

## 16. `/avatar-proxy`

**方法：** `GET`  
**权限：** `__return_true`（公开）

Gravatar 头像代理。服务器端获取 Gravatar 头像并返回，避免前端直接请求 Gravatar 被墙。

### 查询参数

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| `hash` | string | 否 | Gravatar 邮箱 MD5（32 位 hex） |
| `qq` | number | 否 | QQ 号（替代 hash） |
| `s` | number | 否 | 头像尺寸（40–200，默认 80） |

> `hash` 和 `qq` 至少传一个。

---

## 17. `/illustration/{name}`

**方法：** `GET`  
**权限：** `__return_true`（公开）

返回 SVG 插图文件。

### 路径参数

| 参数 | 类型 | 说明 |
|------|------|------|
| `name` | string | 插图文件名（不含路径） |

> 文件从 `dist/illustrations/` 目录读取。  
> 响应头：`Content-Type: image/svg+xml`，`Cache-Control: public, max-age=31536000`（1 年缓存）。

---

## 18. `/settings`

**方法：** `GET` / `POST`  
**权限：** `manage_options`（管理员）

获取/保存主题设置。

### GET（获取设置）

#### 示例响应

```json
{
  "settings": {
    "primary_color": "#333333",
    "body_font": "...",
    "about_info": "{\"avatar\":\"...\"}",
    ...
  },
  "defaults": {
    "primary_color": "#333333",
    ...
  }
}
```

### POST（保存设置）

#### 请求体

```json
{
  "primary_color": "#663399",
  "body_font": "...",
  "about_info": "{\"avatar\":\"...\"}"
}
```

> 仅传入要修改的字段即可，未传入的字段保持原值。  
> `about_info` 字段用于存储关于页面数据（JSON 字符串）。

---

## 通用错误响应

```json
{
  "error": "错误描述信息"
}
```

HTTP 状态码：

| 状态码 | 含义 |
|--------|------|
| 200 | 成功 |
| 201 | 创建成功 |
| 400 | 请求参数错误 |
| 403 | 权限不足 / 验证码错误 / 重复点赞 |
| 404 | 资源未找到 |
| 500 | 服务器内部错误 |

---

## JS API 模块映射

| 前端函数 | 调用的端点 | 所在文件 |
|----------|----------|----------|
| `fetchSiteInfo()` | `GET /site-info` | `src/lib/api-site.ts` |
| `fetchAboutInfo()` | `GET /about` | `src/lib/api-site.ts` |
| `fetchNavigation(location)` | `GET /navigation/{location}` | `src/lib/api-site.ts` |
| `resolveThemePath(path)` | `POST /resolve-url` | `src/lib/api-site.ts` |
| `fetchLinks()` | `GET /links` | `src/lib/api-site.ts` |
| `fetchCollection(type, opts)` | `GET /collection` | `src/lib/api-posts.ts` |
| `fetchLatestPosts(limit)` | `GET /collection` | `src/lib/api-posts.ts` |
| `fetchPostCollectionByTaxonomy(...)` | `GET /collection` | `src/lib/api-posts.ts` |
| `trackPostView(postId)` | `POST /track-view` | `src/lib/api-posts.ts` |
| `fetchComments(postId, ...)` | `GET /comments/{post_id}` | `src/lib/api-comments.ts` |
| `createComment(payload)` | `POST /comments` | `src/lib/api-comments.ts` |
| `fetchCaptcha()` | `GET /comment-captcha` | `src/lib/api-comments.ts` |
| `likeComment(commentId)` | `POST /comment-like` | `src/lib/api-comments.ts` |
| `editComment(commentId, content)` | `POST /comment-edit` | `src/lib/api-comments.ts` |
| `fetchCommentHistory(commentId)` | `GET /comment-history/{id}` | `src/lib/api-comments.ts` |
| `pinComment(commentId, pin)` | `POST /comment-pin` | `src/lib/api-comments.ts` |
| `fetchSettings()` (admin) | `GET /settings` | `src/admin/api.ts` |
| `saveSettings(settings)` (admin) | `POST /settings` | `src/admin/api.ts` |
