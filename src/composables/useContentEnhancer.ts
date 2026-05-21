import { watch, nextTick, onMounted, onUnmounted, type Ref } from 'vue'
import { useToc } from '@/composables/useToc'
import { isExternalUrl } from '@/lib/theme-config'
// Prism is loaded as a regular <script> by WordPress (not an ES module import).
// It's available globally via window.Prism.
declare var Prism: { highlightElement: (el: HTMLElement) => void } | undefined
import { Fancybox } from '@fancyapps/ui'
import '@fancyapps/ui/dist/fancybox/fancybox.css'
import { showToast } from '@/lib/toast'

let fancyboxBound = false

const COPY_LINK_ICON = '#'

const CLIPBOARD_ICON = `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>`

const CHECK_ICON = `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>`

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
  for (const heading of headings) {
    // Auto-generate ID if the heading doesn't have one
    let id = heading.getAttribute('id')
    if (!id) {
      id = (heading.textContent || '')
        .toLowerCase()
        .replace(/[^\w\u4e00-\u9fa5]+/g, '-')
        .replace(/^-+|-+$/g, '')
      if (!id) id = 'heading-' + Math.random().toString(36).slice(2, 8)
      heading.setAttribute('id', id)
    }

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
	      showToast('链接已复制到剪贴板')
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
    showToast('代码已复制到剪贴板')
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

/** Transform external links to route through the /go redirect page. */
function transformExternalLinks(container: Element) {
  const links = container.querySelectorAll<HTMLAnchorElement>('a[href]')
  for (const link of links) {
    const href = link.getAttribute('href')
    if (!href) continue
    if (
      href.startsWith('#') ||
      href.startsWith('mailto:') ||
      href.startsWith('tel:') ||
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

    const wrapper = document.createElement('a')
    // Normalize URL to ensure proper encoding of non-ASCII characters
    try { wrapper.href = new URL(src, window.location.origin).href } catch { wrapper.href = src }
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
    } as any)
  }
}

export function useContentEnhancer(
  content?: Ref<string | null | undefined>,
  selector = '.oat-prose',
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
      addHeadingAnchors(container)
      wrapCodeParagraphs(container)
      processCodeBlocks(container)
      // Route external links through /go redirect page
      transformExternalLinks(container)
      // Initialize Fancybox for article images
      initFancyboxImages(container)
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
