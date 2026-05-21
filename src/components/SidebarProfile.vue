<script setup lang="ts">
import { computed, ref } from 'vue'
import { useSiteShell } from '@/composables/useSiteShell'
import { getThemeConfig } from '@/lib/theme-config'
import type { SocialLink, SiteStats, SimpleThemeConfig } from '@/types/wordpress'

const features = computed(() => getThemeConfig().features ?? ({} as NonNullable<SimpleThemeConfig['features']>))

defineEmits<{
  'toggle-sub': []
}>()

const { siteInfo, shellLoading } = useSiteShell()

const noToggle = defineModel<boolean>('noToggle', { default: false })

const avatarUrl = computed(() => siteInfo.value.hero?.avatar || '')
const showAvatar = computed(() => siteInfo.value.hero?.showAvatar || false)
const siteName = computed(() => siteInfo.value.name || '')
const motto = computed(() => siteInfo.value.hero?.subtitle || siteInfo.value.description || '')
const coverUrl = computed(() => siteInfo.value.hero?.image || '')
const stats = computed<SiteStats | undefined>(() => siteInfo.value.stats)
const socialLinks = computed<SocialLink[] | undefined>(() => siteInfo.value.socialLinks)

// ----- 贡献热力图 (GitHub-style heatmap) -----
interface HeatmapEntry {
  day: string
  count: number
}

const heatmapData = computed<HeatmapEntry[]>(() => {
  return (stats.value as any)?.heatmapData ?? []
})

const monthNames = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec']

const heatmapCells = computed(() => {
  const data = heatmapData.value
  const dataMap = new Map(data.map(d => [d.day, d.count]))
  const cells: { day: string; count: number; level: number; col: number; row: number }[] = []
  const today = new Date()
  const startDate = new Date(today)
  startDate.setDate(startDate.getDate() - 364)

  // 按最大 count 分 4 档
  const maxCount = Math.max(...dataMap.values(), 1)
  const step = Math.max(1, Math.ceil(maxCount / 4))

  for (let i = 0; i < 365; i++) {
    const date = new Date(startDate)
    date.setDate(startDate.getDate() + i)
    const dayStr = date.toISOString().split('T')[0] ?? ''
    const count = dataMap.get(dayStr) || 0
    const level = count === 0 ? 0 : Math.min(Math.ceil(count / step), 4)
    cells.push({
      day: dayStr,
      count,
      level,
      col: Math.floor(i / 7),
      row: i % 7,
    })
  }
  return cells
})

const monthLabels = computed(() => {
  const labels: { name: string; col: number }[] = []
  const today = new Date()
  const startDate = new Date(today)
  startDate.setDate(startDate.getDate() - 364)

  let currentMonth = -1
  for (let i = 0; i < 365; i++) {
    const date = new Date(startDate)
    date.setDate(startDate.getDate() + i)
    const month = date.getMonth()
    const col = Math.floor(i / 7)
    if (month !== currentMonth) {
      labels.push({ name: monthNames[month] ?? '', col })
      currentMonth = month
    }
  }
  return labels
})

function formatWordCount(count: number | undefined | null): string {
  if (!count) return '0'
  if (count >= 10000) {
    return (count / 10000).toFixed(1).replace(/\.0$/, '') + '万'
  }
  return count.toLocaleString()
}

function daysSince(isoDate: string): string {
  if (!isoDate) return '--'
  const then = new Date(isoDate)
  const now = new Date()
  const diffMs = now.getTime() - then.getTime()
  const days = Math.floor(diffMs / (1000 * 60 * 60 * 24))
  return days >= 0 ? `${days} 天` : '--'
}

function daysAgo(isoDate: string): string {
  if (!isoDate) return '--'
  const then = new Date(isoDate)
  const now = new Date()
  const diffMs = now.getTime() - then.getTime()
  const hours = Math.floor(diffMs / (1000 * 60 * 60))
  if (hours < 1) {
    const minutes = Math.floor(diffMs / (1000 * 60))
    return minutes >= 0 ? `${minutes} 分钟前` : '--'
  }
  if (hours < 24) return `${hours} 小时前`
  const days = Math.floor(hours / 24)
  return days >= 0 ? `${days} 天前` : '--'
}

function socialIconHtml(icon: string): string {
  return `<i class="${icon}"></i>`
}
</script>

