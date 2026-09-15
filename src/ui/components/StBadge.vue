<script setup lang="ts">
/**
 * StBadge — 角标
 *
 * 用法：
 *   <StBadge :value="5" />
 *   <StBadge :value="120" :max="99">…</StBadge>
 *   <StBadge dot>…</StBadge>
 *
 * 有默认插槽时角标绝对定位到内容右上角；无插槽时自身即为角标，
 * 两种形态共用同一套 DOM，避免出现两套样式分支。
 */
import { computed, useSlots } from 'vue'
import type { StIntent } from '../types'

defineOptions({ name: 'StBadge' })

const props = withDefaults(
  defineProps<{
    /** 数值；数字超过 max 时显示 `${max}+` */
    value?: number | string
    /** 数字上限 */
    max?: number
    /** 仅渲染小圆点，不显示数值 */
    dot?: boolean
    /** 语义色 */
    type?: StIntent
  }>(),
  {
    max: 99,
    dot: false,
    type: 'error',
  },
)

const slots = useSlots()

const display = computed(() => {
  if (props.dot) return ''
  if (props.value === undefined || props.value === null) return ''
  if (typeof props.value === 'number' && props.value > props.max) return `${props.max}+`
  return String(props.value)
})

/** 圆点无语义数值，用文字描述状态，供读屏播报 */
const ariaLabel = computed(() => (props.dot ? '有新内容' : `角标 ${display.value}`))

const hasContent = computed(() => !!slots.default)
const showBadge = computed(() => props.dot || display.value !== '')

const classes = computed(() => [
  'st-badge',
  `st-badge--${props.type}`,
  {
    'st-badge--dot': props.dot,
    'st-badge--standalone': !hasContent.value,
  },
])
</script>

<template>
  <span :class="classes">
    <span v-if="hasContent" class="st-badge__content"><slot /></span>
    <span v-if="showBadge" class="st-badge__item" :aria-label="ariaLabel">{{ display }}</span>
  </span>
</template>

<style scoped>
.st-badge {
  position: relative;
  display: inline-flex;
  vertical-align: middle;
}

.st-badge__content {
  display: inline-flex;
}

.st-badge__item {
  --st-badge-bg: var(--danger);
  --st-badge-color: var(--st-on-color);

  position: absolute;
  top: 0;
  right: 0;
  z-index: 1;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  box-sizing: border-box;
  min-width: 16px;
  height: 16px;
  padding: 0 5px;
  border-radius: var(--radius-full);
  background-color: var(--st-badge-bg);
  color: var(--st-badge-color);
  font-size: var(--st-font-tiny);
  line-height: 1;
  white-space: nowrap;
  font-variant-numeric: tabular-nums;
  /* 以内容右上角为锚点外移，不遮挡被包裹内容 */
  transform: translate(50%, -50%);
}

/* ==================== 语义色 ==================== */
.st-badge--default .st-badge__item {
  --st-badge-bg: var(--muted);
  --st-badge-color: var(--muted-foreground);
}

.st-badge--primary .st-badge__item {
  --st-badge-bg: var(--primary);
  --st-badge-color: var(--primary-foreground);
}

.st-badge--success .st-badge__item {
  --st-badge-bg: var(--success);
  --st-badge-color: var(--st-on-color);
}

.st-badge--warning .st-badge__item {
  --st-badge-bg: var(--warning);
  --st-badge-color: var(--st-on-color);
}

.st-badge--error .st-badge__item {
  --st-badge-bg: var(--danger);
  --st-badge-color: var(--st-on-color);
}

/* ==================== 圆点 ==================== */
.st-badge--dot .st-badge__item {
  box-sizing: content-box;
  min-width: 0;
  width: 6px;
  height: 6px;
  padding: 0;
}

/* 独立形态无需绝对定位，否则会把父容器尺寸撑破 */
.st-badge--standalone .st-badge__item {
  position: static;
  transform: none;
}
</style>
