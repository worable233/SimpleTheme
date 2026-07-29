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
    class="group flex cursor-pointer flex-col gap-4 rounded-large border-[1.5px] border-border bg-card p-[1.2rem] shadow-[0_4px_24px_0_rgba(0,0,0,0.07)] backdrop-blur-xl transition-all duration-[350ms] ease-[cubic-bezier(0.34,1.56,0.64,1)] hover:-translate-y-1.5 hover:scale-[1.02] hover:border-primary hover:shadow-[0_12px_52px_-8px_rgba(0,0,0,0.18)] focus-visible:border-primary dark:shadow-[inset_0_1px_0_0_#fff3]"
    tabindex="0"
    @click="emit('select', year)"
    @keydown.enter="emit('select', year)"
  >
    <div class="flex items-center justify-between">
      <span class="text-3xl leading-tight font-extrabold text-foreground">{{ year }}</span>
      <span
        class="rounded-full bg-border px-3 py-1 text-[0.9rem] whitespace-nowrap text-foreground dark:bg-white/10 dark:text-white/70"
        >{{ total }} 篇文章</span
      >
    </div>
    <div class="grid grid-cols-6 grid-rows-2 gap-2">
      <span
        v-for="m in 12"
        :key="m"
        class="flex aspect-square items-center justify-center rounded-medium border-[1.5px] border-transparent bg-border text-[0.85rem] font-semibold text-foreground transition-all duration-[350ms] ease-[cubic-bezier(0.34,1.56,0.64,1)] dark:bg-white/[0.08] dark:text-[#aaa]"
        :class="
          activeMonths[m - 1] &&
          'scale-105 border-primary bg-primary text-white shadow-[0_2px_12px_-4px_var(--primary)] dark:text-[#222]'
        "
        >{{ m }}</span
      >
    </div>
  </section>
</template>
