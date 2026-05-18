<script setup lang="ts">
/**
 * TimelineCard — 单个年份时间线卡片（含12月日历格）
 */
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
  <section
    class="timeline-year-card"
    tabindex="0"
    @click="emit('select', year)"
    @keydown.enter="emit('select', year)"
  >
    <div class="timeline-year-header">
      <span class="timeline-year-number">{{ year }}</span>
      <span class="timeline-year-count">{{ total }} 篇文章</span>
    </div>
    <div class="timeline-year-calendar">
      <span
        v-for="m in 12"
        :key="m"
        class="timeline-year-calendar-month"
        :class="{ active: activeMonths[m - 1] }"
      >{{ m }}</span>
    </div>
  </section>
</template>

<style scoped>
.timeline-year-card {
  background: var(--card, rgba(255,255,255,0.7));
  border-radius: var(--radius-large, 8px);
  box-shadow: 0 4px 24px 0 rgba(0,0,0,0.07);
  padding: 1.2rem;
  display: flex;
  flex-direction: column;
  gap: 1rem;
  cursor: pointer;
  border: 1.5px solid var(--border, #e0e0e0);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  transition: all 0.35s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.timeline-year-card:hover {
  transform: translateY(-6px) scale(1.02);
  box-shadow: 0 12px 52px -8px rgba(0,0,0,0.18);
  border-color: var(--primary, #505050);
}

.timeline-year-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.timeline-year-number {
  font-size: 2rem;
  font-weight: 800;
  color: var(--foreground, #222);
  line-height: 1.2;
}

.timeline-year-count {
  font-size: 0.9rem;
  color: var(--foreground, #888);
  background: var(--border, rgba(0,0,0,0.06));
  padding: 0.3rem 0.8rem;
  border-radius: var(--radius-full, 9999px);
  white-space: nowrap;
}

.timeline-year-calendar {
  display: grid;
  grid-template-columns: repeat(6, 1fr);
  grid-template-rows: repeat(2, 1fr);
  gap: 0.5rem;
}

.timeline-year-calendar-month {
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.85rem;
  font-weight: 600;
  color: var(--foreground, #999);
  background: var(--border, rgba(0,0,0,0.04));
  border-radius: var(--radius-medium, 6px);
  aspect-ratio: 1;
  transition: all 0.35s cubic-bezier(0.34, 1.56, 0.64, 1);
  border: 1.5px solid transparent;
}

.timeline-year-calendar-month.active {
  background: var(--primary, #505050);
  color: #fff;
  border-color: var(--primary, #505050);
  box-shadow: 0 2px 12px -4px var(--primary, #505050);
  transform: scale(1.05);
}

/* Dark mode */
:global(body.dark) .timeline-year-card {
  background: rgba(30,30,30,0.92);
  border-color: #333;
}

:global(body.dark) .timeline-year-card:hover {
  border-color: var(--primary, #fff);
}

:global(body.dark) .timeline-year-number {
  color: rgba(255,255,255,0.9);
}

:global(body.dark) .timeline-year-count {
  background: rgba(255,255,255,0.1);
  color: rgba(255,255,255,0.7);
}

:global(body.dark) .timeline-year-calendar-month {
  background: rgba(255,255,255,0.08);
  color: #aaa;
  border-color: #333;
}

:global(body.dark) .timeline-year-calendar-month.active {
  background: var(--primary, #fff);
  color: #222;
  border-color: var(--primary, #fff);
  box-shadow: 0 2px 12px -4px var(--primary, #fff);
}
</style>
