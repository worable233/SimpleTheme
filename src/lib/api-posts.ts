/**
 * api-posts — 文章 / 页面相关 API
 */
import { toInternalPath, getThemeConfig } from '@/lib/theme-config'
import { apiClient, buildRestUrl } from './api-client'
import { shouldUseMock, mockFetchCollection, mockFetchContentByRestUrl } from './mock-api'
import type { PagedPostCollection, WordPressPost } from '@/types/wordpress'
import axios from 'axios'

export async function fetchLatestPosts(limit = 6) {
  const { items } = await fetchCollection('post', { limit })
  return items
}

export async function fetchCollection(
  type: 'post' | 'page' | 'shuoshuo',
  options?: {
    limit?: number
    page?: number
    taxonomy?: string
    termId?: number
  },
) {
  if (shouldUseMock()) return mockFetchCollection(type, options)
  const collectionUrl = getThemeConfig().routes.collection.replace(/\/+$/, '')
  const params = new URLSearchParams({
    type,
    limit: String(options?.limit || 6),
    page: String(options?.page || 1),
  })

  if (options?.taxonomy && options.termId) {
    params.set('taxonomy', options.taxonomy)
    params.set('termId', String(options.termId))
  }

  try {
    const { data } = await apiClient.get<PagedPostCollection>(`${collectionUrl}?${params.toString()}`)
    return data
  } catch (error) {
    if (!axios.isAxiosError(error) || error.response?.status !== 404) throw error

    const wpTypeMap: Record<string, string> = { post: 'posts', page: 'pages', shuoshuo: 'shuoshuo' }
    const wpEndpoint = wpTypeMap[type]
    if (!wpEndpoint) throw error

    const wpParams = new URLSearchParams({
      per_page: String(Math.min(options?.limit ?? 6, 100)),
      page: String(options?.page || 1),
    })

    if (options?.taxonomy && options.termId) {
      const taxMap: Record<string, string> = { category: 'categories', post_tag: 'tags' }
      const taxKey = taxMap[options.taxonomy]
      if (taxKey) wpParams.set(taxKey, String(options.termId))
    }

    wpParams.set('_embed', '1')
    const { data, headers } = await apiClient.get<WordPressPost[]>(buildRestUrl(`wp/v2/${wpEndpoint}`), {
      params: Object.fromEntries(wpParams),
    })

    const items = (Array.isArray(data) ? data : []).map((post) => {
      const media = (post._embedded as Record<string, unknown[]> | undefined)?.['wp:featuredmedia']
      const sourceUrl = (media?.[0] as Record<string, unknown> | undefined)?.source_url
      const terms = (post._embedded as Record<string, unknown[]> | undefined)?.['wp:term'] as Array<Array<{ taxonomy?: string; name?: string }>> | undefined
      const categoryNames: string[] = []
      if (terms) {
        for (const group of terms) {
          for (const term of group) {
            if (term?.taxonomy === 'category' && typeof term.name === 'string') {
              categoryNames.push(term.name)
            }
          }
        }
      }
      return { ...post, featuredImage: (typeof sourceUrl === 'string' ? sourceUrl : undefined) || (post as { featuredImage?: string }).featuredImage, categories: categoryNames.length > 0 ? categoryNames : (post as { categories?: string[] }).categories }
    })

    return {
      items,
      total: Number(headers['x-wp-total'] || 0),
      totalPages: Number(headers['x-wp-totalpages'] || 0),
      page: options?.page || 1,
      perPage: options?.limit || 6,
    } satisfies PagedPostCollection
  }
}

export async function fetchPostCollectionByTaxonomy(taxonomy: string, termId: number, limit = 12) {
  const { items } = await fetchCollection('post', {
    taxonomy,
    termId,
    limit,
  })

  return items
}

export async function fetchContentByRestUrl(restUrl: string) {
  if (shouldUseMock()) return mockFetchContentByRestUrl(restUrl)
  try {
    const url = new URL(restUrl, window.location.origin)
    const normalized =
      url.origin !== window.location.origin
        ? `${window.location.origin}${url.pathname}${url.search}`
        : restUrl
    const { data } = await apiClient.get<WordPressPost>(normalized)
    return data
  } catch {
    return null
  }
}

export async function trackPostView(postId: number) {
  if (shouldUseMock()) return 0
  try {
    const { data } = await apiClient.post<{ viewCount: number }>(
      buildRestUrl('simple-theme/v1/track-view'),
      { postId },
    )
    return data.viewCount
  } catch {
    return 0
  }
}

export async function fetchPage(slug: string): Promise<WordPressPost | null> {
  if (shouldUseMock()) return null
  try {
    const { data } = await apiClient.get<WordPressPost[]>(
      buildRestUrl(`wp/v2/pages?slug=${encodeURIComponent(slug)}&per_page=1`),
    )
    return data[0] || null
  } catch (error) {
    if (axios.isAxiosError(error) && error.response?.status === 404) {
      return null
    }
    throw error
  }
}

export function toRouterPathFromWpLink(value: string) {
  return toInternalPath(value)
}
