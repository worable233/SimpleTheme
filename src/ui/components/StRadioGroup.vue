<script setup lang="ts">
/**
 * StRadioGroup — 单选组
 *
 * 用法：
 *   <StRadioGroup v-model="size" :options="sizeOptions" />
 *   <StRadioGroup v-model="layout" :options="layoutOptions" direction="horizontal" />
 *
 * 基于 reka-ui RadioGroup：role=radiogroup / role=radio、上下左右方向键移动并选中
 * 由它提供。选项文本来自 options，不支持逐项插槽——要自定义请直接用 reka-ui。
 */
import { computed } from 'vue'
import { RadioGroupIndicator, RadioGroupItem, RadioGroupRoot } from 'reka-ui'
import type { StOption, StSize } from '../types'

defineOptions({ name: 'StRadioGroup' })

const props = withDefaults(
  defineProps<{
    options: StOption[]
    /** 尺寸 */
    size?: StSize
    disabled?: boolean
    /** 排列方向 */
    direction?: 'horizontal' | 'vertical'
    /** 无障碍名称；无可见 label 时必填 */
    ariaLabel?: string
  }>(),
  {
    size: 'medium',
    direction: 'vertical',
  },
)

const model = defineModel<string | number>()

const orientation = computed(() => (props.direction === 'horizontal' ? 'horizontal' : 'vertical'))

const classes = computed(() => [
  'st-radio-group',
  `st-radio-group--${props.size}`,
  `st-radio-group--${props.direction}`,
  {
    'st-radio-group--disabled': props.disabled,
  },
])

/** reka-ui 回传的是宽泛的 AcceptableValue，这里只接受组件契约里的值域 */
function handleChange(value: unknown) {
  if (typeof value === 'string' || typeof value === 'number') model.value = value
}
</script>

<template>
  <RadioGroupRoot
    :model-value="model"
    :class="classes"
    :orientation="orientation"
    :disabled="disabled"
    :aria-label="ariaLabel"
    @update:model-value="handleChange"
  >
    <RadioGroupItem
      v-for="option in options"
      :key="option.value"
      class="st-radio-group__item"
      :class="{ 'is-checked': option.value === model }"
      :value="option.value"
      :disabled="option.disabled"
    >
      <span class="st-radio-group__box">
        <RadioGroupIndicator class="st-radio-group__dot" />
      </span>
      <span class="st-radio-group__label">{{ option.label }}</span>
    </RadioGroupItem>
  </RadioGroupRoot>
</template>

<style scoped>
.st-radio-group {
  --st-radio-box: 18px;

  display: flex;
  flex-direction: column;
  gap: 10px;
  box-sizing: border-box;
  color: var(--st-text);
  font-size: var(--st-font-medium);
}

.st-radio-group--horizontal {
  flex-direction: row;
  align-items: center;
  flex-wrap: wrap;
  gap: 16px;
}

.st-radio-group--disabled {
  cursor: not-allowed;
}

/* ==================== 尺寸 ==================== */
.st-radio-group--tiny {
  --st-radio-box: 14px;

  gap: 8px;
  font-size: var(--st-font-tiny);
}

.st-radio-group--tiny.st-radio-group--horizontal {
  gap: 12px;
}

.st-radio-group--small {
  --st-radio-box: 16px;

  font-size: var(--st-font-small);
}

.st-radio-group--large {
  --st-radio-box: 20px;

  font-size: var(--st-font-large);
}

/* ==================== 选项 ==================== */
.st-radio-group__item {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  box-sizing: border-box;
  padding: 0;
  border: none;
  border-radius: var(--radius-small);
  background: transparent;
  color: inherit;
  font-family: inherit;
  font-size: inherit;
  line-height: 1.4;
  text-align: left;
  cursor: pointer;
}

.st-radio-group__item[data-disabled] {
  color: var(--st-text-disabled);
  cursor: not-allowed;
}

.st-radio-group__box {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex: none;
  box-sizing: border-box;
  width: var(--st-radio-box);
  height: var(--st-radio-box);
  border: 1px solid var(--st-border);
  border-radius: var(--radius-full);
  background-color: var(--st-fill);
  transition:
    border-color var(--transition-fast),
    box-shadow var(--transition-fast);
}

.st-radio-group__item:hover:not([data-disabled]) .st-radio-group__box {
  border-color: var(--st-border-hover);
}

.st-radio-group__item:focus-visible {
  outline: none;
}

.st-radio-group__item:focus-visible .st-radio-group__box {
  border-color: var(--ring);
  box-shadow: var(--st-focus-ring);
}

.st-radio-group__item.is-checked .st-radio-group__box {
  border-color: var(--primary);
}

/* 内点常驻 DOM（reka-ui 只在选中时渲染指示器），尺寸用减法保证各档位同心 */
.st-radio-group__dot {
  display: block;
  box-sizing: border-box;
  width: calc(var(--st-radio-box) - 8px);
  height: calc(var(--st-radio-box) - 8px);
  border-radius: var(--radius-full);
  background-color: var(--primary);
}

.st-radio-group__label {
  min-width: 0;
}

@media (prefers-reduced-motion: reduce) {
  .st-radio-group__box {
    transition: none;
  }
}
</style>
