<script setup lang="ts">
import { computed, defineAsyncComponent, defineComponent, h, ref, watch, type Component } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import {
  getThemeConfig,
  isArchivePath,
  isExternalUrl,
  isSafeNavigationUrl,
  toInternalPath,
  toResolvablePath,
} from '@/lib/theme-config'
import { buildRestUrl } from '@/lib/api-client'
import {
  fetchContentByRestUrl,
  fetchPostCollectionByTaxonomy,
  fetchPostCollectionByDate,
  getErrorMessage,
  resolveThemePath,
  trackPostView,
} from '@/lib/wordpress'
import { resolveArticleLicense } from '@/lib/license'
import { getPreviewPayload } from '@/lib/preview'
import { primeCache, withCache } from '@/lib/api-cache'
import { resolveCacheKey } from '@/lib/content-prefetch'
import { getContentPreview, rememberPreviews } from '@/lib/content-preview'
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
import PostListSkeleton from '@/components/PostListSkeleton.vue'
import { getPreloadedSpecialPage } from '@/lib/special-page-loader'
import { useSkeletonSize } from '@/composables/useSkeletonSize'
import { useToast, StTag } from '@/ui'
import { BlockContent } from '@/blocks'

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
const toast = useToast()

const contentType = ref<ResolveResponse['type']>('home')
const errorMessage = ref('')
const loading = ref(false)
const { staticFallbackHtml } = useStaticFallback()
const postData = ref<WordPressPost | null>(null)
const postContent = computed(() => postData.value?.content?.rendered ?? null)
useContentEnhancer(postContent)

// 文章许可声明（后台「页脚与备案 → 文章许可」，'none' 时不渲染）
const articleLicense = computed(() => resolveArticleLicense(siteInfo.value.theme?.articleLicense))

// 列表页写入的预览信息（标题/特色图）— 加载期间精准渲染骨架：
// 有特色图直接展示真图+真标题，无特色图则不渲染 cover 骨架
const preview = computed(() => (loading.value ? getContentPreview(route.path) : undefined))

const termName = ref('')
const termTaxonomy = ref('')
const termPosts = ref<WordPressPost[]>([])
const termPostsLoading = ref(false)

// 解析 URL 期间的类型未知：按路径先判断是否为列表型归档，
// 归档用列表骨架占位，避免误套文章正文骨架。
const { size: archiveCardSize } = useSkeletonSize('st_sk_post_card')
const loadingArchive = computed(() => loading.value && isArchivePath(normalizedPath.value))

const formatDate = (dateString: string) =>
  new Intl.DateTimeFormat('zh-CN', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  }).format(new Date(dateString))

/** 版权信息里展示的链接：去掉 %xx 编码，便于阅读（非法编码时原样返回） */
const readableLink = (link: string) => {
  try {
    return decodeURI(link)
  } catch {
    return link
  }
}

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

