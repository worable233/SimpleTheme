/**
 * api-site — 站点信息 / 导航 / 分类 / 友链 / 路由解析
 */
import axios from 'axios'
import { getThemeConfig, toResolvablePath } from '@/lib/theme-config'
import { apiClient, buildRestUrl } from './api-client'
import type {
  AboutInfo,
  MenuCollectionResponse,
  ResolveResponse,
  SiteInfo,
  WordPressCategory,
  WordPressLinkCategory,
  WordPressPost,
} from '@/types/wordpress'

export async function fetchSiteInfo() {
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

export async function fetchNavigation(location: string) {
  const baseUrl = getThemeConfig().routes.menusBase.replace(/\/+$/, '')
  const { data } = await apiClient.get<MenuCollectionResponse>(
    `${baseUrl}/${encodeURIComponent(location)}`,
  )

  return data.items
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

export async function resolveThemePath(path: string): Promise<ResolveResponse> {
  const resolvablePath = toResolvablePath(path)

  const slug = resolvablePath.split('/').filter(Boolean).pop() || ''

  const results = await Promise.allSettled([
    (async () => {
      const { data } = await apiClient.post<ResolveResponse>(getThemeConfig().routes.resolveUrl, {
        path: resolvablePath,
      })
      return data
    })(),
    (async () => {
      if (!slug) throw new Error('no slug')
      try {
        const { data: pages } = await apiClient.get<WordPressPost[]>(buildRestUrl('wp/v2/pages'), {
          params: { slug: decodeURIComponent(slug), per_page: 1, _embed: 1 },
        })
        if (pages?.[0]) {
          return { type: 'page' as const, restUrl: buildRestUrl(`wp/v2/pages/${pages[0].id}?_embed=1`), id: pages[0].id }
        }
      } catch {}
      try {
        const { data: posts } = await apiClient.get<WordPressPost[]>(buildRestUrl('wp/v2/posts'), {
          params: { slug: decodeURIComponent(slug), per_page: 1, _embed: 1 },
        })
        if (posts?.[0]) {
          return { type: 'post' as const, restUrl: buildRestUrl(`wp/v2/posts/${posts[0].id}?_embed=1`), id: posts[0].id }
        }
      } catch {}
      throw new Error('slug not found')
    })(),
  ])

  const customResult = results[0]
  const wpResult = results[1]

  if (customResult.status === 'fulfilled') {
    const data = customResult.value
    if (data && !['error'].includes(data.type)) {
      return data
    }
  }

  if (wpResult.status === 'fulfilled') {
    return wpResult.value
  }

  if (customResult.status === 'fulfilled') {
    return customResult.value
  }

  return { type: '404', message: '页面未找到' }
}
