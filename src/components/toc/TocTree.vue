<script setup lang="ts">
/**
 * TocTree — 递归 TOC 树渲染组件
 *
 * 视觉：Apple Music 歌词式聚焦——
 *   离当前激活项越远，模糊越强、越淡；激活项放大一号并加粗。
 *   距离由「在扁平目录里的序号差」决定（见 activeIndex / node.order），
 *   各项的 font-size / filter / opacity / font-weight 全部走 CSS 变量 +
 *   transition 输出，因此切换章节时是连贯的渐变而非跳变。
 */
import { type TocItem } from '@/composables/useToc'

export interface TocNode extends TocItem {
  children: TocNode[]
  hasActive: boolean
  /** 在扁平目录列表中的序号（与 tocItems 的顺序一致） */
  order: number
}

defineOptions({ name: 'TocTree' })

const props = withDefaults(
  defineProps<{
    nodes: TocNode[]
    activeId: string
    /** 激活项在扁平目录里的序号；-1 表示尚未选中任何章节 */
    activeIndex?: number
  }>(),
  { activeIndex: -1 },
)

const emit = defineEmits<{
  (e: 'scroll-to', id: string): void
}>()

function scrollTo(id: string) {
  emit('scroll-to', id)
}

/** 各级标题的基础字号（px），激活时再 +1。 */
const BASE_SIZE: Record<number, number> = { 2: 13, 3: 12.5, 4: 12 }
/** 每远离一档增加的模糊量（px）与上限。 */
const BLUR_PER_STEP = 0.6
const BLUR_MAX = 3
/** 每远离一档的透明度衰减与下限。 */
const OPACITY_PER_STEP = 0.13
const OPACITY_MIN = 0.45

/**
 * 由「与激活项的距离」算出该链接的样式变量。
 * 用 CSS 变量而非直接写 filter/opacity，好处是 hover 仍可用样式表规则覆盖。
 */
function linkStyle(node: TocNode) {
  const hasActive = props.activeIndex >= 0
  const distance = hasActive ? Math.abs(node.order - props.activeIndex) : 0
  const isActive = hasActive && distance === 0

  const base = BASE_SIZE[node.level] ?? 13
  return {
    '--toc-size': `${isActive ? base + 1 : base}px`,
    '--toc-blur': `${isActive ? 0 : Math.min(distance * BLUR_PER_STEP, BLUR_MAX)}px`,
    '--toc-opacity': isActive
      ? '1'
      : String(Math.max(1 - distance * OPACITY_PER_STEP, OPACITY_MIN)),
    '--toc-weight': isActive ? '700' : node.hasActive ? '500' : '400',
  }
}
</script>

<template>
  <template v-for="node in nodes" :key="node.id">
    <div
      class="toc-item"
      :class="{ active: activeId === node.id, 'has-active': node.hasActive }"
    >
      <a
        class="toc-link"
        :class="{ active: activeId === node.id }"
        :style="linkStyle(node)"
        :href="'#' + node.id"
        @click.prevent="scrollTo(node.id)"
      >{{ node.text }}</a>
      <ol v-if="node.children.length > 0" class="toc-child">
        <li
          v-for="child in node.children"
          :key="child.id"
          class="toc-item"
          :class="{ active: activeId === child.id, 'has-active': child.hasActive }"
        >
          <a
            class="toc-link"
            :class="{ active: activeId === child.id }"
            :style="linkStyle(child)"
            :href="'#' + child.id"
            @click.prevent="scrollTo(child.id)"
          >{{ child.text }}</a>
          <ol v-if="child.children.length > 0" class="toc-child">
            <li
              v-for="grandchild in child.children"
              :key="grandchild.id"
              class="toc-item"
              :class="{ active: activeId === grandchild.id, 'has-active': grandchild.hasActive }"
            >
              <a
                class="toc-link"
                :class="{ active: activeId === grandchild.id }"
                :style="linkStyle(grandchild)"
                :href="'#' + grandchild.id"
                @click.prevent="scrollTo(grandchild.id)"
              >{{ grandchild.text }}</a>
            </li>
          </ol>
        </li>
      </ol>
    </div>
  </template>
</template>

<style scoped>
/* ==================== TOC List Reset ==================== */
nav > ol,
ol {
  margin: 0;
  padding: 0;
  padding-left: 0.25rem;
  list-style: none;
}

nav > ol {
  padding-left: 0 !important;
}

li,
ol li {
  list-style: none;
}

/* ==================== TOC Item ==================== */
.toc-item {
  position: relative;
  margin: 1px 0;
}

.toc-child {
  display: none;
  list-style: none;
  margin: 0;
  padding: 0;
  padding-left: 0.25rem;
}

.toc-item.active > .toc-child,
.toc-item.has-active > .toc-child {
  display: block;
}

/* ==================== TOC Link ==================== */
/*
 * 字号 / 模糊 / 透明度 / 字重全部由内联 CSS 变量驱动（见 linkStyle），
 * transition 作用在最终属性上，因此变量变化时是平滑过渡。
 */
.toc-link {
  display: flex;
  align-items: center;
  min-height: 40px;
  padding: 8px;
  border-left: 0 solid transparent;
  border-radius: 12px;
  color: var(--secondary);
  font-size: var(--toc-size, 13px);
  font-weight: var(--toc-weight, 400);
  line-height: 24px;
  opacity: var(--toc-opacity, 1);
  filter: blur(var(--toc-blur, 0px));
  text-decoration: none;
  cursor: default;
  transition:
    filter 0.5s var(--ease-out),
    opacity 0.5s var(--ease-out),
    font-size 0.5s var(--ease-out),
    font-weight 0.5s var(--ease-out),
    color 0.25s var(--ease-out),
    background-color 0.25s var(--ease-out);
  word-break: break-word;
  will-change: filter, opacity;
}

/* Non-active: 可点击 */
.toc-link:not(.active) {
  cursor: pointer;
}

/* Tree hover: 悬停时该行聚焦清晰 */
.toc-item:hover > .toc-link:not(.active) {
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

/* Active heading：放大与加粗交给内联变量，这里只负责着色与底衬 */
.toc-link.active {
  border-radius: 8px;
  background: var(--accent);
  color: var(--primary);
}

.toc-item.has-active > .toc-link {
  color: var(--foreground);
}

/* Nested list indentation（字号由变量决定，这里只管缩进） */
.toc-child .toc-link {
  padding-left: 1rem;
}
.toc-child .toc-child .toc-link {
  padding-left: 1.6rem;
}

@media (prefers-reduced-motion: reduce) {
  .toc-link {
    transition: none;
  }
}
</style>
