<script setup lang="ts">
import { computed, defineAsyncComponent, defineComponent, h, ref, watch, type Component } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import {
  getThemeConfig,
  isExternalUrl,
  isSafeNavigationUrl,
  toInternalPath,
  toResolvablePath,
} from '@/lib/theme-config'
import {
  fetchContentByRestUrl,
  fetchPostCollectionByTaxonomy,
  fetchPostCollectionByDate,
  getErrorMessage,
  resolveThemePath,
  trackPostView,
} from '@/lib/wordpress'
import { withCache } from '@/lib/api-cache'
import { getContentPreview, rememberPreviews } from '@/lib/content-preview'
import { showError } from '@/lib/toast'
import type { ResolveResponse, WordPressPost } from '@/types/wordpress'
import CommentsPanel from '@/components/CommentsPanel.vue'
import AppIcon from '@/components/AppIcon.vue'
import { useSiteShell } from '@/composables/useSiteShell'
import { useContentEnhancer } from '@/composables/useContentEnhancer'
import NotFoundView from '@/views/NotFoundView.vue'
import ErrorView from '@/components/ErrorView.vue'
import StaticFallback from '@/components/StaticFallback.vue'
import { useStaticFallback } from '@/composables/useStaticFallback'
import TermArchive from '@/views/TermArchive.vue'
import { getPreloadedSpecialPage } from '@/lib/special-page-loader'

const AsyncPageLoading = defineComponent({
  name: 'AsyncPageLoading',
  setup() {
    return () => h('div', { class: 'content-view__loading', role: 'status' }, '正在加载…')
  },
})

const ShuoshuoView = defineAsyncComponent({
  loader: () => import('@/views/ShuoshuoView.vue'),
  loadingComponent: AsyncPageLoading,
  delay: 0,
})
const ArchivesView = defineAsyncComponent({
  loader: () => import('@/views/ArchivesView.vue'),
  loadingComponent: AsyncPageLoading,
  delay: 0,
})
const AboutView = defineAsyncComponent({
  loader: () => import('@/views/AboutView.vue'),
  loadingComponent: AsyncPageLoading,
  delay: 0,
})
const LinksView = defineAsyncComponent({
  loader: () => import('@/views/LinksView.vue'),
  loadingComponent: AsyncPageLoading,
  delay: 0,
})

const specialPageMap: Record<string, Component> = {
  '/shuoshuo': ShuoshuoView,
  '/about': AboutView,
  '/archives': ArchivesView,
  '/links': LinksView,
}

/**
 * Normalize route path: trim trailing slash (except for root `/`)
 * so `/about/` and `/about` map to the same special page / cache key.
 */
const normalizedPath = computed(() => {
  const p = route.path
  return p.length > 1 ? p.replace(/\/+$/, '') : p
})

const route = useRoute()
const router = useRouter()
const { siteInfo } = useSiteShell()

const contentType = ref<ResolveResponse['type']>('home')
const errorMessage = ref('')
const loading = ref(false)
const { staticFallbackHtml } = useStaticFallback()
const postData = ref<WordPressPost | null>(null)
const postContent = computed(() => postData.value?.content?.rendered ?? null)
useContentEnhancer(postContent)

// 列表页写入的预览信息（标题/特色图）— 加载期间精准渲染骨架：
// 有特色图直接展示真图+真标题，无特色图则不渲染 cover 骨架
const preview = computed(() => (loading.value ? getContentPreview(route.path) : undefined))

const termName = ref('')
const termTaxonomy = ref('')
const termPosts = ref<WordPressPost[]>([])
const termPostsLoading = ref(false)

const formatDate = (dateString: string) =>
  new Intl.DateTimeFormat('zh-CN', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  }).format(new Date(dateString))

const primaryCategory = computed<{ name: string; slug: string } | null>(() => {
  const post = postData.value as (WordPressPost & { _embedded?: Record<string, unknown> }) | null
  const terms =
    (post?._embedded?.['wp:term'] as
      | Array<Array<{ taxonomy?: string; name?: string; slug?: string }>>
      | undefined) || []
  for (const group of terms) {
    const cat = group.find((term) => term?.taxonomy === 'category' && typeof term.name === 'string')
    if (cat?.name && cat?.slug) return { name: cat.name, slug: cat.slug }
  }
  return null
})

const featuredImageUrl = computed(() => {
  const post = postData.value as (WordPressPost & { _embedded?: Record<string, unknown> }) | null
  return (
    (post?._embedded?.['wp:featuredmedia'] as Array<{ source_url?: string }> | undefined)?.[0]
      ?.source_url ||
    postData.value?.featuredImage ||
    ''
  )
})

