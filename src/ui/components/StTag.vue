<script setup lang="ts">
/**
 * StTag — 标签 / 胶囊
 *
 * 用法：
 *   <StTag>默认</StTag>
 *   <StTag type="success">已完成</StTag>
 *   <StTag type="danger" closable @close="remove">可移除</StTag>
 *
 * 状态色不额外定义浅底令牌，统一用 color-mix 把语义色混入 --card，
 * 保证明暗主题下都有足够对比度。
 */
import { computed } from 'vue'
import StIcon from './StIcon.vue'
import type { StIntent, StSize } from '../types'

defineOptions({ name: 'StTag' })

const props = withDefaults(
  defineProps<{
    /** 语义色 */
    type?: StIntent
    /** 尺寸 */
    size?: StSize
    /** 全圆角胶囊 */
    round?: boolean
    /** 是否描边 */
    bordered?: boolean
    /** 显示关闭按钮 */
    closable?: boolean
  }>(),
  {
    type: 'default',
    size: 'small',
    round: false,
    bordered: true,
    closable: false,
  },
)

const emit = defineEmits<{
  (e: 'close', ev: MouseEvent): void
}>()

const classes = computed(() => [
  'st-tag',
  `st-tag--${props.type}`,
  `st-tag--${props.size}`,
  {
    'st-tag--bordered': props.bordered,
    'st-tag--round': props.round,
  },
])
</script>

<template>
  <span :class="classes">
    <span class="st-tag__content"><slot /></span>
    <button
      v-if="closable"
      type="button"
      class="st-tag__close"
      aria-label="移除"
      @click="emit('close', $event)"
    >
      <StIcon name="x" :size="12" />
    </button>
  </span>
</template>

<style scoped>
.st-tag {
  --st-tag-color: var(--muted-foreground);
  --st-tag-bg: var(--faint);
  --st-tag-border: var(--border);

  display: inline-flex;
  align-items: center;
  gap: 4px;
  box-sizing: border-box;
  height: var(--st-height-small);
  padding: 0 8px;
  /* 边框常驻透明，切换 bordered 时不产生 1px 布局抖动 */
  border: 1px solid transparent;
  border-radius: var(--radius-small);
  background-color: var(--st-tag-bg);
  color: var(--st-tag-color);
  font-size: var(--st-font-small);
  line-height: 1;
  white-space: nowrap;
  transition:
    background-color var(--transition-fast),
    border-color var(--transition-fast);
}

.st-tag--bordered {
  border-color: var(--st-tag-border);
}

/* ==================== 尺寸 ==================== */
.st-tag--tiny {
  height: var(--st-height-tiny);
  padding: 0 6px;
  font-size: var(--st-font-tiny);
  border-radius: var(--radius-xs);
}

.st-tag--medium {
  height: var(--st-height-medium);
  padding: 0 10px;
  font-size: var(--st-font-medium);
  border-radius: var(--radius-medium);
}

.st-tag--large {
  height: var(--st-height-large);
  padding: 0 12px;
  font-size: var(--st-font-large);
  border-radius: var(--radius-medium);
}

.st-tag--round {
  border-radius: var(--radius-full);
}

/* ==================== 语义色 ==================== */
.st-tag--primary {
  --st-tag-bg: color-mix(in srgb, var(--primary) 12%, var(--card));
  --st-tag-color: var(--primary);
  --st-tag-border: color-mix(in srgb, var(--primary) 30%, transparent);
}

.st-tag--success {
  --st-tag-bg: color-mix(in srgb, var(--success) 12%, var(--card));
  --st-tag-color: var(--success);
  --st-tag-border: color-mix(in srgb, var(--success) 30%, transparent);
}

.st-tag--warning {
  --st-tag-bg: color-mix(in srgb, var(--warning) 12%, var(--card));
  --st-tag-color: var(--warning);
  --st-tag-border: color-mix(in srgb, var(--warning) 30%, transparent);
}

.st-tag--error {
  --st-tag-bg: color-mix(in srgb, var(--danger) 12%, var(--card));
  --st-tag-color: var(--danger);
  --st-tag-border: color-mix(in srgb, var(--danger) 30%, transparent);
}

/* ==================== 内容 ==================== */
.st-tag__content {
  overflow: hidden;
  text-overflow: ellipsis;
}

.st-tag__close {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex: none;
  width: 14px;
  height: 14px;
  margin-right: -3px;
  padding: 0;
  border: none;
  border-radius: var(--radius-full);
  background: transparent;
  color: inherit;
  opacity: 0.7;
  cursor: pointer;
  transition:
    opacity var(--transition-fast),
    background-color var(--transition-fast);
}

.st-tag__close:hover {
  opacity: 1;
  background-color: color-mix(in srgb, currentColor 16%, transparent);
}

.st-tag__close:focus-visible {
  outline: none;
  opacity: 1;
  box-shadow: var(--st-focus-ring);
}

@media (prefers-reduced-motion: reduce) {
  .st-tag,
  .st-tag__close {
    transition: none;
  }
}
</style>
