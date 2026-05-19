import { computed, ref } from 'vue'
import { fetchNavigation, fetchSiteInfo, getErrorMessage } from '@/lib/wordpress'
import { mockFetchNavigation, mockFetchSiteInfo, shouldUseMock } from '@/lib/mock-api'
import type {
  HeroSettings,
  MenuItem,
  SiteInfo,
  SiteStats,
  SocialLink,
  ThemeSettings,
} from '@/types/wordpress'

const fallbackThemeSettings: ThemeSettings = {
  primaryColor: '#333333',
  bodyFont: '"MiSans VF", "OPPO Sans", "SF Pro SC", HarmonyOS_Regular, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "PingFang SC", "Segoe UI", "Noto Sans", "Microsoft Yahei", Helvetica, Arial, sans-serif, "Apple Color Emoji", "Segoe UI Emoji"',
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

export function useSiteShell() {
  const shellLoading = computed(() => shellInitialState.value || shellLoadingState.value)

  const ensureLoaded = async () => {
    if (shellLoadedState.value) return

    shellInitialState.value = false
    shellLoadingState.value = true
    shellErrorState.value = ''

    try {
      const useMock = shouldUseMock()

      const [nextSiteInfo, nextPrimaryMenu, nextFooterMenu] = await Promise.all([
        useMock ? mockFetchSiteInfo() : fetchSiteInfo(),
        useMock ? mockFetchNavigation('primary') : fetchNavigation('primary').catch(() => []),
        useMock ? mockFetchNavigation('footer') : fetchNavigation('footer').catch(() => []),
      ])

      siteInfo.value = {
        ...fallbackSiteInfo,
        ...nextSiteInfo,
        hero: {
          ...fallbackHeroSettings,
          ...nextSiteInfo.hero,
        },
        theme: {
          ...fallbackThemeSettings,
          ...nextSiteInfo.theme,
          cardMeta: {
            ...fallbackThemeSettings.cardMeta!,
            ...nextSiteInfo.theme?.cardMeta,
          },
        },
        comments: {
          requireNameEmail:
            nextSiteInfo.comments?.requireNameEmail ?? fallbackSiteInfo.comments!.requireNameEmail,
          registrationOnly:
            nextSiteInfo.comments?.registrationOnly ?? fallbackSiteInfo.comments!.registrationOnly,
          showEmailField:
            nextSiteInfo.comments?.showEmailField ?? fallbackSiteInfo.comments!.showEmailField,
          showUrlField:
            nextSiteInfo.comments?.showUrlField ?? fallbackSiteInfo.comments!.showUrlField,
          showCookiesOptIn:
            nextSiteInfo.comments?.showCookiesOptIn ?? fallbackSiteInfo.comments!.showCookiesOptIn,
        },
        stats: {
          ...fallbackSiteStats,
          ...nextSiteInfo.stats,
        },
        socialLinks:
          nextSiteInfo.socialLinks && nextSiteInfo.socialLinks.length > 0
            ? nextSiteInfo.socialLinks
            : undefined,
      }

      primaryMenu.value = nextPrimaryMenu
      footerMenu.value = nextFooterMenu
      shellLoadedState.value = true
    } catch (error) {
      shellErrorState.value = getErrorMessage(error, '站点基础信息加载失败，请稍后重试。')
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