const specialPageComponent = computed(
  () => getPreloadedSpecialPage(normalizedPath.value) || specialPageMap[normalizedPath.value] || null,
)

const postTags = computed(() => {
  const post = postData.value as (WordPressPost & { _embedded?: Record<string, unknown> }) | null
  const terms =
    (post?._embedded?.['wp:term'] as
      | Array<Array<{ taxonomy?: string; name?: string; slug?: string; link?: string }>>
      | undefined) || []
  for (const group of terms) {
    const tags = group
      .filter((term) => term?.taxonomy === 'post_tag' && typeof term.name === 'string')
      .map((term) => ({
        name: term.name as string,
        link: term.link || (term.slug ? `/tag/${term.slug}/` : ''),
      }))
    if (tags.length > 0) return tags
  }
  return [] as { name: string; link: string }[]
})

const showMeta = computed(
  () => getThemeConfig().features?.articleMeta || ({} as Record<string, boolean>),
)

const authorName = computed(() => {
  const post = postData.value as (WordPressPost & { _embedded?: Record<string, unknown> }) | null
  const authors = post?._embedded?.['author'] as Array<{ name?: string }> | undefined
  return authors?.[0]?.name || ''
})

function formatWordCount(count?: number | null): string {
  if (!count) return '0'
  if (count >= 10000) return (count / 10000).toFixed(1).replace(/\.0$/, '') + '万'
  return count.toLocaleString()
}

const loadTermPosts = async (taxonomy: string, id: number) => {
  termPostsLoading.value = true
  termPosts.value = []
  try {
    termPosts.value = await fetchPostCollectionByTaxonomy(taxonomy, id)
    rememberPreviews(termPosts.value)
  } catch (error) {
    errorMessage.value = getErrorMessage(error, '归档内容加载失败，请稍后重试。')
    showError(errorMessage.value)
  } finally {
    termPostsLoading.value = false
  }
}

const loadDatePosts = async (year: number, month?: number) => {
  termPostsLoading.value = true
  termPosts.value = []
  try {
    termPosts.value = await fetchPostCollectionByDate(year, month)
    rememberPreviews(termPosts.value)
  } catch (error) {
    errorMessage.value = getErrorMessage(error, '归档内容加载失败，请稍后重试。')
    showError(errorMessage.value)
  } finally {
    termPostsLoading.value = false
  }
}

const loadCurrentContent = async () => {
  errorMessage.value = ''
  postData.value = null
  termName.value = ''
  termTaxonomy.value = ''
  termPosts.value = []
  loading.value = true

  try {
    const resolved = await withCache(
      resolveThemePath,
      `resolve:${normalizedPath.value}`,
      30_000,
    )(route.path)
    contentType.value = resolved.type

    if (
      ('post' === resolved.type || 'page' === resolved.type || 'shuoshuo' === resolved.type) &&
      resolved.restUrl
    ) {
      postData.value = await withCache(
        fetchContentByRestUrl,
        `post:${resolved.restUrl}`,
        600_000,
      )(resolved.restUrl)
      if (!postData.value) {
        contentType.value = '404'
        return
      }
      if (('post' === resolved.type || 'shuoshuo' === resolved.type) && postData.value?.id) {
        void trackPostView(postData.value.id)
      }
      return
    }

    if ('term' === resolved.type && resolved.id && resolved.taxonomy) {
      termName.value = resolved.name || '归档'
      termTaxonomy.value = resolved.taxonomy
      await loadTermPosts(resolved.taxonomy, resolved.id)
      return
    }

    if ('date' === resolved.type && resolved.year) {
      termName.value = resolved.name || '日期归档'
      termTaxonomy.value = 'date'
      contentType.value = 'term' // 复用 TermArchive 展示
      await loadDatePosts(resolved.year, resolved.month || undefined)
      return
    }

    if (!['404', 'error', 'home'].includes(resolved.type)) {
      showError(resolved.message || '页面解析失败，请稍后重试。')
      contentType.value = 'error'
    }

    // Fallback: if path resolution fails, try direct post ID from query param.
    // Use relative URL to go through Vite proxy (avoids CORS from PHP-injected absolute URL)
    if (contentType.value === '404' && route.query.postId) {
      const id = Number(route.query.postId)
      if (id > 0) {
        const directPost = await fetchContentByRestUrl(`/wp-json/wp/v2/posts/${id}?_embed=1`)
        if (directPost) {
          postData.value = directPost
          contentType.value = 'post'
          void trackPostView(id)
        }
      }
    }
  } catch (error) {
    showError(getErrorMessage(error, '页面解析失败，请稍后重试。'))
    contentType.value = 'error'
  } finally {
    loading.value = false
  }
}

