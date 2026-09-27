/**
 * icon-library — 图标选择器（StIconPicker）的可选清单
 *
 * 数据源：`tabler-icon-map.json`（语义名 / 旧 bx 基础名 / 别名 → Tabler kebab）。
 * 只收录生成脚本已打包进 `ICON_COMPONENTS` 的图标，保证选中的值一定能渲染；
 * 多个别名指向同一 Tabler 图标时合并为一项，取最短别名作展示名，其余进关键词。
 *
 * 新增可选图标：在 `tabler-icon-map.json` 加一行并重跑 `bin/gen-tabler-icons.mjs`，
 * 清单会自动收录 —— 这里不维护第二份数据。
 */

import ICON_MAP from './tabler-icon-map.json'
import { ICON_COMPONENTS } from './tabler-icons.generated'

export interface IconLibraryItem {
  /** 存储值：Tabler kebab 名（StIcon 可直接渲染） */
  name: string
  /** 展示名：最短的可读语义名 */
  label: string
  /** 搜索关键词：全部别名（含 name 本身） */
  keywords: string[]
}

const BUNDLED = new Set(Object.keys(ICON_COMPONENTS))

function buildLibrary(): IconLibraryItem[] {
  const byName = new Map<string, string[]>()

  for (const [alias, tabler] of Object.entries(ICON_MAP as Record<string, string>)) {
    if (alias.startsWith('_') || !BUNDLED.has(tabler)) continue
    const aliases = byName.get(tabler)
    if (aliases) aliases.push(alias)
    else byName.set(tabler, [alias])
  }

  const items: IconLibraryItem[] = []
  for (const [name, aliases] of byName) {
    const keywords = [...new Set([name, ...aliases])].sort(
      (a, b) => a.length - b.length || a.localeCompare(b),
    )
    items.push({ name, label: keywords[0], keywords })
  }
  items.sort((a, b) => a.label.localeCompare(b.label))
  return items
}

/** 精选图标清单（模块加载时构建一次） */
export const ICON_LIBRARY: IconLibraryItem[] = buildLibrary()

/** 按名字/别名模糊搜索；空查询返回全部 */
export function searchIcons(query: string): IconLibraryItem[] {
  const q = query.trim().toLowerCase()
  if (!q) return ICON_LIBRARY
  return ICON_LIBRARY.filter(
    (item) => item.name.includes(q) || item.keywords.some((k) => k.includes(q)),
  )
}

/** 存储值 → 展示名；不在库中时原样返回，兼容历史脏值与自定义写法 */
export function iconLabel(value: string): string {
  if (!value) return ''
  const item = ICON_LIBRARY.find((i) => i.name === value || i.keywords.includes(value))
  return item ? item.label : value
}
