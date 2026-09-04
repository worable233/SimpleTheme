/**
 * api-client — Axios 实例 & REST URL 构建工具
 */
import axios from 'axios'
import { getThemeConfig } from '@/lib/theme-config'

const _themeConfig = getThemeConfig()
export const apiClient = axios.create({
  headers: {
    'X-Requested-With': 'XMLHttpRequest',
  },
  // Keep the WordPress auth cookie across REST requests, including installs
  // where the configured site URL differs by host or subdomain.
  withCredentials: true,
  timeout: 8000,
})

// Inject WordPress REST API nonce so logged-in users' POST requests
// (comment submit, like, pin, etc.) don't get rejected by the CSRF check.
if (_themeConfig.restNonce) {
  apiClient.defaults.headers.common['X-WP-Nonce'] = _themeConfig.restNonce
}

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
