<script setup lang="ts">
/**
 * StButton — 按钮
 *
 * 用法：
 *   <StButton type="primary" @click="save">保存</StButton>
 *   <StButton loading>提交中</StButton>
 *   <StButton quaternary circle aria-label="关闭"><template #icon>…</template></StButton>
 *
 * 约定：对外只暴露 props / slots / emits，不接受 class 透传做样式覆盖。
 * 需要改外观请用 type / size / secondary… 等语义 props。
 */
import { computed, useSlots } from 'vue'
import StSpinner from './StSpinner.vue'
import type { StIntent, StSize } from '../types'

defineOptions({ name: 'StButton' })

const props = withDefaults(
  defineProps<{
    /** 语义色 */
    type?: StIntent
    /** 尺寸 */
    size?: StSize
    /** 降低视觉权重：淡化描边与文字 */
    secondary?: boolean
    /** 三级权重 */
    tertiary?: boolean
    /** 四级权重：无背景、无边框 */
    quaternary?: boolean
    /** 纯文字按钮（无内边距背景，仅悬停变色） */
    text?: boolean
    /** 透明背景 + 彩色描边 */
    ghost?: boolean
    /** 虚线描边 */
    dashed?: boolean
    /** 全圆角 */
    round?: boolean
    /** 正圆（仅图标） */
    circle?: boolean
    /** 撑满父容器宽度 */
    block?: boolean
    /** 加粗文字 */
    strong?: boolean
    disabled?: boolean
    /** 加载中：显示指示器并阻止点击 */
    loading?: boolean
    /** 渲染的标签，默认 button（可传 'a' 等） */
    tag?: string
    /** 原生 type 属性，仅 tag='button' 时生效 */
    attrType?: 'button' | 'submit' | 'reset'
    /** 无障碍名称；circle 模式必填 */
    ariaLabel?: string
  }>(),
  {
    type: 'default',
    size: 'medium',
    tag: 'button',
    attrType: 'button',
  },
)

const emit = defineEmits<{
  (e: 'click', ev: MouseEvent): void
}>()

const slots = useSlots()

const classes = computed(() => [
  'st-button',
  `st-button--${props.type}`,
  `st-button--${props.size}`,
  {
    'st-button--secondary': props.secondary,
    'st-button--tertiary': props.tertiary,
    'st-button--quaternary': props.quaternary,
    'st-button--text': props.text,
    'st-button--ghost': props.ghost,
    'st-button--dashed': props.dashed,
    'st-button--round': props.round,
    'st-button--circle': props.circle,
    'st-button--block': props.block,
    'st-button--strong': props.strong,
    'st-button--icon-only': !slots.default && (!!slots.icon || props.circle),
    'is-disabled': props.disabled,
    'is-loading': props.loading,
  },
])

const isNativeButton = computed(() => props.tag === 'button')
const blocked = computed(() => props.disabled || props.loading)

function handleClick(ev: MouseEvent) {
  if (blocked.value) {
    ev.preventDefault()
    ev.stopPropagation()
    return
  }
  emit('click', ev)
}
</script>

<template>
  <component
    :is="tag"
    :class="classes"
    :type="isNativeButton ? attrType : undefined"
    :disabled="isNativeButton ? blocked : undefined"
    :aria-disabled="!isNativeButton && blocked ? true : undefined"
    :aria-busy="loading ? true : undefined"
    :aria-label="ariaLabel"
    :tabindex="!isNativeButton && blocked ? -1 : undefined"
    @click="handleClick"
  >
    <StSpinner v-if="loading" class="st-button__spinner" :size="size" />
    <span v-else-if="slots.icon" class="st-button__icon"><slot name="icon" /></span>
    <span v-if="slots.default" class="st-button__content"><slot /></span>
  </component>
</template>

<style scoped>
/* ==================== 基础 ==================== */
.st-button {
  --st-button-color: var(--st-text);
  --st-button-bg: var(--st-fill);
  --st-button-border: var(--st-border);

  position: relative;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  box-sizing: border-box;
  height: var(--st-height-medium);
  padding: var(--st-pad-medium);
  border: 1px solid var(--st-button-border);
  border-radius: var(--radius-medium);
  background-color: var(--st-button-bg);
  color: var(--st-button-color);
  font-family: inherit;
  font-size: var(--st-font-medium);
  font-weight: 400;
  line-height: 1;
  white-space: nowrap;
  cursor: pointer;
  user-select: none;
  text-decoration: none;
  transition:
    background-color var(--transition-fast),
    border-color var(--transition-fast),
    color var(--transition-fast),
    opacity var(--transition-fast);
}

