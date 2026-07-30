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
    about: string
    links: string
    settings: string
    smtp_test?: string
    mail_queue?: string
    email_templates?: string
    email_preview?: string
  }
  currentUser?: CurrentUser | null
  restNonce?: string
  logoutUrl?: string
  features?: {
    prismHighlight: boolean
    /** 是否开放用户注册（WP users_can_register） */
    registration?: boolean
    showStats?: boolean
    showHeatmap?: boolean
    showSocial?: boolean
    /** 侧边栏一言卡片 */
    showHitokoto?: boolean
    /** 一言 API 地址（默认 https://v1.hitokoto.cn） */
    hitokotoApi?: string
    meta?: ArticleMeta
    articleMeta?: ArticleMeta
  }
}

// ----- About Page Types -----

export interface ArticleMeta {
  showCategory: boolean
  showPublishDate: boolean
  showModifiedDate: boolean
  showCommentCount: boolean
  showViewCount: boolean
  showReadingTime: boolean
  showWordCount: boolean
  showAuthor: boolean
}

export interface AboutTimelineEntry {
  period: string
  title: string
  subtitle: string
  image: string
}

export interface AboutGameEntry {
  name: string
  icon: string
  uid: string
}

export interface AboutSponsorEntry {
  name: string
  amount: string
}

export interface AboutInfo {
  avatar: string
  subtitleLines: string[]
  identityTags: string[]
  greeting: string
  sloganBlock: string
  skills: string[]
  timeline: AboutTimelineEntry[]
  mbtiType: string
  mbtiLabel: string
  mbtiImage: string
  mbtiUrl: string
  games: AboutGameEntry[]
  animeTitle: string
  animeTagline: string
  musicArtists: string
  musicUrl: string
  location: string
  birthYear: number
  education: string
  occupation: string
  sponsorTotal: string
  sponsorList: AboutSponsorEntry[]
  sponsorUrl: string
  donationWechatQr: string
  donationAlipayQr: string
  donationTotal: string
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
  showImageUpload?: boolean
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
  pageId: number
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
  postsTitle?: string
  postsSubtitle?: string
  shuoshuoTitle?: string
  shuoshuoSubtitle?: string
  showShuoshuoSection?: boolean
  homePostCount?: number
  homeShuoshuoCount?: number
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
  phpVersion?: string
  restApiVersion?: string
  serverOs?: string
  hero?: HeroSettings
  comments?: CommentFormSettings
  theme?: ThemeSettings
  themeVersion?: string
  stats?: SiteStats
  socialLinks?: SocialLink[]
  techInfoItems?: TechInfoItem[]
  loginUrl?: string
  currentUser?: CurrentUser
  icp?: string
  icpGov?: string
  endNote?: string
  collections?: CollectionsSettings
  announcement?: AnnouncementSettings
  cookieConsent?: CookieConsentSettings
}

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
  categories?: string[]
  tags?: string[]
  featuredImage?: string
  commentCount?: number
  viewCount?: number
  readingTime?: number
  wordCount?: number
  title: RenderedText
  excerpt?: RenderedText
  content?: RenderedText
  _embedded?: Record<string, unknown>
}

export interface CommentMetaInfo {
  location: string
  browser: string
  os: string
  ipMask: string
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

export interface CaptchaData {
  challenge: string
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
  type: 'home' | 'post' | 'page' | 'term' | '404' | 'error' | 'shuoshuo'
  id?: number
  name?: string
  taxonomy?: string
  permalink?: string
  restUrl?: string
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
