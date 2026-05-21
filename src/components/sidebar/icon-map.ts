/**
 * sidebar/icon-map — 菜单图标映射
 * 从 ICON_MAP (图标名称 → Boxicons HTML) + TITLE_ICON_MAP (中文标题 → 图标名称)
 * 综合查找菜单项的图标。
 */
import type { MenuItem } from '@/types/wordpress'

/** 图标名称 → Boxicons HTML 映射表 */
const ICON_MAP: Record<string, string> = {
  home: '<i class="bx bxs-home"></i>',
  chat: '<i class="bx bx-message-rounded-dots"></i>',
  about: '<i class="bx bx-info-circle"></i>',
  archive: '<i class="bx bx-archive"></i>',
  link: '<i class="bx bx-link-external"></i>',
  star: '<i class="bx bx-star"></i>',
  tag: '<i class="bx bx-tag"></i>',
  heart: '<i class="bx bx-heart"></i>',
  user: '<i class="bx bx-user"></i>',
  mail: '<i class="bx bx-envelope"></i>',
  bookmark: '<i class="bx bx-bookmark"></i>',
  settings: '<i class="bx bx-cog"></i>',
  music: '<i class="bx bx-music"></i>',
  photo: '<i class="bx bx-image-alt"></i>',
  calendar: '<i class="bx bx-calendar"></i>',
  map: '<i class="bx bx-map"></i>',
  bell: '<i class="bx bx-bell"></i>',
  clock: '<i class="bx bx-time-five"></i>',
  search: '<i class="bx bx-search"></i>',
  shopping: '<i class="bx bx-shopping-bag"></i>',
  book: '<i class="bx bx-book"></i>',
  code: '<i class="bx bx-code-alt"></i>',
  folder: '<i class="bx bx-folder"></i>',
  download: '<i class="bx bx-download"></i>',
  share: '<i class="bx bx-share-alt"></i>',
  lock: '<i class="bx bx-lock-alt"></i>',
  'log-in': '<i class="bx bx-log-in"></i>',
  'log-out': '<i class="bx bx-log-out"></i>',
  // ======== 补充映射 ========
  edit: '<i class="bx bx-edit"></i>',
  grid: '<i class="bx bx-grid"></i>',
  'grid-alt': '<i class="bx bx-grid-alt"></i>',
  list: '<i class="bx bx-list-ul"></i>',
  globe: '<i class="bx bx-globe"></i>',
  badge: '<i class="bx bx-badge"></i>',
  like: '<i class="bx bx-like"></i>',
  dislike: '<i class="bx bx-dislike"></i>',
  quote: '<i class="bx bx-quote-left"></i>',
  send: '<i class="bx bx-send"></i>',
  graduation: '<i class="bx bx-graduation"></i>',
  game: '<i class="bx bx-game"></i>',
  video: '<i class="bx bx-video"></i>',
  brush: '<i class="bx bx-brush"></i>',
  wand: '<i class="bx bx-wand"></i>',
  bulb: '<i class="bx bx-bulb"></i>',
  bolt: '<i class="bx bx-bolt"></i>',
  crown: '<i class="bx bx-crown"></i>',
  diamond: '<i class="bx bx-diamond"></i>',
  flag: '<i class="bx bx-flag"></i>',
  briefcase: '<i class="bx bx-briefcase"></i>',
  coffee: '<i class="bx bx-coffee"></i>',
  peace: '<i class="bx bx-handicap"></i>',
  handshake: '<i class="bx bx-handshake"></i>',
  smile: '<i class="bx bx-smile"></i>',
  frown: '<i class="bx bx-frown"></i>',
  leaf: '<i class="bx bx-leaf"></i>',
  cloud: '<i class="bx bx-cloud"></i>',
  moon: '<i class="bx bx-moon"></i>',
  sun: '<i class="bx bx-sun"></i>',
  umbrella: '<i class="bx bx-umbrella"></i>',
  bike: '<i class="bx bx-bike"></i>',
  car: '<i class="bx bx-car"></i>',
  train: '<i class="bx bx-train"></i>',
  ship: '<i class="bx bx-ship"></i>',
  rocket: '<i class="bx bx-rocket"></i>',
  gift: '<i class="bx bx-gift"></i>',
  credit: '<i class="bx bx-credit-card"></i>',
  wallet: '<i class="bx bx-wallet"></i>',
  phone: '<i class="bx bx-phone"></i>',
  microphone: '<i class="bx bx-microphone"></i>',
  headphone: '<i class="bx bx-headphone"></i>',
  volume: '<i class="bx bx-volume-full"></i>',
  play: '<i class="bx bx-play"></i>',
  pause: '<i class="bx bx-pause"></i>',
  stop: '<i class="bx bx-stop"></i>',
  forward: '<i class="bx bx-skip-next"></i>',
  backward: '<i class="bx bx-skip-previous"></i>',
  shield: '<i class="bx bx-shield"></i>',
  trophy: '<i class="bx bx-trophy"></i>',
  badminton: '<i class="bx bxs-badminton"></i>',
  basketball: '<i class="bx bxs-basketball"></i>',
}

