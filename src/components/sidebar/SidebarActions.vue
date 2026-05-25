<script setup lang="ts">
import { useAuth } from '@/composables/useAuth'
import { useAuthModal } from '@/composables/useAuthModal'

const props = defineProps<{
  currentTheme: string
}>()

defineEmits<{
  'toggle-theme': []
}>()

const { auth } = useAuth()
const { open: openAuthModal } = useAuthModal()
</script>

<template>
  <div class="left-sidebar__actions">
    <!-- Login / User -->
    <template v-if="auth.loading">
      <div class="sidebar-action-btn sidebar-action-btn--skeleton"></div>
    </template>
    <template v-else-if="auth.loggedIn && auth.user">
      <a
        v-if="auth.adminUrl"
        :href="auth.adminUrl"
        class="sidebar-action-btn sidebar-action-btn--user"
        :title="auth.user.displayName"
      >
        <img
          v-if="auth.user.avatar"
          :src="auth.user.avatar"
          :alt="auth.user.displayName"
          class="sidebar-avatar"
        />
        <span v-else class="sidebar-avatar sidebar-avatar--fallback">{{ auth.user.displayName.charAt(0) }}</span>
      </a>
    </template>
    <template v-else>
      <button class="sidebar-action-btn" @click="openAuthModal" title="登录">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="22" height="22">
          <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/>
          <polyline points="10 17 15 12 10 7"/>
          <line x1="15" y1="12" x2="3" y2="12"/>
        </svg>
      </button>
    </template>

    <!-- Theme toggle -->
    <button class="sidebar-action-btn" @click="$emit('toggle-theme')" :aria-label="currentTheme === 'dark' ? '切换到浅色模式' : '切换到深色模式'">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
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
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
        :style="{ display: currentTheme === 'dark' ? 'block' : 'none' }">
        <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
      </svg>
    </button>
  </div>
</template>

<style scoped>
.left-sidebar__actions {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 6px;
  padding: 20px 0;
  flex-shrink: 0;
  width: 100%;
  border-top: 1px solid var(--border);
}

.left-sidebar__actions .sidebar-action-btn {
  display: flex;
  justify-content: center;
  align-items: center;
  width: 50px;
  height: 50px;
  border-radius: 8px;
  background: none;
  border: none;
  cursor: pointer;
  color: var(--foreground);
  transition: background-color var(--transition-fast);
}

.left-sidebar__actions .sidebar-action-btn:hover {
  background-color: var(--menu-hover);
}

.left-sidebar__actions .sidebar-action-btn svg {
  width: 24px;
  height: 24px;
}

/* Skeleton loading */
.sidebar-action-btn--skeleton {
  width: 50px;
  height: 50px;
  border-radius: 8px;
  background: var(--muted, #f5f5f5);
  animation: skeleton-pulse 1.5s infinite;
}

@keyframes skeleton-pulse {
  0%, 100% { opacity: 0.4; }
  50% { opacity: 0.8; }
}

/* Avatar */
.sidebar-avatar {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  object-fit: cover;
}

.sidebar-avatar--fallback {
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--primary, #333);
  color: var(--primary-foreground, #fff);
  font-size: 14px;
  font-weight: 600;
}
</style>
