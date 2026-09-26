/**
 * usePageTransition — 移动端「二级页推进」页面转场
 *
 * 仅在移动布局（≤75rem / 1200px）下，且导航为「文章列表 ⇄ 文章详情」时启用：
 *   - forward（列表 → 详情）：详情页自右滑入盖住列表；列表轻微左移并压暗。
 *   - back（详情 → 列表）：详情页右滑退出；列表自左侧归位。
 *
 * 转场期间把两个页面都固定为「内容列」尺寸的独立滚动容器（`route-page--fixed`）：
 *   1. 离场页停在导航前的滚动位置、被新页盖住（文档高度塌陷也不会跳动）；
 *   2. 入场页从顶部开始，结束后回到正常文档流并还原其历史滚动位置。
 *
 * 视觉样式见 src/styles/page-transition.css。
 */
import { computed, ref } from 'vue'
import type { RouteLocationNormalized, RouteLocationNormalizedLoaded } from 'vue-router'

export type PageTransitionDirection = 'forward' | 'back' | 'none'

/** 与移动顶栏 / 移动布局一致：≤75rem（1200px） */
const MOBILE_LAYOUT_QUERY = '(max-width: 75rem)'

/** 移动顶栏高度，与 SidebarMobileHeader.vue 的 .mobile-header height 保持一致 */
const HEADER_H = 56

const direction = ref<PageTransitionDirection>('none')

/** 按 fullPath 记住各页面滚动位置，返回时可还原 */
const scrollPositions = new Map<string, number>()

/** 转场时缓存的几何（横向不受纵向滚动影响，可安全测量） */
let columnLeft = 0
let columnWidth = 0
let leavingScrollY = 0
let enteringScrollY = 0

function isMobileLayout(): boolean {
  return typeof window !== 'undefined' && window.matchMedia(MOBILE_LAYOUT_QUERY).matches
}

function routeName(
  route: RouteLocationNormalized | RouteLocationNormalizedLoaded,
): string | undefined {
  return typeof route.name === 'string' ? route.name : undefined
}

/** 文章列表：首页 / 分类页 */
function isListRoute(route: RouteLocationNormalized | RouteLocationNormalizedLoaded): boolean {
  const name = routeName(route)
  return name === 'home' || name === 'category'
}

/** 文章详情：ContentView 兜底路由 */
function isDetailRoute(route: RouteLocationNormalized | RouteLocationNormalizedLoaded): boolean {
  return routeName(route) === 'content'
}

/** 计算本次导航的转场方向。非移动布局或非列表⇄详情一律 none。 */
export function resolvePageTransition(
  from: RouteLocationNormalized | RouteLocationNormalizedLoaded,
  to: RouteLocationNormalized | RouteLocationNormalizedLoaded,
): PageTransitionDirection {
  if (!isMobileLayout()) return 'none'
  if (isDetailRoute(to) && isListRoute(from)) return 'forward'
  if (isListRoute(to) && isDetailRoute(from)) return 'back'
  return 'none'
}

/**
 * 路由守卫（beforeEach）调用：在 DOM 变更前确定方向、记录几何与滚动位置。
 */
export function setPageTransition(
  from: RouteLocationNormalized | RouteLocationNormalizedLoaded,
  to: RouteLocationNormalized | RouteLocationNormalizedLoaded,
): void {
  const next = resolvePageTransition(from, to)
  direction.value = next
  if (next === 'none') return

  // 记住离场页当前位置，供下次返回还原
  scrollPositions.set(from.fullPath, window.scrollY)
  if (scrollPositions.size > 50) {
    const oldest = scrollPositions.keys().next().value
    if (oldest !== undefined) scrollPositions.delete(oldest)
  }

  leavingScrollY = window.scrollY
  // 前进进入详情页从顶部开始；后退回到列表页还原历史位置
  enteringScrollY = next === 'back' ? (scrollPositions.get(to.fullPath) ?? 0) : 0

  // 量内容列横向范围（纵向滚动不影响 left/width），供固定层对齐
  const main = document.querySelector<HTMLElement>('.app-content__main')
  if (main) {
    const rect = main.getBoundingClientRect()
    columnLeft = rect.left
    columnWidth = rect.width
  } else {
    columnLeft = 0
    columnWidth = window.innerWidth
  }
}

/** router.afterEach 判断是否需要接管滚动（转场时由转场钩子处理） */
export function getPageTransitionDirection(): PageTransitionDirection {
  return direction.value
}

export function usePageTransition() {
  const pageTransitionName = computed(() =>
    direction.value === 'none' ? 'page-none' : `page-${direction.value}`,
  )

  /** 把页面钉在内容列范围，成为独立滚动容器 */
  function pin(el: Element) {
    const node = el as HTMLElement
    node.classList.add('route-page--fixed')
    node.style.left = `${columnLeft}px`
    node.style.width = `${columnWidth}px`
    node.style.top = `${HEADER_H}px`
    node.style.height = `${window.innerHeight - HEADER_H}px`
  }

  function unpin(el: Element) {
    const node = el as HTMLElement
    node.classList.remove('route-page--fixed')
    node.style.left = ''
    node.style.width = ''
    node.style.top = ''
    node.style.height = ''
  }

  function onBeforeLeave(el: Element) {
    if (direction.value === 'none') return
    pin(el)
    ;(el as HTMLElement).scrollTop = leavingScrollY
  }

  function onAfterLeave(el: Element) {
    unpin(el)
  }

  function onBeforeEnter(el: Element) {
    if (direction.value === 'none') return
    pin(el)
    ;(el as HTMLElement).scrollTop = enteringScrollY
  }

  function onAfterEnter(el: Element) {
    const active = direction.value !== 'none'
    unpin(el)
    if (active) {
      // 回到正常文档流后还原入场页的历史滚动位置（详情页通常为 0）
      window.scrollTo(0, enteringScrollY)
    }
    direction.value = 'none'
  }

  return {
    pageTransitionName,
    onBeforeEnter,
    onAfterEnter,
    onBeforeLeave,
    onAfterLeave,
  }
}
