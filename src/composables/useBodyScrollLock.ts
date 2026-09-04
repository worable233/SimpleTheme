let activeLocks = 0
let previousOverflow = ''

export function useBodyScrollLock() {
  let locked = false

  function lockBodyScroll() {
    if (locked || typeof document === 'undefined') return

    if (activeLocks === 0) {
      previousOverflow = document.body.style.overflow
    }

    activeLocks += 1
    locked = true
    document.body.style.overflow = 'hidden'
  }

  function unlockBodyScroll() {
    if (!locked || typeof document === 'undefined') return

    activeLocks = Math.max(0, activeLocks - 1)
    locked = false

    if (activeLocks === 0) {
      document.body.style.overflow = previousOverflow
    }
  }

  return { lockBodyScroll, unlockBodyScroll }
}
