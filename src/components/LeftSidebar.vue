<script setup lang="ts">
import { computed, ref, onMounted, watch } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import { useSiteShell } from '@/composables/useSiteShell'
import { isExternalUrl } from '@/lib/theme-config'
import type { MenuItem } from '@/types/wordpress'
import SearchModal from './SearchModal.vue'

const { siteInfo, primaryMenu, shellLoading } = useSiteShell()
const route = useRoute()
const searchOpen = ref(false)
const leftOpen = ref(false)
const rightOpen = ref(false)
const currentTheme = ref('light')

function formatWordCount(count: number): string {
  if (count >= 10000) {
    return (count / 10000).toFixed(1).replace(/\.0$/, '') + '万'
  }
  return count.toLocaleString()
}

function daysSince(isoDate: string): string {
  if (!isoDate) return '--'
  const then = new Date(isoDate)
  const now = new Date()
  const diffMs = now.getTime() - then.getTime()
  const days = Math.floor(diffMs / (1000 * 60 * 60 * 24))
  return days >= 0 ? `${days} 天` : '--'
}

function daysAgo(isoDate: string): string {
  if (!isoDate) return '--'
  const then = new Date(isoDate)
  const now = new Date()
  const diffMs = now.getTime() - then.getTime()
  const days = Math.floor(diffMs / (1000 * 60 * 60 * 24))
  return days >= 0 ? `${days} 天前` : '--'
}

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

function socialIconHtml(icon: string): string {
  return `<i class="${icon}"></i>`
}

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

/** 图标名称 → SVG 映射表 */
const ICON_MAP: Record<string, string> = {
  home: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>',
  chat: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>',
  about: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>',
  archive: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 8v13H3V8"/><path d="M1 3h22v5H1z"/><path d="M10 12h4"/></svg>',
  link: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>',
  star: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>',
  tag: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>',
  heart: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>',
  user: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>',
  mail: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>',
  bookmark: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>',
  settings: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>',
  music: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg>',
  photo: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>',
  calendar: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>',
  map: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>',
  bell: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>',
  clock: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>',
  search: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>',
  shopping: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>',
  book: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>',
  code: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>',
  folder: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>',
  download: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>',
  share: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>',
  lock: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>',
  'log-in': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>',
  'log-out': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>',
}

/** 中文标题 → 图标名称（向后兼容旧菜单项） */
const TITLE_ICON_MAP: Record<string, string> = {
  '首页': 'home',
  '说说': 'chat',
  '关于': 'about',
  '归档': 'archive',
  '友情链接': 'link',
  '友链': 'link',
  '赞助': 'star',
  '收藏': 'bookmark',
  '标签': 'tag',
  '分类': 'folder',
  '留言': 'mail',
  '相册': 'photo',
  '音乐': 'music',
  '搜索': 'search',
  '登录': 'log-in',
  '注册': 'user',
  '设置': 'settings',
}

/** 默认兜底图标 */
const DEFAULT_ICON_SVG = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/></svg>'

/**
 * 获取菜单项的图标 SVG。
 * 优先使用 API 返回的 icon 名称 → 按中文标题匹配 → 默认图标。
 */
function getItemIcon(item: MenuItem): string {
  // 1. 尝试按 icon 名称查找
  if (item.icon) {
    const svg = ICON_MAP[item.icon]
    if (svg) return svg
  }
  // 2. 尝试按中文标题查找
  const titleKey = TITLE_ICON_MAP[item.title]
  if (titleKey) {
    const svg = ICON_MAP[titleKey]
    if (svg) return svg
  }
  // 3. 默认
  return DEFAULT_ICON_SVG
}

const menuItems = computed(() => {
  if (primaryMenu.value && primaryMenu.value.length > 0) return primaryMenu.value
  return [
    { id: 0, title: '首页', url: '/', path: '/', target: '', description: '', current: false, icon: 'home', children: [] },
    { id: -1, title: '归档', url: '/archives', path: '/archives', target: '', description: '', current: false, icon: 'archive', children: [] },
    { id: -2, title: '说说', url: '/shuoshuo', path: '/shuoshuo', target: '', description: '', current: false, icon: 'chat', children: [] },
    { id: -3, title: '友链', url: '/links', path: '/links', target: '', description: '', current: false, icon: 'link', children: [] },
  ] as MenuItem[]
})

