/**
 * sidebar/icon-map — 菜单图标映射（向后兼容导出）
 *
 * 所有逻辑已迁移至 src/lib/icon-resolver.ts，
 * 此文件仅作重新导出以保持现有导入路径正常工作。
 *
 * 新代码请直接 import 自 '@/lib/icon-resolver'
 */

export {
  getItemIcon,
  getIconHtml,
  getIconList,
  searchIcons,
  hasIcon,
} from '@/lib/icon-resolver'

export type { IconEntry } from '@/lib/icon-resolver'
