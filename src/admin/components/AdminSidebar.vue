<script setup lang="ts">
import { ref, reactive } from 'vue'
import { StTooltip } from '@/ui'
import type { AdminMenuItem } from '../shell-entry'

const props = defineProps<{
  menuItems: AdminMenuItem[]
  currentUrl: string
}>()

const emit = defineEmits<{
  navigate: [url: string]
}>()

// ========== Floating submenu state (escapes nav overflow clipping) ==========

const hoveredItemId = ref<string | null>(null)
const closeTimer = ref<ReturnType<typeof setTimeout> | null>(null)
const subMenuPositions = reactive<Record<string, { x: number; y: number }>>({})

function openSubMenu(id: string, event: MouseEvent) {
  if (closeTimer.value) {
    clearTimeout(closeTimer.value)
    closeTimer.value = null
  }
  const li = (event.currentTarget as HTMLElement)?.closest('li')
  if (!li) return
  const rect = li.getBoundingClientRect()
  subMenuPositions[id] = {
    x: rect.right + 14,
    y: rect.top + rect.height / 2,
  }
  hoveredItemId.value = id
}

function scheduleCloseSubMenu() {
  closeTimer.value = setTimeout(() => {
    hoveredItemId.value = null
  }, 120)
}

function keepSubMenuOpen() {
  if (closeTimer.value) {
    clearTimeout(closeTimer.value)
    closeTimer.value = null
  }
}

function closeSubMenuNow() {
  if (closeTimer.value) {
    clearTimeout(closeTimer.value)
    closeTimer.value = null
  }
  hoveredItemId.value = null
}

// 悬浮提示统一由 src/ui 的 StTooltip 提供（见模板），不再自建 position:fixed 层。

function isCurrent(item: AdminMenuItem): boolean {
  return props.currentUrl === item.url || props.currentUrl.startsWith(item.slug + '&')
}

function getImageIconUrl(icon: string): string {
  const value = icon.trim()
  if (!value) return ''

  if (/^data:image\/svg\+xml(?:;charset=[^,;]+)?(?:;base64)?,/i.test(value)) {
    return value
  }

  try {
    const url = new URL(value, window.location.href)
    return url.protocol === 'http:' || url.protocol === 'https:' ? url.href : ''
  } catch {
    return ''
  }
}

function getDashiconClass(icon: string): string {
  const value = icon.trim()
  return /^dashicons-[a-z0-9-]+$/.test(value) ? value : ''
}

function isSafeAdminNavigationUrl(value: string): boolean {
  try {
    const target = new URL(value, window.location.href)
    return target.protocol === 'http:' || target.protocol === 'https:'
  } catch {
    return false
  }
}

function safeHref(value: string): string {
  return isSafeAdminNavigationUrl(value) ? value : '#'
}

function handleClick(item: AdminMenuItem) {
  if (!isSafeAdminNavigationUrl(item.url)) return
  emit('navigate', item.url)
}

function handleSubClick(child: { title: string; url: string }) {
  if (!isSafeAdminNavigationUrl(child.url)) return
  emit('navigate', child.url)
}
</script>

<template>
  <aside class="admin-sidebar">
    <!-- Logo -->
    <div class="admin-sidebar__logo">
      <a href="./" title="返回前台" class="admin-sidebar__logo-link">
        <abbr v-if="menuItems.length > 0" class="admin-sidebar__logo-mark">S</abbr>
        <span v-else class="admin-sidebar__logo-skeleton"></span>
      </a>
    </div>

    <!-- Navigation -->
    <nav class="admin-sidebar__nav">
      <ul class="admin-sidebar__list">
        <li
          v-for="item in menuItems"
          :key="item.id"
          class="admin-sidebar__item"
          :data-has-sub="item.children && item.children.length > 0 ? 'true' : 'false'"
          @mouseenter="item.children?.length && openSubMenu(item.id, $event)"
          @mouseleave="item.children?.length && scheduleCloseSubMenu()"
        >
          <!-- 无子项的菜单用 StTooltip 提示；有子项悬停即出浮层，无需提示 -->
          <StTooltip
            :content="item.title"
            placement="right"
            :disabled="!!item.children?.length"
            :side-offset="10"
          >
            <a
              :href="safeHref(item.url)"
              class="admin-sidebar__link"
              :class="{ 'is-current': isCurrent(item) }"
              @click.prevent="handleClick(item)"
            >
              <span class="sta-icon admin-sidebar__icon">
                <img v-if="getImageIconUrl(item.icon)" :src="getImageIconUrl(item.icon)" alt="" width="22" height="22" />
                <span
                  v-else-if="getDashiconClass(item.icon)"
                  class="dashicons dashicons-before"
                  :class="getDashiconClass(item.icon)"
                ></span>
                <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="22" height="22" aria-hidden="true">
                  <circle cx="12" cy="12" r="3" />
                </svg>
              </span>
              <!-- Chevron for items with children -->
              <span
                v-if="item.children?.length"
                aria-hidden="true"
                class="admin-sidebar__chevron"
              ></span>
            </a>
          </StTooltip>
        </li>
      </ul>
    </nav>

    <!-- Floating submenus (position:fixed escapes nav overflow) -->
    <template v-for="item in menuItems" :key="'sub-' + item.id">
      <ul
        v-if="item.children?.length"
        class="sta-shell-pop admin-submenu"
        :class="{ 'is-open': hoveredItemId === item.id }"
        :style="{
          left: (subMenuPositions[item.id]?.x ?? 0) + 'px',
          top: (subMenuPositions[item.id]?.y ?? 0) + 'px',
        }"
        @mouseenter="keepSubMenuOpen"
        @mouseleave="closeSubMenuNow"
      >
        <li
          v-for="child in item.children"
          :key="child.title + child.url"
          class="admin-submenu__item"
        >
          <a
            :href="safeHref(child.url)"
            class="sta-sub-link admin-submenu__link"
            :class="{ 'is-current': currentUrl === child.url }"
            @click.prevent="handleSubClick(child)"
          >{{ child.title }}</a>
        </li>
      </ul>
    </template>

    <!-- Bottom spacer -->
    <div class="admin-sidebar__spacer"></div>
  </aside>
