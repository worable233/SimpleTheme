/**
 * sidebar/icon-map — 图标解析（向后兼容导出）
 *
 * 所有逻辑已迁移至 src/lib/icon-resolver.ts（Tabler 方案）。
 * 新代码请直接 import 自 '@/lib/icon-resolver' 并配合 AppIcon 组件使用。
 */

export { resolveMenuIcon, resolveIconName, hasIcon } from '@/lib/icon-resolver'
export type { ResolvedIcon } from '@/lib/icon-resolver'
