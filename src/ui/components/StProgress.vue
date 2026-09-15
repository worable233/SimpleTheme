<script setup lang="ts">
/**
 * StProgress — 进度条
 *
 * 用法：
 *   <StProgress :percentage="42" />
 *   <StProgress :percentage="80" status="warning" />
 *   <StProgress type="circle" :percentage="60" :stroke-width="6" />
 *
 * 进度值自动 clamp 到 0–100，调用方无需自己兜底；颜色由 status 决定，
 * 不传时回落到 --primary。
 */
import { computed } from 'vue'
import type { StStatus } from '../types'

defineOptions({ name: 'StProgress' })

const props = withDefaults(
  defineProps<{
    /** 进度百分比，自动 clamp 到 0–100 */
    percentage?: number
    type?: 'line' | 'circle'
    /** 语义状态；不传时用 --primary */
    status?: StStatus
    /** 显示百分比文本 */
    showIndicator?: boolean
    /** 线形高度（px），仅 type='line' */
    height?: number
    /** 环形描边宽度（视图单位），仅 type='circle' */
    strokeWidth?: number
  }>(),
  {
    percentage: 0,
    type: 'line',
    showIndicator: true,
    height: 6,
    strokeWidth: 4,
  },
)

/** 环形视口固定尺寸，几何计算只依赖它和 strokeWidth */
const CIRCLE_SIZE = 80

const clamped = computed(() => Math.min(100, Math.max(0, props.percentage)))

const label = computed(() => `${Math.round(clamped.value)}%`)

const radius = computed(() => (CIRCLE_SIZE - props.strokeWidth) / 2)
const circumference = computed(() => 2 * Math.PI * radius.value)
/** 用 dashoffset 表示"未完成"部分，随进度收缩 */
const dashOffset = computed(() => circumference.value * (1 - clamped.value / 100))

const lineStyle = computed(() => ({ height: `${props.height}px` }))
const circleStyle = computed(() => ({ width: `${CIRCLE_SIZE}px`, height: `${CIRCLE_SIZE}px` }))

const classes = computed(() => [
  'st-progress',
  `st-progress--${props.type}`,
  { [`st-progress--${props.status}`]: !!props.status },
])
</script>

<template>
  <div
    :class="classes"
    role="progressbar"
    aria-valuemin="0"
    aria-valuemax="100"
    :aria-valuenow="Math.round(clamped)"
    :aria-label="`进度 ${label}`"
  >
    <template v-if="type === 'line'">
      <div class="st-progress__track" :style="lineStyle">
        <div class="st-progress__fill" :style="{ width: label }" />
      </div>
      <span v-if="showIndicator" class="st-progress__indicator">{{ label }}</span>
    </template>

    <template v-else>
      <div class="st-progress__circle" :style="circleStyle">
        <svg
          :width="CIRCLE_SIZE"
          :height="CIRCLE_SIZE"
          :viewBox="`0 0 ${CIRCLE_SIZE} ${CIRCLE_SIZE}`"
          aria-hidden="true"
        >
          <circle
            class="st-progress__ring"
            :cx="CIRCLE_SIZE / 2"
            :cy="CIRCLE_SIZE / 2"
            :r="radius"
            fill="none"
            :stroke-width="strokeWidth"
          />
          <circle
            class="st-progress__arc"
            :cx="CIRCLE_SIZE / 2"
            :cy="CIRCLE_SIZE / 2"
            :r="radius"
            fill="none"
            :stroke-width="strokeWidth"
            stroke-linecap="round"
            :stroke-dasharray="circumference"
            :stroke-dashoffset="dashOffset"
          />
        </svg>
        <span v-if="showIndicator" class="st-progress__indicator">{{ label }}</span>
      </div>
    </template>
  </div>
</template>

<style scoped>
.st-progress {
  --st-progress-color: var(--primary);

  box-sizing: border-box;
  color: var(--foreground);
  font-size: var(--st-font-small);
}

.st-progress--success {
  --st-progress-color: var(--success);
}

.st-progress--warning {
  --st-progress-color: var(--warning);
}

.st-progress--error {
  --st-progress-color: var(--danger);
}

/* ==================== 线形 ==================== */
.st-progress--line {
  display: flex;
  align-items: center;
  gap: 8px;
  width: 100%;
}

.st-progress__track {
  position: relative;
  flex: 1 1 auto;
  min-width: 0;
  overflow: hidden;
  border-radius: var(--radius-full);
  background-color: var(--st-fill-active);
}

.st-progress__fill {
  height: 100%;
  border-radius: inherit;
  background-color: var(--st-progress-color);
  transition: width var(--transition);
}

.st-progress--line .st-progress__indicator {
  flex: none;
  min-width: 4ch;
  color: var(--muted-foreground);
  text-align: right;
}

/* ==================== 环形 ==================== */
.st-progress--circle {
  display: inline-flex;
}

.st-progress__circle {
  position: relative;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.st-progress__ring {
  stroke: var(--st-fill-active);
}

.st-progress__arc {
  stroke: var(--st-progress-color);
  /* 起点旋转到 12 点方向，符合进度条的读数习惯 */
  transform: rotate(-90deg);
  transform-origin: 50% 50%;
  transition: stroke-dashoffset var(--transition);
}

.st-progress__circle .st-progress__indicator {
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--foreground);
  font-size: var(--st-font-medium);
}

.st-progress__indicator {
  font-variant-numeric: tabular-nums;
}

@media (prefers-reduced-motion: reduce) {
  .st-progress__fill,
  .st-progress__arc {
    transition: none;
  }
}
</style>
