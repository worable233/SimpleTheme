<script setup lang="ts">
/**
 * ArchivesView — 归档页面（数据编排，使用独立卡片/弹窗组件）
 */
import { ref, computed, nextTick, onMounted, onUnmounted, watch } from 'vue'
import { useHead } from '@unhead/vue'
import AppIcon from '@/components/AppIcon.vue'
import { useLoading } from '../composables/useLoading'
import { fetchCollection } from '../lib/wordpress'
import type { WordPressPost } from '../types/wordpress'
import ErrorView from '@/components/ErrorView.vue'
import TimelineCard from '@/components/archive/TimelineCard.vue'
import CategoryCard from '@/components/archive/CategoryCard.vue'
import TimelineModal from '@/components/archive/TimelineModal.vue'
import CategoryModal from '@/components/archive/CategoryModal.vue'
import { useBodyScrollLock } from '@/composables/useBodyScrollLock'

export interface PostWithMeta extends WordPressPost {
  displayDate: string
}

interface YearGroup {
  year: number
  categories: {
    name: string
    posts: WordPressPost[]
  }[]
}

interface CategoryCardData {
  name: string
  count: number
  posts: PostWithMeta[]
  previewPosts: PostWithMeta[]
}

const { isLoading, error, withLoading } = useLoading()
const posts = ref<WordPressPost[]>([])
const activeModal = ref<'timeline' | 'category' | null>(null)
const selectedYear = ref<number | null>(null)
const selectedCategory = ref<string | null>(null)
const { lockBodyScroll, unlockBodyScroll } = useBodyScrollLock()
let modalTrigger: HTMLElement | null = null

useHead({ title: '归档' })

const yearGroups = computed<YearGroup[]>(() => {
  const groups: Record<number, Record<string, WordPressPost[]>> = {}

  for (const post of posts.value) {
    const d = new Date(post.date)
    const year = d.getFullYear()
    const month = String(d.getMonth() + 1).padStart(2, '0')

    if (!groups[year]) groups[year] = {}
    if (!groups[year][month]) groups[year][month] = []
    groups[year][month].push(post)
  }

  return Object.entries(groups)
    .sort(([a], [b]) => Number(b) - Number(a))
    .map(([year, months]) => {
      const catMap = new Map<string, WordPressPost[]>()
      for (const monthPosts of Object.values(months)) {
        for (const post of monthPosts) {
          const cats = post.categories?.length ? post.categories : ['未分类']
          for (const cat of cats) {
            if (!catMap.has(cat)) catMap.set(cat, [])
            catMap.get(cat)!.push(post)
          }
        }
      }
      return {
        year: Number(year),
        categories: Array.from(catMap.entries())
          .map(([name, posts]) => ({
            name,
            posts: posts.sort((a, b) => new Date(b.date).getTime() - new Date(a.date).getTime()),
          }))
          .sort((a, b) => b.posts.length - a.posts.length),
      }
    })
})

// Flat year list with post count for timeline cards
const yearTimelineCards = computed(() => {
  return yearGroups.value.map((g) => {
    const uniquePosts = new Set<number>()
    for (const category of g.categories) {
      for (const post of category.posts) uniquePosts.add(post.id)
    }
    const activeMonths: boolean[] = Array.from({ length: 12 }, () => false)
    for (const cat of g.categories) {
      for (const post of cat.posts) {
        const m = new Date(post.date).getMonth()
        activeMonths[m] = true
      }
    }
    return { year: g.year, total: uniquePosts.size, activeMonths }
  })
})

// All categories across all years for category cards
const categoryCards = computed<CategoryCardData[]>(() => {
  const catMap = new Map<string, WordPressPost[]>()
  for (const post of posts.value) {
    const cats = post.categories?.length ? post.categories : ['未分类']
    for (const cat of cats) {
      if (!catMap.has(cat)) catMap.set(cat, [])
      catMap.get(cat)!.push(post)
    }
  }
  return Array.from(catMap.entries())
    .map(([name, rawPosts]) => ({
      name,
      count: rawPosts.length,
      posts: rawPosts
        .sort((a, b) => new Date(b.date).getTime() - new Date(a.date).getTime())
        .map((p) => ({
          ...p,
          displayDate: formatDate(p.date),
        })),
      previewPosts: rawPosts.slice(0, 5).map((p) => ({
        ...p,
        displayDate: formatDate(p.date),
      })),
    }))
    .sort((a, b) => b.count - a.count)
})

