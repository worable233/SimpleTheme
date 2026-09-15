<script setup lang="ts">
/**
 * StSelect — 下拉选择
 *
 * 用法：
 *   <StSelect v-model="layout" :options="options" />
 *   <StSelect v-model="id" :options="options" clearable placeholder="全部" />
 *   <StSelect v-model="type" :options="options" status="error" aria-label="类型" />
 *
 * 基于 reka-ui Select：键盘导航、typeahead、浮层焦点管理全部交给它，
 * 本组件只补外观与 clearable 这层扩展。触发器视觉对齐 StInput。
 */
import { computed, ref } from 'vue'
import {
  SelectContent,
  SelectIcon,
  SelectItem,
  SelectItemIndicator,
  SelectItemText,
  SelectPortal,
  SelectRoot,
  SelectTrigger,
  SelectValue,
  SelectViewport,
} from 'reka-ui'
import StIcon from './StIcon.vue'
import type { StOption, StSize, StStatus } from '../types'

defineOptions({ name: 'StSelect' })

const props = withDefaults(
  defineProps<{
    options: StOption[]
    /** 尺寸 */
    size?: StSize
    placeholder?: string
    disabled?: boolean
    /** 有值时显示清除按钮 */
    clearable?: boolean
    /** 校验状态 */
    status?: StStatus
    /** 无障碍名称；无可见 label 时必填 */
    ariaLabel?: string
  }>(),
  {
    size: 'medium',
    placeholder: '请选择',
  },
)

const model = defineModel<string | number | null>({ default: null })

const emit = defineEmits<{
  (e: 'change', value: string | number | null): void
}>()

const rootEl = ref<HTMLElement | null>(null)

const hasValue = computed(
  () => model.value !== '' && model.value !== null && model.value !== undefined,
)

const showClear = computed(() => props.clearable && hasValue.value && !props.disabled)

const classes = computed(() => [
  'st-select',
  `st-select--${props.size}`,
  {
    'st-select--disabled': props.disabled,
    'st-select--clearable': showClear.value,
    [`st-select--${props.status}`]: !!props.status,
  },
])

/** reka-ui 的回调参数是宽泛的 AcceptableValue，这里收窄成组件契约里的值域 */
function handleChange(value: unknown) {
  const next = (value ?? null) as string | number | null
  model.value = next
  emit('change', next)
}

function clear() {
  model.value = null
  emit('change', null)
  // 清除后把焦点还给触发器：面板不打开，键盘用户不会丢焦点
  rootEl.value?.querySelector<HTMLElement>('.st-select__trigger')?.focus()
}
</script>

<template>
  <div ref="rootEl" :class="classes">
    <SelectRoot :model-value="model" :disabled="disabled" @update:model-value="handleChange">
      <SelectTrigger
        class="st-select__trigger"
        :disabled="disabled"
        :aria-label="ariaLabel"
        :aria-invalid="status === 'error' ? true : undefined"
      >
        <SelectValue class="st-select__value" :placeholder="placeholder" />
        <SelectIcon class="st-select__icon">
          <StIcon name="chevron-down" :size="14" />
        </SelectIcon>
      </SelectTrigger>

      <!-- Teleport 到 body：脱离后台卡片的 overflow/层叠上下文 -->
      <SelectPortal to="body">
        <SelectContent
          class="st-select__content"
          :class="`st-select__content--${size}`"
          position="popper"
          side="bottom"
          align="start"
          :side-offset="4"
        >
          <SelectViewport class="st-select__viewport">
            <SelectItem
              v-for="option in options"
              :key="option.value"
              class="st-select__item"
              :class="{ 'is-selected': option.value === model }"
              :value="option.value"
              :disabled="option.disabled"
            >
              <SelectItemIndicator class="st-select__indicator">
                <StIcon name="check" :size="14" />
              </SelectItemIndicator>
              <SelectItemText>{{ option.label }}</SelectItemText>
            </SelectItem>
          </SelectViewport>
        </SelectContent>
      </SelectPortal>
    </SelectRoot>

    <!-- 作为触发器兄弟节点而非子节点：button 不能嵌套 button，
         且点击它天然不会打开面板 -->
    <button
      v-if="showClear"
      type="button"
      class="st-select__clear"
      aria-label="清除"
      tabindex="-1"
      @mousedown.prevent
      @click="clear"
    >
      <StIcon name="x" :size="14" />
    </button>
  </div>
</template>

<style scoped>
.st-select {
  --st-select-height: var(--st-height-medium);
  --st-select-font: var(--st-font-medium);
  --st-select-pad: 10px;

  position: relative;
  display: inline-flex;
  align-items: center;
  box-sizing: border-box;
  width: 100%;
  font-size: var(--st-select-font);
}

/* ==================== 尺寸 ==================== */
.st-select--tiny {
  --st-select-height: var(--st-height-tiny);
  --st-select-font: var(--st-font-tiny);
  --st-select-pad: 7px;
}

.st-select--small {
  --st-select-height: var(--st-height-small);
  --st-select-font: var(--st-font-small);
  --st-select-pad: 9px;
}

