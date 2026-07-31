/**
 * 生成 Tabler 图标资源：
 *   读取 src/lib/tabler-icon-map.json（语义名 → Tabler kebab 名），
 *   校验每个 Tabler 名在 @tabler/icons 节点数据中存在，
 *   生成 src/lib/tabler-icons.generated.ts：
 *     - ICON_COMPONENTS / ICON_COMPONENTS_FILLED：给 AppIcon.vue 用的 Tabler Vue 组件（按需静态导入、可 tree-shake）
 *     - ICON_NODES / ICON_NODES_FILLED：给 prose-icons.ts 用的内联 SVG 内部节点串
 *
 * 用法：node bin/gen-tabler-icons.mjs
 */
import { readFileSync, writeFileSync } from 'node:fs'
import { fileURLToPath } from 'node:url'
import { dirname, resolve } from 'node:path'

const __dirname = dirname(fileURLToPath(import.meta.url))
const root = resolve(__dirname, '..')

const map = JSON.parse(readFileSync(resolve(root, 'src/lib/tabler-icon-map.json'), 'utf8'))
const outlineNodes = JSON.parse(
  readFileSync(resolve(root, 'node_modules/@tabler/icons/tabler-nodes-outline.json'), 'utf8'),
)
const filledNodes = JSON.parse(
  readFileSync(resolve(root, 'node_modules/@tabler/icons/tabler-nodes-filled.json'), 'utf8'),
)

// kebab-case → PascalCase Tabler 组件名（IconXxx）
function toComponent(kebab) {
  return 'Icon' + kebab.split('-').map((s) => s.charAt(0).toUpperCase() + s.slice(1)).join('')
}

// 节点数组 [["path",{d:"..."}], ...] → 内部 SVG 串
function nodesToInner(nodes) {
  return nodes
    .map(([tag, attrs]) => {
      const a = Object.entries(attrs)
        .map(([k, v]) => `${k}="${v}"`)
        .join(' ')
      return `<${tag} ${a} />`
    })
    .join('')
}

const kebabs = [
  ...new Set(
    Object.entries(map)
      .filter(([k, v]) => !k.startsWith('_') && typeof v === 'string')
      .map(([, v]) => v),
  ),
].sort()

const missing = []
const outlineImports = []
const filledImports = []
const nodesOut = {}
const nodesFilledOut = {}

for (const kebab of kebabs) {
  if (!outlineNodes[kebab]) {
    missing.push(kebab)
    continue
  }
  outlineImports.push(toComponent(kebab))
  nodesOut[kebab] = nodesToInner(outlineNodes[kebab])
  if (filledNodes[kebab]) {
    filledImports.push(toComponent(kebab) + 'Filled')
    nodesFilledOut[kebab] = nodesToInner(filledNodes[kebab])
  }
}

if (missing.length) {
  console.error('缺失的 Tabler 图标（请修正 tabler-icon-map.json）：\n  ' + missing.join('\n  '))
  process.exit(1)
}

const compEntries = kebabs.map((k) => `  '${k}': ${toComponent(k)},`).join('\n')
const filledCompEntries = kebabs
  .filter((k) => filledNodes[k])
  .map((k) => `  '${k}': ${toComponent(k)}Filled,`)
  .join('\n')
const nodeEntries = Object.entries(nodesOut)
  .map(([k, v]) => `  '${k}': ${JSON.stringify(v)},`)
  .join('\n')
const nodeFilledEntries = Object.entries(nodesFilledOut)
  .map(([k, v]) => `  '${k}': ${JSON.stringify(v)},`)
  .join('\n')

const out = `/* eslint-disable */
// 本文件由 bin/gen-tabler-icons.mjs 自动生成，请勿手动编辑。
// 数据源：src/lib/tabler-icon-map.json + @tabler/icons 节点数据。
import type { Component } from 'vue'
import {
${outlineImports.map((n) => '  ' + n + ',').join('\n')}
${filledImports.map((n) => '  ' + n + ',').join('\n')}
} from '@tabler/icons-vue'

/** Tabler kebab 名 → outline Vue 组件（AppIcon 使用） */
export const ICON_COMPONENTS: Record<string, Component> = {
${compEntries}
}

/** Tabler kebab 名 → filled Vue 组件（存在 filled 变体者） */
export const ICON_COMPONENTS_FILLED: Record<string, Component> = {
${filledCompEntries}
}

/** Tabler kebab 名 → outline SVG 内部节点串（prose-icons 内联使用） */
export const ICON_NODES: Record<string, string> = {
${nodeEntries}
}

/** Tabler kebab 名 → filled SVG 内部节点串 */
export const ICON_NODES_FILLED: Record<string, string> = {
${nodeFilledEntries}
}
`

writeFileSync(resolve(root, 'src/lib/tabler-icons.generated.ts'), out)
console.log(
  `已生成 tabler-icons.generated.ts：${kebabs.length} 个图标（${filledImports.length} 个含 filled 变体）。`,
)
