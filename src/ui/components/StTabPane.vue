<script setup lang="ts">
/**
 * StTabPane — 单个标签页（必须嵌套在 StTabs 内）
 *
 * 用法：
 *   <StTabPane label="文章" value="post" icon="file-text">…</StTabPane>
 *   <StTabPane value="draft">
 *     <template #label>草稿 <StBadge>3</StBadge></template>
 *     …
 *   </StTabPane>
 *
 * 标签头由 StTabs 统一渲染，本组件只负责：把 label/value/icon 注册给父级，
 * 并渲染自己的内容面板（reka-ui TabsContent）。
 *
 * 内容面板选择惰性挂载（不激活即不渲染）而不是 v-show：面板里常放图表、
 * 列表、表单等重组件，隐藏挂载照样会发请求和跑布局；代价是切回来会丢失
 * 面板内部状态，需要保留状态的场景请在调用方用 <KeepAlive> 包住，或把状态
 * 提升到 StTabs 之外（这也是 reka-ui TabsRoot.unmountOnHide 的默认语义）。
 */
import { inject, onBeforeUnmount, useSlots } from 'vue'
import type { Component } from 'vue'
import { TabsContent } from 'reka-ui'
import { ST_TABS_CONTEXT } from './StTabs.vue'

defineOptions({ name: 'StTabPane' })

const props = withDefaults(
  defineProps<{
    /** 标签文本；提供 #label 插槽时由插槽内容取代 */
    label: string
    /** 唯一标识，与 v-model:value 对比 */
    value: string | number
    disabled?: boolean
    /** 图标名，由 StTabs 用 StIcon 渲染在文本左侧 */
    icon?: string
  }>(),
  {
    disabled: false,
    icon: undefined,
  },
)

const slots = useSlots()

const tabs = inject(ST_TABS_CONTEXT, null)
if (!tabs) {
  throw new Error('[StTabPane] 必须嵌套在 <StTabs> 内使用')
}

/** 保持函数标识稳定，否则 StTabs 每次重算都会换掉组件类型导致标签头重挂载 */
const renderLabel: Component = () => slots.label?.()

tabs.registerPane({
  value: props.value,
  label: () => props.label,
  disabled: () => props.disabled,
  icon: () => props.icon,
  renderLabel: () => (slots.label ? renderLabel : undefined),
})

onBeforeUnmount(() => tabs.unregisterPane(props.value))
</script>

<template>
  <TabsContent :value="value" class="st-tab-pane__panel">
    <div class="st-tab-pane"><slot /></div>
  </TabsContent>
</template>

<style scoped>
.st-tab-pane {
  box-sizing: border-box;
  color: var(--st-text);
  font-size: var(--st-font-medium);
  line-height: 1.7;
}

/* reka-ui 给面板加了 tabindex="0"，用焦点环替代浏览器默认 outline；
   类名落在 reka 渲染出的 tabpanel 元素上（子组件根节点继承父作用域标记） */
.st-tab-pane__panel:focus-visible {
  outline: none;
  border-radius: var(--radius-small);
  box-shadow: var(--st-focus-ring);
}
</style>
