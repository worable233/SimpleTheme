/**
 * 内容预取 + 解析缓存键
 *
 * 悬停文章卡片时提前把 resolve-url 和正文拿进内存缓存，点击后直接命中，
 * 省掉 1~2 个网络往返。缓存键必须和 ContentView 读的一模一样，否则白预取
 * （之前 HomeView 用的是带尾斜杠的原始路径，和 ContentView 的键对不上）。
 */
import { fetchContentByRestUrl, resolveThemePath } from './wordpress'
import { primeCache, withCache } from './api-cache'
import { toInternalPath, toResolvablePath } from './theme-config'

const RESOLVE_TTL = 30_000
const CONTENT_TTL = 600_000

const ALREADY_PREFETCHED = new Set<string>()

/**
 * resolve-url 的缓存键：去掉尾斜杠、保留查询串。
 * route.path 是解码后的形式，链接里的 href 是编码后的，这里统一解码。
 */
export function resolveCacheKey(href: string): string {
  let pathname = href
  let search = ''

  try {
    const url = new URL(toInternalPath(href), window.location.origin)
    pathname = url.pathname
    search = url.search
  } catch {
    // 非法 URL：退化成原串，交给上层报错
  }

  try {
    pathname = decodeURIComponent(pathname)
  } catch {
    // 解码失败就保持原样
  }

  const normalized = pathname.length > 1 ? pathname.replace(/\/+$/, '') : pathname
  return `resolve:${normalized}${search}`
}

/** 预取某个内部路径对应的文章内容；重复调用只发一次 */
export function prefetchContent(href: string): void {
  if (!href) return

  const key = resolveCacheKey(href)
  if (ALREADY_PREFETCHED.has(key)) return
  ALREADY_PREFETCHED.add(key)

  void withCache(resolveThemePath, key, RESOLVE_TTL)(toResolvablePath(href))
    .then((resolved) => {
      if (!resolved || !resolved.restUrl) return null
      if (!['post', 'page', 'shuoshuo'].includes(resolved.type)) return null

      const contentKey = `post:${resolved.restUrl}`
      if (resolved.payload) {
        primeCache(contentKey, resolved.payload, CONTENT_TTL)
        return null
      }
      return withCache(fetchContentByRestUrl, contentKey, CONTENT_TTL)(resolved.restUrl)
    })
    .catch(() => {
      // 预取失败无所谓，点击时照常加载
    })
}
