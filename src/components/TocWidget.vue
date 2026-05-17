<script setup lang="ts">
import { computed, nextTick, onMounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useToc, type TocItem } from '@/composables/useToc'

interface TocNode extends TocItem {
  children: TocNode[]
  hasActive: boolean
}

const { tocItems, activeId, clearToc } = useToc()
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

onMounted(() => {
  if (tocItems.value.length > 0) nextTick(setupIntersectionObserver)
})
</script>

<template>
  <!-- Desktop TOC Card (sticky sidebar widget) -->
  <div v-if="tocData.length > 0" class="card-widget" id="card-toc">
    <div class="toc-content">
      <div class="toc-header">
        <svg class="toc-header-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
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
        <template v-for="node in tocData" :key="node.id">
          <div class="toc-item" :class="{ active: activeId === node.id, 'has-active': node.hasActive }">
            <a
              class="toc-link"
              :class="{ active: activeId === node.id }"
              :href="'#' + node.id"
              @click.prevent="scrollToHeading(node.id)"
            >{{ node.text }}</a>
            <ol v-if="node.children.length > 0" class="toc-child">
              <li v-for="child in node.children" :key="child.id" class="toc-item" :class="{ active: activeId === child.id, 'has-active': child.hasActive }">
                <a
                  class="toc-link"
                  :class="{ active: activeId === child.id }"
                  :href="'#' + child.id"
                  @click.prevent="scrollToHeading(child.id)"
                >{{ child.text }}</a>
                <ol v-if="child.children.length > 0" class="toc-child">
                  <li v-for="grandchild in child.children" :key="grandchild.id" class="toc-item" :class="{ active: activeId === grandchild.id, 'has-active': grandchild.hasActive }">
                    <a
                      class="toc-link"
                      :class="{ active: activeId === grandchild.id }"
                      :href="'#' + grandchild.id"
                      @click.prevent="scrollToHeading(grandchild.id)"
                    >{{ grandchild.text }}</a>
                  </li>
                </ol>
              </li>
            </ol>
          </div>
        </template>
      </nav>
    </div>
  </div>

  <!-- Mobile floating TOC trigger button -->
  <button
    v-if="tocData.length > 0"
    class="toc-floating-btn"
    :class="{ open: isOpen }"
    @click="isOpen = !isOpen"
    aria-label="目录"
  >
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
      <line x1="8" y1="6" x2="21" y2="6" />
      <line x1="8" y1="12" x2="21" y2="12" />
      <line x1="8" y1="18" x2="21" y2="18" />
      <line x1="3" y1="6" x2="3.01" y2="6" />
      <line x1="3" y1="12" x2="3.01" y2="12" />
      <line x1="3" y1="18" x2="3.01" y2="18" />
    </svg>
  </button>

  <!-- Mobile TOC drawer -->
  <Teleport to="body">
    <Transition name="toc-drawer">
      <div v-if="isOpen && tocData.length > 0" class="toc-drawer-overlay" @click.self="isOpen = false">
        <div class="toc-drawer" @click.stop>
          <div class="toc-drawer__header">
            <span>文章目录</span>
            <button class="toc-drawer__close" @click="isOpen = false">&times;</button>
          </div>
          <nav>
            <template v-for="node in tocData" :key="node.id">
              <div class="toc-item" :class="{ active: activeId === node.id, 'has-active': node.hasActive }">
                <a
                  class="toc-link" :class="{ active: activeId === node.id }"
                  :href="'#' + node.id"
                  @click.prevent="scrollToHeading(node.id)"
                >{{ node.text }}</a>
                <ol v-if="node.children.length > 0" class="toc-child">
                  <li v-for="child in node.children" :key="child.id" class="toc-item" :class="{ active: activeId === child.id, 'has-active': child.hasActive }">
                    <a
                      class="toc-link" :class="{ active: activeId === child.id }"
                      :href="'#' + child.id"
                      @click.prevent="scrollToHeading(child.id)"
                    >{{ child.text }}</a>
                  </li>
                </ol>
              </div>
            </template>
          </nav>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
/* ==================== Card Widget (sticky sidebar) ==================== */
.card-widget {
  position: sticky;
  top: 16px;
  width: 100%;
  margin-top: 1rem;
  padding: 0.5rem;
  border-radius: 12px;
  background: var(--card);
}

