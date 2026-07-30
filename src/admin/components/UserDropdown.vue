<script setup lang="ts">
import { ref, watch, onMounted, onBeforeUnmount, nextTick } from 'vue'

const props = defineProps<{
  visible: boolean
  userName: string
  userAvatar: string
  currentTheme: string
  anchorEl: HTMLElement | null
}>()

const emit = defineEmits<{
  (e: 'close'): void
  (e: 'toggleTheme'): void
}>()

const dropdownEl = ref<HTMLElement | null>(null)
const pos = ref({ top: '0px', right: '0px' })

// Real URLs scraped from the hidden native admin bar (carry WP nonces).
const profileUrl = ref('profile.php')
const logoutUrl = ref('')

function resolveNativeUrls() {
  const profileLink = document.querySelector<HTMLAnchorElement>(
    '#wp-admin-bar-edit-profile a, #wp-admin-bar-user-info a',
  )
  if (profileLink?.href) profileUrl.value = profileLink.href

  const logoutLink = document.querySelector<HTMLAnchorElement>('#wp-admin-bar-logout a')
  if (logoutLink?.href) logoutUrl.value = logoutLink.href
}

function updatePosition() {
  if (!props.anchorEl || !props.visible) return
  const rect = props.anchorEl.getBoundingClientRect()
  pos.value = {
    top: `${rect.bottom + 8}px`,
    right: `${window.innerWidth - rect.right}px`,
  }
}

watch(() => props.visible, (v) => {
  if (v) {
    nextTick(() => updatePosition())
  }
})

function onClickOutside(e: MouseEvent) {
  if (!props.visible) return
  const target = e.target as HTMLElement
  if (
    dropdownEl.value && !dropdownEl.value.contains(target) &&
    props.anchorEl && !props.anchorEl.contains(target)
  ) {
    emit('close')
  }
}

function onScroll() {
  if (props.visible) updatePosition()
}

onMounted(() => {
  resolveNativeUrls()
  document.addEventListener('click', onClickOutside)
  window.addEventListener('scroll', onScroll, true)
  window.addEventListener('resize', updatePosition)
})

onBeforeUnmount(() => {
  document.removeEventListener('click', onClickOutside)
  window.removeEventListener('scroll', onScroll, true)
  window.removeEventListener('resize', updatePosition)
})
</script>

<template>
  <Teleport to="body">
    <Transition name="topbar-fade">
      <div
        v-if="visible"
        ref="dropdownEl"
        class="sta-shell-pop fixed min-w-[200px] overflow-hidden rounded-lg border border-border bg-card shadow-(--shadow-large)"
        :style="{ top: pos.top, right: pos.right, zIndex: 10001 }"
      >
        <div class="flex items-center gap-3 border-b border-border px-4 py-3.5">
          <img
            v-if="userAvatar"
            :src="userAvatar"
            alt=""
            class="size-10 shrink-0 rounded-full object-cover"
          />
          <span
            v-else
            class="flex size-10 shrink-0 items-center justify-center rounded-full bg-primary text-base font-bold text-primary-foreground"
          >{{ userName ? userName.charAt(0).toUpperCase() : 'U' }}</span>
          <div class="flex-1">
            <span class="text-sm font-semibold text-foreground">{{ userName }}</span>
          </div>
        </div>

        <div class="border-b border-border py-2">
          <a
            :href="profileUrl"
            class="block px-4 py-2 text-[13px] text-foreground no-underline transition-colors duration-150 hover:bg-menu-hover"
          >个人资料</a>
          <button
            class="flex w-full cursor-pointer items-center gap-2 border-0 bg-transparent px-4 py-2 text-left text-[13px] text-foreground transition-colors duration-150 hover:bg-menu-hover"
            @click="emit('toggleTheme')"
          >
            <svg
              v-if="currentTheme === 'dark'"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
              width="16"
              height="16"
            ><circle cx="12" cy="12" r="5"/><path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/></svg>
            <svg
              v-else
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
              width="16"
              height="16"
            ><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
            <span>{{ currentTheme === 'dark' ? '浅色模式' : '深色模式' }}</span>
          </button>
        </div>

        <div class="py-2">
          <a
            :href="logoutUrl || 'wp-login.php?action=logout'"
            class="block px-4 py-2 text-[13px] text-secondary no-underline transition-colors duration-150 hover:bg-menu-hover hover:text-danger"
          >退出登录</a>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.topbar-fade-enter-active,
.topbar-fade-leave-active {
  transition: opacity 0.15s ease;
}

.topbar-fade-enter-from,
.topbar-fade-leave-to {
  opacity: 0;
}
</style>
