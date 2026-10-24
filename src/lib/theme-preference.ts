/**
 * 主题偏好
 *
 * 三种取值：auto（跟随系统，默认）/ light / dark。
 * 存储键沿用旧的 'theme'，老用户已选过的 light / dark 继续有效。
 */

export type ThemePreference = 'auto' | 'light' | 'dark'
export type ResolvedTheme = 'light' | 'dark'

const STORAGE_KEY = 'theme'

const darkQuery = (): MediaQueryList => window.matchMedia('(prefers-color-scheme: dark)')

/** 系统当前的明暗 */
export function getSystemTheme(): ResolvedTheme {
  return darkQuery().matches ? 'dark' : 'light'
}

/** 读取偏好；没存过（或值非法）就是跟随系统 */
export function readThemePreference(): ThemePreference {
  try {
    const saved = localStorage.getItem(STORAGE_KEY)
    if (saved === 'light' || saved === 'dark' || saved === 'auto') return saved
  } catch {
    // 隐私模式 / 存储被禁用时按未设置处理
  }
  return 'auto'
}

/** 偏好 → 实际生效的明暗 */
export function resolveTheme(preference: ThemePreference): ResolvedTheme {
  return preference === 'auto' ? getSystemTheme() : preference
}

export function applyTheme(theme: ResolvedTheme): void {
  document.documentElement.setAttribute('data-theme', theme)
  document.body.setAttribute('data-theme', theme)
  document.documentElement.style.colorScheme = theme
}

export function setThemePreference(preference: ThemePreference): void {
  try {
    localStorage.setItem(STORAGE_KEY, preference)
  } catch {
    // 存不进去不影响本次生效
  }
}

/**
 * 点一下按钮的下一步。
 *
 * 循环固定为「跟随系统 → 与系统相反 → 与系统相同 → 跟随系统」，
 * 这样从默认的「跟随系统」点下去一定看得见变化（否则系统是浅色时
 * 切到「浅色」毫无反馈）。
 */
export function nextThemePreference(preference: ThemePreference): ThemePreference {
  const system = getSystemTheme()
  const opposite: ResolvedTheme = system === 'light' ? 'dark' : 'light'
  if (preference === 'auto') return opposite
  if (preference === opposite) return system
  return 'auto'
}

/** 订阅系统明暗变化；返回取消订阅函数 */
export function watchSystemTheme(handler: (theme: ResolvedTheme) => void): () => void {
  const query = darkQuery()
  const listener = () => handler(getSystemTheme())
  query.addEventListener('change', listener)
  return () => query.removeEventListener('change', listener)
}

/** 偏好的中文名，用于按钮提示 */
export function themePreferenceLabel(
  preference: ThemePreference,
  resolved: ResolvedTheme,
): string {
  if (preference === 'auto') return `跟随系统（当前${resolved === 'dark' ? '深色' : '浅色'}）`
  return preference === 'dark' ? '深色模式' : '浅色模式'
}
