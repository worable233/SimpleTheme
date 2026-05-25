<script setup lang="ts">
import { computed, onMounted, ref, onBeforeUnmount, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useSiteShell } from '@/composables/useSiteShell'
import { fetchCollection, fetchCategories, getErrorMessage, resolveThemePath, fetchContentByRestUrl } from '@/lib/wordpress'
import { mockFetchCollection, mockFetchCategories, shouldUseMock, mockResolveThemePath, mockFetchContentByRestUrl } from '@/lib/mock-api'
import { withCache } from '@/lib/api-cache'
import { toInternalPath } from '@/lib/theme-config'
import { showError } from '@/lib/toast'
import { getThemeConfig } from '@/lib/theme-config'
import type { PagedPostCollection, WordPressPost, WordPressCategory } from '@/types/wordpress'
import ErrorView from '@/components/ErrorView.vue'

const { siteInfo, ensureLoaded } = useSiteShell()
const route = useRoute()

const initialLoading = ref(false)
const loadingMore = ref(false)
const categoryLoading = ref(false)
const latestPosts = ref<WordPressPost[]>([])
const categories = ref<WordPressCategory[]>([])
const page = ref(1)
const totalPages = ref(0)
const hasMore = ref(true)
const errorMessage = ref('')

/** Local category slug; '' means 'all'. Initialized from route param on mount. */
const categorySlug = ref((route.params.slug as string) || '')

const SENTINEL_MARGIN = 400
const perPageCount = computed(() => siteInfo.value.collections?.homePostCount ?? 6)

const metaConfig = computed(() => getThemeConfig().features?.meta)

const formatDate = (dateString: string) =>
  new Intl.DateTimeFormat('zh-CN', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
  }).format(new Date(dateString))

const formatModifiedDate = (dateString?: string) => {
  if (!dateString) return ''
  return '更新: ' + formatDate(dateString)
}

const formatWordCount = (count?: number) => {
  if (!count) return ''
  return count >= 1000 ? `${(count / 1000).toFixed(1)}k 字` : `${count} 字`
}

let observer: IntersectionObserver | null = null
const sentinelRef = ref<HTMLElement | null>(null)

function setupObserver() {
  observer?.disconnect()
  prefetchTimers.forEach((t) => clearTimeout(t))
  prefetchTimers.clear()
  prefetchTimers.forEach((t) => clearTimeout(t))
  prefetchTimers.clear()
  prefetchTimers.forEach((t) => clearTimeout(t))
  prefetchTimers.clear()
  prefetchTimers.forEach((t) => clearTimeout(t))
  prefetchTimers.clear()
  observer = new IntersectionObserver(
    (entries) => {
      if (entries[0]?.isIntersecting && hasMore.value && !loadingMore.value) {
        loadMorePosts()
      }
    },
    { rootMargin: `${SENTINEL_MARGIN}px` },
  )
  if (sentinelRef.value) {
    observer.observe(sentinelRef.value)
  }
}

onMounted(async () => {
  await loadHomepageData()
  setupObserver()
})

onBeforeUnmount(() => {
  observer?.disconnect()
  prefetchTimers.forEach((t) => clearTimeout(t))
  prefetchTimers.clear()
})

// Watch route param changes (category filter via /categories/:slug)
// For browser back/forward navigation — sync local ref and reload.
watch(
  () => route.params.slug,
  async (newSlug) => {
    categorySlug.value = (newSlug as string) || ''
    page.value = 1
    hasMore.value = true
    totalPages.value = 0
    await loadPage(1)
  },
)

async function loadCategories() {
  try {
    const useMock = shouldUseMock()
    const cats = useMock ? await mockFetchCategories() : await withCache(fetchCategories, 'categories')()
    categories.value = cats
  } catch {
    // silently fail — categories are optional
  }
}

function onCategoryClick(slug: string) {
  const newSlug = slug === 'all' ? '' : slug
  if (newSlug === categorySlug.value) return
  categorySlug.value = newSlug

  // Clear current posts — old content disappears instantly
  latestPosts.value = []
  page.value = 1
  hasMore.value = true
  totalPages.value = 0
  categoryLoading.value = true

  loadPage(1)
    .catch(() => {
      // loadPage errors propagate; silently handled
    })
    .finally(() => {
      categoryLoading.value = false
    })
}

async function loadHomepageData() {
  initialLoading.value = true
  errorMessage.value = ''
  page.value = 1
  latestPosts.value = []

  try {
    await ensureLoaded()
    await loadCategories()
    await loadPage(1)
  } catch (error) {
    errorMessage.value = getErrorMessage(error, '首页内容加载失败，请稍后再试。')
    showError(errorMessage.value)
  } finally {
    initialLoading.value = false
  }
}

async function loadMorePosts() {
  if (loadingMore.value || !hasMore.value) return
  loadingMore.value = true

  try {
    await loadPage(page.value + 1)
  } catch (error) {
    showError(getErrorMessage(error, '加载更多文章失败，请稍后再试。'))
  } finally {
    loadingMore.value = false
  }
}

