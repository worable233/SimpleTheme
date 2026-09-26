#!/usr/bin/env node
/**
 * bin/check-ui-business.mjs — 业务层「组件库优先」合规检查
 *
 * 补上 bin/check-ui.mjs 的缺口：那个脚本只看 src/ui/ 自己，管不到业务层。
 * 本脚本扫描 src/{components,views,admin}，对裸原生控件与 Tailwind 工具类
 * 做**登记制**检查：出现即报错，除非在下面的白名单里显式登记了理由。
 *
 *   node bin/check-ui-business.mjs          # 检查，有问题则 exit 1
 *   node bin/check-ui-business.mjs --soft   # 只报告不失败
 *
 * 检查项：
 *   [B1] 裸 <button>（应优先用 StButton）
 *   [B2] 裸 <input> / <textarea> / <select>（应优先用 StInput / StTextarea / StSelect…）
 *   [B3] 静态 class="..." 里的 Tailwind 工具类
 *   [B4] 手写浮层类名（*tooltip* / *mask* / *overlay* / *popover*，应优先用
 *        StTooltip / StModal / StPopover）
 *   [B5] 业务文件里直接写 role="dialog" / aria-modal（应交给 StModal）
 *   [B6] 手写内联 <svg> 图标（应优先用 AppIcon / StIcon）
 *
 * 为什么是登记制而不是"一刀切禁止"：
 *   README 已写明「何时不该用 StButton」（整块卡片即按钮、共享全局 CSS 的
 *   固定格、导航外壳等）。这些保留是**有意**的，但理由必须留在代码里，
 *   而不是散在 commit message。白名单每一条都要写清楚为什么。
 */

import { readFileSync, readdirSync, existsSync } from 'node:fs'
import { join, dirname, relative } from 'node:path'
import { fileURLToPath } from 'node:url'

const ROOT = join(dirname(fileURLToPath(import.meta.url)), '..')
const SCAN_DIRS = ['src/components', 'src/views', 'src/admin', 'src/blocks']
/** src 根目录下的业务壳组件（App.vue 等）单独登记，避免只扫子目录而漏掉页面骨架。 */
const SCAN_ROOT_FILES = ['src/App.vue']

/**
 * 裸 <button> 白名单。
 * key = 相对仓库根的路径，value = { lines: Set<number>, reason: string }
 * line 用 1-based 源码行号；只在**关键子串**命中时才算豁免。
 */
const RAW_BUTTON_ALLOW = new Map([
  [
    'src/components/ModalCloseButton.vue',
    '全局统一关闭按钮：桌面 ESC 键帽 / 触屏圆形 × 两态切换 + 自定义投影，形态不是 StButton 能表达的',
  ],
  [
    'src/components/sidebar/SidebarNav.vue',
    '菜单项折叠开关：与同级 RouterLink 脚手架共用图标+标题+chevron 布局，且由全局 sidebar.css 驱动',
  ],
  [
    'src/components/sidebar/SidebarMobileHeader.vue',
    '移动顶栏阅读模式标题：居中布局 + 阅读标题/站点名 Transition 切换，非单一内容按钮',
  ],
  [
    'src/components/archive/TimelineCard.vue',
    '整块卡片即按钮：依赖 hover:-translate-y/scale 与 12 格栅格几何，迁 StButton 会丢失布局',
  ],
  [
    'src/components/archive/CategoryCard.vue',
    '卡片头部按钮：内部 justify-between（左标题+右计数），StButton 内容会被塞进 inline-block 容器',
  ],
  [
    'src/components/EmojiPicker.vue',
    '表情面板单元格：36×36 固定格 + 选中下划线由共享全局 CSS 驱动，迁组件会孤儿化那些规则',
  ],
  [
    'src/components/DebugPanel.vue',
    '调试浮层专属 UI：整块面板几何 + 路由列表行布局，仅开发态使用',
  ],
  [
    'src/components/CommentForm.vue',
    '业务 scoped CSS 深度定制的提交/取消等按钮，外观由 .comments-form__* 规则驱动',
  ],
  [
    'src/admin/components/UserDropdown.vue',
    '与同级 <a> 菜单行共用 .user-dropdown__row 布局，需图标+文字组合，非独立按钮',
  ],
])

