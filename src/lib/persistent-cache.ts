/**
 * persistent-cache — localStorage-backed cache with content-addressable versioning
 *
 * Data and version hash are stored together under a single localStorage key per entry.
 * All reads/writes are wrapped in try-catch for Private Browsing / quota-exceeded safety.
 */

import { clearApiCache } from './api-cache'

interface StoreEntry<T> {
  data: T
  version: string
  cachedAt: number
}

/** All entries live under one localStorage key to minimize lookups */
const STORAGE_KEY = 'st_cache_store_v1'

function readStore(): Record<string, StoreEntry<unknown>> {
  try {
    const raw = localStorage.getItem(STORAGE_KEY)
    return raw ? JSON.parse(raw) : {}
  } catch {
    return {}
  }
}

function writeStore(store: Record<string, StoreEntry<unknown>>) {
  try {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(store))
  } catch {
    // Storage full or unavailable — silently degrade
  }
}

/**
 * Read cached value + version for a key.
 * No TTL enforcement — the SWR pattern handles staleness.
 */
export function getCached<T>(key: string): { data: T; version: string } | null {
  const store = readStore()
  const entry = store[key] as StoreEntry<T> | undefined
  if (!entry) return null
  return { data: entry.data, version: entry.version }
}

/**
 * Write data to cache, computing a version hash automatically.
 * Overwrites any existing entry for the same key.
 */
export function setCache(key: string, data: unknown): void {
  const store = readStore()
  store[key] = {
    data,
    version: computeHash(JSON.stringify(data)),
    cachedAt: Date.now(),
  }
  writeStore(store)
}

/** Clear all caches (persistent + in-memory API dedup cache) */
export function clearAllCache(): void {
  try {
    localStorage.removeItem(STORAGE_KEY)
  } catch { /* noop */ }
  clearApiCache()
}

/**
 * DJB2 32-bit hash → base-36 string.
 * Used for content version comparison (not cryptographic).
 * Collision probability for < 10k entries is negligible for this use case.
 */
export function computeHash(input: string): string {
  let hash = 5381
  for (let i = 0; i < input.length; i++) {
    hash = ((hash << 5) + hash) + input.charCodeAt(i)
    hash |= 0
  }
  return Math.abs(hash).toString(36)
}
