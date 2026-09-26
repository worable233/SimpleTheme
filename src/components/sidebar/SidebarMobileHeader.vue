<script setup lang="ts">
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { StButton } from '@/ui'
import AppIcon from '@/components/AppIcon.vue'
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
const readingTitle = computed(() => displayText.value || '文章目录')

watch(activeText, (text) => {
  if (text) displayText.value = text
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
    class="mobile-header"
    :class="{ 'is-hidden': hidden }"
  >
    <StButton quaternary circle size="large" aria-label="打开菜单" @click="$emit('toggle-menu')">
      <template #icon>
        <!-- ≤1000px: 汉堡菜单（两边都收起） -->
        <AppIcon class="mobile-header__icon-hamburger" name="menu-2" :size="24" />
        <!-- 1001-1200px: 左侧面板图标（只收起左侧） -->
        <AppIcon class="mobile-header__icon-panel" name="layout-sidebar" :size="24" />
      </template>
    </StButton>

    <div class="mobile-header__center">
      <!-- 保留原生 <button>：阅读模式标题需借助 Transition 在「阅读标题/站点名」间切换，
           且为居中布局而非单一内容按钮。 -->
      <Transition name="rm-fade" mode="out-in">
        <button
          v-if="readingMode"
          key="reading"
          class="mobile-header__reading"
          aria-label="打开文章目录"
          @click="drawerOpen = true"
        >
          <span class="mobile-header__reading-title">{{ readingTitle }}</span>
          <AppIcon class="mobile-header__caret" name="chevron-down" :size="14" />
        </button>
        <RouterLink
          v-else
          key="brand"
          to="/"
          class="mobile-header__brand"
        >
          <span v-if="!shellLoading && siteName" class="mobile-header__brand-name">{{ siteName }}</span>
          <span v-else-if="shellLoading" role="status" class="skeleton line" style="width:80px;height:1rem;"></span>
        </RouterLink>
      </Transition>
    </div>

    <StButton quaternary circle size="large" aria-label="搜索" @click="$emit('open-search')">
      <template #icon><AppIcon name="search" :size="20" /></template>
    </StButton>

    <!-- 阅读进度条（仅阅读模式显示） -->
    <div
      v-if="readingMode"
      class="mobile-header__progress"
      :style="{ width: (progress * 100).toFixed(2) + '%' }"
    ></div>
  </header>
</template>

<style scoped>
/* 1200px 以上隐藏移动顶栏 */
.mobile-header {
  display: none;
}

@media (max-width: 75rem) {
  .mobile-header {
    position: fixed;
    inset-inline: 0;
    top: 0;
    z-index: 999;
    display: flex;
    align-items: center;
    justify-content: space-between;
    height: 56px;
    width: 100%;
    padding: 0 16px;
    overflow: hidden;
    border-bottom: 1px solid var(--border);
    background-color: var(--card);
    transition: transform 0.3s;
  }

  .mobile-header.is-hidden {
    transform: translateY(-100%);
  }
}

/* ≤1000px 显示汉堡，1001-1200px 显示面板图标 */
.mobile-header__icon-hamburger {
  display: none;
}

@media (max-width: 62.5rem) {
  .mobile-header__icon-hamburger {
    display: block;
  }

  .mobile-header__icon-panel {
    display: none;
  }
}

.mobile-header__center {
  position: absolute;
  left: 50%;
  max-width: 55%;
  transform: translateX(-50%);
}

.mobile-header__reading {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 4px;
  width: 100%;
  padding: 0;
  border: none;
  background: transparent;
  color: var(--foreground);
  cursor: pointer;
}

.mobile-header__reading-title {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  font-size: 15px;
  font-weight: 500;
}

.mobile-header__caret {
  flex-shrink: 0;
  color: var(--secondary);
}

.mobile-header__brand {
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  color: var(--foreground);
  text-decoration: none;
}

.mobile-header__brand-name {
  font-size: 16px;
  font-weight: 600;
}

.mobile-header__progress {
  position: absolute;
  bottom: 0;
  left: 0;
  height: 2px;
  background-color: var(--primary);
  transition: width 0.15s ease-out;
}

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
