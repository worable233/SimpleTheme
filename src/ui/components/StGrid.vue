<script setup lang="ts">
/**
 * StGrid — 等宽多列布局容器
 *
 * 用法：
 *   <StGrid :cols="2" gap="4"><StInput /><StInput /></StGrid>
 *   <StGrid :cols="3" :collapsible="false">…</StGrid>   // 关闭窄屏折叠
 *
 * 消费 --st-space-* 刻度，列宽用 minmax(0, 1fr) 避免长内容把栅格撑破
 * （flex 布局在同样场景下会被内容顶开，这是旧 .xh-grid 从 flex 换 grid 的原因）。
 */
import { computed } from 'vue'

defineOptions({ name: 'StGrid' })

const props = withDefaults(
  defineProps<{
    /** 列数 */
    cols?: 1 | 2 | 3 | 4
    /** 间距刻度，映射 --st-space-N */
    gap?: 0 | 1 | 2 | 3 | 4 | 5 | 6 | 8
    /** 窄屏（<640px）折叠为单列。断点固定在 640px：
     * 媒体查询吃不到 CSS 自定义属性，做成可配的 px 只会是个说了不算的 API。 */
    collapsible?: boolean
  }>(),
  {
    cols: 2,
    gap: 4,
    collapsible: true,
  },
)

const classes = computed(() => [
  'st-grid',
  `st-grid--cols-${props.cols}`,
  `st-grid--gap-${props.gap}`,
  { 'st-grid--collapsible': props.collapsible },
])
</script>

<template>
  <div class="st-grid" :class="classes">
    <slot />
  </div>
</template>

<style scoped>
.st-grid {
  display: grid;
  box-sizing: border-box;
  gap: var(--st-grid-gap, var(--st-space-4));
  min-width: 0;
}

.st-grid--cols-1 {
  grid-template-columns: repeat(1, minmax(0, 1fr));
}

.st-grid--cols-2 {
  grid-template-columns: repeat(2, minmax(0, 1fr));
}

.st-grid--cols-3 {
  grid-template-columns: repeat(3, minmax(0, 1fr));
}

.st-grid--cols-4 {
  grid-template-columns: repeat(4, minmax(0, 1fr));
}

/* ==================== 间距刻度 ==================== */
.st-grid--gap-0 {
  --st-grid-gap: 0;
}

.st-grid--gap-1 {
  --st-grid-gap: var(--st-space-1);
}

.st-grid--gap-2 {
  --st-grid-gap: var(--st-space-2);
}

.st-grid--gap-3 {
  --st-grid-gap: var(--st-space-3);
}

.st-grid--gap-4 {
  --st-grid-gap: var(--st-space-4);
}

.st-grid--gap-5 {
  --st-grid-gap: var(--st-space-5);
}

.st-grid--gap-6 {
  --st-grid-gap: var(--st-space-6);
}

.st-grid--gap-8 {
  --st-grid-gap: var(--st-space-8);
}

/* 窄屏折叠为单列，避免两列输入框在小屏上挤成一条 */
@media (max-width: 639px) {
  .st-grid--collapsible.st-grid--cols-2,
  .st-grid--collapsible.st-grid--cols-3,
  .st-grid--collapsible.st-grid--cols-4 {
    grid-template-columns: repeat(1, minmax(0, 1fr));
  }
}
</style>
