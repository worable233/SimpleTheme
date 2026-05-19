<script setup lang="ts">
import { computed, ref, watch, type Component } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { isExternalUrl, toInternalPath, toResolvablePath } from '@/lib/theme-config'
import {
  fetchContentByRestUrl,
  fetchPostCollectionByTaxonomy,
  getErrorMessage,
  resolveThemePath,
  trackPostView,
} from '@/lib/wordpress'
import { mockResolveThemePath, mockFetchContentByRestUrl, shouldUseMock } from '@/lib/mock-api'
import { withCache, clearAllCache } from '@/lib/api-cache'
import { showError } from '@/lib/toast'
import type { ResolveResponse, WordPressPost } from '@/types/wordpress'
import CommentsPanel from '@/components/CommentsPanel.vue'
import { useSiteShell } from '@/composables/useSiteShell'
import { useContentEnhancer } from '@/composables/useContentEnhancer'
import TocWidget from '@/components/TocWidget.vue'
import NotFoundView from '@/views/NotFoundView.vue'
import ErrorView from '@/components/ErrorView.vue'
import PageView from '@/views/PageView.vue'
import ShuoshuoView from '@/views/ShuoshuoView.vue'
import ArchivesView from '@/views/ArchivesView.vue'
import AboutView from '@/views/AboutView.vue'
import LinksView from '@/views/LinksView.vue'
import TermArchive from '@/views/TermArchive.vue'

const specialPageMap: Record<string, Component> = {
  '/shuoshuo': ShuoshuoView,
  '/about': AboutView,
  '/archives': ArchivesView,
  '/links': LinksView,
}

const route = useRoute()
const router = useRouter()
const { siteInfo } = useSiteShell()

const contentType = ref<ResolveResponse['type']>('home')
const errorMessage = ref('')
const loading = ref(false)
const postData = ref<WordPressPost | null>(null)
const postContent = computed(() => postData.value?.content?.rendered ?? null)
useContentEnhancer(postContent)

const termName = ref('')
const termTaxonomy = ref('')
const termPosts = ref<WordPressPost[]>([])
const termPostsLoading = ref(false)

const formatDate = (dateString: string) =>
  new Intl.DateTimeFormat('zh-CN', {
    year: 'numeric', month: 'long', day: 'numeric',
  }).format(new Date(dateString))

const primaryCategory = computed<{ name: string; slug: string } | null>(() => {
  const post = postData.value as (WordPressPost & { _embedded?: Record<string, unknown> }) | null
  const terms = (post?._embedded?.['wp:term'] as Array<Array<{ taxonomy?: string; name?: string; slug?: string }>> | undefined) || []
  for (const group of terms) {
    const cat = group.find((term) => term?.taxonomy === 'category' && typeof term.name === 'string')
    if (cat?.name && cat?.slug) return { name: cat.name, slug: cat.slug }
  }
  return null
})

const featuredImageUrl = computed(() => {
  const post = postData.value as (WordPressPost & { _embedded?: Record<string, unknown> }) | null
  return (post?._embedded?.['wp:featuredmedia'] as Array<{ source_url?: string }> | undefined)?.[0]?.source_url
    || postData.value?.featuredImage
    || ''
})

const specialPageComponent = computed(() => {
  return specialPageMap[route.path] || null
})


const postTags = computed(() => {
  const post = postData.value as (WordPressPost & { _embedded?: Record<string, unknown> }) | null
  const terms = (post?._embedded?.['wp:term'] as Array<Array<{ taxonomy?: string; name?: string }>> | undefined) || []
  for (const group of terms) {
    const tags = group
      .filter((term) => term?.taxonomy === 'post_tag' && typeof term.name === 'string')
      .map((term) => term.name as string)
    if (tags.length > 0) return tags
  }
  return [] as string[]
})

