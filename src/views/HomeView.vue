<script setup lang="ts">
import { computed, onMounted, ref, onBeforeUnmount, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useSiteShell } from '@/composables/useSiteShell'
import { fetchCollection, fetchCategories, getErrorMessage } from '@/lib/wordpress'
import { mockFetchCollection, mockFetchCategories, shouldUseMock } from '@/lib/mock-api'
import { toInternalPath } from '@/lib/theme-config'
import { showError } from '@/lib/toast'
import type { WordPressPost, WordPressCategory } from '@/types/wordpress'
import ErrorView from '@/components/ErrorView.vue'

const { siteInfo, ensureLoaded } = useSiteShell()
const route = useRoute()
const router = useRouter()

const initialLoading = ref(false)
const loadingMore = ref(false)
const latestPosts = ref<WordPressPost[]>([])
const categories = ref<WordPressCategory[]>([])
const activeCategory = ref<string>('all')
const page = ref(1)
const totalPages = ref(0)
const hasMore = ref(true)

const SENTINEL_MARGIN = 400
const perPageCount = computed(() => siteInfo.value.collections?.homePostCount ?? 6)

const formatDate = (dateString: string) =>
  new Intl.DateTimeFormat('zh-CN', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
  }).format(new Date(dateString))

let observer: IntersectionObserver | null = null
const sentinelRef = ref<HTMLElement | null>(null)

function setupObserver() {
  observer?.disconnect()
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
  await loadCategories()
  setupObserver()
})

onBeforeUnmount(() => {
  observer?.disconnect()
})

// Watch route query changes (category filter)
watch(
  () => route.query.category,
  async () => {
    activeCategory.value = (route.query.category as string) || 'all'
    resetPagination()
    await loadHomepageData()
  },
)

function resetPagination() {
  page.value = 1
  totalPages.value = 0
  hasMore.value = true
  latestPosts.value = []
}

async function loadCategories() {
  try {
    const useMock = shouldUseMock()
    const cats = useMock ? await mockFetchCategories() : await fetchCategories()
    categories.value = cats
  } catch {
    // silently fail — categories are optional
  }
}

function onCategoryClick(slug: string) {
  if (slug === 'all') {
    router.push({ path: '/', query: {} })
  } else {
    router.push({ path: '/', query: { category: slug } })
  }
}

