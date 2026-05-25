import { computed, ref } from 'vue'
import { fetchCacheVersion, fetchNavigation, fetchSiteInfo, getErrorMessage } from '@/lib/wordpress'
import { mockFetchNavigation, mockFetchSiteInfo, shouldUseMock } from '@/lib/mock-api'
import { getCached, setCache, computeHash, clearAllCache } from '@/lib/persistent-cache'
import { showToast, showLoadingToast, dismissToast } from '@/lib/toast'

import type {
  HeroSettings,
  MenuItem,
  SiteInfo,
  SiteStats,
  SocialLink,
  ThemeSettings,
} from '@/types/wordpress'

/** 服务端缓存版本号 — 存为单独 localStorage 项 */
const CV_KEY = 'st_cache_version'

function getLocalCacheVersion(): number | null {
  try {
    const v = localStorage.getItem(CV_KEY)
    return v !== null ? Number(v) : null
  } catch {
    return null
  }
}

function setLocalCacheVersion(v: number) {
  try { localStorage.setItem(CV_KEY, String(v)) } catch {}
}

// Manual cache refresh: ?st_refresh=1 clears all caches and reloads
const params = new URLSearchParams(window.location.search)
if (params.has('st_refresh')) {
  clearAllCache()
  try { localStorage.removeItem(CV_KEY) } catch {}
  ;(async () => {
    if ('caches' in window) {
      const keys = (await caches.keys()).filter((k) => k.startsWith('st-'))
      await Promise.all(keys.map((k) => caches.delete(k)))
    }
    params.delete('st_refresh')
    const qs = params.toString()
    window.location.replace(window.location.pathname + (qs ? '?' + qs : ''))
  })()
}

const fallbackThemeSettings: ThemeSettings = {
  primaryColor: '#333333',
  bodyFont: '"MiSans VF", "OPPO Sans", "SF Pro SC", HarmonyOS_Regular, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "PingFang SC", "Segoe UI", "Noto Sans", "Microsoft Yahei", Helvetica, Arial, sans-serif, "Apple Color Emoji", "Segoe UI Emoji"',
  codeFont: 'ui-monospace, "Cascadia Code", "JetBrains Mono", "SF Mono", "Fira Code", Consolas, Menlo, Monaco, "Courier New", monospace',
  radius: 'medium',
  shadow: 'small',
  backgroundLight: '#f5f6f7',
  backgroundDark: '#1a1a1a',
  cardLight: '#ffffff',
  cardDark: '#222222',
  foregroundLight: '#333333',
  foregroundDark: '#e0e0e0',
  accentLight: '#f5f5f5',
  accentDark: '#2a2a2a',
  borderLight: '#e2e2e2',
  borderDark: '#333333',
  containerMaxWidth: 1400,
  articleMaxWidth: 900,
  cardMeta: {
    showCategory: true,
    showPublishDate: true,
    showModifiedDate: false,
    showCommentCount: true,
    showViewCount: true,
    showReadingTime: true,
    showWordCount: false,
    showAuthor: true,
  },
}

const fallbackHeroSettings: HeroSettings = {
  enabled: true,
  displayMode: 'inset',
  useImage: false,
  image: '',
  showAvatar: false,
  avatar: '',
  title: '',
  subtitle: '',
  typewriterEnabled: false,
  typewriterInterval: 110,
  typewriterTexts: '',
}


const fallbackSiteStats: SiteStats = {
  postCount: 0,
  categoryCount: 0,
  tagCount: 0,
  shuoshuoCount: 0,
  totalWordCount: 0,
  commentCount: 0,
  registeredDate: '',
  lastActivityDate: '',
  heatmapData: [],
}

const fallbackSiteInfo: SiteInfo = {
  name: '',
  description: '',
  url: window.location.origin,
  comments: {
    requireNameEmail: true,
    registrationOnly: false,
    showEmailField: true,
    showUrlField: true,
    showCookiesOptIn: true,
    captchaEnabled: false,
    showImageUpload: true,
  },
  hero: fallbackHeroSettings,
  theme: fallbackThemeSettings,
}

const siteInfo = ref<SiteInfo>(fallbackSiteInfo)
const primaryMenu = ref<MenuItem[]>([])
const footerMenu = ref<MenuItem[]>([])
const shellInitialState = ref(true)
const shellLoadingState = ref(false)
const shellLoadedState = ref(false)
const shellErrorState = ref('')

const CACHE_KEY = 'st_shell_v1'

interface CachedShell {
  siteInfo: SiteInfo
  primaryMenu: MenuItem[]
  footerMenu: MenuItem[]
}

