<script setup lang="ts">
import { StAvatar, StButton } from '@/ui'
import { useAuth } from '@/composables/useAuth'
import { useAuthModal } from '@/composables/useAuthModal'

defineProps<{
  currentTheme: string
}>()

defineEmits<{
  'toggle-theme': []
}>()

const { auth } = useAuth()
const { open: openAuthModal } = useAuthModal()
</script>

<template>
  <div
    class="left-sidebar__actions flex w-full shrink-0 flex-col items-center gap-1.5 border-t border-border py-5 max-xl:justify-center max-xl:gap-1 max-xl:py-3"
  >
    <!-- Login / User -->
    <template v-if="auth.loading">
      <div class="h-[50px] w-[50px] animate-skeleton-pulse rounded-lg bg-muted"></div>
    </template>
    <template v-else-if="auth.loggedIn && auth.user">
      <a
        v-if="auth.adminUrl"
        :href="auth.adminUrl"
        class="flex h-[50px] w-[50px] cursor-pointer items-center justify-center rounded-lg text-foreground transition-colors duration-150 hover:bg-menu-hover"
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
        <template #icon>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="24" height="24">
            <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/>
            <polyline points="10 17 15 12 10 7"/>
            <line x1="15" y1="12" x2="3" y2="12"/>
          </svg>
        </template>
      </StButton>
    </template>

    <!-- Theme toggle -->
    <StButton
      quaternary
      circle
      size="large"
      :aria-label="currentTheme === 'dark' ? '切换到浅色模式' : '切换到深色模式'"
      @click="$emit('toggle-theme')"
    >
      <template #icon>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="24" height="24"
          :style="{ display: currentTheme === 'dark' ? 'none' : 'block' }">
          <circle cx="12" cy="12" r="5"></circle>
          <line x1="12" y1="1" x2="12" y2="3"></line>
          <line x1="12" y1="21" x2="12" y2="23"></line>
          <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line>
          <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line>
          <line x1="1" y1="12" x2="3" y2="12"></line>
          <line x1="21" y1="12" x2="23" y2="12"></line>
          <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line>
          <line x1="18.36" y1="5.36" x2="19.78" y2="4.22"></line>
        </svg>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="24" height="24"
          :style="{ display: currentTheme === 'dark' ? 'block' : 'none' }">
          <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
        </svg>
      </template>
    </StButton>
  </div>
</template>
