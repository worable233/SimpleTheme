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
    <div class="admin-topbar__brand">
      <span class="admin-topbar__site-name">{{ siteName || 'WordPress' }}</span>
    </div>

    <div class="admin-topbar__right">
      <!-- User dropdown -->
      <div class="admin-topbar__user">
        <button
          ref="userBtnRef"
          :title="userName"
          class="admin-topbar__avatar-btn"
          @click="toggleUser"
        >
          <img v-if="userAvatar" :src="userAvatar" alt="" class="admin-topbar__avatar-img" />
          <span v-else class="admin-topbar__avatar-letter">{{ avatarLetter }}</span>
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
  display: flex;
  align-items: center;
  justify-content: space-between;
  width: 100%;
  height: 48px;
  padding: 0 24px;
  border-bottom: 1px solid var(--border);
  background-color: var(--card);
}

.admin-topbar__brand,
.admin-topbar__right {
  display: flex;
  align-items: center;
  gap: 12px;
}

.admin-topbar__site-name {
  font-size: 15px;
  font-weight: 600;
  color: var(--foreground);
}

.admin-topbar__user {
  position: relative;
}

.admin-topbar__avatar-btn {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 4px;
  border: none;
  border-radius: var(--radius-full);
  background: transparent;
  cursor: pointer;
  transition: background-color 0.15s;
}

.admin-topbar__avatar-btn:hover {
  background-color: var(--muted);
}

.admin-topbar__avatar-img {
  width: 32px;
  height: 32px;
  border-radius: var(--radius-full);
  object-fit: cover;
}

.admin-topbar__avatar-letter {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  border-radius: var(--radius-full);
  background-color: var(--primary);
  color: var(--primary-foreground);
  font-size: 13px;
  font-weight: 700;
}
</style>
