import axios from 'axios'
import { getThemeConfig, toInternalPath, toResolvablePath } from '@/lib/theme-config'
import type {
  AboutInfo,
  MenuCollectionResponse,
  PagedPostCollection,
  ResolveResponse,
  SiteInfo,
  WordPressCategory,
  WordPressComment,
  WordPressLinkCategory,
  WordPressPost,
} from '@/types/wordpress'

const apiClient = axios.create({
  headers: {
    'X-Requested-With': 'XMLHttpRequest',
  },
})

export const buildRestUrl = (path = '') => {
  let restRoot = getThemeConfig().restRoot.replace(/\/+$/, '')

  // Normalize origin when the page is served from a different origin than WordPress
  // (e.g. Vite dev server on localhost:5173, WordPress on 127.0.0.1 or localhost:8080).
  // Uses the page origin so requests go through the Vite proxy, avoiding CORS.
  try {
    const restUrl = new URL(restRoot)
    if (restUrl.origin !== window.location.origin) {
      restRoot = `${window.location.origin}${restUrl.pathname}`
    }
  } catch {
    // restRoot is not a valid URL — use as-is
  }

  if (!path) {
    return `${restRoot}/`
  }

  return `${restRoot}/${path.replace(/^\/+/, '')}`
}

export async function fetchSiteInfo() {
  const siteInfoUrl = getThemeConfig().routes.siteInfo
  try {
    const { data } = await apiClient.get<SiteInfo>(siteInfoUrl)
    if (data.siteIcon) return data
    // If custom endpoint didn't return a siteIcon, try WordPress settings
    return await withSiteIconFallback(data)
  } catch (error) {
    if (axios.isAxiosError(error) && error.response?.status === 404) {
      return await withSiteIconFallback({} as SiteInfo)
    }
    throw error
  }
}

/** Try to get the site icon URL from WordPress settings or existing page DOM. */
async function withSiteIconFallback(data: Partial<SiteInfo>): Promise<SiteInfo> {
  if (data.siteIcon) return data as SiteInfo

  // 1. Read from existing DOM — always works when WordPress serves the page (wp_head() outputs site icon)
  const existingIcon = document.querySelector<HTMLLinkElement>(
    'link[rel="icon"], link[rel="shortcut icon"], link[rel="apple-touch-icon"]',
  )
  if (existingIcon?.href) {
    return { ...data, siteIcon: existingIcon.href } as SiteInfo
  }

  // 2. Try wp/v2/settings endpoint as fallback (may 401 if unauthenticated)
  try {
    const { data: settings } = await apiClient.get<{ site_icon_url?: string }>(
      buildRestUrl('wp/v2/settings'),
    )
    if (settings.site_icon_url) {
      return { ...data, siteIcon: settings.site_icon_url } as SiteInfo
    }
  } catch {
    // silent
  }

  return data as SiteInfo
}

export async function fetchAboutInfo(): Promise<AboutInfo> {
  const aboutUrl = buildRestUrl('simple-theme/v1/about')
  try {
    const { data } = await apiClient.get<AboutInfo>(aboutUrl)
    return data
  } catch (error) {
    if (axios.isAxiosError(error) && error.response?.status === 404) {
      return {} as AboutInfo
    }
    throw error
  }
}

export async function fetchLatestPosts(limit = 6) {
  const { items } = await fetchCollection('post', { limit })
  return items
}

