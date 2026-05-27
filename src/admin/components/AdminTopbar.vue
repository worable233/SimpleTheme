<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import UserDropdown from './UserDropdown.vue'

const props = defineProps<{
  siteName: string
  userName: string
  userAvatar: string
}>()

const userOpen = ref(false)
const currentTheme = ref('light')
const userBtnRef = ref<HTMLElement | null>(null)

function toggleUser() {
  userOpen.value = !userOpen.value
}

function closeUser() {
  userOpen.value = false
}

const avatarLetter = computed(() => {
  return props.userName ? props.userName.charAt(0).toUpperCase() : 'U'
})

function applyTheme(theme: string) {
  document.documentElement.setAttribute('data-theme', theme)
  document.documentElement.style.colorScheme = theme
}

function toggleTheme() {
  currentTheme.value = currentTheme.value === 'dark' ? 'light' : 'dark'
  applyTheme(currentTheme.value)
  localStorage.setItem('theme', currentTheme.value)
}

onMounted(() => {
  const saved = localStorage.getItem('theme')
  if (saved === 'light' || saved === 'dark') {
    currentTheme.value = saved
  } else {
    currentTheme.value = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'
  }
  applyTheme(currentTheme.value)
})
</script>

<template>
  <header class="admin-topbar">
    <div class="admin-topbar__left">
      <span class="admin-topbar__title">{{ siteName || 'WordPress' }}</span>
    </div>

    <div class="admin-topbar__right">
      <!-- User dropdown -->
      <div class="admin-topbar__user-wrap">
        <button
          ref="userBtnRef"
          class="admin-topbar__user-btn"
          @click="toggleUser"
          :title="userName"
        >
          <img v-if="userAvatar" :src="userAvatar" alt="" class="admin-topbar__avatar" />
          <span v-else class="admin-topbar__avatar admin-topbar__avatar--placeholder">{{ avatarLetter }}</span>
        </button>

        <UserDropdown
          :visible="userOpen"
          :user-name="userName"
          :user-avatar="userAvatar"
          :current-theme="currentTheme"
          :anchor-el="userBtnRef"
          @close="closeUser"
          @toggle-theme="toggleTheme"
        />
      </div>
    </div>
  </header>
</template>

<style scoped>
.admin-topbar {
  height: 48px;
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: space-between;
  background: var(--card, #ffffff);
  border-bottom: 1px solid var(--border, #e2e2e2);
  padding: 0 24px;
  z-index: auto;
}

.admin-topbar__left {
  display: flex;
  align-items: center;
  gap: 12px;
}

.admin-topbar__right {
  display: flex;
  align-items: center;
  gap: 12px;
}

.admin-topbar__title {
  font-size: 15px;
  font-weight: 600;
  color: var(--foreground, #333);
}

.admin-topbar__user-wrap {
  position: relative;
}

.admin-topbar__user-btn {
  display: flex;
  align-items: center;
  gap: 8px;
  background: none;
  border: none;
  cursor: pointer;
  padding: 4px;
  border-radius: var(--radius-full, 9999px);
  transition: background-color var(--transition-fast, 0.15s ease);
}

.admin-topbar__user-btn:hover {
  background-color: var(--muted, #f5f5f5);
}

.admin-topbar__avatar {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  object-fit: cover;
}

.admin-topbar__avatar--placeholder {
  display: flex;
  justify-content: center;
  align-items: center;
  background: var(--primary, #FF2442);
  color: var(--primary-foreground, #fff);
  font-size: 14px;
  font-weight: 700;
}

/* Dropdown */
.admin-topbar__dropdown {
  position: absolute;
  top: calc(100% + 8px);
  right: 0;
  min-width: 200px;
  background: var(--card, #ffffff);
  border: 1px solid var(--border, #e2e2e2);
  border-radius: 8px;
  box-shadow: var(--shadow-large, 0 6px 12px rgb(0 0 0 / 0.1));
  z-index: 10001;
  overflow: hidden;
}

.admin-topbar__dropdown-header {
  border-bottom: 1px solid var(--border, #e2e2e2);
}

.admin-topbar__dropdown-avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  object-fit: cover;
}

.admin-topbar__dropdown-avatar--placeholder {
  background: var(--primary, #FF2442);
  color: var(--primary-foreground, #fff);
  font-size: 16px;
  font-weight: 700;
}

.admin-topbar__dropdown-info {
  flex: 1;
}

.admin-topbar__dropdown-name {
  font-size: 14px;
  font-weight: 600;
  color: var(--foreground, #333);
}

.admin-topbar__dropdown-body {
  padding: 8px 0;
  border-bottom: 1px solid var(--border, #e2e2e2);
}

.admin-topbar__dropdown-body a,
.admin-topbar__dropdown-footer a {
  display: block;
  padding: 8px 16px;
  font-size: 13px;
  color: var(--foreground, #333);
  text-decoration: none;
  transition: background-color var(--transition-fast, 0.15s ease);
}

.admin-topbar__dropdown-body a:hover,
.admin-topbar__dropdown-footer a:hover {
  background-color: var(--muted, #f5f5f5);
  color: var(--primary, #FF2442);
}

.admin-topbar__theme-btn {
  padding: 8px 16px;
  font-size: 13px;
  color: var(--foreground, #333);
  background: none;
  border: none;
  cursor: pointer;
  text-decoration: none;
  transition: background-color var(--transition-fast, 0.15s ease);
}

.admin-topbar__theme-btn:hover {
  background-color: var(--muted, #f5f5f5);
  color: var(--primary, #FF2442);
}

.admin-topbar__dropdown-footer {
  padding: 8px 0;
}

.admin-topbar__dropdown-footer a {
  color: var(--muted-foreground, #999);
}

/* Transition */
.topbar-fade-enter-active,
.topbar-fade-leave-active {
  transition: opacity 0.15s ease;
}

.topbar-fade-enter-from,
.topbar-fade-leave-to {
  opacity: 0;
}
</style>
