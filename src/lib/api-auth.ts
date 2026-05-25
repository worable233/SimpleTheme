/**
 * api-auth.ts — 登录/注册/密码重置 API 客户端
 */
import { apiClient, buildRestUrl } from './api-client'
import { getThemeConfig } from './theme-config'

/** 登录 */
export async function apiLogin(log: string, pwd: string, rememberme = false) {
  const { data } = await apiClient.post(buildRestUrl('auth/login'), { log, pwd, rememberme })
  return data as {
    success: boolean
    user?: { id: number; display_name: string; avatar: string; email: string }
    rest_nonce?: string
    redirect_to?: string
    code?: string
    message?: string
  }
}

/** 注册 */
export async function apiRegister(user_login: string, user_email: string) {
  const { data } = await apiClient.post(buildRestUrl('auth/register'), { user_login, user_email })
  return data as {
    success: boolean
    message?: string
    code?: string
  }
}

/** 忘记密码 */
export async function apiLostPassword(user_login: string) {
  const { data } = await apiClient.post(buildRestUrl('auth/lost-password'), { user_login })
  return data as {
    success: boolean
    message?: string
    code?: string
  }
}

/** 验证重置密钥 */
export async function apiValidateResetKey(key: string, login: string) {
  const { data } = await apiClient.get(buildRestUrl('auth/validate-reset-key'), {
    params: { key, login },
  })
  return data as {
    success: boolean
    message?: string
    code?: string
    user?: { display_name: string; user_email: string }
  }
}

/** 重置密码 */
export async function apiResetPassword(key: string, login: string, pass1: string, pass2: string) {
  const { data } = await apiClient.post(buildRestUrl('auth/reset-password'), { key, login, pass1, pass2 })
  return data as {
    success: boolean
    message?: string
    code?: string
  }
}

/** 获取当前用户信息 */
export async function apiFetchMe() {
  const { data } = await apiClient.get(buildRestUrl('auth/me'))
  return data as {
    logged_in: boolean
    user?: { id: number; display_name: string; avatar: string; email: string }
    rest_nonce?: string
    admin_url?: string
  }
}
