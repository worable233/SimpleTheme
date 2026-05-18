/**
 * 从 CDN 下载 Bilibili / Tieba 表情 PNG 到 emojis/
 * 运行: node bin/download-emojis.mjs
 */
import { existsSync, mkdirSync, readdirSync, rmSync, writeFileSync } from 'node:fs'
import { fileURLToPath } from 'node:url'
import { dirname, resolve, join } from 'node:path'

const __dirname = dirname(fileURLToPath(import.meta.url))
const root = resolve(__dirname, '..')

const CDN_BASE = 'https://s.nmxc.ltd/sakurairo_vision/@3.0/smilies/'

const bilibiliNames = [
  'baiyan', 'bishi', 'bizui', 'chan', 'dai', 'daku', 'dalao', 'dalian',
  'dianzan', 'doge', 'facai', 'fanu', 'ganga', 'guilian', 'guzhang',
  'haixiu', 'heirenwenhao', 'huaixiao', 'jingxia', 'keai', 'koubizi',
  'kun', 'lengmo', 'liubixue', 'liuhan', 'liulei', 'miantian',
  'mudengkoudai', 'nanguo', 'outu', 'qinqin', 'se', 'shengbing',
  'shengqi', 'shuizhao', 'sikao', 'tiaokan', 'tiaopi', 'touxiao',
  'tuxue', 'weiqu', 'weixiao', 'wunai', 'xiaoku', 'xieyanxiao',
  'yiwen', 'yun', 'zaijian', 'zhoumei', 'zhuakuang',
]

const tiebaNames = [
  'good', 'han', 'spray', 'Grievance', 'shui', 'reluctantly', 'anger',
  'tongue', 'se', 'haha', 'rmb', 'doubt', 'tear', 'surprised2', 'Happy',
  'ku', 'surprised', 'theblackline', 'smilingeyes', 'spit', 'huaji',
  'bbd', 'hu', 'shame', 'naive', 'rbq', 'britan', 'aa', 'niconiconi',
  'niconiconi_t', 'niconiconit', 'awesome',
]

const outDir = join(root, 'emojis')

async function download(url, dest) {
  const resp = await fetch(url, { signal: AbortSignal.timeout(15000) })
  if (!resp.ok) throw new Error(`HTTP ${resp.status}`)
  const buf = Buffer.from(await resp.arrayBuffer())
  writeFileSync(dest, buf)
}

async function main() {
  mkdirSync(join(outDir, 'bili'), { recursive: true })
  mkdirSync(join(outDir, 'tieba'), { recursive: true })

  let downloaded = 0
  let failed = 0

  console.log('  下载 Bilibili 表情...')
  for (const name of bilibiliNames) {
    const url = `${CDN_BASE}bilipng/emoji_${name}.png`
    const dest = join(outDir, 'bili', `emoji_${name}.png`)
    try {
      await download(url, dest)
      console.log(`  ✓ bili/${name}`)
      downloaded++
    } catch (e) {
      console.log(`  ✗ bili/${name} (${e.message})`)
      failed++
    }
  }

  console.log('  下载 Tieba 表情...')
  for (const name of tiebaNames) {
    const url = `${CDN_BASE}tiebapng/icon_${name}.png`
    const dest = join(outDir, 'tieba', `icon_${name}.png`)
    try {
      await download(url, dest)
      console.log(`  ✓ tieba/${name}`)
      downloaded++
    } catch (e) {
      console.log(`  ✗ tieba/${name} (${e.message})`)
      failed++
    }
  }

  console.log(`\n完成: 下载 ${downloaded} 个文件${failed ? `, ${failed} 个失败` : ''}`)
  if (failed > 0) process.exit(1)
}

main()
