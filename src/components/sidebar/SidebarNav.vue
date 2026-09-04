<script setup lang="ts">
import { computed } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import { isExternalUrl, isSafeNavigationUrl } from '@/lib/theme-config'
import { resolveMenuIcon } from './icon-map'
import AppIcon from '@/components/AppIcon.vue'
import type { MenuItem } from '@/types/wordpress'

const props = defineProps<{
  menuItems: MenuItem[]
  openMenus: Set<number>
}>()

const emit = defineEmits<{
  'toggle-sub-menu': [id: number]
}>()

const route = useRoute()

function isCurrent(path: string): boolean {
  return route.path === path
}

function isHome(url: string): boolean {
  return url === '/' || url === ''
}

function hasChildren(item: MenuItem): boolean {
  return !!(item.children && item.children.length > 0)
}

const safeMenuItems = computed(() =>
  props.menuItems.filter((item) => hasChildren(item) || isSafeNavigationUrl(item.url)),
)
</script>

<template>
  <nav class="left-sidebar__menu">
    <ul>
      <li
        v-for="item in safeMenuItems"
        :key="item.id"
        :data-menu-id="item.id"
        :class="{
          'current-menu-item': !hasChildren(item) && isCurrent(item.path),
          'menu-item-has-children': hasChildren(item),
          'menu-item-open': openMenus.has(item.id)
        }"
      >
        <!-- 有子菜单项：切换按钮（浮动面板由 LeftSidebar 渲染） -->
        <template v-if="hasChildren(item)">
          <button
            class="menu-toggle"
            @click="emit('toggle-sub-menu', item.id)"
            :aria-expanded="openMenus.has(item.id)"
          >
            <AppIcon v-bind="resolveMenuIcon(item)" class="menu-icon" />
            <span class="menu-item-title">{{ item.title }}</span>
            <svg class="sub-menu-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" width="10" height="10">
              <polyline points="6 9 12 15 18 9" />
            </svg>
          </button>
        </template>

        <!-- 无子菜单项：普通链接 -->
        <RouterLink
          v-else-if="isSafeNavigationUrl(item.url) && !isExternalUrl(item.url) && !isHome(item.url)"
          :to="item.path || item.url"
          :aria-current="isCurrent(item.path) ? 'page' : undefined"
        >
          <AppIcon v-bind="resolveMenuIcon(item, isCurrent(item.path))" class="menu-icon" />
          <span class="menu-item-title">{{ item.title }}</span>
        </RouterLink>
        <RouterLink
          v-else-if="isSafeNavigationUrl(item.url) && !isExternalUrl(item.url) && isHome(item.url)"
          to="/"
          :aria-current="isCurrent('/') ? 'page' : undefined"
        >
          <AppIcon v-bind="resolveMenuIcon(item, isCurrent('/'))" class="menu-icon" />
          <span class="menu-item-title">{{ item.title }}</span>
        </RouterLink>
        <a
          v-else-if="isSafeNavigationUrl(item.url)"
          :href="item.url"
          :target="item.target || '_blank'"
          rel="noreferrer noopener"
        >
          <AppIcon v-bind="resolveMenuIcon(item)" class="menu-icon" />
          <span class="menu-item-title">{{ item.title }}</span>
        </a>
      </li>
    </ul>
  </nav>
</template>
