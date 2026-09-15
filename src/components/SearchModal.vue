<script setup lang="ts">
/**
 * SearchModal — 搜索弹窗（含输入框、模态遮罩、按键导航）
 *
 * 外壳迁移到 StModal（焦点陷阱 / ESC / 遮罩点击 / 滚动锁定由 reka-ui 提供），
 * 输入框迁移到 StInput。搜索逻辑（防抖、请求、结果状态、键盘上下选择）原样保留。
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
import { StInput, StModal } from '@/ui'

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
const searchInput = ref<InstanceType<typeof StInput> | null>(null)
const resultsContainer = ref<HTMLElement | null>(null)
let abortController: AbortController | null = null

const { debounced: debouncedSearch, cancel: cancelSearch } = useDebounce(doSearch, 300)

/** StModal 的受控开关；对外仍是 modelValue 契约 */
const modalShow = computed({
  get: () => props.modelValue,
  set: (value: boolean) => emit('update:modelValue', value),
})

watch(
  () => props.modelValue,
  (open) => {
    if (!open) return
    // StModal/reka 挂载后会把焦点交给第一个可聚焦元素（右上角关闭按钮），
    // 这里等本次挂载的微任务全部跑完再抢回输入框，保持"打开即聚焦搜索框"的原有行为。
    requestAnimationFrame(() => searchInput.value?.focus())
  },
)

const contentVisible = computed(
  () =>
    searchQuery.value.length >= 2 ||
    isSearching.value ||
    hasSearched.value ||
    !!errorMessage.value,
)

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

/** 与旧 onInput 等价：StInput 每次值变化都会回传 update:modelValue */
function onValueChange(value: string | number | undefined) {
  const val = value === undefined || value === null ? '' : String(value)
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

/** 仅清空搜索状态，不触碰弹窗开关 */
function resetSearchState() {
  searchQuery.value = ''
  searchResults.value = []
  hasSearched.value = false
  errorMessage.value = ''
  activeIndex.value = -1
  cancelSearch()
}

function closeSearch() {
  emit('update:modelValue', false)
  resetSearchState()
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
  <StModal v-model:show="modalShow" title="搜索" size="medium" @close="resetSearchState">
    <!-- Search input -->
    <StInput
      ref="searchInput"
      :model-value="searchQuery"
      size="large"
      placeholder="搜索文章…"
      aria-label="搜索"
      clearable
      @update:model-value="onValueChange"
      @keydown="handleKeydown"
    >
      <template #prefix>
        <AppIcon name="search" :size="22" />
      </template>
    </StInput>

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
  </StModal>
</template>

<style scoped>
/* ==================== Results wrap — expand/collapse accordion ==================== */
.search-modal__results-wrap {
  max-height: 0;
  overflow: hidden;
  transition:
    max-height 350ms cubic-bezier(0.4, 0, 0.2, 1),
    opacity 350ms cubic-bezier(0.4, 0, 0.2, 1);
  opacity: 0;
}

.search-modal__results-wrap--open {
  max-height: 500px;
  opacity: 1;
}

.search-modal__results-inner {
  margin-top: 0.75rem;
  opacity: 0;
  transform: translateY(-10px);
  transition:
    opacity 350ms cubic-bezier(0.4, 0, 0.2, 1),
    transform 350ms cubic-bezier(0.4, 0, 0.2, 1);
  transition-delay: 150ms;
}

.search-modal__results-wrap--open .search-modal__results-inner {
  opacity: 1;
  transform: translateY(0);
}

/* ==================== Mobile ==================== */
@media (max-width: 600px) {
  .search-modal__results-wrap--open {
    max-height: 70vh;
  }
}

@media (prefers-reduced-motion: reduce) {
  .search-modal__results-wrap,
  .search-modal__results-inner {
    transition: none;
  }
}
</style>
