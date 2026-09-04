/**
 * prose-icons — 正文图标内联
 *
 * WordPress 正文（含 sakurairo showcard 块）里保存的是 `<i class="bx ...">`/
 * `<i class="ti ...">`/FontAwesome `<i>` 图标字体标签。前端不再加载图标字体，
 * 因此在正文渲染后把这些 `<i>` 就地替换为内联 Tabler SVG（复用解析器与生成的
 * 节点数据）。SEO/爬虫路径走纯文本，无需处理。
 */
import { resolveIconName } from './icon-resolver'
import { ICON_NODES, ICON_NODES_FILLED } from './tabler-icons.generated'

/** 判断一个 <i> 的 class 是否是图标字体标记（bx / ti / fa） */
const ICON_CLASS_RE = /(?:^|\s)(bx|ti|fa|fas|far|fab|fal)(?:\s|$)|(?:^|\s)(?:bx|bxs|bxl|ti|fa)-/

const SVG_NS = 'http://www.w3.org/2000/svg'

/**
 * 用安全 DOM API 构建内联 Tabler SVG 节点。
 * inner 为本项目生成的可信常量；style 来自原 <i>（可信正文），经 setAttribute
 * 写入而非 HTML 解析。
 */
function buildSvg(name: string, filled: boolean, style: string | null): SVGElement {
  const useFilled = filled && !!ICON_NODES_FILLED[name]
  const inner = (useFilled ? ICON_NODES_FILLED[name] : ICON_NODES[name]) || ICON_NODES['circle'] || ''

  const svg = document.createElementNS(SVG_NS, 'svg')
  svg.setAttribute('viewBox', '0 0 24 24')
  svg.setAttribute('width', '1em')
  svg.setAttribute('height', '1em')
  svg.setAttribute('class', 'prose-icon')
  svg.setAttribute('aria-hidden', 'true')
  if (useFilled) {
    svg.setAttribute('fill', 'currentColor')
    svg.setAttribute('stroke', 'none')
  } else {
    svg.setAttribute('fill', 'none')
    svg.setAttribute('stroke', 'currentColor')
    svg.setAttribute('stroke-width', '2')
    svg.setAttribute('stroke-linecap', 'round')
    svg.setAttribute('stroke-linejoin', 'round')
  }
  if (style) {
    const safeStyle = style
      .split(';')
      .map((declaration) => declaration.trim())
      .filter(Boolean)
      .map((declaration) => {
        const separator = declaration.indexOf(':')
        if (separator < 1) return null
        const property = declaration.slice(0, separator).trim().toLowerCase()
        const value = declaration.slice(separator + 1).trim()
        if (!['color', 'font-size', 'width', 'height', 'opacity'].includes(property)) return null
        if (!value || /[<>{};()]/.test(value)) return null
        return `${property}:${value}`
      })
      .filter((declaration): declaration is string => !!declaration)
      .join(';')
    if (safeStyle) svg.setAttribute('style', safeStyle)
  }
  // inner 是可信常量（生成于 @tabler/icons 节点数据），非用户动态输入
  svg.innerHTML = inner
  return svg
}

/**
 * 把容器内所有图标字体 `<i>` 替换为内联 Tabler SVG。幂等（替换后无 `<i>` 可再匹配）。
 */
export function inlineProseIcons(container: Element): void {
  const items = container.querySelectorAll('i[class]')
  for (const el of items) {
    const cls = el.getAttribute('class') || ''
    if (!ICON_CLASS_RE.test(cls)) continue
    const { name, filled } = resolveIconName(cls)
    el.replaceWith(buildSvg(name, filled, el.getAttribute('style')))
  }
}
