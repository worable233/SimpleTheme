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
  <header
    class="fixed inset-x-0 top-0 z-[999] hidden h-14 w-full items-center justify-between overflow-hidden border-b border-border bg-card px-4 transition-transform duration-300 max-xl:flex"
    :class="{ '-translate-y-full': hidden }"
  >
    <button
      class="flex h-10 w-10 cursor-pointer items-center justify-center rounded-md border-none bg-transparent text-foreground hover:bg-menu-hover"
      @click="$emit('toggle-menu')"
      aria-label="打开菜单"
    >
      <!-- ≤1000px: 汉堡菜单（两边都收起） -->
      <svg class="hidden max-lg:block" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="24" height="24">
        <line x1="3" y1="6" x2="21" y2="6" />
        <line x1="3" y1="12" x2="21" y2="12" />
        <line x1="3" y1="18" x2="21" y2="18" />
      </svg>
      <!-- 1001-1200px: 左侧面板图标（只收起左侧） -->
      <svg class="block max-lg:hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="24" height="24">
        <rect x="3" y="3" width="18" height="18" rx="2" />
        <line x1="9" y1="3" x2="9" y2="21" />
        <line x1="3" y1="9" x2="9" y2="9" />
        <line x1="3" y1="15" x2="9" y2="15" />
      </svg>
    </button>

    <RouterLink
      to="/"
      class="absolute left-1/2 flex max-w-[50%] -translate-x-1/2 items-center overflow-hidden text-ellipsis whitespace-nowrap text-foreground no-underline"
    >
      <span v-if="!shellLoading && siteName" class="text-base font-semibold">{{ siteName }}</span>
      <span v-else-if="shellLoading" role="status" class="skeleton line" style="width:80px;height:1rem;"></span>
    </RouterLink>

    <button
      class="flex h-10 w-10 cursor-pointer items-center justify-center rounded-md border-none bg-transparent text-foreground hover:bg-menu-hover"
      @click="$emit('open-search')"
      aria-label="搜索"
    >
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20">
        <circle cx="11" cy="11" r="8"></circle>
        <path d="m21 21-4.35-4.35"></path>
      </svg>
    </button>
  </header>
</template>
