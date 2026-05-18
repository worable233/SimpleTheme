<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import { fetchSettings, saveSettings } from './api'
import type { AdminSettings } from './api'
import AppearanceTab from './components/AppearanceTab.vue'
import HeroTab from './components/HeroTab.vue'
import CardMetaTab from './components/CardMetaTab.vue'
import AdvancedTab from './components/AdvancedTab.vue'

const settings = ref<AdminSettings>({})
const defaults = ref<AdminSettings>({})
const activeTab = ref('appearance')
const saving = ref(false)
const saved = ref(false)
const loading = ref(true)
const error = ref('')
const isDirty = ref(false)

const tabs = [
  { key: 'appearance', label: '外观样式', icon: 'M12.75 3a.75.75 0 0 0-.75.75v6.75h-7.5a.75.75 0 0 0 0 1.5h7.5v6.75a.75.75 0 0 0 1.5 0V12h7.5a.75.75 0 0 0 0-1.5h-7.5V3.75a.75.75 0 0 0-.75-.75z' },
  { key: 'hero', label: '封面区域', icon: 'M2.25 15.75l5.159-5.159a.75.75 0 0 1 1.06 0l3.781 3.78a.75.75 0 0 0 1.06 0l3.78-3.78a.75.75 0 0 1 1.06 0l5.16 5.16M2.25 15.75V21a.75.75 0 0 0 .75.75h18a.75.75 0 0 0 .75-.75v-5.25M2.25 15.75V5.25a.75.75 0 0 1 .75-.75h18a.75.75 0 0 1 .75.75v10.5' },
  { key: 'card-meta', label: '卡片信息', icon: 'M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z' },
  { key: 'advanced', label: '高级设置', icon: 'M10.343 3.94c.09-.542.56-.94 1.11-.94h1.093c.55 0 1.02.398 1.11.94l.149.894c.07.424.384.764.78.93.034.015.068.03.1.046.39.19.842.13 1.12-.15l.626-.626a1.125 1.125 0 0 1 1.591 0l.707.707c.44.44.44 1.152 0 1.591l-.626.626c-.28.28-.34.732-.15 1.12.016.032.031.066.046.1.166.396.506.71.93.78l.894.149c.542.09.94.56.94 1.11v1.094c0 .55-.398 1.02-.94 1.11l-.894.149c-.424.07-.764.384-.93.78-.015.034-.03.068-.046.1-.19.388-.13.84.15 1.12l.626.626c.44.44.44 1.152 0 1.591l-.707.707a1.125 1.125 0 0 1-1.591 0l-.626-.626a1.125 1.125 0 0 0-1.12-.15c-.033.015-.066.03-.1.046-.396.166-.71.506-.78.93l-.149.894c-.09.542-.56.94-1.11.94h-1.094c-.55 0-1.02-.398-1.11-.94l-.149-.894c-.07-.424-.384-.764-.78-.93a.9.9 0 0 0-.1-.046 1.125 1.125 0 0 0-1.12.15l-.626.626a1.125 1.125 0 0 1-1.591 0l-.707-.707a1.125 1.125 0 0 1 0-1.591l.626-.626c.28-.28.34-.732.15-1.12-.016-.032-.031-.066-.046-.1-.166-.396-.506-.71-.93-.78l-.894-.149c-.542-.09-.94-.56-.94-1.11v-1.094c0-.55.398-1.02.94-1.11l.894-.149c.424-.07.764-.384.93-.78.015-.034.03-.068.046-.1.19-.388.13-.84-.15-1.12l-.626-.626a1.125 1.125 0 0 1 0-1.591l.707-.707a1.125 1.125 0 0 1 1.591 0l.626.626c.28.28.732.34 1.12.15.032-.016.066-.031.1-.046.396-.166.71-.506.78-.93l.149-.894z' },
]

const currentSettings = ref<AdminSettings>({})

function handleBeforeUnload(e: BeforeUnloadEvent) {
  if (isDirty.value) {
    e.preventDefault()
  }
}

onMounted(async () => {
  window.addEventListener('beforeunload', handleBeforeUnload)
  try {
    const data = await fetchSettings()
    defaults.value = data.defaults || {}
    settings.value = data.settings || {}
    currentSettings.value = JSON.parse(JSON.stringify(settings.value))
  } catch (e) {
    error.value = '加载设置失败: ' + (e instanceof Error ? e.message : String(e))
  } finally {
    loading.value = false
  }
})

onUnmounted(() => {
  window.removeEventListener('beforeunload', handleBeforeUnload)
})

function updateSetting(key: string, value: unknown) {
  currentSettings.value[key] = value
  isDirty.value = true
  saved.value = false
}

async function handleSave() {
  saving.value = true
  saved.value = false
  try {
    const result = await saveSettings(currentSettings.value)
    settings.value = result
    currentSettings.value = JSON.parse(JSON.stringify(result))
    saved.value = true
    isDirty.value = false
    setTimeout(() => { saved.value = false }, 3000)
  } catch (e) {
    error.value = '保存失败: ' + (e instanceof Error ? e.message : String(e))
  } finally {
    saving.value = false
  }
}

