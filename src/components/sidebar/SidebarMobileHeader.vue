<script setup lang="ts">
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { useToc } from '@/composables/useToc'

const props = defineProps<{
  shellLoading: boolean
  siteName: string
  menuOpen: boolean
}>()

defineEmits<{
  'toggle-menu': []
  'open-search': []
}>()

/* ========== 阅读模式（文章页有目录时，顶栏变为章节标题 + 进度条） ========== */
const { tocItems, activeText, drawerOpen } = useToc()

const progress = ref(0)
const readingMode = computed(() => tocItems.value.length > 0 && progress.value > 0.01)
// 章节间隙（未命中任何标题）时显示上次的标题，避免闪回站点名
const displayText = ref('')
const readingTitle = computed(() => {
  if (activeText.value) displayText.value = activeText.value
  return displayText.value || '文章目录'
})

// 切换文章（目录变化）时清掉残留的上一篇章节标题
watch(tocItems, () => {
  displayText.value = ''
})

/* ========== 滚动时自动隐藏/显示（往下滑隐藏，往上滑出现） ========== */
const HEADER_H = 56
const SCROLL_DELTA = 8 // 滚动增量阈值，防抖

const lastScrollY = ref(0)
const hidden = ref(false)

function onScroll() {
  const sy = window.scrollY

  // 阅读进度（全页滚动比例）
  const max = document.documentElement.scrollHeight - window.innerHeight
  progress.value = max > 0 ? Math.min(1, sy / max) : 0

  // 菜单打开时 → 始终固定显示，不响应滚动
  if (props.menuOpen) {
    hidden.value = false
    return
  }

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

    <div class="absolute left-1/2 max-w-[55%] -translate-x-1/2">
      <Transition name="rm-fade" mode="out-in">
        <button
          v-if="readingMode"
          key="reading"
          class="flex w-full cursor-pointer items-center justify-center gap-1 border-none bg-transparent p-0 text-foreground"
          aria-label="打开文章目录"
          @click="drawerOpen = true"
        >
          <span class="overflow-hidden text-ellipsis whitespace-nowrap text-[15px] font-medium">{{ readingTitle }}</span>
          <svg class="shrink-0 text-secondary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14">
            <polyline points="6 9 12 15 18 9" />
          </svg>
        </button>
        <RouterLink
          v-else
          key="brand"
          to="/"
          class="flex items-center justify-center overflow-hidden text-ellipsis whitespace-nowrap text-foreground no-underline"
        >
          <span v-if="!shellLoading && siteName" class="text-base font-semibold">{{ siteName }}</span>
          <span v-else-if="shellLoading" role="status" class="skeleton line" style="width:80px;height:1rem;"></span>
        </RouterLink>
      </Transition>
    </div>

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

    <!-- 阅读进度条（仅阅读模式显示） -->
    <div
      v-if="readingMode"
      class="absolute bottom-0 left-0 h-[2px] bg-primary transition-[width] duration-150 ease-out"
      :style="{ width: (progress * 100).toFixed(2) + '%' }"
    ></div>
  </header>
</template>

<style scoped>
.rm-fade-enter-active,
.rm-fade-leave-active {
  transition: opacity 0.18s ease, transform 0.18s ease;
}
.rm-fade-enter-from {
  opacity: 0;
  transform: translateY(6px);
}
.rm-fade-leave-to {
  opacity: 0;
  transform: translateY(-6px);
}
</style>
