<script setup lang="ts">
/**
 * StStack — 一维布局容器
 *
 * 用法：
 *   <StStack gap="3"><StInput /><StButton /></StStack>                  // 纵向（默认）
 *   <StStack direction="horizontal" gap="2" justify="end">…</StStack>
 *
 * 为什么需要它：业务层"自由组合"的裸 div + 手写 flex/gap 是样式重复的最大来源
 * （旧后台的 .xh-grid / .xh-field 就是一例，而且那些类名连 CSS 定义都已丢失）。
 * 把间距收敛成 --st-space-* 的有限刻度后，调用方只能从刻度里选，不再各写各的 px。
 */
import { computed } from 'vue'
import type { StSpace } from '../types'

defineOptions({ name: 'StStack' })

const props = withDefaults(
  defineProps<{
    direction?: 'vertical' | 'horizontal'
    /** 间距刻度，映射 --st-space-N；0 表示紧贴。
     * 数字与数字字符串都接受 —— 模板里 `gap="4"` 传的是字符串。 */
    gap?: StSpace
    align?: 'start' | 'center' | 'end' | 'stretch' | 'baseline'
    justify?: 'start' | 'center' | 'end' | 'between' | 'around' | 'evenly'
    /** 允许换行（横向时才有意义） */
    wrap?: boolean
    /** 撑满可用宽度 */
    block?: boolean
    /** 作为 <ul>/<ol> 渲染，用于列表语义 */
    as?: 'div' | 'ul' | 'ol'
  }>(),
  {
    direction: 'vertical',
    gap: 3,
    wrap: false,
    block: false,
    as: 'div',
  },
)

const classes = computed(() => [
  'st-stack',
  `st-stack--${props.direction}`,
  `st-stack--gap-${props.gap}`,
  props.align ? `st-stack--align-${props.align}` : '',
  props.justify ? `st-stack--justify-${props.justify}` : '',
  {
    'st-stack--wrap': props.wrap,
    'st-stack--block': props.block,
  },
])
</script>

<template>
  <component :is="as" :class="classes">
    <slot />
  </component>
</template>

<style scoped>
.st-stack {
  display: flex;
  box-sizing: border-box;
  gap: var(--st-stack-gap, var(--st-space-3));
  min-width: 0;
}

.st-stack--vertical {
  flex-direction: column;
}

.st-stack--horizontal {
  flex-direction: row;
}

/* ==================== 间距刻度 ==================== */
.st-stack--gap-0 {
  --st-stack-gap: 0;
}

.st-stack--gap-1 {
  --st-stack-gap: var(--st-space-1);
}

.st-stack--gap-2 {
  --st-stack-gap: var(--st-space-2);
}

.st-stack--gap-3 {
  --st-stack-gap: var(--st-space-3);
}

.st-stack--gap-4 {
  --st-stack-gap: var(--st-space-4);
}

.st-stack--gap-5 {
  --st-stack-gap: var(--st-space-5);
}

.st-stack--gap-6 {
  --st-stack-gap: var(--st-space-6);
}

.st-stack--gap-8 {
  --st-stack-gap: var(--st-space-8);
}

/* ==================== 对齐 ==================== */
.st-stack--align-start {
  align-items: flex-start;
}

.st-stack--align-center {
  align-items: center;
}

.st-stack--align-end {
  align-items: flex-end;
}

.st-stack--align-stretch {
  align-items: stretch;
}

.st-stack--align-baseline {
  align-items: baseline;
}

.st-stack--justify-start {
  justify-content: flex-start;
}

.st-stack--justify-center {
  justify-content: center;
}

.st-stack--justify-end {
  justify-content: flex-end;
}

.st-stack--justify-between {
  justify-content: space-between;
}

.st-stack--justify-around {
  justify-content: space-around;
}

.st-stack--justify-evenly {
  justify-content: space-evenly;
}

/* ==================== 修饰 ==================== */
.st-stack--wrap {
  flex-wrap: wrap;
}

.st-stack--block {
  width: 100%;
}

/* 列表语义时不显示项目符号 */
ul.st-stack,
ol.st-stack {
  margin: 0;
  padding: 0;
  list-style: none;
}
</style>
