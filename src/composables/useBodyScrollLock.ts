let activeLocks = 0
let previousBodyStyles: Partial<Record<'position' | 'top' | 'width' | 'overflow', string>> = {}
let lockedScrollY = 0

export function useBodyScrollLock() {
  let locked = false

  function lockBodyScroll() {
    if (locked || typeof document === 'undefined') return

    if (activeLocks === 0) {
      const body = document.body
      lockedScrollY = window.scrollY
      previousBodyStyles = {
        position: body.style.position,
        top: body.style.top,
        width: body.style.width,
        overflow: body.style.overflow,
      }
      body.style.position = 'fixed'
      body.style.top = `-${lockedScrollY}px`
      body.style.width = '100%'
      body.style.overflow = 'hidden'
    }

    activeLocks += 1
    locked = true
  }

  function unlockBodyScroll() {
    if (!locked || typeof document === 'undefined') return

    activeLocks = Math.max(0, activeLocks - 1)
    locked = false

    if (activeLocks === 0) {
      const body = document.body
      body.style.position = previousBodyStyles.position || ''
      body.style.top = previousBodyStyles.top || ''
      body.style.width = previousBodyStyles.width || ''
      body.style.overflow = previousBodyStyles.overflow || ''
      window.scrollTo(0, lockedScrollY)
      previousBodyStyles = {}
    }
  }

  return { lockBodyScroll, unlockBodyScroll }
}
