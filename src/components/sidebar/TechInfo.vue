<script setup lang="ts">
import { ref, computed } from 'vue'
import { version as vueVersion } from 'vue'
import { useSiteShell } from '@/composables/useSiteShell'

const expanded = ref(false)

const { siteInfo, shellLoading } = useSiteShell()

interface TechInfoItem {
  label: string
  value: string
}

const licenseMap: Record<string, string> = {
  'cc-by-40': 'CC BY 4.0',
  'cc-by-sa-40': 'CC BY-SA 4.0',
  'cc-by-nc-40': 'CC BY-NC 4.0',
  'cc-by-nc-sa-40': 'CC BY-NC-SA 4.0',
  'cc-by-nd-40': 'CC BY-ND 4.0',
  'cc-by-nc-nd-40': 'CC BY-NC-ND 4.0',
  'cc0-10': 'CC0 1.0',
  mit: 'MIT',
  arr: 'All Rights Reserved',
}

const autoItems = computed<TechInfoItem[]>(() => {
  const s = siteInfo.value
  const licenseKey = s.theme?.articleLicense
  const articleLicense = licenseKey && licenseKey !== 'none' ? licenseMap[licenseKey] || licenseKey : '无'
  return [
    { label: '文章许可', value: articleLicense },
    { label: '规范域名', value: window.location.hostname },
    ...(s.themeVersion ? [{ label: '主题版本', value: s.themeVersion }] : []),
  ]
})

const userItems = computed<TechInfoItem[]>(() => {
  const items = (siteInfo.value as Record<string, unknown>).techInfoItems
  return Array.isArray(items) ? (items as TechInfoItem[]) : []
})

const allItems = computed<TechInfoItem[]>(() => [...userItems.value, ...autoItems.value])

function detectOS(): string {
  const ua = navigator.userAgent
  if (ua.includes('Windows')) return 'Windows'
  if (ua.includes('Mac OS X')) return 'macOS'
  if (ua.includes('Linux') && !ua.includes('Android')) return 'Linux'
  if (ua.includes('Android')) return 'Android'
  if (ua.includes('iPhone') || ua.includes('iPad')) return 'iOS'
  return 'Unknown'
}

const techVersions = computed<TechInfoItem[]>(() => {
  const s = siteInfo.value
  return [
    { label: 'WordPress', value: s.wpVersion || '6.x' },
    { label: 'Vue', value: vueVersion },
    { label: 'OatUI', value: '^0.5.1' },
    { label: 'Prism', value: '^1.30.0' },
    { label: 'PHP', value: s.phpVersion || '8.x' },
    { label: 'REST API', value: s.restApiVersion ? `simple-theme/${s.restApiVersion}` : 'v1' },
    { label: 'OS', value: detectOS() },
  ]
})
</script>

<template>
  <div class="aside-card">
    <h3 class="aside-card__title">技术信息 <span>Tech Info.</span></h3>

    <div class="info-grid">
      <template v-for="(item, index) in allItems" :key="index">
        <div class="info-label">{{ item.label }}</div>
        <div class="info-value">{{ item.value }}</div>
      </template>

      <div class="toggle-row" :class="{ collapsed: !expanded }" @click="expanded = !expanded">
        <span>{{ expanded ? '收起构建信息' : '展开构建信息' }}</span>
        <svg class="toggle-row__icon" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
          <polyline points="6 9 12 15 18 9"/>
        </svg>
      </div>
    </div>

    <div class="env-section-wrap" :class="{ 'env-section-wrap--open': expanded }">
      <div class="env-section">
        <div v-for="(v, i) in techVersions" :key="i" class="env-item">
          <div class="env-name">{{ v.label }}</div>
          <div class="env-version">{{ v.value }}</div>
        </div>
      </div>
    </div>
  </div>
</template>