.st-select--large {
  --st-select-height: var(--st-height-large);
  --st-select-font: var(--st-font-large);
  --st-select-pad: 12px;
}

/* ==================== 触发器 ==================== */
.st-select__trigger {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  box-sizing: border-box;
  width: 100%;
  height: var(--st-select-height);
  padding: 0 var(--st-select-pad);
  padding-right: 26px;
  border: 1px solid var(--st-border);
  border-radius: var(--radius-medium);
  background-color: var(--st-fill);
  color: var(--st-text);
  font-family: inherit;
  font-size: inherit;
  line-height: 1;
  text-align: left;
  cursor: pointer;
  transition:
    border-color var(--transition-fast),
    box-shadow var(--transition-fast);
}

.st-select--tiny .st-select__trigger,
.st-select--small .st-select__trigger {
  border-radius: var(--radius-small);
}

.st-select__trigger:hover:not(:disabled) {
  border-color: var(--st-border-hover);
}

.st-select__trigger:focus-visible,
.st-select__trigger[data-state='open'] {
  outline: none;
  border-color: var(--ring);
  box-shadow: var(--st-focus-ring);
}

.st-select__trigger:disabled,
.st-select--disabled .st-select__trigger {
  cursor: not-allowed;
  opacity: 0.5;
}

/* 校验状态：边色表达，聚焦时不被 --ring 覆盖 */
.st-select--error .st-select__trigger {
  border-color: var(--danger);
}

.st-select--error .st-select__trigger:focus-visible,
.st-select--error .st-select__trigger[data-state='open'] {
  border-color: var(--danger);
  box-shadow: 0 0 0 2px color-mix(in srgb, var(--danger) 20%, transparent);
}

.st-select--warning .st-select__trigger {
  border-color: var(--warning);
}

.st-select--success .st-select__trigger {
  border-color: var(--success);
}

.st-select__value {
  flex: 1 1 auto;
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

/* reka-ui 无值时给 SelectValue 加 data-placeholder */
.st-select__value[data-placeholder] {
  color: var(--st-placeholder);
}

.st-select__icon {
  position: absolute;
  right: var(--st-select-pad);
  display: inline-flex;
  align-items: center;
  color: var(--st-placeholder);
  pointer-events: none;
}

/* 有清除按钮时让出位置，图标与按钮不重叠 */
.st-select--clearable .st-select__trigger {
  padding-right: 48px;
}

.st-select--clearable .st-select__icon {
  right: calc(var(--st-select-pad) - 2px);
}

.st-select__clear {
  position: absolute;
  right: 24px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 16px;
  height: 16px;
  padding: 0;
  border: none;
  border-radius: var(--radius-full);
  background: transparent;
  color: var(--st-placeholder);
  cursor: pointer;
  transition: color var(--transition-fast);
}

.st-select__clear:hover {
  color: var(--st-text);
}

/* ==================== 下拉面板 ==================== */
/* 面板被 Teleport 到 body，拿不到 .st-select 上的私有变量，尺寸必须自带 */
.st-select__content {
  z-index: var(--st-z-dropdown);
  box-sizing: border-box;
  min-width: var(--reka-select-trigger-width);
  border: var(--st-popover-border);
  border-radius: var(--radius-medium);
  background-color: var(--st-popover-bg);
  box-shadow: var(--st-popover-shadow);
  color: var(--st-text);
}

.st-select__content--tiny {
  font-size: var(--st-font-tiny);
}

.st-select__content--small {
  font-size: var(--st-font-small);
}

.st-select__content--medium {
  font-size: var(--st-font-medium);
}

.st-select__content--large {
  font-size: var(--st-font-large);
}

/* 只做透明度动画：定位由 popper 用 transform 完成，动 transform 会打架 */
.st-select__content[data-state='open'] {
  animation: st-select-in 0.12s var(--ease-standard);
}

@keyframes st-select-in {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}

.st-select__viewport {
  padding: 4px;
  max-height: var(--reka-select-content-available-height, 320px);
  overflow-y: auto;
}

.st-select__item {
  position: relative;
  display: flex;
  align-items: center;
  box-sizing: border-box;
  padding: 6px 8px 6px 26px;
  border-radius: var(--radius-small);
  color: var(--st-text);
  font-size: inherit;
  line-height: 1.4;
  cursor: pointer;
  user-select: none;
  outline: none;
}

.st-select__item[data-highlighted] {
  background-color: var(--st-fill-hover);
}

.st-select__item.is-selected {
  background-color: var(--accent);
  font-weight: 500;
}

.st-select__item[data-disabled] {
  color: var(--st-text-disabled);
  cursor: not-allowed;
}

.st-select__indicator {
  position: absolute;
  left: 8px;
  display: inline-flex;
  align-items: center;
  color: var(--primary);
}

@media (prefers-reduced-motion: reduce) {
  .st-select__trigger,
  .st-select__clear {
    transition: none;
  }

  .st-select__content[data-state='open'] {
    animation: none;
  }
}
</style>
