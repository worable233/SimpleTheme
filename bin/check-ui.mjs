#!/usr/bin/env node
/**
 * bin/check-ui.mjs — 组件库契约合规检查
 *
 * 把 src/ui/README.md 里的"铁律"变成可执行断言，避免规则只写在文档里慢慢腐烂。
 *
 *   node bin/check-ui.mjs          # 检查，有问题则 exit 1
 *   node bin/check-ui.mjs --soft   # 只报告不失败（用于渐进迁移期）
 *
 * 检查项：
 *   [E1] <script setup lang="ts">
 *   [E2] defineOptions({ name: 'StXxx' }) 且与文件名一致
 *   [E3] <style scoped>
 *   [E4] 根元素 class 含 st-<kebab-name>
 *   [E5] 无硬编码颜色字面量（#hex / rgb() / hsl()）
 *   [E6] 无遗留 .xh-* 类名
 *   [W1] 模板中疑似 Tailwind 工具类
 *   [W2] v-bind="$attrs" 无条件透传（class 后门）
 */

import { readFileSync, readdirSync, existsSync } from 'node:fs'
import { join, dirname, basename } from 'node:path'
import { fileURLToPath } from 'node:url'

const ROOT = join(dirname(fileURLToPath(import.meta.url)), '..')
const UI_DIR = join(ROOT, 'src/ui')
const COMPONENTS_DIR = join(UI_DIR, 'components')

/** 允许出现颜色字面量的文件及理由（新增需在这里显式登记）。 */
const COLOR_ALLOWLIST = new Map([
  ['StColorPicker.vue', '内置默认色板本身就是一组数据，不是样式取值'],
])

/** 疑似 Tailwind 工具类：命中即提示人工确认。 */
const TW_PATTERNS = [
  /^(?:flex|grid|block|inline|inline-flex|inline-block|hidden|contents|table)$/,
  /^(?:absolute|relative|fixed|sticky|static)$/,
  /^(?:items|justify|content|self|place)-/,
  /^(?:p|px|py|pt|pb|pl|pr|m|mx|my|mt|mb|ml|mr|w|h|min-w|min-h|max-w|max-h|gap|space-[xy])-/,
  /^(?:text|bg|border|ring|from|via|to|fill|stroke|shadow|rounded|divide|outline)-(?!\[)/,
  /^(?:sm|md|lg|xl|2xl):/,
  /^(?:hover|focus|active|disabled|group-hover):/,
]

const KEBAB = /([a-z0-9])([A-Z])/g

function toKebab(name) {
  return name.replace(KEBAB, '$1-$2').toLowerCase()
}

/** 取静态 class="..." 的字面量，用于 Tailwind 嗅探。
 *
 * 刻意只匹配不带冒号的静态 class：`class="flex items-center gap-2"` 这种
 * 手写工具类才是要抓的目标。:class 绑定是 JS 表达式，里面混着变量名、
 * 对象键、模板字符串，无法可靠分词（例如 { 'st-tabs--block': block }
 * 里的 block 是变量而非类名），强行解析只会制造误报。 */
function extractClassLiterals(source) {
  const literals = []
  const re = /(?<![:\w-])class\s*=\s*"([^"]*)"/g
  let m
  while ((m = re.exec(source)) !== null) literals.push(m[1])
  return literals
}

