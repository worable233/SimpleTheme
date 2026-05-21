<script setup lang="ts">
/**
 * TimelineModal — 年份详情弹窗（含统计卡片、月度分组）
 */
import { ref, watch, nextTick } from 'vue'
import type { RenderedText } from '@/types/wordpress'

interface MonthEntry {
  label: string
  posts: PostWithMeta[]
}

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

const props = defineProps<{
  show: boolean
  data: TimelineData | null
}>()

const emit = defineEmits<{
  (e: 'close'): void
}>()

const modalLoading = ref(false)

watch(() => props.show, (val) => {
  if (val) {
    modalLoading.value = true
    nextTick(() => setTimeout(() => { modalLoading.value = false }, 350))
  }
})

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
        <div class="timeline-modal" @click.stop>
          <button class="modal-close" @click="emit('close')" aria-label="按 ESC 关闭">ESC</button>
          <div class="modal-content">
            <div v-if="modalLoading" class="modal-skeleton">
              <div class="skeleton-title"></div>
              <div class="skeleton-stats">
                <div class="skeleton-stat-box" v-for="n in 3" :key="n"></div>
              </div>
              <div class="skeleton-month" v-for="n in 3" :key="n">
                <div class="skeleton-month-label"></div>
                <div class="skeleton-post" v-for="m in 2" :key="m"></div>
              </div>
            </div>
            <div v-else class="modal-content-inner">
              <h2 class="modal-title">{{ data.year }} 年</h2>
              <div class="modal-stats-grid">
                <div class="modal-statbox">
                  <div class="stat-tooltip">汇总</div>
                  <div class="stat-icon">
                    <i class="bx bx-file-blank" style="font-size: 1.3rem;"></i>
                  </div>
                  <div class="stat-label">文章总数</div>
                  <div class="stat-value">{{ data.total }}</div>
                </div>
                <div class="modal-statbox">
                  <div class="stat-tooltip">分类</div>
                  <div class="stat-icon">
                    <i class="bx bx-folder" style="font-size: 1.3rem;"></i>
                  </div>
                  <div class="stat-label">分类数</div>
                  <div class="stat-value">{{ data.categories }}</div>
                </div>
                <div class="modal-statbox">
                  <div class="stat-tooltip">有文章的月份</div>
                  <div class="stat-icon">
                    <i class="bx bx-calendar-check" style="font-size: 1.3rem;"></i>
                  </div>
                  <div class="stat-label">活跃月份</div>
                  <div class="stat-value">{{ data.months.length }}</div>
                </div>
              </div>
              <div class="modal-month-groups">
                <div
                  v-for="[monthLabel, monthPosts] in data.months"
                  :key="monthLabel"
                  class="modal-month-group"
                >
                  <h3 class="modal-month-title">{{ monthLabel }}</h3>
                  <div class="modal-post-list">
                    <a
                      v-for="post in monthPosts"
                      :key="post.id"
                      :href="post.link"
                      class="modal-post-item"
                    >
                      <span class="modal-post-title">{{ (post.title as RenderedText).rendered }}</span>
                      <span class="modal-post-date">{{ post.displayDate }}</span>
                    </a>
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

