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
import { StToast, useToast } from '@/ui'
import type { SidebarWidget, ThemeRadius, ThemeSettings, ThemeShadow } from '@/types/wordpress'

const toast = useToast()

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

function normalizePixelSetting(value: unknown, fallback: number, min: number, max: number) {
  const parsed = Number(value)
  if (!Number.isFinite(parsed)) return fallback
  return Math.min(max, Math.max(min, Math.round(parsed)))
}

function applyThemeSettings(theme?: ThemeSettings) {
  if (!theme) return
  const root = document.documentElement
  const radius = radiusMap[theme.radius]
  const shadow = shadowMap[theme.shadow]
  const containerMaxWidth = normalizePixelSetting(theme.containerMaxWidth, 1500, 960, 2000)
  const articleMaxWidth = normalizePixelSetting(theme.articleMaxWidth, 900, 680, 1200)

  root.style.setProperty('--primary', theme.primaryColor)
  root.style.setProperty('--font-sans-serif', theme.bodyFont)
  root.style.setProperty('--font-code', theme.codeFont)
  root.style.setProperty('--radius-medium', radius.medium)
  root.style.setProperty('--radius-large', radius.large)
  root.style.setProperty('--shadow-small', shadow.small)
  root.style.setProperty('--shadow-medium', shadow.medium)
  root.style.setProperty('--shadow-large', shadow.large)
  // Palette overrides consumed by src/styles/tokens.css semantic tokens
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
  root.style.setProperty('--container-max', `${containerMaxWidth}px`)
  root.style.setProperty('--article-max-width', `${articleMaxWidth}px`)
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
    if (err) toast.error(err)
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
  <div class="app-container">
    <LeftSidebar />

    <div class="app-main">
      <div class="app-content">
        <main id="main-content" class="app-content__main">
          <router-view v-slot="{ Component }">
            <component :is="Component" :key="route.path" />
          </router-view>
        </main>

        <aside class="right-sidebar">
          <div class="right-sidebar__scroll">
            <div
              class="right-sidebar__slider"
              :class="{ 'is-sub': showSubPage }"
            >
              <!-- Main page: widget-driven sidebar（外观→小工具 配置，按顺序渲染） -->
              <div class="main-page right-sidebar__pane">
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
              <div class="sub-page right-sidebar__pane right-sidebar__pane--sub">
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

  <!-- 全局 toast 渲染层：业务层任意 useToast() 调用都会推到这里 -->
  <StToast placement="top" :max="5" />
</template>

<style scoped>
/* ===== 页面骨架（原先靠 Tailwind 工具类，现收敛为 scoped CSS + 令牌） ===== */
.app-container {
  display: flex;
  width: 100%;
  min-height: 100vh;
  max-width: var(--container-max);
  margin: 0 auto;
  border-left: 1px solid var(--border);
  border-right: 1px solid var(--border);
  background-color: var(--card);
}

@media (max-width: 75rem) {
  .app-container {
    border-left: none;
    border-right: none;
  }
}

.app-main {
  flex: 1;
  min-width: 0;
}

@media (max-width: 75rem) {
  .app-main {
    padding-top: 56px;
  }
}

.app-content {
  display: flex;
}

.app-content__main {
  flex: 1;
  min-width: 0;
  background-color: var(--card);
}

/* ===== 桌面右侧栏（< 1000px 隐藏） ===== */
.right-sidebar {
  display: flex;
  flex-direction: column;
  flex-shrink: 0;
  width: 300px;
  min-height: 100dvh;
  border-left: 1px solid var(--border);
  background-color: var(--card);
}

@media (max-width: 62.5rem) {
  .right-sidebar {
    display: none;
  }
}

.right-sidebar__scroll {
  width: 100%;
  flex-shrink: 0;
  overflow-x: hidden;
}

.right-sidebar__slider {
  position: relative;
  display: flex;
  width: 200%;
  flex: none;
  overflow: clip;
  transition: transform 300ms ease;
}

.right-sidebar__slider.is-sub {
  transform: translateX(-50%);
}

.right-sidebar__pane {
  width: 50%;
  height: 100%;
  flex-shrink: 0;
}

.right-sidebar__pane--sub {
  display: flex;
  flex-direction: column;
}
</style>
