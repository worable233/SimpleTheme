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
    { label: 'PHP', value: s.phpVersion || '8.x' },
    { label: 'REST API', value: s.restApiVersion ? `simple-theme/${s.restApiVersion}` : 'v1' },
    { label: 'OS', value: s.serverOs || 'Unknown' },
    { label: 'Build', value: new Date(__BUILD_TIME__).toISOString().replace('T', ' · ').replace(/\.\d{3}Z$/, ' UTC') },
  ]
})
</script>

<template>
  <div v-if="!shellLoading" class="aside-card">
    <h3 class="aside-card__title">信息 <span>Info.</span></h3>

    <div class="mb-0.5 grid grid-cols-[auto_1fr] gap-x-4 gap-y-2">
      <template v-for="(item, index) in allItems" :key="index">
        <div class="flex items-center text-sm text-secondary">{{ item.label }}</div>
        <div class="flex items-center justify-end text-right text-sm text-foreground">{{ item.value }}</div>
      </template>

      <div
        class="col-span-full flex cursor-pointer items-center justify-between text-sm text-secondary transition-colors duration-150 select-none hover:text-foreground"
        @click="expanded = !expanded"
      >
        <span>{{ expanded ? '收起构建信息' : '展开构建信息' }}</span>
        <svg
          class="text-secondary transition-transform duration-300 ease-[cubic-bezier(0.34,1.56,0.64,1)]"
          :class="{ '-rotate-90': !expanded }"
          width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"
        >
          <polyline points="6 9 12 15 18 9"/>
        </svg>
      </div>
    </div>

    <div
      class="grid grid-rows-[0fr] transition-[grid-template-rows] duration-[350ms] ease-[cubic-bezier(0.22,1,0.36,1)]"
      :class="{ 'grid-rows-[1fr]': expanded }"
    >
      <div class="mt-3 flex flex-wrap gap-x-2 gap-y-3 overflow-hidden">
        <div
          v-for="(v, i) in techVersions"
          :key="i"
          class="flex min-w-[calc(33.333%-6px)] flex-1 flex-col items-center text-center"
        >
          <div class="mb-1 text-[13px] text-secondary">{{ v.label }}</div>
          <div class="text-sm font-medium text-foreground">{{ v.value }}</div>
        </div>
      </div>
    </div>
  </div>
</template>
