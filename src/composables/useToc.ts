import { computed, ref } from 'vue'

export interface TocItem {
  id: string
  text: string
  level: number
}

const tocItems = ref<TocItem[]>([])
const activeId = ref('')
const drawerOpen = ref(false)

export function useToc() {
  /** Extract TOC items from the prose container DOM */
  function extractToc(selector = '.prose-content'): TocItem[] {
    const container = document.querySelector(selector)
    if (!container) return []
    const headings = container.querySelectorAll('h2[id], h3[id], h4[id]')
    const items = Array.from(headings).map((h) => ({
      id: h.getAttribute('id') || '',
      text: (h.textContent || '').replace(/(?:🔗|#)$/, '').trim(),
      level: parseInt(h.tagName.charAt(1), 10) || 2,
    }))
    tocItems.value = items
    return items
  }

  function setTocItems(items: TocItem[]) {
    tocItems.value = items
  }

  function clearToc() {
    tocItems.value = []
    activeId.value = ''
    drawerOpen.value = false
  }

  /** 当前激活章节的标题文本（顶栏阅读模式显示用） */
  const activeText = computed(() => {
    if (!activeId.value) return ''
    return tocItems.value.find((item) => item.id === activeId.value)?.text || ''
  })

  return {
    tocItems,
    activeId,
    activeText,
    drawerOpen,
    setTocItems,
    clearToc,
    extractToc,
  }
}
