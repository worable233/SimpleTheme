import {
  mockSiteInfo,
  mockMenuItems,
  mockPosts,
  mockShuoshuo,
  mockAboutInfo,
  mockCategories,
  mockLinkCategories,
} from '@/lib/mock-data'
import type {
  MenuCollectionResponse,
  PagedPostCollection,
  ResolveResponse,
  SiteInfo,
  WordPressPost,
  AboutInfo,
  WordPressCategory,
  WordPressLinkCategory,
} from '@/types/wordpress'

const isMockMode = import.meta.env.VITE_USE_MOCK === 'true'

export function shouldUseMock() {
  return isMockMode
}

export async function mockFetchSiteInfo(): Promise<SiteInfo> {
  await simulateDelay(300)
  return mockSiteInfo
}

export async function mockFetchNavigation(_location: string): Promise<MenuCollectionResponse['items']> {
  await simulateDelay(200)
  return mockMenuItems
}

export async function mockFetchCollection(
  type: 'post' | 'page' | 'shuoshuo',
  options?: { limit?: number; page?: number; taxonomy?: string; termId?: number },
): Promise<PagedPostCollection> {
  await simulateDelay(400)

  const limit = options?.limit || 6
  const page = options?.page || 1

  let allItems: WordPressPost[] = []
  if (type === 'post') {
    allItems = mockPosts
  } else if (type === 'shuoshuo') {
    allItems = mockShuoshuo
  }

  // Filter by category if taxonomy and termId are provided
  let items = allItems
  if (options?.taxonomy === 'category' && options.termId) {
    const category = mockCategories.find((c) => c.id === options.termId)
    if (category) {
      items = allItems.filter((post) => post.categories?.includes(category.name))
    }
  }

  const start = (page - 1) * limit
  const end = start + limit
  const pagedItems = items.slice(start, end)

  return {
    items: pagedItems,
    total: items.length,
    totalPages: Math.ceil(items.length / limit),
    page,
    perPage: limit,
  }
}

export async function mockFetchAboutInfo(): Promise<AboutInfo> {
  await simulateDelay(300)
  return mockAboutInfo
}

export async function mockFetchCategories(): Promise<WordPressCategory[]> {
  await simulateDelay(200)
  return mockCategories
}

export async function mockFetchLinks(): Promise<WordPressLinkCategory[]> {
  await simulateDelay(300)
  return mockLinkCategories
}

export async function mockResolveThemePath(path: string): Promise<ResolveResponse> {
  await simulateDelay(100)

  const slug = path.split('/').filter(Boolean).pop()?.toLowerCase() || ''

  if (!slug) {
    return { type: '404', message: 'Mock: 内容不存在' }
  }

  const allContent: WordPressPost[] = [...mockPosts, ...mockShuoshuo]
  for (const post of allContent) {
    const postSlug = post.link.split('/').filter(Boolean).pop()?.toLowerCase()
    if (postSlug === slug) {
      return {
        type: (post.type as 'post' | 'shuoshuo') || 'post',
        restUrl: `mock://wp/v2/${post.type === 'shuoshuo' ? 'shuoshuo' : 'posts'}/${post.id}?_embed=1`,
        id: post.id,
      }
    }
  }

  return { type: '404', message: 'Mock: 内容不存在' }
}

export async function mockFetchContentByRestUrl(restUrl: string): Promise<WordPressPost | null> {
  await simulateDelay(200)

  const match = restUrl.match(/mock:\/\/wp\/v2\/(?:posts|shuoshuo)\/(\d+)/)
  if (!match) return null

  const id = Number(match[1])
  return [...mockPosts, ...mockShuoshuo].find((p) => p.id === id) || null
}

function simulateDelay(ms: number): Promise<void> {
  return new Promise((resolve) => setTimeout(resolve, ms))
}
