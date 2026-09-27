import type { SimpleThemeConfig } from '@/types/wordpress'

const origin = window.location.origin

const fallbackConfig: SimpleThemeConfig = {
  siteUrl: `${origin}/`,
  homeUrl: `${origin}/`,
  restRoot: `${origin}/wp-json/`,
  themeUrl: `${origin}/wp-content/themes/simple-theme`,
  illustrationsUrl: `${origin}/wp-content/themes/simple-theme/dist/illustrations/`,
  routes: {
    resolveUrl: `${origin}/wp-json/simple-theme/v1/resolve-url`,
    menusBase: `${origin}/wp-json/simple-theme/v1/navigation`,
    siteInfo: `${origin}/wp-json/simple-theme/v1/site-info`,
    collection: `${origin}/wp-json/simple-theme/v1/collection`,
    about: `${origin}/wp-json/simple-theme/v1/about`,
    links: `${origin}/wp-json/simple-theme/v1/links`,
    settings: `${origin}/wp-json/simple-theme/v1/settings`,
  },
  permalink: {
    categoryBase: 'category',
    tagBase: 'tag',
  },
  features: {
    prismHighlight: true,
    showStats: true,
    showHeatmap: true,
    showSocial: true,
    meta: {
      showCategory: true,
      showPublishDate: true,
      showModifiedDate: false,
      showCommentCount: true,
      showViewCount: true,
      showReadingTime: true,
      showWordCount: true,
      showAuthor: true,
    },
    articleMeta: {
      showCategory: true,
      showPublishDate: true,
      showModifiedDate: false,
      showCommentCount: true,
      showViewCount: true,
      showReadingTime: true,
      showWordCount: false,
      showAuthor: true,
    },
  },
}

const injectedConfig = window.SimpleThemeConfig

// Normalize a URL's origin to match the current page, avoiding CORS when WordPress
// is configured with a different hostname (e.g. 127.0.0.1 vs localhost).
const normalizeOrigin = (url: string): string => {
  try {
    const parsed = new URL(url)
    if (parsed.origin !== window.location.origin) {
      return url.replace(parsed.origin, window.location.origin)
    }
  } catch {
    // leave as-is
  }
  return url
}

const themeConfig: SimpleThemeConfig = injectedConfig
  ? {
      ...fallbackConfig,
      ...injectedConfig,
      routes: {
        ...fallbackConfig.routes,
        ...injectedConfig.routes,
      },
      permalink: {
        ...fallbackConfig.permalink,
        ...injectedConfig.permalink,
      },
    }
  : fallbackConfig

// Normalize all URLs to the page's origin so cross-origin CORS is avoided entirely
if (themeConfig.siteUrl) themeConfig.siteUrl = normalizeOrigin(themeConfig.siteUrl)
if (themeConfig.homeUrl) themeConfig.homeUrl = normalizeOrigin(themeConfig.homeUrl)
if (themeConfig.restRoot) themeConfig.restRoot = normalizeOrigin(themeConfig.restRoot)
if (themeConfig.themeUrl) themeConfig.themeUrl = normalizeOrigin(themeConfig.themeUrl)
if (themeConfig.illustrationsUrl)
  themeConfig.illustrationsUrl = normalizeOrigin(themeConfig.illustrationsUrl)
if (themeConfig.routes) {
  for (const key of Object.keys(themeConfig.routes) as (keyof typeof themeConfig.routes)[]) {
    const value = themeConfig.routes[key]
    if (value) themeConfig.routes[key] = normalizeOrigin(value)
  }
}

const siteBaseUrl = new URL(themeConfig.homeUrl)

const PREVIEW_QUERY_KEYS = new Set([
  'customize_changeset_uuid',
  'customize_theme',
  'customize_messenger_channel',
  'customized',
  'nonce',
  'url',
  'autofocus',
  'return',
])

const trimTrailingSlash = (value: string) => {
  if (value.length <= 1) {
    return value || '/'
  }

  return value.replace(/\/+$/, '')
}

const siteBasePath = trimTrailingSlash(siteBaseUrl.pathname || '/')

