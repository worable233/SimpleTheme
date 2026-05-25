<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'

const props = defineProps<{
  siteName: string
  userName: string
  userAvatar: string
}>()

const userOpen = ref(false)
const currentTheme = ref('light')

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
      <div class="admin-topbar__user-wrap" @mouseenter="userOpen = true" @mouseleave="closeUser">
        <button class="admin-topbar__user-btn" @click="toggleUser" :title="userName">
          <img v-if="userAvatar" :src="userAvatar" alt="" class="admin-topbar__avatar" />
          <span v-else class="admin-topbar__avatar admin-topbar__avatar--placeholder">{{ avatarLetter }}</span>
        </button>

        <Transition name="topbar-fade">
          <div v-if="userOpen" class="admin-topbar__dropdown">
            <div class="admin-topbar__dropdown-header">
              <img v-if="userAvatar" :src="userAvatar" alt="" class="admin-topbar__dropdown-avatar" />
              <span v-else class="admin-topbar__dropdown-avatar admin-topbar__dropdown-avatar--placeholder">{{ avatarLetter }}</span>
              <div class="admin-topbar__dropdown-info">
                <span class="admin-topbar__dropdown-name">{{ userName }}</span>
              </div>
            </div>
            <div class="admin-topbar__dropdown-body">
              <a :href="'profile.php'" class="admin-topbar__dropdown-item">个人资料</a>
              <a :href="'profile.php'">编辑个人资料</a>
            </div>
            <div class="admin-topbar__dropdown-body">
              <button class="admin-topbar__theme-btn" @click="toggleTheme">
                <svg v-if="currentTheme === 'dark'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><circle cx="12" cy="12" r="5"/><path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/></svg>
                <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
                <span>{{ currentTheme === 'dark' ? '浅色模式' : '深色模式' }}</span>
              </button>
            </div>
            <div class="admin-topbar__dropdown-footer">
              <a :href="'logout'">退出登录</a>
            </div>
          </div>
        </Transition>
      </div>
    </div>
  </header>
</template>

<style scoped>
.admin-topbar {
  position: fixed;
  top: 0;
  left: 100px;
  right: 0;
  height: 48px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  background: var(--card, #ffffff);
  border-bottom: 1px solid var(--border, #e2e2e2);
  padding: 0 24px;
  z-index: 9998;
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