/** 裸输入类控件白名单（当前 None：CommentForm 已全部迁移到 St*）。 */
const RAW_INPUT_ALLOW = new Map([
  [
    'src/components/CommentForm.vue',
    'altcha 自定义元素与 contenteditable 编辑器，非标准表单控件',
  ],
])

/** Tailwind 工具类白名单（登记制；当前为空：页面级骨架也已收敛为 scoped CSS）。 */
const TW_ALLOW = new Map([])

/**
 * 手写浮层类名白名单（[B4]）。
 * 命中条件：静态 class 里出现 tooltip / mask / overlay / popover 子串。
 * 这类名字几乎总是自建浮层，应改用 StTooltip / StModal / StPopover；
 * 确需保留的在这里登记理由。
 */
const FLOATING_CLASS_ALLOW = new Map([
  [
    'src/components/CommentForm.vue',
    '移动端评论向导 wizard-mask：多步表单 + 方向滑动切换，非 StModal 能表达的一次性流程壳',
  ],
  [
    'src/components/TocWidget.vue',
    '目录抽屉 toc-drawer-mask：文章内浮动目录的遮罩，随 TOC 状态而非独立对话框',
  ],
])

/**
 * role="dialog" / aria-modal 白名单（[B5]）。
 * 对话框语义应由 StModal 内部 reka-ui 提供；业务文件直接写说明又在自建外壳。
 */
const DIALOG_ROLE_ALLOW = new Map([])

/**
 * 手写内联 <svg> 白名单（[B6]）。
 * 常规图标应走 AppIcon / StIcon（统一 Tabler 图标表）；保留的都是非图标形状。
 */
const INLINE_SVG_ALLOW = new Map([
  [
    'src/components/ReadingProgress.vue',
    '环形进度条：直径与 dashoffset 由进度实时驱动，非固定图标形状',
  ],
  [
    'src/components/DebugPanel.vue',
    '开发态调试浮层专属图形，仅开发构建加载',
  ],
  [
    'src/admin/components/AdminSidebar.vue',
    '无图标时的兜底占位圆，替代 dashicons 缺失场景',
  ],
])

/** 疑似 Tailwind 工具类（与 check-ui.mjs 保持一致的判定）。 */
const TW_PATTERNS = [
  /^(?:flex|grid|block|inline|inline-flex|inline-block|hidden|contents|table)$/,
  /^(?:absolute|relative|fixed|sticky|static)$/,
  /^(?:items|justify|self|place-content|place-items|place-self)-/,
  // content- 是语义类名的高频前缀（content-area / content-view），只在带明确
  // Tailwind 取值时才算工具类（content-center / content-between / content-around）。
  /^content-(?:center|between|around|evenly|start|end|baseline|stretch|normal)$/,
  /^(?:p|px|py|pt|pb|pl|pr|m|mx|my|mt|mb|ml|mr|w|h|min-w|min-h|max-w|max-h|gap|space-[xy])-/,
  /^(?:text|bg|border|ring|from|via|to|fill|stroke|shadow|rounded|divide|outline)-(?!\[)/,
  /^(?:sm|md|lg|xl|2xl):/,
  /^(?:hover|focus|active|disabled|group-hover):/,
]

function stripComments(source) {
  return source.replace(/<!--[\s\S]*?-->/g, '').replace(/\/\*[\s\S]*?\*\//g, '').replace(/\/\/[^\n]*/g, '')
}

/** 取静态 class="..." 的字面量。 */
function classLiterals(source) {
  const out = []
  const re = /(?<![:\w-])class\s*=\s*"([^"]*)"/g
  let m
  while ((m = re.exec(source)) !== null) out.push(m[1])
  return out
}

function walk(dir, out = []) {
  for (const entry of readdirSync(dir, { withFileTypes: true })) {
    const p = join(dir, entry.name)
    if (entry.isDirectory()) walk(p, out)
    else if (entry.name.endsWith('.vue')) out.push(p)
  }
  return out
}

