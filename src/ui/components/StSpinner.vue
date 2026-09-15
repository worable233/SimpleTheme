<script setup lang="ts">
/**
 * StSpinner — 加载指示器
 *
 * 纯装饰元素，无交互，因此 aria-hidden 并交由调用方（StButton 等）
 * 通过 aria-busy 表达状态。
 */
import { computed } from 'vue'
import type { StSize } from '../types'

defineOptions({ name: 'StSpinner' })

const props = withDefaults(
  defineProps<{
    size?: StSize | number
    /** 描边宽度（视图单位） */
    stroke?: number
  }>(),
  {
    size: 'medium',
    stroke: 2,
  },
)

const px = computed(() => {
  if (typeof props.size === 'number') return props.size
  return { tiny: 12, small: 14, medium: 16, large: 20 }[props.size]
})
</script>

<template>
  <svg
    class="st-spinner"
    :width="px"
    :height="px"
    viewBox="0 0 24 24"
    fill="none"
    aria-hidden="true"
  >
    <circle
      class="st-spinner__track"
      cx="12"
      cy="12"
      r="9"
      :stroke-width="stroke"
      stroke-linecap="round"
    />
    <circle
      class="st-spinner__head"
      cx="12"
      cy="12"
      r="9"
      :stroke-width="stroke"
      stroke-linecap="round"
    />
  </svg>
</template>

<style scoped>
.st-spinner {
  display: block;
  flex: none;
  animation: st-spinner-rotate 1.6s linear infinite;
}

.st-spinner__track {
  stroke: currentColor;
  opacity: 0.2;
}

.st-spinner__head {
  stroke: currentColor;
  stroke-dasharray: 16 44;
}

@keyframes st-spinner-rotate {
  to {
    transform: rotate(360deg);
  }
}

@media (prefers-reduced-motion: reduce) {
  .st-spinner {
    animation-duration: 3s;
  }
}
</style>