/** 预览模式下隐藏评论区（草稿/修订还没对外发布） */
const isPreviewMode = computed(() => Boolean(getPreviewPayload()))

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
    toast.error(errorMessage.value)
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
    toast.error(errorMessage.value)
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
  // 类型未知期间回到中性态，避免沿用上一页的 contentType 触发错误分支
  // （如归档 → 归档时闪现「暂无内容」空状态）
  contentType.value = 'home'
  loading.value = true

  // 预览请求（?p=ID&preview=true）：正文由 PHP 直出，避免路径解析把
  // 查询串剥掉后落到首页；同时跳过阅读量统计与评论（草稿尚未公开）。
  const preview = getPreviewPayload()
  if (preview) {
    postData.value = {
      id: preview.id,
      date: preview.date,
      modified: preview.modified,
      slug: '',
      link: preview.link,
      status: preview.status,
      comment_status: 'closed',
      title: { rendered: preview.title },
      content: { rendered: preview.content },
      _embedded: {
        author: [{ name: preview.authorName }],
        'wp:term': [
          preview.tags.map((tag) => ({
            taxonomy: 'post_tag',
            name: tag.name,
            link: tag.link,
          })),
        ],
      },
    } as unknown as WordPressPost
    contentType.value = 'post'
    loading.value = false
    return
  }

  try {
    const resolved = await withCache(
      resolveThemePath,
      resolveCacheKey(route.fullPath),
      30_000,
    )(toResolvablePath(route.fullPath))
    contentType.value = resolved.type

    if (
      ('post' === resolved.type || 'page' === resolved.type || 'shuoshuo' === resolved.type) &&
      resolved.restUrl
    ) {
      const contentKey = `post:${resolved.restUrl}`
      if (resolved.payload) {
        // resolve-url 已经把正文一起返回，省掉一次 REST 往返
        primeCache(contentKey, resolved.payload, 600_000)
        postData.value = resolved.payload
      } else {
        postData.value = await withCache(fetchContentByRestUrl, contentKey, 600_000)(
          resolved.restUrl,
        )
      }
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
      toast.error(resolved.message || '页面解析失败，请稍后重试。')
      contentType.value = 'error'
    }

    // Fallback: if path resolution fails, try direct post ID from query param.
    // Use relative URL to go through Vite proxy (avoids CORS from PHP-injected absolute URL)
    if (contentType.value === '404' && route.query.postId) {
      const id = Number(route.query.postId)
      if (id > 0) {
        const directPost = await fetchContentByRestUrl(
          buildRestUrl(`wp/v2/posts/${id}`, { _embed: 1 }),
        )
        if (directPost) {
          postData.value = directPost
          contentType.value = 'post'
          void trackPostView(id)
        }
      }
    }
  } catch (error) {
    toast.error(getErrorMessage(error, '页面解析失败，请稍后重试。'))
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
  // 用 fullPath：朴素固定链接（?p=1 / ?cat=1）只变查询串，路径始终是 "/"
  () => toResolvablePath(route.fullPath),
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
      <TermArchive
        v-else-if="'term' === contentType"
        :term-name="termName"
        :term-taxonomy="termTaxonomy"
        :term-posts-loading="termPostsLoading"
        :error-message="errorMessage"
        :term-posts="termPosts"
      />
      <!-- 归档页解析中：先按路径判定为列表型归档，用列表骨架占位 -->
      <section v-else-if="loadingArchive" class="term-archive term-archive--skeleton" aria-busy="true">
        <header class="section-header">
          <h1>
            <span class="section-header__title"></span>
            <span class="section-header__subtitle"></span>
          </h1>
        </header>
        <PostListSkeleton
          :count="3"
          :card-height="archiveCardSize?.h"
          :show-cover="archiveCardSize?.cover !== false"
        />
      </section>
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
                  height: 1.25rem;
                  width: 6rem;
                  border-radius: var(--radius-small, 4px);
                  background: rgba(255, 255, 255, 0.25);
                  animation: var(--animate-card-pulse);
                "
              ></div>
              <div
                style="
                  height: 1.25rem;
                  width: 4rem;
                  border-radius: var(--radius-small, 4px);
                  background: rgba(255, 255, 255, 0.25);
                  animation: var(--animate-card-pulse);
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
                height: 1.25rem;
                width: 6rem;
                border-radius: var(--radius-small, 4px);
                background: var(--muted);
                animation: var(--animate-card-pulse);
              "
            ></div>
            <div
              style="
                height: 1.25rem;
                width: 4rem;
                border-radius: var(--radius-small, 4px);
                background: var(--muted);
                animation: var(--animate-card-pulse);
              "
            ></div>
          </div>
        </header>
        <!-- 无预览（直接进入/刷新）：保留通用 cover 骨架 -->
        <div
          v-else
          class="single-post__cover"
          style="background: var(--muted); animation: var(--animate-card-pulse)"
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
                animation: var(--animate-card-pulse);
              "
            ></div>
            <div
              style="
                height: 0.75rem;
                width: 100%;
                border-radius: var(--radius-small, 4px);
                background: var(--muted);
                animation: var(--animate-card-pulse);
              "
            ></div>
            <div
              style="
                height: 0.75rem;
                width: 85%;
                border-radius: var(--radius-small, 4px);
                background: var(--muted);
                animation: var(--animate-card-pulse);
              "
            ></div>
            <div
              style="
                height: 0.75rem;
                width: 100%;
                border-radius: var(--radius-small, 4px);
                background: var(--muted);
                animation: var(--animate-card-pulse);
              "
            ></div>
            <div
              style="
                height: 0.75rem;
                width: 60%;
                border-radius: var(--radius-small, 4px);
                background: var(--muted);
                animation: var(--animate-card-pulse);
              "
            ></div>
            <div
              style="
                height: 0.75rem;
                width: 100%;
                border-radius: var(--radius-small, 4px);
                background: var(--muted);
                animation: var(--animate-card-pulse);
              "
            ></div>
            <div
              style="
                height: 0.75rem;
                width: 70%;
                border-radius: var(--radius-small, 4px);
                background: var(--muted);
                animation: var(--animate-card-pulse);
              "
            ></div>
            <div
              style="
                height: 0.75rem;
                width: 100%;
                border-radius: var(--radius-small, 4px);
                background: var(--muted);
                animation: var(--animate-card-pulse);
              "
            ></div>
            <div
              style="
                height: 0.75rem;
                width: 90%;
                border-radius: var(--radius-small, 4px);
                background: var(--muted);
                animation: var(--animate-card-pulse);
              "
            ></div>
          </div>
        </div>
      </article>
      <NotFoundView v-else-if="'404' === contentType" />
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
          <BlockContent class="prose-content" :html="postData.content?.rendered" />
          <footer class="single-post__footer">
            <div v-if="articleLicense" class="single-post__license">
              <div class="single-post__license-head">
                <a class="single-post__license-title" :href="postData.link" v-html="postData.title.rendered"></a>
                <a class="single-post__license-url" :href="postData.link">{{
                  readableLink(postData.link)
                }}</a>
              </div>
              <dl class="single-post__license-meta">
                <div class="single-post__license-item">
                  <dt>作者</dt>
                  <dd>{{ authorName }}</dd>
                </div>
                <div class="single-post__license-item">
                  <dt>发布于</dt>
                  <dd>
                    <time :datetime="postData.date">{{ formatDate(postData.date) }}</time>
                  </dd>
                </div>
                <div v-if="postData.modified" class="single-post__license-item">
                  <dt>更新于</dt>
                  <dd>
                    <time :datetime="postData.modified">{{ formatDate(postData.modified) }}</time>
                  </dd>
                </div>
                <div class="single-post__license-item">
                  <dt>许可协议</dt>
                  <dd>
                    <a
                      v-if="articleLicense.url"
                      :href="articleLicense.url"
                      target="_blank"
                      rel="noopener noreferrer"
                      >{{ articleLicense.label }}</a
                    >
                    <span v-else>{{ articleLicense.label }}</span>
                  </dd>
                </div>
              </dl>
              <p class="single-post__license-name">{{ articleLicense.fullName }}</p>
            </div>
            <div v-if="postTags.length > 0" class="single-post__tags">
              <router-link
                v-for="tag in postTags"
                :key="tag.name"
                :to="toInternalPath(tag.link)"
                class="single-post__tag-link"
              >
                <StTag>#{{ tag.name }}</StTag>
              </router-link>
            </div>
          </footer>
        </div>
        <CommentsPanel
          v-if="!isPreviewMode"
          :post-id="postData.id"
          :enabled="'open' === (postData.comment_status || 'closed')"
          :form-settings="siteInfo.comments!"
        />
      </article>
    </template>
  </section>
</template>

<style scoped>
.content-view__loading {
  min-height: 16rem;
  display: grid;
  place-items: center;
  color: var(--secondary, #888);
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
