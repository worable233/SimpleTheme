/**
 * In-memory LRU cache with TTL for API calls — curried wrapper
 *
 * 淘汰策略：
 * - TTL：命中但过期的条目按未命中处理并删除
 * - LRU：容量满时淘汰最久未使用的条目；每次命中会将条目
 *   刷新到"最近使用"端（利用 Map 的插入顺序实现 O(1) 近似 LRU）
 */

interface CacheEntry<T> {
  data: T
  expiry: number
}

const store = new Map<string, CacheEntry<unknown>>()
const DEFAULT_TTL = 60_000 // 1 min
const MAX_ENTRIES = 100 // LRU 容量上限

function lruGet(key: string): CacheEntry<unknown> | undefined {
  const entry = store.get(key)
  if (!entry) return undefined
  if (Date.now() >= entry.expiry) {
    store.delete(key)
    return undefined
  }
  // 刷新至"最近使用"端：Map 迭代顺序 = 插入顺序
  store.delete(key)
  store.set(key, entry)
  return entry
}

function lruSet(key: string, entry: CacheEntry<unknown>): void {
  // 覆盖写也要刷新位置
  store.delete(key)
  store.set(key, entry)
  // 超容量时淘汰最久未使用（Map 首端）的条目
  while (store.size > MAX_ENTRIES) {
    const oldest = store.keys().next().value
    if (oldest === undefined) break
    store.delete(oldest)
  }
}

export function withCache<T, A extends unknown[]>(
  fetcher: (...args: A) => Promise<T>,
  key: string,
  ttl = DEFAULT_TTL,
): (...args: A) => Promise<T> {
  return (...args: A) => {
    const hit = lruGet(key)
    if (hit) {
      return Promise.resolve(hit.data as T)
    }
    return fetcher(...args).then((data) => {
      lruSet(key, { data, expiry: Date.now() + ttl })
      return data
    })
  }
}

export function clearApiCache(): void {
  store.clear()
}