.st-button:hover:not(.is-disabled):not(.is-loading) {
  border-color: var(--st-border-hover);
}

.st-button:focus-visible {
  outline: none;
  box-shadow: var(--st-focus-ring);
  border-color: var(--ring);
}

.st-button.is-disabled,
.st-button.is-loading {
  cursor: not-allowed;
  opacity: 0.5;
}

/* ==================== 尺寸 ==================== */
.st-button--tiny {
  height: var(--st-height-tiny);
  padding: var(--st-pad-tiny);
  font-size: var(--st-font-tiny);
  border-radius: var(--radius-small);
}

.st-button--small {
  height: var(--st-height-small);
  padding: var(--st-pad-small);
  font-size: var(--st-font-small);
  border-radius: var(--radius-small);
}

.st-button--large {
  height: var(--st-height-large);
  padding: var(--st-pad-large);
  font-size: var(--st-font-large);
}

/* ==================== 语义色 ==================== */
.st-button--primary {
  --st-button-color: var(--primary-foreground);
  --st-button-bg: var(--primary);
  --st-button-border: var(--primary);
}

.st-button--success {
  --st-button-color: #fff;
  --st-button-bg: var(--success);
  --st-button-border: var(--success);
}

.st-button--warning {
  --st-button-color: #fff;
  --st-button-bg: var(--warning);
  --st-button-border: var(--warning);
}

.st-button--error {
  --st-button-color: #fff;
  --st-button-bg: var(--danger);
  --st-button-border: var(--danger);
}

/* 彩色按钮悬停：整体略降不透明度，避免为每种色再定义 hover 令牌 */
.st-button--primary:hover:not(.is-disabled):not(.is-loading),
.st-button--success:hover:not(.is-disabled):not(.is-loading),
.st-button--warning:hover:not(.is-disabled):not(.is-loading),
.st-button--error:hover:not(.is-disabled):not(.is-loading) {
  opacity: 0.85;
  border-color: transparent;
}

/* ==================== 权重变体 ==================== */
.st-button--secondary {
  opacity: 0.85;
}

.st-button--tertiary {
  --st-button-bg: transparent;
  --st-button-border: var(--st-border);
}

.st-button--quaternary,
.st-button--text {
  --st-button-bg: transparent;
  --st-button-border: transparent;
}

.st-button--quaternary:hover:not(.is-disabled):not(.is-loading),
.st-button--text:hover:not(.is-disabled):not(.is-loading) {
  --st-button-bg: var(--st-fill-hover);
  border-color: transparent;
}

.st-button--text {
  padding-left: 4px;
  padding-right: 4px;
}

.st-button--ghost {
  --st-button-bg: transparent;
  --st-button-color: var(--st-button-border);
}

.st-button--ghost.st-button--default {
  --st-button-color: var(--st-text);
}

.st-button--ghost:hover:not(.is-disabled):not(.is-loading) {
  --st-button-bg: color-mix(in srgb, var(--st-button-border) 12%, transparent);
}

.st-button--dashed {
  border-style: dashed;
}

/* ==================== 形状 ==================== */
.st-button--round {
  border-radius: var(--radius-full);
}

.st-button--circle {
  border-radius: var(--radius-full);
  width: var(--st-height-medium);
  padding: 0;
}

.st-button--circle.st-button--tiny {
  width: var(--st-height-tiny);
}

.st-button--circle.st-button--small {
  width: var(--st-height-small);
}

.st-button--circle.st-button--large {
  width: var(--st-height-large);
}

.st-button--block {
  display: flex;
  width: 100%;
}

.st-button--strong {
  font-weight: 600;
}

/* 仅图标时收紧水平内边距，避免图标视觉偏移 */
.st-button--icon-only:not(.st-button--circle) {
  padding-left: 6px;
  padding-right: 6px;
}

/* ==================== 内容 ==================== */
.st-button__icon,
.st-button__spinner {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex: none;
}

.st-button__content {
  display: inline-block;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* 加载态保留原有内容宽度，避免按钮尺寸跳动 */
.st-button.is-loading .st-button__content {
  opacity: 0.6;
}

@media (prefers-reduced-motion: reduce) {
  .st-button {
    transition: none;
  }
}
</style>