<template>
  <!-- ======== Card 1: 个人资料（头像 + 站名 + 格言 + 查看更多） ======== -->
  <div class="aside-card aside-card--profile">
    <template v-if="shellLoading">
      <div class="aside-card__skeleton">
        <div class="aside-author__cover" style="background:var(--muted);"></div>
        <div class="aside-author__info">
          <div class="aside-author__avatar">
            <div role="status" class="skeleton box" style="width:80px;height:80px;border-radius:50%;margin:0 auto;"></div>
          </div>
          <div class="aside-author__name">
            <div role="status" class="skeleton" style="width:50%;height:16px;margin:0 auto;"></div>
          </div>
          <div class="aside-author__des">
            <div role="status" class="skeleton" style="width:70%;height:14px;margin:0 auto;"></div>
          </div>
        </div>
      </div>
    </template>

    <template v-else>
      <div class="aside-author__cover">
        <img v-if="coverUrl" :src="coverUrl" alt="" loading="lazy" />
        <div v-else style="width:100%;height:100%;background:var(--muted);"></div>
      </div>

      <div class="aside-author__info">
        <div class="aside-author__avatar">
          <img v-if="showAvatar && avatarUrl" :src="avatarUrl" alt="" />
          <abbr v-else-if="siteName" :title="siteName">{{ siteName.charAt(0) }}</abbr>
          <div v-else role="status" class="skeleton box" style="width:80px;height:80px;border-radius:50%;"></div>
        </div>

        <div v-if="siteName" class="aside-author__name">{{ siteName }}</div>
        <div v-if="motto" class="aside-author__des">“{{ motto }}”</div>

        <div v-if="!noToggle" class="aside-btn-open" @click="$emit('toggle-sub')">
          查看更多
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><polyline points="9 18 15 12 9 6"/></svg>
        </div>
      </div>
    </template>
  </div>

  <!-- ======== Card 2: 站点统计 ======== -->
  <div v-if="features.showStats !== false && stats" class="aside-card aside-card--stats">
    <h3 class="aside-card__title">统计 <span>Stats.</span></h3>
    <div class="aside-author__stats">
      <div><i>文章</i><span>{{ stats.postCount }}</span></div>
      <div><i>分类</i><span>{{ stats.categoryCount }}</span></div>
      <div><i>标签</i><span>{{ stats.tagCount }}</span></div>
      <div><i>总字数</i><span>{{ formatWordCount(stats.totalWordCount) }}</span></div>
      <div><i>运行时长</i><span>{{ daysSince(stats.registeredDate) }}</span></div>
      <div><i>最后活动</i><span>{{ daysAgo(stats.lastActivityDate) }}</span></div>
    </div>
  </div>

  <!-- ======== Card 3: 贡献热力图 ======== -->
  <div v-if="features.showHeatmap !== false && heatmapData.length > 0" class="aside-card aside-card--heatmap">
    <h3 class="aside-card__title">贡献 <span>Heatmap.</span></h3>
    <div class="heatmap-month-labels">
      <span
        v-for="m in monthLabels"
        :key="m.name"
        class="heatmap-month-label"
        :style="{ gridColumn: m.col + 1 }"
      >{{ m.name }}</span>
    </div>
    <div class="heatmap-grid">
      <div
        v-for="cell in heatmapCells"
        :key="cell.day"
        class="heatmap-cell"
        :class="`level-${cell.level}`"
        :title="`${cell.day}: ${cell.count} 篇`"
        :style="{ gridColumn: cell.col + 1, gridRow: cell.row + 1 }"
      ></div>
    </div>
  </div>

  <!-- ======== Card 4: 社交链接 ======== -->
  <div v-if="features.showSocial !== false && socialLinks && socialLinks.length > 0" class="aside-card aside-card--social">
    <h3 class="aside-card__title">社交 <span>Social.</span></h3>
    <div class="social-content">
      <ul>
        <li v-for="link in socialLinks" :key="link.label">
          <a :href="link.url" target="_blank" rel="noopener noreferrer" :title="link.label" v-html="socialIconHtml(link.icon)"></a>
        </li>
      </ul>
    </div>
  </div>
</template>

<style scoped>
.aside-btn-open {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  position: absolute;
  top: 10px;
  right: 10px;
  padding: 4px 8px 4px 14px;
  border-radius: 5px;
  font-size: 14px;
  color: var(--foreground);
  background: rgba(255, 255, 255, 0.5);
  backdrop-filter: blur(10px);
  box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
  cursor: pointer;
  transition: background 0.2s;
  border: none;
}

.aside-btn-open:hover {
  background: rgba(255, 255, 255, 0.7);
  box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
}

/* ----- 贡献热力图 ----- */
.aside-heatmap {
  padding: 8px 4px 4px;
}

.heatmap-month-labels {
  display: grid;
  grid-template-columns: repeat(53, 1fr);
  font-size: 9px;
  color: var(--muted-foreground, #888);
  margin-bottom: 2px;
  padding-left: 0;
}

.heatmap-month-label {
  font-size: 9px;
  line-height: 1;
}

.heatmap-grid {
  display: grid;
  grid-template-columns: repeat(53, 1fr);
  grid-template-rows: repeat(7, 1fr);
  gap: 2px;
}

.heatmap-cell {
  aspect-ratio: 1;
  border-radius: 2px;
  background: var(--heatmap-0, #ebedf0);
  cursor: default;
}

.heatmap-cell.level-1 {
  background: var(--heatmap-1, #9be9a8);
}

.heatmap-cell.level-2 {
  background: var(--heatmap-2, #40c463);
}

.heatmap-cell.level-3 {
  background: var(--heatmap-3, #30a14e);
}

.heatmap-cell.level-4 {
  background: var(--heatmap-4, #216e39);
}
</style>
