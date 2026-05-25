/**
 * 压缩 emoji 图片为 WebP
 * 扫描 emojis/ 和 public/emojis/ 下的 PNG/JPG，使用 sharp 转为 WebP
 * 转换成功后删除原始文件
 * 运行: node bin/compress-emojis.mjs
 */
import { existsSync, readdirSync, statSync, unlinkSync } from 'node:fs'
import { fileURLToPath } from 'node:url'
import { dirname, extname, join, resolve } from 'node:path'
import sharp from 'sharp'

const __dirname = dirname(fileURLToPath(import.meta.url))
const root = resolve(__dirname, '..')

const SOURCE_DIRS = [
  join(root, 'emojis'),
  join(root, 'public', 'emojis'),
]

const VALID_EXT = new Set(['.png', '.jpg', '.jpeg'])

async function compressDir(dirPath) {
  if (!existsSync(dirPath)) return 0

  let count = 0
  const entries = readdirSync(dirPath)

  for (const entry of entries) {
    const fullPath = join(dirPath, entry)
    const stat = statSync(fullPath)

    if (stat.isDirectory()) {
      count += await compressDir(fullPath)
      continue
    }

    const ext = extname(entry).toLowerCase()
    if (!VALID_EXT.has(ext)) continue

    const nameWOExt = entry.slice(0, -ext.length)
    const webpPath = join(dirPath, `${nameWOExt}.webp`)

    // 跳过已存在的 WebP（非覆盖）
    if (existsSync(webpPath) && statSync(webpPath).mtimeMs >= stat.mtimeMs) {
      // WebP 已是最新，删除原始文件
      unlinkSync(fullPath)
      continue
    }

    try {
      await sharp(fullPath)
        .webp({ quality: 75, effort: 4 })
        .toFile(webpPath)

      const origKB = (stat.size / 1024).toFixed(1)
      const newSize = statSync(webpPath).size
      const newKB = (newSize / 1024).toFixed(1)
      const saved = ((1 - newSize / stat.size) * 100).toFixed(0)
      console.log(`  ✓ ${join(dirPath.split('emojis')[1] || '', entry)} → ${newKB}KB (省 ${saved}%)`)

      // 转换成功，删除原始文件
      unlinkSync(fullPath)
      count++
    } catch (err) {
      console.error(`  ✗ ${entry}: ${err.message}`)
    }
  }

  return count
}

async function main() {
  console.log('--- 压缩 emoji 图片为 WebP ---')
  let total = 0
  for (const dir of SOURCE_DIRS) {
    if (existsSync(dir)) {
      const n = await compressDir(dir)
      total += n
    }
  }
  console.log(`\n完成: 压缩 ${total} 个文件`)
}

main()