async function loadPage(pageNum: number) {
  const useMock = shouldUseMock()
  // Capture slug at call time — if it changes mid-fetch, discard results
  const capturedSlug = categorySlug.value
  const slug = capturedSlug || undefined

  let termId: number | undefined
  if (slug) {
    const found = categories.value.find((c) => c.slug === slug)
    if (found) termId = found.id
  }

  const postsResponse: PagedPostCollection = await (useMock
    ? mockFetchCollection('post', {
        limit: perPageCount.value,
        page: pageNum,
        taxonomy: termId ? 'category' : undefined,
        termId,
      })
    : fetchCollection('post', {
        limit: perPageCount.value,
        page: pageNum,
        taxonomy: termId ? 'category' : undefined,
        termId,
      }))

  const newItems = postsResponse.items || []

  if (pageNum === 1) {
    // Stale check: if user clicked another category while we were fetching, discard
    if (capturedSlug !== categorySlug.value) return

    // Progressive render: add cards one at a time so users see first card ASAP
    latestPosts.value = newItems.length ? newItems.slice(0, 1) : []
    for (let i = 1; i < newItems.length; i++) {
      if (capturedSlug !== categorySlug.value) return // category changed, stop adding
      await new Promise((r) => setTimeout(r, 60))
      latestPosts.value = newItems.slice(0, i + 1)
    }
  } else {
    latestPosts.value = [...latestPosts.value, ...newItems]
  }

  page.value = pageNum
  totalPages.value = postsResponse.totalPages
  hasMore.value = pageNum < postsResponse.totalPages
}

// ----- 悬停预取文章内容 (hover prefetch) -----
const prefetchTimers = new Map<number, ReturnType<typeof setTimeout>>()

function prefetchPost(post: WordPressPost) {
  const t = prefetchTimers.get(post.id)
  if (t) clearTimeout(t)
  prefetchTimers.set(
    post.id,
    setTimeout(async () => {
      try {
        const path = toInternalPath(post.link)
        const useMock = shouldUseMock()
        const resolver = useMock ? mockResolveThemePath : withCache(resolveThemePath, `resolve:${path}`, 30_000)
        const resolved = await resolver(path)
        if (resolved.restUrl) {
          const fetcher = useMock
            ? mockFetchContentByRestUrl
            : withCache(fetchContentByRestUrl, `post:${resolved.restUrl}`, 600_000)
          await fetcher(resolved.restUrl)
        }
      } catch { /* 预取失败静默忽略 */ }
    }, 200),
  )
}

function cancelPrefetch(post: WordPressPost) {
  const t = prefetchTimers.get(post.id)
  if (t) { clearTimeout(t); prefetchTimers.delete(post.id) }
}

</script>

