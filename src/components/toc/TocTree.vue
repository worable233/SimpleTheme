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
.toc-item {
  position: relative;
  margin: 1px 0;
}

.toc-link {
  display: block;
  padding: 5px 10px;
  font-size: 13px;
  color: var(--secondary);
  text-decoration: none;
  border-radius: 6px;
  line-height: 1.5;
  transition: all 0.15s;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.toc-link:hover {
  color: var(--foreground);
  background: var(--muted);
}

.toc-link.active {
  color: var(--primary-foreground);
  background: var(--primary);
  font-weight: 500;
}

.toc-item.has-active > .toc-link {
  color: var(--foreground);
  font-weight: 500;
}

/* Nested list indentation */
.toc-child {
  list-style: none;
  margin: 0;
  padding: 0;
  padding-left: 12px;
}

.toc-child .toc-item {
  margin: 1px 0;
}

.toc-child .toc-link {
  padding-left: 10px;
  font-size: 12.5px;
}
</style>
