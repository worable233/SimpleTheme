import { watch, nextTick, onMounted, onUnmounted, type Ref } from 'vue'
import { useToc } from '@/composables/useToc'
import { isExternalUrl, isSafeNavigationUrl } from '@/lib/theme-config'
import { inlineProseIcons } from '@/lib/prose-icons'
import { defineMorphIcon, type MorphIconElement } from 'morphicons/element'
import { ICON_MORPH_NODES, ICON_NODES } from '@/lib/tabler-icons.generated'
// Prism is loaded as a regular <script> by WordPress (not an ES module import).
// It's available globally via window.Prism.
declare const Prism: { highlightElement: (el: HTMLElement) => void } | undefined
import { Fancybox } from '@fancyapps/ui'
import '@fancyapps/ui/dist/fancybox/fancybox.css'
import { useToast } from '@/ui'

const toast = useToast()

let fancyboxBound = false

const COPY_LINK_ICON = '#'

const CLIPBOARD_ICON = `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>`

const CHECK_ICON = `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>`

/**
 * 激活正文里的 core/accordion 区块。
 * WP 的 Interactivity API 运行时只在服务端直出的 DOM 上 hydrate；
 * SPA 用 v-html 重新渲染后交互失效，这里用原生事件重新实现展开/收起。
 */
function activateAccordions(container: Element) {
  const items = container.querySelectorAll<HTMLElement>('.wp-block-accordion-item')
  for (const item of items) {
    const button = item.querySelector<HTMLButtonElement>('.wp-block-accordion-heading__toggle')
    const panel = item.querySelector<HTMLElement>('.wp-block-accordion-panel')
    if (!button || !panel || button.dataset.stEnhanced) continue
    button.dataset.stEnhanced = '1'

    const setOpen = (open: boolean) => {
      item.classList.toggle('is-open', open)
      button.setAttribute('aria-expanded', String(open))
      if (open) panel.removeAttribute('inert')
      else panel.setAttribute('inert', '')
      const icon = button.querySelector('.wp-block-accordion-heading__toggle-icon')
      if (icon) icon.textContent = open ? '−' : '+'
    }
    setOpen(button.getAttribute('aria-expanded') === 'true')
    button.addEventListener('click', () => {
      setOpen(button.getAttribute('aria-expanded') !== 'true')
    })
  }
}

/**
 * 拦截正文里的 core/search 表单提交：
 * WP 原生提交会整页跳转 /?s=xxx（SPA 无搜索结果页），
 * 改为打开主题自带的搜索弹窗并预填关键词。
 */
function interceptSearchForms(container: Element) {
  const forms = container.querySelectorAll<HTMLFormElement>('form.wp-block-search')
  for (const form of forms) {
    if (form.dataset.stEnhanced) continue
    form.dataset.stEnhanced = '1'
    form.addEventListener('submit', (e) => {
      e.preventDefault()
      const input = form.querySelector<HTMLInputElement>('input[type="search"], .wp-block-search__input')
      window.dispatchEvent(new CustomEvent('st:open-search', { detail: input?.value || '' }))
    })
  }
}

// 正文音频播放器：播放/暂停、音量/静音、循环开关用 morphicons 的
// <morph-icon> 自定义元素做形变；快退/快进/下载用静态 Tabler SVG。
// 用自定义元素而非挂 Vue 应用——内容增强器是命令式 DOM，元素在
// disconnectedCallback 里自行销毁控制器，随正文一起被回收，不会泄漏。
defineMorphIcon()

const SVG_NS = 'http://www.w3.org/2000/svg'

type AudioMorphName =
  | 'player-play'
  | 'player-pause'
  | 'volume'
  | 'volume-off'
  | 'repeat'
  | 'repeat-off'

/** 建一个 `<morph-icon>`；实际尺寸由 CSS 覆盖（此处 16 仅兜底） */
function createMorphIcon(name: AudioMorphName): MorphIconElement {
  const el = document.createElement('morph-icon')
  el.setAttribute('size', '16')
  el.setAttribute('reduced-motion', 'user')
  el.setAttribute('spring', 'snappy')
  el.icon = ICON_MORPH_NODES[name]
  return el
}

