/**
 * api-client — Axios 实例 & REST URL 构建工具
 */
import axios from 'axios'
import { getThemeConfig } from '@/lib/theme-config'

export const apiClient = axios.create({
  headers: {
    'X-Requested-With': 'XMLHttpRequest',
  },
  timeout: 8000,
})

export const buildRestUrl = (path = '') => {
  let restRoot = getThemeConfig().restRoot.replace(/\/+$/, '')

  try {
    const restUrl = new URL(restRoot)
    if (restUrl.origin !== window.location.origin) {
      restRoot = `${window.location.origin}${restUrl.pathname}`
    }
  } catch {
    // restRoot is not a valid URL — use as-is
  }

  if (!path) {
    return `${restRoot}/`
  }

  return `${restRoot}/${path.replace(/^\/+/, '')}`
}
