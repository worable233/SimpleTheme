/**
 * 构建后清理：删除 dist/emojis/ 中的原始 PNG/JPG（已转为 WebP）。
 * 运行: node bin/cleanup-dist.mjs
 */
import { existsSync, readdirSync, statSync, unlinkSync } from 'node:fs'
import { extname, join, resolve } from 'node:path'
import { fileURLToPath } from 'node:url'

const __dirname = fileURLToPath(new URL('.', import.meta.url))
const root = resolve(__dirname, '..')
const emojisDir = join(root, 'dist', 'emojis')

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
