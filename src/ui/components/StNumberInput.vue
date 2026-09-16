<script setup lang="ts">
/**
 * StNumberInput — 数字输入框
 *
 * 用法：
 *   <StNumberInput v-model="count" :min="1" :max="20" aria-label="文章数量" />
 *   <StNumberInput v-model="fontSize" suffix="px" :step="0.5" />
 *
 * 为什么不用原生 type="number"：滚轮会误改数值、本地化显示不可控、且与 StInput
 * 的表单校验样式冲突。这里统一用 type="text" + inputmode="decimal" 自行解析。
 *
 * 受控：始终以 modelValue 为准；非法输入不会写回 model（不留 NaN），
 * 失焦时回退为上一个合法值。
 */
import { computed, ref, useAttrs, watch } from 'vue'
import StIcon from './StIcon.vue'
import type { StSize, StStatus } from '../types'

// inheritAttrs:false —— 理由同 StInput：非声明属性（required/id/name…）
// 必须落到内层 <input>，否则原生校验与 <label for> 关联静默失效。
defineOptions({ name: 'StNumberInput', inheritAttrs: false })

const props = withDefaults(
  defineProps<{
    /** 最小值；不传则不做下限约束 */
    min?: number
    /** 最大值；不传则不做上限约束 */
    max?: number
    /** 步进值（方向键与上下按钮共用） */
    step?: number
    /** 尺寸 */
    size?: StSize
    disabled?: boolean
    readonly?: boolean
    /** 校验状态 */
    status?: StStatus
    placeholder?: string
    /** 单位后缀，如 'px' / '篇' */
    suffix?: string
    /** 是否显示上下步进按钮 */
    showControls?: boolean
    /** 无障碍名称；无可见 label 时必填 */
    ariaLabel?: string
  }>(),
  {
    step: 1,
    size: 'medium',
    showControls: true,
  },
)

const model = defineModel<number | null>({ default: null })

const emit = defineEmits<{
  (e: 'change', value: number | null): void
}>()

const inputEl = ref<HTMLInputElement | null>(null)

const attrs = useAttrs()

/** class/style 留在根 div */
const rootAttrs = computed(() => ({ class: attrs.class, style: attrs.style }))

/** 其余非声明属性下沉到内层 <input> */
const inputAttrs = computed(() =>
  Object.fromEntries(Object.entries(attrs).filter(([k]) => k !== 'class' && k !== 'style')),
)

/** 输入框文本态：允许 '-'、'1.' 这类中间态存在，不与 model 强绑定 */
const text = ref('')
/** 非法输入标红：只在编辑过程中成立，失焦即回退清除 */
const invalid = ref(false)
/** 聚焦期间外部改值不覆盖用户正在敲的文本 */
const focused = ref(false)

const blocked = computed(() => props.disabled || props.readonly)

/** step 的小数位数，用于收敛浮点残留（0.1 + 0.2） */
const stepDecimals = computed(() => {
  const raw = String(props.step)
  const dot = raw.indexOf('.')
  return dot === -1 ? 0 : raw.length - dot - 1
})

const iconSize = computed(() => (props.size === 'tiny' || props.size === 'small' ? 10 : 12))

const atMin = computed(
  () => props.min !== undefined && model.value !== null && model.value <= props.min,
)
const atMax = computed(
  () => props.max !== undefined && model.value !== null && model.value >= props.max,
)

const classes = computed(() => [
  'st-number-input',
  `st-number-input--${props.size}`,
  {
    'st-number-input--controls': props.showControls,
    'st-number-input--disabled': props.disabled,
    'st-number-input--readonly': props.readonly,
    'st-number-input--invalid': invalid.value,
    [`st-number-input--${props.status}`]: !!props.status,
  },
])

function format(value: number | null) {
  return value === null || value === undefined ? '' : String(value)
}

