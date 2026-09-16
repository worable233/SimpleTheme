<script setup lang="ts">
/**
 * StInput — 单行输入框
 *
 * 用法：
 *   <StInput v-model="name" placeholder="昵称" />
 *   <StInput v-model="q" clearable @enter="search" />
 *   <StInput v-model="n" type="number" status="error">
 *     <template #prefix>…</template>
 *   </StInput>
 *
 * 受控：始终以 modelValue 为准；未绑定 v-model 时输入不会回显。
 */
import { computed, ref, useAttrs, useSlots } from 'vue'
import StIcon from './StIcon.vue'
import type { StSize, StStatus } from '../types'

// inheritAttrs:false —— 默认的透传会把属性落到根 div 上，导致 required /
// minlength / id / name / inputmode 这类**非声明 prop** 永远到不了内层
// 原生 <input>：浏览器原生校验静默失效，<label for> 也关联不上。
// 这里手动分流：class/style 留根元素（布局钩子），其余给内层 input。
defineOptions({ name: 'StInput', inheritAttrs: false })

const props = withDefaults(
  defineProps<{
    /** 尺寸 */
    size?: StSize
    /** 原生 input type */
    type?: string
    placeholder?: string
    disabled?: boolean
    readonly?: boolean
    /** 有值时显示清除按钮 */
    clearable?: boolean
    /** 校验状态 */
    status?: StStatus
    /** 圆角胶囊 */
    round?: boolean
    /** 等宽字体，适合 token / 代码类输入 */
    mono?: boolean
    maxlength?: number
    autocomplete?: string
    /** 无障碍名称；无可见 label 时必填 */
    ariaLabel?: string
  }>(),
  {
    size: 'medium',
    type: 'text',
    clearable: false,
  },
)

const model = defineModel<string | number | undefined>({ default: '' })

const emit = defineEmits<{
  (e: 'enter', ev: KeyboardEvent): void
  (e: 'focus', ev: FocusEvent): void
  (e: 'blur', ev: FocusEvent): void
  (e: 'clear'): void
}>()

const slots = useSlots()
const inputEl = ref<HTMLInputElement | null>(null)

const attrs = useAttrs()

/** class/style 留在根 div：调用方仍能传布局类（如给外层定宽） */
const rootAttrs = computed(() => ({ class: attrs.class, style: attrs.style }))

/** 其余非声明属性（required/minlength/id/name/inputmode/pattern/
 *  aria-describedby…）一律下沉到内层原生 input */
const inputAttrs = computed(() => {
  const { class: _class, style: _style, ...rest } = attrs
  return rest
})

const classes = computed(() => [
  'st-input',
  `st-input--${props.size}`,
  {
    'st-input--round': props.round,
    'st-input--mono': props.mono,
    'st-input--disabled': props.disabled,
    'st-input--readonly': props.readonly,
    [`st-input--${props.status}`]: !!props.status,
  },
])

const hasValue = computed(
  () => model.value !== '' && model.value !== null && model.value !== undefined,
)

const showClear = computed(() => props.clearable && hasValue.value && !props.disabled && !props.readonly)

function clear() {
  model.value = ''
  emit('clear')
  inputEl.value?.focus()
}

function onEnter(ev: KeyboardEvent) {
  emit('enter', ev)
}

function focus() {
  inputEl.value?.focus()
}

defineExpose({ focus, el: inputEl })
</script>

<template>
  <div v-bind="rootAttrs" :class="classes">
    <span v-if="slots.prefix" class="st-input__affix st-input__affix--prefix">
      <slot name="prefix" />
    </span>

    <input
      ref="inputEl"
      v-model="model"
      v-bind="inputAttrs"
      class="st-input__el"
      :type="type"
      :placeholder="placeholder"
      :disabled="disabled"
      :readonly="readonly"
      :maxlength="maxlength"
      :autocomplete="autocomplete"
      :aria-label="ariaLabel"
      :aria-invalid="status === 'error' ? true : undefined"
      @keydown.enter="onEnter"
      @focus="emit('focus', $event)"
      @blur="emit('blur', $event)"
    />

    <button
      v-if="showClear"
      type="button"
      class="st-input__clear"
      aria-label="清除"
      tabindex="-1"
      @mousedown.prevent
      @click="clear"
    >
      <StIcon name="x" :size="14" />
    </button>

    <span v-if="slots.suffix" class="st-input__affix st-input__affix--suffix">
      <slot name="suffix" />
    </span>
  </div>
</template>

<style scoped>
.st-input {
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

.st-input:hover:not(.st-input--disabled) {
  border-color: var(--st-border-hover);
}

.st-input:focus-within {
  border-color: var(--ring);
  box-shadow: var(--st-focus-ring);
}

/* ==================== 尺寸 ==================== */
.st-input--tiny {
  height: var(--st-height-tiny);
  font-size: var(--st-font-tiny);
  padding: 0 7px;
  border-radius: var(--radius-small);
}

.st-input--small {
  height: var(--st-height-small);
  font-size: var(--st-font-small);
  padding: 0 9px;
  border-radius: var(--radius-small);
}

.st-input--large {
  height: var(--st-height-large);
  font-size: var(--st-font-large);
  padding: 0 12px;
}

.st-input--round {
  border-radius: var(--radius-full);
}

.st-input--mono .st-input__el {
  font-family: var(--font-code);
}

.st-input--disabled {
  cursor: not-allowed;
  opacity: 0.5;
}

.st-input--readonly {
  background-color: var(--st-fill-active);
}

/* 校验状态：用边色表达，并保证聚焦时不被 --ring 覆盖 */
.st-input--error {
  border-color: var(--danger);
}

.st-input--error:focus-within {
  border-color: var(--danger);
  box-shadow: 0 0 0 2px color-mix(in srgb, var(--danger) 20%, transparent);
}

.st-input--warning {
  border-color: var(--warning);
}

.st-input--success {
  border-color: var(--success);
}

/* ==================== 内层 ==================== */
.st-input__el {
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

.st-input__el::placeholder {
  color: var(--st-placeholder);
}

.st-input__el:disabled {
  cursor: not-allowed;
}

.st-input__affix {
  display: inline-flex;
  align-items: center;
  flex: none;
  color: var(--st-placeholder);
}

.st-input__clear {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex: none;
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

.st-input__clear:hover {
  color: var(--st-text);
}

@media (prefers-reduced-motion: reduce) {
  .st-input {
    transition: none;
  }
}
</style>
