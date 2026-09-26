<script setup lang="ts">
/**
 * StSwitch — 开关
 *
 * 用法：
 *   <StSwitch v-model="enabled">启用评论</StSwitch>
 *   <StSwitch v-model="dark" size="small" aria-label="暗色模式" />
 *
 * 基于 reka-ui SwitchRoot：键盘 Space / Enter 切换、role=switch 由它提供。
 * 标签文本走默认插槽，不传则为纯轨道。
 */
import { computed, useSlots } from 'vue'
import { SwitchRoot, SwitchThumb } from 'reka-ui'
import type { StSize } from '../types'

defineOptions({ name: 'StSwitch' })

const props = withDefaults(
  defineProps<{
    /** 尺寸 */
    size?: StSize
    disabled?: boolean
    /** 无障碍名称；无可见插槽文本时必填 */
    ariaLabel?: string
  }>(),
  {
    size: 'medium',
  },
)

const model = defineModel<boolean>({ default: false })

const slots = useSlots()

const classes = computed(() => [
  'st-switch',
  `st-switch--${props.size}`,
  {
    'is-checked': model.value,
    'st-switch--disabled': props.disabled,
  },
])
</script>

<template>
  <SwitchRoot v-model="model" :class="classes" :disabled="disabled" :aria-label="ariaLabel">
    <span class="st-switch__track">
      <SwitchThumb class="st-switch__thumb" />
    </span>
    <span v-if="slots.default" class="st-switch__label"><slot /></span>
  </SwitchRoot>
</template>

<style scoped>
.st-switch {
  /* 轨道尺寸随档位走；滑块位移由这些变量推导，避免写死 4 组 transform。
   * --st-switch-thumb-pressed 对齐 Naive：按下态宽度 = 常态 + 6px
   * （Naive buttonWidthPressed - buttonWidth 固定为 6px，见 switch/styles/_common.ts）。*/
  --st-switch-track-w: 38px;
  --st-switch-track-h: 20px;
  --st-switch-thumb: 16px;
  --st-switch-thumb-pressed: 22px;

  display: inline-flex;
  align-items: center;
  gap: 8px;
  box-sizing: border-box;
  padding: 0;
  border: none;
  border-radius: var(--radius-small);
  background: transparent;
  color: var(--st-text);
  font-family: inherit;
  font-size: var(--st-font-medium);
  line-height: 1.4;
  text-align: left;
  cursor: pointer;
}

.st-switch:focus-visible {
  outline: none;
  box-shadow: var(--st-focus-ring);
}

.st-switch--disabled {
  cursor: not-allowed;
  opacity: 0.5;
}

/* ==================== 尺寸 ==================== */
.st-switch--tiny {
  --st-switch-track-w: 26px;
  --st-switch-track-h: 14px;
  --st-switch-thumb: 10px;
  --st-switch-thumb-pressed: 16px;

  gap: 6px;
  font-size: var(--st-font-tiny);
}

.st-switch--small {
  --st-switch-track-w: 32px;
  --st-switch-track-h: 18px;
  --st-switch-thumb: 14px;
  --st-switch-thumb-pressed: 20px;

  font-size: var(--st-font-small);
}

.st-switch--large {
  --st-switch-track-w: 44px;
  --st-switch-track-h: 24px;
  --st-switch-thumb: 20px;
  --st-switch-thumb-pressed: 26px;

  font-size: var(--st-font-large);
}

/* ==================== 轨道与滑块 ==================== */
.st-switch__track {
  position: relative;
  flex: none;
  box-sizing: border-box;
  width: var(--st-switch-track-w);
  height: var(--st-switch-track-h);
  /* Naive 的 rail 会裁掉溢出：按下拉伸时滑块被轨道边缘裁切，形成"顶住"的感觉 */
  overflow: hidden;
  border-radius: var(--radius-full);
  background-color: var(--st-fill-active);
  transition: background-color 0.3s var(--ease-in-out);
}

.st-switch.is-checked .st-switch__track {
  background-color: var(--primary);
}

.st-switch__thumb {
  position: absolute;
  top: 2px;
  left: 2px;
  display: block;
  /* Naive 的 rubber-band：滑块常态宽度 = thumb；
   * 按下时把 max-width 放开到 thumb-pressed（= 常态 + 6px），于是横向拉伸。 */
  width: var(--st-switch-thumb);
  height: var(--st-switch-thumb);
  max-width: var(--st-switch-thumb);
  border-radius: var(--radius-full);
  background-color: var(--card);
  box-shadow: var(--shadow-small);
  transition:
    left 0.3s var(--ease-in-out),
    max-width 0.3s var(--ease-in-out),
    background-color 0.3s var(--ease-in-out);
}

.st-switch.is-checked .st-switch__thumb {
  left: calc(var(--st-switch-track-w) - var(--st-switch-thumb) - 2px);
}

/* 橡胶动画：按下瞬间横向拉长（宽度而非 scale），松开回弹 */
.st-switch:not(.st-switch--disabled) .st-switch__track:active .st-switch__thumb {
  max-width: var(--st-switch-thumb-pressed);
}

.st-switch.is-checked:not(.st-switch--disabled)
  .st-switch__track:active
  .st-switch__thumb {
  left: calc(var(--st-switch-track-w) - var(--st-switch-thumb-pressed) - 2px);
}

.st-switch__label {
  min-width: 0;
}

@media (prefers-reduced-motion: reduce) {
  .st-switch__track,
  .st-switch__thumb {
    transition: none;
  }

  /* 关闭橡胶拉伸：按下不再改变宽度与位移 */
  .st-switch:not(.st-switch--disabled) .st-switch__track:active .st-switch__thumb {
    max-width: var(--st-switch-thumb);
  }

  .st-switch.is-checked:not(.st-switch--disabled)
    .st-switch__track:active
    .st-switch__thumb {
    left: calc(var(--st-switch-track-w) - var(--st-switch-thumb) - 2px);
  }
}
</style>
