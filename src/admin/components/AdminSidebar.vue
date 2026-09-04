<script setup lang="ts">
import { ref, reactive } from 'vue'
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

// ========== Global tooltip (escapes scroll container clipping) ==========

const tooltip = reactive({ visible: false, text: '', x: 0, y: 0 })

function onNavMouseOver(e: MouseEvent) {
  const link = (e.target as HTMLElement).closest<HTMLElement>('.admin-sidebar__item > a')
  if (!link) {
    tooltip.visible = false
    return
  }
  const li = link.closest<HTMLElement>('.admin-sidebar__item')
  // Only show tooltip for items WITHOUT children
  if (li?.dataset.hasSub === 'true') {
    tooltip.visible = false
    return
  }
  const title = link.getAttribute('title') || ''
  if (!title) {
    tooltip.visible = false
    return
  }

  const sidebar = link.closest<HTMLElement>('.admin-sidebar')
  if (!sidebar) return
  const sidebarRect = sidebar.getBoundingClientRect()
  const linkRect = link.getBoundingClientRect()
  tooltip.text = title
  tooltip.x = sidebarRect.right + 10
  tooltip.y = linkRect.top + linkRect.height / 2
  tooltip.visible = true
}

function onNavMouseOut() {
  tooltip.visible = false
}

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
  <aside class="admin-sidebar relative flex h-full w-full shrink-0 flex-col items-center bg-card">
    <!-- Logo -->
    <div class="flex h-[72px] w-full shrink-0 items-center justify-center">
      <a href="./" title="返回前台" class="block size-[50px]">
        <abbr
          v-if="menuItems.length > 0"
          class="flex size-[50px] items-center justify-center rounded-full bg-muted text-xl font-bold text-foreground no-underline [text-decoration:none]"
        >S</abbr>
        <span v-else class="block size-[50px] animate-pulse rounded-full bg-muted"></span>
      </a>
    </div>

    <!-- Navigation -->
    <nav
      class="flex min-h-0 w-full flex-1 flex-col overflow-x-clip overflow-y-auto py-2 [scrollbar-width:thin]"
      @mouseover="onNavMouseOver"
      @mouseout="onNavMouseOut"
    >
      <ul class="my-auto flex flex-col items-center gap-2">
        <li
          v-for="item in menuItems"
          :key="item.id"
          class="admin-sidebar__item relative list-none"
          :data-has-sub="item.children && item.children.length > 0 ? 'true' : 'false'"
          @mouseenter="item.children?.length && openSubMenu(item.id, $event)"
          @mouseleave="item.children?.length && scheduleCloseSubMenu()"
        >
          <a
            :href="safeHref(item.url)"
            :title="item.title"
            class="relative flex size-11 items-center justify-center rounded-(--radius-large) p-0 leading-none no-underline transition-colors duration-150"
            :class="isCurrent(item)
              ? 'bg-primary text-primary-foreground hover:bg-primary'
              : 'text-foreground hover:bg-menu-hover'"
            @click.prevent="handleClick(item)"
          >
            <span class="sta-icon flex items-center justify-center leading-none">
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
              class="absolute top-1/2 right-1 size-1.5 -translate-y-1/2 rotate-45 border-t-[1.5px] border-r-[1.5px] border-current opacity-45"
            ></span>
          </a>
        </li>
      </ul>
    </nav>

    <!-- Floating submenus (position:fixed escapes nav overflow) -->
    <template v-for="item in menuItems" :key="'sub-' + item.id">
      <ul
        v-if="item.children?.length"
        class="sta-shell-pop fixed z-[10000] flex min-w-[170px] -translate-y-1/2 flex-col gap-px rounded-(--radius-large) border border-border bg-card p-1.5 shadow-(--shadow-large) transition-[opacity,translate] duration-200"
        :class="hoveredItemId === item.id
          ? 'pointer-events-auto translate-x-0 opacity-100'
          : 'pointer-events-none -translate-x-2 opacity-0'"
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
          class="flex list-none"
        >
          <a
            :href="safeHref(child.url)"
            class="sta-sub-link flex w-full items-center gap-2.5 rounded-[7px] px-3.5 py-[9px] text-[13px] whitespace-nowrap no-underline transition-colors duration-150"
            :class="currentUrl === child.url
              ? 'bg-primary text-primary-foreground hover:bg-primary'
              : 'text-foreground hover:bg-menu-hover'"
            @click.prevent="handleSubClick(child)"
          >{{ child.title }}</a>
        </li>
      </ul>
    </template>

    <!-- Bottom spacer -->
    <div class="h-3 w-full"></div>

    <!-- Global tooltip (position:fixed escapes overflow clipping) -->
    <div
      v-if="tooltip.visible"
      class="pointer-events-none fixed z-[9999] -translate-y-1/2 rounded-md bg-black/50 px-3 py-[5px] text-sm leading-normal whitespace-nowrap text-white backdrop-blur-[10px]"
      :style="{ left: tooltip.x + 'px', top: tooltip.y + 'px' }"
    >{{ tooltip.text }}</div>
  </aside>
</template>

<style scoped>
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
