<script setup lang="ts">
import { computed, ref, onMounted } from 'vue'
import { fetchLinks, fetchPage } from '@/lib/wordpress'
import { showError } from '@/lib/toast'
import { useContentEnhancer } from '@/composables/useContentEnhancer'
import { useSiteShell } from '@/composables/useSiteShell'
import CommentsPanel from '@/components/CommentsPanel.vue'
import type { WordPressLinkCategory, WordPressPost } from '@/types/wordpress'
import ErrorView from '@/components/ErrorView.vue'

interface FlattenedLink {
  id: number
  name: string
  url: string
  description: string
  image: string
}

const { siteInfo } = useSiteShell()

const linkCategories = ref<WordPressLinkCategory[]>([])
const loading = ref(true)
const linksPage = ref<WordPressPost | null>(null)
const linksPageLoading = ref(true)
const linksPageContent = computed(() => linksPage.value?.content?.rendered ?? null)
useContentEnhancer(linksPageContent)

onMounted(async () => {
  try {
    const [data, page] = await Promise.all([
      fetchLinks(),
      fetchPage('links').catch(() => null),
    ])
    linkCategories.value = data
    linksPage.value = page
  } catch (err) {
    showError(err instanceof Error ? err.message : '友链加载失败')
  } finally {
    loading.value = false
    linksPageLoading.value = false
  }
})

function getDomain(url: string): string {
  try {
    const u = new URL(url)
    return u.hostname
  } catch {
    return url
  }
}

function getAvatarUrl(link: FlattenedLink): string {
  return link.image
}
</script>

<template>
  <div class="links-page">
    <!-- 页面标题 -->
    <header class="section-header">
      <h1>
        <span class="section-header__title">友人帐</span>
        <span class="section-header__subtitle">Links.</span>
      </h1>
    </header>

    <!-- 加载骨架 -->
    <div v-if="loading" class="links-loading">
      <div v-for="i in 3" :key="i" class="link-card-skeleton">
        <div class="skeleton-avatar"></div>
        <div class="skeleton-body">
          <div class="skeleton-line w-50"></div>
          <div class="skeleton-line w-70"></div>
          <div class="skeleton-line w-40"></div>
        </div>
      </div>
    </div>

    <!-- 友链列表 -->
    <template v-else-if="linkCategories.length > 0">
      <div class="content-area">
      <section
        v-for="category in linkCategories"
        :key="category.id"
        class="link-category"
      >
        <!-- 分类标题 -->
        <header class="category-header">
          <h2 class="category-name">{{ category.name }}</h2>
          <span v-if="category.description" class="category-desc">
            / {{ category.description }}
          </span>
        </header>

        <!-- 链接卡片网格 -->
        <div class="link-grid">
          <router-link
            v-for="link in category.links"
            :key="link.id"
            :to="{ path: '/go', query: { url: link.url } }"
            class="link-card"
          >
            <div class="link-card__inner">
              <!-- 头像 -->
              <div class="link-card__avatar-wrap">
                <img
                  v-if="link.image"
                  :src="getAvatarUrl(link)"
                  :alt="link.name"
                  class="link-card__avatar"
                  loading="lazy"
                  referrerpolicy="no-referrer"
                />
                <span v-else class="link-card__avatar-letter">{{ link.name.charAt(0) }}</span>
              </div>

              <!-- 站点名称 -->
              <h3 class="link-card__name">{{ link.name }}</h3>
            </div>

            <!-- Hover tooltip -->
            <div class="link-card__tip">
              <div class="link-card__tip-top">
                <div class="link-card__tip-avatar">
                  <img v-if="link.image" :src="link.image" alt="" />
                  <span v-else>{{ link.name.charAt(0) }}</span>
                </div>
                <div class="link-card__tip-info">
                  <span class="link-card__tip-name">{{ link.name }}</span>
                  <span class="link-card__tip-url">{{ getDomain(link.url) }}</span>
                </div>
              </div>
              <div v-if="link.description" class="link-card__tip-desc">{{ link.description }}</div>
            </div>
          </router-link>
        </div>
      </section>

      <!-- 友链页面内容（从 slug 为 links 的页面获取） -->
      <section v-if="linksPage" class="links-content oat-prose">
        <div v-html="linksPage.content?.rendered"></div>
      </section>

      <!-- 评论区（使用 links 文章的评论） -->
      <div v-if="linksPage" class="comments-section">
        <CommentsPanel
          :post-id="linksPage.id"
          :enabled="'open' === (linksPage.comment_status || 'closed')"
          :form-settings="siteInfo.comments!"
        />
      </div>
      </div>
    </template>

    <!-- 空状态 -->
    <ErrorView
      v-else
      illustration="add-friends"
      title="还没有友链"
      description="还没有友链，稍后回来看看吧。"
    />
  </div>