/* ==================== TOC Content Container ==================== */
.toc-content {
  position: relative;
  overflow-y: auto;
  max-height: calc(100vh - 300px);
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

/* ==================== Header ==================== */
.toc-header {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 18px;
  font-weight: 700;
  color: var(--foreground);
  padding: 0.6rem 0.75rem 0.4rem;
  margin-bottom: 0.2rem;
}

.toc-header-icon {
  width: 22px;
  height: 22px;
  flex-shrink: 0;
  color: var(--primary);
}

/* ==================== TOC Navigation ==================== */
.toc-content nav > ol,
.toc-content ol {
  margin: 0;
  padding: 0;
  padding-left: 0.25rem;
  list-style: none;
}

.toc-content li,
.toc-content ol li {
  list-style: none;
}

.toc-content > ol {
  padding-left: 0 !important;
}

/* ==================== TOC Item ==================== */
.toc-item {
  position: relative;
}

.toc-content .toc-child {
  display: none;
}

.toc-content .toc-item.active .toc-child,
.toc-content .toc-item.has-active .toc-child {
  display: block;
}

/* ==================== TOC Link ==================== */
.toc-link {
  display: flex;
  align-items: center;
  min-height: 40px;
  padding: 8px;
  border-left: 0 solid transparent;
  border-radius: 12px;
  color: var(--secondary);
  font-size: 13px;
  line-height: 24px;
  text-decoration: none;
  cursor: default;
  transition: all 0.3s ease;
  word-break: break-word;
}

/* Non-active: blurred with low opacity */
.toc-link:not(.active) {
  opacity: 0.6;
  cursor: pointer;
  filter: blur(1px);
  transition: 0.3s;
}

/* Card hover: unblur all non-active links */
.card-widget:hover .toc-link:not(.active) {
  filter: blur(0);
  opacity: 1;
}

/* Link hover: highlight bg */
.toc-link:hover {
  background: var(--accent);
}
.toc-link:hover:not(.active) {
  color: var(--foreground);
}

/* Active heading */
.toc-link.active {
  opacity: 1;
  filter: blur(0);
  font-weight: 700;
  font-size: 14px;
  border-radius: 8px;
  background: var(--accent);
  color: var(--primary);
}

/* Child level indentation */
.toc-child .toc-link {
  padding-left: 1rem;
  font-size: 12.5px;
}
.toc-child .toc-child .toc-link {
  padding-left: 1.6rem;
  font-size: 12px;
}

/* ==================== Mobile Floating Button ==================== */
.toc-floating-btn {
  display: none;
  position: fixed;
  right: 20px;
  bottom: 30px;
  z-index: 100;
  width: 44px;
  height: 44px;
  border-radius: 50%;
  border: 1px solid var(--border);
  background: var(--card);
  box-shadow: 0 4px 16px rgba(0 0 0 / 0.12);
  cursor: pointer;
  color: var(--foreground);
  padding: 10px;
  transition: transform 0.3s, box-shadow 0.3s;
}

.toc-floating-btn:hover {
  transform: scale(1.05);
  box-shadow: 0 6px 24px rgba(0 0 0 / 0.18);
}

.toc-floating-btn svg {
  width: 100%;
  height: 100%;
}


/* ==================== Mobile Drawer (Teleported) ==================== */
.toc-drawer-overlay {
  position: fixed;
  inset: 0;
  z-index: 1000;
  background: rgba(0 0 0 / 0.3);
  display: flex;
  align-items: flex-end;
  justify-content: center;
}

.toc-drawer {
  width: 100%;
  max-width: 420px;
  max-height: 70vh;
  background: var(--card);
  border-radius: 16px 16px 0 0;
  padding: 1rem 1.2rem;
  overflow-y: auto;
  box-shadow: 0 -4px 20px rgba(0 0 0 / 0.1);
}

.toc-drawer__header {
  display: flex;
  align-items: center;
  gap: 8px;
  padding-bottom: 0.75rem;
  margin-bottom: 0.5rem;
  border-bottom: 1px solid var(--border);
  font-size: 15px;
  font-weight: 600;
  color: var(--foreground);
}

.toc-drawer__close {
  background: none;
  border: none;
  font-size: 22px;
  color: var(--secondary);
  cursor: pointer;
  padding: 0 4px;
  line-height: 1;
}

.toc-drawer__close:hover {
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

/* ==================== Responsive ==================== */
/* When sidebar hides, show floating button */
@media screen and (max-width: 1200px) {
  .card-widget {
    display: none;
  }
  .toc-floating-btn {
    display: block;
  }
}

</style>
