<script setup lang="ts">
/**
 * TocTree — 递归 TOC 树渲染组件
 */
import { type TocItem } from '@/composables/useToc'

export interface TocNode extends TocItem {
  children: TocNode[]
  hasActive: boolean
}

defineOptions({ name: 'TocTree' })

const props = defineProps<{
  nodes: TocNode[]
  activeId: string
}>()

const emit = defineEmits<{
  (e: 'scroll-to', id: string): void
}>()

function scrollTo(id: string) {
  emit('scroll-to', id)
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

/* Tree hover: unblur all non-active links */
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

.toc-item.has-active > .toc-link {
  color: var(--foreground);
  font-weight: 500;
}

/* Nested list indentation */
.toc-child .toc-link {
  padding-left: 1rem;
  font-size: 12.5px;
}
.toc-child .toc-child .toc-link {
  padding-left: 1.6rem;
  font-size: 12px;
}
</style>
