<script setup lang="ts">
import { RouterLink, useRoute } from 'vue-router'
import { isExternalUrl } from '@/lib/theme-config'
import { getItemIcon } from './icon-map'
import type { MenuItem } from '@/types/wordpress'

defineProps<{
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
</script>

<template>
  <nav class="left-sidebar__menu">
    <ul>
      <li
        v-for="item in menuItems"
        :key="item.id"
        :data-menu-id="item.id"
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
            @click="emit('toggle-sub-menu', item.id)"
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
          </Transition>
        </template>

        <!-- 无子菜单项：普通链接 -->
        <RouterLink
          v-else-if="!isExternalUrl(item.url) && !isHome(item.url)"
          :to="item.path || item.url"
          :aria-current="isCurrent(item.path) ? 'page' : undefined"
        >
          <span v-html="getItemIcon(item, isCurrent(item.path))"></span>
          <span class="menu-item-title">{{ item.title }}</span>
        </RouterLink>
        <RouterLink
          v-else-if="!isExternalUrl(item.url) && isHome(item.url)"
          to="/"
          :aria-current="isCurrent('/') ? 'page' : undefined"
        >
          <span v-html="getItemIcon(item, isCurrent('/'))"></span>
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
</template>

<style scoped>
/* ========== Sub-menu panel animation ========== */
.sub-menu-slide-enter-active,
.sub-menu-slide-leave-active {
  transition: all 0.2s ease;
}
.sub-menu-slide-enter-from,
.sub-menu-slide-leave-to {
  opacity: 0;
  transform: translateX(-8px);
}

/* ========== Narrow left-drawer nav (mobile) ========== */
@media (max-width: 1200px) {
  .left-sidebar__menu {
    flex: 1;
    min-height: 0;
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

  .left-sidebar__menu ul li a i.bx {
    font-size: 24px;
    line-height: 1;
  }

  .left-sidebar__menu ul li {
    position: relative;
    padding-bottom: 14px;
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
}
</style>

<!-- ==================== NON-SCOPED: Menu tooltip + sub-menu ==================== -->
<style>
.left-sidebar__menu ul li a,
.left-sidebar__menu ul li button.menu-toggle {
  position: relative;
}

/* 顶部菜单链接：居中图标（桌面端 + 移动端统一） */
.left-sidebar__menu ul li a {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 50px;
  height: 50px;
  border-radius: 8px;
  line-height: 0;
}

/* 图标包装 span：居中内部图标（桌面端 + 移动端） */
.left-sidebar__menu ul li a > span:first-child {
  display: flex;
  align-items: center;
  justify-content: center;
  line-height: 0;
}

/* 图标元素自居：让 ::before 伪元素(实际渲染的图标)在盒内垂直居中 */
.left-sidebar__menu ul li a > span:first-child i.bx {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 100%;
  height: 100%;
}

/* 菜单图标统一尺寸 — 桌面版 24px, 子菜单 16px 由各自选择器覆写 */
.left-sidebar__menu ul li a i.bx {
  font-size: 24px;
  line-height: 1;
  color: var(--foreground);
}

/* 顶部菜单选中态：高亮背景 + 前景色 */
.left-sidebar__menu ul li.current-menu-item > a {
  background-color: var(--primary);
  border-radius: 8px;
  color: var(--primary-foreground);
}

.left-sidebar__menu ul li.current-menu-item > a i.bx {
  color: var(--primary-foreground);
}

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

.left-sidebar__menu ul li button.menu-toggle i.bx {
  font-size: 24px;
  line-height: 1;
  color: var(--foreground);
}

/* Menu items that have children — positioned for sub-menu panel */
.left-sidebar__menu ul li.menu-item-has-children {
  position: relative;
}

/* Chevron indicator */
.sub-menu-chevron {
  position: absolute;
  right: 6px;
  top: 50%;
  transform: translateY(-50%);
  width: 10px !important;
  height: 10px !important;
  opacity: 0.4;
  transition: transform 0.25s ease;
}

.menu-item-open .sub-menu-chevron {
  transform: translateY(-50%) rotate(180deg);
}

/* Desktop: floating sub-menu panel to the right */
.sub-menu {
  position: absolute;
  left: calc(100% + 14px);
  top: 50%;
  transform: translateY(-50%);
  min-width: 170px;
  padding: 6px;
  margin: 0;
  list-style: none;
  display: flex;
  flex-direction: column;
  gap: 1px;
  border-radius: 10px;
  background: var(--card);
  border: 1px solid var(--border);
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
  z-index: 100;
}

.sub-menu li {
  display: flex;
}

.sub-menu li a {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 9px 14px;
  border-radius: 7px;
  color: var(--foreground);
  text-decoration: none;
  white-space: nowrap;
  font-size: 13px;
  font-weight: 450;
  transition: all 0.15s ease;
  width: 100%;
}

.sub-menu li a:hover {
  background-color: var(--menu-hover);
}

.sub-menu li a svg {
  width: 16px;
  height: 16px;
  flex-shrink: 0;
  color: var(--secondary);
  transition: color 0.15s;
}

.sub-menu li a i.bx {
  font-size: 16px;
  line-height: 1;
  flex-shrink: 0;
  color: var(--secondary);
  transition: color 0.15s;
}

.sub-menu li a:hover svg {
  color: var(--foreground);
}

.sub-menu li a:hover i.bx {
  color: var(--foreground);
}

/* Sub-menu items: show icon + title inline, not as tooltip */
.sub-menu li a .menu-item-title {
  position: static;
  opacity: 1;
  visibility: visible;
  transform: none;
  background: none;
  -webkit-backdrop-filter: none;
  backdrop-filter: none;
  pointer-events: auto;
  padding: 0;
  color: inherit;
  font-size: 13px;
  font-weight: 450;
  white-space: nowrap;
  display: inline;
}

.sub-menu li.current-menu-item a {
  background-color: var(--primary);
  color: var(--primary-foreground);
}

.sub-menu li.current-menu-item a svg {
  color: var(--primary-foreground);
}

.sub-menu li.current-menu-item a i.bx {
  color: var(--primary-foreground);
}

/* Hide inline sub-menu — LeftSidebar renders floating panels on all screen sizes */
.left-sidebar__menu .sub-menu {
  display: none !important;
}

</style>
