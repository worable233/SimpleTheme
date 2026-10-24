<script setup lang="ts">
import { computed } from 'vue'
import { StAvatar, StButton, StMorphIcon } from '@/ui'
import AppIcon from '@/components/AppIcon.vue'
import { useAuth } from '@/composables/useAuth'
import { useAuthModal } from '@/composables/useAuthModal'
import {
  nextThemePreference,
  themePreferenceLabel,
  type ResolvedTheme,
  type ThemePreference,
} from '@/lib/theme-preference'

const props = defineProps<{
  currentTheme: ResolvedTheme
  themePreference: ThemePreference
}>()

defineEmits<{
  'toggle-theme': []
}>()

const { auth } = useAuth()
const { open: openAuthModal } = useAuthModal()

/** 自动模式用显示器图标，明确表示「跟着系统走」 */
const themeIcon = computed(() => {
  if (props.themePreference === 'auto') return 'device-desktop'
  return props.currentTheme === 'dark' ? 'moon' : 'sun'
})

const NEXT_LABEL: Record<ThemePreference, string> = {
  auto: '切换到跟随系统',
  light: '切换到浅色模式',
  dark: '切换到深色模式',
}

const nextHint = computed(() => NEXT_LABEL[nextThemePreference(props.themePreference)])

const themeTitle = computed(
  () =>
    `主题：${themePreferenceLabel(props.themePreference, props.currentTheme)}，点击${nextHint.value}`,
)
</script>

<template>
  <div class="left-sidebar__actions sidebar-actions">
    <!-- Login / User -->
    <template v-if="auth.loading">
      <div class="sidebar-actions__skeleton animate-skeleton-pulse"></div>
    </template>
    <template v-else-if="auth.loggedIn && auth.user">
      <a
        v-if="auth.adminUrl"
        :href="auth.adminUrl"
        class="sidebar-actions__cell"
        :title="auth.user.displayName"
      >
        <StAvatar
          :src="auth.user.avatar"
          :alt="auth.user.displayName"
          :fallback-text="auth.user.displayName"
          :size="32"
        />
      </a>
    </template>
    <template v-else>
      <StButton quaternary circle size="large" aria-label="登录" title="登录" @click="openAuthModal">
        <template #icon><AppIcon name="log-in" :size="24" /></template>
      </StButton>
    </template>

    <!-- Theme toggle -->
    <StButton
      quaternary
      circle
      size="large"
      :aria-label="themeTitle"
      :title="themeTitle"
      @click="$emit('toggle-theme')"
    >
      <template #icon>
        <StMorphIcon :name="themeIcon" :size="24" />
      </template>
    </StButton>
  </div>
</template>

<style scoped>
.sidebar-actions {
  display: flex;
  flex-direction: column;
  align-items: center;
  flex-shrink: 0;
  width: 100%;
  gap: 6px;
  padding: 20px 0;
  border-top: 1px solid var(--border);
}

.sidebar-actions__skeleton {
  width: 50px;
  height: 50px;
  border-radius: var(--radius-large);
  background-color: var(--muted);
}

.sidebar-actions__cell {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 50px;
  height: 50px;
  border-radius: var(--radius-large);
  color: var(--foreground);
  cursor: pointer;
  transition: background-color 0.15s;
}

.sidebar-actions__cell:hover {
  background-color: var(--menu-hover);
}

/* 1200px 以下侧栏收窄，操作区改为紧凑居中排布 */
@media (max-width: 75rem) {
  .sidebar-actions {
    justify-content: center;
    gap: 4px;
    padding: 12px 0;
  }
}
</style>
