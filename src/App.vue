<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { RouterView, useRoute } from 'vue-router'
import SiteFooter from '@/components/SiteFooter.vue'
import LeftSidebar from '@/components/LeftSidebar.vue'
import SidebarProfile from '@/components/SidebarProfile.vue'
import TechInfo from '@/components/sidebar/TechInfo.vue'
import HitokotoCard from '@/components/sidebar/HitokotoCard.vue'
import GenericWidget from '@/components/sidebar/GenericWidget.vue'
import TocWidget from '@/components/TocWidget.vue'
import AuthModal from '@/components/AuthModal.vue'
import AnnouncementModal from '@/components/AnnouncementModal.vue'
import AnnouncementCapsule from '@/components/AnnouncementCapsule.vue'
import CookieConsent from '@/components/CookieConsent.vue'
import { useSiteShell } from '@/composables/useSiteShell'
import { useAuth } from '@/composables/useAuth'
import { useAuthModal } from '@/composables/useAuthModal'
import { isExternalUrl, isSafeNavigationUrl } from '@/lib/theme-config'
import { showError } from '@/lib/toast'
import type { SidebarWidget, ThemeRadius, ThemeSettings, ThemeShadow } from '@/types/wordpress'

const { siteInfo, shellError, ensureLoaded, footerMenu } = useSiteShell()
const route = useRoute()

// 未在「外观→小工具」配置时的默认三件套，避免侧栏空白
const DEFAULT_SIDEBAR: SidebarWidget[] = [
  { type: 'profile', settings: { showStats: true, showHeatmap: true, showSocial: true } },
  { type: 'hitokoto', settings: { api: '' } },
  { type: 'techInfo' },
]
const sidebarWidgets = computed<SidebarWidget[]>(() =>
  siteInfo.value.sidebar && siteInfo.value.sidebar.length > 0
    ? siteInfo.value.sidebar
    : DEFAULT_SIDEBAR,
)
const showSubPage = ref(false)

const safeFooterMenu = computed(() =>
  (footerMenu.value || []).filter((item) => {
    const target = item.path || item.url
    return isSafeNavigationUrl(item.url) && isSafeNavigationUrl(target)
  }),
)

const { init: initAuth } = useAuth()
const { visible: authModalVisible } = useAuthModal()

const radiusMap: Record<ThemeRadius, { medium: string; large: string }> = {
  small: { medium: '0.25rem', large: '0.5rem' },
  medium: { medium: '0.375rem', large: '0.75rem' },
  large: { medium: '0.625rem', large: '1rem' },
}

const shadowMap: Record<ThemeShadow, { small: string; medium: string; large: string }> = {
  none: { small: 'none', medium: 'none', large: 'none' },
  small: {
    small: '0 1px 2px 0 rgb(0 0 0 / 0.06)',
    medium: '0 2px 4px rgb(0 0 0 / 0.08)',
    large: '0 6px 12px rgb(0 0 0 / 0.1)',
  },
  medium: {
    small: '0 2px 4px rgb(0 0 0 / 0.1)',
    medium: '0 6px 18px rgb(0 0 0 / 0.12)',
    large: '0 12px 28px rgb(0 0 0 / 0.16)',
  },
  large: {
    small: '0 4px 10px rgb(0 0 0 / 0.12)',
    medium: '0 10px 24px rgb(0 0 0 / 0.16)',
    large: '0 18px 40px rgb(0 0 0 / 0.2)',
  },
}

function applyThemeSettings(theme?: ThemeSettings) {
  if (!theme) return
  const root = document.documentElement
  const radius = radiusMap[theme.radius]
  const shadow = shadowMap[theme.shadow]

  root.style.setProperty('--primary', theme.primaryColor)
  root.style.setProperty('--font-sans-serif', theme.bodyFont)
  root.style.setProperty('--font-code', theme.codeFont)
  root.style.setProperty('--radius-medium', radius.medium)
  root.style.setProperty('--radius-large', radius.large)
  root.style.setProperty('--shadow-small', shadow.small)
  root.style.setProperty('--shadow-medium', shadow.medium)
  root.style.setProperty('--shadow-large', shadow.large)
  // Palette overrides consumed by variables.css semantic tokens
  // (skip empty values so var(--theme-*, fallback) keeps its default)
  const palette: Record<string, string | undefined> = {
    '--theme-bg-light': theme.backgroundLight,
    '--theme-bg-dark': theme.backgroundDark,
    '--theme-card-light': theme.cardLight,
    '--theme-card-dark': theme.cardDark,
    '--theme-fg-light': theme.foregroundLight,
    '--theme-fg-dark': theme.foregroundDark,
    '--theme-accent-light': theme.accentLight,
    '--theme-accent-dark': theme.accentDark,
    '--theme-border-light': theme.borderLight,
    '--theme-border-dark': theme.borderDark,
  }
  for (const [name, value] of Object.entries(palette)) {
    if (value) root.style.setProperty(name, value)
    else root.style.removeProperty(name)
  }
  root.style.setProperty('--container-max', `${theme.containerMaxWidth}px`)
  root.style.setProperty('--article-max-width', `${theme.articleMaxWidth}px`)
}