export async function fetchCollection(
  type: 'post' | 'page' | 'shuoshuo',
  options?: {
    limit?: number
    page?: number
    taxonomy?: string
    termId?: number
  },
) {
  const collectionUrl = getThemeConfig().routes.collection.replace(/\/+$/, '')
  const params = new URLSearchParams({
    type,
    limit: String(options?.limit || 6),
    page: String(options?.page || 1),
  })

  if (options?.taxonomy && options.termId) {
    params.set('taxonomy', options.taxonomy)
    params.set('termId', String(options.termId))
  }

  try {
    const { data } = await apiClient.get<PagedPostCollection>(`${collectionUrl}?${params.toString()}`)
    return data
  } catch (error) {
    if (!axios.isAxiosError(error) || error.response?.status !== 404) throw error

    // Fallback to standard WordPress REST API when custom endpoint is unavailable
    const wpTypeMap: Record<string, string> = { post: 'posts', page: 'pages', shuoshuo: 'shuoshuo' }
    const wpEndpoint = wpTypeMap[type]
    if (!wpEndpoint) throw error

    const wpParams = new URLSearchParams({
      per_page: String(Math.min(options?.limit ?? 6, 100)),
      page: String(options?.page || 1),
    })

    if (options?.taxonomy && options.termId) {
      const taxMap: Record<string, string> = { category: 'categories', post_tag: 'tags' }
      const taxKey = taxMap[options.taxonomy]
      if (taxKey) wpParams.set(taxKey, String(options.termId))
    }

    wpParams.set('_embed', '1')
    const { data, headers } = await apiClient.get<WordPressPost[]>(buildRestUrl(`wp/v2/${wpEndpoint}`), {
      params: Object.fromEntries(wpParams),
    })

    const items = (Array.isArray(data) ? data : []).map((post) => {
      const media = (post._embedded as Record<string, unknown[]> | undefined)?.['wp:featuredmedia']
      const sourceUrl = (media?.[0] as Record<string, unknown> | undefined)?.source_url
      const terms = (post._embedded as Record<string, unknown[]> | undefined)?.['wp:term'] as Array<Array<{ taxonomy?: string; name?: string }>> | undefined
      const categoryNames: string[] = []
      if (terms) {
        for (const group of terms) {
          for (const term of group) {
            if (term?.taxonomy === 'category' && typeof term.name === 'string') {
              categoryNames.push(term.name)
            }
          }
        }
      }
      return { ...post, featuredImage: (typeof sourceUrl === 'string' ? sourceUrl : undefined) || (post as any).featuredImage, categories: categoryNames.length > 0 ? categoryNames : (post as any).categories }
    })

    return {
      items,
      total: Number(headers['x-wp-total'] || 0),
      totalPages: Number(headers['x-wp-totalpages'] || 0),
      page: options?.page || 1,
      perPage: options?.limit || 6,
    } satisfies PagedPostCollection
  }
}

export async function fetchNavigation(location: string) {
  const baseUrl = getThemeConfig().routes.menusBase.replace(/\/+$/, '')
  const { data } = await apiClient.get<MenuCollectionResponse>(
    `${baseUrl}/${encodeURIComponent(location)}`,
  )

  return data.items
}

export async function fetchPostCollectionByTaxonomy(taxonomy: string, termId: number, limit = 12) {
  const { items } = await fetchCollection('post', {
    taxonomy,
    termId,
    limit,
  })

  return items
}

export async function resolveThemePath(path: string): Promise<ResolveResponse> {
  try {
    const { data } = await apiClient.post<ResolveResponse>(getThemeConfig().routes.resolveUrl, {
      path: toResolvablePath(path),
    })

    return data
  } catch (error) {
    if (axios.isAxiosError<ResolveResponse>(error) && error.response?.status === 404) {
      return error.response.data
    }
  }

  // Client-side fallback: extract slug and try standard WordPress REST API
  const rawSlug = toResolvablePath(path).split('/').filter(Boolean).pop() || ''

  if (rawSlug) {
    // Normalize slug: handle both URL-encoded and decoded forms from different browsers
    let slug: string
    try {
      slug = decodeURIComponent(rawSlug)
    } catch {
      slug = rawSlug
    }

    try {
      const { data: pages } = await apiClient.get<WordPressPost[]>(buildRestUrl('wp/v2/pages'), {
        params: { slug, per_page: 1, _embed: 1 },
      })
      if (pages?.[0]) {
        return {
          type: 'page',
          restUrl: buildRestUrl(`wp/v2/pages/${pages[0].id}?_embed=1`),
          id: pages[0].id,
        }
      }
    } catch {}

    try {
      const { data: posts } = await apiClient.get<WordPressPost[]>(buildRestUrl('wp/v2/posts'), {
        params: { slug, per_page: 1, _embed: 1 },
      })
      if (posts?.[0]) {
        return {
          type: 'post',
          restUrl: buildRestUrl(`wp/v2/posts/${posts[0].id}?_embed=1`),
          id: posts[0].id,
        }
      }
    } catch {}
  }

  return { type: '404', message: '页面未找到' }
}

