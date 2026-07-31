<script setup lang="ts">
import { computed, reactive, ref, onMounted, onUnmounted, watch } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import { useSiteShell } from '@/composables/useSiteShell'
import { isExternalUrl } from '@/lib/theme-config'
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
    class="sidebar-root flex w-[100px] shrink-0 max-xl:w-0"
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
      <div v-if="leftOpen" class="fixed inset-0 z-[998] bg-black/50" @click="closeAll" />
    </Transition>

    <!-- Right panel backdrop -->
    <Transition name="drawer-fade">
      <div v-if="rightOpen" class="fixed inset-0 z-[998] bg-black/50" @click="closeAll" />
    </Transition>

    <!-- Desktop sidebar / Mobile narrow left drawer -->
    <aside ref="leftSidebarRef" class="left-sidebar" :class="{ 'left-sidebar--open': leftOpen }">
      <!-- Search button -->
      <div class="flex w-full items-center justify-center border-b border-border px-2.5 py-3 max-xl:hidden">
        <button
          class="flex h-[50px] w-[50px] cursor-pointer items-center justify-center rounded-lg border-none bg-transparent text-foreground transition-colors duration-150 hover:bg-menu-hover"
          @click="searchOpen = true"
          aria-label="搜索"
        >
          <AppIcon name="search" :size="20" />
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
    <aside
      class="hidden max-lg:fixed max-lg:top-14 max-lg:bottom-0 max-lg:right-0 max-lg:z-[999] max-lg:block max-lg:h-[calc(100dvh-3.5rem)] max-lg:w-[260px] max-lg:translate-x-full max-lg:overflow-y-auto max-lg:border-l max-lg:border-border max-lg:bg-card max-lg:transition-transform max-lg:duration-[250ms]"
      :class="{ 'max-lg:translate-x-0! max-lg:shadow-[-4px_0_24px_rgba(0,0,0,0.15)]': rightOpen }"
    >
      <div class="flex h-full flex-col">
        <div
          class="min-h-0 w-full flex-1 overflow-x-hidden overflow-y-auto [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
        >
          <div
            class="relative flex w-[200%] flex-none overflow-clip transition-transform duration-300"
            :class="{ '-translate-x-1/2': showRightSubPage }"
          >
            <!-- Main page: profile + tech info -->
            <div class="main-page w-1/2 shrink-0">
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
            <div class="sub-page flex w-1/2 shrink-0 flex-col">
              <div class="sub-page__header">
                <div class="aside-btn-close" @click="showRightSubPage = false">
                  <AppIcon name="chevron-left" :size="14" />
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
              <AppIcon v-bind="resolveMenuIcon(child, isCurrent(child.path))" class="menu-icon" />
              <span class="menu-item-title">{{ child.title }}</span>
            </RouterLink>
            <RouterLink
              v-else-if="!isExternalUrl(child.url) && isHome(child.url)"
              to="/"
              :aria-current="isCurrent('/') ? 'page' : undefined"
            >
              <AppIcon v-bind="resolveMenuIcon(child, isCurrent('/'))" class="menu-icon" />
              <span class="menu-item-title">{{ child.title }}</span>
            </RouterLink>
            <a
              v-else
              :href="child.url"
              :target="child.target || '_blank'"
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
