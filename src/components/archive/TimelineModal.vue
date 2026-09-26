<script setup lang="ts">
/**
 * TimelineModal — 年份详情弹窗（含统计卡片、月度分组）
 *
 * 数据由父组件预先加载后通过 props 传入，弹窗直接渲染内容，无需骨架。
 */
import type { RenderedText } from '@/types/wordpress'
import ModalCloseButton from '@/components/ModalCloseButton.vue'
import AppIcon from '@/components/AppIcon.vue'
import { StTooltip } from '@/ui'
import { RouterLink } from 'vue-router'
import { toInternalPath } from '@/lib/theme-config'

interface PostWithMeta {
  id: number
  link: string
  title: RenderedText | { rendered: string }
  date: string
  displayDate: string
}

interface TimelineData {
  year: number
  total: number
  categories: number
  months: [string, PostWithMeta[]][]
}

defineProps<{
  show: boolean
  data: TimelineData | null
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
        v-if="show && data"
        class="modal-mask"
        @click="onMaskClick"
      >
        <div
          class="timeline-modal"
          role="dialog"
          aria-modal="true"
          aria-labelledby="timeline-modal-title"
          @click.stop
        >
          <ModalCloseButton class="modal-close" autofocus @click="emit('close')" />
          <div class="modal-content">
            <div class="modal-content-inner">
              <h2 id="timeline-modal-title" class="modal-title">{{ data.year }} 年</h2>
              <div class="modal-stats-grid">
                <StTooltip content="汇总" placement="top">
                  <div class="modal-statbox">
                    <div class="stat-head">
                      <span class="stat-icon"><AppIcon name="file-blank" :size="18" /></span>
                      <span class="stat-label">文章总数</span>
                    </div>
                    <div class="stat-value">{{ data.total }}</div>
                  </div>
                </StTooltip>
                <StTooltip content="分类" placement="top">
                  <div class="modal-statbox">
                    <div class="stat-head">
                      <span class="stat-icon"><AppIcon name="folder" :size="18" /></span>
                      <span class="stat-label">分类数</span>
                    </div>
                    <div class="stat-value">{{ data.categories }}</div>
                  </div>
                </StTooltip>
                <StTooltip content="有文章的月份" placement="top">
                  <div class="modal-statbox">
                    <div class="stat-head">
                      <span class="stat-icon"><AppIcon name="calendar-check" :size="18" /></span>
                      <span class="stat-label">活跃月份</span>
                    </div>
                    <div class="stat-value">{{ data.months.length }}</div>
                  </div>
                </StTooltip>
              </div>
              <div class="modal-month-groups">
                <div
                  v-for="[monthLabel, monthPosts] in data.months"
                  :key="monthLabel"
                  class="modal-month-group"
                >
                  <h3 class="modal-month-title">{{ monthLabel }}</h3>
                  <div class="modal-post-list">
                    <RouterLink
                      v-for="post in monthPosts"
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
  transition: opacity var(--st-duration-modal) var(--ease-out), backdrop-filter var(--st-duration-modal) var(--ease-out);
}

.modal-enter-active .timeline-modal,
.modal-leave-active .timeline-modal {
  transition: transform var(--st-duration-modal) var(--ease-out), opacity var(--st-duration-modal) var(--ease-out);
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
  backdrop-filter: blur(0px);
}

.modal-enter-from .timeline-modal,
.modal-leave-to .timeline-modal {
  transform: translateY(8px);
  opacity: 0;
}

/* ===== Stats Grid ===== */
.modal-stats-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1rem;
  margin-bottom: 2rem;
}

.modal-statbox {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  padding: 1rem 1.25rem;
  background: var(--accent);
  border-radius: var(--radius-large);
  transition: background-color var(--transition);
  animation: slideIn 0.45s var(--ease-out) both;
}

.modal-statbox:nth-child(2) { animation-delay: 0.06s; }
.modal-statbox:nth-child(3) { animation-delay: 0.12s; }

.stat-head {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: var(--secondary);
}

.stat-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  color: var(--secondary);
}

.stat-label {
  font-size: 0.8125rem;
  font-weight: 500;
  line-height: 1;
}

.stat-value {
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--foreground);
  line-height: 1.1;
  font-variant-numeric: tabular-nums;
}

/* ===== Month Groups ===== */
.modal-month-group {
  margin: 0 0 1.5rem;
  animation: slideIn 0.35s var(--ease-out) both;
}

.modal-month-group:nth-child(2) { animation-delay: 0.04s; }
.modal-month-group:nth-child(3) { animation-delay: 0.08s; }
.modal-month-group:nth-child(4) { animation-delay: 0.12s; }
.modal-month-group:nth-child(5) { animation-delay: 0.16s; }

.modal-month-title {
  font-size: 1.15rem;
  font-weight: 700;
  color: var(--foreground, #505050);
  margin-bottom: 1rem;
  padding-left: 1rem;
  position: relative;
}

.modal-month-title::before {
  content: '';
  position: absolute;
  left: 0;
  top: 50%;
  width: 3px;
  height: 1.2em;
  background: var(--primary, #505050);
  transform: translateY(-50%);
  border-radius: 3px;
  opacity: 0.7;
}

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
  transition: background-color var(--transition), border-color var(--transition), box-shadow var(--transition);
  animation: slideIn 0.3s var(--ease-out) both;
}

.modal-month-group:nth-child(1) .modal-post-item:nth-child(2) { animation-delay: 0.03s; }
.modal-month-group:nth-child(1) .modal-post-item:nth-child(3) { animation-delay: 0.06s; }
.modal-month-group:nth-child(2) .modal-post-item:nth-child(1) { animation-delay: 0.02s; }
.modal-month-group:nth-child(2) .modal-post-item:nth-child(2) { animation-delay: 0.04s; }
.modal-month-group:nth-child(2) .modal-post-item:nth-child(3) { animation-delay: 0.06s; }
.modal-month-group:nth-child(3) .modal-post-item:nth-child(1) { animation-delay: 0.03s; }
.modal-month-group:nth-child(3) .modal-post-item:nth-child(2) { animation-delay: 0.05s; }
.modal-month-group:nth-child(3) .modal-post-item:nth-child(3) { animation-delay: 0.07s; }

.modal-post-item:hover {
  background: var(--border, rgba(0,0,0,0.05));
  border-color: var(--primary);
  box-shadow: 0 2px 8px rgba(0,0,0,0.06);
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

/* ===== Slide In Animation ===== */
@keyframes slideIn {
  from { opacity: 0; transform: translateY(8px); }
  to { opacity: 1; transform: translateY(0); }
}

/* ===== Responsive ===== */
@media (max-width: 600px) {
  .modal-stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }

  .timeline-modal {
    max-height: 82vh;
    padding: 1.5rem;
  }
}

/* ===== Dark Mode ===== */
[data-theme='dark'] .timeline-modal {
  background: rgba(25,25,25,0.98);
  border-color: rgba(255,255,255,0.08);
}

[data-theme='dark'] .modal-title {
  color: rgba(255,255,255,0.9);
}

[data-theme='dark'] .modal-month-title {
  color: rgba(255,255,255,0.9);
}

[data-theme='dark'] .modal-month-title::before {
  background: var(--primary, rgba(255,255,255,0.8));
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
</style>
