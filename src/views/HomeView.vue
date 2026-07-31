<script setup lang="ts">
import { computed, onMounted, ref, onBeforeUnmount, watch, nextTick } from 'vue'
import { useRoute } from 'vue-router'
import { useSiteShell } from '@/composables/useSiteShell'
import { fetchCollection, fetchCategories, getErrorMessage, resolveThemePath, fetchContentByRestUrl } from '@/lib/wordpress'
import { withCache } from '@/lib/api-cache'
import { toInternalPath } from '@/lib/theme-config'
import { rememberPreviews } from '@/lib/content-preview'
import { useSkeletonSize } from '@/composables/useSkeletonSize'
import { showError } from '@/lib/toast'
import { getThemeConfig } from '@/lib/theme-config'
import type { PagedPostCollection, WordPressPost, WordPressCategory } from '@/types/wordpress'
import ErrorView from '@/components/ErrorView.vue'
import AppIcon from '@/components/AppIcon.vue'

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

const perPageCount = computed(() => siteInfo.value.collections?.homePostCount ?? 6)

// 骨架尺寸记忆：用上次真实卡片的高度/形态渲染骨架，避免加载完成时跳动
const { size: cardSize, measure: measureCard } = useSkeletonSize('st_sk_post_card')
const skeletonStyle = computed(() =>
  cardSize.value ? { height: cardSize.value.h + 'px', minHeight: cardSize.value.h + 'px' } : undefined,
)

const metaConfig = computed(() => getThemeConfig().features?.meta)

/** 卡片是否有任何 meta 需要展示 — 全部关闭时不渲染胶囊（否则出现空白悬浮胶囊） */
const hasCardMeta = (post: WordPressPost): boolean => {
  const m = metaConfig.value
  if (!m) return false
  return !!(
    (m.showCategory && post.categories?.[0]) ||
    m.showPublishDate ||
    (m.showModifiedDate && post.modified) ||
    (m.showCommentCount && post.commentCount !== undefined) ||
    (m.showViewCount && post.viewCount !== undefined) ||
    (m.showReadingTime && post.readingTime) ||
    (m.showWordCount && post.wordCount)
  )
}

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

// ----- 无限滚动（基于 scroll + requestAnimationFrame）-----
let scrollRafId: number | null = null

function checkScrollAndLoad() {
  scrollRafId = null
  if (!hasMore.value || loadingMore.value) return
  const distFromBottom = document.documentElement.scrollHeight - (window.innerHeight + window.scrollY)
  // 距离底部 1200px 内触发加载，给足够余量应对快速滚动
  if (distFromBottom < 1200) {
    loadMorePosts()
  }
}

function onScroll() {
  if (scrollRafId !== null) return
  scrollRafId = requestAnimationFrame(checkScrollAndLoad)
}

function startInfiniteScroll() {
  window.addEventListener('scroll', onScroll, { passive: true })
  // 初始检查：如果页面已滚动过底部则立即触发
  checkScrollAndLoad()
}

function stopInfiniteScroll() {
  window.removeEventListener('scroll', onScroll)
  if (scrollRafId !== null) {
    cancelAnimationFrame(scrollRafId)
    scrollRafId = null
  }
}

onMounted(async () => {
  await loadHomepageData()
  nextTick(() => startInfiniteScroll())
})

onBeforeUnmount(() => {
  stopInfiniteScroll()
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
    categories.value = await withCache(fetchCategories, 'categories')()
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
      // 切换分类后检查是否需要立即加载更多
      requestAnimationFrame(checkScrollAndLoad)
    })
}

async function loadHomepageData() {
  initialLoading.value = true
  errorMessage.value = ''
  page.value = 1
  latestPosts.value = []

  try {
    await ensureLoaded()
    // Fire categories in parallel with first page — they don't block post rendering
    const catPromise = loadCategories()
    await loadPage(1)
    await catPromise
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
    requestAnimationFrame(checkScrollAndLoad)
  }
}

