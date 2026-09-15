<script setup lang="ts">
/**
 * StSkeleton — 骨架屏
 *
 * 用法：
 *   <StSkeleton width="60%" />
 *   <StSkeleton :rows="3" />
 *   <StSkeleton circle :width="40" :height="40" />
 *
 * 纯装饰元素：整块 aria-hidden，读屏只应读到加载完成后的真实内容。
 * rows > 1 时最后一行缩短，模拟真实段落的参差收尾。
 */
import { computed } from 'vue'

defineOptions({ name: 'StSkeleton' })

const props = withDefaults(
  defineProps<{
    width?: string | number
    height?: string | number
    /** 圆形（头像 / 图标位） */
    circle?: boolean
    /** 文本行数 */
    rows?: number
    /** 脉冲动画 */
    animated?: boolean
  }>(),
  {
    rows: 1,
    animated: true,
    circle: false,
  },
)

function toCss(value: string | number | undefined): string | undefined {
  if (value === undefined) return undefined
  return typeof value === 'number' ? `${value}px` : value
}

const textMode = computed(() => !props.circle && props.rows > 1)

const textRows = computed(() =>
  Array.from({ length: Math.max(1, props.rows) }, (_, index) => {
    const isLast = index === props.rows - 1
    return {
      width: toCss(props.width) ?? (isLast ? '60%' : '100%'),
      height: toCss(props.height) ?? '1em',
    }
  }),
)

const singleStyle = computed(() => {
  // 圆形只用一方长度，长宽保持一致才是正圆
  if (props.circle) {
    const side = toCss(props.width ?? props.height) ?? '40px'
    return { width: side, height: side }
  }
  return { width: toCss(props.width) ?? '100%', height: toCss(props.height) ?? '1em' }
})

const classes = computed(() => [
  'st-skeleton',
  {
    'st-skeleton--circle': props.circle,
    'st-skeleton--text': textMode.value,
    'st-skeleton--animated': props.animated,
  },
])
</script>

<template>
  <div :class="classes" aria-hidden="true">
    <template v-if="textMode">
      <span v-for="(row, index) in textRows" :key="index" class="st-skeleton__row" :style="row" />
    </template>
    <span v-else class="st-skeleton__block" :style="singleStyle" />
  </div>
</template>

<style scoped>
.st-skeleton {
  display: block;
  width: 100%;
}

.st-skeleton--circle {
  display: inline-block;
  width: auto;
}

.st-skeleton--text {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.st-skeleton__block,
.st-skeleton__row {
  display: block;
  box-sizing: border-box;
  background-color: var(--st-fill-active);
  border-radius: var(--radius-small);
}

.st-skeleton--circle .st-skeleton__block {
  border-radius: var(--radius-full);
}

.st-skeleton--animated .st-skeleton__block,
.st-skeleton--animated .st-skeleton__row {
  animation: var(--animate-skeleton-pulse);
}

@media (prefers-reduced-motion: reduce) {
  .st-skeleton--animated .st-skeleton__block,
  .st-skeleton--animated .st-skeleton__row {
    animation: none;
  }
}
</style>