/** 用 ICON_NODES 里的内部节点串建一个静态 Tabler SVG（inner 来自生成的可信常量） */
function createStaticIcon(name: string): SVGSVGElement {
  const svg = document.createElementNS(SVG_NS, 'svg')
  svg.setAttribute('viewBox', '0 0 24 24')
  svg.setAttribute('fill', 'none')
  svg.setAttribute('stroke', 'currentColor')
  svg.setAttribute('stroke-width', '2')
  svg.setAttribute('stroke-linecap', 'round')
  svg.setAttribute('stroke-linejoin', 'round')
  svg.setAttribute('aria-hidden', 'true')
  svg.innerHTML = ICON_NODES[name] || ''
  return svg
}

function formatAudioTime(sec: number): string {
  if (!isFinite(sec) || sec < 0) return '00:00'
  const m = Math.floor(sec / 60)
  const s = Math.floor(sec % 60)
  return `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`
}

/**
 * 把正文里的原生 <audio controls> 换掉，改成主题风格的播放条：
 * 顶部整宽细进度条（点击/拖动跳转）+ 左时间 + 中走带（快退 10s / 播放暂停 /
 * 快进 10s）+ 右工具（循环、静音、下载）。原 audio 隐藏作为音源。
 */
function enhanceAudioPlayers(container: Element) {
  const audios = container.querySelectorAll<HTMLAudioElement>('audio')
  for (const audio of audios) {
    if (audio.dataset.stEnhanced) continue
    audio.dataset.stEnhanced = '1'
    audio.removeAttribute('controls')
    audio.style.display = 'none'
    audio.preload = audio.preload || 'metadata'

    const ui = document.createElement('div')
    ui.className = 'st-audio'

    // ---- 顶部整宽进度条 ----
    const track = document.createElement('div')
    track.className = 'st-audio__track'
    const rail = document.createElement('div')
    rail.className = 'st-audio__rail'
    const fill = document.createElement('div')
    fill.className = 'st-audio__fill'
    rail.appendChild(fill)
    track.appendChild(rail)

    const inner = document.createElement('div')
    inner.className = 'st-audio__inner'

    // ---- 左：时间 ----
    const meta = document.createElement('div')
    meta.className = 'st-audio__meta'
    const time = document.createElement('span')
    time.className = 'st-audio__time'
    const cur = document.createElement('span')
    cur.className = 'st-audio__time-cur'
    cur.textContent = '00:00'
    const sep = document.createElement('span')
    sep.className = 'st-audio__time-sep'
    sep.textContent = '/'
    const total = document.createElement('span')
    total.className = 'st-audio__time-total'
    total.textContent = '00:00'
    time.append(cur, sep, total)
    meta.appendChild(time)

    // ---- 中：走带 ----
    const transport = document.createElement('div')
    transport.className = 'st-audio__transport'
    const backBtn = document.createElement('button')
    backBtn.type = 'button'
    backBtn.className = 'st-audio__icon-btn'
    backBtn.setAttribute('aria-label', '后退 10 秒')
    backBtn.appendChild(createStaticIcon('rewind-backward-10'))
    const playBtn = document.createElement('button')
    playBtn.type = 'button'
    playBtn.className = 'st-audio__btn'
    playBtn.setAttribute('aria-label', '播放')
    const playIcon = createMorphIcon('player-play')
    playBtn.appendChild(playIcon)
    const fwdBtn = document.createElement('button')
    fwdBtn.type = 'button'
    fwdBtn.className = 'st-audio__icon-btn'
    fwdBtn.setAttribute('aria-label', '前进 10 秒')
    fwdBtn.appendChild(createStaticIcon('rewind-forward-10'))
    transport.append(backBtn, playBtn, fwdBtn)

    // ---- 右：工具 ----
    const tools = document.createElement('div')
    tools.className = 'st-audio__tools'
    const loopBtn = document.createElement('button')
    loopBtn.type = 'button'
    loopBtn.className = 'st-audio__icon-btn'
    loopBtn.setAttribute('aria-label', '单曲循环')
    loopBtn.setAttribute('aria-pressed', 'false')
    const loopIcon = createMorphIcon('repeat-off')
    loopBtn.appendChild(loopIcon)
    const muteBtn = document.createElement('button')
    muteBtn.type = 'button'
    muteBtn.className = 'st-audio__icon-btn'
    muteBtn.setAttribute('aria-label', '静音')
    const muteIcon = createMorphIcon('volume')
    muteBtn.appendChild(muteIcon)
    const dlBtn = document.createElement('button')
    dlBtn.type = 'button'
    dlBtn.className = 'st-audio__icon-btn st-audio__dl'
    dlBtn.setAttribute('aria-label', '下载音频')
    dlBtn.appendChild(createStaticIcon('download'))
    tools.append(loopBtn, muteBtn, dlBtn)

    inner.append(meta, transport, tools)
    ui.append(track, inner)
    audio.after(ui)

    const renderAt = (t: number) => {
      const p = audio.duration ? (t / audio.duration) * 100 : 0
      cur.textContent = formatAudioTime(t)
      fill.style.width = `${p}%`
    }
    const syncTime = () => {
      total.textContent = formatAudioTime(audio.duration)
      renderAt(audio.currentTime)
    }
    const syncPlayState = () => {
      playIcon.icon = ICON_MORPH_NODES[audio.paused ? 'player-play' : 'player-pause']
      playBtn.setAttribute('aria-label', audio.paused ? '播放' : '暂停')
    }
    const syncLoop = () => {
      loopIcon.icon = ICON_MORPH_NODES[audio.loop ? 'repeat' : 'repeat-off']
      loopBtn.setAttribute('aria-pressed', String(audio.loop))
      loopBtn.classList.toggle('is-active', audio.loop)
    }
    const syncVolume = () => {
      const off = audio.muted || audio.volume === 0
      muteIcon.icon = ICON_MORPH_NODES[off ? 'volume-off' : 'volume']
      muteBtn.setAttribute('aria-label', off ? '取消静音' : '静音')
    }
    const seekTo = (clientX: number) => {
      if (!audio.duration) return
      const r = rail.getBoundingClientRect()
      const ratio = Math.min(1, Math.max(0, (clientX - r.left) / r.width))
      const t = ratio * audio.duration
      audio.currentTime = t
      // 乐观更新：服务器不支持 Range（本地 Studio）时也跟手，松手由 syncTime 校正
      renderAt(t)
    }

    playBtn.addEventListener('click', () => {
      if (audio.paused) void audio.play()
      else audio.pause()
    })
    backBtn.addEventListener('click', () => {
      audio.currentTime = Math.max(0, audio.currentTime - 10)
      syncTime()
    })
    fwdBtn.addEventListener('click', () => {
      const end = isFinite(audio.duration) ? audio.duration : audio.currentTime + 10
      audio.currentTime = Math.min(end, audio.currentTime + 10)
      syncTime()
    })
    loopBtn.addEventListener('click', () => {
      audio.loop = !audio.loop
      syncLoop()
    })
    muteBtn.addEventListener('click', () => {
      audio.muted = !audio.muted
      syncVolume()
    })
    dlBtn.addEventListener('click', () => {
      const src = audio.currentSrc || audio.src
      if (!src) return
      const a = document.createElement('a')
      a.href = src
      a.download = ''
      a.rel = 'noopener'
      document.body.appendChild(a)
      a.click()
      a.remove()
    })
    // 进度条：按下即跳转，按住可拖动
    track.addEventListener('pointerdown', (e) => {
      seekTo(e.clientX)
      // 合成事件/无效指针下 setPointerCapture 会抛 NotFoundError，忽略即可
      try {
        track.setPointerCapture(e.pointerId)
      } catch {
        /* noop */
      }
      const move = (ev: PointerEvent) => seekTo(ev.clientX)
      const up = () => {
        track.removeEventListener('pointermove', move)
        track.removeEventListener('pointerup', up)
        track.removeEventListener('pointercancel', up)
        // 松手校正到真实位置（seek 失败时收回乐观值）
        syncTime()
      }
      track.addEventListener('pointermove', move)
      track.addEventListener('pointerup', up)
      track.addEventListener('pointercancel', up)
    })
    audio.addEventListener('timeupdate', syncTime)
    audio.addEventListener('loadedmetadata', syncTime)
    audio.addEventListener('durationchange', syncTime)
    audio.addEventListener('play', syncPlayState)
    audio.addEventListener('pause', syncPlayState)
    audio.addEventListener('ended', syncPlayState)
    audio.addEventListener('volumechange', syncVolume)
    syncTime()
    syncPlayState()
    syncLoop()
    syncVolume()
  }
}