function stripComments(source) {
  return source.replace(/<!--[\s\S]*?-->/g, '').replace(/\/\*[\s\S]*?\*\//g, '')
}

function checkFile(file) {
  const raw = readFileSync(join(COMPONENTS_DIR, file), 'utf8')
  const src = stripComments(raw)
  const errors = []
  const warnings = []

  const name = basename(file, '.vue')
  // StNumberInput → st-number-input（前缀 St 已含在根 class 里，不能重复拼接）
  const kebab = `st-${toKebab(name.replace(/^St/, ''))}`

  // [E1] / [E2]
  // 注意：SFC 允许出现多个 script 块（StTabs / StCollapse 用独立的
  // 普通 <script> 放共享 context 类型与 InjectionKey），所以要扫全部块。
  const scriptBlocks = [...src.matchAll(/<script[^>]*>([\s\S]*?)<\/script>/g)].map((m) => m[1])
  if (!/<script\s+setup\s+lang="ts">/.test(src)) {
    errors.push('E1 缺少 <script setup lang="ts">')
  }
  if (scriptBlocks.length) {
    const declared = scriptBlocks.join('\n').match(/defineOptions\s*\(\s*\{\s*name:\s*['"]([\w-]+)['"]/)
    if (!declared) errors.push("E2 缺少 defineOptions({ name: '...' })")
    else if (declared[1] !== name) errors.push(`E2 name '${declared[1]}' 与文件名 '${name}' 不一致`)
  } else {
    errors.push('E2 找不到 script 块')
  }

  // [E3]
  if (!/<style\s+scoped>/.test(src)) errors.push('E3 缺少 <style scoped>')
  if (/<style(?![^>]*\bscoped\b)/.test(src)) errors.push('E3 存在非 scoped 的 style 块')

  // [E4]
  // class 里的实际写法是 st-number-input，kebab 已含 st- 前缀
  if (!src.includes(kebab)) {
    errors.push(`E4 未出现根 class '${kebab}'`)
  }

  // [E5]
  if (!COLOR_ALLOWLIST.has(file)) {
    const colorRe = /#[0-9a-fA-F]{3,8}\b|\brgba?\s*\(|\bhsla?\s*\(/g
    const hits = []
    for (const cm of src.matchAll(colorRe)) {
      const line = src.slice(0, cm.index).split('\n').length
      hits.push(`L${line}:${cm[0]}`)
    }
    if (hits.length) errors.push(`E5 硬编码颜色 ${hits.slice(0, 5).join(' ')}${hits.length > 5 ? ` (+${hits.length - 5})` : ''}`)
  }

  // [E6]
  const xh = [...src.matchAll(/xh-[a-z0-9-]+/g)].map((x) => x[0])
  if (xh.length) errors.push(`E6 残留 xh-* 类名 ${[...new Set(xh)].slice(0, 5).join(' ')}`)

  // [W1]
  // 只看"纯 class 字面量"里的 token。:class 的对象/数组语法里会混进
  // 表达式片段（如 { 'st-tabs--block': block } 中的 block），
  // 含引号/冒号/括号/逗号的 token 一律跳过，否则误报。
  const twHits = new Set()
  for (const lit of extractClassLiterals(src)) {
    for (const token of lit.split(/\s+/)) {
      if (!token || /['":{}().,=[\]$]/.test(token)) continue
      const t = token.replace(/^!/, '')
      if (!t || t.startsWith('st-')) continue
      if (TW_PATTERNS.some((re) => re.test(t))) twHits.add(t)
    }
  }
  if (twHits.size) warnings.push(`W1 疑似 Tailwind 工具类: ${[...twHits].slice(0, 8).join(' ')}`)

  // [W2]
  if (/v-bind="\$attrs"/.test(src)) warnings.push('W2 无条件 $attrs 透传，class 后门')

  return { file, errors, warnings }
}

function main() {
  const soft = process.argv.includes('--soft')

  if (!existsSync(COMPONENTS_DIR)) {
    console.error(`找不到 ${COMPONENTS_DIR}`)
    process.exit(1)
  }

  const files = readdirSync(COMPONENTS_DIR)
    .filter((f) => f.endsWith('.vue'))
    .sort()

  const results = files.map(checkFile)
  let errorCount = 0
  let warnCount = 0

  for (const r of results) {
    if (!r.errors.length && !r.warnings.length) continue
    console.log(`\n${r.file}`)
    for (const e of r.errors) {
      console.log(`  ✗ ${e}`)
      errorCount++
    }
    for (const w of r.warnings) {
      console.log(`  ! ${w}`)
      warnCount++
    }
  }

  // 全局扫描：src 下任何地方都不该再有 xh-*
  const xhGrep = []
  const walk = (dir) => {
    for (const entry of readdirSync(dir, { withFileTypes: true })) {
      const p = join(dir, entry.name)
      if (entry.isDirectory()) walk(p)
      else if (/\.(vue|css|ts|scss)$/.test(entry.name)) {
        const text = readFileSync(p, 'utf8')
        const n = (text.match(/xh-[a-z0-9-]+/g) || []).length
        if (n) xhGrep.push(`${p.replace(ROOT + '/', '')}: ${n}`)
      }
    }
  }
  walk(join(ROOT, 'src'))

  console.log(`\n${'─'.repeat(56)}`)
  console.log(`组件数 ${files.length} · 错误 ${errorCount} · 警告 ${warnCount}`)
  if (xhGrep.length) {
    console.log(`\n遗留 .xh-* 分布（共 ${xhGrep.reduce((a, s) => a + +s.split(': ')[1], 0)} 处）:`)
    for (const line of xhGrep) console.log(`  ${line}`)
  } else {
    console.log('遗留 .xh-* 分布: 无 ✓')
  }

  if (errorCount > 0 && !soft) process.exit(1)
}

main()
