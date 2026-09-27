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
import { StDrawer } from '@/ui'
import type { TocNode } from './toc/TocTree.vue'

const { tocItems, activeId, drawerOpen: isOpen } = useToc()
const route = useRoute()

// Build hierarchical tree from flat items
const tocTree = computed(() => {
  const root: TocNode[] = []
  const stack: TocNode[] = []
  for (const [order, item] of tocItems.value.entries()) {
    const node: TocNode = { ...item, children: [], hasActive: false, order }
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

/** 激活项在扁平目录里的序号（供 Apple Music 式距离模糊计算）。 */
const activeIndex = computed(() => tocItems.value.findIndex((item) => item.id === activeId.value))

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

// 移动端目录抽屉的 ESC / 遮罩关闭 / 滚动锁定由 StDrawer（reka Dialog）负责
onMounted(() => {
  if (tocItems.value.length > 0) nextTick(setupIntersectionObserver)
})

onUnmounted(() => {
  observer?.disconnect()
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
          :active-index="activeIndex"
          @scroll-to="scrollToHeading"
        />
      </nav>
    </div>
  </div>

  <!-- Mobile TOC drawer（入口在移动端顶栏阅读模式，见 SidebarMobileHeader）
       外壳（遮罩 / 焦点陷阱 / ESC / 滚动锁定 / 关闭按钮）由 StDrawer 提供 -->
  <StDrawer v-model:show="isOpen" placement="bottom" title="文章目录">
    <TocTree
      :nodes="tocData"
      :active-id="activeId"
      :active-index="activeIndex"
      @scroll-to="scrollToHeading"
    />
  </StDrawer>
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
</style>
