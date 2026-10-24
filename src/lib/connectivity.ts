/**
 * connectivity — 请求失败的全局分类、熔断与提示去噪
 *
 * 场景：站点挂在 Cloudflare（或类似 WAF）后面时，人机验证的 cookie 过期后
 * 所有 REST 请求都会返回 403 + 挑战页 HTML。如果不加处理，SPA 每跳一次页
 * 就会并发打一串请求、每个都弹一条 toast，用户看到的就是满屏
 * 「Request failed with status code 403」。
 *
 * 这里做三件事：
 * 1. 把失败归类成 challenge / offline / server；
 * 2. 触发熔断：冷却期内新请求直接本地失败，不再打网络（也不再逐条弹提示）；
 * 3. 让 UI 用一个横幅统一说明情况，并给出「刷新 / 重试」出口。
 */
import { shallowRef } from 'vue'
import axios from 'axios'

export type ConnectionIssue = 'challenge' | 'offline' | 'server'

/** 当前生效的连接问题；null 表示一切正常 */
export const connectionIssue = shallowRef<ConnectionIssue | null>(null)

/** 熔断截止时间戳（毫秒） */
let circuitUntil = 0
/** 连续失败计数（server 类错误需要连续两次才熔断） */
let consecutiveFailures = 0

/** 各类型问题的熔断时长 */
const ISSUE_COOLDOWN: Record<ConnectionIssue, number> = {
  // 人机验证：用户必须刷新页面重新过验证，冷却时间长一些避免反复打扰
  challenge: 60_000,
  offline: 15_000,
  server: 15_000,
}

/** 熔断期间抛出的错误标记，便于上层识别“没有真的发请求” */
const CIRCUIT_ERROR_MESSAGE = '__ST_CIRCUIT_OPEN__'

/** Cloudflare 挑战页的典型特征（响应体是 HTML，不是 REST 的 JSON） */
const CHALLENGE_MARKERS = [
  'just a moment',
  'cf-chl',
  'cf_chl',
  '_cf_chl',
  'challenge-platform',
  'checking your browser',
  'attention required',
]

export interface CircuitError extends Error {
  isCircuitError: true
  __stCircuit: true
}

/** 判断错误是否是“熔断期间本地生成的”，而不是真实网络失败 */
export function isCircuitError(error: unknown): error is CircuitError {
  return Boolean(
    error && typeof error === 'object' && (error as { __stCircuit?: boolean }).__stCircuit,
  )
}

/** 生成一个熔断期错误（不发请求，直接失败） */
export function createCircuitError(url?: string): CircuitError {
  const error = new Error(CIRCUIT_ERROR_MESSAGE) as CircuitError
  error.name = 'StCircuitError'
  error.isCircuitError = true
  error.__stCircuit = true
  if (url) error.message = `${CIRCUIT_ERROR_MESSAGE} ${url}`
  return error
}

function looksLikeChallenge(error: unknown): boolean {
  if (!axios.isAxiosError(error)) return false
  const response = error.response
  if (!response) return false
  if (![403, 503].includes(response.status)) return false

  const body = typeof response.data === 'string' ? response.data.toLowerCase() : ''
  if (body && CHALLENGE_MARKERS.some((marker) => body.includes(marker))) return true

  // REST 正常返回 JSON；403 + HTML 说明请求被验证层截走了
  const contentType = String(response.headers?.['content-type'] ?? '')
  return response.status === 403 && contentType.includes('text/html')
}

/**
 * 归类一次请求失败。
 * 返回 null 表示这是业务错误（404 / 参数错误 / 业务 403 等），不该触发熔断。
 */
export function classifyFailure(error: unknown): ConnectionIssue | null {
  if (isCircuitError(error)) return connectionIssue.value ?? 'offline'
  if (!axios.isAxiosError(error)) return null

  if (looksLikeChallenge(error)) return 'challenge'

  // 没有 response：断网、DNS 失败、超时
  if (!error.response) return 'offline'

  if (error.response.status >= 500) return 'server'

  return null
}

/** 请求成功：一切恢复正常 */
export function reportRequestSuccess() {
  consecutiveFailures = 0
  circuitUntil = 0
  connectionIssue.value = null
}

/** 请求失败：按类型决定是否熔断 */
export function reportRequestFailure(error: unknown) {
  const issue = classifyFailure(error)
  if (!issue) return

  consecutiveFailures += 1

  // 人机验证过期与断网都是“整站不可用”，立即熔断；
  // 单次 5xx 可能只是某个接口抖动，连续两次再熔断。
  const shouldOpen = issue !== 'server' || consecutiveFailures >= 2
  if (!shouldOpen) return

  circuitUntil = Date.now() + ISSUE_COOLDOWN[issue]
  connectionIssue.value = issue
}

/** 当前是否处于熔断期（使用 !== 以便覆盖 Date.now() 恰好相等的边界） */
export function isCircuitOpen() {
  return Date.now() < circuitUntil
}

/** 手动复位（横幅上的「重试」按钮会先复位再刷新） */
export function resetConnection() {
  reportRequestSuccess()
}

/**
 * 是否属于“连接层问题”——这类错误的 toast 由横幅统一表达，不必逐条弹。
 */
export function isConnectivityError(error: unknown) {
  return classifyFailure(error) !== null
}

/** 文案上的网络类错误特征（用于在熔断期拦住其他入口弹出的同类提示） */
const NETWORK_LIKE_MESSAGE =
  /(__ST_CIRCUIT_OPEN__)|(status code (?:403|408|429|5\d\d))|(network error)|(failed to fetch)|(timeout of \d)|(econnaborted)/i

export function isNetworkLikeMessage(message: string) {
  return NETWORK_LIKE_MESSAGE.test(message)
}

/** 连接问题的默认文案与按钮（横幅与提示层共用） */
export const CONNECTION_COPY: Record<ConnectionIssue, { message: string; action: string }> = {
  challenge: {
    message: '访问验证已过期，实时内容已暂停加载。刷新页面完成验证后即可继续。',
    action: '刷新验证',
  },
  offline: {
    message: '当前网络不可用，已切换到本地存档内容。网络恢复后点「重试」即可。',
    action: '重试',
  },
  server: {
    message: '服务器暂时无响应，已切换到本地存档内容。稍后点「重试」即可。',
    action: '重试',
  },
}
