<script setup lang="ts">
import { computed, reactive, ref, onMounted, onUnmounted, watch } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import { StButton } from '@/ui'
import { useSiteShell } from '@/composables/useSiteShell'
import { isExternalUrl, isSafeNavigationUrl } from '@/lib/theme-config'
import { resolveMenuIcon } from './sidebar/icon-map'
import AppIcon from '@/components/AppIcon.vue'
import SidebarProfile from './SidebarProfile.vue'
import SidebarMobileHeader from './sidebar/SidebarMobileHeader.vue'
import SidebarNav from './sidebar/SidebarNav.vue'
import SidebarActions from './sidebar/SidebarActions.vue'
import SearchModal from './SearchModal.vue'
import TechInfo from './sidebar/TechInfo.vue'
import HitokotoCard from './sidebar/HitokotoCard.vue'
import GenericWidget from './sidebar/GenericWidget.vue'
import SiteFooter from './SiteFooter.vue'
import type { MenuItem, SidebarWidget } from '@/types/wordpress'

const { siteInfo, primaryMenu, footerMenu, shellLoading } = useSiteShell()

// 与 App.vue 右侧栏一致：移动端抽屉也按「外观→小工具」配置渲染，未配置时回退默认三件套
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
  // 收起抽屉时同步关闭浮动子菜单，避免面板脱离抽屉残留
  if (!next) closeAllSubMenus()
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
  // 窄屏下抽屉收起时菜单不可见，忽略切换（防止脚本/焊点触发导致面板脱离抽屉残留）
  if (window.innerWidth < 1200 && !leftOpen.value) return
  const next = new Set(openMenus.value)
  if (next.has(id)) {
    next.delete(id)
  } else {
    // 同一时刻只展开一个子菜单 — 多个浮动面板会在相近坐标叠加
    next.clear()
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

/** 点击菜单外部关闭浮动子菜单 — mouseleave 在触屏设备上不触发，必须有 click 兑底 */
function onDocumentClick(e: MouseEvent) {
  if (openMenus.value.size === 0) return
  const target = e.target as HTMLElement
  if (target.closest('.menu-toggle') || target.closest('.sub-menu--floating')) return
  closeAllSubMenus()
}

onMounted(() => document.addEventListener('click', onDocumentClick))
onUnmounted(() => document.removeEventListener('click', onDocumentClick))

// 正文 core/search 区块提交时由 useContentEnhancer 派发，打开搜索弹窗（关键词由 SearchModal 自行接收）
function onOpenSearchEvent() {
  searchOpen.value = true
}
onMounted(() => window.addEventListener('st:open-search', onOpenSearchEvent))
onUnmounted(() => window.removeEventListener('st:open-search', onOpenSearchEvent))

function onSidebarMouseLeave() {
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

const safeFooterMenu = computed<MenuItem[]>(() =>
  (footerMenu.value || []).filter((item) => isSafeNavigationUrl(item.url)),
)

function safeMenuChildren(item: MenuItem): MenuItem[] {
  return (item.children || []).filter((child) => isSafeNavigationUrl(child.url))
}

// ========== Sub-menu helpers ==========

function isCurrent(path: string): boolean {
  return route.path === path
}

function isHome(url: string): boolean {
  return url === '/' || url === ''
}

const subMenuPositions = reactive<Record<number, { x: number; y: number }>>({})
</script>

<template>
  <div
    class="sidebar-root"
    @mouseleave="onSidebarMouseLeave"
    @mouseenter="onSidebarMouseEnter"
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
      <div v-if="leftOpen" class="sidebar-backdrop" @click="closeAll" />
    </Transition>

    <!-- Right panel backdrop -->
    <Transition name="drawer-fade">
      <div v-if="rightOpen" class="sidebar-backdrop" @click="closeAll" />
    </Transition>

    <!-- Desktop sidebar / Mobile narrow left drawer -->
    <aside ref="leftSidebarRef" class="left-sidebar" :class="{ 'left-sidebar--open': leftOpen }">
      <!-- Search button -->
      <div class="left-sidebar__search">
        <StButton quaternary circle size="large" aria-label="搜索" @click="searchOpen = true">
          <template #icon><AppIcon name="search" :size="20" /></template>
        </StButton>
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
    <aside
      class="sidebar-drawer"
      :class="{ 'is-open': rightOpen }"
    >
      <div class="sidebar-drawer__inner">
        <div class="sidebar-drawer__scroll">
          <div
            class="sidebar-drawer__slider"
            :class="{ 'is-sub': showRightSubPage }"
          >
            <!-- Main page: profile + tech info -->
            <div class="main-page sidebar-drawer__pane">
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
                <template v-for="(widget, i) in sidebarWidgets" :key="i">
                  <SidebarProfile
                    v-if="widget.type === 'profile'"
                    :settings="widget.settings"
                    @toggle-sub="showRightSubPage = !showRightSubPage"
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
              </template>
            </div>

            <!-- Sub page: menu / links -->
            <div class="sub-page sidebar-drawer__pane sidebar-drawer__pane--sub">
              <div class="sub-page__header">
                <div class="aside-btn-close" @click="showRightSubPage = false">
                  <AppIcon name="chevron-left" :size="14" />
                  返回
                </div>
              </div>
              <div v-if="safeFooterMenu.length > 0" class="aside-card">
                <h2 class="sub-page__menu-title">菜单 <span>Menus.</span></h2>
                <ul class="sub-page__menu-list">
                  <li v-for="item in safeFooterMenu" :key="item.id" class="sub-page__menu-item">
                    <router-link v-if="!isExternalUrl(item.url)" :to="item.path || item.url" :target="item.target !== '_self' ? item.target : undefined" @click="closeAll">{{ item.title }}</router-link>
                    <a v-else :href="item.url" :target="item.target || '_self'" rel="noopener noreferrer" @click="closeAll">{{ item.title }}</a>
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
            v-for="child in safeMenuChildren(item)"
            :key="child.id"
            :class="{ 'current-menu-item': isCurrent(child.path) }"
          >
            <RouterLink
              v-if="!isExternalUrl(child.url) && !isHome(child.url)"
              :to="child.path || child.url"
              :target="child.target !== '_self' ? child.target : undefined"
              :aria-current="isCurrent(child.path) ? 'page' : undefined"
            >
              <AppIcon v-bind="resolveMenuIcon(child, isCurrent(child.path))" class="menu-icon" />
              <span class="menu-item-title">{{ child.title }}</span>
            </RouterLink>
            <RouterLink
              v-else-if="!isExternalUrl(child.url) && isHome(child.url)"
              to="/"
              :target="child.target !== '_self' ? child.target : undefined"
              :aria-current="isCurrent('/') ? 'page' : undefined"
            >
              <AppIcon v-bind="resolveMenuIcon(child, isCurrent('/'))" class="menu-icon" />
              <span class="menu-item-title">{{ child.title }}</span>
            </RouterLink>
            <a
              v-else
              :href="child.url"
              :target="child.target || '_self'"
              rel="noreferrer noopener"
            >
              <AppIcon v-bind="resolveMenuIcon(child)" class="menu-icon" />
              <span class="menu-item-title">{{ child.title }}</span>
            </a>
          </li>
        </ul>
      </template>
    </div>
  </div>
</template>

<style scoped>
/* ===== 布局骨架（原先靠 Tailwind 工具类，现收敛为 scoped CSS + 令牌） ===== */
.sidebar-root {
  display: flex;
  width: 100px;
  flex-shrink: 0;
}

@media (max-width: 75rem) {
  .sidebar-root {
    width: 0;
  }
}

.sidebar-backdrop {
  position: fixed;
  inset: 0;
  z-index: 998;
  background-color: rgb(0 0 0 / 0.5);
}

.left-sidebar__search {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 100%;
  padding: 12px 10px;
  border-bottom: 1px solid var(--border);
}

@media (max-width: 75rem) {
  .left-sidebar__search {
    display: none;
  }
}

/* ===== 移动端右侧资料抽屉（< 1000px） ===== */
.sidebar-drawer {
  display: none;
}

@media (max-width: 62.5rem) {
  .sidebar-drawer {
    position: fixed;
    top: 56px;
    right: 0;
    bottom: 0;
    z-index: 999;
    display: block;
    width: 260px;
    height: calc(100dvh - 3.5rem);
    overflow-y: auto;
    border-left: 1px solid var(--border);
    background-color: var(--card);
    transform: translateX(100%);
    transition: transform 250ms;
  }

  .sidebar-drawer.is-open {
    transform: translateX(0) !important;
    box-shadow: -4px 0 24px rgb(0 0 0 / 0.15);
  }
}

.sidebar-drawer__inner {
  display: flex;
  flex-direction: column;
  height: 100%;
}

.sidebar-drawer__scroll {
  flex: 1;
  width: 100%;
  min-height: 0;
  overflow-x: hidden;
  overflow-y: auto;
  scrollbar-width: none;
}

.sidebar-drawer__scroll::-webkit-scrollbar {
  display: none;
}

.sidebar-drawer__slider {
  position: relative;
  display: flex;
  width: 200%;
  flex: none;
  overflow: clip;
  transition: transform 300ms;
}

.sidebar-drawer__slider.is-sub {
  transform: translateX(-50%);
}

.sidebar-drawer__pane {
  width: 50%;
  flex-shrink: 0;
}

.sidebar-drawer__pane--sub {
  display: flex;
  flex-direction: column;
}

/* Vue <Transition> classes for drawer backdrops */
.drawer-fade-enter-active,
.drawer-fade-leave-active {
  transition: opacity 0.25s ease;
}
.drawer-fade-enter-from,
.drawer-fade-leave-to {
  opacity: 0;
}
</style>