/** Wrap bare text paragraphs that look like code in <pre><code>. */
function wrapCodeParagraphs(container: Element) {
  // Target block elements that might contain code pasted as plain text
  const candidates = container.querySelectorAll<HTMLElement>('p, li, div')
  for (const el of candidates) {
    if (el.closest('pre, code, figure')) continue
    const text = el.textContent || ''
    if (text.length < 30) continue
    // Must have at least 2 lines
    const lines = text.split('\n')
    if (lines.length < 2) continue

    // Check for explicit code signatures in first meaningful line
    const first = lines.find((l) => l.trim())?.trim() || ''
    const isCode =
      /^<\?php/i.test(first) ||
      /^php\s*<\?php/i.test(first) ||
      /^(import |export |const |let |var |function|class |interface |trait |enum |def |#!|\/\/|\/\*)/.test(first) ||
      /^(public |private |protected |static )/.test(first)

    if (!isCode) continue

    // Only wrap if the element is mostly code (text doesn't mix prose & code)
    // If it has child elements (links, bold, etc.), skip — it's mixed content
    if (el.children.length > 0) continue

    const pre = document.createElement('pre')
    pre.className = 'wp-block-code auto-detected'
    const code = document.createElement('code')
    code.textContent = text
    pre.appendChild(code)
    el.replaceWith(pre)
  }
}

function addHeadingAnchors(container: Element) {
  const headings = container.querySelectorAll('h2, h3, h4')
  /** 已占用的 id，保证自动生成的锚点唯一（重复标题不会撞车）。 */
  const usedIds = new Set<string>(
    Array.from(container.querySelectorAll('[id]'))
      .map((el) => el.id)
      .filter(Boolean),
  )
  for (const heading of headings) {
    // Auto-generate ID if the heading doesn't have one
    let id = heading.getAttribute('id')
    if (!id) {
      const base = (heading.textContent || '')
        .toLowerCase()
        .replace(/[^\w\u4e00-\u9fa5]+/g, '-')
        .replace(/^-+|-+$/g, '')
      id = base || 'heading-' + Math.random().toString(36).slice(2, 8)
      // 去重：同名标题追加 -2 / -3 …，避免 getElementById 命中错误锚点
      if (usedIds.has(id)) {
        let n = 2
        while (usedIds.has(`${id}-${n}`)) n += 1
        id = `${id}-${n}`
      }
      heading.setAttribute('id', id)
    }
    usedIds.add(id)

    if (heading.querySelector('.heading-anchor-btn')) continue

    // Fumadocs-style: heading is a group for hover-reveal anchor
    heading.classList.add('fh', 'group/heading')

    // Wrap heading text in anchor link
    const textAnchor = document.createElement('a')
    textAnchor.href = `#${id}`
    textAnchor.setAttribute('data-card', '')
    while (heading.firstChild) {
      textAnchor.appendChild(heading.firstChild)
    }
    heading.appendChild(textAnchor)

    // Copy link button — hidden until heading hover
    const copyBtn = document.createElement('button')
    copyBtn.className = 'heading-anchor-btn'
    copyBtn.setAttribute('aria-label', 'Copy Anchor Link')
    copyBtn.innerHTML = COPY_LINK_ICON
    copyBtn.addEventListener('click', (e) => {
      e.preventDefault()
      e.stopPropagation()
      const url = `${window.location.href.split('#')[0]}#${id}`
      navigator.clipboard.writeText(url).catch(() => {})
	      toast.success('链接已复制到剪贴板')
      copyBtn.innerHTML = CHECK_ICON
      setTimeout(() => {
        copyBtn.innerHTML = COPY_LINK_ICON
      }, 2000)
    })

    heading.appendChild(copyBtn)
  }
}

/** Try to infer a Prism language from the first meaningful line of code. */
function sniffLanguage(text: string): string {
  const line = text.split('\n').find((l) => l.trim())?.trim() || ''
  if (/^<\?php/i.test(line)) return 'php'
  if (/^php\s*<\?php/i.test(line)) return 'php'
  if (/^(import |export |const |let |var |function|class |interface |trait |enum |def )/.test(line)) return 'javascript'
  if (/^(public |private |protected |static )/.test(line)) return 'php'
  if (/^(#!|\/\/|\/\*)/.test(line)) return 'bash'
  if (/^{|}\s*$/.test(line)) return 'json'
  return ''
}

/** Infer language for a <code> block, checking class then content. */
function detectLanguage(code: HTMLElement): string {
  // 1. Check existing language-* class on <code>
  const m = code.className.match(/(?:^|\s)language-(\w+)/)
  if (m?.[1]) return m[1]

  // 2. Check lang-* class on parent <pre>
  const pre = code.closest('pre')
  const pm = pre?.className.match(/lang(?:uage)?-(\w+)/)
  if (pm?.[1]) return pm[1]

  // 3. Sniff from content
  return sniffLanguage(code.textContent || '')
}

function wrapCodeBlockUI(pre: HTMLPreElement) {
  if (pre.closest('.code-block-wrapper')) return

  const code = pre.querySelector('code')
  if (!code) return

  const figure = document.createElement('figure')
  figure.className = 'code-block-wrapper'

  const filename = code.getAttribute('data-filename') || pre.getAttribute('data-filename')
  if (filename) {
    const header = document.createElement('div')
    header.className = 'code-block-header'
    const figcaption = document.createElement('figcaption')
    figcaption.textContent = filename
    header.appendChild(figcaption)
    figure.appendChild(header)
  }

  const body = document.createElement('div')
  body.className = 'code-block-body'

  pre.replaceWith(figure)
  figure.appendChild(body)
  body.appendChild(pre)

  const copyWrapper = document.createElement('div')
  copyWrapper.className = 'code-block-copy'
  const copyBtn = document.createElement('button')
  copyBtn.className = 'copy-btn'
  copyBtn.innerHTML = `${CLIPBOARD_ICON} Copy`
  copyBtn.addEventListener('click', () => {
    const text = code.textContent || ''
    navigator.clipboard.writeText(text).catch(() => {})
    toast.success('代码已复制到剪贴板')
    copyBtn.innerHTML = `${CHECK_ICON} Copied!`
    setTimeout(() => {
      copyBtn.innerHTML = `${CLIPBOARD_ICON} Copy`
    }, 2000)
  })
  copyWrapper.appendChild(copyBtn)
  figure.appendChild(copyWrapper)
}

function processCodeBlocks(container: Element) {
  const blocks = container.querySelectorAll<HTMLPreElement>('pre')
  let highlighted = 0
  let errors = 0

  for (const pre of blocks) {
    const code = pre.querySelector<HTMLElement>('code')
    if (!code) continue

    // Skip already-highlighted blocks
    if (code.querySelector('.token')) continue

    // Infer language
    const lang = detectLanguage(code)
    if (lang) {
      code.classList.add('language-' + lang)
    }

    // Highlight using Prism (loaded globally by WordPress, may be unavailable in dev)
    if (typeof Prism !== 'undefined') {
      try {
        Prism.highlightElement(code)
        highlighted++
      } catch (e) {
        errors++
        console.error('[useContentEnhancer] Prism highlight failed:', e)
      }
    }

    // Wrap in UI chrome
    wrapCodeBlockUI(pre)
  }

  container.setAttribute('data-prism-status', `highlighted=${highlighted} errors=${errors}`)
}

/** Remove unsafe links, then route HTTP(S) external links through /go. */
function transformExternalLinks(container: Element) {
  const links = container.querySelectorAll<HTMLAnchorElement>('a[href]')
  for (const link of links) {
    const href = link.getAttribute('href')
    if (!href) continue

    if (!isSafeNavigationUrl(href)) {
      link.removeAttribute('href')
      link.removeAttribute('target')
      link.removeAttribute('rel')
      link.setAttribute('aria-disabled', 'true')
      continue
    }

    let protocol = ''
    try {
      protocol = new URL(href, window.location.href).protocol.toLowerCase()
    } catch {
      link.removeAttribute('href')
      continue
    }

    if (
      href.startsWith('#') ||
      protocol === 'mailto:' ||
      protocol === 'tel:' ||
      link.hasAttribute('download')
    ) continue
    // Skip already-transformed or go-page links
    if (href.startsWith('/go?url=')) continue
    if (isExternalUrl(href)) {
      link.href = `/go?url=${encodeURIComponent(href)}`
    }
  }
}

function initFancyboxImages(container: Element) {
  // Wrap standalone <img> inside <a data-fancybox="article-gallery"> for Fancybox preview
  // Skip images already wrapped in a link
  const images = container.querySelectorAll<HTMLImageElement>('img')
  let groupIndex = 0

  for (const img of images) {
    if (img.closest('a')) continue
    // Skip already-processed images (already wrapped in data-fancybox)
    if (img.closest('[data-fancybox]')) continue
    // Skip tiny icons/avatars/emojis — check rendered size (CSS dimensions)
    if (img.offsetWidth > 0 && img.offsetHeight > 0 && img.offsetWidth < 48 && img.offsetHeight < 48) continue

    const src = img.getAttribute('src') || img.getAttribute('data-src') || ''
    if (!src || src.startsWith('data:')) continue

    let imageUrl: URL
    try {
      imageUrl = new URL(src, window.location.origin)
    } catch {
      continue
    }
    if (!['http:', 'https:'].includes(imageUrl.protocol)) continue

    const wrapper = document.createElement('a')
    // Normalize URL to ensure proper encoding of non-ASCII characters.
    wrapper.href = imageUrl.href
    wrapper.dataset.fancybox = 'article-gallery'
    const alt = img.getAttribute('alt') || ''
    if (alt) {
      wrapper.dataset.caption = alt
    }

    img.parentNode?.insertBefore(wrapper, img)
    wrapper.appendChild(img)
    groupIndex++
  }

  if (groupIndex > 0) {
    if (fancyboxBound) {
      Fancybox.unbind('[data-fancybox="article-gallery"]')
    }
    fancyboxBound = true
    Fancybox.bind('[data-fancybox="article-gallery"]', {
      Thumbs: { autoStart: false },
      Toolbar: {
        display: {
          left: ['infobar'],
          middle: [],
          right: ['zoomIn', 'zoomOut', 'toggle1to1', 'slideshow', 'fullscreen', 'thumbs', 'close'],
        },
      },
    } as Parameters<typeof Fancybox.bind>[2])
  }
}

export function useContentEnhancer(
  content?: Ref<string | null | undefined>,
  selector = '.prose-content',
) {
  const { clearToc } = useToc()
  let observer: MutationObserver | null = null
  let enhancing = false

  function enhance() {
    if (enhancing) return
    enhancing = true
    try {
      const container = document.querySelector(selector)
      if (!container) return
      // 正文图标字体 <i> → 内联 Tabler SVG（不再加载图标字体）
      inlineProseIcons(container)
      addHeadingAnchors(container)
      wrapCodeParagraphs(container)
      processCodeBlocks(container)
      // Route external links through /go redirect page
      transformExternalLinks(container)
      // core/accordion 交互（Interactivity API 在 v-html 下失效，自行接管）
      activateAccordions(container)
      // core/search 表单 → 主题搜索弹窗
      interceptSearchForms(container)
      // 原生 audio → 自定义播放器
      enhanceAudioPlayers(container)
      // Initialize Fancybox for article images
      initFancyboxImages(container)

      // Lazy load all images — content text renders immediately, images stream in
      container.querySelectorAll<HTMLImageElement>('img').forEach((img) => {
        if (!img.hasAttribute('loading')) img.loading = 'lazy'
      })
      // Sync TOC from the same container (headings now have IDs)
      const { setTocItems, extractToc } = useToc()
      setTocItems(extractToc(selector))
    } finally {
      enhancing = false
    }
  }

  if (content) {
    watch(
      content,
      (val) => {
        if (val) nextTick(() => enhance())
        else clearToc()
      },
      { immediate: false },
    )
  }

  onMounted(() => {
    if (!content || content.value) nextTick(() => enhance())

    const container = document.querySelector(selector)
    if (container) {
      observer = new MutationObserver(() => {
        if (
          container.querySelector('pre:not(.code-block-wrapper pre)') ||
          container.querySelector('h2[id]:not(:has(.heading-anchor-btn))')
        ) {
          enhance()
        }
      })
      observer.observe(container, { childList: true, subtree: true })
    }
  })

  onUnmounted(() => {
    observer?.disconnect()
    clearToc()
  })

  return { enhance }
}
