<script setup lang="ts">
import { onMounted, ref, watch } from 'vue'
import { RouterView, useRoute } from 'vue-router'
import SiteFooter from '@/components/SiteFooter.vue'
import LeftSidebar from '@/components/LeftSidebar.vue'
import SidebarProfile from '@/components/SidebarProfile.vue'
import TechInfo from '@/components/sidebar/TechInfo.vue'
import TocWidget from '@/components/TocWidget.vue'
import { useSiteShell } from '@/composables/useSiteShell'
import { showError } from '@/lib/toast'
import type { ThemeRadius, ThemeSettings, ThemeShadow } from '@/types/wordpress'

const { siteInfo, shellError, ensureLoaded, footerMenu } = useSiteShell()
const route = useRoute()
const showSubPage = ref(false)

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
  root.style.setProperty('--theme-bg-light', theme.backgroundLight)
  root.style.setProperty('--theme-bg-dark', theme.backgroundDark)
  root.style.setProperty('--theme-card-light', theme.cardLight)
  root.style.setProperty('--theme-card-dark', theme.cardDark)
  root.style.setProperty('--theme-fg-light', theme.foregroundLight)
  root.style.setProperty('--theme-fg-dark', theme.foregroundDark)
  root.style.setProperty('--theme-accent-light', theme.accentLight)
  root.style.setProperty('--theme-accent-dark', theme.accentDark)
  root.style.setProperty('--theme-border-light', theme.borderLight)
  root.style.setProperty('--theme-border-dark', theme.borderDark)
  root.style.setProperty('--container-max', `${theme.containerMaxWidth}px`)
  root.style.setProperty('--article-max-width', `${theme.articleMaxWidth}px`)
}

onMounted(() => {
  void ensureLoaded()
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


</script>

<template>
  <div class="app-container">
    <LeftSidebar />

    <div class="app-main">
      <div class="app-content">
        <main id="main-content">
          <router-view v-slot="{ Component }">
            <transition name="page" mode="out-in">
              <component :is="Component" :key="route.path" />
            </transition>
          </router-view>
        </main>

        <aside class="right-sidebar">
          <div class="aside-slide-wrap">
            <div class="aside-content" :class="{ active: showSubPage }">
              <!-- Main page: profile + social -->
              <div class="aside-page main-page">
                <SidebarProfile @toggle-sub="showSubPage = !showSubPage" />
                <TechInfo />
              </div>
              <!-- Sub page: menu -->
              <div class="aside-page sub-page">
              <div class="sub-page__header">
                <div class="aside-btn-close" @click="showSubPage = false">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><polyline points="15 18 9 12 15 6"/></svg>
                  返回
                </div>
              </div>
              <div v-if="footerMenu && footerMenu.length > 0" class="aside-card">
                <h2 class="sub-page__menu-title">菜单 <span>Menus.</span></h2>
                <ul class="sub-page__menu-list">
                  <li v-for="item in footerMenu" :key="item.id" class="sub-page__menu-item">
                    <router-link :to="item.path || item.url" @click="showSubPage = false">{{ item.title }}</router-link>
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
</template>
