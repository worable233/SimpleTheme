<script setup lang="ts">
/**
 * StCollapseItem — 单个折叠项（必须嵌套在 StCollapse 内）
 *
 * 用法：
 *   <StCollapseItem title="基础设置" name="basic">…</StCollapseItem>
 *   <StCollapseItem name="adv">
 *     <template #title>高级设置</template>
 *     <template #extra><StButton text size="tiny">重置</StButton></template>
 *     …
 *   </StCollapseItem>
 *
 * 展开/收起、aria-expanded、aria-controls、方向键与 Home/End 导航全部由
 * reka-ui AccordionItem/Header/Trigger/Content 提供，本组件只负责外观与注册。
 */
import { computed, inject, onBeforeUnmount } from 'vue'
import {
  AccordionContent,
  AccordionHeader,
  AccordionItem,
  AccordionTrigger,
} from 'reka-ui'
import StIcon from './StIcon.vue'
import { ST_COLLAPSE_CONTEXT } from './StCollapse.vue'

defineOptions({ name: 'StCollapseItem' })

const props = withDefaults(
  defineProps<{
    /** 标题文本；提供 #title 插槽时由插槽内容取代 */
    title: string
    /** 唯一标识，与容器的 v-model:expanded 对应 */
    name: string | number
    disabled?: boolean
  }>(),
  {
    disabled: false,
  },
)

const collapse = inject(ST_COLLAPSE_CONTEXT, null)
if (!collapse) {
  throw new Error('[StCollapseItem] 必须嵌套在 <StCollapse> 内使用')
}

// reka-ui 的 AccordionItem.value 只接受 string；容器侧用注册表把它还原回原始 name
const itemValue = computed(() => String(props.name))

// 无外框时去掉左右留白，让标题与内容同容器左边缘对齐
const flush = computed(() => !collapse.bordered.value)

collapse.registerItem(props.name)
onBeforeUnmount(() => collapse.unregisterItem(props.name))
</script>

<template>
  <AccordionItem
    class="st-collapse-item"
    :class="{ 'is-disabled': disabled, 'st-collapse-item--flush': flush }"
    :value="itemValue"
    :disabled="disabled"
  >
    <AccordionHeader class="st-collapse-item__header">
      <AccordionTrigger class="st-collapse-item__trigger" :disabled="disabled">
        <span class="st-collapse-item__title">
          <slot name="title">{{ title }}</slot>
        </span>
        <StIcon class="st-collapse-item__arrow" name="chevron-down" :size="16" />
      </AccordionTrigger>
      <span v-if="$slots.extra" class="st-collapse-item__extra"><slot name="extra" /></span>
    </AccordionHeader>

    <AccordionContent class="st-collapse-item__content">
      <div class="st-collapse-item__body"><slot /></div>
    </AccordionContent>
  </AccordionItem>
</template>

<style scoped>
.st-collapse-item {
  box-sizing: border-box;
  background-color: transparent;
  transition: background-color var(--transition-fast);
}

.st-collapse-item.is-disabled {
  color: var(--st-text-disabled);
}

/* 无外框（bordered=false）时与容器左边缘对齐 */
.st-collapse-item--flush .st-collapse-item__trigger {
  padding-left: 0;
  padding-right: 0;
}

.st-collapse-item--flush .st-collapse-item__body {
  padding-left: 0;
  padding-right: 0;
}

.st-collapse-item--flush .st-collapse-item__extra {
  padding-right: 0;
}

/* ==================== 标题栏 ==================== */
.st-collapse-item__header {
  display: flex;
  align-items: center;
  gap: 8px;
  margin: 0;
  font-size: var(--st-font-medium);
  font-weight: 500;
  line-height: 1.5;
}

.st-collapse-item__trigger {
  display: flex;
  flex: 1 1 auto;
  align-items: center;
  gap: 8px;
  box-sizing: border-box;
  min-width: 0;
  padding: 12px 16px;
  border: none;
  background-color: transparent;
  color: inherit;
  font-family: inherit;
  font-size: inherit;
  font-weight: inherit;
  text-align: left;
  cursor: pointer;
  transition: background-color var(--transition-fast);
}

.st-collapse-item__trigger:hover:not(:disabled) {
  background-color: var(--st-fill-hover);
}

/* 内嵌焦点环：触发器铺满整行，外扩的 ring 会被相邻项或外框遮住 */
.st-collapse-item__trigger:focus-visible {
  outline: none;
  box-shadow: inset var(--st-focus-ring);
}

.st-collapse-item__trigger:disabled {
  color: var(--st-text-disabled);
  cursor: not-allowed;
}

.st-collapse-item__title {
  flex: 1 1 auto;
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
}

.st-collapse-item__arrow {
  color: var(--st-placeholder);
  transition: transform var(--transition);
}

/* reka-ui 在触发器上写 data-state="open|closed" */
.st-collapse-item__trigger[data-state='open'] .st-collapse-item__arrow {
  transform: rotate(180deg);
}

.st-collapse-item__extra {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  flex: none;
  padding-right: 16px;
  color: var(--st-placeholder);
  font-size: var(--st-font-small);
}

/* ==================== 内容 ==================== */
.st-collapse-item__content {
  overflow: hidden;
  font-size: var(--st-font-medium);
  line-height: 1.7;
  color: var(--st-text);
}

.st-collapse-item__body {
  padding: 0 16px 14px;
}

@media (prefers-reduced-motion: reduce) {
  .st-collapse-item,
  .st-collapse-item__trigger,
  .st-collapse-item__arrow {
    transition: none;
  }
}
</style>