const loadTermPosts = async (taxonomy: string, id: number) => {
  termPostsLoading.value = true
  termPosts.value = []
  try {
    termPosts.value = await fetchPostCollectionByTaxonomy(taxonomy, id)
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
    const useMock = shouldUseMock()
    const cachedResolve = useMock
      ? mockResolveThemePath
      : withCache(resolveThemePath, `resolve:${route.path}`, 30_000)
    const resolved = await cachedResolve(route.path)
    contentType.value = resolved.type

    if (
      ('post' === resolved.type || 'page' === resolved.type || 'shuoshuo' === resolved.type) &&
      resolved.restUrl
    ) {
      postData.value = await (useMock ? mockFetchContentByRestUrl(resolved.restUrl) : fetchContentByRestUrl(resolved.restUrl))
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

  const anchor = target.closest('a')
  if (!anchor) return

  const href = anchor.getAttribute('href')
  if (
    !href || href.startsWith('#') || 'mailto:' === href.slice(0, 7) ||
    'tel:' === href.slice(0, 4) || '_blank' === anchor.target ||
    anchor.hasAttribute('download') || anchor.hasAttribute('data-fancybox') || isExternalUrl(href)
  ) return

  event.preventDefault()
  void router.push(toInternalPath(href))
}

watch(
  () => toResolvablePath(route.path),
  () => { void loadCurrentContent() },
  { immediate: true },
)
</script>

<template>
  <section class="content-view" @click.capture="handleContentClick">

    <!-- Special pages (shuoshuo, about, archives, links) always render — they handle their own errors and headers -->
    <template v-if="specialPageComponent">
      <component :is="specialPageComponent" />
    </template>

    <!-- Non-special pages — mutually exclusive states chained with v-if/v-else-if/v-else -->
    <template v-else>
      <ErrorView
        v-if="'error' === contentType"
        illustration="warning"
        title="页面加载失败"
        description="抱歉，页面暂时无法加载，请稍后重试。"
      />
      <article v-else-if="loading" class="single-post">
        <div class="single-post__cover" style="background: var(--muted); animation: pulse 1.5s ease-in-out infinite;">
          <div class="single-post__cover-info">
            <div class="single-post__cover-title">
              <div style="height: 1.5rem; width: 65%; margin: 0 auto; border-radius: var(--radius-small, 4px); background: rgba(255,255,255,0.25);"></div>
            </div>
            <div class="single-post__cover-meta">
              <div style="height: 0.75rem; width: 6rem; border-radius: var(--radius-small, 4px); background: rgba(255,255,255,0.25);"></div>
              <div style="height: 0.75rem; width: 4rem; border-radius: var(--radius-small, 4px); background: rgba(255,255,255,0.25);"></div>
            </div>
          </div>
        </div>
        <div class="single-post__body">
          <div style="display: flex; flex-direction: column; gap: 0.75rem;">
            <div style="height: 0.75rem; width: 100%; border-radius: var(--radius-small, 4px); background: var(--muted); animation: pulse 1.5s ease-in-out infinite;"></div>
            <div style="height: 0.75rem; width: 100%; border-radius: var(--radius-small, 4px); background: var(--muted); animation: pulse 1.5s ease-in-out infinite;"></div>
            <div style="height: 0.75rem; width: 85%; border-radius: var(--radius-small, 4px); background: var(--muted); animation: pulse 1.5s ease-in-out infinite;"></div>
            <div style="height: 0.75rem; width: 100%; border-radius: var(--radius-small, 4px); background: var(--muted); animation: pulse 1.5s ease-in-out infinite;"></div>
            <div style="height: 0.75rem; width: 60%; border-radius: var(--radius-small, 4px); background: var(--muted); animation: pulse 1.5s ease-in-out infinite;"></div>
            <div style="height: 0.75rem; width: 100%; border-radius: var(--radius-small, 4px); background: var(--muted); animation: pulse 1.5s ease-in-out infinite;"></div>
            <div style="height: 0.75rem; width: 70%; border-radius: var(--radius-small, 4px); background: var(--muted); animation: pulse 1.5s ease-in-out infinite;"></div>
            <div style="height: 0.75rem; width: 100%; border-radius: var(--radius-small, 4px); background: var(--muted); animation: pulse 1.5s ease-in-out infinite;"></div>
            <div style="height: 0.75rem; width: 90%; border-radius: var(--radius-small, 4px); background: var(--muted); animation: pulse 1.5s ease-in-out infinite;"></div>
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
      <PageView v-else-if="'page' === contentType && postData" :page-data="postData" />
      <article v-else-if="postData && 'page' !== contentType" class="single-post">
        <div v-if="featuredImageUrl" class="single-post__cover">
          <div class="single-post__cover-img">
            <img :src="featuredImageUrl" alt="" />
          </div>
          <div class="single-post__cover-info">
            <div class="single-post__cover-title">
              <h1 v-html="postData.title.rendered"></h1>
            </div>
            <div class="single-post__cover-meta">
              <time :datetime="postData.date">{{ formatDate(postData.date) }}</time>
              <router-link v-if="primaryCategory" :to="{ path: '/', query: { category: primaryCategory.slug } }" class="single-post__cover-category">{{ primaryCategory.name }}</router-link>
            </div>
          </div>
        </div>
        <div v-else class="single-post__header">
          <h1 class="single-post__header-title" v-html="postData.title.rendered"></h1>
          <div class="single-post__header-meta">
            <time :datetime="postData.date">{{ formatDate(postData.date) }}</time>
            <router-link v-if="primaryCategory" :to="{ path: '/', query: { category: primaryCategory.slug } }" class="single-post__header-category">{{ primaryCategory.name }}</router-link>
          </div>
        </div>
        <div class="single-post__body">
          <div class="oat-prose" v-html="postData.content?.rendered"></div>
          <footer class="single-post__footer">
            <div v-if="postTags.length > 0" class="single-post__tags">
              <span v-for="tag in postTags" :key="tag">#{{ tag }}</span>
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

@keyframes slideIn {
  from { opacity: 0; transform: translateX(-24px); }
  to { opacity: 1; transform: translateX(0); }
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
</style>
