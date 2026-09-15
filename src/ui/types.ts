/**
 * src/ui — 组件库共享类型
 *
 * 这些类型定义了组件对外契约的公共词汇表。新增组件时优先复用，
 * 不要各自发明同义命名。
 */

/** 控件尺寸档位，映射到 --st-height-* / --st-font-* 令牌 */
export type StSize = 'tiny' | 'small' | 'medium' | 'large'

/** 表单校验状态 */
export type StStatus = 'success' | 'warning' | 'error'

/** 按钮/标签的语义色。
 * 刻意不含 'info' —— 令牌层没有定义 info 色，不凭空发明颜色。 */
export type StIntent = 'default' | 'primary' | 'success' | 'warning' | 'error'

/** 浮层相对触发器的方位 */
export type StPlacement =
  | 'top'
  | 'top-start'
  | 'top-end'
  | 'bottom'
  | 'bottom-start'
  | 'bottom-end'
  | 'left'
  | 'right'

/** 标签页 / 分段控件的通用选项结构 */
export interface StOption {
  label: string
  value: string | number
  disabled?: boolean
}

export const ST_SIZES: readonly StSize[] = ['tiny', 'small', 'medium', 'large'] as const
