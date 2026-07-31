<script setup lang="ts">
/**
 * AppIcon — 统一图标组件（Tabler SVG）
 *
 * 接受语义名 / 旧 bx 类名 / fa 类名 / ti 类名 / `<i>` HTML，经解析器归一化后
 * 渲染对应的 Tabler Vue 组件。找不到时回退到默认圆圈。
 */
import { computed } from 'vue'
import { resolveIconName } from '@/lib/icon-resolver'
import {
  ICON_COMPONENTS,
  ICON_COMPONENTS_FILLED,
} from '@/lib/tabler-icons.generated'

const props = withDefaults(
  defineProps<{
    /** 图标标识：语义名 / bx|fa|ti 类名 / `<i>` HTML */
    name: string
    /** 使用实心变体（若存在） */
    filled?: boolean
    /** 尺寸（px 或带单位字符串），默认 20 */
    size?: number | string
    /** 描边宽度，默认 2 */
    stroke?: number
  }>(),
  {
    filled: false,
    size: 20,
    stroke: 2,
  },
)

const resolved = computed(() => resolveIconName(props.name))

const component = computed(() => {
  const { name } = resolved.value
  const filled = props.filled || resolved.value.filled
  if (filled && ICON_COMPONENTS_FILLED[name]) return ICON_COMPONENTS_FILLED[name]
  return ICON_COMPONENTS[name] || ICON_COMPONENTS['circle']
})
</script>

<template>
  <component :is="component" :size="size" :stroke="stroke" aria-hidden="true" />
</template>
