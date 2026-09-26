<script setup lang="ts">
/**
 * StProgress — 进度条
 *
 * 用法：
 *   <StProgress :percentage="42" />
 *   <StProgress :percentage="80" status="warning" />
 *   <StProgress :percentage="60" processing />            <!-- 进行中流光 -->
 *   <StProgress type="circle" :percentage="60" :gap-degree="90" />
 *   <StProgress :percentage="30" unit="项" />
 *
 * 进度值自动 clamp 到 0–100，调用方无需自己兜底；颜色由 status 决定，
 * 不传时回落到 --primary。
 *
 * 对齐 Naive UI 的进度能力：
 *   - `processing` 进行中流光（Naive progress/styles 的 loading 光带）
 *   - `railColor` 轨道色（Naive Line/Circle 的 railColor）
 *   - 环形 `gapDegree` / `gapOffsetDegree`：开口角度与起始角
 *     （Naive progress Circle 的 gapDegree / offsetDegree）
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
    /** 进行中：填充上叠加一条循环流光 */
    processing?: boolean
    /** 轨道颜色，覆盖默认的 --st-fill-active */
    railColor?: string
    /** 环形开口角度（deg）；0 为整圆，常见 90 表示缺口仪表盘 */
    gapDegree?: number
    /** 环形起始角偏移（deg），从 12 点方向顺时针计 */
    gapOffsetDegree?: number
    /** 指示文本单位，默认 '%' */
    unit?: string
  }>(),
  {
    percentage: 0,
    type: 'line',
    showIndicator: true,
    height: 6,
    strokeWidth: 4,
    processing: false,
    gapDegree: 0,
    gapOffsetDegree: 0,
    unit: '%',
  },
)

/** 环形视口固定尺寸，几何计算只依赖它和 strokeWidth */
const CIRCLE_SIZE = 80

const clamped = computed(() => Math.min(100, Math.max(0, props.percentage)))

const label = computed(() => `${Math.round(clamped.value)}${props.unit}`)

const radius = computed(() => (CIRCLE_SIZE - props.strokeWidth) / 2)

/** 环形有效弧长（扣除缺口）；缺口为 0 时即整圆 */
const arcLength = computed(() => {
  const full = 2 * Math.PI * radius.value
  const gap = Math.min(360, Math.max(0, props.gapDegree))
  return full * ((360 - gap) / 360)
})

const dashOffset = computed(() => arcLength.value * (1 - clamped.value / 100))

/** 起始角：默认 12 点（-90deg），再叠加开口偏移 */
const startAngle = computed(() => -90 + props.gapOffsetDegree)

const lineStyle = computed(() => ({ height: `${props.height}px` }))
const circleStyle = computed(() => ({ width: `${CIRCLE_SIZE}px`, height: `${CIRCLE_SIZE}px` }))
const trackStyle = computed(() => (props.railColor ? { backgroundColor: props.railColor } : undefined))
const ringStyle = computed(() => (props.railColor ? { stroke: props.railColor } : undefined))

const classes = computed(() => [
  'st-progress',
  `st-progress--${props.type}`,
  {
    [`st-progress--${props.status}`]: !!props.status,
    'st-progress--processing': props.processing,
  },
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
      <div class="st-progress__track" :style="[lineStyle, trackStyle]">
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
            :stroke-dasharray="arcLength"
            :style="ringStyle"
          />
          <circle
            class="st-progress__arc"
            :cx="CIRCLE_SIZE / 2"
            :cy="CIRCLE_SIZE / 2"
            :r="radius"
            fill="none"
            :stroke-width="strokeWidth"
            stroke-linecap="round"
            :stroke-dasharray="arcLength"
            :stroke-dashoffset="dashOffset"
            :style="{ transform: `rotate(${startAngle}deg)` }"
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
  position: relative;
  height: 100%;
  overflow: hidden;
  border-radius: inherit;
  background-color: var(--st-progress-color);
  transition: width var(--transition);
}

/* 进行中：填充内部循环流光（Naive loading 光带的自绘等价物）。
 * 高光色由进度色与画布色混合派生，不引入新的颜色字面量。 */
.st-progress--processing .st-progress__fill::after {
  content: '';
  position: absolute;
  inset: 0;
  background: linear-gradient(
    90deg,
    transparent 0%,
    color-mix(in srgb, var(--card) 55%, transparent) 50%,
    transparent 100%
  );
  transform: translateX(-100%);
  animation: st-progress-flux 1.4s linear infinite;
}

@keyframes st-progress-flux {
  to {
    transform: translateX(100%);
  }
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
  /* 起点旋转到 12 点方向（可叠加 gapOffsetDegree），由内联 style 给出 */
  transform-origin: 50% 50%;
  transition:
    stroke-dashoffset var(--transition),
    transform var(--transition);
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

  .st-progress--processing .st-progress__fill::after {
    animation: none;
  }
}
</style>
