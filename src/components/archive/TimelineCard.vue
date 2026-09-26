<script setup lang="ts">
/**
 * TimelineCard — 单个年份时间线卡片（含12月日历格）
 */
import { StTag } from '@/ui'

defineProps<{
  year: number
  total: number
  activeMonths: boolean[]
}>()

const emit = defineEmits<{
  (e: 'select', year: number): void
}>()
</script>

<template>
  <button
    type="button"
    class="timeline-card"
    :aria-label="`查看 ${year} 年的 ${total} 篇文章`"
    aria-haspopup="dialog"
    @click="emit('select', year)"
  >
    <!--
      保留原生 <button>：整块卡片即按钮，依赖 hover:-translate-y/scale 与
      任意栅格几何，迁 StButton 会把内容塞进 .st-button__content 而丢失布局。
      依据见 src/ui/README.md「何时不该用 StButton」。
    -->
    <div class="timeline-card__head">
      <span class="timeline-card__year">{{ year }}</span>
      <StTag round>{{ total }} 篇文章</StTag>
    </div>
    <div class="timeline-card__months">
      <span
        v-for="m in 12"
        :key="m"
        class="timeline-card__month"
        :class="{ 'is-active': activeMonths[m - 1] }"
        >{{ m }}</span
      >
    </div>
  </button>
</template>

<style scoped>
.timeline-card {
  display: flex;
  flex-direction: column;
  gap: 16px;
  width: 100%;
  padding: 1.2rem;
  border: 1.5px solid var(--border);
  border-radius: var(--radius-large);
  background-color: var(--card);
  text-align: left;
  cursor: pointer;
  appearance: none;
  backdrop-filter: blur(24px);
  box-shadow: 0 4px 24px 0 rgb(0 0 0 / 0.07);
  transition: all 350ms cubic-bezier(0.34, 1.56, 0.64, 1);
}

.timeline-card:hover {
  transform: translateY(-6px) scale(1.02);
  border-color: var(--primary);
  box-shadow: 0 12px 52px -8px rgb(0 0 0 / 0.18);
}

.timeline-card:focus-visible {
  border-color: var(--primary);
}

.timeline-card__head {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.timeline-card__year {
  font-size: 24px;
  line-height: 1.25;
  font-weight: 800;
  color: var(--foreground);
}

.timeline-card__months {
  display: grid;
  grid-template-columns: repeat(6, 1fr);
  grid-template-rows: repeat(2, 1fr);
  gap: 8px;
}

.timeline-card__month {
  display: flex;
  aspect-ratio: 1;
  align-items: center;
  justify-content: center;
  border: 1.5px solid transparent;
  border-radius: var(--radius-medium);
  background-color: var(--border);
  font-size: 0.85rem;
  font-weight: 600;
  color: var(--foreground);
  transition: all 350ms cubic-bezier(0.34, 1.56, 0.64, 1);
}

[data-theme='dark'] .timeline-card__month {
  background-color: rgb(255 255 255 / 0.08);
  color: #aaa;
}

.timeline-card__month.is-active {
  transform: scale(1.05);
  border-color: var(--primary);
  background-color: var(--primary);
  color: #fff;
  box-shadow: 0 2px 12px -4px var(--primary);
}

[data-theme='dark'] .timeline-card__month.is-active {
  color: #222;
}
</style>
