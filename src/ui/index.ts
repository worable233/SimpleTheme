/**
 * src/ui — Simple Theme 组件库
 *
 * 统一出口。业务层按需具名引入，不要全局注册，以保留 tree-shaking：
 *
 *   import { StButton, StCard } from '@/ui'
 *
 * 组件规范见 ./README.md。
 */

// ── 基础元件 ──
export { default as StIcon } from './components/StIcon.vue'
export { default as StSpinner } from './components/StSpinner.vue'
export { default as StButton } from './components/StButton.vue'

// ── 数据录入 ──
export { default as StInput } from './components/StInput.vue'

// ── 数据展示 ──
export { default as StCard } from './components/StCard.vue'

// ── 类型契约 ──
export type { StSize, StStatus, StIntent, StPlacement, StOption } from './types'
export { ST_SIZES } from './types'