/** 中文标题 → 图标名称（向后兼容旧菜单项） */
const TITLE_ICON_MAP: Record<string, string> = {
  '首页': 'home',
  '说说': 'chat',
  '关于': 'about',
  '归档': 'archive',
  '友情链接': 'link',
  '友链': 'link',
  '赞助': 'star',
  '收藏': 'bookmark',
  '标签': 'tag',
  '分类': 'folder',
  '留言': 'mail',
  '相册': 'photo',
  '音乐': 'music',
  '搜索': 'search',
  '登录': 'log-in',
  '注册': 'user',
  '设置': 'settings',
}

/**
 * FontAwesome → Boxicons 兼容映射
 * 处理旧菜单项中保存的 fa- 类名（如 fa-solid fa-home → home）
 */
const FA_TO_BX: Record<string, string> = {
  'fa-solid fa-home': 'home',
  'fa-home': 'home',
  'fa-solid fa-envelope': 'mail',
  'fa-envelope': 'mail',
  'fa-solid fa-tag': 'tag',
  'fa-tag': 'tag',
  'fa-solid fa-folder': 'folder',
  'fa-folder': 'folder',
  'fa-solid fa-user': 'user',
  'fa-user': 'user',
  'fa-solid fa-cog': 'settings',
  'fa-cog': 'settings',
  'fa-solid fa-gear': 'settings',
  'fa-solid fa-search': 'search',
  'fa-search': 'search',
  'fa-solid fa-star': 'star',
  'fa-star': 'star',
  'fa-solid fa-heart': 'heart',
  'fa-heart': 'heart',
  'fa-solid fa-bookmark': 'bookmark',
  'fa-bookmark': 'bookmark',
  'fa-regular fa-bookmark': 'bookmark',
  'fa-solid fa-calendar': 'calendar',
  'fa-calendar': 'calendar',
  'fa-solid fa-clock': 'clock',
  'fa-clock': 'clock',
  'fa-solid fa-music': 'music',
  'fa-music': 'music',
  'fa-solid fa-image': 'photo',
  'fa-image': 'photo',
  'fa-solid fa-camera': 'photo',
  'fa-solid fa-link': 'link',
  'fa-link': 'link',
  'fa-solid fa-external-link': 'link',
  'fa-solid fa-download': 'download',
  'fa-download': 'download',
  'fa-solid fa-lock': 'lock',
  'fa-lock': 'lock',
  'fa-solid fa-unlock': 'lock',
  'fa-solid fa-bell': 'bell',
  'fa-bell': 'bell',
  'fa-solid fa-info': 'about',
  'fa-info': 'about',
  'fa-info-circle': 'about',
  'fa-solid fa-info-circle': 'about',
  'fa-solid fa-archive': 'archive',
  'fa-archive': 'archive',
  'fa-solid fa-comment': 'chat',
  'fa-comment': 'chat',
  'fa-solid fa-comments': 'chat',
  'fa-solid fa-message': 'chat',
  'fa-solid fa-code': 'code',
  'fa-code': 'code',
  'fa-solid fa-shopping-cart': 'shopping',
  'fa-shopping-cart': 'shopping',
  'fa-solid fa-shopping-bag': 'shopping',
  'fa-solid fa-sign-in-alt': 'log-in',
  'fa-sign-in-alt': 'log-in',
  'fa-solid fa-sign-out-alt': 'log-out',
  'fa-sign-out-alt': 'log-out',

  // ======== 补充常见 sakurairo 菜单图标 ========
  'fa-solid fa-pencil': 'edit',
  'fa-pencil': 'edit',
  'fa-solid fa-edit': 'edit',
  'fa-edit': 'edit',
  'fa-solid fa-pen': 'edit',
  'fa-solid fa-dashboard': 'grid',
  'fa-dashboard': 'grid',
  'fa-solid fa-tachometer': 'grid',
  'fa-solid fa-th': 'grid-alt',
  'fa-th': 'grid-alt',
  'fa-solid fa-th-large': 'grid',
  'fa-solid fa-th-list': 'list',
  'fa-th-list': 'list',
  'fa-solid fa-list': 'list',
  'fa-list': 'list',
  'fa-solid fa-rss': 'chat',
  'fa-rss': 'chat',
  'fa-solid fa-globe': 'globe',
  'fa-globe': 'globe',
  'fa-solid fa-fire': 'badge',
  'fa-fire': 'badge',
  'fa-solid fa-thumbs-up': 'like',
  'fa-thumbs-up': 'like',
  'fa-regular fa-thumbs-up': 'like',
  'fa-solid fa-thumbs-down': 'dislike',
  'fa-solid fa-quote-left': 'quote',
  'fa-quote-left': 'quote',
  'fa-quote-right': 'quote',
  'fa-solid fa-paper-plane': 'send',
  'fa-paper-plane': 'send',
  'fa-solid fa-send': 'send',
  'fa-solid fa-plane': 'send',
  'fa-solid fa-graduation-cap': 'graduation',
  'fa-graduation-cap': 'graduation',
  'fa-solid fa-gamepad': 'game',
  'fa-gamepad': 'game',
  'fa-solid fa-film': 'video',
  'fa-film': 'video',
  'fa-solid fa-video': 'video',
  'fa-video': 'video',
  'fa-solid fa-camera-retro': 'photo',
  'fa-camera-retro': 'photo',
  'fa-solid fa-paint-brush': 'brush',
  'fa-paint-brush': 'brush',
  'fa-solid fa-magic': 'wand',
  'fa-magic': 'wand',
  'fa-solid fa-wrench': 'settings',
  'fa-wrench': 'settings',
  'fa-solid fa-tools': 'settings',
  'fa-solid fa-lightbulb': 'bulb',
  'fa-lightbulb': 'bulb',
  'fa-regular fa-lightbulb': 'bulb',
  'fa-solid fa-bolt': 'bolt',
  'fa-bolt': 'bolt',
  'fa-solid fa-crown': 'crown',
  'fa-crown': 'crown',
  'fa-solid fa-gem': 'diamond',
  'fa-gem': 'diamond',
  'fa-regular fa-gem': 'diamond',
  'fa-solid fa-flag': 'flag',
  'fa-flag': 'flag',
  'fa-solid fa-book': 'book',
  'fa-book': 'book',
  'fa-regular fa-book': 'book',
  'fa-solid fa-map-marker': 'map',
  'fa-map-marker': 'map',
  'fa-solid fa-map-pin': 'map',
  'fa-solid fa-map': 'map',
  'fa-map': 'map',
  'fa-regular fa-map': 'map',
  'fa-solid fa-briefcase': 'briefcase',
  'fa-briefcase': 'briefcase',
  'fa-solid fa-suitcase': 'briefcase',
  'fa-solid fa-coffee': 'coffee',
  'fa-coffee': 'coffee',
  'fa-solid fa-mug-hot': 'coffee',
  'fa-solid fa-hand-peace': 'peace',
  'fa-regular fa-hand-peace': 'peace',
  'fa-solid fa-handshake': 'handshake',
  'fa-regular fa-handshake': 'handshake',
  'fa-solid fa-smile': 'smile',
  'fa-regular fa-smile': 'smile',
  'fa-regular fa-smile-wink': 'smile',
  'fa-solid fa-frown': 'frown',
  'fa-regular fa-frown': 'frown',
  'fa-solid fa-leaf': 'leaf',
  'fa-leaf': 'leaf',
  'fa-solid fa-tree': 'leaf',
  'fa-solid fa-cloud': 'cloud',
  'fa-cloud': 'cloud',
  'fa-solid fa-moon': 'moon',
  'fa-moon': 'moon',
  'fa-regular fa-moon': 'moon',
  'fa-solid fa-sun': 'sun',
  'fa-sun': 'sun',
  'fa-regular fa-sun': 'sun',
  'fa-solid fa-umbrella': 'umbrella',
  'fa-solid fa-bicycle': 'bike',
  'fa-solid fa-car': 'car',
  'fa-car': 'car',
  'fa-solid fa-train': 'train',
  'fa-solid fa-ship': 'ship',
  'fa-solid fa-rocket': 'rocket',
  'fa-rocket': 'rocket',
  'fa-solid fa-space-shuttle': 'rocket',
  'fa-solid fa-gift': 'gift',
  'fa-gift': 'gift',
  'fa-regular fa-gift': 'gift',
  'fa-solid fa-credit-card': 'credit',
  'fa-credit-card': 'credit',
  'fa-regular fa-credit-card': 'credit',
  'fa-solid fa-wallet': 'wallet',
  'fa-wallet': 'wallet',
  'fa-solid fa-phone': 'phone',
  'fa-phone': 'phone',
  'fa-solid fa-phone-alt': 'phone',
  'fa-solid fa-microphone': 'microphone',
  'fa-microphone': 'microphone',
  'fa-solid fa-headphones': 'headphone',
  'fa-headphones': 'headphone',
  'fa-solid fa-volume-up': 'volume',
  'fa-volume-up': 'volume',
  'fa-solid fa-play': 'play',
  'fa-play': 'play',
  'fa-solid fa-pause': 'pause',
  'fa-pause': 'pause',
  'fa-solid fa-stop': 'stop',
  'fa-solid fa-forward': 'forward',
  'fa-solid fa-backward': 'backward',
  'fa-solid fa-step-forward': 'forward',
  'fa-solid fa-step-backward': 'backward',
  'fa-solid fa-shield': 'shield',
  'fa-shield': 'shield',
  'fa-solid fa-shield-alt': 'shield',
  'fa-solid fa-trophy': 'trophy',
  'fa-trophy': 'trophy',
  'fa-solid fa-medal': 'trophy',
  'fa-solid fa-award': 'trophy',
}

