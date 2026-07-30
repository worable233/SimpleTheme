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
  <header class="flex h-12 w-full items-center justify-between border-b border-border bg-card px-6">
    <div class="flex items-center gap-3">
      <span class="text-[15px] font-semibold text-foreground">{{ siteName || 'WordPress' }}</span>
    </div>

    <div class="flex items-center gap-3">
      <!-- User dropdown -->
      <div class="relative">
        <button
          ref="userBtnRef"
          :title="userName"
          class="flex cursor-pointer items-center gap-2 rounded-full border-0 bg-transparent p-1 transition-colors duration-150 hover:bg-muted"
          @click="toggleUser"
        >
          <img v-if="userAvatar" :src="userAvatar" alt="" class="size-8 rounded-full object-cover" />
          <span
            v-else
            class="flex size-8 items-center justify-center rounded-full bg-primary text-sm font-bold text-primary-foreground"
          >{{ avatarLetter }}</span>
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
