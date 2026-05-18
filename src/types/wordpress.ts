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
  }
  currentUser?: CurrentUser | null
  restNonce?: string
  features?: {
    prismHighlight: boolean
  }
}

// ----- About Page Types -----

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
  cardMeta?: {
    showCategory: boolean
    showPublishDate: boolean
    showModifiedDate: boolean
    showCommentCount: boolean
    showViewCount: boolean
    showReadingTime: boolean
    showWordCount: boolean
  }
  copyrightStyle?: string
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
}

export interface SocialLink {
  label: string
  url: string
  icon: string
  sidebarEnabled?: boolean
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
  hero?: HeroSettings
  comments?: CommentFormSettings
  theme?: ThemeSettings
  stats?: SiteStats
  socialLinks?: SocialLink[]
  loginUrl?: string
  currentUser?: CurrentUser
  icp?: string
  icpGov?: string
  endNote?: string
  collections?: CollectionsSettings
}

export interface RenderedText {
  rendered: string
}

export interface WordPressPost {
  id: number
  slug?: string
  date: string
  modified?: string
  link: string
  type: string
  comment_status?: 'open' | 'closed'
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
  question: string
  seed: string
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