function isCurrent(path: string): boolean {
  return route.path === path
}

function isHome(url: string): boolean {
  return url === '/' || url === ''
}

/** 子菜单展开状态 */
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

function hasChildren(item: MenuItem): boolean {
  return !!(item.children && item.children.length > 0)
}
</script>

<template>
  <!-- Single root wrapper to avoid Vue fragment issues with scoped CSS -->
  <div class="sidebar-root">
    <!-- Mobile header (fixed top, visible on < 1200px) -->
    <header class="mobile-header">
      <button class="menu-btn" @click="toggleMenu" aria-label="打开菜单">
        <!-- ≤1000px: 汉堡菜单（两边都收起） -->
        <svg class="menu-btn__icon menu-btn__icon--hamburger" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="24" height="24">
          <line x1="3" y1="6" x2="21" y2="6" />
          <line x1="3" y1="12" x2="21" y2="12" />
          <line x1="3" y1="18" x2="21" y2="18" />
        </svg>
        <!-- 1001-1200px: 左侧面板图标（只收起左侧） -->
        <svg class="menu-btn__icon menu-btn__icon--left-panel" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="24" height="24">
          <rect x="3" y="3" width="18" height="18" rx="2" />
          <line x1="9" y1="3" x2="9" y2="21" />
          <line x1="3" y1="9" x2="9" y2="9" />
          <line x1="3" y1="15" x2="9" y2="15" />
        </svg>
      </button>

      <RouterLink to="/" class="mobile-header__brand">
        <span v-if="!shellLoading && siteInfo.name" class="mobile-header__site-name">{{ siteInfo.name }}</span>
        <span v-else-if="shellLoading" role="status" class="skeleton line" style="width:80px;height:1rem;"></span>
      </RouterLink>

      <button class="mobile-search-btn" @click="searchOpen = true" aria-label="搜索">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20">
          <circle cx="11" cy="11" r="8"></circle>
          <path d="m21 21-4.35-4.35"></path>
        </svg>
      </button>
    </header>

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
        <button class="sidebar-search-btn" @click="searchOpen = true" aria-label="搜索">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20">
            <circle cx="11" cy="11" r="8"></circle>
            <path d="m21 21-4.35-4.35"></path>
          </svg>
        </button>
      </div>

      <!-- Menu -->
      <nav class="left-sidebar__menu">
        <ul>
          <li
            v-for="item in menuItems"
            :key="item.id"
            :class="{
              'current-menu-item': !hasChildren(item) && isCurrent(item.path),
              'menu-item-has-children': hasChildren(item),
              'menu-item-open': openMenus.has(item.id)
            }"
          >
            <!-- 有子菜单项：切换按钮 -->
            <template v-if="hasChildren(item)">
              <button
                class="menu-toggle"
                @click="toggleSubMenu(item.id)"
                :aria-expanded="openMenus.has(item.id)"
              >
                <span v-html="getItemIcon(item)"></span>
                <span class="menu-item-title">{{ item.title }}</span>
                <svg class="sub-menu-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" width="10" height="10">
                  <polyline points="6 9 12 15 18 9" />
                </svg>
              </button>
              <Transition name="sub-menu-slide">
                <ul v-if="openMenus.has(item.id)" class="sub-menu">
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
                      <span v-html="getItemIcon(child)"></span>
                      <span class="menu-item-title">{{ child.title }}</span>
                    </RouterLink>
                    <RouterLink
                      v-else-if="!isExternalUrl(child.url) && isHome(child.url)"
                      to="/"
                      :aria-current="isCurrent('/') ? 'page' : undefined"
                    >
                      <span v-html="getItemIcon(child)"></span>
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
              </Transition>
            </template>

            <!-- 无子菜单项：普通链接 -->
            <RouterLink
              v-else-if="!isExternalUrl(item.url) && !isHome(item.url)"
              :to="item.path || item.url"
              :aria-current="isCurrent(item.path) ? 'page' : undefined"
            >
              <span v-html="getItemIcon(item)"></span>
              <span class="menu-item-title">{{ item.title }}</span>
            </RouterLink>
            <RouterLink
              v-else-if="!isExternalUrl(item.url) && isHome(item.url)"
              to="/"
              :aria-current="isCurrent('/') ? 'page' : undefined"
            >
              <span v-html="getItemIcon(item)"></span>
              <span class="menu-item-title">{{ item.title }}</span>
            </RouterLink>
            <a
              v-else
              :href="item.url"
              :target="item.target || '_blank'"
              rel="noreferrer noopener"
            >
              <span v-html="getItemIcon(item)"></span>
              <span class="menu-item-title">{{ item.title }}</span>
            </a>
          </li>
        </ul>
      </nav>

      <!-- Actions -->
      <div class="left-sidebar__actions">
        <button class="sidebar-action-btn" @click="toggleTheme" :aria-label="currentTheme === 'dark' ? '切换到浅色模式' : '切换到深色模式'">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
            :style="{ display: currentTheme === 'dark' ? 'none' : 'block' }">
            <circle cx="12" cy="12" r="5"></circle>
            <line x1="12" y1="1" x2="12" y2="3"></line>
            <line x1="12" y1="21" x2="12" y2="23"></line>
            <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line>
            <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line>
            <line x1="1" y1="12" x2="3" y2="12"></line>
            <line x1="21" y1="12" x2="23" y2="12"></line>
            <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line>
            <line x1="18.36" y1="5.36" x2="19.78" y2="4.22"></line>
          </svg>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
            :style="{ display: currentTheme === 'dark' ? 'block' : 'none' }">
            <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
          </svg>
        </button>
      </div>
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
          <div class="aside-author__cover">
            <img v-if="siteInfo.hero?.image" :src="siteInfo.hero.image" alt="" loading="lazy" />
            <div v-else style="width:100%;height:100%;background:var(--muted);"></div>
          </div>
          <div class="aside-author__info">
            <div class="aside-author__avatar">
              <img v-if="siteInfo.hero?.showAvatar && siteInfo.hero?.avatar" :src="siteInfo.hero.avatar" alt="" />
              <abbr v-else-if="siteInfo.name" :title="siteInfo.name">{{ siteInfo.name.charAt(0) }}</abbr>
              <div v-else role="status" class="skeleton box" style="width:80px;height:80px;border-radius:50%;"></div>
            </div>

            <div v-if="siteInfo.name" class="aside-author__name">{{ siteInfo.name }}</div>
            <div v-if="siteInfo.hero?.subtitle || siteInfo.description" class="aside-author__des">
              “{{ siteInfo.hero?.subtitle || siteInfo.description }}”
            </div>

            <div v-if="siteInfo.stats" class="aside-author__stats">
              <div><i>文章</i><span>{{ siteInfo.stats.postCount }}</span></div>
              <div><i>分类</i><span>{{ siteInfo.stats.categoryCount }}</span></div>
              <div><i>标签</i><span>{{ siteInfo.stats.tagCount }}</span></div>
              <div><i>总字数</i><span>{{ formatWordCount(siteInfo.stats.totalWordCount) }}</span></div>
              <div><i>运行时长</i><span>{{ daysSince(siteInfo.stats.registeredDate) }}</span></div>
              <div><i>最后活动</i><span>{{ daysAgo(siteInfo.stats.lastActivityDate) }}</span></div>
            </div>
          </div>
        </template>
      </div>

      <div v-if="siteInfo.socialLinks && siteInfo.socialLinks.length > 0" class="aside-section aside-social">
        <h3 class="aside-section__title">社交 <span>Social.</span></h3>
        <div class="social-content">
          <ul>
            <li v-for="link in siteInfo.socialLinks" :key="link.label">
              <a :href="link.url" target="_blank" rel="noopener noreferrer" :title="link.label" v-html="socialIconHtml(link.icon)"></a>
            </li>
          </ul>
        </div>
      </div>
    </aside>

    <SearchModal v-model="searchOpen" />
  </div>