/** 只接受十进制字面量；'-' / '1.2.3' / 'abc' 一律返回 null（不提交） */
function parse(raw: string): number | null {
  const trimmed = raw.trim()
  if (!trimmed || !/^-?(\d+\.?\d*|\.\d+)$/.test(trimmed)) return null
  const value = Number(trimmed)
  return Number.isFinite(value) ? value : null
}

function round(value: number) {
  const factor = 10 ** stepDecimals.value
  return Math.round(value * factor) / factor
}

/** 无 min/max 时不做 clamp —— 不做无依据的边界假设 */
function clamp(value: number) {
  let next = value
  if (props.min !== undefined && next < props.min) next = props.min
  if (props.max !== undefined && next > props.max) next = props.max
  return round(next)
}

function commit(next: number | null) {
  if (next === model.value) return
  model.value = next
  emit('change', next)
}

// 外部改值（如重置表单）时同步文本；编辑中不打断输入
watch(
  () => model.value,
  (value) => {
    if (!focused.value) text.value = format(value)
  },
  { immediate: true },
)

function onFocus() {
  focused.value = true
}

function onInput(ev: Event) {
  const raw = (ev.target as HTMLInputElement).value
  text.value = raw
  const parsed = parse(raw)
  invalid.value = raw.trim() !== '' && parsed === null
  // 留空交给 blur 处理，避免边删边写导致 model 反复抖动
  if (parsed !== null) commit(clamp(parsed))
}

function onBlur() {
  focused.value = false
  invalid.value = false
  if (text.value.trim() === '') {
    // 清空即清值：number | null 契约里 null 是合法状态
    commit(null)
    text.value = format(model.value)
    return
  }
  const parsed = parse(text.value)
  // 非法输入回退到上一个合法值，绝不把 NaN 写进 model
  text.value = parsed === null ? format(model.value) : format(clamp(parsed))
  if (parsed !== null) commit(clamp(parsed))
}

function stepBy(direction: 1 | -1) {
  if (blocked.value) return
  if (model.value === null) {
    // 无值时落到对应边界（对应 Radix 的行为），避免第一下就跳过 min
    const edge = clamp(direction === 1 ? (props.min ?? 0) : (props.max ?? 0))
    text.value = format(edge)
    invalid.value = false
    commit(edge)
    return
  }
  const next = clamp(model.value + direction * props.step)
  text.value = format(next)
  invalid.value = false
  commit(next)
}

function onKeydown(ev: KeyboardEvent) {
  if (blocked.value) return
  if (ev.key === 'ArrowUp') {
    ev.preventDefault()
    stepBy(1)
  } else if (ev.key === 'ArrowDown') {
    ev.preventDefault()
    stepBy(-1)
  }
}

function focus() {
  inputEl.value?.focus()
}

defineExpose({ focus, el: inputEl })
</script>

<template>
  <div v-bind="rootAttrs" :class="classes">
    <input
      ref="inputEl"
      v-bind="inputAttrs"
      class="st-number-input__el"
      type="text"
      inputmode="decimal"
      role="spinbutton"
      autocomplete="off"
      :value="text"
      :placeholder="placeholder"
      :disabled="disabled"
      :readonly="readonly"
      :aria-label="ariaLabel"
      :aria-invalid="invalid || status === 'error' ? true : undefined"
      :aria-valuenow="model === null ? undefined : model"
      :aria-valuemin="min"
      :aria-valuemax="max"
      @input="onInput"
      @blur="onBlur"
      @focus="onFocus"
      @keydown="onKeydown"
    />

    <span v-if="suffix" class="st-number-input__suffix">{{ suffix }}</span>

    <span v-if="showControls" class="st-number-input__controls">
      <button
        type="button"
        class="st-number-input__step"
        aria-label="增加"
        tabindex="-1"
        :disabled="blocked || atMax"
        @mousedown.prevent
        @click="stepBy(1)"
      >
        <StIcon name="chevron-up" :size="iconSize" />
      </button>
      <button
        type="button"
        class="st-number-input__step"
        aria-label="减少"
        tabindex="-1"
        :disabled="blocked || atMin"
        @mousedown.prevent
        @click="stepBy(-1)"
      >
        <StIcon name="chevron-down" :size="iconSize" />
      </button>
    </span>
  </div>