</template>

<style scoped>
/* ============ Page Layout ============ */
.links-page {
  --anim-ease-enter: cubic-bezier(0.16, 1, 0.3, 1);
  --anim-ease-hover: cubic-bezier(0.34, 1.56, 0.64, 1);
  --anim-duration-enter: 0.5s;
  --anim-duration-hover: 0.35s;
  padding: 25px;
}

@keyframes slideIn {
  from { opacity: 0; transform: translateX(-24px); }
  to { opacity: 1; transform: translateX(0); }
}

.content-area .section-header,
.content-area .link-category,
.content-area .link-card,
.content-area .comments-section {
  animation: none;
  opacity: 1;
  transform: none;
}

.section-header {
  animation: slideIn var(--anim-duration-enter) var(--anim-ease-enter) both;
}

.link-card-skeleton {
  animation: slideIn var(--anim-duration-enter) var(--anim-ease-enter) both;
}
.link-card-skeleton:nth-child(1) { animation-delay: 0.06s; }
.link-card-skeleton:nth-child(2) { animation-delay: 0.11s; }
.link-card-skeleton:nth-child(3) { animation-delay: 0.16s; }

.link-card {
  animation: slideIn var(--anim-duration-enter) var(--anim-ease-enter) both;
}
.link-card:nth-child(1) { animation-delay: 0.06s; }
.link-card:nth-child(2) { animation-delay: 0.11s; }
.link-card:nth-child(3) { animation-delay: 0.16s; }
.link-card:nth-child(4) { animation-delay: 0.21s; }
.link-card:nth-child(5) { animation-delay: 0.26s; }
.link-card:nth-child(6) { animation-delay: 0.31s; }

/* ============ Loading Skeleton ============ */
.links-loading {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.link-card-skeleton {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  background: var(--card);
  border-radius: var(--radius-large, 8px);
  border: 1px solid var(--border, transparent);
}

.skeleton-avatar {
  width: 3rem;
  height: 3rem;
  border-radius: 50%;
  background: var(--muted);
  animation: pulse 1.5s ease-in-out infinite;
  flex-shrink: 0;
}

.skeleton-body {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.skeleton-line {
  height: 0.75rem;
  border-radius: var(--radius-small, 4px);
  background: var(--muted);
  animation: pulse 1.5s ease-in-out infinite;
}

.w-50 { width: 50%; }
.w-70 { width: 70%; }
.w-40 { width: 40%; }

@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.4; }
}

/* ============ Category Section ============ */
.link-category {
  margin-bottom: 2.5rem;
}

.category-header {
  display: flex;
  align-items: baseline;
  gap: 0.5rem;
  margin-bottom: 1.25rem;
  padding-bottom: 0.75rem;
  border-bottom: 2px solid var(--muted);
}

.category-name {
  font-size: 1.25rem;
  font-weight: 600;
  color: var(--foreground);
  margin: 0;
}

.category-desc {
  font-size: 0.875rem;
  color: var(--secondary);
  font-weight: 400;
}

/* ============ Link Grid ============ */
.link-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 0.75rem;
}

/* ============ Link Card ============ */
.link-card {
  position: relative;
  display: block;
  text-decoration: none;
  color: inherit;
  border-radius: var(--radius-large, 8px);
  background: var(--card);
  border: 1px solid var(--border, transparent);
  transition: all 0.25s cubic-bezier(0.55, 0, 0.85, 0.25);
}

