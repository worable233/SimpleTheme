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
        class="admin-topbar__dropdown"
        :style="{
          position: 'fixed',
          top: pos.top,
          right: pos.right,
          zIndex: 10001,
        }"
      >
        <div class="admin-topbar__dropdown-header">
          <img v-if="userAvatar" :src="userAvatar" alt="" class="admin-topbar__dropdown-avatar" />
          <span
            v-else
            class="admin-topbar__dropdown-avatar admin-topbar__dropdown-avatar--placeholder"
          >{{ userName ? userName.charAt(0).toUpperCase() : 'U' }}</span>
          <div class="admin-topbar__dropdown-info">
            <span class="admin-topbar__dropdown-name">{{ userName }}</span>
          </div>
        </div>
        <div class="admin-topbar__dropdown-body">
          <a :href="'profile.php'" class="admin-topbar__dropdown-item">个人资料</a>
          <a :href="'profile.php'">编辑个人资料</a>
        </div>
        <div class="admin-topbar__dropdown-body">
          <button class="admin-topbar__theme-btn" @click="emit('toggleTheme')">
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
        <div class="admin-topbar__dropdown-footer">
          <a :href="'logout'">退出登录</a>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.admin-topbar__dropdown {
  min-width: 200px;
  background: var(--card, #ffffff);
  border: 1px solid var(--border, #e2e2e2);
  border-radius: 8px;
  box-shadow: var(--shadow-large, 0 6px 12px rgb(0 0 0 / 0.1));
  overflow: hidden;
}

.admin-topbar__dropdown-header {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 14px 16px;
  border-bottom: 1px solid var(--border, #e2e2e2);
}

.admin-topbar__dropdown-avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  object-fit: cover;
  flex-shrink: 0;
}

.admin-topbar__dropdown-avatar--placeholder {
  display: flex;
  justify-content: center;
  align-items: center;
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
  display: flex;
  align-items: center;
  gap: 8px;
  width: 100%;
  padding: 8px 16px;
  font-size: 13px;
  color: var(--foreground, #333);
  background: none;
  border: none;
  cursor: pointer;
  text-decoration: none;
  text-align: left;
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
