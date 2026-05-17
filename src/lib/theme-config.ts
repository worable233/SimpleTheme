import type { SimpleThemeConfig } from '@/types/wordpress'

const origin = window.location.origin

const fallbackConfig: SimpleThemeConfig = {
  siteUrl: `${origin}/`,
  homeUrl: `${origin}/`,
  restRoot: `${origin}/wp-json/`,
  themeUrl: `${origin}/wp-content/themes/simple-theme`,
  illustrationsUrl: `${origin}/illustrations/`,
  routes: {
    resolveUrl: `${origin}/wp-json/simple-theme/v1/resolve-url`,
    menusBase: `${origin}/wp-json/simple-theme/v1/navigation`,
    siteInfo: `${origin}/wp-json/simple-theme/v1/site-info`,
    collection: `${origin}/wp-json/simple-theme/v1/collection`,
    about: `${origin}/wp-json/simple-theme/v1/about`,
    links: `${origin}/wp-json/simple-theme/v1/links`,
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

let themeConfig: SimpleThemeConfig = injectedConfig
  ? {
      ...fallbackConfig,
      ...injectedConfig,
      routes: {
        ...fallbackConfig.routes,
        ...injectedConfig.routes,
      },
    }
  : fallbackConfig

// Normalize all URLs to the page's origin so cross-origin CORS is avoided entirely
if (themeConfig.siteUrl) themeConfig.siteUrl = normalizeOrigin(themeConfig.siteUrl)
if (themeConfig.homeUrl) themeConfig.homeUrl = normalizeOrigin(themeConfig.homeUrl)
if (themeConfig.restRoot) themeConfig.restRoot = normalizeOrigin(themeConfig.restRoot)
if (themeConfig.themeUrl) themeConfig.themeUrl = normalizeOrigin(themeConfig.themeUrl)
if (themeConfig.illustrationsUrl) themeConfig.illustrationsUrl = normalizeOrigin(themeConfig.illustrationsUrl)
if (themeConfig.routes) {
  for (const key of Object.keys(themeConfig.routes) as (keyof typeof themeConfig.routes)[]) {
    themeConfig.routes[key] = normalizeOrigin(themeConfig.routes[key])
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
  if (!value) {
    return '/'
  }

  try {
    const targetUrl = new URL(value, themeConfig.homeUrl)
    const pathname = stripSiteBase(targetUrl.pathname)
    return `${pathname}${targetUrl.search}${targetUrl.hash}`
  } catch {
    return value.startsWith('/') ? value : `/${value}`
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

export function isExternalUrl(value: string) {
  if (!value) {
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
