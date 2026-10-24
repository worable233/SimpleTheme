export interface SimpleThemeConfig {
  siteUrl: string
  homeUrl: string
  restRoot: string
  themeUrl: string
  illustrationsUrl: string
  routes: {
    resolveUrl: string
    menusBase: string
    siteInfo: string
    collection: string
    links: string
    settings: string
    smtp_test?: string
    mail_queue?: string
    email_templates?: string
    email_preview?: string
  }
  restNonce?: string
  /** 归档路径前缀（分类 / 标签），用于加载骨架选型 */
  permalink?: {
    categoryBase: string
    tagBase: string
  }
  /** 布局宽度（随初始 HTML 下发，首屏即用，避免宽度跳变） */
  layout?: {
    containerMaxWidth: number
    articleMaxWidth: number
  }
  features?: {
    prismHighlight: boolean
    /** 是否开放用户注册（WP users_can_register） */
    registration?: boolean
    commentOrder?: 'asc' | 'desc'
    meta?: ArticleMeta
    articleMeta?: ArticleMeta
  }
}

export interface ArticleMeta {
  showCategory: boolean
  showPublishDate: boolean
  showModifiedDate: boolean
  showCommentCount: boolean
  showViewCount: boolean
  showReadingTime: boolean
  showWordCount: boolean
  showAuthor: boolean
  /** 编辑文章入口（仅对有编辑权限的用户显示） */
  showEditLink?: boolean
}

export type ThemeRadius = 'small' | 'medium' | 'large'
export type ThemeShadow = 'none' | 'small' | 'medium' | 'large'

export interface ThemeSettings {
  primaryColor: string
  bodyFont: string
  codeFont: string
  radius: ThemeRadius
  shadow: ThemeShadow
  backgroundLight: string
  backgroundDark: string
  cardLight: string
  cardDark: string
  foregroundLight: string
  foregroundDark: string
  accentLight: string
  accentDark: string
  borderLight: string
  borderDark: string
  containerMaxWidth: number
  articleMaxWidth: number
  cardMeta?: ArticleMeta
  articleMeta?: ArticleMeta
  copyrightStyle?: string
  articleLicense?: string
  showCredit?: boolean
  prismEnabled?: boolean
}

export interface HeroSettings {
  enabled: boolean
  displayMode: 'full' | 'half' | 'inset'
  useImage: boolean
  image: string
  showAvatar: boolean
  avatar: string
  title: string
  subtitle: string
  typewriterEnabled: boolean
  typewriterInterval: number
  typewriterTexts: string
}

export interface CommentFormSettings {
  requireNameEmail: boolean
  registrationOnly: boolean
  showEmailField: boolean
  showUrlField: boolean
  showCookiesOptIn: boolean
  captchaEnabled?: boolean
  showPrivateOption?: boolean
  showMarkdownOption?: boolean
  showMailNotifyOption?: boolean
}

export interface AnnouncementButton {
  text: string
  url?: string
  action?: 'close' | 'link'
}

export interface AnnouncementSettings {
  enabled: boolean
  mode: 'modal' | 'capsule'
  /** 开启后每次访问都展示，不记忆关闭状态 */
  alwaysShow: boolean
  pageTitle?: string
  pageContent?: string
  buttons: AnnouncementButton[]
  capsuleTitle: string
  icon: string
}

export interface CookieConsentSettings {
  enabled: boolean
  message: string
}
export interface HeatmapEntry {
  day: string
  count: number
}

export interface SiteStats {
  postCount: number
  categoryCount: number
  tagCount: number
  shuoshuoCount: number
  totalWordCount: number
  commentCount: number
  registeredDate: string
  lastActivityDate: string
  heatmapData?: HeatmapEntry[]
}

export interface SocialLink {
  label: string
  url: string
  icon: string
  sidebarEnabled?: boolean
}

export interface TechInfoItem {
  label: string
  value: string
}

export interface CollectionsSettings {
  homePostCount?: number
  shuoshuoPageSize?: number
}

export interface UserData {
  displayName: string
  email?: string
  url?: string
}

export interface CurrentUser {
  displayName: string
  email: string
  url: string
}