async function loadHomepageData() {
  initialLoading.value = true
  page.value = 1
  latestPosts.value = []

  try {
    await ensureLoaded()
    await loadPage(1)
  } catch (error) {
    showError(getErrorMessage(error, '首页内容加载失败，请稍后再试。'))
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
  const categorySlug = route.query.category as string | undefined

  let termId: number | undefined
  if (categorySlug) {
    const found = categories.value.find((c) => c.slug === categorySlug)
    if (found) termId = found.id
  }

  const postsResponse = await (useMock
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
    latestPosts.value = newItems
  } else {
    latestPosts.value = [...latestPosts.value, ...newItems]
  }

  page.value = pageNum
  totalPages.value = postsResponse.totalPages
  hasMore.value = pageNum < postsResponse.totalPages
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
          :class="['filter-bar__btn', { 'filter-bar__btn--active': activeCategory === 'all' }]"
          @click="onCategoryClick('all')"
        >
          全部
        </button>
        <button
          v-for="cat in categories"
          :key="cat.id"
          :class="['filter-bar__btn', { 'filter-bar__btn--active': activeCategory === cat.slug }]"
          @click="onCategoryClick(cat.slug)"
        >
          {{ cat.name }}
        </button>
      </div>

      <!-- Initial loading skeleton -->
      <div v-if="initialLoading" class="post-list">
        <div v-for="i in 3" :key="'sk-post-' + i" class="post-card-skeleton">
          <div class="post-card-skeleton__text">
            <div style="height: 1.125rem; width: 70%; margin-bottom: 0.5rem; border-radius: var(--radius-small, 4px); background: var(--muted); animation: pulse 1.5s ease-in-out infinite;"></div>
            <div style="height: 0.75rem; width: 100%; margin-bottom: 0.5rem; border-radius: var(--radius-small, 4px); background: var(--muted); animation: pulse 1.5s ease-in-out infinite;"></div>
            <div style="height: 0.75rem; width: 65%; margin-bottom: 0.75rem; border-radius: var(--radius-small, 4px); background: var(--muted); animation: pulse 1.5s ease-in-out infinite;"></div>
          </div>
          <div class="post-card-skeleton__cover"></div>
        </div>
      </div>

      <!-- Post list -->
      <div v-else-if="latestPosts.length" class="content-area">
        <div class="post-list">
          <article v-for="post in latestPosts" :key="post.id" class="post-card">
            <!-- Text content: left side -->
            <div class="post-card__text">
              <!-- Title -->
              <h2 class="post-card__title">
                <router-link
                  :to="toInternalPath(post.link)"
                  v-html="post.title.rendered"
                ></router-link>
              </h2>

              <!-- Excerpt -->
              <p v-if="post.excerpt?.rendered" class="post-card__excerpt" v-html="post.excerpt.rendered"></p>
            </div>

            <!-- Cover: right side (only if featured image exists) -->
            <div v-if="post.featuredImage" class="post-card__cover-wrap">
              <router-link
                :to="toInternalPath(post.link)"
                :aria-label="post.title.rendered"
                class="post-card__cover-link"
              >
                <img :src="post.featuredImage" alt="" loading="lazy" class="post-card__cover" />
              </router-link>
            </div>

            <!-- Meta badge — top-right overlay (bare text when no cover image) -->
            <div :class="['post-card__meta', { 'post-card__meta--bare': !post.featuredImage }]">
              <time :datetime="post.date" class="post-card__meta-item">{{ formatDate(post.date) }}</time>
              <span v-if="post.readingTime" class="post-card__meta-item">{{ post.readingTime }} 分钟</span>
              <span v-if="post.viewCount !== undefined" class="post-card__meta-item">{{ post.viewCount }} 热度</span>
            </div>
          </article>
        </div>

        <!-- Loading more skeleton (shown below existing posts) -->
        <div v-if="loadingMore" class="post-list post-list--append">
          <div v-for="i in 3" :key="'sk-more-' + i" class="post-card-skeleton">
            <div class="post-card-skeleton__text">
              <div style="height: 1.125rem; width: 70%; margin-bottom: 0.5rem; border-radius: var(--radius-small, 4px); background: var(--muted); animation: pulse 1.5s ease-in-out infinite;"></div>
              <div style="height: 0.75rem; width: 100%; margin-bottom: 0.5rem; border-radius: var(--radius-small, 4px); background: var(--muted); animation: pulse 1.5s ease-in-out infinite;"></div>
              <div style="height: 0.75rem; width: 65%; margin-bottom: 0.75rem; border-radius: var(--radius-small, 4px); background: var(--muted); animation: pulse 1.5s ease-in-out infinite;"></div>
            </div>
            <div class="post-card-skeleton__cover"></div>
          </div>
        </div>

        <!-- Intersection sentinel -->
        <div
          v-if="hasMore"
          ref="sentinelRef"
          class="scroll-sentinel"
        ></div>
      </div>

      <ErrorView
        v-else-if="!initialLoading"
        illustration="blank-canvas"
        title="还没有文章"
        description="还没有文章，稍后回来看看吧。"
      />
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

@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateX(-24px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

.content-area .section-header,
.content-area .filter-bar,
.content-area .post-card,
.content-area .post-list {
  animation: none;
  opacity: 1;
  transform: none;
}

.section-header {
  animation: slideIn var(--anim-duration-enter) var(--anim-ease-enter) both;
}

.filter-bar {
  animation: slideIn var(--anim-duration-enter) var(--anim-ease-enter) both;
  animation-delay: 0.06s;
}

.post-card {
  animation: slideIn var(--anim-duration-enter) var(--anim-ease-enter) both;
}
.post-card:nth-child(1) {
  animation-delay: 0.12s;
}
.post-card:nth-child(2) {
  animation-delay: 0.18s;
}
.post-card:nth-child(3) {
  animation-delay: 0.24s;
}
.post-card:nth-child(4) {
  animation-delay: 0.30s;
}

/* Skeleton cards pulled from global pulse animation */
:deep(.post-list--append .post-card-skeleton) {
  animation: slideIn 0.4s ease both;
}

/* Sentinel — invisible trigger for IntersectionObserver */
.scroll-sentinel {
  height: 1px;
  pointer-events: none;
}
</style>
