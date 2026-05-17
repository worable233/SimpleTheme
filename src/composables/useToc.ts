import { ref } from 'vue'

export interface TocItem {
  id: string
  text: string
  level: number
}

const tocItems = ref<TocItem[]>([])
const activeId = ref('')

export function useToc() {
  /** Extract TOC items from the prose container DOM */
  function extractToc(selector = '.oat-prose'): TocItem[] {
    const container = document.querySelector(selector)
    if (!container) return []
    const headings = container.querySelectorAll('h2[id], h3[id], h4[id]')
    const items = Array.from(headings).map((h) => ({
      id: h.getAttribute('id') || '',
      text: (h.textContent || '').replace(/🔗$/, '').trim(),
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
  }

  return {
    tocItems,
    activeId,
    setTocItems,
    clearToc,
    extractToc,
  }
}
