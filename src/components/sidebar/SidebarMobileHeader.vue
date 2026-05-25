<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'

const props = defineProps<{
  shellLoading: boolean
  siteName: string
  menuOpen: boolean
}>()

defineEmits<{
  'toggle-menu': []
  'open-search': []
}>()

/* ========== 滚动时自动隐藏/显示（往下滑隐藏，往上滑出现） ========== */
const HEADER_H = 56
const SCROLL_DELTA = 8 // 滚动增量阈值，防抖

const lastScrollY = ref(0)
const hidden = ref(false)

function onScroll() {
  // 菜单打开时 → 始终固定显示，不响应滚动
  if (props.menuOpen) {
    hidden.value = false
    return
  }

  const sy = window.scrollY
  const delta = sy - lastScrollY.value

  // 页面顶部 → 始终显示
  if (sy <= HEADER_H) {
    hidden.value = false
    lastScrollY.value = sy
    return
  }

  // 增量太小 → 忽略，防抖
  if (Math.abs(delta) < SCROLL_DELTA) return

  lastScrollY.value = sy
  hidden.value = delta > 0
}

onMounted(() => window.addEventListener('scroll', onScroll, { passive: true }))
onUnmounted(() => window.removeEventListener('scroll', onScroll))
</script>

<template>
  <header class="mobile-header" :class="{ 'mobile-header--hidden': hidden }">
    <button class="menu-btn" @click="$emit('toggle-menu')" aria-label="打开菜单">
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
      <span v-if="!shellLoading && siteName" class="mobile-header__site-name">{{ siteName }}</span>
      <span v-else-if="shellLoading" role="status" class="skeleton line" style="width:80px;height:1rem;"></span>
    </RouterLink>

    <button class="mobile-search-btn" @click="$emit('open-search')" aria-label="搜索">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20">
        <circle cx="11" cy="11" r="8"></circle>
        <path d="m21 21-4.35-4.35"></path>
      </svg>
    </button>
  </header>
</template>

<style scoped>
.mobile-header {
  display: none;
}

@media (max-width: 1200px) {
  .mobile-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    width: 100%;
    box-sizing: border-box;
    height: 56px;
    padding: 0 16px;
    background: var(--card);
    border-bottom: 1px solid var(--border);
    z-index: 999;
    overflow: hidden;
    transition: transform 0.3s ease;
  }

  .mobile-header--hidden {
    transform: translateY(-100%);
  }

  .mobile-header__brand {
    position: absolute;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    align-items: center;
    text-decoration: none;
    color: var(--foreground);
    max-width: 50%;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
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

  /* 中等宽度 (1001-1200px): 显示左侧面板图标，隐藏汉堡 */
  .menu-btn__icon--hamburger {
    display: none;
  }
  .menu-btn__icon--left-panel {
    display: block;
  }
}

/* 窄宽度 (≤1000px): 显示汉堡，隐藏左侧面板图标 */
@media (max-width: 1000px) {
  .menu-btn__icon--hamburger {
    display: block;
  }
  .menu-btn__icon--left-panel {
    display: none;
  }
}
</style>
