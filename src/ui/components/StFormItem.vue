<script setup lang="ts">
/**
 * StFormItem — 表单行包装器
 *
 * 用法：
 *   <StFormItem label="站点名称" htmlFor="site-name" required description="显示在浏览器标题栏">
 *     <StInput id="site-name" v-model="name" />
 *   </StFormItem>
 *   <StFormItem label="主色" label-placement="left" label-width="120px">
 *     <StColorPicker v-model="color" />
 *   </StFormItem>
 *
 * 只负责布局与标签 / 描述 / 反馈，不 import 任何具体控件——控件由调用方放进默认插槽，
 * 这样后台迁移时不会被 StFormItem 的依赖绑死。
 */
import { computed, useSlots } from 'vue'
import type { StStatus } from '../types'

defineOptions({ name: 'StFormItem' })

const props = withDefaults(
  defineProps<{
    /** 标签文本；与 label 插槽二选一，插槽优先 */
    label?: string
    /** 标签位置：top = 上下堆叠（默认），left = 左右并列 */
    labelPlacement?: 'top' | 'left'
    /** 标签列宽，仅 left 布局生效（如 '120px'） */
    labelWidth?: string
    /** 控件下方的补充说明 */
    description?: string
    /** 校验反馈文本，配色由 validationStatus 决定 */
    feedback?: string
    /** 校验状态；有值时反馈文字按状态着色 */
    validationStatus?: StStatus
    /** 必填标记（视觉星号 + 读屏文本） */
    required?: boolean
    /** 关联控件的 id，透传给 label 的 for */
    htmlFor?: string
  }>(),
  {
    labelPlacement: 'top',
  },
)

const slots = useSlots()

const hasLabel = computed(() => !!slots.label || !!props.label)
const hasDescription = computed(() => !!props.description)
const hasFeedback = computed(() => !!slots.feedback || !!props.feedback)

const classes = computed(() => [
  'st-form-item',
  `st-form-item--${props.labelPlacement}`,
  {
    [`st-form-item--${props.validationStatus}`]: !!props.validationStatus,
  },
])

/** 宽度只作用于标签列；用行内 style 传值，避免为任意宽度生成 class */
const labelStyle = computed(() =>
  props.labelPlacement === 'left' && props.labelWidth ? { width: props.labelWidth } : undefined,
)

/** 错误反馈用 alert（打断式播报），其余用 status（礼貌播报） */
const feedbackRole = computed(() => (props.validationStatus === 'error' ? 'alert' : 'status'))
</script>

<template>
  <div :class="classes">
    <div v-if="hasLabel" class="st-form-item__label-wrap" :style="labelStyle">
      <label class="st-form-item__label" :for="htmlFor">
        <slot name="label">{{ label }}</slot>
        <template v-if="required">
          <span class="st-form-item__required" aria-hidden="true">*</span>
          <span class="st-form-item__required-text">必填</span>
        </template>
      </label>
    </div>

    <div class="st-form-item__body">
      <div class="st-form-item__control"><slot /></div>

      <p v-if="hasDescription" class="st-form-item__description">{{ description }}</p>

      <p
        v-if="hasFeedback"
        class="st-form-item__feedback"
        :role="feedbackRole"
        :aria-live="validationStatus === 'error' ? 'assertive' : 'polite'"
      >
        <slot name="feedback">{{ feedback }}</slot>
      </p>
    </div>
  </div>
</template>

<style scoped>
.st-form-item {
  display: flex;
  flex-direction: column;
  gap: 6px;
  box-sizing: border-box;
  color: var(--st-text);
  font-size: var(--st-font-medium);
}

.st-form-item--left {
  flex-direction: row;
  gap: 12px;
}

.st-form-item__label-wrap {
  flex: none;
  min-width: 0;
}

/* 左布局下标签与控件首行对齐：输入类控件上下对称留白约 10px */
.st-form-item--left .st-form-item__label-wrap {
  padding-top: 9px;
}

.st-form-item__label {
  position: relative;
  display: inline-block;
  color: var(--st-text);
  font-size: var(--st-font-small);
  font-weight: 500;
  line-height: 1.5;
  overflow-wrap: anywhere;
}

.st-form-item__required {
  margin-left: 2px;
  color: var(--danger);
}

/* 颜色之外再给读屏文本，避免"只靠颜色传达必填" */
.st-form-item__required-text {
  position: absolute;
  width: 1px;
  height: 1px;
  overflow: hidden;
  clip-path: inset(50%);
  white-space: nowrap;
}

.st-form-item__body {
  display: flex;
  flex-direction: column;
  gap: 4px;
  flex: 1 1 auto;
  min-width: 0;
}

.st-form-item__description,
.st-form-item__feedback {
  margin: 0;
  font-size: var(--st-font-tiny);
  line-height: 1.5;
}

.st-form-item__description {
  color: var(--st-placeholder);
}

.st-form-item__feedback {
  color: var(--st-placeholder);
}

/* ==================== 校验状态 ==================== */
.st-form-item--error .st-form-item__feedback {
  color: var(--danger);
}

.st-form-item--warning .st-form-item__feedback {
  color: var(--warning);
}

.st-form-item--success .st-form-item__feedback {
  color: var(--success);
}
</style>
