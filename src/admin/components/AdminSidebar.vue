<script setup lang="ts">
import { computed, ref, reactive } from 'vue'
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
  if (li?.classList.contains('admin-sidebar__item--has-sub')) {
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

function onNavMouseOut(e: MouseEvent) {
  const related = e.relatedTarget as HTMLElement | null
  if (related?.closest('.admin-sidebar__tooltip-global')) return
  tooltip.visible = false
}

function isCurrent(item: AdminMenuItem): boolean {
  return props.currentUrl === item.url || props.currentUrl.startsWith(item.slug + '&')
}

// Decode a data:image/svg+xml URL and force colors to inherit from CSS
function normalizeSvgToCurrentColor(svgContent: string): string {
  return svgContent
    // Replace hex fills (but keep none/currentColor/transparent/inherit)
    .replace(/\sfill="(?:#[0-9a-fA-F]{3,8}|black|white)"/gi, ' fill="currentColor"')
    .replace(/\sstroke="(?:#[0-9a-fA-F]{3,8}|black|white)"/gi, ' stroke="currentColor"')
    .replace(/\sfill='(?:#[0-9a-fA-F]{3,8}|black|white)'/gi, " fill='currentColor'")
    .replace(/\sstroke='(?:#[0-9a-fA-F]{3,8}|black|white)'/gi, " stroke='currentColor'")
}

function decodeSvgDataUrl(url: string): string {
  try {
    let svgContent: string
    if (url.includes(';base64,')) {
      svgContent = atob(url.split(';base64,')[1] || '')
    } else {
      svgContent = decodeURIComponent(url.split(',')[1] || '')
    }
    // Strip XML declaration
    svgContent = svgContent.replace(/<\?xml[^>]*\?>/g, '')
    // Force currentColor
    svgContent = normalizeSvgToCurrentColor(svgContent)
    return svgContent
  } catch (_) {
    return ''
  }
}

function getIconHtml(icon: string): string {
  if (!icon || icon === 'none' || icon === 'div') {
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="22" height="22"><circle cx="12" cy="12" r="3"/></svg>'
  }
  if (icon.startsWith('data:image/svg')) {
    // Decode data URL SVG and force currentColor to match the theme
    const inlined = decodeSvgDataUrl(icon)
    if (inlined) return inlined
    return `<img src="${icon}" alt="" width="22" height="22" />`
  }
  if (icon.startsWith('http')) {
    return `<img src="${icon}" alt="" width="22" height="22" />`
  }
  if (icon.startsWith('<svg')) {
    // Normalize inline SVGs to use currentColor so they inherit the link's text color
    return normalizeSvgToCurrentColor(icon)
  }
  if (icon.startsWith('dashicons-')) {
    return `<span class="dashicons dashicons-before ${icon}" style="font-family:dashicons"></span>`
  }
  return icon
}

function handleClick(item: AdminMenuItem) {
  emit('navigate', item.url)
}

function handleSubClick(child: { title: string; url: string }) {
  emit('navigate', child.url)
}
</script>

<template>
  <aside class="admin-sidebar">
    <!-- Logo -->
    <div class="admin-sidebar__logo">
      <a :href="'./'" class="admin-sidebar__logo-link" title="返回前台">
        <abbr v-if="menuItems.length > 0">S</abbr>
        <span v-else class="admin-sidebar__logo-loading"></span>
      </a>
    </div>

    <!-- Navigation -->
    <nav
      class="admin-sidebar__nav"
      @mouseover="onNavMouseOver"
      @mouseout="onNavMouseOut"
    >
      <ul>
        <li
          v-for="item in menuItems"
          :key="item.id"
          :class="{
            'admin-sidebar__item': true,
            'admin-sidebar__item--active': isCurrent(item),
            'admin-sidebar__item--has-sub': item.children && item.children.length > 0,
          }"
          @mouseenter="item.children?.length && openSubMenu(item.id, $event)"
          @mouseleave="item.children?.length && scheduleCloseSubMenu()"
        >
          <a
            :href="item.url"
            :title="item.title"
            @click.prevent="handleClick(item)"
          ><span v-html="getIconHtml(item.icon)"></span></a>
        </li>
      </ul>
    </nav>

    <!-- Floating submenus (outside nav overflow) -->
    <div class="admin-sidebar__floating-submenus">
      <template v-for="item in menuItems" :key="'sub-' + item.id">
        <ul
          v-if="item.children?.length"
          class="admin-sidebar__sub"
          :class="{ 'admin-sidebar__sub--open': hoveredItemId === item.id }"
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
            :class="{ 'admin-sidebar__sub--current': currentUrl === child.url }"
          >
            <a
              :href="child.url"
              @click.prevent="handleSubClick(child)"
            >{{ child.title }}</a>
          </li>
        </ul>
      </template>
    </div>

    <!-- Bottom spacer -->
    <div class="admin-sidebar__spacer"></div>

    <!-- Global tooltip (position:fixed escapes overflow clipping) -->
    <div
      v-if="tooltip.visible"
      class="admin-sidebar__tooltip-global"
      :style="{ left: tooltip.x + 'px', top: tooltip.y + 'px' }"
    >{{ tooltip.text }}</div>
  </aside>
