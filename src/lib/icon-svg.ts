/**
 * icon-svg — 用生成好的节点字符串渲染 Tabler SVG
 *
 * 图标数据分两层：
 *   - 核心图标（ICON_NODES_CORE，同步、随主包）——首屏和正文常用，绝不闪；
 *   - 全量图标（tabler-icons.generated，`import()` 按需加载）——用户自定义
 *     菜单图标等冷门图标，用到时才请求一次，之后常驻内存。
 * 全量数据到位后 dataVersion 自增，依赖它的组件（AppIcon/StIcon）会自动重算，
 * 于是「先空一帧、再显示」而不是显示错图标。
 *
 * 渲染不用 @tabler/icons-vue（组件里重复带了一份路径数据，几百 KB）。
 */
import { h, ref, type VNode } from 'vue'
import { ICON_NODES_CORE, ICON_NODES_FILLED_CORE } from './tabler-icons-core.generated'

const SVG_NS = 'http://www.w3.org/2000/svg'

/** 全量数据版本号；加载完成后自增，触发依赖方重渲染 */
const dataVersion = ref(0)

let fullNodes: Record<string, string> | null = null
let fullNodesFilled: Record<string, string> | null = null
let loadPromise: Promise<void> | null = null

/** 全量图标数据是否已经就位（用于区分「还没加载」和「确实没这个图标」） */
export function isIconDataLoaded(): boolean {
  return !!fullNodes
}

/** 触发（或复用）全量图标数据的加载；可重复调用 */
export function ensureIconDataLoaded(): Promise<void> {
  if (fullNodes) return Promise.resolve()
  if (loadPromise) return loadPromise
  loadPromise = import('./tabler-icons.generated')
    .then((mod) => {
      fullNodes = mod.ICON_NODES
      fullNodesFilled = mod.ICON_NODES_FILLED
      dataVersion.value++
    })
    .catch(() => {
      // 网络失败（离线）时别把 promise 缓存成永久失败，下次还能重试
      loadPromise = null
    })
  return loadPromise
}

/**
 * 取图标的内部节点串：先查核心，再查已加载的全量数据。
 * 返回 null 表示「还没加载到」——调用方可以据此触发加载或先占位。
 */
export function iconInner(
  name: string,
  filled = false,
): { inner: string; filled: boolean } | null {
  const filledInner = ICON_NODES_FILLED_CORE[name] || fullNodesFilled?.[name]
  const outlineInner = ICON_NODES_CORE[name] || fullNodes?.[name]
  if (filled && filledInner) return { inner: filledInner, filled: true }
  if (outlineInner) return { inner: outlineInner, filled: false }
  return null
}

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

/** 生成 svg 元素的 vnode（数据缺失时用圆圈占位并自动加载全量数据） */
export function iconSvg(name: string, options: IconSvgOptions = {}): VNode {
  // 读一次版本号：全量数据加载完成后这个 computed 会重算，图标就补上了
  void dataVersion.value

  let data = iconInner(name, options.filled)
  if (!data) {
    void ensureIconDataLoaded()
    data = iconInner('circle', options.filled) || { inner: '', filled: false }
  }

  const size = options.size ?? 20
  const attrs: Record<string, unknown> = {
    xmlns: SVG_NS,
    viewBox: '0 0 24 24',
    width: size,
    height: size,
    class: options.class ? ['ti', options.class] : 'ti',
    // 便于排查「哪个图标没进核心集合」，也方便自定义样式精确命中
    'data-icon': name,
    'aria-hidden': 'true',
    focusable: 'false',
    // inner 是生成的可信常量（同 prose-icons），不经过 HTML 解析用户输入
    innerHTML: data.inner,
  }
  if (data.filled) {
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
