/**
 * api-comments — 评论相关 API（基于 WordPress /wp/v2/comments 标准端点）
 */
import { buildRestUrl } from './api-client'
import type { WordPressComment, CaptchaData, CommentsResponse } from '@/types/wordpress'
import { apiClient } from './api-client'
import axios from 'axios'

/** 将 WP REST API 评论响应映射到前端 WordPressComment 类型 */
function mapWPComment(item: any): WordPressComment {
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
    children: item.children || [],
    isPinned: item.isPinned ?? false,
    isPrivate: item.isPrivate ?? false,
    canEdit: false,
    useMarkdown: item.useMarkdown ?? false,
    canPin: item.canPin ?? false,
    qqAvatar: item.qqAvatar || '',
  }
}

/** 将扁平评论列表构建为嵌套树 */
function buildCommentTree(items: WordPressComment[], parentId = 0, maxDepth = 3, currentDepth = 0): WordPressComment[] {
  const branch: WordPressComment[] = []
  for (const item of items) {
    if (item.parent === parentId) {
      const children = currentDepth < maxDepth
        ? buildCommentTree(items, item.id, maxDepth, currentDepth + 1)
        : []
      branch.push({ ...item, children })
    }
  }
  return branch
}

export async function fetchComments(
  postId: number,
  _clientId?: string,
  page = 1,
  perPage = 50,
): Promise<CommentsResponse> {
  try {
    const url = buildRestUrl('wp/v2/comments') + `?post=${postId}&page=${page}&per_page=${perPage}&order=asc`
    const { data, headers } = await apiClient.get<any[]>(url)

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
}): Promise<WordPressComment> {
  const { data } = await apiClient.post<any>(
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
  const { data } = await apiClient.get<{ items: WordPressComment[] }>(
    buildRestUrl(`simple-theme/v1/user-pending-comments?post_id=${postId}`),
  )
  return data.items
}

export async function pinComment(commentId: number, pin: boolean) {
  const { data } = await apiClient.post<{ pinned: boolean; id: number }>(
    buildRestUrl('simple-theme/v1/comment-pin'),
    { commentId, pin },
  )
  return data
}