/**
 * 从完整 HTML 字符串中提取 CSS 类名
 * 兼容某些插件保存 <i class="fa fa-home"></i> 这种格式
 */
function extractClassFromHtml(html: string): string | null {
  const match = html.match(/class=["']([^"']+)["']/)
  if (match) return match[1]
  return null
}

/**
 * 标准化 icon 值：如果是 HTML 标签则提取 class，否则原样返回
 */
function normalizeIcon(raw: string): string {
  if (raw.startsWith('<')) {
    const cls = extractClassFromHtml(raw)
    return cls || raw
  }
  return raw
}

/**
 * 通过 FontAwesome 类名尝试查找对应的 Boxicons HTML
 * 支持带 HTML 标签包裹的格式
 */
function faToBx(icon: string): string | undefined {
  const cls = normalizeIcon(icon)
  // 尝试精确匹配
  const bxName = FA_TO_BX[cls]
  if (bxName) return ICON_MAP[bxName]
  // 尝试只匹配最后一个非 bxs/bxr/bxl 的类名
  const parts = cls.split(/\s+/)
  for (let i = parts.length - 1; i >= 0; i--) {
    const p = parts[i]
    if (p && !p.startsWith('fa-')) continue
    const bxName2 = FA_TO_BX[p]
    if (bxName2) return ICON_MAP[bxName2]
  }
  return undefined
}

