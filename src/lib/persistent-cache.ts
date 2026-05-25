/**
 * persistent-cache — Simple in-memory cache with TTL
 */
const store = new Map<string, { data: unknown; expiry: number }>()

export function getCached<T>(key: string): T | null {
  const entry = store.get(key)
  if (!entry) return null
  if (Date.now() > entry.expiry) {
    store.delete(key)
    return null
  }
  return entry.data as T
}

export function setCache(key: string, data: unknown, ttlMs: number): void {
  store.set(key, { data, expiry: Date.now() + ttlMs })
}

export function clearAllCache(): void {
  store.clear()
}

export function computeHash(input: string): string {
  let hash = 5381
  for (let i = 0; i < input.length; i++) {
    hash = ((hash << 5) + hash) + input.charCodeAt(i)
    hash |= 0
  }
  return Math.abs(hash).toString(36)
}
