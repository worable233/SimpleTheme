/**
 * 构建后清理：
 * 1. 删除多余的 boxicons 字体格式，只保留 .woff2
 * 2. 从 CSS 中移除已删除字体的引用
 * 3. 删除 dist/emojis/ 中的原始 PNG/JPG（已转为 WebP）
 * 运行: node bin/cleanup-boxicons.mjs
 */
import { existsSync, readdirSync, readFileSync, statSync, unlinkSync, writeFileSync } from 'node:fs'
import { extname, join, resolve } from 'node:path'
import { fileURLToPath } from 'node:url'

const __dirname = fileURLToPath(new URL('.', import.meta.url))
const root = resolve(__dirname, '..')
const assetsDir = join(root, 'dist', 'assets')
const emojisDir = join(root, 'dist', 'emojis')

if (!existsSync(assetsDir)) {
  console.log('  dist/assets/ 不存在，跳过清理')
  process.exit(0)
}

// ── 1. boxicons 字体清理 ──
const KEEP_FORMATS = new Set(['.woff2', '.css'])
const BOXICONS_PREFIX = 'boxicons-'

let deleted = 0
for (const file of readdirSync(assetsDir)) {
  if (!file.startsWith(BOXICONS_PREFIX)) continue
  const ext = extname(file).toLowerCase()
  if (KEEP_FORMATS.has(ext)) continue

  const fullPath = join(assetsDir, file)
  unlinkSync(fullPath)
  deleted++
  console.log(`  ✗ boxicons: ${file}`)
}
console.log(`  boxicons 字体清理完成: 删除 ${deleted} 个文件`)

// ── 2. 从 CSS 中移除已删除字体的引用，只保留 woff2 ──
const cssFiles = readdirSync(assetsDir).filter(f => f.endsWith('.css'))
for (const cssFile of cssFiles) {
  const cssPath = join(assetsDir, cssFile)
  let css = readFileSync(cssPath, 'utf8')

  // Replace the entire boxicons @font-face block with a clean woff2-only version
  css = css.replace(/@font-face\{font-family:boxicons[^}]+\}/g, (match) => {
    const woff2Match = match.match(/url\([^)]+\.woff2\)/)
    if (woff2Match) {
      return `@font-face{font-family:boxicons;font-weight:400;font-style:normal;src:${woff2Match[0]}format("woff2")}`
    }
    return match
  })

  writeFileSync(cssPath, css, 'utf8')
  console.log(`  ✓ cleaned boxicons font references from ${cssFile}`)
}

// ── 3. emoji 原始 PNG/JPG 清理 ──
if (!existsSync(emojisDir)) {
  console.log('  dist/emojis/ 不存在，跳过 emoji 清理')
  process.exit(0)
}

const ORIGINAL_EXTS = new Set(['.png', '.jpg', '.jpeg'])
let emojiDeleted = 0
let emojiBytes = 0

function cleanEmojiDir(dir) {
  for (const entry of readdirSync(dir)) {
    const fullPath = join(dir, entry)
    const stat = statSync(fullPath)
    if (stat.isDirectory()) {
      cleanEmojiDir(fullPath)
      continue
    }
    const ext = extname(entry).toLowerCase()
    if (ORIGINAL_EXTS.has(ext)) {
      unlinkSync(fullPath)
      emojiDeleted++
      emojiBytes += stat.size
      console.log(`  ✗ emoji: ${fullPath.replace(emojisDir, '')}`)
    }
  }
}

cleanEmojiDir(emojisDir)
const savedKB = (emojiBytes / 1024).toFixed(0)
console.log(`  emoji 原始图片清理完成: 删除 ${emojiDeleted} 个文件 (节省 ${savedKB} KB)`)
