<script setup lang="ts">
/**
 * StCheckbox — 复选框
 *
 * 用法：
 *   <StCheckbox v-model="agree">我已阅读并同意</StCheckbox>
 *   <StCheckbox v-model="all" indeterminate>全选</StCheckbox>
 *   <StCheckbox v-model="item.checked" aria-label="选择该项" />
 *
 * 基于 reka-ui CheckboxRoot：role=checkbox、Space 切换由它提供。
 * indeterminate 是纯展示态，由调用方单独维护——半选点击按"勾选"处理。
 */
import { computed, useSlots } from 'vue'
import { CheckboxIndicator, CheckboxRoot } from 'reka-ui'
import StMorphIcon from './StMorphIcon.vue'
import type { StSize } from '../types'

defineOptions({ name: 'StCheckbox' })

const props = withDefaults(
  defineProps<{
    /** 尺寸 */
    size?: StSize
    disabled?: boolean
    /** 半选态（如"全选"框）；值仍由 v-model 决定，点击后回到勾选 */
    indeterminate?: boolean
    /** 无障碍名称；无可见插槽文本时必填 */
    ariaLabel?: string
  }>(),
  {
    size: 'medium',
  },
)

const model = defineModel<boolean>({ default: false })

const slots = useSlots()

/** 图标跟着方框档位缩放，与 StTabs 的做法一致 */
const ICON_SIZE: Record<StSize, number> = { tiny: 10, small: 12, medium: 12, large: 14 }

const iconSize = computed(() => ICON_SIZE[props.size])

/** 把 indeterminate 作为受控状态喂给 reka-ui，它才知道渲染指示器并用 mixed 语义 */
const checkedState = computed<boolean | 'indeterminate'>(() =>
  props.indeterminate ? 'indeterminate' : model.value,
)

const classes = computed(() => [
  'st-checkbox',
  `st-checkbox--${props.size}`,
  {
    'is-checked': !!model.value || props.indeterminate,
    'st-checkbox--disabled': props.disabled,
  },
])

/** reka-ui 在点击半选态时回传 trueValue；此处只接受布尔，忽略 'indeterminate' */
function handleChange(value: unknown) {
  if (value === 'indeterminate') return
  model.value = value === true
}
</script>

<template>
  <CheckboxRoot
    :model-value="checkedState"
    :class="classes"
    :disabled="disabled"
    :aria-label="ariaLabel"
    @update:model-value="handleChange"
  >
    <span class="st-checkbox__box">
      <CheckboxIndicator class="st-checkbox__indicator">
        <StMorphIcon :name="indeterminate ? 'minus' : 'check'" :size="iconSize" :stroke="3" />
      </CheckboxIndicator>
    </span>
    <span v-if="slots.default" class="st-checkbox__label"><slot /></span>
  </CheckboxRoot>
</template>

<style scoped>
.st-checkbox {
  --st-checkbox-box: 18px;

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

.st-checkbox:focus-visible {
  outline: none;
  box-shadow: var(--st-focus-ring);
}

.st-checkbox--disabled {
  cursor: not-allowed;
  opacity: 0.5;
}

/* ==================== 尺寸 ==================== */
.st-checkbox--tiny {
  --st-checkbox-box: 14px;

  gap: 6px;
  font-size: var(--st-font-tiny);
}

.st-checkbox--small {
  --st-checkbox-box: 16px;

  font-size: var(--st-font-small);
}

.st-checkbox--large {
  --st-checkbox-box: 20px;

  font-size: var(--st-font-large);
}

/* ==================== 方框 ==================== */
.st-checkbox__box {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex: none;
  box-sizing: border-box;
  width: var(--st-checkbox-box);
  height: var(--st-checkbox-box);
  border: 1px solid var(--st-border);
  border-radius: var(--radius-small);
  background-color: var(--st-fill);
  color: var(--primary-foreground);
  transition:
    background-color var(--transition-fast),
    border-color var(--transition-fast);
}

.st-checkbox--tiny .st-checkbox__box {
  border-radius: var(--radius-xs);
}

.st-checkbox:hover:not(.st-checkbox--disabled) .st-checkbox__box {
  border-color: var(--st-border-hover);
}

.st-checkbox.is-checked .st-checkbox__box {
  border-color: var(--primary);
  background-color: var(--primary);
}

.st-checkbox__indicator {
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.st-checkbox__label {
  min-width: 0;
}

@media (prefers-reduced-motion: reduce) {
  .st-checkbox__box {
    transition: none;
  }
}
</style>