const handleContentClick = (event: MouseEvent) => {
  const target = event.target
  if (!(target instanceof HTMLElement)) return

  const anchor = target.closest<HTMLAnchorElement>('a')
  if (!anchor) return

  const href = anchor.getAttribute('href')
  if (!href || !isSafeNavigationUrl(href)) {
    event.preventDefault()
    return
  }

  if (
    event.defaultPrevented ||
    event.button !== 0 ||
    event.metaKey ||
    event.ctrlKey ||
    event.shiftKey ||
    event.altKey
  )
    return

  if (
    href.startsWith('#') ||
    anchor.target ||
    anchor.hasAttribute('download') ||
    anchor.hasAttribute('data-fancybox') ||
    isExternalUrl(href)
  )
    return

  event.preventDefault()
  void router.push(toInternalPath(href))
}

watch(
  () => toResolvablePath(route.path),
  () => {
    void loadCurrentContent()
  },
  { immediate: true },
)
</script>

<template>
  <section class="content-view" @click.capture="handleContentClick">
    <template v-if="specialPageComponent">
      <component :is="specialPageComponent" />
    </template>
    <template v-else>
      <StaticFallback
        v-if="'error' === contentType && staticFallbackHtml"
        :html="staticFallbackHtml"
      />
      <ErrorView
        v-else-if="'error' === contentType"
        illustration="warning"
        title="页面加载失败"
        description="抱歉，页面暂时无法加载，请稍后重试。"
      />
      <article v-else-if="loading" class="single-post">
        <!-- 有预览且含特色图：直接展示真实封面+标题，只骨架化 meta -->
        <div v-if="preview?.cover" class="single-post__cover">
          <div class="single-post__cover-img">
            <img :src="preview.cover" alt="" />
          </div>
          <div class="single-post__cover-info">
            <div class="single-post__cover-title">
              <h1 v-html="preview.title"></h1>
            </div>
            <div class="single-post__cover-meta">
              <div
                style="
                  height: 0.75rem;
                  width: 6rem;
                  border-radius: var(--radius-small, 4px);
                  background: rgba(255, 255, 255, 0.25);
                  animation: pulse 1.5s ease-in-out infinite;
                "
              ></div>
              <div
                style="
                  height: 0.75rem;
                  width: 4rem;
                  border-radius: var(--radius-small, 4px);
                  background: rgba(255, 255, 255, 0.25);
                  animation: pulse 1.5s ease-in-out infinite;
                "
              ></div>
            </div>
          </div>
        </div>
        <!-- 有预览但无特色图：不渲染 cover 骨架，直接真标题（与真实渲染同结构，切换无跳动） -->
        <header v-else-if="preview" class="single-post__header">
          <h1 class="single-post__header-title" v-html="preview.title"></h1>
          <div class="single-post__header-meta">
            <div
              style="
                height: 0.75rem;
                width: 6rem;
                border-radius: var(--radius-small, 4px);
                background: var(--muted);
                animation: pulse 1.5s ease-in-out infinite;
              "
            ></div>
            <div
              style="
                height: 0.75rem;
                width: 4rem;
                border-radius: var(--radius-small, 4px);
                background: var(--muted);
                animation: pulse 1.5s ease-in-out infinite;
              "
            ></div>
          </div>
        </header>
        <!-- 无预览（直接进入/刷新）：保留通用 cover 骨架 -->
        <div
          v-else
          class="single-post__cover"
          style="background: var(--muted); animation: pulse 1.5s ease-in-out infinite"
        >
          <div class="single-post__cover-info">
            <div class="single-post__cover-title">
              <div
                style="
                  height: 1.5rem;
                  width: 65%;
                  margin: 0 auto;
                  border-radius: var(--radius-small, 4px);
                  background: rgba(255, 255, 255, 0.25);
                "
              ></div>
            </div>
            <div class="single-post__cover-meta">
              <div
                style="
                  height: 0.75rem;
                  width: 6rem;
                  border-radius: var(--radius-small, 4px);
                  background: rgba(255, 255, 255, 0.25);
                "
              ></div>
              <div
                style="
                  height: 0.75rem;
                  width: 4rem;
                  border-radius: var(--radius-small, 4px);
                  background: rgba(255, 255, 255, 0.25);
                "
              ></div>
            </div>
          </div>
        </div>
        <div class="single-post__body">
          <div style="display: flex; flex-direction: column; gap: 0.75rem">
            <div
              style="
                height: 0.75rem;
                width: 100%;
                border-radius: var(--radius-small, 4px);
                background: var(--muted);
                animation: pulse 1.5s ease-in-out infinite;
              "
            ></div>
            <div
              style="
                height: 0.75rem;
                width: 100%;
                border-radius: var(--radius-small, 4px);
                background: var(--muted);
                animation: pulse 1.5s ease-in-out infinite;
              "
            ></div>
            <div
              style="
                height: 0.75rem;
                width: 85%;
                border-radius: var(--radius-small, 4px);
                background: var(--muted);
                animation: pulse 1.5s ease-in-out infinite;
              "
            ></div>
            <div
              style="
                height: 0.75rem;
                width: 100%;
                border-radius: var(--radius-small, 4px);
                background: var(--muted);
                animation: pulse 1.5s ease-in-out infinite;
              "
            ></div>
            <div
              style="
                height: 0.75rem;
                width: 60%;
                border-radius: var(--radius-small, 4px);
                background: var(--muted);
                animation: pulse 1.5s ease-in-out infinite;
              "
            ></div>
            <div
              style="
                height: 0.75rem;
                width: 100%;
                border-radius: var(--radius-small, 4px);
                background: var(--muted);
                animation: pulse 1.5s ease-in-out infinite;
              "
            ></div>
            <div
              style="
                height: 0.75rem;
                width: 70%;
                border-radius: var(--radius-small, 4px);
                background: var(--muted);
                animation: pulse 1.5s ease-in-out infinite;
              "
            ></div>
            <div
              style="
                height: 0.75rem;
                width: 100%;
                border-radius: var(--radius-small, 4px);
                background: var(--muted);
                animation: pulse 1.5s ease-in-out infinite;
              "
            ></div>
            <div
              style="
                height: 0.75rem;
                width: 90%;
                border-radius: var(--radius-small, 4px);
                background: var(--muted);
                animation: pulse 1.5s ease-in-out infinite;
              "
            ></div>
          </div>
        </div>
      </article>
      <NotFoundView v-else-if="'404' === contentType" />
      <TermArchive
        v-else-if="'term' === contentType"
        :term-name="termName"
        :term-taxonomy="termTaxonomy"
        :term-posts-loading="termPostsLoading"
        :error-message="errorMessage"
        :term-posts="termPosts"
      />
      <article v-else-if="postData" class="single-post">
        <div v-if="featuredImageUrl" class="single-post__cover">
          <div class="single-post__cover-img">
            <img :src="featuredImageUrl" alt="" loading="lazy" />
          </div>
          <div class="single-post__cover-info">
            <div class="single-post__cover-title">
              <h1 v-html="postData.title.rendered"></h1>
            </div>
            <div class="single-post__cover-meta">
              <span v-if="showMeta?.showReadingTime && postData.readingTime" class="meta-item">
                <AppIcon name="time" :size="16" />
                <span>{{ postData.readingTime }} 分钟</span>
              </span>
              <span v-if="showMeta?.showPublishDate" class="meta-item">
                <AppIcon name="calendar" :size="16" />
                <time :datetime="postData.date">{{ formatDate(postData.date) }}</time>
              </span>
              <span v-if="showMeta?.showWordCount && postData.wordCount" class="meta-item">
                <AppIcon name="file" :size="16" />
                <span>{{ formatWordCount(postData.wordCount) }}</span>
              </span>
              <span v-if="showMeta?.showModifiedDate && postData.modified" class="meta-item">
                <AppIcon name="edit" :size="16" />
                <time :datetime="postData.modified">{{ formatDate(postData.modified) }}</time>
              </span>
              <span
                v-if="showMeta?.showCommentCount && postData.commentCount !== undefined"
                class="meta-item"
              >
                <AppIcon name="message-dots" :size="16" />
                <span>{{ postData.commentCount }} 条评论</span>
              </span>
              <span
                v-if="showMeta?.showViewCount && postData.viewCount !== undefined"
                class="meta-item"
              >
                <AppIcon name="show" :size="16" />
                <span>{{ postData.viewCount }} 次浏览</span>
              </span>
              <span v-if="showMeta?.showCategory && primaryCategory" class="meta-item">
                <AppIcon name="folder" :size="16" />
                <router-link :to="`/categories/${primaryCategory.slug}`">{{
                  primaryCategory.name
                }}</router-link>
              </span>
              <span v-if="showMeta?.showAuthor && authorName" class="meta-item">
                <AppIcon name="user" :size="16" />
                <span>{{ authorName }}</span>
              </span>
              <span v-if="showMeta?.showEditLink && postData.editUrl" class="meta-item">
                <AppIcon name="edit-alt" :size="16" />
                <a :href="postData.editUrl" target="_blank" rel="noopener">编辑文章</a>
              </span>
            </div>
          </div>
        </div>
        <div v-else class="single-post__header">
          <h1 class="single-post__header-title" v-html="postData.title.rendered"></h1>
          <div class="single-post__header-meta">
            <span v-if="showMeta?.showReadingTime && postData.readingTime" class="meta-item">
              <AppIcon name="time" :size="16" />
              <span>{{ postData.readingTime }} 分钟</span>
            </span>
            <span v-if="showMeta?.showPublishDate" class="meta-item">
              <AppIcon name="calendar" :size="16" />
              <time :datetime="postData.date">{{ formatDate(postData.date) }}</time>
            </span>
            <span v-if="showMeta?.showWordCount && postData.wordCount" class="meta-item">
              <AppIcon name="file" :size="16" />
              <span>{{ formatWordCount(postData.wordCount) }}</span>
            </span>
            <span v-if="showMeta?.showModifiedDate && postData.modified" class="meta-item">
              <AppIcon name="edit" :size="16" />
              <time :datetime="postData.modified">{{ formatDate(postData.modified) }}</time>
            </span>
            <span
              v-if="showMeta?.showCommentCount && postData.commentCount !== undefined"
              class="meta-item"
            >
              <AppIcon name="message-dots" :size="16" />
              <span>{{ postData.commentCount }} 条评论</span>
            </span>
            <span
              v-if="showMeta?.showViewCount && postData.viewCount !== undefined"
              class="meta-item"
            >
              <AppIcon name="show" :size="16" />
              <span>{{ postData.viewCount }} 次浏览</span>
            </span>
            <span v-if="showMeta?.showCategory && primaryCategory" class="meta-item">
              <AppIcon name="folder" :size="16" />
              <router-link :to="`/categories/${primaryCategory.slug}`">{{
                primaryCategory.name
              }}</router-link>
            </span>
            <span v-if="showMeta?.showAuthor && authorName" class="meta-item">
              <AppIcon name="user" :size="16" />
              <span>{{ authorName }}</span>
            </span>
            <span v-if="showMeta?.showEditLink && postData.editUrl" class="meta-item">
              <AppIcon name="edit-alt" :size="16" />
              <a :href="postData.editUrl" target="_blank" rel="noopener">编辑文章</a>
            </span>
          </div>
        </div>
        <div class="single-post__body">
          <div class="prose-content" v-html="postData.content?.rendered"></div>
          <footer class="single-post__footer">
            <div v-if="postTags.length > 0" class="single-post__tags">
              <router-link v-for="tag in postTags" :key="tag.name" :to="toInternalPath(tag.link)"
                >#{{ tag.name }}</router-link
              >
            </div>
          </footer>
        </div>
        <CommentsPanel
          :post-id="postData.id"
          :enabled="'open' === (postData.comment_status || 'closed')"
          :form-settings="siteInfo.comments!"
        />
      </article>
    </template>
  </section>
