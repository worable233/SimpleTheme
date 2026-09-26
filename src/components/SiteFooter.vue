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
  <div class="sidebar-footer site-footer">
    <template v-if="shellLoading">
      <div class="site-footer__skeleton" role="status">
        <div class="skeleton line" style="width: 60%"></div>
        <div class="skeleton line" style="width: 40%"></div>
      </div>
    </template>
    <template v-else>
      <div v-if="copyrightStyle !== 'none'" class="site-footer__copyright">
        <p v-if="copyrightStyle === 'detailed'">Copyright © {{ currentYear }} {{ siteInfo.name }} All Rights Reserved.</p>
        <p v-else>{{ currentYear }} © {{ siteInfo.name }}.</p>
        <p v-if="siteInfo.theme?.showCredit !== false">Theme <a class="footer-theme-link" href="https://github.com/worable233/SimpleTheme" target="_blank" rel="noopener noreferrer">SimpleTheme</a>.</p>
      </div>
    </template>
  </div>
</template>

<style scoped>
.site-footer {
  margin-top: auto;
  flex-shrink: 0;
  border-top: 1px solid var(--border);
  background-color: var(--card);
  padding: 15px 25px;
}

.site-footer__skeleton {
  display: flex;
  flex-direction: column;
  gap: 4px;
  padding: 12px 0;
}

.site-footer__copyright {
  text-align: center;
  font-size: 13px;
  color: var(--secondary);
}

/* Gradient wordmark link — pseudo-element underline + gradient text stay in CSS */
.footer-theme-link {
  font-family: 'Georgia', 'Noto Serif SC', '楷体', 'KaiTi', serif;
  font-weight: 600;
  font-size: 14px;
  letter-spacing: 0.5px;
  background: linear-gradient(135deg, #888 0%, #444 50%, #222 100%);
  background-clip: text;
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  text-decoration: none;
  transition:
    letter-spacing 0.3s ease,
    filter 0.3s ease;
  position: relative;
  display: inline-block;
}

.footer-theme-link::after {
  content: '';
  position: absolute;
  left: 50%;
  bottom: -1px;
  width: 100%;
  height: 1.5px;
  background: linear-gradient(90deg, #888, #444, #222);
  transform: translateX(-50%) scaleX(0);
  transition: transform 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
}

.footer-theme-link:hover {
  filter: brightness(1.3);
  letter-spacing: 1.2px;
}

.footer-theme-link:hover::after {
  transform: translateX(-50%) scaleX(1);
}

[data-theme='dark'] .footer-theme-link {
  background: linear-gradient(135deg, #ccc 0%, #999 50%, #666 100%);
  background-clip: text;
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

[data-theme='dark'] .footer-theme-link::after {
  background: linear-gradient(90deg, #ccc, #999, #666);
}
</style>
