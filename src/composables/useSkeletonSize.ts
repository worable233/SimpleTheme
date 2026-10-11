/**
 * useSkeletonSize — 骨架屏尺寸记忆
 *
 * 首次渲染后测量真实卡片的高度与形态（是否带特色图），
 * 按断点持久化到 localStorage；下次进入页面时骨架直接
 * 使用真实尺寸渲染，避免"骨架与实际内容高度差导致的跳动"。
 */
import { ref, nextTick } from 'vue'

export interface SkeletonSize {
  /** 卡片实测高度 px */
  h: number
  /** 卡片是否包含特色图区域 */
  cover: boolean
}

export function useSkeletonSize(storageKey: string) {
  // 卡片形态由容器宽度（440px 断点）决定，而非视口；这里用视口近似分档，
  // 阈值与 home.css 的 @container (max-width: 440px) 对齐，避免跨档误用尺寸。
  const key = () => `${storageKey}:${window.innerWidth <= 440 ? 'm' : 'd'}`

  const read = (): SkeletonSize | null => {
    try {
      const raw = localStorage.getItem(key())
      const parsed = raw ? (JSON.parse(raw) as SkeletonSize) : null
      return parsed && typeof parsed.h === 'number' && parsed.h > 0 ? parsed : null
    } catch {
      return null
    }
  }

  const size = ref<SkeletonSize | null>(read())

  /** 列表渲染完成后调用：测量首个真实卡片并持久化 */
  async function measure(cardSelector: string, coverSelector: string) {
    await nextTick()
    const el = document.querySelector<HTMLElement>(cardSelector)
    if (!el) return
    const h = Math.round(el.getBoundingClientRect().height)
    if (h < 40) return // 尚未布局完成，忽略
    const next: SkeletonSize = { h, cover: !!el.querySelector(coverSelector) }
    size.value = next
    try {
      localStorage.setItem(key(), JSON.stringify(next))
    } catch {
      // 存储不可用时静默降级为默认骨架
    }
  }

  return { size, measure }
}