function hasControlCharacter(value: string): boolean {
  for (let index = 0; index < value.length; index++) {
    const code = value.charCodeAt(index)
    if (code <= 0x1f || code === 0x7f) return true
  }
  return false
}

const stripSiteBase = (pathname: string) => {
  if ('/' === siteBasePath) {
    return pathname || '/'
  }

  if (pathname === siteBasePath) {
    return '/'
  }

  if (pathname.startsWith(`${siteBasePath}/`)) {
    return pathname.slice(siteBasePath.length) || '/'
  }

  return pathname || '/'
}

export function getThemeConfig() {
  return themeConfig
}

export function getRouterBase() {
  return '/' === siteBasePath ? '/' : `${siteBasePath}/`
}

export function toInternalPath(value: string) {
  const normalizedValue = value.trim()
  if (!normalizedValue || normalizedValue.startsWith('//')) {
    return '/'
  }

  try {
    const targetUrl = new URL(normalizedValue, themeConfig.homeUrl)
    if (!['http:', 'https:'].includes(targetUrl.protocol)) {
      return '/'
    }
    const pathname = stripSiteBase(targetUrl.pathname)
    return `${pathname}${targetUrl.search}${targetUrl.hash}`
  } catch {
    return normalizedValue.startsWith('/') ? normalizedValue : `/${normalizedValue}`
  }
}

export function toResolvablePath(value: string) {
  const internalPath = toInternalPath(value)

  try {
    const parsedUrl = new URL(internalPath, themeConfig.homeUrl)

    Array.from(parsedUrl.searchParams.keys()).forEach((key) => {
      if (PREVIEW_QUERY_KEYS.has(key)) {
        parsedUrl.searchParams.delete(key)
      }
    })

    const query = parsedUrl.searchParams.toString()
    return `${parsedUrl.pathname}${query ? `?${query}` : ''}${parsedUrl.hash}`
  } catch {
    return internalPath
  }
}

/**
 * 判断内部路由路径是否属于「列表型归档」（分类 / 标签 / 日期）。
 *
 * 主题在 URL 解析（resolve-url）返回前无从得知类型，若此时一律套用
 * 文章正文骨架，归档页会先闪出一副「文章骨架」。分类 / 标签前缀取自
 * 站点 permalink 配置，日期归档形如 /YYYY 或 /YYYY/MM。
 */
export function isArchivePath(value: string): boolean {
  const raw = (value || '/').split(/[?#]/)[0]
  const path = `/${raw.split('/').filter(Boolean).join('/')}`
  if ('/' === path) return false

  // 日期归档：/YYYY 或 /YYYY/MM
  if (/^\/\d{4}(\/\d{1,2})?$/.test(path)) return true

  const first = path.split('/')[1]?.toLowerCase()
  if (!first) return false

  const { categoryBase, tagBase } = getThemeConfig().permalink ?? {}
  // 'categories' 为主题自定义的分类过滤路由
  return [categoryBase, tagBase, 'categories'].some(
    (base) => !!base && base.toLowerCase() === first,
  )
}

export function isSafeNavigationUrl(value: string) {
  const normalizedValue = value.trim()
  if (
    !normalizedValue ||
    normalizedValue.startsWith('//') ||
    hasControlCharacter(normalizedValue)
  ) {
    return false
  }

  try {
    const targetUrl = new URL(normalizedValue, themeConfig.homeUrl)
    return ['http:', 'https:', 'mailto:', 'tel:'].includes(targetUrl.protocol)
  } catch {
    return false
  }
}

export function isExternalUrl(value: string) {
  if (!isSafeNavigationUrl(value)) {
    return false
  }

  try {
    const targetUrl = new URL(value, themeConfig.homeUrl)
    const sharesOrigin = targetUrl.origin === siteBaseUrl.origin
    const sharesBase =
      '/' === siteBasePath ||
      targetUrl.pathname === siteBasePath ||
      targetUrl.pathname.startsWith(`${siteBasePath}/`)

    return !(sharesOrigin && sharesBase)
  } catch {
    return false
  }
}
