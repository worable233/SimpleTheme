/**
 * useAuthModal — 全局认证弹窗状态（单例）
 */
import { ref } from 'vue'

const visible = ref(false)

export function useAuthModal() {
  function open() {
    visible.value = true
  }

  function close() {
    visible.value = false
  }

  return {
    visible,
    open,
    close,
  }
}
