/**
 * api-utils — 通用工具函数
 */
import axios from 'axios'

export function getErrorMessage(error: unknown, fallback = '请求失败，请稍后重试。') {
  if (axios.isAxiosError(error)) {
    const message = error.response?.data?.message

    if (typeof message === 'string' && message.trim()) {
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
