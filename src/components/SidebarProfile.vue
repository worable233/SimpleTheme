<script setup lang="ts">
import { computed } from 'vue'
import { useSiteShell } from '@/composables/useSiteShell'
import AppIcon from '@/components/AppIcon.vue'
import type { SocialLink, SiteStats } from '@/types/wordpress'

const props = defineProps<{
  /** 小工具实例设置；未传时（fallback 默认渲染）全部显示 */
  settings?: { showStats: boolean; showHeatmap: boolean; showSocial: boolean }
}>()

defineEmits<{
  'toggle-sub': []
}>()

const { siteInfo, shellLoading } = useSiteShell()

const noToggle = defineModel<boolean>('noToggle', { default: false })

const showStatsCard = computed(() => props.settings?.showStats ?? true)
const showHeatmapCard = computed(() => props.settings?.showHeatmap ?? true)
const showSocialCard = computed(() => props.settings?.showSocial ?? true)

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
  return stats.value?.heatmapData ?? []
})

const monthNames = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec']

/** 获取某日期所在的周一（或更早最近的周一） */
function getMonday(date: Date): Date {
  const d = new Date(date)
  const day = d.getDay() // 0=Sun…6=Sat
  const diff = day === 0 ? -6 : 1 - day // 回退到周一
  d.setDate(d.getDate() + diff)
  return d
}

/** 计算某日期基于周一锚点的周列索引 (0-based) */
function weekCol(date: Date, mondayRef: Date): number {
  const ms = date.getTime() - mondayRef.getTime()
  return Math.floor(Math.round(ms / 86400000) / 7)
}

// 从两个月前的 1 号开始，正好覆盖最近三个完整月份
const heatmapCells = computed(() => {
  const data = heatmapData.value
  const dataMap = new Map(data.map(d => [d.day, d.count]))
  const now = new Date()
  const startDate = new Date(now.getFullYear(), now.getMonth() - 2, 1)
  const mondayRef = getMonday(startDate)
  const today = new Date(now.getFullYear(), now.getMonth(), now.getDate())
  const DAYS = Math.round((today.getTime() - startDate.getTime()) / 86400000) + 1
  const maxCount = Math.max(...dataMap.values(), 1)
  const step = Math.max(1, Math.ceil(maxCount / 4))

  const cells: { day: string; count: number; level: number; col: number; row: number }[] = []
  for (let i = 0; i < DAYS; i++) {
    const date = new Date(startDate)
    date.setDate(startDate.getDate() + i)
    const dayStr = ldate(date)
    const count = dataMap.get(dayStr) || 0
    const level = count === 0 ? 0 : Math.min(Math.ceil(count / step), 4)
    cells.push({
      day: dayStr,
      count,
      level,
      col: weekCol(date, mondayRef),
      row: (date.getDay() + 6) % 7, // 0=Mon…6=Sun
    })
  }
  return cells
})

const monthLabels = computed(() => {
  const labels: { name: string; col: number }[] = []
  const now = new Date()
  const startDate = new Date(now.getFullYear(), now.getMonth() - 2, 1)
  const mondayRef = getMonday(startDate)
  const today = new Date(now.getFullYear(), now.getMonth(), now.getDate())
  const DAYS = Math.round((today.getTime() - startDate.getTime()) / 86400000) + 1
  let currentMonth = -1
  for (let i = 0; i < DAYS; i++) {
    const date = new Date(startDate)
    date.setDate(startDate.getDate() + i)
    const month = date.getMonth()
    const col = weekCol(date, mondayRef)
    if (month !== currentMonth) {
      labels.push({ name: monthNames[month] ?? '', col })
      currentMonth = month
    }
  }
  return labels
})

// 局部日期格式化 YYYY-MM-DD
function ldate(date: Date): string {
  const y = date.getFullYear()
  const m = String(date.getMonth() + 1).padStart(2, '0')
  const d = String(date.getDate()).padStart(2, '0')
  return `${y}-${m}-${d}`
}

const dayLabels = [
  { text: 'Mon', row: 0 },
  { text: 'Wed', row: 2 },
  { text: 'Fri', row: 4 },
  { text: 'Sun', row: 6 },
]

