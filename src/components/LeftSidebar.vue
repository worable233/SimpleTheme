<script setup lang="ts">
import { computed, reactive, ref, onMounted, watch } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import { useSiteShell } from '@/composables/useSiteShell'
import { isExternalUrl } from '@/lib/theme-config'
import { getItemIcon } from './sidebar/icon-map'
import SidebarProfile from './SidebarProfile.vue'
import SidebarMobileHeader from './sidebar/SidebarMobileHeader.vue'
import SidebarNav from './sidebar/SidebarNav.vue'
import SidebarActions from './sidebar/SidebarActions.vue'
import SearchModal from './SearchModal.vue'
import TechInfo from './sidebar/TechInfo.vue'
import SiteFooter from './SiteFooter.vue'
import type { MenuItem } from '@/types/wordpress'

const { siteInfo, primaryMenu, footerMenu, shellLoading } = useSiteShell()
const route = useRoute()
const searchOpen = ref(false)
const leftSidebarRef = ref<HTMLElement | null>(null)
const leftOpen = ref(false)
const rightOpen = ref(false)
const showRightSubPage = ref(false)
const currentTheme = ref('light')

// ========== Theme ==========

onMounted(() => {
  const saved = localStorage.getItem('theme')
  if (saved === 'light' || saved === 'dark') {
    currentTheme.value = saved
  } else {
    currentTheme.value = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'
  }
  applyTheme(currentTheme.value)
})

function toggleTheme() {
  currentTheme.value = currentTheme.value === 'dark' ? 'light' : 'dark'
  applyTheme(currentTheme.value)
  localStorage.setItem('theme', currentTheme.value)
}

function applyTheme(theme: string) {
  document.documentElement.setAttribute('data-theme', theme)
  document.body.setAttribute('data-theme', theme)
  document.documentElement.style.colorScheme = theme
}

// ========== Drawer state ==========

function toggleMenu() {
  const next = !leftOpen.value
  leftOpen.value = next
  if (window.innerWidth < 1000) {
    rightOpen.value = next
  }
}

function closeAll() {
  leftOpen.value = false
  rightOpen.value = false
  showRightSubPage.value = false
  closeAllSubMenus()
}

watch(() => route.path, () => {
  leftOpen.value = false
  rightOpen.value = false
  showRightSubPage.value = false
  closeAllSubMenus()
})

// ========== Sub-menu ==========

const openMenus = ref<Set<number>>(new Set())
const closeTimer = ref<ReturnType<typeof setTimeout> | null>(null)

function toggleSubMenu(id: number) {
  const next = new Set(openMenus.value)
  if (next.has(id)) {
    next.delete(id)
  } else {
    next.add(id)
    const li = document.querySelector<HTMLElement>(`[data-menu-id="${id}"]`)
    if (li && leftSidebarRef.value) {
      const sidebarRect = leftSidebarRef.value.getBoundingClientRect()
      const liRect = li.getBoundingClientRect()
      subMenuPositions[id] = {
        x: sidebarRect.right + 14,
        y: liRect.top + liRect.height / 2,
      }
    }
  }
  openMenus.value = next
}

function closeAllSubMenus() {
  openMenus.value = new Set()
}

function onSidebarMouseLeave() {
  tooltip.visible = false
  if (openMenus.value.size === 0) return
  closeTimer.value = setTimeout(() => {
    closeAllSubMenus()
  }, 300)
}

function onSidebarMouseEnter() {
  if (closeTimer.value !== null) {
    clearTimeout(closeTimer.value)
    closeTimer.value = null
  }
}

// ========== Menu items ==========

const menuItems = computed<MenuItem[]>(() => {
  const apiMenu = primaryMenu.value

  // Only show menu items when API has returned data
  if (apiMenu && apiMenu.length > 0) {
    return apiMenu
  }

  // No items until API is ready
  return []
})

// ========== Sub-menu helpers ==========

function isCurrent(path: string): boolean {
  return route.path === path
}

function isHome(url: string): boolean {
  return url === '/' || url === ''
}

const subMenuPositions = reactive<Record<number, { x: number; y: number }>>({})

// ========== Global tooltip (escapes scroll container clipping) ==========

const tooltip = reactive({ visible: false, text: '', x: 0, y: 0 })

