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
        class="sta-shell-pop user-dropdown"
        :style="{ top: pos.top, right: pos.right, zIndex: 10001 }"
      >
        <div class="user-dropdown__head">
          <img
            v-if="userAvatar"
            :src="userAvatar"
            alt=""
            class="user-dropdown__avatar-img"
          />
          <span v-else class="user-dropdown__avatar-letter">{{
            userName ? userName.charAt(0).toUpperCase() : 'U'
          }}</span>
          <div class="user-dropdown__name-wrap">
            <span class="user-dropdown__name">{{ userName }}</span>
          </div>
        </div>

        <div class="user-dropdown__group">
          <a :href="profileUrl" class="user-dropdown__row">个人资料</a>
          <!--
            保留原生 <button>：与同级 <a> 菜单行共用 .user-dropdown__row 布局，
            且需要图标 + 文字组合，迁 StButton 会引入不必要的按钮盒模型。
          -->
          <button class="user-dropdown__row user-dropdown__row--btn" @click="emit('toggleTheme')">
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

        <div class="user-dropdown__group user-dropdown__group--last">
          <a
            :href="logoutUrl || 'wp-login.php?action=logout'"
            class="user-dropdown__row user-dropdown__row--logout"
          >退出登录</a>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.user-dropdown {
  position: fixed;
  min-width: 200px;
  overflow: hidden;
  border: 1px solid var(--border);
  border-radius: var(--radius-large);
  background-color: var(--card);
  box-shadow: var(--shadow-large);
}

.user-dropdown__head {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 14px 16px;
  border-bottom: 1px solid var(--border);
}

.user-dropdown__avatar-img {
  width: 40px;
  height: 40px;
  flex-shrink: 0;
  border-radius: var(--radius-full);
  object-fit: cover;
}

.user-dropdown__avatar-letter {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 40px;
  height: 40px;
  flex-shrink: 0;
  border-radius: var(--radius-full);
  background-color: var(--primary);
  color: var(--primary-foreground);
  font-size: 16px;
  font-weight: 700;
}

.user-dropdown__name-wrap {
  flex: 1;
}

.user-dropdown__name {
  font-size: 13px;
  font-weight: 600;
  color: var(--foreground);
}

.user-dropdown__group {
  padding: 8px 0;
  border-bottom: 1px solid var(--border);
}

.user-dropdown__group--last {
  border-bottom: none;
}

.user-dropdown__row {
  display: block;
  padding: 8px 16px;
  font-size: 13px;
  color: var(--foreground);
  text-decoration: none;
  transition: background-color 0.15s;
}

.user-dropdown__row:hover {
  background-color: var(--menu-hover);
}

.user-dropdown__row--btn {
  display: flex;
  align-items: center;
  gap: 8px;
  width: 100%;
  border: none;
  background: transparent;
  text-align: left;
  cursor: pointer;
}

.user-dropdown__row--logout {
  color: var(--secondary);
}

.user-dropdown__row--logout:hover {
  color: var(--danger);
}

.topbar-fade-enter-active,
.topbar-fade-leave-active {
  transition: opacity 0.15s ease;
}

.topbar-fade-enter-from,
.topbar-fade-leave-to {
  opacity: 0;
}
</style>
