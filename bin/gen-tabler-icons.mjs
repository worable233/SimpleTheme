/**
 * 生成 Tabler 图标资源：
 *   读取 src/lib/tabler-icon-map.json（语义名 → Tabler kebab 名），
 *   校验每个 Tabler 名在 @tabler/icons 节点数据中存在，
 *   生成 src/lib/tabler-icons.generated.ts：
 *     - ICON_NAMES：已收录的 Tabler kebab 名清单（图标选择器用）
 *     - ICON_NODES / ICON_NODES_FILLED：内联 SVG 内部节点串（AppIcon / StIcon /
 *       prose-icons 用，见 src/lib/icon-svg.ts）
 *     - ICON_MORPH_NODES：原始 IconNode 数组，给 morphicons 形变动画消费
 *
 * 说明：早期版本还会生成 ICON_COMPONENTS（@tabler/icons-vue 的 Vue 组件），
 * 但组件里重复携带了一遍路径数据，打进前端是几百 KB。现在统一走节点串渲染，
 * 运行时不再需要 @tabler/icons-vue。
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
const nodesOut = {}
const nodesFilledOut = {}

for (const kebab of kebabs) {
  if (!outlineNodes[kebab]) {
    missing.push(kebab)
    continue
  }
  nodesOut[kebab] = nodesToInner(outlineNodes[kebab])
  if (filledNodes[kebab]) {
    nodesFilledOut[kebab] = nodesToInner(filledNodes[kebab])
  }
}

if (missing.length) {
  console.error('缺失的 Tabler 图标（请修正 tabler-icon-map.json）：\n  ' + missing.join('\n  '))
  process.exit(1)
}

// morphicons 形变数据：只对白名单里的成对图标生成原始 IconNode。
// 全量生成会多出 ~13KB gzip，而这些图标绝大多数不会运行时两两切换。
const morphKebabs = Array.isArray(map._morphIcons) ? map._morphIcons : []
const morphOut = {}
const morphMissing = []
for (const kebab of morphKebabs) {
  if (!outlineNodes[kebab]) {
    morphMissing.push(kebab)
    continue
  }
  morphOut[kebab] = outlineNodes[kebab]
}
if (morphMissing.length) {
  console.error('_morphIcons 中缺失的 Tabler 图标：\n  ' + morphMissing.join('\n  '))
  process.exit(1)
}

const nodeEntries = Object.entries(nodesOut)
  .map(([k, v]) => `  '${k}': ${JSON.stringify(v)},`)
  .join('\n')
const nodeFilledEntries = Object.entries(nodesFilledOut)
  .map(([k, v]) => `  '${k}': ${JSON.stringify(v)},`)
  .join('\n')
const morphEntries = Object.entries(morphOut)
  .map(([k, v]) => `  '${k}': ${JSON.stringify(v)},`)
  .join('\n')

const out = `// 本文件由 bin/gen-tabler-icons.mjs 自动生成，请勿手动编辑。
// 数据源：src/lib/tabler-icon-map.json + @tabler/icons 节点数据。

/** 原始 IconNode 数据（[tag, attrs] 列表），与 morphicons 的 IconNode 结构一致 */
export type IconNodeData = ReadonlyArray<readonly [string, Record<string, string | number>]>

/** 已收录的 Tabler kebab 名清单（顺序与节点数据无关） */
export const ICON_NAMES: readonly string[] = [
${kebabs.map((k) => `  '${k}',`).join('\n')}
]

/** Tabler kebab 名 → outline SVG 内部节点串 */
export const ICON_NODES: Record<string, string> = {
${nodeEntries}
}

/** Tabler kebab 名 → filled SVG 内部节点串 */
export const ICON_NODES_FILLED: Record<string, string> = {
${nodeFilledEntries}
}

/** Tabler kebab 名 → 原始 IconNode（morphicons 形变动画消费）。
 *  仅包含 tabler-icon-map.json 的 _morphIcons 白名单，控制体积。 */
export const ICON_MORPH_NODES: Record<string, IconNodeData> = {
${morphEntries}
}
`

writeFileSync(resolve(root, 'src/lib/tabler-icons.generated.ts'), out)
console.log(
  `已生成 tabler-icons.generated.ts：${kebabs.length} 个图标（${Object.keys(nodesFilledOut).length} 个含 filled 变体，${morphKebabs.length} 个含 morph 数据）。`,
)
