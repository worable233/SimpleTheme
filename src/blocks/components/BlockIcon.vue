<script setup lang="ts">
/**
 * core/icon 区块。
 *
 * 服务端输出内联 SVG，JSX 层无法可靠地按名称空间重建，故：
 *   - 保留原 `<div class="wp-block-icon">` 包裹与全部属性；
 *   - 用 `v-html` 注入原始 innerHTML（含 `<svg>`），由浏览器按 SVG 名称空间解析。
 * 主题钩子类 `st-block-icon` 负责尺寸与对齐（`blocks.css`）。
 */
import { computed } from 'vue'
import { blockElProp, useBlock } from '../helpers'

defineOptions({ name: 'StBlockIcon' })

const props = defineProps(blockElProp)
const { attrs } = useBlock(props, 'st-block-icon')
const inner = computed(() => (props.el as Element).innerHTML)
</script>

<template>
  <div v-bind="attrs" v-html="inner" />
</template>
