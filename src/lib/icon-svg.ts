/**
 * icon-svg — 用生成好的节点字符串渲染 Tabler SVG
 *
 * 前端只需要「画出来」，不需要 @tabler/icons-vue 的 271 个 Vue 组件（打进来
 * 是几百 KB）。`ICON_NODES` 里的内部节点串本来就已经在包里（正文图标
 * prose-icons 在用），这里直接拿它拼 `<svg>`，属性与 Tabler 组件保持一致，
 * 视觉上完全等价。
 *
 * 图标选择器（StIconPicker）预览也走这里，只是从 ICON_NAMES 里取清单。
 */
import { h, type VNode } from 'vue'
import { ICON_NODES, ICON_NODES_FILLED } from './tabler-icons.generated'

const SVG_NS = 'http://www.w3.org/2000/svg'

export interface IconSvgOptions {
  /** 使用实心变体（若该图标有 filled 版） */
  filled?: boolean
  /** 尺寸（px 数字或带单位字符串） */
  size?: number | string
  /** 描边宽度 */
  stroke?: number
  /** 额外 class（与 `ti` 并列） */
  class?: string
}

/** 取图标的内部节点串；找不到时回退到默认圆圈 */
export function iconInner(name: string, filled = false): { inner: string; filled: boolean } {
  const useFilled = filled && !!ICON_NODES_FILLED[name]
  const inner = (useFilled ? ICON_NODES_FILLED[name] : ICON_NODES[name]) || ICON_NODES['circle'] || ''
  return { inner, filled: useFilled }
}

/**
 * 生成 svg 元素的 vnode。
 * `innerHTML` 写入的是本项目生成的可信常量（同 prose-icons 的处理方式），
 * 不经过 HTML 解析用户输入。
 */
export function iconSvg(name: string, options: IconSvgOptions = {}): VNode {
  const { inner, filled } = iconInner(name, options.filled)
  const size = options.size ?? 20
  const attrs: Record<string, unknown> = {
    xmlns: SVG_NS,
    viewBox: '0 0 24 24',
    width: size,
    height: size,
    class: options.class ? ['ti', options.class] : 'ti',
    'aria-hidden': 'true',
    focusable: 'false',
    innerHTML: inner,
  }
  if (filled) {
    attrs.fill = 'currentColor'
    attrs.stroke = 'none'
  } else {
    attrs.fill = 'none'
    attrs.stroke = 'currentColor'
    attrs['stroke-width'] = options.stroke ?? 2
    attrs['stroke-linecap'] = 'round'
    attrs['stroke-linejoin'] = 'round'
  }
  return h('svg', attrs)
}