.link-card:hover {
  border-color: var(--primary);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

/* ============ Link Card Inner ============ */
.link-card__inner {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.875rem;
}

/* Avatar */
.link-card__avatar-wrap {
  width: 2.5rem;
  height: 2.5rem;
  border-radius: 50%;
  overflow: hidden;
  flex-shrink: 0;
  background: var(--muted);
}

.link-card__avatar {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

.link-card__avatar-letter {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1rem;
  font-weight: 600;
  color: var(--primary);
  background: var(--muted);
  user-select: none;
}

/* Name — takes remaining space */
.link-card__name {
  flex: 1;
  font-size: 0.9375rem;
  font-weight: 600;
  color: var(--foreground);
  margin: 0;
  line-height: 1.3;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* ============ Hover Tooltip ============ */
.link-card__tip {
  position: absolute;
  bottom: calc(100% + 6px);
  left: 50%;
  transform: translateX(-50%) translateY(8px);
  width: 260px;
  background: var(--card);
  border: none;
  border-radius: var(--radius-large, 8px);
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
  opacity: 0;
  visibility: hidden;
  transition: opacity 0.2s 0.2s, visibility 0s 0.4s, transform 0.25s 0.2s cubic-bezier(0.55, 0, 0.8, 0.25);
  pointer-events: none;
  z-index: 10;
}

body[data-theme='dark'] .link-card__tip {
  box-shadow: 0 6px 24px rgba(0, 0, 0, 0.45);
}

.link-card:hover .link-card__tip {
  transition-delay: 0s;
  opacity: 1;
  visibility: visible;
  transform: translateX(-50%) translateY(0);
  pointer-events: auto;
}

/* Staggered entrance for children */
.link-card__tip-avatar,
.link-card__tip-name,
.link-card__tip-url,
.link-card__tip-desc {
  opacity: 0;
  transform: translateY(4px);
  transition: all 0.2s cubic-bezier(0.55, 0, 0.8, 0.25);
  transition-delay: 0.2s;
}

.link-card:hover .link-card__tip-avatar {
  opacity: 1;
  transform: translateY(0);
  transition-delay: 0s;
}

.link-card:hover .link-card__tip-name {
  opacity: 1;
  transform: translateY(0);
  transition-delay: 0.02s;
}

.link-card:hover .link-card__tip-url {
  opacity: 1;
  transform: translateY(0);
  transition-delay: 0.04s;
}

.link-card:hover .link-card__tip-desc {
  opacity: 1;
  transform: translateY(0);
  transition-delay: 0.06s;
}

/* Top row: avatar + info */
.link-card__tip-top {
  display: flex;
  align-items: center;
  gap: 0.625rem;
  padding: 0.625rem 0.75rem 0.5rem;
}

.link-card__tip-avatar {
  width: 2.25rem;
  height: 2.25rem;
  border-radius: 50%;
  overflow: hidden;
  flex-shrink: 0;
  background: var(--muted);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--primary);
}

.link-card__tip-avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

.link-card__tip-info {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 0.125rem;
}

.link-card__tip-name {
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--foreground);
  line-height: 1.3;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.link-card__tip-url {
  font-size: 0.6875rem;
  color: var(--primary);
  opacity: 0.55;
  line-height: 1.25;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* Bottom: description area */
.link-card__tip-desc {
  padding: 0.5rem 0.75rem 0.5rem;
  background: var(--muted);
  border-radius: 0 0 var(--radius-large, 8px) var(--radius-large, 8px);
  font-size: 0.75rem;
  color: var(--secondary);
  line-height: 1.45;
  max-width: 100%;
  word-wrap: break-word;
}

/* Arrow pointing down */
.link-card__tip::after {
  content: '';
  position: absolute;
  top: 100%;
  left: 50%;
  margin-left: -5px;
  border: 5px solid transparent;
  border-top-color: var(--card);
  filter: drop-shadow(0 2px 3px rgba(0, 0, 0, 0.08));
}

/* ============ Links Page Content ============ */
.links-content {
  margin-top: 3rem;
}

/* ============ Comments Section ============ */
.comments-section {
  margin-top: 2.5rem;
}

/* ============ Responsive ============ */
@media (max-width: 640px) {
  .link-grid {
    grid-template-columns: 1fr;
  }

  .link-card__inner {
    padding: 0.75rem;
  }
}
</style>
