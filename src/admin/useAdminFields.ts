import type { AdminSettings } from './api'

/**
 * useAdminFields — 后台设置页读取字段的统一入口。
 *
 * 设置是「已保存值 ?? 默认值 ?? 兜底」的三段回退：后端返回的 settings 已经过
 * sanitize，理论上字段齐全；但旧站迁移、局部字段缺失时仍要靠 defaults 兜底。
 * 把回退逻辑收在一处，避免每个标签页各写一遍 `settings[key] ?? defaults[key]`。
 *
 * 入参是 getter 而非对象：settings/defaults 都是 ref，父子组件各自 update 后
 * 需要读到最新值。
 */
export function useAdminFields(settings: () => AdminSettings, defaults: () => AdminSettings) {
  function value(key: string, fallback: unknown = '') {
    const resolved = settings()[key] ?? defaults()[key]
    return resolved === undefined || resolved === null ? fallback : resolved
  }

  return {
    value,
    text(key: string, fallback = '') {
      return String(value(key, fallback))
    },
    number(key: string, fallback = 0) {
      const parsed = Number(value(key, fallback))
      return Number.isFinite(parsed) ? parsed : fallback
    },
    checked(key: string, fallback = false) {
      return Boolean(value(key, fallback))
    },
  }
}
