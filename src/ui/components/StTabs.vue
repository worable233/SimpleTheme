<script lang="ts">
/**
 * StTabs / StTabPane 的共享上下文。
 *
 * 为什么不用"遍历默认插槽 vnode 提取 label/value"：调用方完全可能用 v-for、
 * 条件渲染或包裹一层 Fragment，vnode 结构既不稳定也拿不到响应式更新。
 * 改成子组件主动注册，标签头与内容各自独立，与渲染结构彻底解耦。
 */
import type { Component, ComputedRef, InjectionKey, ModelRef } from 'vue'
import type { StSize } from '../types'

/** StTabPane 向 StTabs 注册的元信息。
 * 字段一律是 thunk：StTabs 在 computed 里调用它们，才能追踪到 pane 的 props 变化。 */
export interface StTabPaneRegistration {
  value: string | number
  /** 标签文本 */
  label: () => string
  disabled: () => boolean
  /** 图标名，由 StTabs 用 StIcon 渲染在文本左侧 */
  icon: () => string | undefined
  /** #label 渲染函数；返回 undefined 表示用 label 文本 */
  renderLabel: () => Component | undefined
}

export interface StTabsContext {
  /** 当前值。StTabPane 只读，写一律经过 TabsRoot 的 update 事件 */
  modelValue: ModelRef<string | number | undefined>
  type: ComputedRef<'line' | 'segment'>
  size: ComputedRef<StSize>
  registerPane: (pane: StTabPaneRegistration) => void
  unregisterPane: (value: string | number) => void
}

export const ST_TABS_CONTEXT: InjectionKey<StTabsContext> = Symbol('StTabs')
</script>

<script setup lang="ts">
/**
 * StTabs — 标签页容器（与 StTabPane 配套）
 *
 * 用法：
 *   <StTabs v-model:value="tab" type="segment">
 *     <StTabPane label="文章" value="post">…</StTabPane>
 *     <StTabPane label="页面" value="page">…</StTabPane>
 *   </StTabs>
 *
 * 职责划分：StTabs 只渲染标签头（TabsList/TabsTrigger），内容面板由 StTabPane
 * 自己渲染 TabsContent。键盘方向键导航由 reka-ui 的 RovingFocus 提供。
 */
import { computed, provide, ref } from 'vue'
import { TabsList, TabsRoot, TabsTrigger } from 'reka-ui'
import StIcon from './StIcon.vue'
// StSize 由上方普通 <script> 块导入：两个 script 块会被合并进同一模块作用域，
// 这里再 import 一次会触发 TS2300 Duplicate identifier。

defineOptions({ name: 'StTabs' })

const props = withDefaults(
  defineProps<{
    /** line = 下划线（默认）；segment = 分段控件，选中项浮起 */
    type?: 'line' | 'segment'
    size?: StSize
    /** 标签栏等分撑满容器宽度 */
    block?: boolean
  }>(),
  {
    type: 'line',
    size: 'medium',
    block: false,
  },
)

const model = defineModel<string | number>('value')

/** reka-ui 的 TabsTrigger 内部会自动生成 id/aria 关联，图标尺寸只跟档位走 */
const ICON_SIZE: Record<StSize, number> = { tiny: 14, small: 15, medium: 16, large: 18 }

const registrations = ref<StTabPaneRegistration[]>([])

function registerPane(pane: StTabPaneRegistration) {
  const index = registrations.value.findIndex((item) => item.value === pane.value)
  // 重名（HMR 重挂载 / value 复用）时覆盖旧记录，标签头顺序始终等于子组件挂载顺序
  if (index === -1) registrations.value = [...registrations.value, pane]
  else registrations.value.splice(index, 1, pane)
}

function unregisterPane(value: string | number) {
  registrations.value = registrations.value.filter((item) => item.value !== value)
}

/** 展开注册表：computed 里读取 thunk，从而订阅到各 pane 的 props 变化 */
const panes = computed(() =>
  registrations.value.map((pane) => ({
    value: pane.value,
    label: pane.label(),
    disabled: pane.disabled(),
    icon: pane.icon(),
    renderLabel: pane.renderLabel(),
  })),
)

provide<StTabsContext>(ST_TABS_CONTEXT, {
  modelValue: model,
  type: computed(() => props.type),
  size: computed(() => props.size),
  registerPane,
  unregisterPane,
})
</script>

