<script setup lang="ts">
/**
 * StSpinner — 加载指示器
 *
 * 用法：
 *   <StSpinner />
 *   <StSpinner size="large" />
 *   <StSpinner :show="loading" :delay="200" description="加载中" />
 *
 * 纯装饰元素，无交互，因此 aria-hidden 并交由调用方（StButton 等）
 * 通过 aria-busy 表达状态。
 *
 * 对齐 Naive Spin：`show` 控制显隐、`delay` 延迟出现（避免快请求闪一下）、
 * `description` 在图下方补一行说明、`strokeWidth` 对应 `stroke`。
 */
import { computed, onBeforeUnmount, ref, watch } from 'vue'
import type { StSize } from '../types'

defineOptions({ name: 'StSpinner' })

const props = withDefaults(
  defineProps<{
    /** 尺寸档位或像素值；省略时按 medium */
    size?: StSize | number
    /** 描边宽度（视图单位） */
    stroke?: number
    /** 是否显示；配合 delay 可做「慢请求才出现」 */
    show?: boolean
    /** 延迟多少毫秒后才真正显示（Naive Spin 的 delay） */
    delay?: number
    /** 指示器下方的说明文字 */
    description?: string
  }>(),
  {
    // size 是联合类型，字符串默认值会被 eslint 的
    // vue/require-valid-default-prop 误判，故默认值在 px 里兜底
    stroke: 2,
    show: true,
    delay: 0,
  },
)

const px = computed(() => {
  if (typeof props.size === 'number') return props.size
  return { tiny: 12, small: 14, medium: 16, large: 20 }[props.size ?? 'medium']
})

/** 延迟显隐：delay 期间保持隐藏，避免瞬时加载闪烁 */
const visible = ref(props.delay <= 0 ? props.show : false)
let timer: ReturnType<typeof setTimeout> | undefined

watch(
  () => [props.show, props.delay] as const,
  ([show, delay]) => {
    if (timer) clearTimeout(timer)
    if (!show) {
      visible.value = false
      return
    }
    if (delay <= 0) {
      visible.value = true
      return
    }
    timer = setTimeout(() => {
      visible.value = true
    }, delay)
  },
  { immediate: true },
)

onBeforeUnmount(() => {
  if (timer) clearTimeout(timer)
})
</script>

<template>
  <span
    v-if="visible"
    class="st-spinner"
    :role="description ? 'status' : undefined"
    :aria-label="description ? description : undefined"
    :aria-hidden="description ? undefined : true"
  >
    <svg
      class="st-spinner__svg"
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
    <span v-if="description" class="st-spinner__description">{{ description }}</span>
  </span>
</template>

<style scoped>
.st-spinner {
  display: inline-flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 6px;
  flex: none;
  line-height: 0;
}

.st-spinner__svg {
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

.st-spinner__description {
  color: var(--muted-foreground);
  font-size: var(--st-font-small);
  line-height: 1.4;
}

@keyframes st-spinner-rotate {
  to {
    transform: rotate(360deg);
  }
}

@media (prefers-reduced-motion: reduce) {
  .st-spinner__svg {
    animation-duration: 3s;
  }
}
</style>
