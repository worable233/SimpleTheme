/**
 * 文章预览（?p=ID&preview=true / &preview_id=...）
 *
 * 预览页由 WordPress 服务端渲染，但 SPA 的路由解析（resolve-url）只认
 * 公开路径，查询串里的 p/preview 会被剥离后解析成首页。因此 PHP 在
 * wp_footer 里直出预览文章的真实内容（含未保存的自动草稿修订），
 * 前端直接用它渲染文章页，避免再走一次需要鉴权的 REST/鉴权链路。
 */

export interface PreviewTag {
  name: string
  link: string
}

export interface PreviewPayload {
  id: number
  title: string
  /** 已过 the_content 过滤器的正文 HTML */
  content: string
  date: string
  modified: string
  link: string
  authorName: string
  tags: PreviewTag[]
  status: string
}

/** 当前是否处于预览请求；预览时返回服务端下发的数据 */
export function getPreviewPayload(): PreviewPayload | null {
  const payload = window.__ST_PREVIEW__
  if (!payload || !payload.id) return null
  return payload
}
