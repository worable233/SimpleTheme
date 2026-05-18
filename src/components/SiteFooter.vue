<script setup lang="ts">
import { computed } from 'vue'
import { useSiteShell } from '@/composables/useSiteShell'
import type { SiteInfo } from '@/types/wordpress'

const { shellLoading } = useSiteShell()

const props = defineProps<{
  siteInfo: SiteInfo
}>()

const currentYear = new Date().getFullYear()
const copyrightStyle = computed(() => props.siteInfo.theme?.copyrightStyle || 'detailed')
</script>

<template>
  <div class="sidebar-footer">
    <template v-if="shellLoading">
      <div style="display: flex; flex-direction: column; gap: 0.25rem; padding: 0.75rem 0">
        <div role="status" class="skeleton line" style="width: 60%"></div>
        <div role="status" class="skeleton line" style="width: 40%"></div>
      </div>
    </template>
    <template v-else>
      <div v-if="copyrightStyle !== 'none'" class="copyright">
        <p v-if="copyrightStyle === 'detailed'">Copyright © {{ currentYear }} {{ siteInfo.name }} All Rights Reserved.</p>
        <p v-else>{{ currentYear }} © {{ siteInfo.name }}.</p>
        <p v-if="siteInfo.theme?.showCredit !== false">Theme <a class="footer-theme-link" href="https://github.com/worable233/SimpleTheme" target="_blank" rel="noopener noreferrer">SimpleTheme</a>.</p>
      </div>
    </template>
  </div>
</template>
