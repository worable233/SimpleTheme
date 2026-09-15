<script lang="ts">
/**
 * StCollapse / StCollapseItem 的共享上下文。
 *
 * reka-ui 的 AccordionItem.value 只接受 string，而调用方的 name 可能是 number，
 * 所以容器侧维护一张「String(name) → 原始 name」的注册表：向外 emit 时把
 * reka 的 string 还原成调用方给的原始值，避免 1 与 '1' 混用。
 */
import type { ComputedRef, InjectionKey } from 'vue'

export interface StCollapseContext {
  /** 外观态，子项据此决定分隔线与圆角 */
  bordered: ComputedRef<boolean>
  /** 登记本项的原始 name */
  registerItem: (name: string | number) => void
  unregisterItem: (name: string | number) => void
  /** 把 reka 的 string key 还原成调用方的原始 name */
  resolveName: (key: string) => string | number
}

export const ST_COLLAPSE_CONTEXT: InjectionKey<StCollapseContext> = Symbol('StCollapse')
</script>

<script setup lang="ts">
/**
 * StCollapse — 折叠面板容器（与 StCollapseItem 配套）
 *
 * 用法：
 *   <StCollapse v-model:expanded="open">
 *     <StCollapseItem title="基础" name="basic">…</StCollapseItem>
 *     <StCollapseItem title="高级" name="advanced">…</StCollapseItem>
 *   </StCollapse>
 *
 * 展开态由 reka-ui AccordionRoot 持有（含方向键、Home/End 导航与 aria 关联），
 * 本组件只做两件事：在「数组 v-model」与「reka 的单值/数组」之间转换，
 * 以及下发 items 注册表。
 */
import { computed, provide, ref } from 'vue'
import { AccordionRoot } from 'reka-ui'

defineOptions({ name: 'StCollapse' })

const props = withDefaults(
  defineProps<{
    /** 手风琴模式：同时只展开一项 */
    accordion?: boolean
    /** 带外框与分隔线 */
    bordered?: boolean
    /** 非受控时的初始展开项 */
    defaultExpanded?: (string | number)[]
  }>(),
  {
    accordion: false,
    bordered: true,
    defaultExpanded: () => [],
  },
)

const expanded = defineModel<(string | number)[]>('expanded', { default: () => [] })

// defaultExpanded 只在初始为空时播种一次（非受控默认值语义）；之后完全由 v-model 说了算，
// 否则用户手动收起最后一项会被"初值"再弹开
if (props.defaultExpanded.length > 0 && expanded.value.length === 0) {
  expanded.value = [...props.defaultExpanded]
}

/** 已挂载子项的原始 name（去重），供 string → number 还原 */
const registeredNames = ref<(string | number)[]>([])

function registerItem(name: string | number) {
  if (registeredNames.value.some((item) => String(item) === String(name))) return
  registeredNames.value = [...registeredNames.value, name]
}

function unregisterItem(name: string | number) {
  registeredNames.value = registeredNames.value.filter((item) => String(item) !== String(name))
}

function resolveName(key: string): string | number {
  const found = registeredNames.value.find((item) => String(item) === key)
  return found === undefined ? key : found
}

/** reka 的入参：手风琴用单值，多选用数组 —— 数组形态会被 reka 判定为 multiple */
const innerValue = computed<string | string[] | undefined>({
  get() {
    const keys = expanded.value.map((name) => String(name))
    return props.accordion ? keys[0] : keys
  },
  set(value) {
    const keys = value === undefined ? [] : Array.isArray(value) ? value : [value]
    expanded.value = keys.map((key) => resolveName(key))
  },
})

provide<StCollapseContext>(ST_COLLAPSE_CONTEXT, {
  bordered: computed(() => props.bordered),
  registerItem,
  unregisterItem,
  resolveName,
})
</script>

<template>
  <AccordionRoot
    v-model="innerValue"
    class="st-collapse"
    :class="{ 'st-collapse--bordered': bordered }"
    :type="accordion ? 'single' : 'multiple'"
    :collapsible="accordion"
  >
    <slot />
  </AccordionRoot>
</template>

<style scoped>
.st-collapse {
  box-sizing: border-box;
  color: var(--st-text);
}

.st-collapse--bordered {
  border: 1px solid var(--st-border);
  border-radius: var(--radius-large);
}

/* 不给容器加 overflow: hidden —— 那会裁掉子项的焦点环；
   改为让首尾子项自己吃掉圆角，内部 hover 背景就不会顶破外框 */
.st-collapse--bordered :deep(.st-collapse-item:first-child) {
  border-start-start-radius: calc(var(--radius-large) - 1px);
  border-start-end-radius: calc(var(--radius-large) - 1px);
}

.st-collapse--bordered :deep(.st-collapse-item:last-child) {
  border-end-start-radius: calc(var(--radius-large) - 1px);
  border-end-end-radius: calc(var(--radius-large) - 1px);
}

/* 分隔线由容器控制：子项无法知道自己是第几项 */
.st-collapse--bordered :deep(.st-collapse-item + .st-collapse-item) {
  border-top: 1px solid var(--st-border);
}
</style>
