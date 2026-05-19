#!/usr/bin/env node
/**
 * Copy minified PrismJS files (core + needed languages) from node_modules
 * to dist/prism/ so they can be enqueued as regular <script> tags (not ES modules).
 *
 * @package SimpleTheme
 */
import { copyFileSync, existsSync, mkdirSync, readFileSync, writeFileSync } from 'fs'
import { resolve, dirname } from 'path'
import { fileURLToPath } from 'url'

const __dirname = dirname(fileURLToPath(import.meta.url))
const ROOT = resolve(__dirname, '..')
const PRISM_SRC = resolve(ROOT, 'node_modules/prismjs')
const PRISM_DST = resolve(ROOT, 'dist/prism')

const NEEDED = [
  'prism-core',
  'prism-clike',
  'prism-markup',
  'prism-css',
  'prism-javascript',
  'prism-typescript',
  'prism-bash',
  'prism-json',
  'prism-python',
  'prism-sql',
  'prism-yaml',
  'prism-markdown',
  'prism-markup-templating',
  'prism-php',
]

if (!existsSync(PRISM_DST)) {
  mkdirSync(PRISM_DST, { recursive: true })
}

for (const name of NEEDED) {
  const src = resolve(PRISM_SRC, `components/${name}.min.js`)
  const dst = resolve(PRISM_DST, `${name}.min.js`)
  if (!existsSync(src)) {
    console.error(`[copy-prism] WARN: ${src} not found, skipping`)
    continue
  }
  copyFileSync(src, dst)
  console.log(`[copy-prism] ${name}.min.js`)
}

console.log('[copy-prism] Done')
