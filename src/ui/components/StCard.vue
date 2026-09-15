<script setup lang="ts">
/**
 * StCard — 卡片容器
 *
 * 用法：
 *   <StCard title="基本设置">内容</StCard>
 *   <StCard hoverable>…</StCard>
 *   <StCard>
 *     <template #header>自定义头部</template>
 *     <template #action>…</template>
 *   </StCard>
 */
import { computed, useSlots } from 'vue'
import type { StSize } from '../types'

defineOptions({ name: 'StCard' })

const props = withDefaults(
  defineProps<{
    /** 标题；与 header 插槽二选一，插槽优先 */
    title?: string
    /** 副标题，仅在有标题时展示 */
    subtitle?: string
    size?: StSize
    bordered?: boolean
    /** 悬停时抬升阴影 */
    hoverable?: boolean
    /** 内容区去掉内边距（例如内嵌表格/列表） */
    contentFlush?: boolean
  }>(),
  {
    size: 'medium',
    bordered: true,
    hoverable: false,
    contentFlush: false,
  },
)

const slots = useSlots()

const hasHeader = computed(() => !!slots.header || !!props.title)
const hasFooter = computed(() => !!slots.footer)
const hasAction = computed(() => !!slots.action)

const classes = computed(() => [
  'st-card',
  `st-card--${props.size}`,
  {
    'st-card--bordered': props.bordered,
    'st-card--hoverable': props.hoverable,
    'st-card--flush': props.contentFlush,
  },
])
</script>

<template>
  <section :class="classes">
    <header v-if="hasHeader" class="st-card__header">
      <slot name="header">
        <div class="st-card__heading">
          <h3 class="st-card__title">{{ title }}</h3>
          <p v-if="subtitle" class="st-card__subtitle">{{ subtitle }}</p>
        </div>
      </slot>
      <div v-if="hasAction" class="st-card__action"><slot name="action" /></div>
    </header>

    <div class="st-card__content"><slot /></div>

    <footer v-if="hasFooter" class="st-card__footer"><slot name="footer" /></footer>
  </section>
</template>

<style scoped>
.st-card {
  display: flex;
  flex-direction: column;
  box-sizing: border-box;
  background-color: var(--card);
  color: var(--card-foreground);
  border-radius: var(--radius-large);
  box-shadow: var(--card-highlight);
  transition:
    box-shadow var(--transition),
    border-color var(--transition);
}

.st-card--bordered {
  border: 1px solid var(--border);
}

.st-card--hoverable:hover {
  border-color: color-mix(in srgb, var(--border) 40%, var(--foreground));
  box-shadow: var(--shadow-medium);
}

/* ==================== 尺寸 ==================== */
.st-card--small {
  border-radius: var(--radius-medium);
}

.st-card--small .st-card__header,
.st-card--small .st-card__content,
.st-card--small .st-card__footer {
  padding: 12px;
}

.st-card--medium .st-card__header,
.st-card--medium .st-card__content,
.st-card--medium .st-card__footer {
  padding: 20px;
}

.st-card--large .st-card__header,
.st-card--large .st-card__content,
.st-card--large .st-card__footer {
  padding: 28px;
}

.st-card--flush > .st-card__content {
  padding: 0;
}

/* ==================== 分区 ==================== */
.st-card__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
}

.st-card__header + .st-card__content {
  padding-top: 0;
}

.st-card__heading {
  min-width: 0;
}

.st-card__title {
  margin: 0;
  font-size: 1rem;
  font-weight: 600;
  line-height: 1.4;
  color: var(--foreground);
  overflow-wrap: anywhere;
}

.st-card__subtitle {
  margin: 4px 0 0;
  font-size: var(--st-font-small);
  line-height: 1.5;
  color: var(--muted-foreground);
}

.st-card__action {
  display: flex;
  align-items: center;
  gap: 8px;
  flex: none;
}

.st-card__content {
  flex: 1 1 auto;
  min-width: 0;
  font-size: var(--st-font-medium);
}

.st-card__footer {
  border-top: 1px solid var(--border);
}

@media (prefers-reduced-motion: reduce) {
  .st-card {
    transition: none;
  }
}
</style>
