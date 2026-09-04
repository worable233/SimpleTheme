<script setup lang="ts">
/**
 * CategoryModal — 分类详情弹窗（含文章列表）
 *
 * 数据由父组件预先加载后通过 props 传入，弹窗直接渲染内容，无需骨架。
 */
import type { RenderedText } from '@/types/wordpress'
import ModalCloseButton from '@/components/ModalCloseButton.vue'
import { RouterLink } from 'vue-router'
import { toInternalPath } from '@/lib/theme-config'

interface PostWithMeta {
  id: number
  link: string
  title: RenderedText | { rendered: string }
  date: string
  displayDate: string
}

defineProps<{
  show: boolean
  name: string
  posts: PostWithMeta[]
}>()

const emit = defineEmits<{
  (e: 'close'): void
}>()

function onMaskClick(e: MouseEvent) {
  if ((e.target as HTMLElement).classList.contains('modal-mask')) {
    emit('close')
  }
}
</script>

<template>
  <Teleport to="body">
    <Transition name="modal">
      <div
        v-if="show && name"
        class="modal-mask"
        @click="onMaskClick"
      >
        <div
          class="timeline-modal"
          role="dialog"
          aria-modal="true"
          aria-labelledby="category-modal-title"
          @click.stop
        >
          <ModalCloseButton class="modal-close" autofocus @click="emit('close')" />
          <div class="modal-content">
            <div class="modal-content-inner">
              <div class="category-modal-header">
                <h2 id="category-modal-title" class="modal-title" style="margin:0">{{ name }}</h2>
              </div>
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
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
/* ===== Modal Shared ===== */
.modal-mask {
  position: fixed;
  inset: 0;
  z-index: 9999;
  background: rgba(0,0,0,0.4);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1rem;
  backdrop-filter: blur(4px);
}

.timeline-modal {
  background: var(--card, rgba(255,255,255,0.98));
  border-radius: var(--radius-large, 8px);
  max-width: 640px;
  width: 100%;
  max-height: 85vh;
  overflow: hidden;
  padding: 2rem;
  box-shadow: 0 8px 32px rgba(0,0,0,0.15);
  border: 1px solid var(--border, rgba(0,0,0,0.08));
  backdrop-filter: blur(20px);
  -webkit-backdrop-filter: blur(20px);
  position: relative;
}

.timeline-modal .modal-content {
  max-height: calc(85vh - 4rem);
  overflow-y: auto;
  overflow-x: hidden;
  padding-right: 6px;
  margin-right: -6px;
}

.timeline-modal {
  scrollbar-width: thin;
  scrollbar-color: rgba(0,0,0,0.15) transparent;
}

.timeline-modal::-webkit-scrollbar {
  width: 6px;
  height: 6px;
}

.timeline-modal::-webkit-scrollbar-track {
  background: transparent;
  border-radius: 3px;
}

.timeline-modal::-webkit-scrollbar-thumb {
  background: var(--border, rgba(0,0,0,0.15));
  border-radius: 3px;
}

/* 关闭按钮定位（外观由 ModalCloseButton 统一） */
.modal-close {
  position: absolute;
  top: 1rem;
  right: 1rem;
  z-index: 2;
}

.modal-title {
  font-size: 1.8rem;
  font-weight: 800;
  color: var(--foreground, #222);
  margin: 0 0 1.5rem;
}

/* ===== Transitions ===== */
.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.35s cubic-bezier(0.16, 1, 0.3, 1), backdrop-filter 0.35s cubic-bezier(0.16, 1, 0.3, 1);
}

.modal-enter-active .timeline-modal,
.modal-leave-active .timeline-modal {
  transition: transform 0.35s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.35s cubic-bezier(0.16, 1, 0.3, 1);
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
  backdrop-filter: blur(0px);
}

.modal-enter-from .timeline-modal,
.modal-leave-to .timeline-modal {
  transform: translateY(24px);
  opacity: 0;
}

/* ===== Category Header ===== */
.category-modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 1.5rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid var(--border, #eee);
}

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
  background: var(--border, rgba(0,0,0,0.03));
  border: 1px solid var(--border, rgba(0,0,0,0.06));
  text-decoration: none;
  color: inherit;
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
  animation: slideIn 0.35s cubic-bezier(0.16, 1, 0.3, 1) both;
}

.category-modal-header ~ .modal-post-list .modal-post-item:nth-child(1) { animation-delay: 0.15s; }
.category-modal-header ~ .modal-post-list .modal-post-item:nth-child(2) { animation-delay: 0.22s; }
.category-modal-header ~ .modal-post-list .modal-post-item:nth-child(3) { animation-delay: 0.29s; }
.category-modal-header ~ .modal-post-list .modal-post-item:nth-child(4) { animation-delay: 0.36s; }
.category-modal-header ~ .modal-post-list .modal-post-item:nth-child(5) { animation-delay: 0.43s; }

.modal-post-item:hover {
  background: var(--border, rgba(0,0,0,0.05));
  transform: translateX(6px) scale(1.005);
  box-shadow: 0 2px 12px rgba(0,0,0,0.06);
}

.modal-post-title {
  flex: 1;
  font-weight: 500;
  font-size: 0.95rem;
  color: var(--foreground, #222);
  line-height: 1.5;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.modal-post-date {
  font-size: 0.8rem;
  color: var(--foreground, #999);
  background: var(--border, rgba(0,0,0,0.03));
  padding: 0.25rem 0.6rem;
  border-radius: 0.5rem;
  flex-shrink: 0;
}

/* ===== Slide In ===== */
@keyframes slideIn {
  from { opacity: 0; transform: translateX(-24px); }
  to { opacity: 1; transform: translateX(0); }
}

/* ===== Responsive ===== */
@media (max-width: 600px) {
  .timeline-modal {
    max-height: 82vh;
    padding: 1.5rem;
  }
}

/* ===== Dark Mode ===== */
[data-theme='dark'] .timeline-modal {
  background: rgba(25,25,25,0.98);
  border-color: rgba(255,255,255,0.08);
  box-shadow: inset 0 1px 0 0 #fff3;
}

[data-theme='dark'] .modal-title {
  color: rgba(255,255,255,0.9);
}

[data-theme='dark'] .modal-post-item {
  background: rgba(255,255,255,0.03);
  border-color: rgba(255,255,255,0.06);
}

[data-theme='dark'] .modal-post-item:hover {
  background: rgba(255,255,255,0.05);
  border-color: rgba(255,255,255,0.1);
}

[data-theme='dark'] .modal-post-title {
  color: rgba(255,255,255,0.9);
}

[data-theme='dark'] .modal-post-date {
  color: rgba(255,255,255,0.5);
  background: rgba(255,255,255,0.08);
}

[data-theme='dark'] .category-modal-header {
  border-bottom-color: #333;
}
</style>
