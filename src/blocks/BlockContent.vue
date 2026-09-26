<script setup lang="ts">
/**
 * BlockContent — 正文 HTML 的组件化渲染入口。
 *
 * 取代 `v-html`：把 `content.rendered` 解析成 DOM，再用 `renderChildren`
 * 递归映射为 Vue 组件。已登记区块走主题组件，未登记内容原样透传
 * （保留标签、属性与内联样式），因此区块编辑器设置的一切照常生效。
 *
 * 与 `useContentEnhancer` 的协作：
 *   正文增强（标题锚点、代码块外壳、Fancybox、自定义音频…）仍是挂载后对 DOM
 *   的命令式改写。为避免 Vue 的 diff 与这些改写互相打架，这里用 `key` 让每次
 *   内容变化都**整体重建**根元素：旧子树（含增强产生的未经 Vue 跟踪的节点）
 *   会随根元素一并销毁，不会残留。
 *
 * 用法：
 *   <BlockContent class="prose-content" :html="postData.content.rendered" />
 */
import { computed, shallowRef, watch } from 'vue'
import type { VNode } from 'vue'
import { RenderNodes, renderChildren } from './renderer'
import { registerBlocks } from './registry'

defineOptions({ name: 'BlockContent' })

const props = defineProps<{ html?: string | null }>()

// 启动时确保区块已登记（幂等）。
registerBlocks()

const nodes = shallowRef<VNode[]>([])
let version = 0
const key = shallowRef(0)

function parse(html: string | null | undefined): VNode[] {
  if (!html) return []
  const host = document.createElement('div')
  host.innerHTML = html
  return renderChildren(host)
}

watch(
  () => props.html,
  (val) => {
    nodes.value = parse(val)
    version += 1
    key.value = version
  },
  { immediate: true },
)

const rendered = computed(() => nodes.value)
</script>

<template>
  <!-- 单根 div：父级传入的 class/style/其它属性由 Vue 自动落到它上面 -->
  <div :key="key">
    <RenderNodes :nodes="rendered" />
  </div>
</template>