.modal-close {
  position: absolute;
  top: 1rem;
  right: 1rem;
  min-width: 3rem;
  height: 1.8rem;
  padding: 0 0.5rem;
  border-radius: var(--radius-small, 4px);
  border: 1px solid var(--border, rgba(0,0,0,0.15));
  background: var(--surface, rgba(0,0,0,0.03));
  color: var(--foreground, #666);
  font-family: 'SF Mono', 'Cascadia Code', 'Consolas', monospace;
  font-size: 0.7rem;
  font-weight: 700;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  line-height: 1;
  box-shadow: 0 2px 0 var(--border, rgba(0,0,0,0.12));
  user-select: none;
  transition: all 0.2s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.modal-close:hover {
  transform: translateY(1px);
  box-shadow: 0 1px 0 var(--border, rgba(0,0,0,0.12));
  background: var(--border, rgba(0,0,0,0.06));
  color: var(--foreground, #222);
}

.modal-close:active {
  transform: translateY(2px) scale(0.96);
  box-shadow: none;
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

/* ===== Stats Grid ===== */
.modal-stats-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1rem;
  margin-bottom: 2rem;
}

.modal-statbox {
  background: var(--border, rgba(0,0,0,0.03));
  border-radius: var(--radius-medium, 6px);
  padding: 1rem;
  text-align: center;
  border: 1.5px solid var(--border, rgba(0,0,0,0.06));
  transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
  position: relative;
  animation: slideIn 0.45s cubic-bezier(0.16, 1, 0.3, 1) both;
}

.modal-statbox:nth-child(2) { animation-delay: 0.08s; }
.modal-statbox:nth-child(3) { animation-delay: 0.16s; }

.modal-statbox .stat-tooltip {
  position: absolute;
  bottom: 100%;
  left: 50%;
  transform: translateX(-50%) translateY(-10px);
  background: rgba(0,0,0,0.85);
  color: #fff;
  padding: 0.4rem 0.6rem;
  border-radius: var(--radius-small, 4px);
  font-size: 0.7rem;
  white-space: nowrap;
  pointer-events: none;
  opacity: 0;
  visibility: hidden;
  transition: all 0.25s cubic-bezier(0.34, 1.56, 0.64, 1);
  box-shadow: 0 4px 12px rgba(0,0,0,0.15);
  z-index: 999;
}

.modal-statbox .stat-tooltip::after {
  content: '';
  position: absolute;
  top: 100%;
  left: 50%;
  transform: translateX(-50%);
  border: 6px solid transparent;
  border-top-color: rgba(0,0,0,0.85);
}

.modal-statbox:hover .stat-tooltip {
  opacity: 1;
  visibility: visible;
  transform: translateX(-50%) translateY(0);
}

.modal-statbox:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 16px rgba(0,0,0,0.08);
}

.stat-icon {
  font-size: 1.15rem;
  margin-bottom: 0.4rem;
  opacity: 0.7;
}

.stat-label {
  font-size: 0.85rem;
  color: var(--foreground, #888);
  margin-bottom: 0.3rem;
  font-weight: 500;
}

.stat-value {
  font-size: 1.4rem;
  font-weight: 800;
  color: var(--foreground, #222);
  line-height: 1.2;
}

/* ===== Month Groups ===== */
.modal-month-group {
  margin: 0 0 1.5rem;
  animation: slideIn 0.4s cubic-bezier(0.16, 1, 0.3, 1) both;
}

.modal-month-group:nth-child(2) { animation-delay: 0.06s; }
.modal-month-group:nth-child(3) { animation-delay: 0.12s; }
.modal-month-group:nth-child(4) { animation-delay: 0.18s; }
.modal-month-group:nth-child(5) { animation-delay: 0.24s; }

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
  transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
  animation: slideIn 0.35s cubic-bezier(0.16, 1, 0.3, 1) both;
}

.modal-month-group:nth-child(1) .modal-post-item:nth-child(2) { animation-delay: 0.05s; }
.modal-month-group:nth-child(1) .modal-post-item:nth-child(3) { animation-delay: 0.10s; }
.modal-month-group:nth-child(2) .modal-post-item:nth-child(1) { animation-delay: 0.03s; }
.modal-month-group:nth-child(2) .modal-post-item:nth-child(2) { animation-delay: 0.08s; }
.modal-month-group:nth-child(2) .modal-post-item:nth-child(3) { animation-delay: 0.13s; }
.modal-month-group:nth-child(3) .modal-post-item:nth-child(1) { animation-delay: 0.06s; }
.modal-month-group:nth-child(3) .modal-post-item:nth-child(2) { animation-delay: 0.11s; }
.modal-month-group:nth-child(3) .modal-post-item:nth-child(3) { animation-delay: 0.16s; }

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

/* ===== Modal Skeleton ===== */
.modal-skeleton {
  padding: 2rem;
  animation: skeletonPulse 2.2s ease-in-out infinite;
}

.skeleton-title {
  height: 32px;
  width: 200px;
  background: var(--skeleton-bg, rgba(128,128,128,0.15));
  border-radius: var(--radius-sm, 6px);
  margin-bottom: 1.5rem;
}

.skeleton-stats {
  display: flex;
  gap: 1rem;
  margin-bottom: 2rem;
}

.skeleton-stat-box {
  flex: 1;
  height: 96px;
  background: var(--skeleton-bg, rgba(128,128,128,0.15));
  border-radius: var(--radius-md, 12px);
}

.skeleton-month {
  margin-bottom: 1.5rem;
}

.skeleton-month-label {
  height: 24px;
  width: 120px;
  background: var(--skeleton-bg, rgba(128,128,128,0.15));
  border-radius: var(--radius-sm, 6px);
  margin-bottom: 0.75rem;
}

.skeleton-post {
  height: 20px;
  width: 100%;
  background: var(--skeleton-bg, rgba(128,128,128,0.15));
  border-radius: var(--radius-xs, 4px);
  margin-bottom: 0.5rem;
}

.skeleton-post:last-child {
  width: 75%;
}

/* ===== Slide In Animation ===== */
@keyframes slideIn {
  from { opacity: 0; transform: translateX(-24px); }
  to { opacity: 1; transform: translateX(0); }
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
:global(body.dark) .timeline-modal {
  background: rgba(25,25,25,0.98);
  border-color: rgba(255,255,255,0.08);
}

:global(body.dark) .modal-title {
  color: rgba(255,255,255,0.9);
}

:global(body.dark) .modal-statbox {
  background: rgba(255,255,255,0.04);
  border-color: rgba(255,255,255,0.08);
}

:global(body.dark) .stat-label {
  color: rgba(255,255,255,0.5);
}

:global(body.dark) .stat-value {
  color: rgba(255,255,255,0.95);
}

:global(body.dark) .modal-month-title {
  color: rgba(255,255,255,0.9);
}

:global(body.dark) .modal-month-title::before {
  background: var(--primary, rgba(255,255,255,0.8));
}

:global(body.dark) .modal-post-item {
  background: rgba(255,255,255,0.03);
  border-color: rgba(255,255,255,0.06);
}

:global(body.dark) .modal-post-item:hover {
  background: rgba(255,255,255,0.05);
  border-color: rgba(255,255,255,0.1);
}

:global(body.dark) .modal-post-title {
  color: rgba(255,255,255,0.9);
}

:global(body.dark) .modal-post-date {
  color: rgba(255,255,255,0.5);
  background: rgba(255,255,255,0.08);
}
</style>