</template>

<style scoped>
.admin-sidebar {
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: center;
  height: 100%;
  width: 100%;
  flex-shrink: 0;
  background-color: var(--card);
}

.admin-sidebar__logo {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 100%;
  height: 72px;
  flex-shrink: 0;
}

.admin-sidebar__logo-link {
  display: block;
  width: 50px;
  height: 50px;
}

.admin-sidebar__logo-mark {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 50px;
  height: 50px;
  border-radius: var(--radius-full);
  background-color: var(--muted);
  color: var(--foreground);
  font-size: 20px;
  font-weight: 700;
  text-decoration: none;
}

.admin-sidebar__logo-skeleton {
  display: block;
  width: 50px;
  height: 50px;
  border-radius: var(--radius-full);
  background-color: var(--muted);
  animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

.admin-sidebar__nav {
  display: flex;
  flex-direction: column;
  flex: 1;
  min-height: 0;
  width: 100%;
  padding: 8px 0;
  overflow-x: clip;
  overflow-y: auto;
  scrollbar-width: thin;
}

.admin-sidebar__list {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  margin: auto 0;
}

.admin-sidebar__item {
  position: relative;
  list-style: none;
}

.admin-sidebar__link {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 44px;
  height: 44px;
  padding: 0;
  border-radius: var(--radius-large);
  line-height: 1;
  text-decoration: none;
  color: var(--foreground);
  transition: background-color 0.15s;
}

.admin-sidebar__link:hover {
  background-color: var(--menu-hover);
}

.admin-sidebar__link.is-current {
  background-color: var(--primary);
  color: var(--primary-foreground);
}

.admin-sidebar__link.is-current:hover {
  background-color: var(--primary);
}

.admin-sidebar__icon {
  display: flex;
  align-items: center;
  justify-content: center;
  line-height: 1;
}

.admin-sidebar__chevron {
  position: absolute;
  top: 50%;
  right: 4px;
  width: 6px;
  height: 6px;
  transform: translateY(-50%) rotate(45deg);
  border-top: 1.5px solid currentColor;
  border-right: 1.5px solid currentColor;
  opacity: 0.45;
}

.admin-submenu {
  position: fixed;
  z-index: 10000;
  display: flex;
  flex-direction: column;
  gap: 1px;
  min-width: 170px;
  padding: 6px;
  border: 1px solid var(--border);
  border-radius: var(--radius-large);
  background-color: var(--card);
  box-shadow: var(--shadow-large);
  opacity: 0;
  pointer-events: none;
  transform: translateX(-8px);
  transition:
    opacity 0.2s,
    translate 0.2s;
}

.admin-submenu.is-open {
  opacity: 1;
  pointer-events: auto;
  transform: translateX(0);
}

.admin-submenu__item {
  display: flex;
  list-style: none;
}

.admin-submenu__link {
  display: flex;
  align-items: center;
  gap: 10px;
  width: 100%;
  padding: 9px 14px;
  border-radius: 7px;
  font-size: 13px;
  white-space: nowrap;
  text-decoration: none;
  color: var(--foreground);
  transition: background-color 0.15s;
}

.admin-submenu__link:hover {
  background-color: var(--menu-hover);
}

.admin-submenu__link.is-current {
  background-color: var(--primary);
  color: var(--primary-foreground);
}

.admin-submenu__link.is-current:hover {
  background-color: var(--primary);
}

.admin-sidebar__spacer {
  width: 100%;
  height: 12px;
}

.sta-icon svg {
  width: 22px;
  height: 22px;
  display: block;
}

.sta-icon :deep(img) {
  width: 22px;
  height: 22px;
  border-radius: 4px;
  object-fit: contain;
}

.sta-icon :deep(.dashicons) {
  width: 22px;
  height: 22px;
  font-size: 22px;
  line-height: 1;
  color: currentColor;
}
</style>
