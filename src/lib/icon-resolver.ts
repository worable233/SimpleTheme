/**
 * icon-resolver — 图标名称解析器（Tabler）
 *
 * 职责：把各种来源的图标标识（语义名 / 旧 Boxicons 类名 / FontAwesome 类名 /
 * `<i class="...">` HTML / Tabler 类名 / 中文菜单标题）归一化为一个 Tabler
 * kebab 名（如 "brand-github"、"home"）与 filled 布尔，供 AppIcon.vue 渲染
 * 对应的 Tabler SVG 组件，或供 prose-icons 内联 SVG。
 *
 * 兼容：旧站数据库里存的 `bx bxl-github`、`bx bxs-home`、FA 类名、中文标题
 * 全部零改动继续可用（无破坏式迁移）。
 */

import ICON_MAP from './tabler-icon-map.json'
import type { MenuItem } from '@/types/wordpress'

/** 解析结果：name = Tabler kebab 名；filled = 是否使用实心变体 */
export interface ResolvedIcon {
  name: string
  filled: boolean
}

/** 找不到时的兜底图标 */
export const DEFAULT_ICON_NAME = 'circle'

/** 语义名/别名 → Tabler kebab（数据源，供生成脚本与本模块共用） */
const NAME_MAP = ICON_MAP as Record<string, string>

/** 所有合法的 Tabler kebab 值（允许直接传 Tabler 名） */
const VALID_TABLER = new Set<string>(
  Object.entries(NAME_MAP)
    .filter(([k]) => !k.startsWith('_'))
    .map(([, v]) => v),
)

// ============================================================
// 中文标题 → 语义名（向后兼容旧菜单项）
// ============================================================
const TITLE_ICON_MAP: Record<string, string> = {
  首页: 'home',
  说说: 'chat',
  关于: 'about',
  关于我: 'about',
  归档: 'archive',
  文章归档: 'archive',
  友情链接: 'link',
  友链: 'link',
  赞助: 'star',
  赞赏: 'star',
  收藏: 'bookmark',
  标签: 'tag',
  分类: 'folder',
  留言: 'mail',
  留言板: 'mail',
  相册: 'photo',
  音乐: 'music',
  搜索: 'search',
  登录: 'log-in',
  注册: 'user',
  设置: 'settings',
  个人中心: 'user',
  个人资料: 'user-detail',
  动态: 'chat',
  项目: 'grid',
  工具: 'settings',
  学习: 'graduation',
  笔记: 'book',
  日记: 'notepad',
  摄影: 'camera',
  电影: 'video',
  游戏: 'game',
  动漫: 'smile',
  阅读: 'book',
  写作: 'edit',
  生活: 'sun',
  旅行: 'map',
  美食: 'coffee',
  运动: 'dumbbell',
  代码: 'code',
  资源: 'download',
  下载: 'download',
  问答: 'question',
  帮助: 'info',
  反馈: 'mail',
  关于博客: 'info-circle',
  建站日志: 'clock',
  站点地图: 'map',
}

// ============================================================
// FontAwesome 类名 → 语义名（常见项；少数插件保存 fa- 格式）
// ============================================================
const FA_TO_SEMANTIC: Record<string, string> = {
  'fa-home': 'home',
  'fa-house': 'home',
  'fa-bars': 'menu',
  'fa-search': 'search',
  'fa-magnifying-glass': 'search',
  'fa-user': 'user',
  'fa-users': 'group',
  'fa-gear': 'settings',
  'fa-cog': 'settings',
  'fa-envelope': 'mail',
  'fa-comment': 'chat',
  'fa-comments': 'chat',
  'fa-star': 'star',
  'fa-heart': 'heart',
  'fa-bookmark': 'bookmark',
  'fa-tag': 'tag',
  'fa-tags': 'tag',
  'fa-folder': 'folder',
  'fa-file': 'file',
  'fa-book': 'book',
  'fa-pen': 'edit',
  'fa-pencil': 'edit',
  'fa-clock': 'clock',
  'fa-calendar': 'calendar',
  'fa-camera': 'camera',
  'fa-image': 'photo',
  'fa-images': 'photo',
  'fa-music': 'music',
  'fa-video': 'video',
  'fa-download': 'download',
  'fa-upload': 'upload',
  'fa-link': 'link',
  'fa-map': 'map',
  'fa-location-dot': 'map-pin',
  'fa-gamepad': 'game',
  'fa-code': 'code',
  'fa-graduation-cap': 'graduation',
  'fa-circle-info': 'info-circle',
  'fa-info': 'info',
  'fa-circle-question': 'question',
  'fa-question': 'question',
  'fa-triangle-exclamation': 'error',
  'fa-check': 'check',
  'fa-xmark': 'close',
  'fa-cart-shopping': 'cart',
  'fa-gift': 'gift',
  'fa-github': 'github',
  'fa-twitter': 'twitter',
  'fa-x-twitter': 'twitter-x',
  'fa-facebook': 'facebook',
  'fa-instagram': 'instagram',
  'fa-linkedin': 'linkedin',
  'fa-youtube': 'youtube',
  'fa-weibo': 'weibo',
  'fa-qq': 'qq',
  'fa-weixin': 'wechat',
  'fa-telegram': 'telegram',
  'fa-discord': 'discord',
  'fa-rss': 'rss',
}

