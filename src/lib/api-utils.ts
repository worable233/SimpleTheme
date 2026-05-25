/**
 * api-utils — 通用工具函数
 */
import axios from 'axios'

export function getErrorMessage(error: unknown, fallback = '请求失败，请稍后重试。') {
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