function onRootTooltipHover(e: MouseEvent) {
  // Only on desktop — mobile has its own tooltip positioning below icons
  if (window.innerWidth < 1200) return

  const link = (e.target as HTMLElement).closest<HTMLElement>(
    '.left-sidebar__menu > ul > li > a, .left-sidebar__menu > ul > li > button.menu-toggle',
  )
  if (!link) {
    tooltip.visible = false
    return
  }

  const titleEl = link.querySelector<HTMLElement>('.menu-item-title')
  if (!titleEl?.textContent?.trim()) {
    tooltip.visible = false
    return
  }

  const sidebarRect = leftSidebarRef.value?.getBoundingClientRect()
  if (!sidebarRect) return
  const linkRect = link.getBoundingClientRect()
  tooltip.text = titleEl.textContent.trim()
  tooltip.x = sidebarRect.right + 10
  tooltip.y = linkRect.top + linkRect.height / 2
  tooltip.visible = true
}
</script>

<template>
  <div
    class="sidebar-root"
    @mouseleave="onSidebarMouseLeave"
    @mouseenter="onSidebarMouseEnter"
    @mouseover="onRootTooltipHover"
  >
    <!-- Mobile header (fixed top, visible on < 1200px) -->
    <SidebarMobileHeader
      :shell-loading="shellLoading"
      :site-name="siteInfo.name"
      :menu-open="leftOpen"
      @toggle-menu="toggleMenu"
      @open-search="searchOpen = true"
    />

    <!-- Left drawer backdrop -->
    <Transition name="drawer-fade">
      <div v-if="leftOpen" class="drawer-overlay" @click="closeAll" />
    </Transition>

    <!-- Right panel backdrop -->
    <Transition name="drawer-fade">
      <div v-if="rightOpen" class="drawer-overlay drawer-overlay--right" @click="closeAll" />
    </Transition>

    <!-- Desktop sidebar / Mobile narrow left drawer -->
    <aside ref="leftSidebarRef" class="left-sidebar" :class="{ 'left-sidebar--open': leftOpen }">
      <!-- Search button -->
      <div class="left-sidebar__search">
        <button class="sidebar-search-btn" @click="searchOpen = true" aria-label="搜索">
          <i class="bx bx-search" style="font-size: 20px;"></i>
        </button>
      </div>

      <!-- Navigation menu -->
      <SidebarNav
        :menu-items="menuItems"
        :open-menus="openMenus"
        @toggle-sub-menu="toggleSubMenu"
      />

      <!-- Theme toggle -->
      <SidebarActions
        :current-theme="currentTheme"
        @toggle-theme="toggleTheme"
      />
    </aside>

    <!-- Mobile right profile panel -->
    <aside class="right-panel" :class="{ 'right-panel--open': rightOpen }">
      <div class="right-panel__scroll">
        <div class="right-panel__slide-wrap">
          <div class="right-panel__content" :class="{ active: showRightSubPage }">
            <!-- Main page: profile + tech info -->
            <div class="right-panel__page main-page">
              <template v-if="shellLoading">
                <div class="aside-author__cover" style="background:var(--muted);"></div>
                <div class="aside-author__info">
                  <div class="aside-author__avatar">
                    <div role="status" class="skeleton box" style="width:80px;height:80px;border-radius:50%;margin:0 auto;"></div>
                  </div>
                  <div class="aside-author__name">
                    <div role="status" class="skeleton" style="width:50%;height:16px;margin:0 auto;"></div>
                  </div>
                  <div class="aside-author__des">
                    <div role="status" class="skeleton" style="width:70%;height:14px;margin:0 auto;"></div>
                  </div>
                  <div class="aside-author__stats is-loading">
                    <div><div role="status" class="skeleton" style="width:36px;height:18px;margin:0 auto;"></div></div>
                    <div><div role="status" class="skeleton" style="width:36px;height:18px;margin:0 auto;"></div></div>
                    <div><div role="status" class="skeleton" style="width:36px;height:18px;margin:0 auto;"></div></div>
                    <div><div role="status" class="skeleton" style="width:48px;height:18px;margin:0 auto;"></div></div>
                    <div><div role="status" class="skeleton" style="width:56px;height:18px;margin:0 auto;"></div></div>
                    <div><div role="status" class="skeleton" style="width:56px;height:18px;margin:0 auto;"></div></div>
                  </div>
                </div>
              </template>
              <template v-else>
                <SidebarProfile @toggle-sub="showRightSubPage = !showRightSubPage" />
                <TechInfo />
              </template>
            </div>

            <!-- Sub page: menu / links -->
            <div class="right-panel__page sub-page">
              <div class="sub-page__header">
                <div class="aside-btn-close" @click="showRightSubPage = false">
                  <i class="bx bx-chevron-left" style="font-size: 14px;"></i>
                  返回
                </div>
              </div>
              <div v-if="footerMenu && footerMenu.length > 0" class="aside-card">
                <h2 class="sub-page__menu-title">菜单 <span>Menus.</span></h2>
                <ul class="sub-page__menu-list">
                  <li v-for="item in footerMenu" :key="item.id" class="sub-page__menu-item">
                    <router-link :to="item.path || item.url" @click="closeAll">{{ item.title }}</router-link>
                  </li>
                </ul>
              </div>
              <p v-else class="sub-page__empty">暂无菜单</p>
            </div>
          </div>
        </div>

        <SiteFooter :site-info="siteInfo" />
      </div>
    </aside>

    <SearchModal v-model="searchOpen" />

    <!-- Global tooltip (rendered outside scrollable menu, position:fixed to escape overflow clipping) -->
    <div
      class="sidebar-global-tooltip"
      :class="{ 'is-visible': tooltip.visible }"
      :style="{ left: tooltip.x + 'px', top: tooltip.y + 'px' }"
    >{{ tooltip.text }}</div>

    <!-- Floating sub-menu panels (desktop only, outside scroll container) -->
    <div class="sidebar-sub-menu-desktop">
      <template v-for="item in menuItems" :key="'sub-' + item.id">
        <ul
          v-if="item.children?.length"
          class="sub-menu sub-menu--floating"
          :class="{ 'is-open': openMenus.has(item.id) }"
          :style="{
            left: (subMenuPositions[item.id]?.x ?? 0) + 'px',
            top: (subMenuPositions[item.id]?.y ?? 0) + 'px',
          }"
        >
          <li
            v-for="child in item.children"
            :key="child.id"
            :class="{ 'current-menu-item': isCurrent(child.path) }"
          >
            <RouterLink
              v-if="!isExternalUrl(child.url) && !isHome(child.url)"
              :to="child.path || child.url"
              :aria-current="isCurrent(child.path) ? 'page' : undefined"
            >
              <span v-html="getItemIcon(child, isCurrent(child.path))"></span>
              <span class="menu-item-title">{{ child.title }}</span>
            </RouterLink>
            <RouterLink
              v-else-if="!isExternalUrl(child.url) && isHome(child.url)"
              to="/"
              :aria-current="isCurrent('/') ? 'page' : undefined"
            >
              <span v-html="getItemIcon(child, isCurrent('/'))"></span>
              <span class="menu-item-title">{{ child.title }}</span>
            </RouterLink>
            <a
              v-else
              :href="child.url"
              :target="child.target || '_blank'"
              rel="noreferrer noopener"
            >
              <span v-html="getItemIcon(child)"></span>
              <span class="menu-item-title">{{ child.title }}</span>
            </a>
          </li>
        </ul>
      </template>
    </div>
  </div>
