/**
 * 将 undraw-svg 包中使用的插画复制到：
 *   - src/assets/illustrations/ — 供 import.meta.glob('?raw') 构建时内联
 *   - public/illustrations/    — 作为静态产物拷贝到 dist/，供 PHP 端点读取
 * 运行: node bin/copy-illustrations.mjs
 */
import { copyFileSync, existsSync, mkdirSync, readdirSync, rmSync } from 'node:fs'
import { fileURLToPath } from 'node:url'
import { dirname, resolve, join } from 'node:path'

const __dirname = dirname(fileURLToPath(import.meta.url))
const root = resolve(__dirname, '..')

const svgDir = join(root, 'node_modules', 'undraw-svg', 'svgs')
const targetDirs = [
  join(root, 'src', 'assets', 'illustrations'),
  join(root, 'public', 'illustrations'),
]

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

let copied = 0
let failed = 0

for (const dir of targetDirs) {
  // 清空已有文件，避免构建打包未使用的旧文件
  if (existsSync(dir)) {
    const existing = readdirSync(dir)
    for (const file of existing) {
      rmSync(join(dir, file), { force: true })
    }
    console.log(`  清空 ${dir}（${existing.length} 个旧文件）`)
  } else {
    mkdirSync(dir, { recursive: true })
  }

  for (const name of usedSvgs) {
    const src = join(svgDir, `${name}.svg`)
    const dst = join(dir, `${name}.svg`)
    if (existsSync(src)) {
      copyFileSync(src, dst)
      copied++
    } else {
      console.log(`  ✗ ${name}.svg（在 undraw-svg 包中未找到）`)
      failed++
    }
  }
}

console.log(`\n完成: 复制 ${copied} 个文件${failed ? `, ${failed} 个失败` : ''}`)