<template>
  <TabsRoot
    v-model="model"
    class="st-tabs"
    :class="[`st-tabs--${type}`, `st-tabs--${size}`, { 'st-tabs--block': block }]"
  >
    <TabsList class="st-tabs__list">
      <TabsTrigger
        v-for="pane in panes"
        :key="pane.value"
        class="st-tabs__tab"
        :value="pane.value"
        :disabled="pane.disabled"
      >
        <StIcon v-if="pane.icon" class="st-tabs__icon" :name="pane.icon" :size="ICON_SIZE[size]" />
        <span class="st-tabs__label">
          <component :is="pane.renderLabel" v-if="pane.renderLabel" />
          <template v-else>{{ pane.label }}</template>
        </span>
      </TabsTrigger>
    </TabsList>

    <div class="st-tabs__body"><slot /></div>
  </TabsRoot>
</template>

<style scoped>
/* ==================== 基础 ==================== */
.st-tabs {
  --st-tabs-tab-height: var(--st-height-medium);
  --st-tabs-font: var(--st-font-medium);
  --st-tabs-pad: 14px;

  display: flex;
  flex-direction: column;
  box-sizing: border-box;
  color: var(--st-text);
}

.st-tabs__list {
  display: flex;
  align-items: stretch;
  box-sizing: border-box;
}

.st-tabs__tab {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  box-sizing: border-box;
  min-width: 0;
  height: var(--st-tabs-tab-height);
  padding: 0 var(--st-tabs-pad);
  border: none;
  background-color: transparent;
  color: inherit;
  font-family: inherit;
  font-size: var(--st-tabs-font);
  line-height: 1;
  white-space: nowrap;
  cursor: pointer;
  transition:
    color var(--transition-fast),
    background-color var(--transition-fast),
    border-color var(--transition-fast),
    box-shadow var(--transition-fast);
}

.st-tabs__tab:focus-visible {
  outline: none;
  box-shadow: var(--st-focus-ring);
}

/* reka-ui 会给禁用的触发器加 disabled 属性与 data-disabled */
.st-tabs__tab:disabled,
.st-tabs__tab[data-disabled] {
  color: var(--st-text-disabled);
  cursor: not-allowed;
}

.st-tabs__icon {
  color: inherit;
}

.st-tabs__label {
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
}

.st-tabs__body {
  margin-top: 12px;
}

/* ==================== 尺寸 ==================== */
.st-tabs--tiny {
  --st-tabs-tab-height: var(--st-height-tiny);
  --st-tabs-font: var(--st-font-tiny);
  --st-tabs-pad: 8px;
}

.st-tabs--small {
  --st-tabs-tab-height: var(--st-height-small);
  --st-tabs-font: var(--st-font-small);
  --st-tabs-pad: 10px;
}

.st-tabs--large {
  --st-tabs-tab-height: var(--st-height-large);
  --st-tabs-font: var(--st-font-large);
  --st-tabs-pad: 18px;
}

/* ==================== line ==================== */
.st-tabs--line .st-tabs__list {
  gap: 4px;
  border-bottom: 1px solid var(--st-border);
}

/* 负外边距让 2px 指示线压在列表底边上，避免出现双线 */
.st-tabs--line .st-tabs__tab {
  margin-bottom: -1px;
  border-bottom: 2px solid transparent;
  color: var(--st-placeholder);
}

.st-tabs--line .st-tabs__tab:hover:not(:disabled) {
  color: var(--st-text);
}

.st-tabs--line .st-tabs__tab[data-state='active'] {
  border-bottom-color: var(--primary);
  color: var(--st-text);
  font-weight: 600;
}

/* ==================== segment ==================== */
.st-tabs--segment .st-tabs__list {
  gap: 2px;
  padding: 2px;
  border-radius: var(--radius-medium);
  background-color: var(--st-fill-active);
}

/* 轨道自带内边距，标签需减去对应高度才不至于比普通控件更高 */
.st-tabs--segment .st-tabs__tab {
  height: calc(var(--st-tabs-tab-height) - 6px);
  border-radius: var(--radius-small);
  color: var(--st-placeholder);
}

.st-tabs--segment .st-tabs__tab:hover:not(:disabled):not([data-state='active']) {
  color: var(--st-text);
}

.st-tabs--segment .st-tabs__tab[data-state='active'] {
  background-color: var(--st-fill);
  box-shadow: var(--shadow-small);
  color: var(--st-text);
}

/* ==================== block ==================== */
.st-tabs--block .st-tabs__tab {
  flex: 1 1 0;
}

@media (prefers-reduced-motion: reduce) {
  .st-tabs__tab {
    transition: none;
  }
}
</style>
