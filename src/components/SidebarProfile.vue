<script setup lang="ts">
import { computed } from 'vue'
import { useSiteShell } from '@/composables/useSiteShell'
import type { SocialLink, SiteStats } from '@/types/wordpress'

defineEmits<{
  'toggle-sub': []
}>()

const { siteInfo, shellLoading } = useSiteShell()

const avatarUrl = computed(() => siteInfo.value.hero?.avatar || '')
const showAvatar = computed(() => siteInfo.value.hero?.showAvatar || false)
const siteName = computed(() => siteInfo.value.name || '')
const motto = computed(() => siteInfo.value.hero?.subtitle || siteInfo.value.description || '')
const coverUrl = computed(() => siteInfo.value.hero?.image || '')
const stats = computed<SiteStats | undefined>(() => siteInfo.value.stats)
const socialLinks = computed<SocialLink[] | undefined>(() => siteInfo.value.socialLinks)

function formatWordCount(count: number): string {
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
  const days = Math.floor(diffMs / (1000 * 60 * 60 * 24))
  return days >= 0 ? `${days} 天前` : '--'
}

function socialIconHtml(icon: string): string {
  return `<i class="${icon}"></i>`
}
</script>

<template>
  <div class="aside-author">
    <div class="aside-author__cover">
      <img v-if="coverUrl" :src="coverUrl" alt="" loading="lazy" />
      <div v-else style="width:100%;height:100%;background:var(--muted);"></div>
    </div>

    <div class="aside-author__info">
      <template v-if="shellLoading">
        <div class="aside-author__avatar">
          <div role="status" class="skeleton box" style="width:80px;height:80px;border-radius:50%;margin:0 auto;"></div>
        </div>
        <div class="aside-author__name">
          <div role="status" class="skeleton" style="width:50%;height:16px;margin:0 auto;"></div>
        </div>
        <div class="aside-author__des">
          <div role="status" class="skeleton" style="width:70%;height:14px;margin:0 auto;"></div>
        </div>
        <div class="aside-author__stats is-loading">
          <div><div role="status" class="skeleton" style="width:36px;height:18px;margin:0 auto;"></div></div>
          <div><div role="status" class="skeleton" style="width:36px;height:18px;margin:0 auto;"></div></div>
          <div><div role="status" class="skeleton" style="width:36px;height:18px;margin:0 auto;"></div></div>
          <div><div role="status" class="skeleton" style="width:48px;height:18px;margin:0 auto;"></div></div>
          <div><div role="status" class="skeleton" style="width:56px;height:18px;margin:0 auto;"></div></div>
          <div><div role="status" class="skeleton" style="width:56px;height:18px;margin:0 auto;"></div></div>
        </div>
      </template>

      <template v-else>
        <div class="aside-author__avatar">
          <img v-if="showAvatar && avatarUrl" :src="avatarUrl" alt="" />
          <abbr v-else-if="siteName" :title="siteName">{{ siteName.charAt(0) }}</abbr>
          <div v-else role="status" class="skeleton box" style="width:80px;height:80px;border-radius:50%;"></div>
        </div>

        <div v-if="siteName" class="aside-author__name">{{ siteName }}</div>
        <div v-if="motto" class="aside-author__des">“{{ motto }}”</div>

        <div v-if="stats" class="aside-author__stats">
          <div><i>文章</i><span>{{ stats.postCount }}</span></div>
          <div><i>分类</i><span>{{ stats.categoryCount }}</span></div>
          <div><i>标签</i><span>{{ stats.tagCount }}</span></div>
          <div><i>总字数</i><span>{{ formatWordCount(stats.totalWordCount) }}</span></div>
          <div><i>运行时长</i><span>{{ daysSince(stats.registeredDate) }}</span></div>
          <div><i>最后活动</i><span>{{ daysAgo(stats.lastActivityDate) }}</span></div>
        </div>

        <div class="aside-btn-open" @click="$emit('toggle-sub')">
          查看更多
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><polyline points="9 18 15 12 9 6"/></svg>
        </div>
      </template>
    </div>
  </div>

  <div v-if="socialLinks && socialLinks.length > 0" class="aside-section aside-social">
    <h3 class="aside-section__title">社交 <span>Social.</span></h3>
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
</style>
