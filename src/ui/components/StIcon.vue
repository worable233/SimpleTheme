<script setup lang="ts">
/**
 * StIcon — 统一图标组件（Tabler SVG）
 *
 * 取代原 src/components/AppIcon.vue。接受语义名 / 旧 bx 类名 / fa 类名 /
 * ti 类名 / `<i>` HTML，经解析器归一化后渲染对应 Tabler 组件。
 */
import { computed } from 'vue'
import { resolveIconName } from '@/lib/icon-resolver'
import { ICON_COMPONENTS, ICON_COMPONENTS_FILLED } from '@/lib/tabler-icons.generated'

defineOptions({ name: 'StIcon' })

const props = withDefaults(
  defineProps<{
    /** 图标标识：语义名 / bx|fa|ti 类名 / `<i>` HTML */
    name: string
    /** 使用实心变体（若存在） */
    filled?: boolean
    /** 尺寸（px 或带单位字符串） */
    size?: number | string
    /** 描边宽度 */
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
  <component :is="component" class="st-icon" :size="size" :stroke="stroke" aria-hidden="true" />
</template>

<style scoped>
.st-icon {
  display: inline-block;
  flex: none;
  vertical-align: -0.15em;
}
</style>
