/**
 * api-site — 站点信息 / 导航 / 分类 / 友链 / 路由解析
 */
import axios from 'axios'
import { getThemeConfig, toResolvablePath } from '@/lib/theme-config'
import { apiClient, buildRestUrl } from './api-client'
import { isConnectivityError } from '@/lib/connectivity'
import {
  shouldUseMock,
  mockFetchSiteInfo,
  mockFetchNavigation,
  mockFetchCategories,
  mockFetchLinks,
  mockResolveThemePath,
} from './mock-api'
import type {
  MenuCollectionResponse,
  MenuItem,
  ResolveResponse,
  SiteInfo,
  WordPressCategory,
  WordPressLinkCategory,
  WordPressPost,
} from '@/types/wordpress'

/** 轻量端点：只获取服务端缓存版本号 */
export async function fetchCacheVersion(): Promise<number> {
  if (shouldUseMock()) return 1
  const { data } = await apiClient.get<{ version: number }>(buildRestUrl('cache-version'))
  return data.version
}

export async function fetchSiteInfo() {
  if (shouldUseMock()) return mockFetchSiteInfo()
  const siteInfoUrl = getThemeConfig().routes.siteInfo
  try {
    const { data } = await apiClient.get<SiteInfo>(siteInfoUrl)
    if (data.siteIcon) return data
    return await withSiteIconFallback(data)
  } catch (error) {
    if (axios.isAxiosError(error) && error.response?.status === 404) {
      return await withSiteIconFallback({} as SiteInfo)
    }
    throw error
  }
}

async function withSiteIconFallback(data: Partial<SiteInfo>): Promise<SiteInfo> {
  if (data.siteIcon) return data as SiteInfo

  const existingIcon = document.querySelector<HTMLLinkElement>(
    'link[rel="icon"], link[rel="shortcut icon"], link[rel="apple-touch-icon"]',
  )
  if (existingIcon?.href) {
    return { ...data, siteIcon: existingIcon.href } as SiteInfo
  }

  // wp/v2/settings requires a nonce. The frontend config deliberately no
  // longer exposes one, so read the session-scoped value set by useAuth.
  if (!apiClient.defaults.headers.common['X-WP-Nonce']) {
    return data as SiteInfo
  }

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

export async function fetchNavigation(location: string) {
  if (shouldUseMock()) return mockFetchNavigation(location)
  const baseUrl = getThemeConfig().routes.menusBase.replace(/\/+$/, '')
  const { data } = await apiClient.get<MenuCollectionResponse>(
    `${baseUrl}/${encodeURIComponent(location)}`,
  )

  return data.items
}

/**
 * 一次取回 primary + footer 两个菜单（首屏只需要一个请求）。
 * 后端不支持时会抛出，调用方保留已有数据即可。
 */
export async function fetchMenus(): Promise<{ primary: MenuItem[]; footer: MenuItem[] }> {
  if (shouldUseMock()) {
    const [primary, footer] = await Promise.all([
      mockFetchNavigation('primary'),
      mockFetchNavigation('footer'),
    ])
    return { primary, footer }
  }

  const baseUrl = getThemeConfig().routes.menusBase.replace(/\/+$/, '')
  const { data } = await apiClient.get<{ menus?: { primary?: MenuItem[]; footer?: MenuItem[] } }>(
    `${baseUrl}/all`,
  )

  return {
    primary: data.menus?.primary ?? [],
    footer: data.menus?.footer ?? [],
  }
}

export async function fetchCategories(): Promise<WordPressCategory[]> {
  if (shouldUseMock()) return mockFetchCategories()
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

export async function fetchLinks(): Promise<WordPressLinkCategory[]> {
  if (shouldUseMock()) return mockFetchLinks()
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

export async function resolveThemePath(path: string): Promise<ResolveResponse> {
  const resolvablePath = toResolvablePath(path)

  if (shouldUseMock()) return mockResolveThemePath(resolvablePath)

  let resolved: ResolveResponse | null = null
  try {
    const { data } = await apiClient.post<ResolveResponse>(getThemeConfig().routes.resolveUrl, {
      path: resolvablePath,
    })
    resolved = data
  } catch (error) {
    // 断网 / 人机验证 / 5xx 不能被当成 404，否则用户看到「页面不存在」而真实原因无从得知
    if (isConnectivityError(error)) throw error
  }

  // 主接口已经给出结论（文章/页面/归档/首页…）就直接用，不再发兜底查询
  if (resolved && !['error', '404'].includes(resolved.type)) return resolved
  if (resolved && resolved.type === 'error') return resolved

  // 只有主接口失败或 404 时，才按 slug 依次查页面/文章兜底。
  // 这段以前是无条件并发执行的：正常打开一篇文章会白白多打两个 REST 请求（各 ~28KB）。
  const fallback = await resolveBySlug(resolvablePath)
  return fallback ?? resolved ?? { type: '404', message: '页面未找到' }
}

/** slug 兜底：先按页面查，再按文章查；查不到返回 null */
async function resolveBySlug(resolvablePath: string): Promise<ResolveResponse | null> {
  const slug = resolvablePath.split('/').filter(Boolean).pop() || ''
  if (!slug) return null
  const decodedSlug = decodeURIComponentSafely(slug)

  try {
    const { data: pages } = await apiClient.get<WordPressPost[]>(buildRestUrl('wp/v2/pages'), {
      params: { slug: decodedSlug, per_page: 1, _embed: 1 },
    })
    if (pages?.[0]) {
      return {
        type: 'page',
        restUrl: buildRestUrl(`wp/v2/pages/${pages[0].id}?_embed=1`),
        id: pages[0].id,
      }
    }
  } catch (error) {
    if (isConnectivityError(error)) throw error
  }

  try {
    const { data: posts } = await apiClient.get<WordPressPost[]>(buildRestUrl('wp/v2/posts'), {
      params: { slug: decodedSlug, per_page: 1, _embed: 1 },
    })
    if (posts?.[0]) {
      return {
        type: 'post',
        restUrl: buildRestUrl(`wp/v2/posts/${posts[0].id}?_embed=1`),
        id: posts[0].id,
      }
    }
  } catch (error) {
    if (isConnectivityError(error)) throw error
  }

  return null
}

function decodeURIComponentSafely(value: string): string {
  try {
    return decodeURIComponent(value)
  } catch {
    return value
  }
}
