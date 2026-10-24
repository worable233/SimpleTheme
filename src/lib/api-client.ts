/**
 * api-client — Axios 实例 & REST URL 构建工具
 */
import axios from 'axios'
import { getThemeConfig } from '@/lib/theme-config'
import {
  createCircuitError,
  isCircuitOpen,
  reportRequestFailure,
  reportRequestSuccess,
} from '@/lib/connectivity'

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

// ========== 连接层熔断 ==========
// 站点被人机验证（Cloudflare 等）拦住或后端完全不可达时，一屏并行请求会全部
// 失败。熔断后新请求直接本地失败，既不打网络，也不再逐条弹 toast；
// 具体提示由 <ConnectionBanner /> 统一给出。
apiClient.interceptors.request.use((config) => {
  if (isCircuitOpen()) {
    return Promise.reject(createCircuitError(config.url))
  }
  return config
})

apiClient.interceptors.response.use(
  (response) => {
    reportRequestSuccess()
    return response
  },
  (error) => {
    reportRequestFailure(error)
    return Promise.reject(error)
  },
)

/**
 * 构建 REST 地址。
 *
 * WordPress 未开启「固定链接」（朴素链接）时 restRoot 是
 * `index.php?rest_route=/` 形式，直接把 `path?query` 拼在后面会把查询串
 * 塞进 rest_route 参数里，接口返回 404。这里统一拆解路径与查询参数：
 * 朴素链接写进 rest_route，其余参数正常附加。
 */
export const buildRestUrl = (
  path = '',
  extraQuery?: Record<string, string | number | boolean | undefined>,
) => {
  let restRoot = getThemeConfig().restRoot

  try {
    const restUrl = new URL(restRoot)
    if (restUrl.origin !== window.location.origin) {
      restRoot = `${window.location.origin}${restUrl.pathname}${restUrl.search}`
    }
  } catch {
    // restRoot is not a valid URL — use as-is
  }

  const [rawPath = '', rawQuery = ''] = path.split('?')
  const route = rawPath.replace(/^\/+/, '').replace(/\/+$/, '')
  const params = new URLSearchParams(rawQuery)
  Object.entries(extraQuery || {}).forEach(([key, value]) => {
    if (value !== undefined && value !== null) params.set(key, String(value))
  })

  if (restRoot.includes('rest_route=')) {
    const url = new URL(restRoot)
    const baseRoute = url.searchParams.get('rest_route') || '/'
    url.searchParams.set('rest_route', `${baseRoute.replace(/\/+$/, '')}/${route}`)
    params.forEach((value, key) => url.searchParams.set(key, value))
    return url.toString()
  }

  const base = `${restRoot.replace(/\/+$/, '')}/${route}`
  const query = params.toString()
  return query ? `${base}?${query}` : base
}
