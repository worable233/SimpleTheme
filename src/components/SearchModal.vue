<script setup lang="ts">
/**
 * SearchModal — 搜索弹窗（含输入框、模态遮罩、按键导航）
 */
import { ref, computed, watch, onMounted, onUnmounted, nextTick } from 'vue'
import { useRouter } from 'vue-router'
import AppIcon from '@/components/AppIcon.vue'
import { buildRestUrl, getErrorMessage } from '@/lib/wordpress'
import { toInternalPath } from '@/lib/theme-config'
import { showError } from '@/lib/toast'
import { useDebounce } from '@/composables/useDebounce'
import type { WordPressPost } from '@/types/wordpress'
import SearchResultList from '@/components/search/SearchResultList.vue'
import ModalCloseButton from '@/components/ModalCloseButton.vue'

const props = defineProps<{
  modelValue: boolean
}>()

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
}>()

const router = useRouter()

const searchQuery = ref('')
const searchResults = ref<WordPressPost[]>([])
const isSearching = ref(false)
const hasSearched = ref(false)
const errorMessage = ref('')
const activeIndex = ref(-1)
const searchInput = ref<HTMLInputElement | null>(null)
const resultsContainer = ref<HTMLElement | null>(null)
let abortController: AbortController | null = null

const { debounced: debouncedSearch, cancel: cancelSearch } = useDebounce(doSearch, 300)

watch(() => props.modelValue, (open) => {
  if (open) {
    nextTick(() => searchInput.value?.focus())
  }
})

const contentVisible = computed(() => searchQuery.value.length >= 2 || isSearching.value || hasSearched.value || !!errorMessage.value)

function doSearch(query: string) {
  if (query.length < 2) {
    searchResults.value = []
    hasSearched.value = false
    errorMessage.value = ''
    return
  }

  if (abortController) {
    abortController.abort()
  }
  abortController = new AbortController()

  isSearching.value = true
  errorMessage.value = ''
  activeIndex.value = -1

  const url = `${buildRestUrl('wp/v2/posts')}?search=${encodeURIComponent(query)}&per_page=10&_embed=1`

  fetch(url, { signal: abortController.signal })
    .then((res) => {
      if (!res.ok) throw new Error(`HTTP ${res.status}`)
      return res.json()
    })
    .then((data: WordPressPost[]) => {
      searchResults.value = data
      hasSearched.value = true
    })
    .catch((err: Error) => {
      if (err.name === 'AbortError') return
      errorMessage.value = getErrorMessage(err, '搜索请求失败')
      showError(errorMessage.value)
    })
    .finally(() => {
      isSearching.value = false
    })
}

function onInput(e: Event) {
  const val = (e.target as HTMLInputElement).value
  searchQuery.value = val
  if (val.length >= 2) {
    debouncedSearch(val)
  } else {
    cancelSearch()
    searchResults.value = []
    hasSearched.value = false
    errorMessage.value = ''
  }
}

function navigateToResult(index: number) {
  const post = searchResults.value[index]
  if (!post) return
  closeSearch()
  router.push(post.slug ? `/${post.slug}/` : toInternalPath(post.link))
}

function handleKeydown(e: KeyboardEvent) {
  if (e.key === 'Escape') {
    closeSearch()
    return
  }

  if (e.key === 'ArrowDown') {
    e.preventDefault()
    if (activeIndex.value < searchResults.value.length - 1) {
      activeIndex.value++
      scrollIntoView()
    }
    return
  }

  if (e.key === 'ArrowUp') {
    e.preventDefault()
    if (activeIndex.value > 0) {
      activeIndex.value--
      scrollIntoView()
    }
    return
  }

  if (e.key === 'Enter' && activeIndex.value >= 0) {
    e.preventDefault()
    navigateToResult(activeIndex.value)
    return
  }
}

function scrollIntoView() {
  nextTick(() => {
    const container = resultsContainer.value
    if (!container) return
    const active = container.querySelector('.search-modal__result--active') as HTMLElement | null
    active?.scrollIntoView({ block: 'nearest' })
  })
}

function closeSearch() {
  emit('update:modelValue', false)
  searchQuery.value = ''
  searchResults.value = []
  hasSearched.value = false
  errorMessage.value = ''
  activeIndex.value = -1
  cancelSearch()
}

function handleGlobalKeydown(e: KeyboardEvent) {
  if (!props.modelValue) return

  if (e.key === 'Escape') {
    e.preventDefault()
    closeSearch()
    return
  }

  if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
    e.preventDefault()
    closeSearch()
    return
  }
}

// 正文 core/search 区块提交 → 预填关键词并立即搜索（弹窗由 LeftSidebar 监听同一事件打开）
function onOpenSearchEvent(e: Event) {
  const query = (e as CustomEvent<string>).detail || ''
  if (!query) return
  searchQuery.value = query
  nextTick(() => {
    if (query.length >= 2) doSearch(query)
  })
}

onMounted(() => {
  document.addEventListener('keydown', handleGlobalKeydown)
  window.addEventListener('st:open-search', onOpenSearchEvent)
})

onUnmounted(() => {
  document.removeEventListener('keydown', handleGlobalKeydown)
  window.removeEventListener('st:open-search', onOpenSearchEvent)
  if (abortController) abortController.abort()
})
</script>