export async function fetchContentByRestUrl(restUrl: string) {
  try {
    // Normalize origin to page origin so the request goes through Vite proxy,
    // avoiding CORS when WordPress lives on a different host (e.g. 127.0.0.1).
    const url = new URL(restUrl, window.location.origin)
    const normalized =
      url.origin !== window.location.origin
        ? `${window.location.origin}${url.pathname}${url.search}`
        : restUrl
    const { data } = await apiClient.get<WordPressPost>(normalized)
    return data
  } catch {
    return null
  }
}

export async function trackPostView(postId: number) {
  try {
    const { data } = await apiClient.post<{ viewCount: number }>(
      buildRestUrl('simple-theme/v1/track-view'),
      { postId },
    )
    return data.viewCount
  } catch {
    return 0
  }
}

export async function fetchComments(postId: number, clientId?: string) {
  try {
    let url = buildRestUrl(`simple-theme/v1/comments/${postId}`)
    if (clientId) {
      url += `?client_id=${encodeURIComponent(clientId)}`
    }
    const { data } = await apiClient.get<{ items: WordPressComment[] }>(url)
    return data.items
  } catch (error) {
    if (axios.isAxiosError(error) && error.response?.status === 404) {
      return []
    }
    throw error
  }
}

export async function createComment(payload: {
  post: number
  parent?: number
  author_name: string
  author_email?: string
  author_url?: string
  content: string
  client_id: string
}) {
  const { data } = await apiClient.post<{ item: WordPressComment }>(
    buildRestUrl('simple-theme/v1/comments'),
    payload,
  )
  return data.item
}

export async function likeComment(commentId: number) {
  try {
    const { data } = await apiClient.post<{ likes: number }>(
      buildRestUrl('simple-theme/v1/comment-like'),
      { commentId },
    )
    return data.likes
  } catch (error) {
    if (axios.isAxiosError(error) && error.response?.status === 404) {
      return 0
    }
    throw error
  }
}

export function getErrorMessage(error: unknown, fallback = '请求失败，请稍后重试。') {
  if (axios.isAxiosError(error)) {
    const message = error.response?.data?.message

    if (typeof message === 'string' && message.trim()) {
      return message
    }

    if (error.message) {
      return error.message
    }
  }

  if (error instanceof Error && error.message) {
    return error.message
  }

  return fallback
}

/**
 * 获取页面内容（通过 slug）
 */
export async function fetchPage(slug: string): Promise<WordPressPost | null> {
  try {
    const { data } = await apiClient.get<WordPressPost[]>(
      buildRestUrl(`wp/v2/pages?slug=${encodeURIComponent(slug)}&per_page=1`),
    )
    return data[0] || null
  } catch (error) {
    if (axios.isAxiosError(error) && error.response?.status === 404) {
      return null
    }
    throw error
  }
}

export function toRouterPathFromWpLink(value: string) {
  return toInternalPath(value)
}

export async function fetchCategories(): Promise<WordPressCategory[]> {
  try {
    const { data } = await apiClient.get<(Omit<WordPressCategory, 'slug'> & { slug: string })[]>(
      buildRestUrl('wp/v2/categories'),
      { params: { per_page: 100, hide_empty: true } },
    )
    return data.map((c) => ({ id: c.id, name: c.name, slug: c.slug }))
  } catch (error) {
    if (axios.isAxiosError(error) && error.response?.status === 404) {
      return []
    }
    throw error
  }
}

/**
 * 获取友链（按 link_category 分类）
 */
export async function fetchLinks(): Promise<WordPressLinkCategory[]> {
  const linksUrl = buildRestUrl('simple-theme/v1/links')
  try {
    const { data } = await apiClient.get<WordPressLinkCategory[]>(linksUrl)
    return data
  } catch (error) {
    if (axios.isAxiosError(error) && error.response?.status === 404) {
      return []
    }
    throw error
  }
}
