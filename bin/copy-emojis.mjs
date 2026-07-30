/**
 * 将 emojis/ 镜像到 public/emojis/（供 Vite dev server 使用）
 * 运行: node bin/copy-emojis.mjs
 */
import { cpSync, rmSync } from 'node:fs'
import { fileURLToPath } from 'node:url'
import { dirname, join, resolve } from 'node:path'

const __dirname = dirname(fileURLToPath(import.meta.url))
const root = resolve(__dirname, '..')

const srcDir = join(root, 'emojis')
const publicDir = join(root, 'public', 'emojis')

rmSync(publicDir, { recursive: true, force: true })
cpSync(srcDir, publicDir, { recursive: true })
console.log('  ✓ 已镜像 emojis/ → public/emojis/')