/** 默认兜底图标 */
const DEFAULT_ICON_SVG = '<i class="bx bx-circle"></i>'

/**
 * 获取菜单项的图标 HTML。
 * 优先使用 API 返回的 icon 名称 → 兼容旧 FA 类名/HTML → 按中文标题匹配 → 默认图标。
 */
export function getItemIcon(item: MenuItem): string {
  if (!item.icon) {
    // 无 icon → 直接按标题匹配
    const titleKey = TITLE_ICON_MAP[item.title]
    if (titleKey) return ICON_MAP[titleKey] || DEFAULT_ICON_SVG
    return DEFAULT_ICON_SVG
  }

  const normalized = normalizeIcon(item.icon)

  // 1. 尝试作为 Boxicons 名称直接查找
  if (!normalized.startsWith('fa')) {
    const svg = ICON_MAP[normalized]
    if (svg) return svg
  }

  // 2. 兼容旧 FontAwesome 类名 / HTML
  const faResult = faToBx(item.icon)
  if (faResult) return faResult

  // 3. 尝试按中文标题查找
  const titleKey = TITLE_ICON_MAP[item.title]
  if (titleKey) {
    const svg = ICON_MAP[titleKey]
    if (svg) return svg
  }

  // 4. 默认
  return DEFAULT_ICON_SVG
}