<template>
  <Teleport to="body">
    <Transition name="search-modal">
      <div
        v-if="modelValue"
        class="search-modal"
        @click.self="closeSearch"
        @touchstart.self="closeSearch"
      >
        <div class="search-modal__panel">
          <!-- Search input -->
          <div class="search-modal__input">
            <AppIcon name="search" :size="22" class="search-modal__icon" />
            <input
              ref="searchInput"
              :value="searchQuery"
              type="text"
              class="search-modal__field"
              placeholder="搜索文章…"
              aria-label="搜索"
              @input="onInput"
              @keydown="handleKeydown"
            />
            <button
              v-if="searchQuery"
              class="search-modal__clear"
              @click="searchQuery = ''; searchResults = []; hasSearched = false; errorMessage = ''; cancelSearch()"
              aria-label="清除搜索"
            >
              <AppIcon name="x" :size="18" />
            </button>
            <ModalCloseButton class="search-modal__close" @click="closeSearch" />
          </div>

          <!-- Results area -->
          <div
            class="search-modal__results-wrap"
            :class="{ 'search-modal__results-wrap--open': contentVisible }"
          >
            <div class="search-modal__results-inner">
              <SearchResultList
                ref="resultsContainer"
                :results="searchResults"
                :query="searchQuery"
                :is-searching="isSearching"
                :has-searched="hasSearched"
                :error-message="errorMessage"
                :active-index="activeIndex"
                @navigate="navigateToResult"
                @retry="doSearch(searchQuery)"
                @update:active-index="activeIndex = $event"
              />
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
/* ==================== Backdrop ==================== */
.search-modal {
  position: fixed;
  inset: 0;
  z-index: 99999;
  display: flex;
  align-items: flex-start;
  justify-content: center;
  padding-top: 12vh;
  background-color: color-mix(in srgb, var(--background) 88%, transparent);
  backdrop-filter: blur(6px);
  -webkit-backdrop-filter: blur(6px);
}

/* ==================== Panel ==================== */
.search-modal__panel {
  width: 100%;
  max-width: 600px;
  margin: 0 1rem;
  background-color: var(--card);
  border-radius: var(--radius-large);
  box-shadow: 0 12px 48px rgba(0, 0, 0, 0.15);
  overflow: hidden;
  display: flex;
  flex-direction: column;
  max-height: 70vh;
}

/* ==================== Input bar ==================== */
.search-modal__input {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.875rem 1.25rem;
  border-bottom: 1px solid var(--border);
  flex-shrink: 0;
  border-radius: var(--radius-large) var(--radius-large) 0 0;
}

.search-modal__icon {
  flex-shrink: 0;
  color: var(--secondary);
}

.search-modal__field {
  flex: 1;
  min-width: 0;
  border: none;
  background: transparent;
  font-size: 1rem;
  font-weight: 500;
  letter-spacing: 0.01em;
  line-height: 1.5;
  padding: 0.35rem 0;
  color: var(--foreground);
  outline: none;
}

.search-modal__field:focus {
  box-shadow: none;
}

.search-modal__clear {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  padding: 0;
  background: transparent;
  border: none;
  border-radius: 50%;
  cursor: pointer;
  color: var(--secondary);
  flex-shrink: 0;
  transition: background-color var(--transition-fast);
}

.search-modal__clear:hover {
  background-color: var(--muted);
}

.search-modal__close {
  margin-left: 0.35rem;
}

/* ==================== Transitions — panel fade/slide with smooth bezier ==================== */
.search-modal-enter-active,
.search-modal-leave-active {
  transition: opacity 0.2s ease;
}

.search-modal-enter-from,
.search-modal-leave-to {
  opacity: 0;
}

.search-modal-enter-active .search-modal__panel,
.search-modal-leave-active .search-modal__panel {
  transition: transform 350ms cubic-bezier(0.4, 0, 0.2, 1), opacity 350ms cubic-bezier(0.4, 0, 0.2, 1);
}

.search-modal-enter-from .search-modal__panel,
.search-modal-leave-to .search-modal__panel {
  transform: translateY(-16px) scale(0.98);
  opacity: 0;
}

/* ==================== Results wrap — expand/collapse accordion ==================== */
.search-modal__results-wrap {
  max-height: 0;
  overflow: hidden;
  transition: max-height 350ms cubic-bezier(0.4, 0, 0.2, 1), opacity 350ms cubic-bezier(0.4, 0, 0.2, 1);
  opacity: 0;
}

.search-modal__results-wrap--open {
  max-height: 500px;
  opacity: 1;
}

.search-modal__results-inner {
  opacity: 0;
  transform: translateY(-10px);
  transition: opacity 350ms cubic-bezier(0.4, 0, 0.2, 1), transform 350ms cubic-bezier(0.4, 0, 0.2, 1);
  transition-delay: 150ms;
}

.search-modal__results-wrap--open .search-modal__results-inner {
  opacity: 1;
  transform: translateY(0);
}

/* ==================== Mobile ==================== */
@media (max-width: 600px) {
  .search-modal {
    padding-top: 0;
    align-items: flex-end;
    background-color: color-mix(in srgb, var(--background) 95%, transparent);
  }

  .search-modal__panel {
    max-width: 100%;
    margin: 0;
    max-height: 85vh;
    border-radius: var(--radius-large) var(--radius-large) 0 0;
  }

  .search-modal-enter-active .search-modal__panel,
  .search-modal-leave-active .search-modal__panel {
    transition: transform 350ms cubic-bezier(0.4, 0, 0.2, 1);
  }

  .search-modal-enter-from .search-modal__panel,
  .search-modal-leave-to .search-modal__panel {
    transform: translateY(100%);
    opacity: 1;
  }

  .search-modal__results-wrap--open {
    max-height: 70vh;
  }

  .search-modal__input {
    padding: 0.75rem 1rem;
  }

  .search-modal__field {
    font-size: 1rem;
    padding: 0.25rem 0;
  }
}

body[data-theme='dark'] .search-modal__input {
  background: var(--card);
  box-shadow: inset 0 1px 0 0 #fff3;
}
</style>
