<script setup lang="ts">
/**
 * StTextarea — 多行输入框
 *
 * 用法：
 *   <StTextarea v-model="content" placeholder="说点什么" />
 *   <StTextarea v-model="bio" :rows="6" resize="none" status="error" />
 *   <StTextarea v-model="note" maxlength="200" aria-label="备注" />
 *
 * 视觉与 StInput 完全一致（同边框 / 同焦点环 / 同 status 配色），只是多行。
 * 受控：始终以 modelValue 为准；未绑定 v-model 时输入不会回显。
 */
import { computed, ref, useAttrs } from 'vue'
import type { StSize, StStatus } from '../types'

// inheritAttrs:false —— 理由同 StInput：默认透传会把 required / id / name
// 等非声明属性落到根 div 上，内层 <textarea> 拿不到，原生校验与
// <label for> 关联会静默失效。class/style 仍留在根元素。
defineOptions({ name: 'StTextarea', inheritAttrs: false })

const props = withDefaults(
  defineProps<{
    /** 尺寸 */
    size?: StSize
    placeholder?: string
    disabled?: boolean
    readonly?: boolean
    /** 校验状态 */
    status?: StStatus
    /** 初始可见行数 */
    rows?: number
    maxlength?: number
    /** 拖拽缩放方向；vertical 是默认值，避免拖宽破坏栅格布局 */
    resize?: 'none' | 'vertical' | 'both'
    /** 无障碍名称；无可见 label 时必填 */
    ariaLabel?: string
  }>(),
  {
    size: 'medium',
    rows: 4,
    resize: 'vertical',
  },
)

const model = defineModel<string>({ default: '' })

const emit = defineEmits<{
  (e: 'focus', ev: FocusEvent): void
  (e: 'blur', ev: FocusEvent): void
}>()

const el = ref<HTMLTextAreaElement | null>(null)

const attrs = useAttrs()

/** class/style 留在根 div */
const rootAttrs = computed(() => ({ class: attrs.class, style: attrs.style }))

/** 其余非声明属性下沉到内层 <textarea> */
const areaAttrs = computed(() =>
  Object.fromEntries(Object.entries(attrs).filter(([k]) => k !== 'class' && k !== 'style')),
)

const classes = computed(() => [
  'st-textarea',
  `st-textarea--${props.size}`,
  `st-textarea--resize-${props.resize}`,
  {
    'st-textarea--disabled': props.disabled,
    'st-textarea--readonly': props.readonly,
    [`st-textarea--${props.status}`]: !!props.status,
  },
])

function focus() {
  el.value?.focus()
}

defineExpose({ focus, el })
</script>

<template>
  <div v-bind="rootAttrs" :class="classes">
    <textarea
      ref="el"
      v-model="model"
      v-bind="areaAttrs"
      class="st-textarea__el"
      :rows="rows"
      :placeholder="placeholder"
      :disabled="disabled"
      :readonly="readonly"
      :maxlength="maxlength"
      :aria-label="ariaLabel"
      :aria-invalid="status === 'error' ? true : undefined"
      @focus="emit('focus', $event)"
      @blur="emit('blur', $event)"
    />
  </div>
</template>

<style scoped>
.st-textarea {
  display: block;
  box-sizing: border-box;
  width: 100%;
  padding: 7px 10px;
  border: 1px solid var(--st-border);
  border-radius: var(--radius-medium);
  background-color: var(--st-fill);
  color: var(--st-text);
  font-size: var(--st-font-medium);
  transition:
    border-color var(--transition-fast),
    box-shadow var(--transition-fast);
}

.st-textarea:hover:not(.st-textarea--disabled) {
  border-color: var(--st-border-hover);
}

.st-textarea:focus-within {
  border-color: var(--ring);
  box-shadow: var(--st-focus-ring);
}

/* ==================== 尺寸 ==================== */
.st-textarea--tiny {
  padding: 4px 7px;
  font-size: var(--st-font-tiny);
  border-radius: var(--radius-small);
}

.st-textarea--small {
  padding: 5px 9px;
  font-size: var(--st-font-small);
  border-radius: var(--radius-small);
}

.st-textarea--large {
  padding: 9px 12px;
  font-size: var(--st-font-large);
}

.st-textarea--disabled {
  cursor: not-allowed;
  opacity: 0.5;
}

.st-textarea--readonly {
  background-color: var(--st-fill-active);
}

/* 校验状态：用边色表达，并保证聚焦时不被 --ring 覆盖 */
.st-textarea--error {
  border-color: var(--danger);
}

.st-textarea--error:focus-within {
  border-color: var(--danger);
  box-shadow: 0 0 0 2px color-mix(in srgb, var(--danger) 20%, transparent);
}

.st-textarea--warning {
  border-color: var(--warning);
}

.st-textarea--success {
  border-color: var(--success);
}

/* ==================== 拖拽方向 ==================== */
.st-textarea--resize-none .st-textarea__el {
  resize: none;
}

.st-textarea--resize-vertical .st-textarea__el {
  resize: vertical;
}

.st-textarea--resize-both .st-textarea__el {
  resize: both;
}

/* ==================== 内层 ==================== */
.st-textarea__el {
  display: block;
  box-sizing: border-box;
  width: 100%;
  padding: 0;
  border: none;
  outline: none;
  background: transparent;
  color: inherit;
  font-family: inherit;
  font-size: inherit;
  line-height: 1.6;
}

.st-textarea__el::placeholder {
  color: var(--st-placeholder);
}

.st-textarea__el:disabled {
  cursor: not-allowed;
}

@media (prefers-reduced-motion: reduce) {
  .st-textarea {
    transition: none;
  }
}
</style>
