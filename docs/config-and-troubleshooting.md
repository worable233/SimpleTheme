# 配置与故障排查

## 主题设置

所有设置集中在 **WordPress 后台 → 设置 → Simple Theme**（Vue 3 设置面板）：
首页文章数量、主题色、公告、评论、SMTP 邮件、Cookie 合规等。

主题色基于 Material Design 3 动态配色，种子色逻辑见 `src/lib/theme-config.ts`。

## 评论系统

基于 WordPress 原生 `wp/v2/comments`，主题扩展了嵌套回复、点赞、Markdown、
表情包、ALTCHA 验证码、IP 归属地与浏览器信息展示。

IP 归属地按优先级查询，失败自动回退，均不可用时显示「未知地区」：

| 优先级 | API | 说明 |
|--------|-----|------|
| 1 | api2.upk.com.cn | 主接口，精确到区县 |
| 2 | ip-api.com | 回退接口，海外主机友好 |

## 故障排查

**改了代码 / 更新主题后页面没变化**
前端启用了 Service Worker 缓存。硬刷新（`⌘⇧R` / `Ctrl+F5`），或在
开发者工具 → Application → Service Workers 中 Unregister 后刷新。

**头像一直转圈或加载超时**
服务器无法直连 gravatar.com 时，主题已自动改写到镜像源；若仍失败，
检查服务器出网策略，或在设置面板启用头像代理。

**未登录无法评论**
确认 WordPress 后台 **设置 → 讨论** 允许匿名评论；主题已注册
`rest_allow_anonymous_comments` 过滤器，无需插件。

**邮件通知发不出去**
在设置面板配置 SMTP 后使用「发送测试邮件」验证；失败邮件进入队列，
可在面板中查看错误原因并重试。

**搜索引擎收录的是空白页？**
不会——主题在 `index.php` 直出完整静态 HTML（含 `<noscript>`），
无需额外配置。可用 `curl https://你的域名/文章地址/` 验证输出。
