<script setup lang="ts">
import { computed, ref, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useSiteShell } from '@/composables/useSiteShell'
import SidebarProfile from './SidebarProfile.vue'
import SidebarMobileHeader from './sidebar/SidebarMobileHeader.vue'
import SidebarNav from './sidebar/SidebarNav.vue'
import SidebarActions from './sidebar/SidebarActions.vue'
import SearchModal from './SearchModal.vue'
import type { MenuItem } from '@/types/wordpress'

const { siteInfo, primaryMenu, shellLoading } = useSiteShell()
const route = useRoute()
const searchOpen = ref(false)
const leftOpen = ref(false)
const rightOpen = ref(false)
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
}

watch(() => route.path, () => {
  leftOpen.value = false
  rightOpen.value = false
})

// ========== Sub-menu ==========

const openMenus = ref<Set<number>>(new Set())

function toggleSubMenu(id: number) {
  const next = new Set(openMenus.value)
  if (next.has(id)) {
    next.delete(id)
  } else {
    next.add(id)
  }
  openMenus.value = next
}

// ========== Menu items ==========

const menuItems = computed<MenuItem[]>(() => {
  const apiMenu = primaryMenu.value

  // User has configured a menu in WordPress → use it directly
  if (apiMenu && apiMenu.length > 0) {
    return apiMenu
  }

  // Fallback defaults when no menu is configured
  const fallbackItems: MenuItem[] = [
    { id: -1, title: '首页', url: '/', path: '/', target: '', description: '', current: false, icon: '', children: [] },
    { id: -2, title: '说说', url: '/shuoshuo', path: '/shuoshuo', target: '', description: '', current: false, icon: '', children: [] },
    { id: -3, title: '友链', url: '/links', path: '/links', target: '', description: '', current: false, icon: '', children: [] },
  ]
  return fallbackItems
})
</script>

<template>
  <div class="sidebar-root">
    <!-- Mobile header (fixed top, visible on < 1200px) -->
    <SidebarMobileHeader
      :shell-loading="shellLoading"
      :site-name="siteInfo.name"
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
    <aside class="left-sidebar" :class="{ 'left-sidebar--open': leftOpen }">
      <!-- Search button -->
      <div class="left-sidebar__search">
        <button class="sidebar-search-btn gradient-card" @click="searchOpen = true" aria-label="搜索">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20">
            <circle cx="11" cy="11" r="8"></circle>
            <path d="m21 21-4.35-4.35"></path>
          </svg>
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
      <div class="right-panel__profile">
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
          <SidebarProfile :no-toggle="true" />
        </template>
      </div>
    </aside>

    <SearchModal v-model="searchOpen" />
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

  .right-panel__profile {
    width: 100%;
  }

  .drawer-overlay--right {
    z-index: 998;
  }
}
</style>

<!-- ==================== NON-SCOPED: Root layout + gradient card ==================== -->
<style>
.sidebar-root {
  display: flex;
  flex-shrink: 0;
  width: 100px;
}

/* Gradient card: applied to search button */
.gradient-card {
  border-radius: 0.5rem;
  position: relative;
  z-index: 0;
}
.gradient-card::before {
  background:
    linear-gradient(var(--card), var(--card)) padding-box,
    linear-gradient(45deg, var(--accent), var(--primary)) border-box;
  border: 2px solid transparent;
  border-radius: inherit;
  content: "";
  inset: 0;
  opacity: 0;
  position: absolute;
  transition: opacity 0.2s;
  z-index: -1;
}
.gradient-card:hover {
  color: var(--foreground);
}
.gradient-card:hover::before {
  opacity: 1;
}
</style>
