/**
 * content-preview — 列表页 → 详情页的内容预览缓存
 *
 * 列表页（首页/说说/归档等）已经拿到每篇文章的标题、特色图等元信息。
 * 写入本缓存后，详情页在正文加载期间即可：
 *   - 立即渲染真实标题和特色图（而非占位骨架）
 *   - 精准决定骨架形态（无特色图的文章不渲染 cover 骨架）
 *
 * 纯内存 Map，跟随 SPA 生命周期，无需失效策略。
 */
import type { WordPressPost } from '@/types/wordpress'
import { toInternalPath } from '@/lib/theme-config'

export interface ContentPreview {
  title: string
  cover: string
  type: string
}

const previews = new Map<string, ContentPreview>()

/** 统一 key：内部路由路径，decode 后比较（WP 链接常含 URL 编码中文） */
function normalizeKey(path: string): string {
  try {
    return decodeURIComponent(path)
  } catch {
    return path
  }
}

/** 列表页调用：批量写入文章预览信息 */
export function rememberPreviews(posts: WordPressPost[]): void {
  for (const post of posts) {
    if (!post.link) continue
    previews.set(normalizeKey(toInternalPath(post.link)), {
      title: post.title?.rendered || '',
      cover: post.featuredImage || '',
      type: post.type || 'post',
    })
  }
}

/** 详情页调用：按当前路由路径取预览信息 */
export function getContentPreview(routePath: string): ContentPreview | undefined {
  return previews.get(normalizeKey(routePath))
}
