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

export type StToastType = 'success' | 'error' | 'warning' | 'info' | 'loading'

export interface StToastItem {
  id: number
  type: StToastType
  title: string
  message?: string
  /** 停留毫秒数；<= 0 表示常驻，需调用方显式 remove（例如 loading 态） */
  duration: number
}

export interface StToastApi {
  toasts: Ref<StToastItem[]>
  show(item: Partial<StToastItem> & { title: string }): number
  success(title: string, message?: string): number
  error(title: string, message?: string): number
  warning(title: string, message?: string): number
  info(title: string, message?: string): number
  /** 常驻进行中提示，需调用方以 remove(id) 收尾 */
  loading(title: string, message?: string): number
  /** 局部更新已存在的提示（例如倒计时改写文案） */
  update(id: number, patch: Partial<Omit<StToastItem, 'id'>>): void
  remove(id: number): void
  clear(): void
}

/** 节奏约定：错误停留更久 */
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

/** 局部更新已存在的提示；若改了 duration 则重置计时器（0 表示改为常驻） */
export function updateToast(id: number, patch: Partial<Omit<StToastItem, 'id'>>): void {
  const target = toasts.value.find((t) => t.id === id)
  if (!target) return

  const durationChanged = patch.duration !== undefined && patch.duration !== target.duration
  Object.assign(target, patch)

  if (durationChanged) {
    clearTimer(id)
    if (target.duration > 0) {
      timers.set(
        id,
        setTimeout(() => removeToast(id), target.duration),
      )
    }
  }
}

export function useToast(): StToastApi {
  return {
    toasts,
    show: showToast,
    success: (title: string, message?: string) => showToast({ type: 'success', title, message }),
    error: (title: string, message?: string) => showToast({ type: 'error', title, message }),
    warning: (title: string, message?: string) => showToast({ type: 'warning', title, message }),
    info: (title: string, message?: string) => showToast({ type: 'info', title, message }),
    // 常驻：duration <= 0，由调用方 remove(id) 结束
    loading: (title: string, message?: string) =>
      showToast({ type: 'loading', title, message, duration: 0 }),
    update: updateToast,
    remove: removeToast,
    clear: clearToasts,
  }
}
