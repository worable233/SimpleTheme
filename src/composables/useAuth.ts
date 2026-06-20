/**
 * useAuth — 全局认证状态管理（单例）
 */
import { reactive, readonly } from 'vue'
import { apiClient } from '@/lib/api-client'
import { apiFetchMe } from '@/lib/api-auth'
import { getThemeConfig } from '@/lib/theme-config'

export interface AuthUser {
  id: number
  displayName: string
  avatar: string
  email: string
}

interface AuthState {
  loggedIn: boolean
  user: AuthUser | null
  restNonce: string | null
  adminUrl: string | null
  loading: boolean
}

const state = reactive<AuthState>({
  loggedIn: false,
  user: null,
  restNonce: null,
  adminUrl: null,
  loading: true,
})

let initialized = false

export function useAuth() {
  /** 初始化：检查当前是否已登录 */
  async function init() {
    if (initialized) return
    initialized = true
    state.loading = true
    try {
      const result = await apiFetchMe()
      if (result.logged_in && result.user) {
        state.loggedIn = true
        state.user = {
          id: result.user.id,
          displayName: result.user.display_name,
          avatar: result.user.avatar,
          email: result.user.email,
        }
        state.restNonce = result.rest_nonce || null
        state.adminUrl = result.admin_url || null

        // 同步更新 axios 实例的 nonce
        if (state.restNonce) {
          apiClient.defaults.headers.common['X-WP-Nonce'] = state.restNonce
        }
      } else {
        state.loggedIn = false
        state.user = null
        state.restNonce = null
        state.adminUrl = null
      }
    } catch {
      state.loggedIn = false
      state.user = null
    } finally {
      state.loading = false
    }
  }

  /** 登录成功后更新状态 */
  function setLoggedIn(user: AuthUser, restNonce: string, adminUrl: string) {
    state.loggedIn = true
    state.user = { ...user }
    state.restNonce = restNonce
    state.adminUrl = adminUrl
  }

  /** 退出登录 */
  function logout() {
    state.loggedIn = false
    state.user = null
    state.restNonce = null
    state.adminUrl = null

    // 清除 axios 的 nonce header，防止过期 nonce 污染后续请求
    delete apiClient.defaults.headers.common['X-WP-Nonce']

    // 使用 PHP 生成的 logout URL（包含 nonce）
    const config = getThemeConfig()
    if (config?.logoutUrl) {
      window.location.href = config.logoutUrl
    } else {
      window.location.reload()
    }
  }

  return {
    auth: readonly(state) as typeof state,
    init,
    setLoggedIn,
    logout,
  }
}
