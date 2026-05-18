declare global {
  interface Window {
    ot?: {
      toast: {
        el: (el: HTMLElement, opts: { placement: string; duration: number }) => HTMLElement | null
      }
    }
  }
}

// --- SVG icons (Semi Design style) ---
const ICONS: Record<string, string> = {
  success:
    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M8 12l2 2 4-4"/></svg>',
  danger:
    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M15 9l-6 6M9 9l6 6"/></svg>',
  warning:
    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3L2 21h20L12 3z"/><line x1="12" y1="10" x2="12" y2="14"/><line x1="12" y1="17" x2="12" y2="17"/></svg>',
  info:
    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="12" x2="12" y2="16"/><line x1="12" y1="8" x2="12" y2="8"/></svg>',
  loading:
    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" class="toast-spinner"><circle cx="12" cy="12" r="10" stroke-dasharray="31.4 31.4" stroke-linecap="round"/></svg>',
}

function getIcon(variant: string): string {
  return ICONS[variant] || ICONS.info!
}

function createToastElement(message: string, title?: string, variant = 'info'): HTMLOutputElement {
  const el = document.createElement('output')
  el.setAttribute('data-variant', variant)
  el.setAttribute('role', 'alert')

  // Icon wrapper
  const iconWrap = document.createElement('span')
  iconWrap.className = 'toast-icon'
  iconWrap.innerHTML = getIcon(variant)
  el.appendChild(iconWrap)

  // Content wrapper (title + message)
  const contentWrap = document.createElement('div')
  contentWrap.className = 'toast-content'

  if (title) {
    const titleEl = document.createElement('h6')
    titleEl.className = 'toast-title'
    titleEl.textContent = title
    contentWrap.appendChild(titleEl)
  }

  const msgEl = document.createElement('div')
  msgEl.className = 'toast-message'
  msgEl.textContent = message
  contentWrap.appendChild(msgEl)

  el.appendChild(contentWrap)

  // Close button (event listener wired after oat cloning)
  const closeBtn = document.createElement('button')
  closeBtn.className = 'toast-close'
  closeBtn.setAttribute('aria-label', '关闭')
  closeBtn.innerHTML =
    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>'
  el.appendChild(closeBtn)

  return el
}

export function showToast(
  message: string,
  title?: string,
  options?: { variant?: string; placement?: string; duration?: number },
) {
  const ot = window.ot
  if (!ot?.toast) return

  const { variant = 'info', placement = 'bottom-center', duration = 4000 } = options || {}

  const el = createToastElement(message, title, variant)
  el.style.setProperty('--toast-duration', `${duration}ms`)

  const progress = document.createElement('div')
  progress.className = 'toast-progress'
  const bar = document.createElement('div')
  bar.className = 'toast-progress-bar'
  progress.appendChild(bar)
  el.appendChild(progress)

  // ot.toast.el() internally clones the element, returns the actual DOM node
  const toastEl = ot.toast.el(el, { placement, duration })
  if (!toastEl) return

  // Wire up close button on the actual DOM node
  toastEl.querySelector('.toast-close')?.addEventListener('click', () => dismissToast(toastEl))

  // Remove oat's built-in pause-on-hover behavior
  toastEl.onmouseenter = null
  toastEl.onmouseleave = null

  return toastEl
}

export function dismissToast(toastEl: HTMLElement | null | undefined) {
  if (!toastEl || toastEl.hasAttribute('data-exiting')) return

  // Match oat's internal dismiss behavior for smooth exit
  toastEl.setAttribute('data-exiting', '')
  toastEl.style.setProperty('--transition', '250ms')
  const remove = () => toastEl.remove()
  toastEl.addEventListener('transitionend', remove, { once: true })
  // Fallback if transitionend doesn't fire
  setTimeout(remove, 350)
}

export function showLoadingToast(message: string, title: string) {
  const ot = window.ot
  if (!ot?.toast) return null

  const el = createToastElement(message, title, 'loading')
  // Duration-based progress bar is replaced by indeterminate animation
  el.style.setProperty('--toast-duration', '0ms')

  const progress = document.createElement('div')
  progress.className = 'toast-progress'
  progress.classList.add('toast-progress--indeterminate')
  const bar = document.createElement('div')
  bar.className = 'toast-progress-bar'
  progress.appendChild(bar)
  el.appendChild(progress)

  // Long timeout as safety net
  const toastEl = ot.toast.el(el, { placement: 'bottom-center', duration: 60000 })
  if (!toastEl) return null

  // Wire up close button on the actual DOM node
  toastEl.querySelector('.toast-close')?.addEventListener('click', () => dismissToast(toastEl))

  toastEl.onmouseenter = null
  toastEl.onmouseleave = null

  return toastEl
}

export function showError(message: string) {
  showToast(message, '错误', { variant: 'danger', placement: 'top-center', duration: 6000 })
}

export function showSuccess(message: string) {
  showToast(message, '成功', { variant: 'success' })
}

export function showWarning(message: string) {
  showToast(message, '提示', { variant: 'warning', duration: 5000 })
}