/** 图标库/样式前缀，跳过不作为图标名 */
const PREFIX_TOKENS = new Set([
  'bx',
  'ti',
  'fa',
  'fas',
  'far',
  'fab',
  'fal',
  'fa-solid',
  'fa-regular',
  'fa-brands',
  'fa-light',
  'iconfont',
])

/** 从 `<i class="...">` 提取 class；否则原样返回 */
function normalizeIcon(raw: string): string {
  const s = raw.trim()
  if (s.startsWith('<')) {
    const m = s.match(/class=["']([^"']+)["']/)
    return m?.[1] ?? s
  }
  return s
}

/** 把一段图标标识拆成候选语义名 + 推断 filled */
function resolveTokens(input: string): ResolvedIcon | null {
  const tokens = normalizeIcon(input).split(/\s+/).filter(Boolean)
  let filled = false
  const candidates: string[] = []

  for (const t of tokens) {
    if (PREFIX_TOKENS.has(t)) continue
    if (t.includes('-filled') || t.endsWith('-fill')) filled = true

    if (t.startsWith('bxs-')) {
      filled = true
      candidates.push(t.slice(4))
    } else if (t.startsWith('bx-')) {
      candidates.push(t.slice(3))
    } else if (t.startsWith('bxl-')) {
      candidates.push(t.slice(4))
    } else if (t.startsWith('ti-')) {
      candidates.push(t.slice(3))
    } else if (t.startsWith('fa-')) {
      const sem = FA_TO_SEMANTIC[t]
      if (sem) candidates.push(sem)
    } else {
      candidates.push(t)
    }
  }

  for (const c of candidates) {
    if (NAME_MAP[c]) return { name: NAME_MAP[c], filled }
    if (VALID_TABLER.has(c)) return { name: c, filled }
  }
  return null
}

// ============================================================
// 公开 API
// ============================================================

/**
 * 解析任意图标标识为 Tabler 图标。
 * @param raw 语义名 / bx 类名 / ti 类名 / fa 类名 / `<i>` HTML
 */
export function resolveIconName(raw?: string | null): ResolvedIcon {
  if (!raw) return { name: DEFAULT_ICON_NAME, filled: false }
  return resolveTokens(raw) || { name: DEFAULT_ICON_NAME, filled: false }
}

/**
 * 解析菜单项图标：item.icon → FA 兼容 → 中文标题匹配 → 默认。
 * @param active 活跃路径时用 filled 变体
 */
export function resolveMenuIcon(item: MenuItem, active?: boolean): ResolvedIcon {
  let resolved: ResolvedIcon | null = null

  if (item.icon) resolved = resolveTokens(item.icon)

  if (!resolved && item.title) {
    const sem = TITLE_ICON_MAP[item.title]
    if (sem) resolved = { name: NAME_MAP[sem] || DEFAULT_ICON_NAME, filled: false }
  }

  if (!resolved) resolved = { name: DEFAULT_ICON_NAME, filled: false }
  if (active) resolved.filled = true
  return resolved
}

/** 该图标标识是否能解析到已知 Tabler 图标 */
export function hasIcon(raw: string): boolean {
  return resolveTokens(raw) !== null
}