export interface SiteInfo {
  name: string
  description: string
  url: string
  siteIcon?: string
  wpVersion?: string
  restApiVersion?: string
  hero?: HeroSettings
  comments?: CommentFormSettings
  theme?: ThemeSettings
  themeVersion?: string
  stats?: SiteStats
  socialLinks?: SocialLink[]
  techInfoItems?: TechInfoItem[]
  icp?: string
  icpGov?: string
  endNote?: string
  collections?: CollectionsSettings
  announcement?: AnnouncementSettings
  cookieConsent?: CookieConsentSettings
  externalRedirect?: ExternalRedirectSettings
  /** 右侧栏小工具序列（外观→小工具 配置，按顺序渲染） */
  sidebar?: SidebarWidget[]
}

export interface ExternalRedirectSettings {
  enabled: boolean
  delay: number
  target: '_self' | '_blank' | '_parent' | '_top'
}

/** 右侧栏小工具项：主题结构化卡片，或核心/区块小工具的 HTML */
export type SidebarWidget =
  | { type: 'profile'; settings: { showStats: boolean; showHeatmap: boolean; showSocial: boolean } }
  | { type: 'hitokoto'; settings: { api: string } }
  | { type: 'techInfo'; settings?: Record<string, never> }
  | { type: 'html'; idBase: string; html: string }

export interface RenderedText {
  rendered: string
}

export interface WordPressPost {
  id: number
  /* Standard WP REST fields (always emitted by inc/rest/posts.php) */
  date: string
  modified: string
  slug: string
  link: string
  status?: string
  type?: string
  comment_status?: 'open' | 'closed'
  /* Theme-provided fields */
  /** wp-admin 编辑链接（后端仅对可编辑该文章的用户返回） */
  editUrl?: string
  categories?: string[]
  tags?: string[]
  featuredImage?: string
  commentCount?: number
  viewCount?: number
  readingTime?: number
  wordCount?: number
  /** WordPress 原生置顶文章 */
  isSticky?: boolean
  title: RenderedText
  excerpt?: RenderedText
  content?: RenderedText
  _embedded?: Record<string, unknown>
}

export interface CommentMetaInfo {
  location: string
  browser: string
  os: string
}

export interface WordPressComment {
  id: number
  parent: number
  date: string
  authorName: string
  authorEmail: string
  authorUrl: string
  status?: string
  avatar: string
  content: RenderedText
  likes: number
  metaInfo: CommentMetaInfo
  children: WordPressComment[]
  isPinned?: boolean
  isPrivate?: boolean
  canEdit?: boolean
  useMarkdown?: boolean
  canPin?: boolean
  qqAvatar?: string
}

export interface CommentsResponse {
  items: WordPressComment[]
  total: number
  page: number
  perPage: number
  totalPages: number
}

/** 验证码挑战（后端按 ALTCHA 协议出题，前端 worker 自己求解） */
export interface CaptchaData {
  algorithm: string
  challenge: string
  maxnumber: number
  salt: string
  signature: string
}

export interface MenuItem {
  id: number
  title: string
  url: string
  path: string
  target: string
  description: string
  current: boolean
  icon?: string
  children: MenuItem[]
}

export interface MenuCollectionResponse {
  items: MenuItem[]
}

export interface PagedPostCollection {
  items: WordPressPost[]
  total: number
  totalPages: number
  page: number
  perPage: number
}

export interface ResolveResponse {
  type: 'home' | 'post' | 'page' | 'term' | 'date' | '404' | 'error' | 'shuoshuo'
  id?: number
  name?: string
  taxonomy?: string
  /** 日期归档（type='date'） */
  year?: number
  month?: number
  permalink?: string
  restUrl?: string
  /** 单篇解析时随响应内联返回的 wp/v2 数据，省掉一次额外请求 */
  payload?: WordPressPost | null
  message?: string
}

export interface WordPressCategory {
  id: number
  name: string
  slug: string
}

export interface WordPressLink {
  id: number
  name: string
  url: string
  description: string
  image: string
  target: string
  rating: number
  notes: string
}

export interface WordPressLinkCategory {
  id: number
  name: string
  slug: string
  description: string
  links: WordPressLink[]
}