<template>
  <div class="home-content">
    <!-- Page Header -->
    <header class="section-header">
      <h1>
        <span class="section-header__title">首页</span>
        <span class="section-header__subtitle">Home.</span>
      </h1>
    </header>

    <!-- Latest Posts -->
    <section>
      <!-- Filter bar -->
      <div class="filter-bar">
        <button
          :class="['filter-bar__btn', { 'filter-bar__btn--active': categorySlug === '' }]"
          @click="onCategoryClick('all')"
        >
          全部
        </button>
        <button
          v-for="cat in categories"
          :key="cat.id"
          :class="['filter-bar__btn', { 'filter-bar__btn--active': categorySlug === cat.slug }]"
          @click="onCategoryClick(cat.slug)"
        >
          {{ cat.name }}
        </button>
      </div>

      <!-- Error state overlays everything -->
      <ErrorView
        v-if="errorMessage"
        illustration="warning"
        title="首页加载失败"
        :description="errorMessage"
      />

      <!-- Progressively rendered post cards: skeleton → real cards -->
      <template v-else>
        <!-- Skeleton grid (initial load only — category transitions skip skeleton) -->
        <div v-if="initialLoading && latestPosts.length === 0" class="post-list">
          <div v-for="i in perPageCount" :key="'sk-init-' + i" class="post-card-skeleton">
            <div class="post-card-skeleton__cover"></div>
            <div class="post-card-skeleton__text">
              <div style="height: 1.125rem; width: 70%; margin-bottom: 0.5rem; border-radius: var(--radius-small, 4px); background: var(--muted); animation: pulse 1.5s ease-in-out infinite;"></div>
              <div style="height: 0.75rem; width: 100%; margin-bottom: 0.5rem; border-radius: var(--radius-small, 4px); background: var(--muted); animation: pulse 1.5s ease-in-out infinite;"></div>
              <div style="height: 0.75rem; width: 65%; margin-bottom: 0.75rem; border-radius: var(--radius-small, 4px); background: var(--muted); animation: pulse 1.5s ease-in-out infinite;"></div>
            </div>
          </div>
        </div>

        <!-- Real post cards with fade-up transition -->
        <div v-else class="post-list">
          <TransitionGroup name="fade-up" tag="div" class="post-list__grid">
            <article
              v-for="post in latestPosts"
              :key="post.id"
              class="post-card"
              @mouseenter="prefetchPost(post)"
              @mouseleave="cancelPrefetch(post)"
            >
              <img
                v-if="post.featuredImage"
                :src="post.featuredImage"
                alt=""
                loading="lazy"
                class="post-card__cover"
              />
              <div class="post-card__meta">
                <span v-if="metaConfig?.showCategory && post.categories?.[0]" class="post-card__meta-item">{{ post.categories[0] }}</span>
                <time v-if="metaConfig?.showPublishDate" :datetime="post.date" class="post-card__meta-item">{{ formatDate(post.date) }}</time>
                <time v-if="metaConfig?.showModifiedDate && post.modified" :datetime="post.modified" class="post-card__meta-item">{{ formatModifiedDate(post.modified) }}</time>
                <span v-if="metaConfig?.showCommentCount && post.commentCount !== undefined" class="post-card__meta-item">{{ post.commentCount }} 评论</span>
                <span v-if="metaConfig?.showViewCount && post.viewCount !== undefined" class="post-card__meta-item">{{ post.viewCount }} 热度</span>
                <span v-if="metaConfig?.showReadingTime && post.readingTime" class="post-card__meta-item">{{ post.readingTime }} 分钟</span>
                <span v-if="metaConfig?.showWordCount && post.wordCount" class="post-card__meta-item">{{ formatWordCount(post.wordCount) }}</span>
              </div>
              <div class="post-card__body">
                <h2 class="post-card__title">
                  <router-link
                    :to="toInternalPath(post.link)"
                    v-html="post.title.rendered"
                  ></router-link>
                </h2>
                <p v-if="post.excerpt?.rendered" class="post-card__excerpt" v-html="post.excerpt.rendered"></p>
              </div>
            </article>
          </TransitionGroup>
        </div>

        <!-- Empty state (not during initial load or category loading) -->
        <ErrorView
          v-if="!initialLoading && !categoryLoading && latestPosts.length === 0"
          illustration="blank-canvas"
          title="还没有文章"
          description="还没有文章，稍后回来看看吧。"
        />

        <!-- Loading more skeleton (appended below existing posts) -->
        <div v-if="loadingMore" class="post-list post-list--append">
          <div v-for="i in 3" :key="'sk-more-' + i" class="post-card-skeleton">
            <div class="post-card-skeleton__cover"></div>
            <div class="post-card-skeleton__text">
              <div style="height: 1.125rem; width: 70%; margin-bottom: 0.5rem; border-radius: var(--radius-small, 4px); background: var(--muted); animation: pulse 1.5s ease-in-out infinite;"></div>
              <div style="height: 0.75rem; width: 100%; margin-bottom: 0.5rem; border-radius: var(--radius-small, 4px); background: var(--muted); animation: pulse 1.5s ease-in-out infinite;"></div>
              <div style="height: 0.75rem; width: 65%; margin-bottom: 0.75rem; border-radius: var(--radius-small, 4px); background: var(--muted); animation: pulse 1.5s ease-in-out infinite;"></div>
            </div>
          </div>
        </div>

        <!-- Intersection sentinel -->
        <div
          v-if="hasMore && latestPosts.length > 0"
          ref="sentinelRef"
          class="scroll-sentinel"
        ></div>
      </template>
    </section>

    <!-- End note (shown when all pages loaded) -->
    <p v-if="!hasMore && latestPosts.length > 0" class="end-note">{{ siteInfo.endNote || '好像就这么多' }}</p>
  </div>
</template>

<style scoped>
.home-content {
  --anim-ease-enter: cubic-bezier(0.16, 1, 0.3, 1);
  --anim-ease-hover: cubic-bezier(0.34, 1.56, 0.64, 1);
  --anim-duration-enter: 0.5s;
  --anim-duration-hover: 0.35s;
}

/* Fade-up enter transition for category switch (each card fades in rising) */
.fade-up-enter-active {
  transition: opacity 0.45s ease-out, transform 0.45s cubic-bezier(0.16, 1, 0.3, 1);
}
.fade-up-enter-from {
  opacity: 0;
  transform: translateY(20px);
}

/* Leave is instant — cards disappear immediately */
.fade-up-leave-active {
  position: absolute;
  opacity: 0;
  transition: none;
}

/* Top gap for loading-more skeleton */
.post-list--append {
  margin-top: 1.5rem;
}

/* Sentinel — invisible trigger for IntersectionObserver */
.scroll-sentinel {
  height: 1px;
  pointer-events: none;
}

/* Responsive skeleton — stack vertically on mobile */
@media (max-width: 600px) {
  .post-card-skeleton {
    flex-direction: column;
    min-height: auto;
  }
  .post-card-skeleton__text {
    padding: 1rem;
    gap: 0.5rem;
  }
  .post-card-skeleton__cover {
    width: 100%;
    height: 140px;
    min-height: auto;
    flex-shrink: 0;
  }
}
</style>
