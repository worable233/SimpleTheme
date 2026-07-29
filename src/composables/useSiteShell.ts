import { computed, ref } from 'vue'
import { fetchNavigation, fetchSiteInfo, getErrorMessage } from '@/lib/wordpress'
import { getCached, setCache, computeHash, clearAllCache } from '@/lib/persistent-cache'

import type {
  HeroSettings,
  MenuItem,
  SiteInfo,
  SiteStats,
  ThemeSettings,
} from '@/types/wordpress'

// Manual cache refresh: ?st_refresh=1 clears all caches and reloads
const params = new URLSearchParams(window.location.search)
if (params.has('st_refresh')) {
  clearAllCache()
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

    // 2. Background refresh — always fetch latest, silently update if changed
    try {
      const [nextSiteInfo, nextPrimaryMenu, nextFooterMenu] = await Promise.all([
        fetchSiteInfo(),
        cached ? fetchNavigation('primary') : fetchNavigation('primary').catch(() => []),
        cached ? fetchNavigation('footer') : fetchNavigation('footer').catch(() => []),
      ])

      const merged: CachedShell = {
        siteInfo: mergeSiteInfo(nextSiteInfo),
        primaryMenu: nextPrimaryMenu,
        footerMenu: nextFooterMenu,
      }

      const newVersion = computeHash(JSON.stringify(merged))

      if (!cached || newVersion !== cached.version) {
        setCache(CACHE_KEY, merged)
        applyShellData(merged)
      }

      shellLoadedState.value = true
    } catch (error) {
      if (!cached) {
        shellErrorState.value = getErrorMessage(error, '站点基础信息加载失败，请稍后重试。')
      }
      // With cache: silent — stale data is better than nothing
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
