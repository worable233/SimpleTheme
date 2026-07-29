import { ref } from 'vue'

export function useLoading() {
  const isLoading = ref(false)
  const error = ref<string | null>(null)

  async function withLoading<T>(fn: () => Promise<T>): Promise<T | undefined> {
    isLoading.value = true
    error.value = null
    try {
      return await fn()
    } catch (e: unknown) {
      const message = e instanceof Error ? e.message : String(e)
      error.value = message
      console.error('[useLoading]', message)
      return undefined
    } finally {
      isLoading.value = false
    }
  }

  return { isLoading, error, withLoading } as const
}

export type UseLoadingReturn = ReturnType<typeof useLoading>
