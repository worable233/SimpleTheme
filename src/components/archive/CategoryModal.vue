<script setup lang="ts">
/**
 * CategoryModal — 分类详情弹窗（含文章列表）
 *
 * 数据由父组件预先加载后通过 props 传入，弹窗直接渲染内容，无需骨架。
 * 外壳（遮罩 / 焦点陷阱 / ESC / 滚动锁定 / 右上角关闭按钮）统一由
 * src/ui 的 StModal 提供，本组件只保留内容排版。
 */
import { ref, watch } from 'vue'
import type { RenderedText } from '@/types/wordpress'
import { StModal } from '@/ui'
import { RouterLink } from 'vue-router'
import { toInternalPath } from '@/lib/theme-config'

interface PostWithMeta {
  id: number
  link: string
  title: RenderedText | { rendered: string }
  date: string
  displayDate: string
}

const props = defineProps<{
  show: boolean
  name: string
  posts: PostWithMeta[]
}>()

const emit = defineEmits<{
  (e: 'close'): void
}>()

/** StModal 受控开关；父级要求显示且分类名就绪时才展开，关闭动作统一上抛 */
const open = ref(false)
watch(
  () => props.show && !!props.name,
  (value) => {
    open.value = value
  },
  { immediate: true },
)
</script>

<template>
  <StModal
    v-model:show="open"
    :title="name || '分类'"
    size="large"
    @close="emit('close')"
  >
    <div class="modal-post-list">
      <RouterLink
        v-for="post in posts"
        :key="post.id"
        :to="toInternalPath(post.link)"
        class="modal-post-item"
      >
        <span class="modal-post-title">{{ (post.title as RenderedText).rendered }}</span>
        <span class="modal-post-date">{{ post.displayDate }}</span>
      </RouterLink>
    </div>
  </StModal>
</template>

<style scoped>
/* ===== Post List ===== */
.modal-post-list {
  display: grid;
  gap: 0.6rem;
}

.modal-post-item {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 0.8rem 1rem;
  border-radius: 0.8rem;
  background: var(--accent);
  border: 1px solid var(--border);
  text-decoration: none;
  color: inherit;
  cursor: pointer;
  transition:
    background-color var(--transition),
    border-color var(--transition),
    box-shadow var(--transition);
  animation: slideIn 0.3s var(--ease-out) both;
}

.modal-post-item:nth-child(1) {
  animation-delay: 0.06s;
}
.modal-post-item:nth-child(2) {
  animation-delay: 0.09s;
}
.modal-post-item:nth-child(3) {
  animation-delay: 0.12s;
}
.modal-post-item:nth-child(4) {
  animation-delay: 0.15s;
}
.modal-post-item:nth-child(5) {
  animation-delay: 0.18s;
}

.modal-post-item:hover {
  background: var(--muted);
  border-color: var(--primary);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
}

.modal-post-title {
  flex: 1;
  font-weight: 500;
  font-size: 0.95rem;
  color: var(--foreground);
  line-height: 1.5;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.modal-post-date {
  font-size: 0.8rem;
  color: var(--secondary);
  background: var(--accent);
  padding: 0.25rem 0.6rem;
  border-radius: 0.5rem;
  flex-shrink: 0;
}

/* ===== Slide In ===== */
@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateY(8px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>
