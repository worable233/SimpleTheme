<script setup lang="ts">
/**
 * ArchivesView — 归档页面（数据编排，使用独立卡片/弹窗组件）
 */
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useHead } from '@vueuse/head'
import { useLoading } from '../composables/useLoading'
import { fetchCollection } from '../lib/wordpress'
import type { WordPressPost, RenderedText } from '../types/wordpress'
import ErrorView from '@/components/ErrorView.vue'
import TimelineCard from '@/components/archive/TimelineCard.vue'
import CategoryCard from '@/components/archive/CategoryCard.vue'
import TimelineModal from '@/components/archive/TimelineModal.vue'
import CategoryModal from '@/components/archive/CategoryModal.vue'

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
}

const { isLoading, error, withLoading } = useLoading()
const posts = ref<WordPressPost[]>([])
const activeModal = ref<'timeline' | 'category' | null>(null)
const selectedYear = ref<number | null>(null)
const selectedCategory = ref<string | null>(null)

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
    const total = g.categories.reduce((s, c) => s + c.posts.length, 0)
    const activeMonths: boolean[] = new Array(12).fill(false)
    for (const cat of g.categories) {
      for (const post of cat.posts) {
        const m = new Date(post.date).getMonth()
        activeMonths[m] = true
      }
    }
    return { year: g.year, total, activeMonths }
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
        .slice(0, 5)
        .map((p) => ({
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
  const allPosts = group.categories.flatMap((c) => c.posts)
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
  selectedYear.value = year
  activeModal.value = 'timeline'
}

function openCategoryModal(name: string) {
  selectedCategory.value = name
  activeModal.value = 'category'
}

function closeModal() {
  activeModal.value = null
  selectedYear.value = null
  selectedCategory.value = null
}

onMounted(async () => {
  await withLoading(async () => {
    const res = await fetchCollection('post', { limit: 100 })
    posts.value = res.items
  })
})

function onKeydown(e: KeyboardEvent) {
  if (e.key === 'Escape' && activeModal.value) {
    closeModal()
  }
}

onMounted(() => window.addEventListener('keydown', onKeydown))
onUnmounted(() => window.removeEventListener('keydown', onKeydown))
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
    <div v-if="isLoading" class="archives-page">
      <div class="section-header">
        <div role="status" class="skeleton line" style="width: 140px; height: 28px;"></div>
      </div>
      <div class="timeline-root">
        <div v-for="i in 3" :key="'sk-year-' + i" class="timeline-year-card-skeleton" style="pointer-events: none;">
          <div class="timeline-year-header">
            <div role="status" class="skeleton line" style="width: 80px; height: 36px;"></div>
            <div role="status" class="skeleton line" style="width: 90px; height: 28px;"></div>
          </div>
          <div class="timeline-year-calendar">
            <div
              v-for="m in 12"
              :key="m"
              role="status"
              class="skeleton box"
              style="width: 100%; height: auto; aspect-ratio: 1; border-radius: 6px; margin: 0;"
            ></div>
          </div>
        </div>
      </div>
      <div class="section-header" style="margin-top: 3rem;">
        <div role="status" class="skeleton line" style="width: 140px; height: 28px;"></div>
      </div>
      <div class="category-root">
        <div v-for="i in 4" :key="'sk-cat-' + i" class="category-card-skeleton" style="pointer-events: none;">
          <div class="category-header">
            <div role="status" class="skeleton line" style="width: 120px; height: 26px;"></div>
            <div role="status" class="skeleton line" style="width: 80px; height: 26px;"></div>
          </div>
          <div class="category-posts" style="border-top: none;">
            <div v-for="j in 3" :key="j" role="status" class="skeleton line" style="width: 100%; height: 20px; margin: 0.35rem 0;"></div>
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
            <i class="bx bx-calendar" style="vertical-align: -2px; margin-right: 6px; font-size: 1.3rem;"></i>
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
        <section class="section-header" style="margin-top: 3rem;">
          <h2 class="section-title">
            <i class="bx bx-folder" style="vertical-align: -2px; margin-right: 6px; font-size: 1.3rem;"></i>
            分类
          </h2>
        </section>
        <div class="category-root" id="category-root">
          <CategoryCard
            v-for="cat in categoryCards"
            :key="cat.name"
            :name="cat.name"
            :count="cat.count"
            :posts="cat.posts"
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
  font-size: 1.4rem;
  font-weight: 700;
  color: var(--foreground, #333);
  margin: 0;
}

/* Content that replaces skeleton should not re-animate */
.content-area .section-header,
.content-area .timeline-year-card,
.content-area .category-card {
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

.timeline-year-card-skeleton,
.category-card-skeleton {
  background: var(--card, rgba(255,255,255,0.7));
  border-radius: var(--radius-large, 8px);
  box-shadow: 0 4px 24px 0 rgba(0,0,0,0.07);
  padding: 1.2rem;
  display: flex;
  flex-direction: column;
  border: 1.5px solid var(--border, #e0e0e0);
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
  from { opacity: 0; transform: translateX(-24px); }
  to { opacity: 1; transform: translateX(0); }
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
