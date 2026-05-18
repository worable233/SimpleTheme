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
</template>

<style scoped>
/* ========== Sub-menu slide animation ========== */
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

/* ========== Narrow left-drawer nav (mobile) ========== */
@media (max-width: 1200px) {
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
}
</style>

<!-- ==================== NON-SCOPED: Menu tooltip + sub-menu ==================== -->
<style>
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

/* Sub-menu (二级菜单) */

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
</style>