</template>

<!-- ==================== SCOPLED STYLES ==================== -->
<style scoped>
/* ========== Drawer Overlay ========== */
.drawer-overlay {
  position: fixed;
  inset: 0;
  z-index: 998;
  background: rgba(0, 0, 0, 0.5);
}

.drawer-fade-enter-active,
.drawer-fade-leave-active {
  transition: opacity 0.25s ease;
}
.drawer-fade-enter-from,
.drawer-fade-leave-to {
  opacity: 0;
}

/* ========== Left Sidebar Search ========== */
.left-sidebar__search {
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 12px 10px;
  border-bottom: 1px solid var(--border);
  width: 100%;
}

.sidebar-search-btn {
  display: flex;
  justify-content: center;
  align-items: center;
  width: 50px;
  height: 50px;
  border-radius: 8px;
  background: none;
  border: none;
  cursor: pointer;
  color: var(--foreground);
  transition: background-color var(--transition-fast);
}

.sidebar-search-btn:hover {
  background-color: var(--menu-hover);
}

.sidebar-search-btn svg {
  width: 24px;
  height: 24px;
}

/* ========== Right profile panel (hidden on desktop, drawer on < 1000px) ========== */
.right-panel {
  display: none;
}

/* ========== Responsive < 1200px ========== */
@media (max-width: 1200px) {
  .sidebar-root {
    width: 0;
    flex-shrink: 0;
  }

  .left-sidebar__search {
    display: none;
  }

  /* Narrow left drawer */
  .left-sidebar {
    position: fixed !important;
    top: 56px !important;
    left: 0 !important;
    bottom: 0 !important;
    width: 90px !important;
    height: calc(100vh - 56px);
    height: calc(100dvh - 56px) !important;
    z-index: 999 !important;
    background: var(--card);
    border-right: 1px solid var(--border);
    border-left: none !important;
    transform: translateX(-100%);
    transition: transform 0.25s ease;
    display: flex !important;
    flex-direction: column;
    align-items: center;
    padding-top: 12px !important;
    overflow-y: auto;
    scrollbar-width: none;
    -ms-overflow-style: none;
    -webkit-overflow-scrolling: touch;
    box-shadow: none;
  }

  .left-sidebar--open {
    transform: translateX(0);
    box-shadow: 4px 0 24px rgba(0, 0, 0, 0.15);
  }

  .left-sidebar__logo {
    display: none !important;
  }

  .left-sidebar__actions {
    flex-direction: column;
    justify-content: center;
    padding: 12px 0;
    gap: 4px;
    width: 100%;
    border-top: 1px solid var(--border);
  }

  .left-sidebar__actions .sidebar-action-btn {
    width: 44px;
    height: 44px;
  }
}

