/**
 * 生成 Tabler 图标资源：
 *   读取 src/lib/tabler-icon-map.json（语义名 → Tabler kebab 名），
 *   校验每个 Tabler 名在 @tabler/icons 节点数据中存在，生成两个文件：
 *
 *   src/lib/tabler-icons-core.generated.ts（随主包同步加载，很小）
 *     - ICON_NAMES：已收录的 Tabler kebab 名清单（图标选择器用）
 *     - ICON_NODES_CORE / ICON_NODES_FILLED_CORE：`_coreIcons` 白名单的节点串
 *       （首屏/正文常用图标，同步渲染，不会闪）
 *     - ICON_MORPH_PATHS：`_morphIcons` 白名单的 d 串（形变动画用）
 *
 *   src/lib/tabler-icons.generated.ts（按需 import()，不占首屏）
 *     - ICON_NODES / ICON_NODES_FILLED：全量节点串，配合 icon-svg 的懒加载
 *
 * 说明：早期版本会用 @tabler/icons-vue 生成 Vue 组件，组件里重复带了一遍
 * 路径数据（几百 KB），现已全部改为节点串渲染，运行时不再依赖它。
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

// 节点数组 → 合并后的 d 串（形变动画只认路径数据）
function nodesToPathData(nodes) {
  return nodes.map(([, attrs]) => attrs.d).join(' ')
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

// 首屏核心图标白名单：少了会先空一帧再出现，多了就白占体积。
// 由 map 的 `_coreIcons` 显式声明（生成脚本只做校验）。
const coreKebabs = (Array.isArray(map._coreIcons) ? map._coreIcons : []).filter((k) => {
  if (nodesOut[k]) return true
  console.warn(`提示：_coreIcons 中的 ${k} 不在已收录图标里，已跳过`)
  return false
})

// 形变动画数据：只对 `_morphIcons` 白名单生成，避免多带几十 KB
const morphKebabs = Array.isArray(map._morphIcons) ? map._morphIcons : []
const morphOut = {}
const morphMissing = []
for (const kebab of morphKebabs) {
  if (!outlineNodes[kebab]) {
    morphMissing.push(kebab)
    continue
  }
  morphOut[kebab] = nodesToPathData(outlineNodes[kebab])
}
if (morphMissing.length) {
  console.error('_morphIcons 中缺失的 Tabler 图标：\n  ' + morphMissing.join('\n  '))
  process.exit(1)
}

const entry = (k, v) => `  '${k}': ${JSON.stringify(v)},`

const coreOut = `// 本文件由 bin/gen-tabler-icons.mjs 自动生成，请勿手动编辑。
// 数据源：src/lib/tabler-icon-map.json + @tabler/icons 节点数据。
// 这里是「随主包同步加载」的小份数据（核心图标 + 形变动画），
// 全量图标在 tabler-icons.generated.ts 里按需 import。

/** 已收录的 Tabler kebab 名清单 */
export const ICON_NAMES: readonly string[] = [
${kebabs.map((k) => `  '${k}',`).join('\n')}
]

/** 核心图标（首屏/正文会用到，同步渲染）：Tabler kebab 名 → outline SVG 内部节点串 */
export const ICON_NODES_CORE: Record<string, string> = {
${coreKebabs.map((k) => entry(k, nodesOut[k])).join('\n')}
}

/** 核心图标（实心变体），仅包含存在 filled 版者 */
export const ICON_NODES_FILLED_CORE: Record<string, string> = {
${coreKebabs
  .filter((k) => nodesFilledOut[k])
  .map((k) => entry(k, nodesFilledOut[k]))
  .join('\n')}
}

/** 可形变图标：Tabler kebab 名 → 合并后的 d 串（见 src/lib/icon-morph.ts） */
export const ICON_MORPH_PATHS: Record<string, string> = {
${Object.entries(morphOut)
  .map(([k, v]) => entry(k, v))
  .join('\n')}
}
`

const fullOut = `// 本文件由 bin/gen-tabler-icons.mjs 自动生成，请勿手动编辑。
// 数据源：src/lib/tabler-icon-map.json + @tabler/icons 节点数据。
// 全量图标数据：由 src/lib/icon-svg.ts 在遇到非核心图标时按需 import()，
// 不要在启动路径上静态引入它。

/** Tabler kebab 名 → outline SVG 内部节点串 */
export const ICON_NODES: Record<string, string> = {
${Object.entries(nodesOut)
  .map(([k, v]) => entry(k, v))
  .join('\n')}
}

/** Tabler kebab 名 → filled SVG 内部节点串 */
export const ICON_NODES_FILLED: Record<string, string> = {
${Object.entries(nodesFilledOut)
  .map(([k, v]) => entry(k, v))
  .join('\n')}
}
`

writeFileSync(resolve(root, 'src/lib/tabler-icons-core.generated.ts'), coreOut)
writeFileSync(resolve(root, 'src/lib/tabler-icons.generated.ts'), fullOut)
console.log(
  `已生成图标数据：全量 ${kebabs.length} 个；核心 ${coreKebabs.length} 个（同步）；morph ${morphKebabs.length} 个。`,
)
