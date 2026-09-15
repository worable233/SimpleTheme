/**
 * useToast — 全局提示队列（模块级单例）
 *
 * 用法：
 *   const toast = useToast()
 *   toast.success('已保存')
 *   toast.error('保存失败', '网络请求超时')
 *
 * 队列是模块级共享的：任意调用点与 <StToast /> 渲染层读到同一份 ref，
 * 因此不需要 provide/inject，也不需要把 toast 实例透传到子组件。
 * 渲染由 <StToast /> 负责，本文件只维护数据与计时器。
 */
import { ref, type Ref } from 'vue'

export type StToastType = 'success' | 'error' | 'warning' | 'info'

export interface StToastItem {
  id: number
  type: StToastType
  title: string
  message?: string
  duration: number
}

export interface StToastApi {
  toasts: Ref<StToastItem[]>
  show(item: Partial<StToastItem> & { title: string }): number
  success(title: string, message?: string): number
  error(title: string, message?: string): number
  warning(title: string, message?: string): number
  remove(id: number): void
  clear(): void
}

/** 与 src/lib/toast.ts 的既有节奏保持一致：错误停留更久 */
const ERROR_DURATION = 6000
const DEFAULT_DURATION = 4000

/** 模块级共享队列：这是「单例」的实体，StToast 与调用方都引用它 */
export const toasts = ref<StToastItem[]>([])

let seed = 0
const timers = new Map<number, ReturnType<typeof setTimeout>>()

function clearTimer(id: number) {
  const timer = timers.get(id)
  if (timer === undefined) return
  clearTimeout(timer)
  timers.delete(id)
}

export function removeToast(id: number) {
  clearTimer(id)
  const index = toasts.value.findIndex((item) => item.id === id)
  if (index !== -1) toasts.value.splice(index, 1)
}

export function clearToasts() {
  timers.forEach((timer) => clearTimeout(timer))
  timers.clear()
  toasts.value = []
}

export function showToast(item: Partial<StToastItem> & { title: string }): number {
  const type = item.type ?? 'info'
  const duration = item.duration ?? (type === 'error' ? ERROR_DURATION : DEFAULT_DURATION)
  const id = ++seed

  toasts.value.push({ id, type, title: item.title, message: item.message, duration })

  // duration <= 0 表示常驻，由调用方显式 remove
  if (duration > 0) {
    timers.set(
      id,
      setTimeout(() => removeToast(id), duration),
    )
  }

  return id
}

export function useToast(): StToastApi {
  return {
    toasts,
    show: showToast,
    success: (title: string, message?: string) => showToast({ type: 'success', title, message }),
    error: (title: string, message?: string) => showToast({ type: 'error', title, message }),
    warning: (title: string, message?: string) => showToast({ type: 'warning', title, message }),
    remove: removeToast,
    clear: clearToasts,
  }
}
