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

/** 布局间距刻度，映射 --st-space-N（见 tokens.css） */
export type StSpaceScale = 0 | 1 | 2 | 3 | 4 | 5 | 6 | 8

/**
 * 模板里可接受的间距写法。
 *
 * 必须同时接受 number 与数字字符串：模板里写静态属性 `gap="4"` 时，
 * Vue 传进来的是**字符串** `"4"`，而 `:gap="4"` 传的是数字 `4`。
 * 只声明 number 联合会导致 `gap="4"` 触发 TS2322 —— 而"能写 gap=\"4\""
 * 恰恰是组件自己文档示例里的写法。这里放宽到两种都收，
 * 由组件内部归一化成数字再拼 class。
 */
export type StSpace = StSpaceScale | `${StSpaceScale}`

/** 栅格列数 */
export type StCols = 1 | 2 | 3 | 4

/** 模板里可接受的列数写法（理由同 StSpace） */
export type StColsInput = StCols | `${StCols}`

export const ST_SIZES: readonly StSize[] = ['tiny', 'small', 'medium', 'large'] as const
