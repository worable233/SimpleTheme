/**
 * 将 undraw-svg 包中使用的插画复制到 public/illustrations/
 * 运行: node bin/copy-illustrations.js
 */
import { copyFileSync, existsSync, mkdirSync, readdirSync, rmSync } from 'node:fs'
import { fileURLToPath } from 'node:url'
import { dirname, resolve, join } from 'node:path'

const __dirname = dirname(fileURLToPath(import.meta.url))
const root = resolve(__dirname, '..')

const svgDir = join(root, 'node_modules', 'undraw-svg', 'svgs')
const publicDir = join(root, 'public', 'illustrations')

// 项目中实际使用的插画列表（不含 .svg 后缀）
const usedSvgs = [
  'cancel',
  'access-denied',
  'chatting',
  'alert',
  'searching',
  'search',
  'about-me',
  'warning',
  'lost',
  'empty',
  'team-spirit',
  'programming',
  'navigator',
  'blank-canvas',
  'add-friends',
]

// 清空已有文件，避免构建打包未使用的旧文件
if (existsSync(publicDir)) {
  const existing = readdirSync(publicDir)
  for (const file of existing) {
    rmSync(join(publicDir, file), { force: true })
  }
  console.log(`  清空 ${publicDir}（${existing.length} 个旧文件）`)
}

if (!existsSync(publicDir)) {
  mkdirSync(publicDir, { recursive: true })
}

let copied = 0
let failed = 0

for (const name of usedSvgs) {
  const src = join(svgDir, `${name}.svg`)
  const dst = join(publicDir, `${name}.svg`)
  if (existsSync(src)) {
    copyFileSync(src, dst)
    console.log(`  ✓ ${name}.svg`)
    copied++
  } else {
    console.log(`  ✗ ${name}.svg（在 undraw-svg 包中未找到）`)
    failed++
  }
}

console.log(`\n完成: 复制 ${copied} 个文件${failed ? `, ${failed} 个失败` : ''}`)
