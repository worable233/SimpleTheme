/**
 * api-utils — 通用工具函数
 */
import axios from 'axios'
import { classifyFailure } from '@/lib/connectivity'

export function getErrorMessage(error: unknown, fallback = '请求失败，请稍后重试。') {
  // 连接层问题不要抛原始 axios 文案（如「Request failed with status code 403」）
  const issue = classifyFailure(error)
  if (issue) {
    return issue === 'challenge'
      ? '访问验证已过期，请刷新页面后重试。'
      : '网络连接异常，请检查网络后重试。'
  }

  if (axios.isAxiosError(error)) {
    // Try .message first, then .error (both are used by different endpoints)
    const data = error.response?.data
    const message =
      (typeof data?.message === 'string' && data.message.trim()) ||
      (typeof data?.error === 'string' && data.error.trim())

    if (message) {
      return message
    }

    if (error.message) {
      return error.message
    }
  }

  if (error instanceof Error && error.message) {
    return error.message
  }

  return fallback
}
