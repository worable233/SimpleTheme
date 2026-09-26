/**
 * 区块渲染器：DOM 节点 → Vue VNode 的递归映射。
 *
 * 设计要点：
 *   - `renderNode` 遇到已登记区块名时调用对应组件；否则按「同标签 + 原属性 +
 *     递归子节点」透传。透传保证未知区块与编辑器自定义样式永远不丢。
 *   - 组件通过 `renderChildren(el)` 拿到已转换的子 VNode，在自定义结构里嵌入
 *     子区块（如列、封面、媒体文字）。
 *   - 组件通过 `attrsOf(el)` 转发原始属性（含 class / style / data-*）。
 *   - SVG / MathML 名称空间特殊：`document.createElement('svg')` 会得到
 *     HTMLUnknownElement，Vue 的运行时 createElement 也走 HTML 分支，
 *     只有 `innerHTML` 才按名称空间解析。故这类子树交给 `RawNode` 原样注入。
 */
import { createTextVNode, defineComponent, h, type Component, type PropType, type VNode } from 'vue'

/** 已登记区块组件（由 registry.ts 在启动时填充）。 */
const REGISTRY = new Map<string, Component>()

/** 登记一个区块名 → 组件。 */
export function registerBlock(name: string, component: Component): void {
  REGISTRY.set(name, component)
}

/** 是否已登记该区块名。 */
export function hasBlock(name: string): boolean {
  return REGISTRY.has(name)
}

/** 已知区块名集合。 */
export function knownBlockNames(): string[] {
  return [...REGISTRY.keys()]
}

const WP_BLOCK_CLASS = /^wp-block-(.+)$/

/**
 * 从元素的 class 里解析区块名。
 *
 * 只认 `wp-block-<suffix>` 且 `<suffix>` 已在注册表登记，避免把
 * `wp-block-buttons-is-layout-flex` 这类布局类名误当成区块。
 */
export function detectBlockName(el: Element): string | null {
  const list = (el as HTMLElement).classList
  for (let i = 0; i < list.length; i++) {
    const m = WP_BLOCK_CLASS.exec(list[i])
    if (m && REGISTRY.has(m[1])) return m[1]
  }
  return null
}

/** 取元素全部属性为普通对象（跳过 Vue 作用域属性）。 */
export function attrsOf(el: Element): Record<string, string> {
  const out: Record<string, string> = {}
  for (const attr of Array.from(el.attributes)) {
    if (attr.name.startsWith('data-v-')) continue
    out[attr.name] = attr.value
  }
  return out
}

/** 转发属性并合并额外 class（给通用容器补主题类名）。 */
export function attrsWithClass(
  el: Element,
  ...extra: (string | undefined | false | null)[]
): Record<string, string> {
  const attrs = attrsOf(el)
  const merged = [attrs.class, ...extra].filter(Boolean).join(' ')
  if (merged) attrs.class = merged
  else delete attrs.class
  return attrs
}

/** 递归渲染元素子节点为 VNode 数组。 */
export function renderChildren(el: Element): VNode[] {
  return Array.from(el.childNodes)
    .map(renderNode)
    .filter((n): n is VNode => n !== null)
}

const SVG_NS = 'http://www.w3.org/2000/svg'
const MATHML_NS = 'http://www.w3.org/1998/Math/MathML'

/**
 * 原样注入子树（SVG / MathML）。
 * 用 `display:contents` 的包装元素，让包装自身不产生盒子，
 * 子树与直接内联的观感一致。
 */
const RawNode = defineComponent({
  name: 'StBlockRaw',
  props: { el: { type: Object as PropType<Element>, required: true } },
  setup(props) {
    return () =>
      h('span', {
        class: 'st-block-raw',
        style: 'display:contents',
        innerHTML: (props.el as Element).outerHTML,
      })
  },
})

/**
 * RenderNodes — 把预先生成好的 VNode 数组渲染出来（Fragment）。
 * 供容器类区块在自定义结构里嵌入「已转换的子区块」。
 */
export const RenderNodes = defineComponent({
  name: 'StBlockRenderNodes',
  props: { nodes: { type: Array as PropType<VNode[]>, default: () => [] } },
  setup(props) {
    return () => props.nodes as VNode[]
  },
})

/** 渲染单个 DOM 节点。文本节点原样输出，注释忽略。 */
export function renderNode(node: Node): VNode | null {
  if (node.nodeType === Node.TEXT_NODE) {
    return createTextVNode(node.textContent || '')
  }
  if (node.nodeType !== Node.ELEMENT_NODE) return null
  const el = node as Element
  const ns = el.namespaceURI
  if (ns === SVG_NS || ns === MATHML_NS) return h(RawNode, { el })
  const name = detectBlockName(el)
  const comp = name ? REGISTRY.get(name) : undefined
  if (comp) return h(comp, { el })
  // 透传：同标签 + 原属性 + 递归子节点。
  return h(el.tagName.toLowerCase(), attrsOf(el), renderChildren(el))
}
