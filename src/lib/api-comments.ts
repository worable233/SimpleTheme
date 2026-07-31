/**
 * api-comments — 评论相关 API（基于 WordPress /wp/v2/comments 标准端点）
 */
import { buildRestUrl } from './api-client'
import { shouldUseMock } from './mock-api'
import type { WordPressComment, CaptchaData, CommentsResponse } from '@/types/wordpress'
import { apiClient } from './api-client'
import axios from 'axios'

/** 将 WP REST API 评论响应映射到前端 WordPressComment 类型 */
interface WPCommentPayload {
  id: number
  parent?: number
  date?: string
  status?: string
  content?: { rendered?: string }
  likes?: number
  children?: WPCommentPayload[]
  /* 主题自定义字段（驼峰）与 WP 标准字段（下划线）双命名 */
  authorName?: string
  author_name?: string
  authorEmail?: string
  author_email?: string
  authorUrl?: string
  author_url?: string
  avatar?: string
  author_avatar_urls?: Record<string, string>
  metaInfo?: WordPressComment['metaInfo']
  isPinned?: boolean
  isPrivate?: boolean
  canEdit?: boolean
  useMarkdown?: boolean
  canPin?: boolean
  qqAvatar?: string
}

function mapWPComment(item: WPCommentPayload): WordPressComment {
  return {
    id: item.id,
    parent: item.parent || 0,
    date: item.date || '',
    authorName: item.authorName || item.author_name || '',
    authorEmail: item.authorEmail || item.author_email || '',
    authorUrl: item.authorUrl || item.author_url || '',
    status: item.status === '0' ? 'hold' : item.status === '1' ? 'approved' : item.status || '',
    avatar: item.avatar || item.author_avatar_urls?.['96'] || '',
    content: { rendered: item.content?.rendered || '' },
    likes: item.likes ?? 0,
    metaInfo: item.metaInfo || { location: '', browser: '', os: '', ipMask: '' },
    children: (item.children || []).map(mapWPComment),
    isPinned: item.isPinned ?? false,
    isPrivate: item.isPrivate ?? false,
    canEdit: item.canEdit ?? false,
    useMarkdown: item.useMarkdown ?? false,
    canPin: item.canPin ?? false,
    qqAvatar: item.qqAvatar || '',
  }
}

/** 将扁平评论列表构建为嵌套树。
 *  父级不在当前列表中的“孤儿”（分页导致）提升为根节点，避免回复丢失；
 *  根节点置顶评论排在最前（稳定排序，组内保持时间序）。 */
function buildCommentTree(items: WordPressComment[], maxDepth = 3): WordPressComment[] {
  const ids = new Set(items.map((i) => i.id))
  const build = (parentId: number, depth: number): WordPressComment[] => {
    const branch: WordPressComment[] = []
    for (const item of items) {
      const isRoot = parentId === 0 && (item.parent === 0 || !ids.has(item.parent))
      if (isRoot || (parentId !== 0 && item.parent === parentId)) {
        const children = depth < maxDepth ? build(item.id, depth + 1) : []
        branch.push({ ...item, children })
      }
    }
    return branch
  }
  const tree = build(0, 0)
  tree.sort((a, b) => Number(b.isPinned) - Number(a.isPinned))
  return tree
}

export async function fetchComments(
  postId: number,
  _clientId?: string,
  page = 1,
  perPage = 50,
): Promise<CommentsResponse> {
  if (shouldUseMock()) return { items: [], total: 0, page: 1, perPage, totalPages: 0 }
  try {
    const url = buildRestUrl('wp/v2/comments') + `?post=${postId}&page=${page}&per_page=${perPage}&order=asc`
    const { data, headers } = await apiClient.get<WPCommentPayload[]>(url)

    const total = parseInt(headers['x-wp-total'] || '0')
    const totalPages = parseInt(headers['x-wp-totalpages'] || '1')

    const flat = data.map(mapWPComment)
    const tree = buildCommentTree(flat)

    return {
      items: tree,
      total,
      page,
      perPage,
      totalPages,
    }
  } catch (error) {
    if (axios.isAxiosError(error) && error.response?.status === 404) {
      return { items: [], total: 0, page: 1, perPage, totalPages: 0 }
    }
    throw error
  }
}

export async function createComment(payload: {
  post: number
  parent?: number
  author_name: string
  author_email?: string
  author_url?: string
  content: string
  client_id: string
  captchaPayload?: string
  isPrivate?: boolean
  mailNotify?: boolean
  useMarkdown?: boolean
  cookiesConsent?: boolean
}): Promise<WordPressComment> {
  const { data } = await apiClient.post<WPCommentPayload>(
    buildRestUrl('wp/v2/comments'),
    payload,
  )
  return mapWPComment(data)
}

export async function likeComment(commentId: number) {
  try {
    const { data } = await apiClient.post<{ likes: number }>(
      buildRestUrl('simple-theme/v1/comment-like'),
      { commentId },
    )
    return data.likes
  } catch (error) {
    if (axios.isAxiosError(error) && error.response?.status === 404) {
      return 0
    }
    throw error
  }
}

export async function fetchCaptcha() {
  const { data } = await apiClient.get<CaptchaData>(
    buildRestUrl('simple-theme/v1/comment-captcha'),
  )
  return data
}

export async function deleteComment(commentId: number) {
  const { data } = await apiClient.post<{ deleted: boolean }>(
    buildRestUrl('simple-theme/v1/comment-delete'),
    { commentId },
  )
  return data.deleted
}

export async function fetchUserPendingComments(postId: number) {
  if (shouldUseMock()) return []
  const { data } = await apiClient.get<{ items: WPCommentPayload[] }>(
    buildRestUrl(`simple-theme/v1/user-pending-comments?post_id=${postId}`),
  )
  // 后端返回主题格式（status 为原始 '0'），统一映射为前端类型（'hold'）
  return data.items.map(mapWPComment)
}

export async function pinComment(commentId: number, pin: boolean) {
  const { data } = await apiClient.post<{ pinned: boolean; id: number }>(
    buildRestUrl('simple-theme/v1/comment-pin'),
    { commentId, pin },
  )
  return data
}