/* ========== Responsive < 1000px ========== */
@media (max-width: 1000px) {
  .right-panel {
    display: block;
    position: fixed;
    top: 56px;
    right: 0;
    bottom: 0;
    width: 260px;
    height: calc(100vh - 56px);
    height: calc(100dvh - 56px);
    z-index: 999;
    background: var(--card);
    border-left: 1px solid var(--border);
    transform: translateX(100%);
    transition: transform 0.25s ease;
    overflow-y: auto;
    box-shadow: none;
  }

  .right-panel--open {
    transform: translateX(0);
    box-shadow: -4px 0 24px rgba(0, 0, 0, 0.15);
  }

  .right-panel__scroll {
    display: flex;
    flex-direction: column;
    height: 100%;
  }

  .right-panel__slide-wrap {
    overflow-x: hidden;
    overflow-y: auto;
    scrollbar-width: none;
    -ms-overflow-style: none;
    width: 100%;
    flex-shrink: 0;
    flex: 1 1 auto;
    min-height: 0;
  }
  .right-panel__slide-wrap::-webkit-scrollbar {
    display: none;
  }

  .right-panel__content {
    flex: none;
    width: 200%;
    display: flex;
    position: relative;
    transition: transform 0.3s ease;
    transform: translateX(0);
    overflow: clip;
  }

  .right-panel__content.active {
    transform: translateX(-50%);
  }

  .right-panel__page {
    width: 50%;
    flex-shrink: 0;
  }

  .right-panel__page.sub-page {
    display: flex;
    flex-direction: column;
  }

  .drawer-overlay--right {
    z-index: 998;
  }
}
</style>

<!-- ==================== Global sidebar tooltip (escapes overflow clip) ==================== -->
<style>
.sidebar-global-tooltip {
  position: fixed;
  z-index: 9999;
  transform: translateY(-50%) translateX(-4px);
  opacity: 0;
  pointer-events: none;
  font-size: 14px;
  line-height: 1.4;
  color: #fff;
  white-space: nowrap;
  padding: 5px 12px;
  border-radius: 6px;
  background-color: rgba(0, 0, 0, 0.5);
  -webkit-backdrop-filter: blur(10px);
  backdrop-filter: blur(10px);
  transition: opacity 0.12s ease, transform 0.12s ease;
}

.sidebar-global-tooltip.is-visible {
  opacity: 1;
  transform: translateY(-50%) translateX(0);
}

/* Floating sub-menu panel — position:fixed escapes scroll container clipping */
.sub-menu--floating {
  position: fixed !important;
  transform: translateY(-50%) translateX(-8px) !important;
  left: 0;
  top: 0;
  z-index: 100;
  opacity: 0;
  pointer-events: none;
  transition: opacity 0.2s ease, transform 0.2s ease;
}

/* Mobile: floating panel above drawer (z-index: 999) */
@media (max-width: 1200px) {
  .sub-menu--floating {
    z-index: 1000 !important;
  }
}

.sub-menu--floating.is-open {
  transform: translateY(-50%) translateX(0) !important;
  opacity: 1;
  pointer-events: auto;
}

/* Always show floating sub-menu panels (desktop + mobile) */
.sidebar-sub-menu-desktop {
  display: block;
}
</style>

<!-- ==================== NON-SCOPED: Root layout ==================== -->
<style>
.sidebar-root {
  display: flex;
  flex-shrink: 0;
  width: 100px;
}
</style>
