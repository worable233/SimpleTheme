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
  /* 轨道尺寸随档位走；滑块位移由这些变量推导，避免写死 4 组 transform */
  --st-switch-track-w: 38px;
  --st-switch-track-h: 20px;
  --st-switch-thumb: 16px;

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

  gap: 6px;
  font-size: var(--st-font-tiny);
}

.st-switch--small {
  --st-switch-track-w: 32px;
  --st-switch-track-h: 18px;
  --st-switch-thumb: 14px;

  font-size: var(--st-font-small);
}

.st-switch--large {
  --st-switch-track-w: 44px;
  --st-switch-track-h: 24px;
  --st-switch-thumb: 20px;

  font-size: var(--st-font-large);
}

/* ==================== 轨道与滑块 ==================== */
.st-switch__track {
  position: relative;
  flex: none;
  box-sizing: border-box;
  width: var(--st-switch-track-w);
  height: var(--st-switch-track-h);
  border-radius: var(--radius-full);
  background-color: var(--st-fill-active);
  transition: background-color var(--transition-fast);
}

.st-switch.is-checked .st-switch__track {
  background-color: var(--primary);
}

.st-switch__thumb {
  position: absolute;
  top: 50%;
  left: 2px;
  display: block;
  width: var(--st-switch-thumb);
  height: var(--st-switch-thumb);
  border-radius: var(--radius-full);
  background-color: var(--card);
  box-shadow: var(--shadow-small);
  transform: translateY(-50%);
  transition: transform var(--transition-fast);
}

.st-switch.is-checked .st-switch__thumb {
  transform: translateY(-50%)
    translateX(calc(var(--st-switch-track-w) - var(--st-switch-thumb) - 4px));
}

.st-switch__label {
  min-width: 0;
}

@media (prefers-reduced-motion: reduce) {
  .st-switch__track,
  .st-switch__thumb {
    transition: none;
  }
}
</style>