const activeComponent = computed(() => {
  const map: Record<string, any> = {
    appearance: AppearanceTab,
    hero: HeroTab,
    'card-meta': CardMetaTab,
    advanced: AdvancedTab,
  }
  return map[activeTab.value]
})
</script>

<template>
  <div id="simple-theme-admin" class="sta-root" v-if="!loading && !error">
    <aside class="sta-sidebar">
      <div class="sta-sidebar__header">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M12 2L2 7l10 5 10-5-10-5z"/>
          <path d="M2 17l10 5 10-5"/>
          <path d="M2 12l10 5 10-5"/>
        </svg>
        <span>Simple Theme</span>
      </div>
      <nav class="sta-sidebar__nav">
        <button
          v-for="tab in tabs"
          :key="tab.key"
          class="sta-nav-item"
          :class="{ 'sta-nav-item--active': activeTab === tab.key }"
          @click="activeTab = tab.key"
        >
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <path :d="tab.icon"/>
          </svg>
          <span>{{ tab.label }}</span>
        </button>
      </nav>
    </aside>

    <main class="sta-content">
      <header class="sta-topbar">
        <h2 class="sta-topbar__title">{{ tabs.find(t => t.key === activeTab)?.label }}</h2>
        <div class="sta-topbar__actions">
          <span v-if="isDirty" class="sta-toast sta-toast--warn">未保存</span>
          <span v-if="saved" class="sta-toast sta-toast--success">已保存</span>
          <button
            class="sta-btn sta-btn--primary"
            :disabled="saving || !isDirty"
            @click="handleSave"
          >
            <svg v-if="saving" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="sta-spin">
              <path d="M21 12a9 9 0 1 1-6.219-8.56"/>
            </svg>
            {{ saving ? '保存中...' : '保存设置' }}
          </button>
        </div>
      </header>

      <div class="sta-body">
        <component
          :is="activeComponent"
          :settings="currentSettings"
          :defaults="defaults"
          @update="updateSetting"
        />
      </div>
    </main>
  </div>

  <!-- Loading state -->
  <div v-else-if="loading" id="simple-theme-admin" class="sta-root sta-root--loading">
    <div class="sta-loading">加载中...</div>
  </div>

  <!-- Error state -->
  <div v-else-if="error" id="simple-theme-admin" class="sta-root sta-root--error">
    <div class="sta-error">{{ error }}</div>
  </div>
</template>

<style>
/* ── Reset / Scoping ── */
#simple-theme-admin {
  --sta-bg: #f5f6f7;
  --sta-card: #ffffff;
  --sta-border: #e2e2e2;
  --sta-foreground: #1f2937;
  --sta-secondary: #6b7280;
  --sta-primary: #333333;
  --sta-primary-hover: #555555;
  --sta-radius: 8px;
  --sta-sidebar-width: 220px;
  --sta-topbar-height: 60px;
}

#simple-theme-admin,
#simple-theme-admin * {
  box-sizing: border-box;
}

#simple-theme-admin {
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'PingFang SC', 'Microsoft YaHei', sans-serif;
  color: var(--sta-foreground);
  line-height: 1.5;
}

/* ── Layout ── */
.sta-root {
  display: flex;
  min-height: calc(100vh - 32px);
  background: var(--sta-bg);
  margin: -10px -20px -20px;
}

.sta-root--loading,
.sta-root--error {
  justify-content: center;
  align-items: center;
}

.sta-loading {
  font-size: 15px;
  color: var(--sta-secondary);
}

.sta-error {
  font-size: 14px;
  color: #dc2626;
  background: #fef2f2;
  padding: 12px 20px;
  border-radius: var(--sta-radius);
  border: 1px solid #fecaca;
}

/* ── Sidebar ── */
.sta-sidebar {
  width: var(--sta-sidebar-width);
  flex-shrink: 0;
  background: var(--sta-card);
  border-right: 1px solid var(--sta-border);
  display: flex;
  flex-direction: column;
  position: sticky;
  top: 32px;
  height: calc(100vh - 32px);
}

.sta-sidebar__header {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 16px 20px;
  font-size: 16px;
  font-weight: 625;
  color: var(--sta-foreground);
  border-bottom: 1px solid var(--sta-border);
}

.sta-sidebar__nav {
  flex: 1;
  display: flex;
  flex-direction: column;
  padding: 8px;
  gap: 2px;
  overflow-y: auto;
}

.sta-nav-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 14px;
  border: none;
  background: none;
  border-radius: 6px;
  font-size: 14px;
  color: var(--sta-foreground);
  cursor: pointer;
  transition: background 0.15s;
  width: 100%;
  text-align: left;
  font-family: inherit;
}

.sta-nav-item:hover {
  background: rgba(0,0,0,0.05);
}

.sta-nav-item--active {
  background: var(--sta-primary);
  color: #fff;
}

