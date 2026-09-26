<script setup lang="ts">
import { ref, computed } from 'vue'
import { version as vueVersion } from 'vue'
import { useSiteShell } from '@/composables/useSiteShell'

declare const __BUILD_TIME__: string

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

  ]
})

const userItems = computed<TechInfoItem[]>(() => {
  const items = (siteInfo.value as Record<string, unknown>).techInfoItems
  return Array.isArray(items) ? (items as TechInfoItem[]) : []
})

const allItems = computed<TechInfoItem[]>(() => [...userItems.value, ...autoItems.value])

const techVersions = computed<TechInfoItem[]>(() => {
  const s = siteInfo.value
  return [
    { label: 'WordPress', value: s.wpVersion || '6.x' },
    { label: 'Version', value: s.themeVersion || '-' },
    { label: 'Vue', value: vueVersion },
    { label: 'Tailwind CSS', value: '^4.3' },
    { label: 'Prism', value: '^1.30.0' },
	    { label: 'REST API', value: s.restApiVersion ? `simple-theme/${s.restApiVersion}` : 'v1' },
    { label: 'Build', value: new Date(__BUILD_TIME__).toISOString().replace('T', ' · ').replace(/\.\d{3}Z$/, ' UTC') },
  ]
})
</script>

<template>
  <div v-if="!shellLoading" class="aside-card">
    <h3 class="aside-card__title">信息 <span>Info.</span></h3>

    <div class="tech-info__list">
      <template v-for="(item, index) in allItems" :key="index">
        <div class="tech-info__label">{{ item.label }}</div>
        <div class="tech-info__value">{{ item.value }}</div>
      </template>

      <div class="tech-info__toggle" @click="expanded = !expanded">
        <span>{{ expanded ? '收起构建信息' : '展开构建信息' }}</span>
        <svg
          class="tech-info__chevron"
          :class="{ 'is-collapsed': !expanded }"
          width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"
        >
          <polyline points="6 9 12 15 18 9"/>
        </svg>
      </div>
    </div>

    <div class="tech-info__collapsible" :class="{ 'is-expanded': expanded }">
      <div class="tech-info__versions">
        <div
          v-for="(v, i) in techVersions"
          :key="i"
          class="tech-info__version"
        >
          <div class="tech-info__version-label">{{ v.label }}</div>
          <div class="tech-info__version-value">{{ v.value }}</div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.tech-info__list {
  display: grid;
  grid-template-columns: auto 1fr;
  column-gap: 16px;
  row-gap: 8px;
  margin-bottom: 2px;
}

.tech-info__label,
.tech-info__value {
  display: flex;
  align-items: center;
  font-size: 14px;
}

.tech-info__label {
  color: var(--secondary);
}

.tech-info__value {
  justify-content: flex-end;
  text-align: right;
  color: var(--foreground);
}

.tech-info__toggle {
  grid-column: 1 / -1;
  display: flex;
  align-items: center;
  justify-content: space-between;
  font-size: 14px;
  color: var(--secondary);
  cursor: pointer;
  user-select: none;
  transition: color 0.15s;
}

.tech-info__toggle:hover {
  color: var(--foreground);
}

.tech-info__chevron {
  color: var(--secondary);
  transition: transform var(--transition);
}

.tech-info__chevron.is-collapsed {
  transform: rotate(-90deg);
}

/* 0fr → 1fr 的 grid 过渡实现展开动画 */
.tech-info__collapsible {
  display: grid;
  grid-template-rows: 0fr;
  transition: grid-template-rows 0.35s cubic-bezier(0.22, 1, 0.36, 1);
}

.tech-info__collapsible.is-expanded {
  grid-template-rows: 1fr;
}

.tech-info__versions {
  margin-top: 12px;
  display: flex;
  flex-wrap: wrap;
  column-gap: 8px;
  row-gap: 12px;
  overflow: hidden;
}

.tech-info__version {
  display: flex;
  flex: 1;
  flex-direction: column;
  align-items: center;
  min-width: calc(33.333% - 6px);
  text-align: center;
}

.tech-info__version-label {
  margin-bottom: 4px;
  font-size: 13px;
  color: var(--secondary);
}

.tech-info__version-value {
  font-size: 14px;
  font-weight: 500;
  color: var(--foreground);
}
</style>
