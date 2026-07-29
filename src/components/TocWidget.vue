<script setup lang="ts">
/**
 * TocWidget — 目录小部件（桌面端侧边栏 + 移动端浮动按钮/抽屉）
 */
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useToc } from '@/composables/useToc'
import TocTree from './toc/TocTree.vue'
import ModalCloseButton from '@/components/ModalCloseButton.vue'
import type { TocNode } from './toc/TocTree.vue'

const { tocItems, activeId } = useToc()
const route = useRoute()

const isOpen = ref(false)

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
    class="sticky top-4 mt-4 w-full rounded-xl bg-card p-2 max-xl:hidden"
  >
    <div class="toc-content relative max-h-[calc(100vh-300px)] overflow-y-auto">
      <div class="mb-[0.2rem] flex items-center gap-2 px-3 pt-[0.6rem] pb-[0.4rem] text-lg font-bold text-foreground">
        <svg class="h-[22px] w-[22px] shrink-0 text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <line x1="8" y1="6" x2="21" y2="6" />
          <line x1="8" y1="12" x2="21" y2="12" />
          <line x1="8" y1="18" x2="21" y2="18" />
          <line x1="3" y1="6" x2="3.01" y2="6" />
          <line x1="3" y1="12" x2="3.01" y2="12" />
          <line x1="3" y1="18" x2="3.01" y2="18" />
        </svg>
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

  <!-- Mobile floating TOC trigger button (teleported to body to escape right-sidebar hiding) -->
  <Teleport to="body">
    <button
      v-if="tocData.length > 0"
      class="fixed right-5 bottom-[30px] z-[600] block h-11 w-11 cursor-pointer rounded-full border border-border bg-card p-2.5 text-foreground shadow-[0_4px_16px_rgba(0,0,0,0.12)] transition-[transform,box-shadow] duration-300 max-sm:bottom-20 hover:scale-105 hover:shadow-[0_6px_24px_rgba(0,0,0,0.18)]"
      @click="isOpen = !isOpen"
      aria-label="目录"
    >
      <i class="bx bx-list-ul flex h-full w-full items-center justify-center text-xl"></i>
    </button>
  </Teleport>

  <!-- Mobile TOC drawer -->
  <Teleport to="body">
    <Transition name="toc-drawer">
      <div
        v-if="isOpen && tocData.length > 0"
        class="fixed inset-0 z-[1000] flex items-end justify-center bg-black/30"
        @click.self="isOpen = false"
      >
        <div
          class="toc-drawer max-h-[70vh] w-full max-w-[420px] overflow-y-auto rounded-t-2xl bg-card px-[1.2rem] py-4 shadow-[0_-4px_20px_rgba(0,0,0,0.1)]"
          @click.stop
        >
          <div class="mb-2 flex items-center justify-between gap-2 border-b border-border pb-3 text-[15px] font-semibold text-foreground">
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
/* TOC 滚动容器：自定义细滚动条（hover 时才显示滑块） */
.toc-content {
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