</template>

<!-- ==================== SCOPLED STYLES ==================== -->
<style scoped>
/* ========== Mobile Header ========== */
.mobile-header {
  display: none;
}

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

/* ========== Left Sidebar Actions ========== */
.left-sidebar__search {
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 12px 10px;
  border-bottom: 1px solid var(--border);
  width: 100%;
}


/* Sub-menu slide animation */
.sub-menu-slide-enter-active,
.sub-menu-slide-leave-active {
  transition: all 0.2s ease;
  overflow: hidden;
}
.sub-menu-slide-enter-from,
.sub-menu-slide-leave-to {
  opacity: 0;
  transform: translateY(-8px);
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

.left-sidebar__actions {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 6px;
  padding: 20px 0;
  flex-shrink: 0;
  width: 100%;
  border-top: 1px solid var(--border);
}

.left-sidebar__actions .sidebar-action-btn {
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

.left-sidebar__actions .sidebar-action-btn:hover {
  background-color: var(--menu-hover);
}

.left-sidebar__actions .sidebar-action-btn svg {
  width: 24px;
  height: 24px;
}

/* ========== Mobile header + Left drawer: < 1200px ========== */
@media (max-width: 1200px) {
  /* Sidebar wrapper takes no space */
  .sidebar-root {
    width: 0;
    flex-shrink: 0;
  }

  /* Fixed top header bar */
  .mobile-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    height: 56px;
    padding: 0 16px;
    background: var(--card);
    border-bottom: 1px solid var(--border);
    z-index: 999;
  }

  .mobile-header__brand {
    position: absolute;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    align-items: center;
    text-decoration: none;
    color: var(--foreground);
  }

  .mobile-header__site-name {
    font-size: 16px;
    font-weight: 600;
  }

  .menu-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    border: none;
    background: none;
    cursor: pointer;
    color: var(--foreground);
    border-radius: 6px;
  }

  .menu-btn:hover {
    background: var(--menu-hover);
  }

  .mobile-search-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    border: none;
    background: none;
    cursor: pointer;
    color: var(--foreground);
    border-radius: 6px;
  }

  .mobile-search-btn:hover {
    background: var(--menu-hover);
  }

  .left-sidebar__search {
    display: none;
  }

  /* 中等宽度 (1001-1200px): 显示左侧面板图标，隐藏汉堡 */
  .menu-btn__icon--hamburger {
    display: none;
  }
  .menu-btn__icon--left-panel {
    display: block;
  }

  /* Narrow left drawer */
  .left-sidebar {
    position: fixed !important;
    top: 56px !important;
    left: 0 !important;
    bottom: 0 !important;
    width: 90px !important;
    height: calc(100vh - 56px) !important;
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

  .left-sidebar__menu {
    overflow-y: auto;
    scrollbar-width: none;
  }
  .left-sidebar__menu::-webkit-scrollbar {
    display: none;
  }

  .left-sidebar__menu {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 4px 0;
    width: 100%;
  }

  .left-sidebar__menu ul {
    flex-direction: column;
    align-items: center;
    gap: 30px;
    width: 100%;
  }

  .left-sidebar__menu ul li {
    width: 100%;
    display: flex;
    justify-content: center;
  }

  .left-sidebar__menu ul li a {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 50px;
    height: 50px;
    border-radius: 8px;
    box-sizing: border-box;
    padding: 0;
  }

  .left-sidebar__menu ul li a svg {
    width: 24px;
    height: 24px;
    flex-shrink: 0;
  }

  /* --- Mobile menu: title below icon, outside background box --- */
  .left-sidebar__menu ul li {
    position: relative;
    padding-bottom: 14px;
  }

  .sub-menu-chevron {
    display: none;
  }

  .left-sidebar__menu ul li a .menu-item-title,
  .left-sidebar__menu ul li button.menu-toggle .menu-item-title {
    position: absolute;
    top: calc(100% + 2px);
    left: 50%;
    transform: translateX(-50%) !important;
    opacity: 1;
    visibility: visible;
    background: none;
    backdrop-filter: none;
    -webkit-backdrop-filter: none;
    padding: 0;
    font-size: 12px;
    line-height: 1.3;
    color: var(--secondary);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 80px;
    text-align: center;
    pointer-events: none;
    display: block;
    border-radius: 0;
    transition: none;
  }

  .left-sidebar__menu ul {
    gap: 16px;
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

/* ========== Right profile drawer: hidden on desktop, visible as drawer on < 1000px ========== */
.right-panel {
  display: none;
}

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

  /* ≤1000px: 显示汉堡菜单，隐藏左侧面板图标 */
  .menu-btn__icon--hamburger {
    display: block;
  }
  .menu-btn__icon--left-panel {
    display: none;
  }
}
</style>

<!-- ==================== NON-SCOPED: Menu tooltip + layout ==================== -->
<style>
/*
  Sidebar-root wrapper for Vue 3 single-root compatibility.
  Flex-shrink:0 + width:100px matches the original left-sidebar flex layout.
*/
.sidebar-root {
  display: flex;
  flex-shrink: 0;
  width: 100px;
}

/* Tooltip: hidden by default, shown on hover (all screen sizes) */
.left-sidebar__menu ul li a,
.left-sidebar__menu ul li button.menu-toggle {
  position: relative;
}

.left-sidebar__menu ul li a .menu-item-title,
.left-sidebar__menu ul li button.menu-toggle .menu-item-title {
  display: block;
  position: absolute;
  font-size: 14px;
  color: #fff;
  left: calc(100% + 10px);
  transform: translateX(-5px);
  opacity: 0;
  visibility: hidden;
  white-space: nowrap;
  padding: 5px 12px;
  border-radius: 6px;
  background-color: rgba(0, 0, 0, 0.5);
  -webkit-backdrop-filter: blur(10px);
  backdrop-filter: blur(10px);
  pointer-events: none;
  z-index: 1;
  transition: all var(--transition-fast);
}

.left-sidebar__menu ul li a:hover .menu-item-title,
.left-sidebar__menu ul li button.menu-toggle:hover .menu-item-title {
  transform: translateX(0);
  opacity: 1;
  visibility: visible;
}

/* ========== Sub-menu (二级菜单) ========== */

/* Menu toggle button (parent item with children) */
.left-sidebar__menu ul li button.menu-toggle {
  display: flex;
  justify-content: center;
  align-items: center;
  width: 50px;
  height: 50px;
  border-radius: 8px;
  border: none;
  background: none;
  cursor: pointer;
  color: var(--foreground);
  position: relative;
  transition: background-color var(--transition-fast);
}

.left-sidebar__menu ul li button.menu-toggle:hover {
  background-color: var(--menu-hover);
}

.left-sidebar__menu ul li button.menu-toggle svg {
  width: 24px;
  height: 24px;
  color: var(--foreground);
}

/* Chevron indicator */
.sub-menu-chevron {
  position: absolute;
  bottom: 3px;
  right: 3px;
  width: 10px !important;
  height: 10px !important;
  opacity: 0.5;
  transition: transform 0.2s ease;
}

.menu-item-open .sub-menu-chevron {
  transform: rotate(180deg);
}

/* Nested sub-menu list */
.sub-menu {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px !important;
  padding: 4px 0 8px !important;
  width: 100%;
  list-style: none;
  margin: 0;
}

.sub-menu li {
  width: 100%;
  display: flex;
  justify-content: center;
}

.sub-menu li a {
  display: flex;
  justify-content: center;
  align-items: center;
  width: 40px;
  height: 40px;
  min-height: 40px;
  border-radius: 6px;
  color: var(--foreground);
  position: relative;
  transition: background-color var(--transition-fast);
  text-decoration: none;
}

.sub-menu li a:hover {
  background-color: var(--menu-hover);
}

.sub-menu li a svg {
  width: 18px;
  height: 18px;
  color: var(--foreground);
}

.sub-menu li.current-menu-item a {
  color: var(--primary-foreground);
  background-color: var(--primary);
  box-shadow: 0 2px 20px 0 rgba(0 0 0 / 0.2);
}

.sub-menu li.current-menu-item a svg {
  color: var(--primary-foreground);
  filter: drop-shadow(0 0 3px rgba(0 0 0 / 0.2));
}

/* Mobile narrow sub-menu */
@media (max-width: 1200px) {
  .sub-menu {
    gap: 2px !important;
    padding: 2px 0 4px !important;
  }

  .sub-menu li a {
    width: 36px !important;
    height: 36px !important;
    min-height: 36px !important;
  }

  .sub-menu li a svg {
    width: 16px !important;
    height: 16px !important;
  }
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