</template>

<style scoped>
.admin-sidebar {
  width: 100%;
  height: 100%;
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: center;
  flex-shrink: 0;
  background-color: var(--card);
  z-index: auto;
  padding-top: 0;
}

.admin-sidebar__logo {
  width: 100%;
  height: 72px;
  display: flex;
  justify-content: center;
  align-items: center;
  flex-shrink: 0;
}

.admin-sidebar__logo-link {
  display: block;
  width: 50px;
  height: 50px;
}

.admin-sidebar__logo-link abbr {
  display: flex;
  width: 50px;
  height: 50px;
  justify-content: center;
  align-items: center;
  background: var(--muted, #f5f5f5);
  border-radius: 50%;
  font-size: 20px;
  font-weight: 700;
  color: var(--foreground, #333);
  text-decoration: none;
}

.admin-sidebar__logo-loading {
  display: block;
  width: 50px;
  height: 50px;
  border-radius: 50%;
  background: var(--muted, #f5f5f5);
  animation: pulse 1.5s ease-in-out infinite;
}

.admin-sidebar__nav {
  flex: 1;
  display: flex;
  flex-direction: column;
  min-height: 0;
  overflow-y: auto;
  overflow-x: clip;
  width: 100%;
  padding: 8px 0;
  scrollbar-width: thin;
}

.admin-sidebar__nav::-webkit-scrollbar {
  width: 4px;
}

.admin-sidebar__nav::-webkit-scrollbar-thumb {
  background: var(--border, #e2e2e2);
  border-radius: 2px;
}

.admin-sidebar__nav ul {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  margin-top: auto;
  margin-bottom: auto;
}

.admin-sidebar__item {
  list-style: none;
  position: relative;
}

.admin-sidebar__item a {
  width: 44px;
  height: 44px;
  border-radius: var(--radius-large);
  color: var(--foreground, #333);
  position: relative;
  transition: background-color var(--transition-fast, 0.15s ease);
  text-decoration: none;
  display: flex;
  align-items: center;
  justify-content: center;
  line-height: 0;
  box-sizing: border-box;
  padding: 0;
}

.admin-sidebar__item a:hover {
  background-color: var(--menu-hover, #f0f0f0);
}

/* Icon wrapper inside the link */
.admin-sidebar__item a > span:first-child {
  display: flex;
  align-items: center;
  justify-content: center;
  line-height: 0;
}

.admin-sidebar__item a > span:first-child i.bx {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 100%;
  height: 100%;
  font-size: 24px;
  line-height: 1;
  color: var(--foreground, #333);
}

.admin-sidebar__item a :deep(.dashicons) {
  width: 22px;
  height: 22px;
  font-size: 22px;
  line-height: 1;
  color: var(--foreground, #333);
}

.admin-sidebar__item a :deep(svg) {
  width: 22px;
  height: 22px;
  display: block;
}

.admin-sidebar__item a :deep(img) {
  width: 22px;
  height: 22px;
  border-radius: 4px;
  object-fit: contain;
}

/* Chevron for items with children */
.admin-sidebar__item--has-sub a::after {
  content: '';
  position: absolute;
  right: -12px;
  top: 50%;
  width: 8px;
  height: 8px;
  border-right: 1.5px solid var(--foreground, #333);
  border-top: 1.5px solid var(--foreground, #333);
  transform: translateY(-50%) rotate(45deg);
  opacity: 0.3;
}

.admin-sidebar__item--active a {
  color: var(--primary-foreground, #fff);
  background-color: var(--primary, #333);
}

.admin-sidebar__item--active a :deep(.dashicons) {
  color: var(--primary-foreground, #fff);
}

.admin-sidebar__item--active a > span:first-child i.bx {
  color: var(--primary-foreground, #fff);
}

/* === Global tooltip (position:fixed escapes overflow clipping) === */
.admin-sidebar__tooltip-global {
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

.admin-sidebar__tooltip-global[style*="left"] {
  opacity: 1;
  transform: translateY(-50%) translateX(0);
}

/* Floating submenu container */
.admin-sidebar__floating-submenus {
  position: static;
}

/* Floating submenu — positioned via JS style + position:fixed */
.admin-sidebar__sub {
  position: fixed !important;
  z-index: 10000;
  background: var(--card);
  border: 1px solid var(--border);
  border-radius: var(--radius-large);
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
  padding: 6px;
  margin: 0;
  min-width: 170px;
  list-style: none;
  display: flex;
  flex-direction: column;
  gap: 1px;
  transform: translateY(-50%) translateX(-8px);
  opacity: 0;
  pointer-events: none;
  transition: opacity 0.2s ease, transform 0.2s ease;
}

.admin-sidebar__sub--open {
  opacity: 1;
  pointer-events: auto;
  transform: translateY(-50%) translateX(0);
}

.admin-sidebar__sub li {
  list-style: none;
  display: flex;
}

.admin-sidebar__sub li a {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 9px 14px;
  border-radius: 7px;
  width: auto;
  height: auto;
  font-size: 13px;
  font-weight: 450;
  color: var(--foreground, #333);
  text-decoration: none;
  white-space: nowrap;
  transition: all 0.15s ease;
  width: 100%;
}

.admin-sidebar__sub li a:hover {
  background-color: var(--menu-hover, #f0f0f0);
}

.admin-sidebar__sub li a :deep(.dashicons) {
  width: 16px;
  height: 16px;
  font-size: 16px;
  line-height: 1;
  flex-shrink: 0;
  color: var(--secondary, #666);
  transition: color 0.15s;
}

.admin-sidebar__sub li a :deep(svg) {
  width: 16px;
  height: 16px;
  display: block;
  flex-shrink: 0;
  color: var(--secondary, #666);
  transition: color 0.15s;
}

.admin-sidebar__sub li a:hover :deep(.dashicons) {
  color: var(--foreground, #333);
}

.admin-sidebar__sub li a:hover :deep(svg) {
  color: var(--foreground, #333);
}

.admin-sidebar__sub--current a {
  background-color: var(--primary, #333) !important;
  color: var(--primary-foreground, #fff) !important;
}

.admin-sidebar__sub--current a :deep(.dashicons) {
  color: var(--primary-foreground, #fff) !important;
}

.admin-sidebar__sub--current a :deep(svg) {
  color: var(--primary-foreground, #fff) !important;
}

.admin-sidebar__spacer {
  width: 100%;
  height: 12px;
}

@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.4; }
}
</style>

<!--
  Global CSS custom properties for the admin shell (light + dark themes).
  These variables are consumed by AdminSidebar, AdminTopbar, and any
  component inside the admin shell via var(--foreground) etc.

  The theme is toggled by setting data-theme="dark" on <html>,
  done in AdminTopbar.vue's applyTheme().
-->
<style>
:root,
[data-theme="light"] {
  --foreground: #1d1d1f;
  --secondary: #86868b;
  --background: #f5f5f7;
  --card: #ffffff;
  --border: rgba(0, 0, 0, 0.06);
  --muted: #f5f5f5;
  --menu-hover: rgba(0, 0, 0, 0.05);
  --primary: #1d1d1f;
  --primary-foreground: #ffffff;
  --transition-fast: 0.15s cubic-bezier(0.4, 0, 0.2, 1);
  --shadow-large: 0 8px 30px rgb(0 0 0 / 0.08);
  --radius-full: 9999px;
}

[data-theme="dark"] {
  --foreground: #f5f5f7;
  --secondary: #98989d;
  --background: #1c1c1e;
  --card: #2c2c2e;
  --border: rgba(255, 255, 255, 0.08);
  --muted: #2c2c2e;
  --menu-hover: rgba(255, 255, 255, 0.08);
  --primary: #f5f5f7;
  --primary-foreground: #1c1c1e;
  --transition-fast: 0.15s cubic-bezier(0.4, 0, 0.2, 1);
  --shadow-large: 0 8px 30px rgb(0 0 0 / 0.5);
  --radius-full: 9999px;
}
</style>
