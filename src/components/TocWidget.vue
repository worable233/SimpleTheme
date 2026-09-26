<script setup lang="ts">
/**
 * TocWidget — 目录小部件（桌面端侧边栏卡片 + 移动端底部抽屉，
 * 移动端入口在顶栏阅读模式，见 SidebarMobileHeader）
 */
import { computed, nextTick, onMounted, onUnmounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useToc } from '@/composables/useToc'
import TocTree from './toc/TocTree.vue'
import AppIcon from '@/components/AppIcon.vue'
import ModalCloseButton from '@/components/ModalCloseButton.vue'
import type { TocNode } from './toc/TocTree.vue'

const { tocItems, activeId, drawerOpen: isOpen } = useToc()
const route = useRoute()

// Build hierarchical tree from flat items
const tocTree = computed(() => {
  const root: TocNode[] = []
  const stack: TocNode[] = []
  for (const item of tocItems.value) {
    const node: TocNode = { ...item, children: [], hasActive: false }
    while (stack.length > 0) {
      const parent = stack.at(-1)
      if (!parent || parent.level < node.level) break
      stack.pop()
    }
    const newParent = stack.at(-1)
    if (newParent) {
      newParent.children.push(node)
    } else {
      root.push(node)
    }
    stack.push(node)
  }
  return root
})

// Enrich tree: set hasActive on any node that is or contains the active heading
function markActive(nodes: TocNode[], activeId: string): boolean {
  for (const node of nodes) {
    if (node.id === activeId || markActive(node.children, activeId)) {
      node.hasActive = true
      return true
    }
  }
  return false
}

const tocData = computed(() => {
  const tree = tocTree.value
  if (activeId.value) markActive(tree, activeId.value)
  return tree
})

let observer: IntersectionObserver | null = null

function setupIntersectionObserver() {
  if (observer) observer.disconnect()

  const ids = tocItems.value.map((item) => item.id)
  const headings = ids
    .map((id) => document.getElementById(id))
    .filter(Boolean) as HTMLElement[]
  if (headings.length === 0) return

  observer = new IntersectionObserver(
    (entries) => {
      const visible = entries
        .filter((e) => e.isIntersecting)
        .sort((a, b) => a.boundingClientRect.top - b.boundingClientRect.top)

      const top = visible.at(0)
      if (top) {
        activeId.value = top.target.id
      }
    },
    {
      rootMargin: '-80px 0px -65% 0px',
      threshold: 0,
    },
  )

  headings.forEach((h) => observer?.observe(h))
}

function scrollToHeading(id: string) {
  const el = document.getElementById(id)
  if (el) {
    el.scrollIntoView({ behavior: 'smooth', block: 'start' })
    activeId.value = id
  }
  isOpen.value = false
}

// Watch activeId to auto-scroll TOC container to active item
watch(activeId, (id) => {
  if (!id) return
  nextTick(() => {
    const container = document.querySelector('.toc-content')
    if (!container) return
    const activeEl = container.querySelector('.toc-link.active')
    if (activeEl) {
      const containerRect = container.getBoundingClientRect()
      const elRect = activeEl.getBoundingClientRect()
      const offset = elRect.top - containerRect.top
      const target = container.scrollTop + offset - containerRect.height / 2 + elRect.height / 2
      container.scrollTop = target
    }
  })
})

// Watch tocItems changes to setup observer (content enhancer syncs tocItems)
watch(
  () => tocItems.value.length,
  (len) => {
    if (len > 0) nextTick(setupIntersectionObserver)
  },
)

// Cleanup on route change
watch(
  () => route.path,
  () => {
    observer?.disconnect()
    activeId.value = ''
  },
)

// 移动端目录抽屉：ESC 关闭（与统一关闭按钮的 ESC 键帽一致）
function onDrawerKeydown(e: KeyboardEvent) {
  if (e.key === 'Escape' && isOpen.value) isOpen.value = false
}

