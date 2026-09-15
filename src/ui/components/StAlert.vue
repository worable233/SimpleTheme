<script setup lang="ts">
/**
 * StAlert — 内联提示条（非浮层）
 *
 * 用法：
 *   <StAlert type="warning" title="注意">该操作不可撤销</StAlert>
 *   <StAlert v-model:show="visible" type="error" closable>保存失败</StAlert>
 *   <StAlert type="success">
 *     <template #action><StButton size="small">查看</StButton></template>
 *     已同步
 *   </StAlert>
 *
 * 说明：只负责内联展示，不参与页面布局之外的任何交互；浮层请用 StToast / StModal。
 */
import { computed, useSlots } from 'vue'
import StIcon from './StIcon.vue'

defineOptions({ name: 'StAlert' })

type StAlertType = 'default' | 'success' | 'warning' | 'error'

const props = withDefaults(
  defineProps<{
    /** 语义类型，决定强调色与图标 */
    type?: StAlertType
    /** 标题；省略时正文单独占据一行 */
    title?: string
    /** 有值时显示关闭按钮 */
    closable?: boolean
    /** 是否显示类型图标，icon 插槽优先 */
    showIcon?: boolean
  }>(),
  {
    type: 'default',
    closable: false,
    showIcon: true,
  },
)

const show = defineModel<boolean>('show', { default: true })

const slots = useSlots()

const ICONS: Record<StAlertType, string> = {
  default: 'info-circle',
  success: 'circle-check',
  warning: 'alert-triangle',
  error: 'circle-x',
}

const iconName = computed(() => ICONS[props.type])
const hasIcon = computed(() => !!slots.icon || props.showIcon)

/** warning/error 打断当前朗读，其余用 status 走礼貌播报 */
const role = computed(() =>
  props.type === 'error' || props.type === 'warning' ? 'alert' : 'status',
)
</script>

<template>
  <div v-if="show" class="st-alert" :class="`st-alert--${type}`" :role="role">
    <span v-if="hasIcon" class="st-alert__icon">
      <slot name="icon"><StIcon :name="iconName" :size="16" /></slot>
    </span>

    <div class="st-alert__content">
      <div v-if="title" class="st-alert__title">{{ title }}</div>
      <div v-if="slots.default" class="st-alert__body"><slot /></div>
    </div>

    <div v-if="slots.action" class="st-alert__action"><slot name="action" /></div>

    <button
      v-if="closable"
      type="button"
      class="st-alert__close"
      aria-label="关闭"
      @click="show = false"
    >
      <StIcon name="x" :size="14" />
    </button>
  </div>
</template>

<style scoped>
.st-alert {
  /* 强调色由 type 覆写；背景/边框都由令牌派生，不引入新颜色 */
  --st-alert-accent: var(--muted-foreground);

  display: flex;
  align-items: flex-start;
  gap: 10px;
  box-sizing: border-box;
  padding: 10px 12px;
  border: 1px solid color-mix(in srgb, var(--st-alert-accent) 30%, var(--st-border));
  border-radius: var(--radius-medium);
  background-color: color-mix(in srgb, var(--st-alert-accent) 10%, var(--card));
  color: var(--st-text);
  font-size: var(--st-font-medium);
}

.st-alert--success {
  --st-alert-accent: var(--success);
}

.st-alert--warning {
  --st-alert-accent: var(--warning);
}

.st-alert--error {
  --st-alert-accent: var(--danger);
}

.st-alert__icon {
  display: inline-flex;
  flex: none;
  margin-top: 1px;
  color: var(--st-alert-accent);
}

.st-alert__content {
  flex: 1 1 auto;
  min-width: 0;
}

.st-alert__title {
  font-weight: 600;
  line-height: 1.4;
  /* 标题保持前景色：状态色在浅色/深色下的对比度不足以保证可读 */
  color: var(--foreground);
  overflow-wrap: anywhere;
}

.st-alert__body {
  line-height: 1.55;
  overflow-wrap: anywhere;
}

/* 有标题时正文降级为辅助文字，形成层级 */
.st-alert__title + .st-alert__body {
  margin-top: 2px;
  color: var(--muted-foreground);
  font-size: var(--st-font-small);
}

.st-alert__action {
  display: flex;
  align-items: center;
  gap: 6px;
  flex: none;
}

.st-alert__close {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex: none;
  width: 20px;
  height: 20px;
  margin: -1px -2px 0 0;
  padding: 0;
  border: none;
  border-radius: var(--radius-full);
  background-color: transparent;
  color: var(--muted-foreground);
  cursor: pointer;
  transition: color var(--transition-fast);
}

.st-alert__close:hover {
  color: var(--foreground);
}

.st-alert__close:focus-visible {
  outline: none;
  box-shadow: var(--st-focus-ring);
}

@media (prefers-reduced-motion: reduce) {
  .st-alert__close {
    transition: none;
  }
}
</style>