// GitHub 风格热力图色阶（light / dark）
const HEATMAP_LEVEL_CLASSES = [
  'bg-[#ebedf0] dark:bg-[#2a2a2a]',
  'bg-[#9be9a8] dark:bg-[#0d4429]',
  'bg-[#40c463] dark:bg-[#006d32]',
  'bg-[#30a14e] dark:bg-[#26a641]',
  'bg-[#216e39] dark:bg-[#39d353]',
]

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

function socialIconName(icon: string): string {
  // 兼容旧值：完整类名（含空格，如 "bx bxl-github"）或裸名（如 "github"）
  return icon.includes(' ') ? icon : `bxl-${icon}`
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

        <div v-if="!noToggle" class="aside-btn-open absolute top-2.5 right-2.5 inline-flex cursor-pointer items-center gap-1 rounded-[5px] border-none bg-white/50 py-1 pr-2 pl-3.5 text-sm text-foreground shadow-[0_0_10px_rgba(0,0,0,0.1)] backdrop-blur-[10px] transition-colors duration-200 hover:bg-white/70 hover:shadow-[0_0_20px_rgba(0,0,0,0.1)] dark:bg-black/35 dark:hover:bg-black/50" @click="$emit('toggle-sub')">
          查看更多
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><polyline points="9 18 15 12 9 6"/></svg>
        </div>
      </div>
    </template>
  </div>

  <!-- ======== Card 2: 站点统计 ======== -->
  <div v-if="showStatsCard && stats" class="aside-card aside-card--stats">
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
  <div v-if="showHeatmapCard && heatmapData.length > 0" class="aside-card aside-card--heatmap">
    <h3 class="aside-card__title">贡献 <span>Heatmap.</span></h3>
    <div class="mx-auto max-w-[200px]">
      <div class="mb-0.5 grid grid-cols-[22px_repeat(13,1fr)] gap-0.5 text-[9px] text-muted-foreground">
        <span
          v-for="m in monthLabels"
          :key="m.name"
          class="text-[9px] leading-none"
          :style="{ gridColumn: m.col + 2 }"
        >{{ m.name }}</span>
      </div>
      <div class="grid grid-cols-[22px_repeat(13,1fr)] grid-rows-[repeat(7,auto)] gap-[3px]">
        <span
          v-for="d in dayLabels"
          :key="d.text"
          class="flex items-center justify-end pr-0.5 text-[8px] leading-none text-muted-foreground"
          :style="{ gridColumn: 1, gridRow: d.row + 1 }"
        >{{ d.text }}</span>
        <div
          v-for="cell in heatmapCells"
          :key="cell.day"
          class="aspect-square cursor-default rounded-[2px]"
          :class="HEATMAP_LEVEL_CLASSES[cell.level]"
          :title="`${cell.day}: ${cell.count} 篇`"
          :style="{ gridColumn: cell.col + 2, gridRow: cell.row + 1 }"
        ></div>
      </div>
      <div class="mt-1.5 flex items-center justify-end gap-[3px] pr-0.5">
        <span class="text-[9px] leading-none text-muted-foreground">Less</span>
        <span
          v-for="(cls, level) in HEATMAP_LEVEL_CLASSES"
          :key="level"
          class="inline-block h-2.5 w-2.5 rounded-[2px]"
          :class="cls"
        ></span>
        <span class="text-[9px] leading-none text-muted-foreground">More</span>
      </div>
    </div>
  </div>

  <!-- ======== Card 4: 社交链接 ======== -->
  <div v-if="showSocialCard && socialLinks && socialLinks.length > 0" class="aside-card aside-card--social">
    <h3 class="aside-card__title">社交 <span>Social.</span></h3>
    <div class="social-content">
      <div class="social-icons">
        <a v-for="link in socialLinks" :key="link.label" :href="link.url" target="_blank" rel="noopener noreferrer" :title="link.label">
          <AppIcon :name="socialIconName(link.icon)" :size="20" />
        </a>
      </div>
    </div>
  </div>
</template>
