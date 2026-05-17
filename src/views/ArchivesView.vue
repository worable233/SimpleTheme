<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue'
import { useHead } from '@vueuse/head'
import { useLoading } from '../composables/useLoading'
import { fetchCollection } from '../lib/wordpress'
import type { WordPressPost, RenderedText } from '../types/wordpress'
import ErrorView from '@/components/ErrorView.vue'

interface YearGroup {
  year: number
  categories: CategoryGroup[]
}

interface CategoryGroup {
  name: string
  posts: WordPressPost[]
}

interface PostWithMeta extends WordPressPost {
  displayDate: string
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
const modalLoading = ref(false)

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
    // Determine which months have posts
    const activeMonths: boolean[] = new Array(12).fill(false)
    for (const cat of g.categories) {
      for (const post of cat.posts) {
        const m = new Date(post.date).getMonth() // 0-indexed
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
  // Group posts by month
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

function openTimelineModal(year: number) {
  selectedYear.value = year
  activeModal.value = 'timeline'
  modalLoading.value = true
  nextTick(() => setTimeout(() => { modalLoading.value = false }, 350))
}

function openCategoryModal(name: string) {
  selectedCategory.value = name
  activeModal.value = 'category'
  modalLoading.value = true
  nextTick(() => setTimeout(() => { modalLoading.value = false }, 350))
}

function closeModal() {
  activeModal.value = null
  selectedYear.value = null
  selectedCategory.value = null
}

// Click outside modal mask to close
function onMaskClick(e: MouseEvent) {
  if ((e.target as HTMLElement).classList.contains('modal-mask')) {
    closeModal()
  }
}

function formatDate(dateString: string) {
  const d = new Date(dateString)
  const y = d.getFullYear()
  const m = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  return `${y}-${m}-${day}`
}

function getMonthPosts(year: number, month: number): PostWithMeta[] {
  const group = yearGroups.value.find((g) => g.year === year)
  if (!group) return []
  const result: PostWithMeta[] = []
  for (const cat of group.categories) {
    for (const post of cat.posts) {
      const d = new Date(post.date)
      if (d.getFullYear() === year && d.getMonth() + 1 === month) {
        result.push({ ...post, displayDate: formatDate(post.date) })
      }
    }
  }
  return result.sort((a, b) => new Date(b.date).getTime() - new Date(a.date).getTime())
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
      <!-- Skeleton section header -->
      <div class="section-header">
        <div role="status" class="skeleton line" style="width: 140px; height: 28px;"></div>
      </div>
      <!-- Skeleton timeline cards -->
      <div class="timeline-root">
        <div v-for="i in 3" :key="'sk-year-' + i" class="timeline-year-card" style="pointer-events: none;">
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
      <!-- Skeleton section header -->
      <div class="section-header" style="margin-top: 3rem;">
        <div role="status" class="skeleton line" style="width: 140px; height: 28px;"></div>
      </div>
      <!-- Skeleton category cards -->
      <div class="category-root">
        <div v-for="i in 4" :key="'sk-cat-' + i" class="category-card" style="pointer-events: none;">
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
        <section
          v-for="card in yearTimelineCards"
          :key="card.year"
          class="timeline-year-card"
          tabindex="0"
          @click="openTimelineModal(card.year)"
          @keydown.enter="openTimelineModal(card.year)"
        >
          <div class="timeline-year-header">
            <span class="timeline-year-number">{{ card.year }}</span>
            <span class="timeline-year-count">{{ card.total }} 篇文章</span>
          </div>
          <div class="timeline-year-calendar">
            <span
              v-for="m in 12"
              :key="m"
              class="timeline-year-calendar-month"
              :class="{ active: card.activeMonths[m - 1] }"
            >{{ m }}</span>
          </div>
        </section>
      </div>

      <!-- Category Cards Section -->
      <section class="section-header" style="margin-top: 3rem;">
        <h2 class="section-title">
          <i class="bx bx-folder" style="vertical-align: -2px; margin-right: 6px; font-size: 1.3rem;"></i>
          分类
        </h2>
      </section>
      <div class="category-root" id="category-root">
        <section
          v-for="cat in categoryCards"
          :key="cat.name"
          class="category-card"
          @click="openCategoryModal(cat.name)"
        >
          <div class="category-header">
            <h3 class="category-name">{{ cat.name }}</h3>
            <span class="category-count">{{ cat.count }} 篇</span>
          </div>
          <div class="category-posts">
            <div
              v-for="post in cat.posts"
              :key="post.id"
              class="category-post-item"
            >
              <a :href="post.link" class="category-post-title">{{ (post.title as RenderedText).rendered }}</a>
              <span class="category-post-date">{{ post.displayDate }}</span>
            </div>
          </div>
        </section>
      </div>
      </div>

      <!-- Timeline Modal -->
      <Teleport to="body">
        <Transition name="modal">
          <div
            v-if="activeModal === 'timeline' && timelineModalData"
            class="modal-mask"
            @click="onMaskClick"
          >
            <div class="timeline-modal" @click.stop>
              <button class="modal-close" @click="closeModal" aria-label="按 ESC 关闭">ESC</button>
              <div class="modal-content">
                <div v-if="modalLoading" class="modal-skeleton">
                  <div class="skeleton-title"></div>
                  <div class="skeleton-stats">
                    <div class="skeleton-stat-box" v-for="n in 3" :key="n"></div>
                  </div>
                  <div class="skeleton-month" v-for="n in 3" :key="n">
                    <div class="skeleton-month-label"></div>
                    <div class="skeleton-post" v-for="m in 2" :key="m"></div>
                  </div>
                </div>
                <div v-else class="modal-content-inner">
                    <h2 class="modal-title">{{ timelineModalData.year }} 年</h2>
                    <div class="modal-stats-grid">
                      <div class="modal-statbox">
                        <div class="stat-tooltip">汇总</div>
                        <div class="stat-icon">
                          <i class="bx bx-file-blank" style="font-size: 1.3rem;"></i>
                        </div>
                        <div class="stat-label">文章总数</div>
                        <div class="stat-value">{{ timelineModalData.total }}</div>
                      </div>
                      <div class="modal-statbox">
                        <div class="stat-tooltip">分类</div>
                        <div class="stat-icon">
                          <i class="bx bx-folder" style="font-size: 1.3rem;"></i>
                        </div>
                        <div class="stat-label">分类数</div>
                        <div class="stat-value">{{ timelineModalData.categories }}</div>
                      </div>
                      <div class="modal-statbox">
                        <div class="stat-tooltip">有文章的月份</div>
                        <div class="stat-icon">
                          <i class="bx bx-calendar-check" style="font-size: 1.3rem;"></i>
                        </div>
                        <div class="stat-label">活跃月份</div>
                        <div class="stat-value">{{ timelineModalData.months.length }}</div>
                      </div>
                    </div>
                    <div class="modal-month-groups">
                      <div
                        v-for="[monthLabel, monthPosts] in timelineModalData.months"
                        :key="monthLabel"
                        class="modal-month-group"
                      >
                        <h3 class="modal-month-title">{{ monthLabel }}</h3>
                        <div class="modal-post-list">
                          <a
                            v-for="post in monthPosts"
                            :key="post.id"
                            :href="post.link"
                            class="modal-post-item"
                          >
                            <span class="modal-post-title">{{ (post.title as RenderedText).rendered }}</span>
                            <span class="modal-post-date">{{ post.displayDate }}</span>
                          </a>
                        </div>
                      </div>
                    </div>
                </div>
              </div>
            </div>
          </div>
        </Transition>
      </Teleport>

    <!-- Category Modal -->
      <Teleport to="body">
        <Transition name="modal">
          <div
            v-if="activeModal === 'category' && selectedCategory"
            class="modal-mask"
            @click="onMaskClick"
          >
            <div class="timeline-modal" @click.stop>
              <button class="modal-close" @click="closeModal" aria-label="按 ESC 关闭">ESC</button>
              <div class="modal-content">
                <div v-if="modalLoading" class="modal-skeleton">
                  <div class="skeleton-title"></div>
                  <div class="skeleton-post" v-for="n in 4" :key="n"></div>
                </div>
                <div v-else class="modal-content-inner">
                  <div class="category-modal-header">
                    <h2 class="modal-title" style="margin:0;">{{ selectedCategory }}</h2>
                  </div>
                  <div class="modal-post-list">
                    <a
                      v-for="post in categoryCards.find((c) => c.name === selectedCategory)?.posts ?? []"
                      :key="post.id"
                      :href="post.link"
                      class="modal-post-item"
                    >
                      <span class="modal-post-title">{{ (post.title as RenderedText).rendered }}</span>
                      <span class="modal-post-date">{{ post.displayDate }}</span>
                    </a>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </Transition>
      </Teleport>
    </template>

    <!-- Empty -->
    <div v-else class="archives-empty">
      <p>还没有文章</p>
    </div>
  </div>
</template>

<style scoped>
/* ---------- Unified Animation Variables ---------- */
.archives-page {
  --anim-ease-enter: cubic-bezier(0.16, 1, 0.3, 1);
  --anim-ease-hover: cubic-bezier(0.34, 1.56, 0.64, 1);
  --anim-duration-enter: 0.5s;
  --anim-duration-hover: 0.35s;
}

@keyframes slideIn {
  from { opacity: 0; transform: translateX(-24px); }
  to { opacity: 1; transform: translateX(0); }
}

/* Content that replaces skeleton should not re-animate */
.content-area .section-header,
.content-area .timeline-year-card,
.content-area .category-card {
  animation: none;
  opacity: 1;
  transform: none;
}

.section-header {
  animation: slideIn var(--anim-duration-enter) var(--anim-ease-enter) both;
}
.section-header:nth-of-type(2) { animation-delay: 0.22s; }

.timeline-year-card {
  animation: slideIn var(--anim-duration-enter) var(--anim-ease-enter) both;
}
.timeline-year-card:nth-child(1) { animation-delay: 0.06s; }
.timeline-year-card:nth-child(2) { animation-delay: 0.11s; }
.timeline-year-card:nth-child(3) { animation-delay: 0.16s; }

.category-card {
  animation: slideIn var(--anim-duration-enter) var(--anim-ease-enter) both;
}
.category-card:nth-child(1) { animation-delay: 0.28s; }
.category-card:nth-child(2) { animation-delay: 0.34s; }
.category-card:nth-child(3) { animation-delay: 0.40s; }
.category-card:nth-child(4) { animation-delay: 0.46s; }
.category-card:nth-child(5) { animation-delay: 0.52s; }
.category-card:nth-child(6) { animation-delay: 0.58s; }

/* ---------- Layout ---------- */
.archives-page {
  padding: 25px;
}

.section-title {
  font-size: 1.4rem;
  font-weight: 700;
  color: var(--foreground, #333);
  margin: 0;
}

/* ---------- Loading / Error / Empty ---------- */
.archives-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 4rem 2rem;
  color: var(--foreground, #666);
  gap: 0.8rem;
}

/* ===== Timeline Year Cards ===== */
.timeline-root {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1.5rem;
  margin: 0 0 1rem;
}

.timeline-year-card {
  background: var(--card, rgba(255,255,255,0.7));
  border-radius: var(--radius-large, 8px);
  box-shadow: 0 4px 24px 0 rgba(0,0,0,0.07);
  padding: 1.2rem;
  display: flex;
  flex-direction: column;
  gap: 1rem;
  cursor: pointer;
  border: 1.5px solid var(--border, #e0e0e0);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  transition: all var(--anim-duration-hover) var(--anim-ease-hover);
}

.timeline-year-card:hover {
  transform: translateY(-6px) scale(1.02);
  box-shadow: 0 12px 52px -8px rgba(0,0,0,0.18);
  border-color: var(--primary, #505050);
}

.timeline-year-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.timeline-year-number {
  font-size: 2rem;
  font-weight: 800;
  color: var(--foreground, #222);
  line-height: 1.2;
}

.timeline-year-count {
  font-size: 0.9rem;
  color: var(--foreground, #888);
  background: var(--border, rgba(0,0,0,0.06));
  padding: 0.3rem 0.8rem;
  border-radius: var(--radius-full, 9999px);
  white-space: nowrap;
}

.timeline-year-calendar {
  display: grid;
  grid-template-columns: repeat(6, 1fr);
  grid-template-rows: repeat(2, 1fr);
  gap: 0.5rem;
}

.timeline-year-calendar-month {
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.85rem;
  font-weight: 600;
  color: var(--foreground, #999);
  background: var(--border, rgba(0,0,0,0.04));
  border-radius: var(--radius-medium, 6px);
  aspect-ratio: 1;
  transition: all var(--anim-duration-hover) var(--anim-ease-hover);
  border: 1.5px solid transparent;
}

.timeline-year-calendar-month.active {
  background: var(--primary, #505050);
  color: #fff;
  border-color: var(--primary, #505050);
  box-shadow: 0 2px 12px -4px var(--primary, #505050);
  transform: scale(1.05);
}

/* ===== Category Cards ===== */
.category-root {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 2rem;
  margin: 0;
}

.category-card {
  background: var(--card, rgba(255,255,255,0.7));
  border-radius: var(--radius-large, 8px);
  box-shadow: 0 4px 24px 0 rgba(0,0,0,0.07);
  padding: 1.2rem;
  display: flex;
  flex-direction: column;
  cursor: pointer;
  border: 1.5px solid var(--border, #e0e0e0);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  transition: all var(--anim-duration-hover) var(--anim-ease-hover);
}

.category-card:hover {
  transform: perspective(800px) translateY(-5px) rotateX(2deg);
  box-shadow: 0 10px 48px -4px rgba(0,0,0,0.13);
  border-color: var(--primary, #505050);
}

.category-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.8rem;
}

.category-name {
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--foreground, #222);
  margin: 0;
}

.category-count {
  font-size: 0.9rem;
  background: var(--border, rgba(0,0,0,0.08));
  padding: 0.3rem 0.8rem;
  border-radius: var(--radius-full, 9999px);
  color: var(--foreground, #666);
  white-space: nowrap;
}

.category-posts {
  padding-top: 0.8rem;
  border-top: 1px dashed var(--border, #e0e0e0);
}

.category-post-item {
  display: flex;
  justify-content: space-between;
  font-size: 0.9rem;
  padding: 0.3rem 0;
}

.category-post-title {
  flex: 1;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  color: var(--foreground, #555);
  text-decoration: none;
  transition: color 0.2s;
}

.category-post-title:hover {
  color: var(--primary, #505050);
}

.category-post-date {
  color: var(--foreground, #999);
  font-size: 0.8rem;
  margin-left: 0.8rem;
  flex-shrink: 0;
}

/* ===== Modal ===== */
.modal-mask {
  position: fixed;
  inset: 0;
  z-index: 9999;
  background: rgba(0,0,0,0.4);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1rem;
  backdrop-filter: blur(4px);
}

.timeline-modal {
  background: var(--card, rgba(255,255,255,0.98));
  border-radius: var(--radius-large, 8px);
  max-width: 640px;
  width: 100%;
  max-height: 85vh;
  overflow: hidden;
  padding: 2rem;
  box-shadow: 0 8px 32px rgba(0,0,0,0.15);
  border: 1px solid var(--border, rgba(0,0,0,0.08));
  backdrop-filter: blur(20px);
  -webkit-backdrop-filter: blur(20px);
  position: relative;
}

.timeline-modal .modal-content {
  max-height: calc(85vh - 4rem);
  overflow-y: auto;
  overflow-x: hidden;
  padding-right: 6px;
  margin-right: -6px;
}

/* ---------- Modal Enter/Leave ---------- */
.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.35s var(--anim-ease-enter), backdrop-filter 0.35s var(--anim-ease-enter);
}
.modal-enter-active .timeline-modal,
.modal-leave-active .timeline-modal {
  transition: transform 0.35s var(--anim-ease-enter), opacity 0.35s var(--anim-ease-enter);
}
.modal-enter-from,
.modal-leave-to {
  opacity: 0;
  backdrop-filter: blur(0px);
}
.modal-enter-from .timeline-modal,
.modal-leave-to .timeline-modal {
  transform: translateY(24px);
  opacity: 0;
}

/* ---------- Modal Content Stagger ---------- */
.modal-statbox {
  animation: slideIn 0.45s var(--anim-ease-enter) both;
}
.modal-statbox:nth-child(2) { animation-delay: 0.08s; }
.modal-statbox:nth-child(3) { animation-delay: 0.16s; }

.modal-month-group {
  animation: slideIn 0.4s var(--anim-ease-enter) both;
}
.modal-month-group:nth-child(2) { animation-delay: 0.06s; }
.modal-month-group:nth-child(3) { animation-delay: 0.12s; }
.modal-month-group:nth-child(4) { animation-delay: 0.18s; }
.modal-month-group:nth-child(5) { animation-delay: 0.24s; }

.modal-post-item {
  animation: slideIn 0.35s var(--anim-ease-enter) both;
}
.modal-month-group:nth-child(1) .modal-post-item:nth-child(2) { animation-delay: 0.05s; }
.modal-month-group:nth-child(1) .modal-post-item:nth-child(3) { animation-delay: 0.10s; }
.modal-month-group:nth-child(2) .modal-post-item:nth-child(1) { animation-delay: 0.03s; }
.modal-month-group:nth-child(2) .modal-post-item:nth-child(2) { animation-delay: 0.08s; }
.modal-month-group:nth-child(2) .modal-post-item:nth-child(3) { animation-delay: 0.13s; }
.modal-month-group:nth-child(3) .modal-post-item:nth-child(1) { animation-delay: 0.06s; }
.modal-month-group:nth-child(3) .modal-post-item:nth-child(2) { animation-delay: 0.11s; }
.modal-month-group:nth-child(3) .modal-post-item:nth-child(3) { animation-delay: 0.16s; }

/* Category modal post stagger (no month-group wrapper) */
.category-modal-header ~ .modal-post-list .modal-post-item:nth-child(1) { animation-delay: 0.15s; }
.category-modal-header ~ .modal-post-list .modal-post-item:nth-child(2) { animation-delay: 0.22s; }
.category-modal-header ~ .modal-post-list .modal-post-item:nth-child(3) { animation-delay: 0.29s; }
.category-modal-header ~ .modal-post-list .modal-post-item:nth-child(4) { animation-delay: 0.36s; }
.category-modal-header ~ .modal-post-list .modal-post-item:nth-child(5) { animation-delay: 0.43s; }

.modal-close {
  position: absolute;
  top: 1rem;
  right: 1rem;
  min-width: 3rem;
  height: 1.8rem;
  padding: 0 0.5rem;
  border-radius: var(--radius-small, 4px);
  border: 1px solid var(--border, rgba(0,0,0,0.15));
  background: var(--surface, rgba(0,0,0,0.03));
  color: var(--foreground, #666);
  font-family: 'SF Mono', 'Cascadia Code', 'Consolas', monospace;
  font-size: 0.7rem;
  font-weight: 700;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  line-height: 1;
  box-shadow: 0 2px 0 var(--border, rgba(0,0,0,0.12));
  user-select: none;
  transition: all 0.2s var(--anim-ease-hover);
}

.modal-close:hover {
  transform: translateY(1px);
  box-shadow: 0 1px 0 var(--border, rgba(0,0,0,0.12));
  background: var(--border, rgba(0,0,0,0.06));
  color: var(--foreground, #222);
}

.modal-close:active {
  transform: translateY(2px) scale(0.96);
  box-shadow: none;
}

.modal-title {
  font-size: 1.8rem;
  font-weight: 800;
  color: var(--foreground, #222);
  margin: 0 0 1.5rem;
}

/* Modal Stats */
.modal-stats-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1rem;
  margin-bottom: 2rem;
}

.modal-statbox {
  background: var(--border, rgba(0,0,0,0.03));
  border-radius: var(--radius-medium, 6px);
  padding: 1rem;
  text-align: center;
  border: 1.5px solid var(--border, rgba(0,0,0,0.06));
  transition: all 0.3s var(--anim-ease-hover);
  position: relative;
}

.modal-statbox .stat-tooltip {
  position: absolute;
  bottom: 100%;
  left: 50%;
  transform: translateX(-50%) translateY(-10px);
  background: rgba(0,0,0,0.85);
  color: #fff;
  padding: 0.4rem 0.6rem;
  border-radius: var(--radius-small, 4px);
  font-size: 0.7rem;
  white-space: nowrap;
  pointer-events: none;
  opacity: 0;
  visibility: hidden;
  transition: all 0.25s var(--anim-ease-hover);
  box-shadow: 0 4px 12px rgba(0,0,0,0.15);
  z-index: 999;
}

.modal-statbox .stat-tooltip::after {
  content: '';
  position: absolute;
  top: 100%;
  left: 50%;
  transform: translateX(-50%);
  border: 6px solid transparent;
  border-top-color: rgba(0,0,0,0.85);
}

.modal-statbox:hover .stat-tooltip {
  opacity: 1;
  visibility: visible;
  transform: translateX(-50%) translateY(0);
}

.modal-statbox:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 16px rgba(0,0,0,0.08);
}

.stat-icon {
  font-size: 1.15rem;
  margin-bottom: 0.4rem;
  opacity: 0.7;
}

.stat-label {
  font-size: 0.85rem;
  color: var(--foreground, #888);
  margin-bottom: 0.3rem;
  font-weight: 500;
}

.stat-value {
  font-size: 1.4rem;
  font-weight: 800;
  color: var(--foreground, #222);
  line-height: 1.2;
}

/* Modal Month Groups */
.modal-month-group {
  margin: 0 0 1.5rem;
}

.modal-month-title {
  font-size: 1.15rem;
  font-weight: 700;
  color: var(--foreground, #505050);
  margin-bottom: 1rem;
  padding-left: 1rem;
  position: relative;
}

.modal-month-title::before {
  content: '';
  position: absolute;
  left: 0;
  top: 50%;
  width: 3px;
  height: 1.2em;
  background: var(--primary, #505050);
  transform: translateY(-50%);
  border-radius: 3px;
  opacity: 0.7;
}

.modal-post-list {
  display: grid;
  gap: 0.6rem;
}

.modal-post-item {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 0.8rem 1rem;
  border-radius: 0.8rem;
  background: var(--border, rgba(0,0,0,0.03));
  border: 1px solid var(--border, rgba(0,0,0,0.06));
  text-decoration: none;
  color: inherit;
  cursor: pointer;
  transition: all 0.3s var(--anim-ease-hover);
}

.modal-post-item:hover {
  background: var(--border, rgba(0,0,0,0.05));
  transform: translateX(6px) scale(1.005);
  box-shadow: 0 2px 12px rgba(0,0,0,0.06);
}

.modal-post-title {
  flex: 1;
  font-weight: 500;
  font-size: 0.95rem;
  color: var(--foreground, #222);
  line-height: 1.5;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.modal-post-date {
  font-size: 0.8rem;
  color: var(--foreground, #999);
  background: var(--border, rgba(0,0,0,0.03));
  padding: 0.25rem 0.6rem;
  border-radius: 0.5rem;
  flex-shrink: 0;
}

/* Category Modal Header */
.category-modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 1.5rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid var(--border, #eee);
}



/* ===== Dark Mode Overrides ===== */
body.dark .timeline-year-card,
body.dark .category-card {
  background: rgba(30,30,30,0.92);
  border-color: #333;
}

body.dark .timeline-year-card:hover,
body.dark .category-card:hover {
  border-color: var(--primary, #fff);
}

body.dark .timeline-year-number {
  color: rgba(255,255,255,0.9);
}

body.dark .timeline-year-count {
  background: rgba(255,255,255,0.1);
  color: rgba(255,255,255,0.7);
}

body.dark .timeline-year-calendar-month {
  background: rgba(255,255,255,0.08);
  color: #aaa;
  border-color: #333;
}

body.dark .timeline-year-calendar-month.active {
  background: var(--primary, #fff);
  color: #222;
  border-color: var(--primary, #fff);
  box-shadow: 0 2px 12px -4px var(--primary, #fff);
}

body.dark .category-name {
  color: rgba(255,255,255,0.9);
}

body.dark .category-count {
  background: rgba(255,255,255,0.1);
  color: rgba(255,255,255,0.7);
}

body.dark .category-posts {
  border-top-color: #333;
}

body.dark .category-post-title {
  color: rgba(255,255,255,0.7);
}

body.dark .category-post-title:hover {
  color: var(--primary, #fff);
}

body.dark .category-post-date {
  color: rgba(255,255,255,0.5);
}

body.dark .timeline-modal {
  background: rgba(25,25,25,0.98);
  border-color: rgba(255,255,255,0.08);
}

body.dark .modal-title {
  color: rgba(255,255,255,0.9);
}

body.dark .modal-statbox {
  background: rgba(255,255,255,0.04);
  border-color: rgba(255,255,255,0.08);
}

body.dark .stat-label {
  color: rgba(255,255,255,0.5);
}

body.dark .stat-value {
  color: rgba(255,255,255,0.95);
}

body.dark .modal-month-title {
  color: rgba(255,255,255,0.9);
}

body.dark .modal-month-title::before {
  background: var(--primary, rgba(255,255,255,0.8));
}

body.dark .modal-post-item {
  background: rgba(255,255,255,0.03);
  border-color: rgba(255,255,255,0.06);
}

body.dark .modal-post-item:hover {
  background: rgba(255,255,255,0.05);
  border-color: rgba(255,255,255,0.1);
}

body.dark .modal-post-title {
  color: rgba(255,255,255,0.9);
}

body.dark .modal-post-date {
  color: rgba(255,255,255,0.5);
  background: rgba(255,255,255,0.08);
}

body.dark .category-modal-header {
  border-bottom-color: #333;
}


/* ===== Responsive ===== */
@media (max-width: 900px) {
  .timeline-root {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 600px) {
  .timeline-root {
    grid-template-columns: 1fr;
  }

  .timeline-year-calendar {
    grid-template-columns: repeat(6, 1fr);
    grid-template-rows: repeat(2, 1fr);
  }

  .archives-page {
    padding: 1rem 0.8rem 3rem;
  }

  .modal-stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }

  .timeline-modal {
    max-height: 82vh;
    padding: 1.5rem;
  }
}

/* Scrollbar for modal */
.timeline-modal::-webkit-scrollbar {
  width: 6px;
  height: 6px;
}

.timeline-modal::-webkit-scrollbar-track {
  background: transparent;
  border-radius: 3px;
}

.timeline-modal::-webkit-scrollbar-thumb {
  background: var(--border, rgba(0,0,0,0.15));
  border-radius: 3px;
}

/* ---------- Modal Skeleton ---------- */
@keyframes skeletonPulse {
  0%, 100% { opacity: 0.3; }
  25% { opacity: 0.7; }
  50% { opacity: 0.45; }
  75% { opacity: 0.65; }
}

.modal-skeleton {
  padding: 2rem;
  animation: skeletonPulse 2.2s ease-in-out infinite;
}

.skeleton-title {
  height: 32px;
  width: 200px;
  background: var(--skeleton-bg, rgba(128,128,128,0.15));
  border-radius: var(--radius-sm, 6px);
  margin-bottom: 1.5rem;
}

.skeleton-stats {
  display: flex;
  gap: 1rem;
  margin-bottom: 2rem;
}

.skeleton-stat-box {
  flex: 1;
  height: 96px;
  background: var(--skeleton-bg, rgba(128,128,128,0.15));
  border-radius: var(--radius-md, 12px);
}

.skeleton-month {
  margin-bottom: 1.5rem;
}

.skeleton-month-label {
  height: 24px;
  width: 120px;
  background: var(--skeleton-bg, rgba(128,128,128,0.15));
  border-radius: var(--radius-sm, 6px);
  margin-bottom: 0.75rem;
}

.skeleton-post {
  height: 20px;
  width: 100%;
  background: var(--skeleton-bg, rgba(128,128,128,0.15));
  border-radius: var(--radius-xs, 4px);
  margin-bottom: 0.5rem;
}

.skeleton-post:last-child {
  width: 75%;
}
</style>
