<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'

defineProps<{
  routes: Array<{
    path: string
    name?: string
    label: string
  }>
}>()

const isOpen = ref(false)
const router = useRouter()

function toggle() {
  isOpen.value = !isOpen.value
}

function navigate(path: string) {
  router.push(path)
  isOpen.value = false
}
</script>

<template>
  <div class="debug-panel">
    <button class="debug-panel__toggle" @click="toggle" aria-label="调试面板">
      <svg
        v-if="!isOpen"
        width="16"
        height="16"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="2"
      >
        <path d="M12 20h9"></path>
        <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path>
      </svg>
      <svg
        v-else
        width="16"
        height="16"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="2"
      >
        <line x1="18" y1="6" x2="6" y2="18"></line>
        <line x1="6" y1="6" x2="18" y2="18"></line>
      </svg>
    </button>

    <div v-if="isOpen" class="debug-panel__content">
      <div class="debug-panel__header">
        <h3>调试面板</h3>
        <button class="debug-panel__close" @click="toggle">
          <svg
            width="16"
            height="16"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
          >
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
          </svg>
        </button>
      </div>

      <div class="debug-panel__section">
        <h4>网站地图</h4>
        <div class="debug-panel__routes">
          <button
            v-for="route in routes"
            :key="route.path"
            class="debug-panel__route"
            @click="navigate(route.path)"
          >
            <span class="debug-panel__route-label">{{ route.label }}</span>
            <code class="debug-panel__route-path">{{ route.path }}</code>
          </button>
        </div>
      </div>

      <div class="debug-panel__section">
        <h4>快捷操作</h4>
        <div class="debug-panel__actions">
          <a
            href="/wp-admin/themes.php?page=simple-theme-options"
            class="debug-panel__action"
            target="_blank"
          >
            <svg
              width="16"
              height="16"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
            >
              <circle cx="12" cy="12" r="3"></circle>
              <path
                d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"
              ></path>
            </svg>
            <span>主题设置</span>
          </a>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.debug-panel {
  position: fixed;
  bottom: 1rem;
  right: 1rem;
  z-index: 9999;
}

.debug-panel__toggle {
  width: 40px;
  height: 40px;
  border-radius: var(--radius-full);
  background: var(--card);
  border: 1px solid var(--border);
  box-shadow: var(--shadow-medium);
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s ease;
}

.debug-panel__toggle:hover {
  transform: scale(1.05);
  box-shadow: var(--shadow-large);
}

.debug-panel__content {
  position: absolute;
  bottom: calc(100% + 0.5rem);
  right: 0;
  width: 320px;
  max-height: 70vh;
  background: var(--card);
  border: 1px solid var(--border);
  border-radius: var(--radius-large);
  box-shadow: var(--shadow-large);
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

.debug-panel__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.75rem 1rem;
  border-bottom: 1px solid var(--border);
}

.debug-panel__header h3 {
  margin: 0;
  font-size: 1.25rem;
  font-weight: 600;
}

.debug-panel__close {
  padding: 0.25rem;
}

.debug-panel__section {
  padding: 1rem;
  overflow-y: auto;
}

.debug-panel__section h4 {
  margin: 0 0 0.75rem;
  font-size: 1.5rem;
  font-weight: 500;
  color: var(--foreground);
  opacity: 0.8;
}

.debug-panel__routes {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.debug-panel__route {
  display: flex;
  align-items: center;
  justify-content: space-between;
  width: 100%;
  text-align: left;
  padding: 0.5rem 0.75rem;
  border-radius: var(--radius-medium);
  transition: background 0.15s ease;
}

.debug-panel__route:hover {
  background: var(--accent);
}

.debug-panel__route-label {
  font-weight: 500;
}

.debug-panel__route-path {
  font-size: 0.75rem;
  opacity: 0.6;
  font-family: monospace;
}

.debug-panel__actions {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.debug-panel__action {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  width: 100%;
  text-align: left;
  padding: 0.5rem 0.75rem;
  border-radius: var(--radius-medium);
  transition: background 0.15s ease;
}

.debug-panel__action:hover {
  background: var(--accent);
}
</style>