</template>

<style scoped>
.content-view {
  --anim-ease-enter: cubic-bezier(0.16, 1, 0.3, 1);
  --anim-ease-hover: cubic-bezier(0.34, 1.56, 0.64, 1);
  --anim-duration-enter: 0.5s;
  --anim-duration-hover: 0.35s;
}

.content-view__loading {
  min-height: 16rem;
  display: grid;
  place-items: center;
  color: var(--secondary, #888);
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

/* Staggered entrance — cover/header, body, footer animate in sequence */
.single-post__cover,
.single-post__header {
  animation: slideIn var(--anim-duration-enter) var(--anim-ease-enter) both;
}

.single-post__body {
  animation: slideIn var(--anim-duration-enter) var(--anim-ease-enter) both;
  animation-delay: 0.12s;
}

.single-post__footer {
  animation: slideIn var(--anim-duration-enter) var(--anim-ease-enter) both;
  animation-delay: 0.24s;
}

/* ----- Cover meta (on featured image) — white on dark overlay ----- */
.single-post__cover-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 0.375rem 1rem;
  font-size: 0.8125rem;
  color: rgba(255 255 255 / 0.88);
  text-shadow: 0 1px 6px rgba(0 0 0 / 0.45);
}

/* ----- Header meta (no image) — adapts to theme ----- */
.single-post__header-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 0.375rem 1rem;
  font-size: 0.8125rem;
  color: var(--muted-foreground, #888);
}

.meta-item {
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
  white-space: nowrap;
}

.meta-item svg {
  width: 1rem;
  height: 1rem;
  flex-shrink: 0;
}

.meta-item a {
  color: inherit;
  text-decoration: none;
}

.meta-item a:hover {
  color: var(--primary);
}
</style>