function mergeSiteInfo(next: SiteInfo): SiteInfo {
  return {
    ...fallbackSiteInfo,
    ...next,
    hero: { ...fallbackHeroSettings, ...next.hero },
    theme: {
      ...fallbackThemeSettings,
      ...next.theme,
      cardMeta: { ...fallbackThemeSettings.cardMeta!, ...next.theme?.cardMeta },
    },
    comments: {
      requireNameEmail:
        next.comments?.requireNameEmail ?? fallbackSiteInfo.comments!.requireNameEmail,
      registrationOnly:
        next.comments?.registrationOnly ?? fallbackSiteInfo.comments!.registrationOnly,
      showEmailField:
        next.comments?.showEmailField ?? fallbackSiteInfo.comments!.showEmailField,
      showUrlField:
        next.comments?.showUrlField ?? fallbackSiteInfo.comments!.showUrlField,
      showCookiesOptIn:
        next.comments?.showCookiesOptIn ?? fallbackSiteInfo.comments!.showCookiesOptIn,
      captchaEnabled:
        next.comments?.captchaEnabled ?? fallbackSiteInfo.comments!.captchaEnabled,
      showImageUpload:
        next.comments?.showImageUpload ?? fallbackSiteInfo.comments!.showImageUpload,
    },
    stats: { ...fallbackSiteStats, ...next.stats },
    socialLinks:
      next.socialLinks && next.socialLinks.length > 0 ? next.socialLinks : undefined,
  }
}

function applyShellData(data: CachedShell) {
  siteInfo.value = data.siteInfo
  primaryMenu.value = data.primaryMenu
  footerMenu.value = data.footerMenu
}

const sleep = (ms: number) => new Promise<void>((r) => setTimeout(r, ms))

/** 版本更新 toast 序列：正在更新 → 更新成功 → 自动刷新 */
async function showUpdateToastSequence(
  oldVersion: string,
  newVersion: string,
  merged: CachedShell,
  cacheVersion: number,
) {
  // 1. Show "正在更新" with version info
  const lt = showLoadingToast(
    `${oldVersion.slice(0, 6)} → ${newVersion.slice(0, 6)}`,
    '正在更新',
  )

  // 2. Apply and cache
  try {
    // Clear SW caches first (most likely to fail, so do it before touching state)
    if ('caches' in window) {
      const keys = (await caches.keys()).filter((k) => k.startsWith('st-'))
      await Promise.all(keys.map((k) => caches.delete(k)))
    }

    setCache(CACHE_KEY, merged)
    setLocalCacheVersion(cacheVersion)
    applyShellData(merged)

    dismissToast(lt)
  } catch (e) {
    dismissToast(lt)
    throw e
  }

  // 3. Done — seamless update, no page reload needed
  showToast('', '已自动刷新完成', { variant: 'success', duration: 3000 })
}

export function useSiteShell() {
  const shellLoading = computed(() => shellInitialState.value || shellLoadingState.value)

  const ensureLoaded = async () => {
    if (shellLoadedState.value) return

    shellInitialState.value = false

    // 1. Serve cached data immediately (stale-while-revalidate)
    const cached = getCached<CachedShell>(CACHE_KEY)
    if (cached) {
      applyShellData(cached.data)
      shellLoadedState.value = true
    } else {
      shellLoadingState.value = true
      shellErrorState.value = ''
    }

    // 2. Lightweight version check — skip background fetch if server says nothing changed
    let serverVersion = -1
    try {
      serverVersion = await fetchCacheVersion()
      const localVersion = getLocalCacheVersion()
      if (localVersion !== null && localVersion === serverVersion && cached) {
        shellLoadingState.value = false
        return
      }
    } catch {
      serverVersion = -1
    }

    // 3. Background fetch
    try {
      const useMock = shouldUseMock()

      const [nextSiteInfo, nextPrimaryMenu, nextFooterMenu] = await Promise.all([
        useMock ? mockFetchSiteInfo() : fetchSiteInfo(),
        useMock ? mockFetchNavigation('primary')
          : cached ? fetchNavigation('primary') : fetchNavigation('primary').catch(() => []),
        useMock ? mockFetchNavigation('footer')
          : cached ? fetchNavigation('footer') : fetchNavigation('footer').catch(() => []),
      ])

      const merged: CachedShell = {
        siteInfo: mergeSiteInfo(nextSiteInfo),
        primaryMenu: nextPrimaryMenu,
        footerMenu: nextFooterMenu,
      }

      // Compute version hash WITHOUT writing to cache yet
      const newVersion = computeHash(JSON.stringify(merged))

      if (!cached || newVersion !== cached.version) {
        if (cached) {
          // Version changed — show toast sequence
          try {
            await showUpdateToastSequence(cached.version, newVersion, merged, serverVersion)
          } catch {
            showToast('更新失败，旧版本内容保持不变，下次刷新将重试。', '更新失败', {
              variant: 'warning',
              duration: 5000,
            })
          }
          return
        }

        // No cache before — normal first-time load
        setCache(CACHE_KEY, merged)
        applyShellData(merged)
        if (serverVersion > 0) setLocalCacheVersion(serverVersion)
      } else {
        // Content unchanged — just update the stored server version
        if (serverVersion > 0) setLocalCacheVersion(serverVersion)
      }

      shellLoadedState.value = true
    } catch (error) {
      if (cached) {
        // Background fetch failed but we have cache — silent for stale-while-revalidate
      } else {
        shellErrorState.value = getErrorMessage(error, '站点基础信息加载失败，请稍后重试。')
      }
    } finally {
      shellLoadingState.value = false
    }
  }

  return {
    siteInfo: computed(() => siteInfo.value),
    primaryMenu: computed(() => primaryMenu.value),
    footerMenu: computed(() => footerMenu.value),
    shellLoading,
    shellError: computed(() => shellErrorState.value),
    ensureLoaded,
  }
}