</template>

<style scoped>
.st-number-input {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  box-sizing: border-box;
  width: 100%;
  height: var(--st-height-medium);
  padding: 0 10px;
  border: 1px solid var(--st-border);
  border-radius: var(--radius-medium);
  background-color: var(--st-fill);
  color: var(--st-text);
  font-size: var(--st-font-medium);
  transition:
    border-color var(--transition-fast),
    box-shadow var(--transition-fast);
}

.st-number-input:hover:not(.st-number-input--disabled) {
  border-color: var(--st-border-hover);
}

.st-number-input:focus-within {
  border-color: var(--ring);
  box-shadow: var(--st-focus-ring);
}

/* ==================== 尺寸 ==================== */
.st-number-input--tiny {
  height: var(--st-height-tiny);
  font-size: var(--st-font-tiny);
  padding: 0 7px;
  border-radius: var(--radius-small);
}

.st-number-input--small {
  height: var(--st-height-small);
  font-size: var(--st-font-small);
  padding: 0 9px;
  border-radius: var(--radius-small);
}

.st-number-input--large {
  height: var(--st-height-large);
  font-size: var(--st-font-large);
  padding: 0 12px;
}

/* 有步进按钮时收紧右侧内边距，按钮自身留出可点区域 */
.st-number-input--controls {
  padding-right: 4px;
}

.st-number-input--disabled {
  cursor: not-allowed;
  opacity: 0.5;
}

.st-number-input--readonly {
  background-color: var(--st-fill-active);
}

/* ==================== 校验状态 ==================== */
.st-number-input--invalid,
.st-number-input--error {
  border-color: var(--danger);
}

.st-number-input--invalid:focus-within,
.st-number-input--error:focus-within {
  border-color: var(--danger);
  box-shadow: 0 0 0 2px color-mix(in srgb, var(--danger) 20%, transparent);
}

.st-number-input--warning {
  border-color: var(--warning);
}

.st-number-input--success {
  border-color: var(--success);
}

/* ==================== 内层 ==================== */
.st-number-input__el {
  flex: 1 1 auto;
  min-width: 0;
  height: 100%;
  padding: 0;
  border: none;
  outline: none;
  background: transparent;
  color: inherit;
  font-family: inherit;
  font-size: inherit;
  line-height: 1;
}

.st-number-input__el::placeholder {
  color: var(--st-placeholder);
}

.st-number-input__el:disabled {
  cursor: not-allowed;
}

.st-number-input__suffix {
  flex: none;
  color: var(--st-placeholder);
  font-size: 0.9em;
}

.st-number-input__controls {
  display: flex;
  flex-direction: column;
  flex: none;
  gap: 1px;
  height: 100%;
  padding: 2px 0;
}

.st-number-input__step {
  display: flex;
  align-items: center;
  justify-content: center;
  flex: 1 1 0;
  width: 16px;
  padding: 0;
  border: none;
  border-radius: var(--radius-xs);
  background: transparent;
  color: var(--st-placeholder);
  cursor: pointer;
  transition:
    background-color var(--transition-fast),
    color var(--transition-fast);
}

.st-number-input__step:hover:not(:disabled) {
  background-color: var(--st-fill-hover);
  color: var(--st-text);
}

.st-number-input__step:focus-visible {
  outline: none;
  box-shadow: var(--st-focus-ring);
}

.st-number-input__step:disabled {
  color: var(--st-text-disabled);
  cursor: not-allowed;
  opacity: 0.5;
}

@media (prefers-reduced-motion: reduce) {
  .st-number-input,
  .st-number-input__step {
    transition: none;
  }
}
</style>
