<script setup lang="ts">
/**
 * StSegmented — 分段控件
 *
 * 用法：
 *   <StSegmented v-model="view" :options="[{ label: '列表', value: 'list' }]" />
 *   <StSegmented v-model="range" :options="ranges" block size="small" />
 *
 * 语义按单选组实现（role=radiogroup + 内部 role=radio），键盘左右方向键移动并选中；
 * 视觉上是"轨道 + 浮起块"。选项来自 options，与 StTabs type="segment" 视觉相近但
 * 语义不同：这里不承载面板，只表达取值。
 */
import { computed } from 'vue'
import { RadioGroupItem, RadioGroupRoot } from 'reka-ui'
import type { StOption, StSize } from '../types'

defineOptions({ name: 'StSegmented' })

const props = withDefaults(
  defineProps<{
    options: StOption[]
    /** 尺寸 */
    size?: StSize
    disabled?: boolean
    /** 等分撑满父容器宽度 */
    block?: boolean
    /** 无障碍名称；无可见 label 时必填 */
    ariaLabel?: string
  }>(),
  {
    size: 'medium',
    block: false,
  },
)

const model = defineModel<string | number>()

const classes = computed(() => [
  'st-segmented',
  `st-segmented--${props.size}`,
  {
    'st-segmented--block': props.block,
    'st-segmented--disabled': props.disabled,
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
    orientation="horizontal"
    :disabled="disabled"
    :aria-label="ariaLabel"
    @update:model-value="handleChange"
  >
    <RadioGroupItem
      v-for="option in options"
      :key="option.value"
      class="st-segmented__item"
      :class="{ 'is-checked': option.value === model }"
      :value="option.value"
      :disabled="option.disabled"
    >
      <span class="st-segmented__label">{{ option.label }}</span>
    </RadioGroupItem>
  </RadioGroupRoot>
</template>

<style scoped>
.st-segmented {
  --st-segmented-height: var(--st-height-medium);

  display: inline-flex;
  align-items: stretch;
  gap: 2px;
  box-sizing: border-box;
  padding: 2px;
  border-radius: var(--radius-medium);
  background-color: var(--st-fill-active);
  font-size: var(--st-font-medium);
}

.st-segmented--block {
  display: flex;
  width: 100%;
}

.st-segmented--disabled {
  opacity: 0.5;
}

/* ==================== 尺寸 ==================== */
.st-segmented--tiny {
  --st-segmented-height: var(--st-height-tiny);

  font-size: var(--st-font-tiny);
}

.st-segmented--small {
  --st-segmented-height: var(--st-height-small);

  font-size: var(--st-font-small);
}

.st-segmented--large {
  --st-segmented-height: var(--st-height-large);

  font-size: var(--st-font-large);
}

/* ==================== 分段 ==================== */
.st-segmented__item {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  box-sizing: border-box;
  min-width: 0;
  height: calc(var(--st-segmented-height) - 4px);
  padding: 0 14px;
  border: none;
  border-radius: var(--radius-small);
  background-color: transparent;
  color: var(--st-placeholder);
  font-family: inherit;
  font-size: inherit;
  line-height: 1;
  white-space: nowrap;
  cursor: pointer;
  transition:
    background-color var(--transition-fast),
    color var(--transition-fast),
    box-shadow var(--transition-fast);
}

.st-segmented--tiny .st-segmented__item,
.st-segmented--small .st-segmented__item {
  padding: 0 10px;
}

.st-segmented--large .st-segmented__item {
  padding: 0 18px;
}

.st-segmented--block .st-segmented__item {
  flex: 1 1 0;
}

.st-segmented__item:hover:not([data-disabled]):not(.is-checked) {
  color: var(--st-text);
}

.st-segmented__item:focus-visible {
  outline: none;
  box-shadow: var(--st-focus-ring);
}

.st-segmented__item.is-checked {
  background-color: var(--st-fill);
  box-shadow: var(--shadow-small);
  color: var(--st-text);
  font-weight: 500;
}

.st-segmented__item[data-disabled] {
  color: var(--st-text-disabled);
  cursor: not-allowed;
}

.st-segmented__label {
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
}

@media (prefers-reduced-motion: reduce) {
  .st-segmented__item {
    transition: none;
  }
}
</style>