async function loadPage(pageNum: number) {
  // Capture slug at call time — if it changes mid-fetch, discard results
  const capturedSlug = categorySlug.value
  const slug = capturedSlug || undefined

  let termId: number | undefined
  if (slug) {
    const found = categories.value.find((c) => c.slug === slug)
    if (found) termId = found.id
  }

  const postsResponse: PagedPostCollection = await fetchCollection('post', {
    limit: perPageCount.value,
    page: pageNum,
    taxonomy: termId ? 'category' : undefined,
    termId,
  })

  const newItems = postsResponse.items || []

  if (pageNum === 1) {
    // Stale check: if user clicked another category while we were fetching, discard
    if (capturedSlug !== categorySlug.value) return
    latestPosts.value = newItems
    // 测量首张真实卡片，供下次骨架使用真实尺寸
    void measureCard('.post-list__grid .post-card', '.post-card__cover')
  } else {
    latestPosts.value = [...latestPosts.value, ...newItems]
  }

  // 写入详情页预览缓存（标题/特色图），让文章页骨架精准渲染
  rememberPreviews(newItems)

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
        const resolved = await withCache(resolveThemePath, `resolve:${path}`, 30_000)(path)
        if (resolved.restUrl) {
          await withCache(fetchContentByRestUrl, `post:${resolved.restUrl}`, 600_000)(resolved.restUrl)
        }
      } catch { /* 预取失败静默忽略 */ }
    }, 200),
  )
}

function cancelPrefetch(post: WordPressPost) {
  const t = prefetchTimers.get(post.id)
  if (t) { clearTimeout(t); prefetchTimers.delete(post.id) }
}

// ----- 分类筛选按钮样式 -----
const filterBtnBase =
  'inline-flex cursor-pointer items-center rounded-full border-none px-3.5 py-[5px] text-[13px] font-medium transition-all duration-200 select-none dark:shadow-[inset_0_1px_0_0_#fff3]'
const filterBtnInactive = `${filterBtnBase} bg-muted text-secondary hover:bg-[color-mix(in_srgb,var(--muted)_75%,var(--foreground))] hover:text-foreground dark:bg-[#333] dark:text-[#ccc] dark:hover:bg-[#444] dark:hover:text-foreground`
const filterBtnActive = `${filterBtnBase} bg-primary text-white hover:opacity-90 dark:text-[#1a1a1a]`

</script>

<template>
  <div class="home-content p-[25px] max-[800px]:p-5 max-sm:p-[15px]">
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
      <div class="mb-6 flex flex-wrap gap-1.5">
        <button
          :class="categorySlug === '' ? filterBtnActive : filterBtnInactive"
          @click="onCategoryClick('all')"
        >
          全部
        </button>
        <button
          v-for="cat in categories"
          :key="cat.id"
          :class="categorySlug === cat.slug ? filterBtnActive : filterBtnInactive"
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
          <div v-for="i in perPageCount" :key="'sk-init-' + i" class="post-card-skeleton" :style="skeletonStyle">
            <div v-if="cardSize?.cover !== false" class="post-card-skeleton__cover"></div>
            <div class="post-card-skeleton__text">
              <div class="mb-2 h-[1.125rem] w-[70%] animate-card-pulse rounded-small bg-muted"></div>
              <div class="mb-2 h-3 w-full animate-card-pulse rounded-small bg-muted"></div>
              <div class="mb-3 h-3 w-[65%] animate-card-pulse rounded-small bg-muted"></div>
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
              <div v-if="hasCardMeta(post)" class="post-card__meta">
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
                  <span v-if="post.isSticky" class="post-card__sticky">
                    <AppIcon name="pin" filled :size="13" />置顶
                  </span>
                  <router-link :to="toInternalPath(post.link)">
                    <span v-html="post.title.rendered"></span>
                  </router-link>
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
        <div v-if="loadingMore" class="post-list mt-6">
          <div v-for="i in 3" :key="'sk-more-' + i" class="post-card-skeleton" :style="skeletonStyle">
            <div v-if="cardSize?.cover !== false" class="post-card-skeleton__cover"></div>
            <div class="post-card-skeleton__text">
              <div class="mb-2 h-[1.125rem] w-[70%] animate-card-pulse rounded-small bg-muted"></div>
              <div class="mb-2 h-3 w-full animate-card-pulse rounded-small bg-muted"></div>
              <div class="mb-3 h-3 w-[65%] animate-card-pulse rounded-small bg-muted"></div>
            </div>
          </div>
        </div>

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
