<script setup lang="ts">
/**
 * core/math 区块。
 *
 * 服务端已把 LaTeX 渲染成 MathML（`<math><semantics>…`）。Vue 运行时无法为
 * `math` / `mi` 等标签创建 MathML 名称空间（`document.createElement` 只会得到
 * HTMLUnknownElement），因此用 `v-html` 注入原始 innerHTML，交给 HTML 解析器
 * 按 MathML 名称空间处理。主题钩子类 `st-block-math` 负责滚动与间距。
 */
import { computed } from 'vue'
import { blockElProp, useBlock } from '../helpers'

defineOptions({ name: 'StBlockMath' })

const props = defineProps(blockElProp)
const { attrs } = useBlock(props, 'st-block-math')
const inner = computed(() => (props.el as Element).innerHTML)
</script>

<template>
  <div v-bind="attrs" v-html="inner" />
</template>