onMounted(() => {
  if (tocItems.value.length > 0) nextTick(setupIntersectionObserver)
  document.addEventListener('keydown', onDrawerKeydown)
})

onUnmounted(() => {
  observer?.disconnect()
  document.removeEventListener('keydown', onDrawerKeydown)
})
</script>

<template>
  <!-- Desktop TOC Card (sticky sidebar widget) -->
  <div
    v-if="tocData.length > 0"
    id="card-toc"
    class="toc-card"
  >
    <div class="toc-content">
      <div class="toc-card__head">
        <AppIcon class="toc-card__icon" name="list" :size="22" />
        文章目录
      </div>
      <nav>
        <TocTree
          :nodes="tocData"
          :active-id="activeId"
          @scroll-to="scrollToHeading"
        />
      </nav>
    </div>
  </div>

  <!-- Mobile TOC drawer（入口在移动端顶栏阅读模式，见 SidebarMobileHeader） -->
  <Teleport to="body">
    <Transition name="toc-drawer">
      <div
        v-if="isOpen && tocData.length > 0"
        class="toc-drawer-mask"
        @click.self="isOpen = false"
      >
        <div class="toc-drawer" @click.stop>
          <div class="toc-drawer__head">
            <span>文章目录</span>
            <ModalCloseButton @click="isOpen = false" />
          </div>
          <nav>
            <TocTree
              :nodes="tocData"
              :active-id="activeId"
              @scroll-to="scrollToHeading"
            />
          </nav>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.toc-card {
  position: sticky;
  top: 16px;
  margin-top: 16px;
  width: 100%;
  padding: 8px;
  border-radius: var(--radius-xl);
  background-color: var(--card);
}

/* 1200px 以下隐藏桌面目录卡 */
@media (max-width: 75rem) {
  .toc-card {
    display: none;
  }
}

.toc-content {
  position: relative;
  max-height: calc(100vh - 300px);
  overflow-y: auto;
  /* TOC 滚动容器：自定义细滚动条（hover 时才显示滑块） */
  scrollbar-width: thin;
  scrollbar-color: transparent transparent;
}

.toc-content::-webkit-scrollbar {
  width: 3px;
}
.toc-content::-webkit-scrollbar-thumb {
  background: transparent;
  border-radius: 3px;
}
.toc-content:hover::-webkit-scrollbar-thumb {
  background: var(--scroll);
}

.toc-card__head {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 0.2rem;
  padding: 0.6rem 12px 0.4rem;
  font-size: 18px;
  font-weight: 700;
  color: var(--foreground);
}

.toc-card__icon {
  width: 22px;
  height: 22px;
  flex-shrink: 0;
  color: var(--primary);
}

.toc-drawer-mask {
  position: fixed;
  inset: 0;
  z-index: 1000;
  display: flex;
  align-items: flex-end;
  justify-content: center;
  background-color: rgb(0 0 0 / 0.3);
}

.toc-drawer {
  width: 100%;
  max-width: 420px;
  max-height: 70vh;
  padding: 16px 1.2rem;
  overflow-y: auto;
  border-radius: var(--radius-xl) var(--radius-xl) 0 0;
  background-color: var(--card);
  box-shadow: 0 -4px 20px rgb(0 0 0 / 0.1);
}

.toc-drawer__head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  margin-bottom: 8px;
  padding-bottom: 12px;
  border-bottom: 1px solid var(--border);
  font-size: 15px;
  font-weight: 600;
  color: var(--foreground);
}

/* Drawer transition */
.toc-drawer-enter-active,
.toc-drawer-leave-active {
  transition: opacity 0.25s ease;
}

.toc-drawer-enter-active .toc-drawer,
.toc-drawer-leave-active .toc-drawer {
  transition: transform 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}

.toc-drawer-enter-from,
.toc-drawer-leave-to {
  opacity: 0;
}

.toc-drawer-enter-from .toc-drawer,
.toc-drawer-leave-to .toc-drawer {
  transform: translateY(100%);
}
</style>
