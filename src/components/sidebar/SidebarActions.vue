<script setup lang="ts">
defineProps<{
  currentTheme: string
}>()

defineEmits<{
  'toggle-theme': []
}>()
</script>

<template>
  <div class="left-sidebar__actions">
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
</style>
