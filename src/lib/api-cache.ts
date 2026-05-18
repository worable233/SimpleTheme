/** Simple in-memory cache with TTL for API calls — curried wrapper */

interface CacheEntry<T> {
  data: T
  expiry: number
}

const store = new Map<string, CacheEntry<unknown>>()
const DEFAULT_TTL = 60_000 // 1 min

export function withCache<T, A extends unknown[]>(
  fetcher: (...args: A) => Promise<T>,
  key: string,
  ttl = DEFAULT_TTL,
): (...args: A) => Promise<T> {
  return (...args: A) => {
    const hit = store.get(key)
    if (hit && Date.now() < hit.expiry) {
      return Promise.resolve(hit.data as T)
    }
    return fetcher(...args).then((data) => {
      store.set(key, { data, expiry: Date.now() + ttl })
      return data
    })
  }
}

export function clearAllCache(): void {
  store.clear()
}