onMounted(() => {
  void ensureLoaded()
  void initAuth()
})

watch(
  () => siteInfo.value.theme,
  (theme) => {
    applyThemeSettings(theme)
  },
  { immediate: true, deep: true },
)

watch(
  () => shellError.value,
  (err) => {
    if (err) showError(err)
  },
)

// Update favicon from WordPress site icon
watch(
  () => siteInfo.value.siteIcon,
  (icon) => {
    if (!icon) return
    let link = document.querySelector<HTMLLinkElement>('link[rel="icon"]')
    if (!link) {
      link = document.createElement('link')
      link.rel = 'icon'
      document.head.appendChild(link)
    }
    link.href = icon
  },
  { immediate: true },
)

// 动态更新页面描述（<meta name="description">）
function updateMetaDescription(desc: string) {
  if (!desc) return
  let meta = document.querySelector<HTMLMetaElement>('meta[name="description"]')
  if (!meta) {
    meta = document.createElement('meta')
    meta.name = 'description'
    document.head.appendChild(meta)
  }
  meta.content = desc
}

watch(
  [() => route.path, () => siteInfo.value.description],
  ([path]) => {
    // 首页标题用站点副标题
    if (path === '/') {
      updateMetaDescription(siteInfo.value.description || siteInfo.value.name || '')
    }
  },
  { immediate: true },
)


</script>

<template>
  <div
    class="app-container mx-auto flex min-h-screen max-w-(--container-max) border-x border-border bg-card max-xl:border-none"
  >
    <LeftSidebar />

    <div class="app-main min-w-0 flex-1 max-xl:pt-14">
      <div class="app-content flex">
        <main id="main-content" class="min-w-0 flex-1 bg-card">
          <router-view v-slot="{ Component }">
            <component :is="Component" :key="route.path" />
          </router-view>
        </main>

        <aside
          class="right-sidebar flex min-h-dvh w-[300px] shrink-0 flex-col border-l border-border bg-card max-lg:hidden"
        >
          <div class="w-full shrink-0 overflow-x-hidden">
            <div
              class="relative flex w-[200%] flex-none overflow-clip transition-transform duration-300 ease-[ease]"
              :class="{ '-translate-x-1/2': showSubPage }"
            >
              <!-- Main page: widget-driven sidebar（外观→小工具 配置，按顺序渲染） -->
              <div class="main-page h-full w-1/2 shrink-0">
                <template v-for="(widget, i) in sidebarWidgets" :key="i">
                  <SidebarProfile
                    v-if="widget.type === 'profile'"
                    :settings="widget.settings"
                    @toggle-sub="showSubPage = !showSubPage"
                  />
                  <HitokotoCard
                    v-else-if="widget.type === 'hitokoto'"
                    :settings="widget.settings"
                  />
                  <TechInfo v-else-if="widget.type === 'techInfo'" />
                  <GenericWidget
                    v-else-if="widget.type === 'html'"
                    :html="widget.html"
                  />
                </template>
              </div>
              <!-- Sub page: menu -->
              <div class="sub-page flex h-full w-1/2 shrink-0 flex-col">
                <div class="sub-page__header">
                  <div class="aside-btn-close" @click="showSubPage = false">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><polyline points="15 18 9 12 15 6"/></svg>
                    返回
                  </div>
                </div>
                <div v-if="safeFooterMenu.length > 0" class="aside-card">
                  <h2 class="sub-page__menu-title">菜单 <span>Menus.</span></h2>
                  <ul class="sub-page__menu-list">
                    <li v-for="item in safeFooterMenu" :key="item.id" class="sub-page__menu-item">
                      <router-link
                        v-if="!isExternalUrl(item.path || item.url)"
                        :to="item.path || item.url"
                        @click="showSubPage = false"
                      >{{ item.title }}</router-link>
                      <a
                        v-else
                        :href="item.url"
                        :target="item.target || '_blank'"
                        rel="noopener noreferrer"
                        @click="showSubPage = false"
                      >{{ item.title }}</a>
                    </li>
                  </ul>
                </div>
                <p v-else class="sub-page__empty">暂无菜单</p>
              </div>
            </div>
          </div>
          <TocWidget />
          <SiteFooter :site-info="siteInfo" />
        </aside>
      </div>
    </div>
  </div>

  <AuthModal v-if="authModalVisible" @close="useAuthModal().close()" />

  <!-- Announcement Modal (only when mode === 'modal') -->
  <AnnouncementModal
    v-if="siteInfo.announcement?.enabled && siteInfo.announcement.mode === 'modal'"
    :announcement="siteInfo.announcement"
  />

  <!-- Announcement Capsule (only when mode === 'capsule') -->
  <AnnouncementCapsule
    v-if="siteInfo.announcement?.enabled && siteInfo.announcement.mode === 'capsule'"
    :announcement="siteInfo.announcement"
  />

  <!-- Cookie Consent Toast -->
  <CookieConsent
    v-if="siteInfo.cookieConsent?.enabled"
    :message="siteInfo.cookieConsent.message"
  />
</template>
