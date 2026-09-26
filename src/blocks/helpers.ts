/**
 * 区块组件共享辅助。
 *
 * 所有区块组件都接收 `el`（服务端渲染出来的原始根元素）。这里提供几组
 * 一致的读取方式，避免每个组件各写各的：
 *   - `useBlockEl` 拿到根元素、其子 VNode、其属性；
 *   - `innerBlocks` 取子区块（渲染成 VNode，供容器类区块嵌套使用）。
 */
import { computed, type PropType, type VNode } from 'vue'
import { attrsOf, attrsWithClass, renderChildren, renderNode } from './renderer'

/** 所有区块组件统一的 props。 */
export const blockElProp = {
  el: { type: Object as PropType<Element>, required: true },
} as const

/** 在 setup 中可以这样用：`const { el, attrs, children } = useBlock(props)`。 */
export function useBlock(props: { el: Element }, ...extraClass: string[]) {
  const el = computed(() => props.el as HTMLElement)
  const attrs = computed(() => attrsWithClass(props.el, ...extraClass))
  const rawAttrs = computed(() => attrsOf(props.el))
  const children = computed<VNode[]>(() => renderChildren(props.el))
  const child = (index: number): VNode | null => {
    const node = props.el.childNodes[index]
    return node ? renderNode(node) : null
  }
  return { el, attrs, rawAttrs, children, child }
}

/** 取带某 class 的第一个子元素（用于定位子部位）。 */
export function queryChild(el: Element, selector: string): Element | null {
  return el.querySelector(selector)
}

/** 取元素的文本。 */
export function textOf(el: Element | null): string {
  return el?.textContent?.trim() || ''
}
