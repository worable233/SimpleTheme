/**
 * useAuth — 全局认证状态管理（单例）
 */
import { reactive, readonly } from 'vue'
import { apiClient } from '@/lib/api-client'
import { apiFetchMe } from '@/lib/api-auth'

export interface AuthUser {
  id: number
  displayName: string
  avatar: string
  email: string
  url?: string
}

interface AuthState {
  loggedIn: boolean
  user: AuthUser | null
  restNonce: string | null
  adminUrl: string | null
  logoutUrl: string | null
  loading: boolean
}

const state = reactive<AuthState>({
  loggedIn: false,
  user: null,
  restNonce: null,
  adminUrl: null,
  logoutUrl: null,
  loading: true,
})

let initialized = false

function clearAuthState() {
  state.loggedIn = false
  state.user = null
  state.restNonce = null
  state.adminUrl = null
  state.logoutUrl = null
  delete apiClient.defaults.headers.common['X-WP-Nonce']
}

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
          url: result.user.url,
        }
        state.restNonce = result.rest_nonce || null
        state.adminUrl = result.admin_url || null
        state.logoutUrl = result.logout_url || null

        // 同步更新 axios 实例的 nonce
        if (state.restNonce) {
          apiClient.defaults.headers.common['X-WP-Nonce'] = state.restNonce
        }
      } else {
        clearAuthState()
      }
    } catch {
      clearAuthState()
    } finally {
      state.loading = false
    }
  }

  /** 登录成功后更新状态 */
  function setLoggedIn(user: AuthUser, restNonce: string, adminUrl: string, logoutUrl?: string) {
    state.loggedIn = true
    state.user = { ...user }
    state.restNonce = restNonce
    state.adminUrl = adminUrl
    state.logoutUrl = logoutUrl || null
    apiClient.defaults.headers.common['X-WP-Nonce'] = restNonce
  }

  /** 退出登录 */
  function logout() {
    const logoutUrl = state.logoutUrl
    clearAuthState()

    // auth/me returns this private, session-specific URL after cookie auth.
    if (logoutUrl) {
      window.location.href = logoutUrl
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
