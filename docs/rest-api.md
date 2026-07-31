# REST API

命名空间 `simple-theme/v1`，前缀 `/wp-json/simple-theme/v1/`。
评论的读取与提交使用 WordPress 原生 `wp/v2/comments`，主题仅做扩展。

## 公共端点

| 端点 | 方法 | 用途 |
|------|------|------|
| `/site-info` | GET | 站点信息（主题配置 + 统计） |
| `/about` | GET | 关于页数据 |
| `/navigation/{location}` | GET | 导航菜单 |
| `/resolve-url` | POST | 路径 → 内容类型解析（`path`） |
| `/collection` | GET | 文章/说说列表（`type` `page` `limit`） |
| `/home-posts` | GET | 首页分页查询（`taxonomy` 过滤） |
| `/pages` | GET | 页面列表 |
| `/track-view` | POST | 记录文章阅读数 |
| `/links` | GET | 友情链接 |
| `/illustration/{name}` | GET | unDraw 插画 SVG |
| `/avatar-proxy` | GET | 头像代理（`hash` / `qq` / `s`） |
| `/comment-captcha` | GET | ALTCHA 验证码挑战 |
| `/comment-like` | POST | 评论点赞 |

## 认证端点（登录态相关）

| 端点 | 方法 | 用途 |
|------|------|------|
| `/auth/login` | POST | 登录 |
| `/auth/register` | POST | 注册 |
| `/auth/lost-password` | POST | 找回密码 |
| `/auth/validate-reset-key` | GET | 校验重置链接 |
| `/auth/reset-password` | POST | 重置密码 |
| `/auth/me` | GET | 当前用户信息 |
| `/comment-delete` | POST | 删除自己的评论 |
| `/comment-pin` | POST | 置顶评论（需管理权限） |
| `/user-pending-comments` | GET | 当前用户待审评论 |

## 管理端点（需 `manage_options` + `X-WP-Nonce`）

| 端点 | 方法 | 用途 |
|------|------|------|
| `/settings` | GET / POST | 主题设置读写 |
| `/smtp-test` | POST | SMTP 发信测试 |
| `/mail-queue` | GET | 邮件队列列表 |
| `/mail-queue/retry/{id}` | PUT | 重试队列邮件 |
| `/mail-queue/clear` | PUT | 清空队列 |
| `/email-templates` | GET | 邮件模板列表 |
| `/email-template-preview` | GET | 邮件模板预览 |

## 约定

- 成功返回 `200/201`；错误返回标准 WP 错误结构（`code` / `message` / `data.status`）
- 常见错误码：`400` 参数错误 · `403` 权限/验证码不通过 · `404` 未找到
- 匿名评论提交依赖 `rest_allow_anonymous_comments` 过滤器（主题已注册）

## 示例

```bash
# 解析任意路径
curl -X POST https://example.com/wp-json/simple-theme/v1/resolve-url \
  -H 'Content-Type: application/json' -d '{"path":"/2026/07/31/hello/"}'

# 文章列表（第 2 页，每页 10 篇）
curl 'https://example.com/wp-json/simple-theme/v1/collection?type=post&page=2&limit=10'
```

详细字段以 `inc/rest/`、`inc/core/auth-handler.php`、`inc/core/smtp-handler.php` 源码为准。
