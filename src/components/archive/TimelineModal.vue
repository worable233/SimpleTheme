<script setup lang="ts">
/**
 * TimelineModal — 年份详情弹窗（含统计卡片、月度分组）
 *
 * 数据由父组件预先加载后通过 props 传入，弹窗直接渲染内容，无需骨架。
 * 外壳（遮罩 / 焦点陷阱 / ESC / 滚动锁定 / 右上角关闭按钮）统一由
 * src/ui 的 StModal 提供，本组件只保留内容排版。
 */
import { ref, watch } from 'vue'
import type { RenderedText } from '@/types/wordpress'
import AppIcon from '@/components/AppIcon.vue'
import { StModal, StTooltip } from '@/ui'
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

const props = defineProps<{
  show: boolean
  data: TimelineData | null
}>()

const emit = defineEmits<{
  (e: 'close'): void
}>()

/** StModal 受控开关；父级要求显示且数据就绪时才展开，关闭动作统一上抛 */
const open = ref(false)
watch(
  () => props.show && !!props.data,
  (value) => {
    open.value = value
  },
  { immediate: true },
)
</script>

<template>
  <StModal
    v-model:show="open"
    :title="data ? `${data.year} 年` : '归档'"
    size="large"
    @close="emit('close')"
  >
    <template v-if="data">
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
    </template>
  </StModal>
</template>

<style scoped>
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

.modal-statbox:nth-child(2) {
  animation-delay: 0.06s;
}
.modal-statbox:nth-child(3) {
  animation-delay: 0.12s;
}

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

.modal-month-group:nth-child(2) {
  animation-delay: 0.04s;
}
.modal-month-group:nth-child(3) {
  animation-delay: 0.08s;
}
.modal-month-group:nth-child(4) {
  animation-delay: 0.12s;
}
.modal-month-group:nth-child(5) {
  animation-delay: 0.16s;
}

.modal-month-title {
  font-size: 1.15rem;
  font-weight: 700;
  color: var(--foreground);
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
  background: var(--primary);
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

.modal-month-group:nth-child(1) .modal-post-item:nth-child(2) {
  animation-delay: 0.03s;
}
.modal-month-group:nth-child(1) .modal-post-item:nth-child(3) {
  animation-delay: 0.06s;
}
.modal-month-group:nth-child(2) .modal-post-item:nth-child(1) {
  animation-delay: 0.02s;
}
.modal-month-group:nth-child(2) .modal-post-item:nth-child(2) {
  animation-delay: 0.04s;
}
.modal-month-group:nth-child(2) .modal-post-item:nth-child(3) {
  animation-delay: 0.06s;
}
.modal-month-group:nth-child(3) .modal-post-item:nth-child(1) {
  animation-delay: 0.03s;
}
.modal-month-group:nth-child(3) .modal-post-item:nth-child(2) {
  animation-delay: 0.05s;
}
.modal-month-group:nth-child(3) .modal-post-item:nth-child(3) {
  animation-delay: 0.07s;
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

/* ===== Slide In Animation ===== */
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

/* ===== Responsive ===== */
@media (max-width: 600px) {
  .modal-stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}
</style>