function checkFile(abs) {
  const rel = relative(ROOT, abs)
  const raw = readFileSync(abs, 'utf8')
  const src = stripComments(raw)
  const errors = []

  // [B1] 裸 <button>：跳过 <StButton
  const btnLines = []
  src.split('\n').forEach((line, i) => {
    if (/<button[\s>]/.test(line)) btnLines.push(i + 1)
  })
  if (btnLines.length && !RAW_BUTTON_ALLOW.has(rel)) {
    errors.push(`B1 裸 <button> ×${btnLines.length}（L${btnLines.slice(0, 6).join(',')}）→ 优先用 StButton，或在白名单登记理由`)
  }

  // [B2] 裸输入控件
  const inputLines = []
  src.split('\n').forEach((line, i) => {
    if (/<(input|textarea|select)[\s>]/.test(line)) inputLines.push(i + 1)
  })
  if (inputLines.length && !RAW_INPUT_ALLOW.has(rel)) {
    errors.push(`B2 裸输入控件 ×${inputLines.length}（L${inputLines.slice(0, 6).join(',')}）→ 优先用 St*，或在白名单登记理由`)
  }

  // [B3] Tailwind 工具类
  if (!TW_ALLOW.has(rel)) {
    const hits = new Set()
    for (const lit of classLiterals(src)) {
      for (const token of lit.split(/\s+/)) {
        if (!token || /['":{}().,=[\]$]/.test(token)) continue
        const t = token.replace(/^!/, '')
        if (!t || t.startsWith('st-')) continue
        if (TW_PATTERNS.some((re) => re.test(t))) hits.add(t)
      }
    }
    if (hits.size) {
      errors.push(`B3 Tailwind 工具类: ${[...hits].slice(0, 8).join(' ')}${hits.size > 8 ? ` (+${hits.size - 8})` : ''}`)
    }
  }

  // [B4] 手写浮层类名
  if (!FLOATING_CLASS_ALLOW.has(rel)) {
    const hits = new Set()
    for (const lit of classLiterals(src)) {
      for (const token of lit.split(/\s+/)) {
        if (/tooltip|mask|overlay|popover/i.test(token)) hits.add(token)
      }
    }
    if (hits.size) {
      errors.push(
        `B4 手写浮层类名: ${[...hits].slice(0, 6).join(' ')} → 优先用 StTooltip / StModal / StPopover，或在白名单登记理由`,
      )
    }
  }

  // [B5] 对话框语义（role="dialog" / aria-modal）
  if (!DIALOG_ROLE_ALLOW.has(rel)) {
    const lines = []
    src.split('\n').forEach((line, i) => {
      if (/role\s*=\s*"dialog"|aria-modal\s*=/.test(line)) lines.push(i + 1)
    })
    if (lines.length) {
      errors.push(`B5 手写对话框语义 ×${lines.length}（L${lines.slice(0, 6).join(',')}）→ 交给 StModal，或在白名单登记理由`)
    }
  }

  // [B6] 手写内联 <svg> 图标
  if (!INLINE_SVG_ALLOW.has(rel)) {
    const lines = []
    src.split('\n').forEach((line, i) => {
      if (/<svg[\s>]/.test(line)) lines.push(i + 1)
    })
    if (lines.length) {
      errors.push(
        `B6 手写内联 <svg> ×${lines.length}（L${lines.slice(0, 6).join(',')}）→ 优先用 AppIcon / StIcon，或在白名单登记理由`,
      )
    }
  }

  return { rel, errors }
}

function main() {
  const soft = process.argv.includes('--soft')

  const files = []
  for (const d of SCAN_DIRS) {
    const abs = join(ROOT, d)
    if (existsSync(abs)) walk(abs, files)
  }
  for (const f of SCAN_ROOT_FILES) {
    const abs = join(ROOT, f)
    if (existsSync(abs)) files.push(abs)
  }

  let errorCount = 0
  for (const abs of files.sort()) {
    const { rel, errors } = checkFile(abs)
    if (!errors.length) continue
    console.log(`\n${rel}`)
    for (const e of errors) {
      console.log(`  ✗ ${e}`)
      errorCount++
    }
  }

  console.log(`\n${'─'.repeat(56)}`)
  console.log(`扫描 ${files.length} 个业务组件 · 错误 ${errorCount}`)
  console.log(`裸 button 白名单 ${RAW_BUTTON_ALLOW.size} · 裸输入白名单 ${RAW_INPUT_ALLOW.size} · 工具类白名单 ${TW_ALLOW.size}`)
  console.log(`手写浮层白名单 ${FLOATING_CLASS_ALLOW.size} · 对话框语义白名单 ${DIALOG_ROLE_ALLOW.size} · 内联 svg 白名单 ${INLINE_SVG_ALLOW.size}`)

  if (errorCount > 0 && !soft) process.exit(1)
}

main()
