/**
 * api-comments — 评论相关 API
 */
import { buildRestUrl } from './api-client'
import type { WordPressComment, CaptchaData, CommentsResponse } from '@/types/wordpress'
import { apiClient } from './api-client'
import axios from 'axios'

export async function fetchComments(
  postId: number,
  clientId?: string,
  page = 1,
  perPage = 50,
): Promise<CommentsResponse> {
  try {
    let url = buildRestUrl(`simple-theme/v1/comments/${postId}`)
    const params: Record<string, string> = {
      page: String(page),
      perPage: String(perPage),
    }
    if (clientId) {
      params.client_id = clientId
    }
    url += '?' + new URLSearchParams(params).toString()
    const { data } = await apiClient.get<CommentsResponse>(url)
    return data
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
  captchaSeed?: string
  captchaAnswer?: number
  isPrivate?: boolean
  mailNotify?: boolean
  useMarkdown?: boolean
}) {
  const { data } = await apiClient.post<{ item: WordPressComment }>(
    buildRestUrl('simple-theme/v1/comments'),
    payload,
  )
  return data.item
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

export async function editComment(commentId: number, content: string) {
  const { data } = await apiClient.post<{ item: WordPressComment }>(
    buildRestUrl('simple-theme/v1/comment-edit'),
    { commentId, content },
  )
  return data.item
}

export async function fetchCommentHistory(commentId: number) {
  const { data } = await apiClient.get<{ history: Array<{ content: string; time: string }> }>(
    buildRestUrl(`simple-theme/v1/comment-history/${commentId}`),
  )
  return data.history
}

export async function pinComment(commentId: number, pin: boolean) {
  const { data } = await apiClient.post<{ pinned: boolean; id: number }>(
    buildRestUrl('simple-theme/v1/comment-pin'),
    { commentId, pin },
  )
  return data
}
