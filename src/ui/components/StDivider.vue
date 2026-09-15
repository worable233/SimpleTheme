<script setup lang="ts">
/**
 * StDivider — 分隔线
 *
 * 用法：
 *   <StDivider />
 *   <StDivider>分组标题</StDivider>
 *   <StDivider vertical />
 *
 * vertical 的高度取 1em：行内分隔符的父容器高度往往不确定，1em 随字号
 * 自适应，比 100% 更不容易塌陷成不可见。
 */
import { computed, useSlots } from 'vue'

defineOptions({ name: 'StDivider' })

const props = withDefaults(
  defineProps<{
    /** 行内竖线 */
    vertical?: boolean
    /** 标题相对位置，仅水平模式且带标题时生效 */
    titlePlacement?: 'left' | 'center' | 'right'
    /** 虚线 */
    dashed?: boolean
  }>(),
  {
    vertical: false,
    titlePlacement: 'center',
    dashed: false,
  },
)

const slots = useSlots()

const hasTitle = computed(() => !props.vertical && !!slots.default)

const classes = computed(() => [
  'st-divider',
  `st-divider--${props.vertical ? 'vertical' : 'horizontal'}`,
  {
    'st-divider--dashed': props.dashed,
    'st-divider--with-title': hasTitle.value,
    [`st-divider--${props.titlePlacement}`]: hasTitle.value,
  },
])
</script>

<template>
  <div :class="classes" role="separator" :aria-orientation="vertical ? 'vertical' : 'horizontal'">
    <template v-if="hasTitle">
      <span class="st-divider__line" />
      <span class="st-divider__title"><slot /></span>
      <span class="st-divider__line" />
    </template>
    <span v-else-if="!vertical" class="st-divider__line" />
  </div>
</template>

<style scoped>
.st-divider {
  --st-divider-color: var(--border);

  box-sizing: border-box;
}

/* ==================== 竖线 ==================== */
.st-divider--vertical {
  display: inline-block;
  width: 0;
  height: 1em;
  margin: 0 8px;
  vertical-align: middle;
  /* 用 border 而非背景，虚线样式才生效 */
  border-left: 1px solid var(--st-divider-color);
}

.st-divider--vertical.st-divider--dashed {
  border-left-style: dashed;
}

/* ==================== 横线 ==================== */
.st-divider--horizontal {
  display: flex;
  align-items: center;
  width: 100%;
  margin: 16px 0;
  color: var(--muted-foreground);
  font-size: var(--st-font-small);
}

.st-divider--horizontal .st-divider__line {
  flex: 1 1 auto;
  height: 0;
  border-top: 1px solid var(--st-divider-color);
}

.st-divider--horizontal.st-divider--dashed .st-divider__line {
  border-top-style: dashed;
}

.st-divider__title {
  flex: none;
  padding: 0 12px;
  white-space: nowrap;
}

/* 标题靠边时对应一侧的线段收短，避免标题被推到角落 */
.st-divider--left .st-divider__line:first-child {
  flex: 0 0 12px;
}

.st-divider--right .st-divider__line:last-child {
  flex: 0 0 12px;
}
</style>