.sta-nav-item--active svg {
  stroke: #fff;
}

/* ── Topbar ── */
.sta-topbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 32px;
  height: var(--sta-topbar-height);
  border-bottom: 1px solid var(--sta-border);
  background: var(--sta-card);
  position: sticky;
  top: 32px;
  z-index: 10;
}

.sta-topbar__title {
  margin: 0;
  font-size: 18px;
  font-weight: 625;
}

.sta-topbar__actions {
  display: flex;
  align-items: center;
  gap: 12px;
}

/* ── Button ── */
.sta-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 8px 20px;
  border: 1px solid var(--sta-border);
  border-radius: 6px;
  font-size: 14px;
  font-family: inherit;
  cursor: pointer;
  transition: all 0.15s;
  background: var(--sta-card);
  color: var(--sta-foreground);
}

.sta-btn:hover {
  background: #f3f4f6;
}

.sta-btn--primary {
  background: var(--sta-primary);
  color: #fff;
  border-color: var(--sta-primary);
}

.sta-btn--primary:hover {
  background: var(--sta-primary-hover);
}

.sta-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

/* ── Toast ── */
.sta-toast {
  font-size: 13px;
  padding: 6px 12px;
  border-radius: 4px;
}

.sta-toast--success {
  background: #ecfdf5;
  color: #059669;
  border: 1px solid #a7f3d0;
}

.sta-toast--warn {
  background: #fffbeb;
  color: #d97706;
  border: 1px solid #fde68a;
}

/* ── Content ── */
.sta-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  min-width: 0;
}

.sta-body {
  flex: 1;
  padding: 32px;
  overflow-y: auto;
}

/* ── Form elements ── */
.sta-field {
  margin-bottom: 24px;
  max-width: 640px;
}

.sta-field__label {
  display: block;
  font-size: 14px;
  font-weight: 600;
  margin-bottom: 6px;
  color: var(--sta-foreground);
}

.sta-field__desc {
  font-size: 13px;
  color: var(--sta-secondary);
  margin: 4px 0 0;
}

.sta-input {
  width: 100%;
  padding: 8px 12px;
  border: 1px solid var(--sta-border);
  border-radius: 6px;
  font-size: 14px;
  font-family: inherit;
  color: var(--sta-foreground);
  background: var(--sta-card);
  transition: border-color 0.15s;
}

.sta-input:focus {
  outline: none;
  border-color: var(--sta-primary);
  box-shadow: 0 0 0 2px rgba(51,51,51,0.1);
}

.sta-textarea {
  width: 100%;
  padding: 8px 12px;
  border: 1px solid var(--sta-border);
  border-radius: 6px;
  font-size: 14px;
  font-family: 'Menlo', 'Consolas', monospace;
  color: var(--sta-foreground);
  background: var(--sta-card);
  resize: vertical;
  transition: border-color 0.15s;
  min-height: 80px;
}

.sta-textarea:focus {
  outline: none;
  border-color: var(--sta-primary);
  box-shadow: 0 0 0 2px rgba(51,51,51,0.1);
}

.sta-color-row {
  display: flex;
  align-items: center;
  gap: 8px;
}

.sta-input--color-picker {
  width: 40px;
  height: 36px;
  padding: 2px;
  border-radius: 6px;
  cursor: pointer;
  flex-shrink: 0;
}

.sta-input--color-text {
  width: 110px;
  font-family: 'Menlo', 'Consolas', monospace;
}

.sta-input--number {
  width: 100px;
}

.sta-input--flex {
  flex: 1;
  min-width: 0;
}

.sta-input-row {
  display: flex;
  align-items: center;
  gap: 8px;
}

.sta-select {
  padding: 8px 12px;
  border: 1px solid var(--sta-border);
  border-radius: 6px;
  font-size: 14px;
  font-family: inherit;
  color: var(--sta-foreground);
  background: var(--sta-card);
  cursor: pointer;
}

.sta-checkbox {
  display: flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
  font-size: 14px;
}

.sta-checkbox input[type="checkbox"] {
  width: 16px;
  height: 16px;
  accent-color: var(--sta-primary);
}

.sta-radio-group {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.sta-radio {
  display: flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
  font-size: 14px;
}

.sta-radio input[type="radio"] {
  accent-color: var(--sta-primary);
}

/* ── Section ── */
.sta-section {
  margin-bottom: 40px;
}

.sta-section__title {
  font-size: 16px;
  font-weight: 625;
  margin: 0 0 4px;
}

.sta-section__desc {
  font-size: 13px;
  color: var(--sta-secondary);
  margin: 0 0 20px;
}

/* ── Grid ── */
.sta-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 16px;
}

.sta-grid .sta-field {
  margin-bottom: 0;
  max-width: none;
}

/* ── Spin animation ── */
@keyframes sta-spin {
  to { transform: rotate(360deg); }
}
.sta-spin {
  animation: sta-spin 1s linear infinite;
}
</style>