// Modal data for year/timeline view
const timelineModalData = computed(() => {
  if (selectedYear.value === null) return null
  const group = yearGroups.value.find((g) => g.year === selectedYear.value)
  if (!group) return null
  const allPosts = Array.from(
    new Map(group.categories.flatMap((c) => c.posts).map((post) => [post.id, post])).values(),
  )
  const total = allPosts.length
  const categories = group.categories.length
  const monthMap = new Map<string, PostWithMeta[]>()
  for (const post of allPosts) {
    const d = new Date(post.date)
    const monthLabel = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`
    if (!monthMap.has(monthLabel)) monthMap.set(monthLabel, [])
    monthMap.get(monthLabel)!.push({ ...post, displayDate: formatDate(post.date) })
  }
  return {
    year: selectedYear.value,
    total,
    categories,
    months: Array.from(monthMap.entries()).sort(([a], [b]) => b.localeCompare(a)),
  }
})

// Modal data for category view
const categoryModalPosts = computed<PostWithMeta[]>(() => {
  if (!selectedCategory.value) return []
  const card = categoryCards.value.find((c) => c.name === selectedCategory.value)
  return card?.posts ?? []
})

function formatDate(dateString: string) {
  const d = new Date(dateString)
  const y = d.getFullYear()
  const m = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  return `${y}-${m}-${day}`
}

function openTimelineModal(year: number) {
  modalTrigger = document.activeElement instanceof HTMLElement ? document.activeElement : null
  selectedYear.value = year
  activeModal.value = 'timeline'
}

function openCategoryModal(name: string) {
  modalTrigger = document.activeElement instanceof HTMLElement ? document.activeElement : null
  selectedCategory.value = name
  activeModal.value = 'category'
}

function closeModal() {
  activeModal.value = null
  selectedYear.value = null
  selectedCategory.value = null
  const trigger = modalTrigger
  modalTrigger = null
  void nextTick(() => trigger?.focus())
}

watch(activeModal, (modal) => {
  if (modal) lockBodyScroll()
  else unlockBodyScroll()
})

onMounted(async () => {
  await withLoading(async () => {
    const firstPage = await fetchCollection('post', { limit: 50, page: 1 })
    const allPosts = [...firstPage.items]
    const totalPages = Math.max(1, firstPage.totalPages || 1)
    for (let page = 2; page <= totalPages; page += 1) {
      const nextPage = await fetchCollection('post', { limit: 50, page })
      allPosts.push(...nextPage.items)
      if (nextPage.items.length === 0) break
    }
    posts.value = allPosts
  })
})

function onKeydown(e: KeyboardEvent) {
  if (e.key === 'Escape' && activeModal.value) {
    closeModal()
  }
}

onMounted(() => window.addEventListener('keydown', onKeydown))
onUnmounted(() => {
  unlockBodyScroll()
  window.removeEventListener('keydown', onKeydown)
})
</script>

<template>
  <div class="archives-page">
    <!-- Header -->
    <header class="section-header">
      <h1>
        <span class="section-header__title">归档</span>
        <span class="section-header__subtitle">&nbsp;Archives.</span>
      </h1>
    </header>

    <!-- Loading skeleton -->
    <div v-if="isLoading" class="archives-skeleton">
      <div class="section-header">
        <div role="status" class="skeleton" style="width: 140px; height: 28px"></div>
      </div>
      <div class="timeline-root">
        <div v-for="i in 3" :key="'sk-year-' + i" class="timeline-year-card">
          <div class="timeline-year-header">
            <span
              role="status"
              class="skeleton"
              style="width: 60px; height: 38px; border-radius: 6px"
            ></span>
            <span
              role="status"
              class="skeleton"
              style="width: 85px; height: 28px; border-radius: 9999px"
            ></span>
          </div>
          <div class="timeline-year-calendar">
            <span
              v-for="m in 12"
              :key="m"
              role="status"
              class="skeleton"
              style="width: 100%; aspect-ratio: 1; border-radius: 6px"
            ></span>
          </div>
        </div>
      </div>
      <div class="section-header" style="margin-top: 3rem">
        <div role="status" class="skeleton" style="width: 140px; height: 28px"></div>
      </div>
      <div class="category-root">
        <div v-for="i in 4" :key="'sk-cat-' + i" class="category-card">
          <div class="category-header">
            <span
              role="status"
              class="skeleton"
              style="width: 100px; height: 26px; border-radius: 6px"
            ></span>
            <span
              role="status"
              class="skeleton"
              style="width: 56px; height: 26px; border-radius: 9999px"
            ></span>
          </div>
          <div class="category-posts">
            <div v-for="j in 3" :key="j" class="category-post-item">
              <span
                role="status"
                class="skeleton"
                style="width: 65%; height: 18px; border-radius: 4px"
              ></span>
              <span
                role="status"
                class="skeleton"
                style="width: 64px; height: 16px; border-radius: 4px"
              ></span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Error -->
    <ErrorView
      v-else-if="error"
      illustration="warning"
      title="数据加载失败"
      description="无法获取归档数据，请稍后重试。"
    />

    <!-- Content -->
    <template v-else-if="posts.length > 0">
      <div class="content-area">
        <!-- Timeline Year Cards Section -->
        <section class="section-header">
          <h2 class="section-title">
            <AppIcon name="calendar" :size="21" class="archives-icon" />
            时间线
          </h2>
        </section>
        <div class="timeline-root" id="timeline-root">
          <TimelineCard
            v-for="card in yearTimelineCards"
            :key="card.year"
            :year="card.year"
            :total="card.total"
            :active-months="card.activeMonths"
            @select="openTimelineModal"
          />
        </div>

        <!-- Category Cards Section -->
        <section class="section-header" style="margin-top: 3rem">
          <h2 class="section-title">
            <AppIcon name="folder" :size="21" class="archives-icon" />
            分类
          </h2>
        </section>
        <div class="category-root" id="category-root">
          <CategoryCard
            v-for="cat in categoryCards"
            :key="cat.name"
            :name="cat.name"
            :count="cat.count"
            :posts="cat.previewPosts"
            @select="openCategoryModal"
          />
        </div>
      </div>

      <!-- Timeline Modal -->
      <TimelineModal
        :show="activeModal === 'timeline'"
        :data="timelineModalData"
        @close="closeModal"
      />

      <!-- Category Modal -->
      <CategoryModal
        :show="activeModal === 'category'"
        :name="selectedCategory ?? ''"
        :posts="categoryModalPosts"
        @close="closeModal"
      />
    </template>

    <!-- Empty -->
    <div v-else class="archives-empty">
      <p>还没有文章</p>
    </div>
  </div>
</template>

<style scoped>
/* ========== Layout ========== */
.archives-page {
  animation: slideIn 0.5s cubic-bezier(0.16, 1, 0.3, 1) both;
  padding: 25px;
}

.section-title {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 1.4rem;
  font-weight: 700;
  color: var(--foreground, #333);
  margin: 0;
}

.archives-icon {
  color: var(--primary);
  flex-shrink: 0;
}

/* Content that replaces skeleton should not re-animate */
.content-area .section-header {
  animation: none;
  opacity: 1;
  transform: none;
}

/* ========== Loading / Empty ========== */
.archives-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 4rem 2rem;
  color: var(--foreground, #666);
  gap: 0.8rem;
}

.archives-skeleton .timeline-year-card,
.archives-skeleton .category-card {
  background: var(--card, rgba(255, 255, 255, 0.7));
  border-radius: var(--radius-large, 8px);
  box-shadow: 0 4px 24px 0 rgba(0, 0, 0, 0.07);
  padding: 1.2rem;
  display: flex;
  flex-direction: column;
  border: 1.5px solid var(--border, #e0e0e0);
  pointer-events: none;
}
.archives-skeleton .timeline-year-card {
  gap: 1rem;
}
.archives-skeleton .timeline-year-card:hover,
.archives-skeleton .category-card:hover {
  transform: none;
  box-shadow: 0 4px 24px 0 rgba(0, 0, 0, 0.07);
  border-color: var(--border, #e0e0e0);
}
.archives-skeleton .timeline-year-header,
.archives-skeleton .category-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.archives-skeleton .category-header {
  margin-bottom: 0.8rem;
}
.archives-skeleton .timeline-year-calendar {
  display: grid;
  grid-template-columns: repeat(6, 1fr);
  gap: 0.5rem;
}
.archives-skeleton .timeline-year-calendar .skeleton {
  border: 1.5px solid var(--border, #e0e0e0);
}
.archives-skeleton .category-posts {
  padding-top: 0.8rem;
  border-top: 1px dashed var(--border, #e0e0e0);
  display: flex;
  flex-direction: column;
  gap: 0.3rem;
}
.archives-skeleton .category-post-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.3rem 0;
}

/* ========== Grid Layouts ========== */
.timeline-root {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1.5rem;
  margin: 0 0 1rem;
}

.category-root {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 2rem;
  margin: 0;
}

/* ========== Animation ========== */
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

/* ========== Responsive ========== */
@media (max-width: 900px) {
  .timeline-root {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 600px) {
  .timeline-root {
    grid-template-columns: 1fr;
  }

  .archives-page {
    padding: 1rem 0.8rem 3rem;
  }
}
</style>
