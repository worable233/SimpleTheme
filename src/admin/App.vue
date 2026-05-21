<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import { fetchSettings, saveSettings } from './api'
import type { AdminSettings } from './api'
import AppearanceTab from './components/AppearanceTab.vue'
import HomeTab from './components/HomeTab.vue'
import SidebarTab from './components/SidebarTab.vue'
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
  { key: 'appearance', label: '外观样式', icon: 'bx bxs-palette' },
  { key: 'home', label: '首页', icon: 'bx bx-home-alt' },
  { key: 'sidebar', label: '侧边栏', icon: 'bx bx-menu' },
  { key: 'advanced', label: '高级设置', icon: 'bx bx-cog' },
]

const currentSettings = ref<AdminSettings>({})
const mobileMenuOpen = ref(false)

function handleBeforeUnload(e: BeforeUnloadEvent) {
  if (isDirty.value) {
    e.preventDefault()
  }
}

function handleResize() {
  if (window.innerWidth > 768 && mobileMenuOpen.value) {
    mobileMenuOpen.value = false
  }
}

async function retryLoad() {
  loading.value = true
  error.value = ''
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
}

onMounted(async () => {
  window.addEventListener('beforeunload', handleBeforeUnload)
  window.addEventListener('resize', handleResize)
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
  window.removeEventListener('resize', handleResize)
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
    home: HomeTab,
    sidebar: SidebarTab,
    advanced: AdvancedTab,
  }
  return map[activeTab.value]
})

const activeTabLabel = computed(() => {
  return tabs.find(t => t.key === activeTab.value)?.label || ''
})
</script>

<template>
  <div id="simple-theme-admin" class="sta-root" v-if="!loading && !error">
    <!-- Mobile backdrop -->
    <div
      class="sta-backdrop"
      :class="{ 'sta-backdrop--visible': mobileMenuOpen }"
      @click="mobileMenuOpen = false"
    ></div>

    <!-- Sidebar -->
    <aside class="sta-sidebar" :class="{ 'sta-sidebar--open': mobileMenuOpen }">
      <div class="sta-sidebar__header">
        <i class="bx bx-layer" style="font-size: 22px; color: var(--sta-primary);"></i>
        <span>Simple Theme</span>
        <button class="sta-sidebar__close" @click="mobileMenuOpen = false" aria-label="关闭菜单">
          <i class="bx bx-x" style="font-size: 18px;"></i>
        </button>
      </div>
      <nav class="sta-sidebar__nav">
        <button
          v-for="tab in tabs"
          :key="tab.key"
          class="sta-nav-item"
          :class="{ 'sta-nav-item--active': activeTab === tab.key }"
          :data-tip="tab.label"
          @click="activeTab = tab.key; mobileMenuOpen = false"
        >
          <i :class="tab.icon" style="font-size: 18px;"></i>
          <span>{{ tab.label }}</span>
        </button>
      </nav>
    </aside>

    <!-- Main content -->
    <main class="sta-content">
      <header class="sta-topbar">
        <div class="sta-topbar__left">
          <button class="sta-hamburger" @click="mobileMenuOpen = !mobileMenuOpen" aria-label="打开菜单">
            <i class="bx bx-menu" style="font-size: 20px;"></i>
          </button>
          <h2 class="sta-topbar__title">{{ activeTabLabel }}</h2>
        </div>
        <div class="sta-topbar__actions">
          <span v-if="isDirty" class="sta-toast sta-toast--warn">未保存</span>
          <span v-if="saved" class="sta-toast sta-toast--success">已保存</span>
          <button
            class="sta-btn sta-btn--primary"
            :class="{ 'sta-btn--saved': saved }"
            :disabled="saving || !isDirty"
            @click="handleSave"
          >
            <i v-if="saving" class="bx bx-loader-alt sta-spin" style="font-size: 16px;"></i>
            <i v-else-if="saved" class="bx bx-check" style="font-size: 16px;"></i>
            <span v-if="!saved">{{ saving ? '保存中...' : '保存设置' }}</span>
            <span v-else>已保存</span>
            <span v-if="isDirty && !saving && !saved" class="sta-btn__dot"></span>
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

  <!-- Loading skeleton -->
  <div v-else-if="loading" id="simple-theme-admin" class="sta-root sta-root--loading">
    <div class="sta-skeleton">
      <div class="sta-skeleton__header"></div>
      <div class="sta-skeleton__line"></div>
      <div class="sta-skeleton__line sta-skeleton__line--short"></div>
      <div class="sta-skeleton__section">
        <div class="sta-skeleton__section-title"></div>
        <div class="sta-skeleton__grid">
          <div class="sta-skeleton__card"><div class="sta-skeleton__label"></div><div class="sta-skeleton__input"></div></div>
          <div class="sta-skeleton__card"><div class="sta-skeleton__label"></div><div class="sta-skeleton__input"></div></div>
          <div class="sta-skeleton__card"><div class="sta-skeleton__label"></div><div class="sta-skeleton__input"></div></div>
          <div class="sta-skeleton__card"><div class="sta-skeleton__label"></div><div class="sta-skeleton__input"></div></div>
        </div>
      </div>
    </div>
  </div>

  <!-- Error state -->
  <div v-else-if="error" id="simple-theme-admin" class="sta-root sta-root--error">
    <div class="sta-error-card">
    <div class="sta-error-card__icon">
      <i class="bx bx-error-circle" style="font-size: 48px; color: var(--sta-danger);"></i>
    </div>
      <h3 class="sta-error-card__title">加载失败</h3>
      <p class="sta-error-card__message">{{ error }}</p>
      <button class="sta-btn sta-btn--primary" @click="retryLoad">重试</button>
    </div>
  </div>
</template>

<style>
/* ═══════════════════════════════════════
   CSS Custom Properties & Dark Mode
   ═══════════════════════════════════════ */
#simple-theme-admin {
  --sta-bg: #f0f2f5;
  --sta-card: #ffffff;
  --sta-border: #e2e5ea;
  --sta-border-light: #f0f2f5;
  --sta-foreground: #1e293b;
  --sta-secondary: #64748b;
  --sta-primary: #334155;
  --sta-primary-hover: #475569;
  --sta-primary-ring: rgba(51, 65, 85, 0.15);
  --sta-radius: 10px;
  --sta-radius-sm: 6px;
  --sta-sidebar-width: 220px;
  --sta-topbar-height: 60px;
  --sta-shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.05);
  --sta-shadow-md: 0 4px 12px rgba(0, 0, 0, 0.08);
  --sta-shadow-lg: 0 8px 24px rgba(0, 0, 0, 0.12);
  --sta-success: #059669;
  --sta-success-bg: #ecfdf5;
  --sta-success-border: #a7f3d0;
  --sta-warning: #d97706;
  --sta-warning-bg: #fffbeb;
  --sta-warning-border: #fde68a;
  --sta-error: #dc2626;
  --sta-error-bg: #fef2f2;
  --sta-error-border: #fecaca;
  --sta-transition: 0.2s ease;
  --sta-nav-active-bg: #f1f5f9;
}

/* Dark mode: respects system preference */
@media (prefers-color-scheme: dark) {
  #simple-theme-admin {
    --sta-bg: #0f172a;
    --sta-card: #1e293b;
    --sta-border: #334155;
    --sta-border-light: #1e293b;
    --sta-foreground: #e2e8f0;
    --sta-secondary: #94a3b8;
    --sta-primary: #6366f1;
    --sta-primary-hover: #818cf8;
    --sta-primary-ring: rgba(99, 102, 241, 0.25);
    --sta-shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.2);
    --sta-shadow-md: 0 4px 12px rgba(0, 0, 0, 0.3);
    --sta-shadow-lg: 0 8px 24px rgba(0, 0, 0, 0.4);
    --sta-success: #34d399;
    --sta-success-bg: #064e3b;
    --sta-success-border: #065f46;
    --sta-warning: #fbbf24;
    --sta-warning-bg: #78350f;
    --sta-warning-border: #92400e;
    --sta-error: #f87171;
    --sta-error-bg: #450a0a;
    --sta-error-border: #7f1d1d;
    --sta-nav-active-bg: #334155;
  }
}

/* ═══════════════════════════════════════
   Reset & Scoping
   ═══════════════════════════════════════ */
#simple-theme-admin,
#simple-theme-admin * {
  box-sizing: border-box;
}

#simple-theme-admin {
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'PingFang SC', 'Microsoft YaHei', sans-serif;
  color: var(--sta-foreground);
  line-height: 1.5;
  font-size: 14px;
}

/* ═══════════════════════════════════════
   Root Layout
   ═══════════════════════════════════════ */
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

/* ═══════════════════════════════════════
   Backdrop (mobile drawer overlay)
   ═══════════════════════════════════════ */
.sta-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.4);
  z-index: 90;
  opacity: 0;
  pointer-events: none;
  transition: opacity 0.25s ease;
}

.sta-backdrop--visible {
  opacity: 1;
  pointer-events: auto;
}

/* ═══════════════════════════════════════
   Sidebar
   ═══════════════════════════════════════ */
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
  z-index: 50;
  transition: width 0.25s ease;
  overflow: hidden;
  white-space: nowrap;
}

.sta-sidebar__header {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 18px 20px;
  font-size: 15px;
  font-weight: 650;
  color: var(--sta-foreground);
  border-bottom: 1px solid var(--sta-border);
  flex-shrink: 0;
}

.sta-sidebar__header svg {
  flex-shrink: 0;
  color: var(--sta-primary);
}

.sta-sidebar__close {
  display: none;
  margin-left: auto;
  background: none;
  border: none;
  color: var(--sta-secondary);
  cursor: pointer;
  padding: 4px;
  border-radius: 4px;
}

.sta-sidebar__close:hover {
  background: var(--sta-nav-active-bg);
  color: var(--sta-foreground);
}

.sta-sidebar__nav {
  flex: 1;
  display: flex;
  flex-direction: column;
  padding: 10px 8px;
  gap: 1px;
  overflow-y: auto;
}

.sta-sidebar__nav::-webkit-scrollbar {
  width: 4px;
}

.sta-sidebar__nav::-webkit-scrollbar-thumb {
  background: var(--sta-border);
  border-radius: 2px;
}

/* ═══════════════════════════════════════
   Nav Items
   ═══════════════════════════════════════ */
.sta-nav-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 14px;
  border: none;
  background: none;
  border-radius: var(--sta-radius-sm);
  font-size: 14px;
  color: var(--sta-secondary);
  cursor: pointer;
  transition: all var(--sta-transition);
  width: 100%;
  text-align: left;
  font-family: inherit;
  position: relative;
  flex-shrink: 0;
}

.sta-nav-item svg {
  flex-shrink: 0;
  transition: stroke var(--sta-transition);
}

.sta-nav-item:hover {
  background: var(--sta-nav-active-bg);
  color: var(--sta-foreground);
}

.sta-nav-item--active {
  background: var(--sta-nav-active-bg);
  color: var(--sta-primary);
  font-weight: 600;
}

.sta-nav-item--active svg {
  stroke: var(--sta-primary);
}

.sta-nav-item--active::before {
  content: '';
  position: absolute;
  left: 0;
  top: 6px;
  bottom: 6px;
  width: 3px;
  background: var(--sta-primary);
  border-radius: 0 2px 2px 0;
}

/* ═══════════════════════════════════════
   Topbar
   ═══════════════════════════════════════ */
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
  z-index: 30;
  box-shadow: var(--sta-shadow-sm);
  flex-shrink: 0;
}

.sta-topbar__left {
  display: flex;
  align-items: center;
  gap: 12px;
  min-width: 0;
}

.sta-topbar__title {
  margin: 0;
  font-size: 17px;
  font-weight: 625;
  color: var(--sta-foreground);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.sta-topbar__actions {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-shrink: 0;
}

/* ═══════════════════════════════════════
   Hamburger (mobile only)
   ═══════════════════════════════════════ */
.sta-hamburger {
  display: none;
  background: none;
  border: none;
  color: var(--sta-foreground);
  cursor: pointer;
  padding: 6px;
  border-radius: var(--sta-radius-sm);
  transition: background var(--sta-transition);
}

.sta-hamburger:hover {
  background: var(--sta-nav-active-bg);
}

/* ═══════════════════════════════════════
   Buttons
   ═══════════════════════════════════════ */
.sta-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 8px 20px;
  border: 1px solid var(--sta-border);
  border-radius: var(--sta-radius-sm);
  font-size: 14px;
  font-family: inherit;
  cursor: pointer;
  transition: all var(--sta-transition);
  background: var(--sta-card);
  color: var(--sta-foreground);
  line-height: 1.4;
  white-space: nowrap;
}

.sta-btn:hover {
  background: var(--sta-nav-active-bg);
}

.sta-btn--primary {
  background: var(--sta-primary);
  color: #fff;
  border-color: var(--sta-primary);
}

.sta-btn--primary:hover {
  background: var(--sta-primary-hover);
  border-color: var(--sta-primary-hover);
}

.sta-btn--primary:active {
  transform: scale(0.97);
}

.sta-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.sta-btn:disabled:active {
  transform: none;
}

.sta-btn--saved {
  background: var(--sta-success) !important;
  border-color: var(--sta-success) !important;
  color: #fff !important;
  pointer-events: none;
}

.sta-btn__dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: var(--sta-warning);
  display: inline-block;
  flex-shrink: 0;
}

/* ═══════════════════════════════════════
   Toast
   ═══════════════════════════════════════ */
.sta-toast {
  font-size: 12px;
  padding: 4px 10px;
  border-radius: 4px;
  font-weight: 500;
  white-space: nowrap;
}

.sta-toast--success {
  background: var(--sta-success-bg);
  color: var(--sta-success);
  border: 1px solid var(--sta-success-border);
}

.sta-toast--warn {
  background: var(--sta-warning-bg);
  color: var(--sta-warning);
  border: 1px solid var(--sta-warning-border);
}

/* ═══════════════════════════════════════
   Content Body
   ═══════════════════════════════════════ */
.sta-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  min-width: 0;
}

.sta-body {
  flex: 1;
  padding: 28px 32px;
  overflow-y: auto;
}

/* ═══════════════════════════════════════
   Form Elements
   ═══════════════════════════════════════ */
.sta-field {
  margin-bottom: 20px;
  max-width: 640px;
}

.sta-field:last-child {
  margin-bottom: 0;
}

.sta-field__label {
  display: block;
  font-size: 13px;
  font-weight: 600;
  margin-bottom: 6px;
  color: var(--sta-foreground);
}

.sta-field__desc {
  font-size: 12px;
  color: var(--sta-secondary);
  margin: 5px 0 0;
  line-height: 1.5;
}

.sta-input {
  width: 100%;
  padding: 8px 12px;
  border: 1px solid var(--sta-border);
  border-radius: var(--sta-radius-sm);
  font-size: 14px;
  font-family: inherit;
  color: var(--sta-foreground);
  background: var(--sta-card);
  transition: border-color var(--sta-transition), box-shadow var(--sta-transition);
  line-height: 1.5;
}

.sta-input:focus {
  outline: none;
  border-color: var(--sta-primary);
  box-shadow: 0 0 0 2px var(--sta-primary-ring);
}

.sta-input::placeholder {
  color: var(--sta-secondary);
  opacity: 0.6;
}

.sta-textarea {
  width: 100%;
  padding: 10px 12px;
  border: 1px solid var(--sta-border);
  border-radius: var(--sta-radius-sm);
  font-size: 14px;
  font-family: 'Menlo', 'Consolas', 'Courier New', monospace;
  color: var(--sta-foreground);
  background: var(--sta-card);
  resize: vertical;
  transition: border-color var(--sta-transition), box-shadow var(--sta-transition);
  min-height: 80px;
  line-height: 1.6;
}

.sta-textarea:focus {
  outline: none;
  border-color: var(--sta-primary);
  box-shadow: 0 0 0 2px var(--sta-primary-ring);
}

.sta-textarea::placeholder {
  color: var(--sta-secondary);
  opacity: 0.6;
}

.sta-select {
  padding: 8px 12px;
  border: 1px solid var(--sta-border);
  border-radius: var(--sta-radius-sm);
  font-size: 14px;
  font-family: inherit;
  color: var(--sta-foreground);
  background: var(--sta-card);
  cursor: pointer;
  transition: border-color var(--sta-transition), box-shadow var(--sta-transition);
  min-width: 180px;
  line-height: 1.5;
}

.sta-select:focus {
  outline: none;
  border-color: var(--sta-primary);
  box-shadow: 0 0 0 2px var(--sta-primary-ring);
}

.sta-checkbox {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  cursor: pointer;
  font-size: 14px;
  user-select: none;
  line-height: 1.5;
}

.sta-checkbox input[type="checkbox"] {
  width: 17px;
  height: 17px;
  accent-color: var(--sta-primary);
  cursor: pointer;
  flex-shrink: 0;
}

.sta-radio-group {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.sta-radio {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  cursor: pointer;
  font-size: 14px;
  user-select: none;
}

.sta-radio input[type="radio"] {
  accent-color: var(--sta-primary);
  width: 17px;
  height: 17px;
  cursor: pointer;
}

/* Input number narrow */
.sta-input--number {
  width: 130px;
}

.sta-input--flex {
  flex: 1;
  min-width: 0;
}

/* ═══════════════════════════════════════
   Color Row (picker + text group)
   ═══════════════════════════════════════ */
.sta-color-row {
  display: flex;
  align-items: center;
  border: 1px solid var(--sta-border);
  border-radius: var(--sta-radius-sm);
  overflow: hidden;
  transition: border-color var(--sta-transition), box-shadow var(--sta-transition);
  background: var(--sta-card);
  width: fit-content;
}

.sta-color-row:focus-within {
  border-color: var(--sta-primary);
  box-shadow: 0 0 0 2px var(--sta-primary-ring);
}

.sta-input--color-picker {
  width: 40px;
  height: 38px;
  padding: 3px;
  border: none;
  border-radius: 0;
  cursor: pointer;
  flex-shrink: 0;
  background: none;
  display: block;
}

.sta-input--color-picker::-webkit-color-swatch-wrapper {
  padding: 2px;
}

.sta-input--color-picker::-webkit-color-swatch {
  border: 1px solid var(--sta-border);
  border-radius: 3px;
}

.sta-input--color-picker::-moz-color-swatch {
  border: 1px solid var(--sta-border);
  border-radius: 3px;
}

.sta-input--color-text {
  width: 108px;
  font-family: 'Menlo', 'Consolas', 'Courier New', monospace;
  border: none;
  border-radius: 0;
  border-left: 1px solid var(--sta-border);
  font-size: 13px;
  padding: 8px 10px;
  background: var(--sta-card);
  color: var(--sta-foreground);
}

.sta-input--color-text:focus {
  outline: none;
}

/* ═══════════════════════════════════════
   Input Row (label + button)
   ═══════════════════════════════════════ */
.sta-input-row {
  display: flex;
  align-items: center;
  gap: 8px;
}

.sta-input-row .sta-btn {
  flex-shrink: 0;
}

/* ═══════════════════════════════════════
   Sections (card containers)
   ═══════════════════════════════════════ */
.sta-section {
  background: var(--sta-card);
  border: 1px solid var(--sta-border);
  border-radius: var(--sta-radius);
  padding: 24px;
  margin-bottom: 24px;
  box-shadow: var(--sta-shadow-sm);
  transition: box-shadow var(--sta-transition);
}

.sta-section:hover {
  box-shadow: var(--sta-shadow-md);
}

.sta-section__title {
  font-size: 15px;
  font-weight: 650;
  margin: 0 0 4px;
  padding-left: 12px;
  position: relative;
}

.sta-section__title::before {
  content: '';
  position: absolute;
  left: 0;
  top: 3px;
  bottom: 3px;
  width: 3px;
  background: var(--sta-primary);
  border-radius: 2px;
}

.sta-section__desc {
  font-size: 13px;
  color: var(--sta-secondary);
  margin: 0 0 20px;
  padding-left: 12px;
}

/* ═══════════════════════════════════════
   Grid (auto-fill cards for checkboxes/colors)
   ═══════════════════════════════════════ */
.sta-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 12px;
}

.sta-grid .sta-field {
  margin-bottom: 0;
  max-width: none;
  background: var(--sta-bg);
  border: 1px solid var(--sta-border);
  border-radius: var(--sta-radius-sm);
  padding: 14px 16px;
  transition: all var(--sta-transition);
}

.sta-grid .sta-field:hover {
  border-color: var(--sta-primary);
}

.sta-grid .sta-field:has(input[type="checkbox"]:checked),
.sta-grid .sta-field:has(input[type="radio"]:checked) {
  border-color: var(--sta-primary);
  background: color-mix(in srgb, var(--sta-primary) 6%, var(--sta-card));
}

.sta-grid .sta-color-row {
  width: 100%;
}

/* ═══════════════════════════════════════
   Skeleton Loading
   ═══════════════════════════════════════ */
.sta-skeleton {
  width: 100%;
  max-width: 680px;
  padding: 40px;
}

.sta-skeleton__header {
  height: 28px;
  width: 40%;
  background: linear-gradient(90deg, var(--sta-border) 25%, var(--sta-border-light) 50%, var(--sta-border) 75%);
  background-size: 200px 100%;
  animation: sta-shimmer 1.5s ease-in-out infinite;
  border-radius: 6px;
  margin-bottom: 24px;
}

.sta-skeleton__line {
  height: 40px;
  background: linear-gradient(90deg, var(--sta-border) 25%, var(--sta-border-light) 50%, var(--sta-border) 75%);
  background-size: 200px 100%;
  animation: sta-shimmer 1.5s ease-in-out infinite;
  border-radius: 6px;
  margin-bottom: 16px;
}

.sta-skeleton__line--short {
  width: 60%;
}

.sta-skeleton__section {
  margin-top: 32px;
}

.sta-skeleton__section-title {
  height: 20px;
  width: 30%;
  background: linear-gradient(90deg, var(--sta-border) 25%, var(--sta-border-light) 50%, var(--sta-border) 75%);
  background-size: 200px 100%;
  animation: sta-shimmer 1.5s ease-in-out infinite;
  border-radius: 6px;
  margin-bottom: 16px;
}

.sta-skeleton__grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}

.sta-skeleton__card {
  background: var(--sta-card);
  border: 1px solid var(--sta-border);
  border-radius: var(--sta-radius);
  padding: 20px;
}

.sta-skeleton__label {
  height: 14px;
  width: 50%;
  background: linear-gradient(90deg, var(--sta-border) 25%, var(--sta-border-light) 50%, var(--sta-border) 75%);
  background-size: 200px 100%;
  animation: sta-shimmer 1.5s ease-in-out infinite;
  border-radius: 4px;
  margin-bottom: 12px;
}

.sta-skeleton__input {
  height: 38px;
  background: linear-gradient(90deg, var(--sta-border) 25%, var(--sta-border-light) 50%, var(--sta-border) 75%);
  background-size: 200px 100%;
  animation: sta-shimmer 1.5s ease-in-out infinite;
  border-radius: 6px;
}

/* ═══════════════════════════════════════
   Error Card
   ═══════════════════════════════════════ */
.sta-error-card {
  text-align: center;
  background: var(--sta-card);
  border: 1px solid var(--sta-error-border);
  border-radius: var(--sta-radius);
  padding: 48px 40px;
  max-width: 460px;
  box-shadow: var(--sta-shadow-md);
}

.sta-error-card__icon {
  margin-bottom: 16px;
  color: var(--sta-error);
}

.sta-error-card__title {
  font-size: 18px;
  font-weight: 650;
  margin: 0 0 8px;
  color: var(--sta-foreground);
}

.sta-error-card__message {
  font-size: 14px;
  color: var(--sta-secondary);
  margin: 0 0 24px;
  line-height: 1.6;
}

/* ═══════════════════════════════════════
   Tab Transition
   ═══════════════════════════════════════ */
.sta-tab-enter-active,
.sta-tab-leave-active {
  transition: all 0.2s ease;
}

.sta-tab-enter-from {
  opacity: 0;
  transform: translateY(10px);
}

.sta-tab-leave-to {
  opacity: 0;
  transform: translateY(-8px);
}

/* ═══════════════════════════════════════
   Animations
   ═══════════════════════════════════════ */
@keyframes sta-spin {
  to { transform: rotate(360deg); }
}

.sta-spin {
  animation: sta-spin 1s linear infinite;
}

@keyframes sta-shimmer {
  0% { background-position: -200px 0; }
  100% { background-position: calc(200px + 100%) 0; }
}

/* ═══════════════════════════════════════════════════
   RESPONSIVE: Tablet (768px - 1024px) — collapsed sidebar
   ═══════════════════════════════════════════════════ */
@media (max-width: 1024px) and (min-width: 769px) {
  .sta-sidebar {
    width: 58px;
  }

  .sta-sidebar__header span {
    opacity: 0;
    transition: opacity 0.2s ease;
  }

  .sta-sidebar__header {
    justify-content: center;
    padding: 18px 0;
  }

  .sta-nav-item {
    justify-content: center;
    padding: 10px 0;
  }

  .sta-nav-item span {
    display: none;
  }

  .sta-nav-item--active::before {
    left: 0;
    top: 8px;
    bottom: 8px;
    width: 3px;
  }

  /* Tooltip on hover */
  .sta-nav-item:hover::after {
    content: attr(data-tip);
    position: absolute;
    left: 100%;
    top: 50%;
    transform: translateY(-50%);
    background: var(--sta-foreground);
    color: var(--sta-card);
    padding: 5px 10px;
    border-radius: 4px;
    font-size: 12px;
    white-space: nowrap;
    z-index: 100;
    margin-left: 10px;
    pointer-events: none;
    font-weight: 500;
    box-shadow: var(--sta-shadow-md);
  }

  .sta-topbar {
    padding: 0 24px;
  }

  .sta-body {
    padding: 24px;
  }
}

/* ═══════════════════════════════════════════════════
   RESPONSIVE: Mobile (<768px) — drawer sidebar
   ═══════════════════════════════════════════════════ */
@media (max-width: 768px) {
  .sta-root {
    margin: -10px -10px -20px;
  }

  .sta-sidebar {
    position: fixed;
    top: 0;
    left: 0;
    bottom: 0;
    z-index: 100;
    transform: translateX(-100%);
    transition: transform 0.25s ease;
    width: 260px;
    height: 100vh;
    box-shadow: none;
    border-right: 1px solid var(--sta-border);
  }

  .sta-sidebar--open {
    transform: translateX(0);
    box-shadow: var(--sta-shadow-lg);
  }

  .sta-sidebar__header span {
    opacity: 1;
  }

  .sta-sidebar__close {
    display: block;
  }

  .sta-sidebar__header {
    justify-content: flex-start;
    padding: 18px 20px;
  }

  .sta-nav-item {
    justify-content: flex-start;
    padding: 12px 16px;
  }

  .sta-nav-item span {
    display: inline;
  }

  .sta-nav-item--active::before {
    left: 0;
    top: 6px;
    bottom: 6px;
    width: 3px;
  }

  /* Hamburger visible on mobile */
  .sta-hamburger {
    display: block;
  }

  .sta-topbar {
    padding: 0 16px;
    height: 54px;
  }

  .sta-topbar__title {
    font-size: 15px;
  }

  .sta-topbar__actions {
    gap: 8px;
  }

  .sta-body {
    padding: 16px;
  }

  .sta-section {
    padding: 18px;
    margin-bottom: 16px;
  }

  .sta-section__desc {
    margin-bottom: 16px;
  }

  .sta-grid {
    grid-template-columns: 1fr;
  }

  .sta-field {
    max-width: none;
  }

  .sta-btn {
    padding: 8px 14px;
  }

  .sta-btn .sta-btn__text--mobile-hidden {
    display: none;
  }

  .sta-toast {
    font-size: 11px;
    padding: 3px 8px;
  }

  .sta-select {
    min-width: 120px;
    width: 100%;
  }

  .sta-input--number {
    width: 100%;
  }

  .sta-skeleton__grid {
    grid-template-columns: 1fr;
  }
}

/* ═══════════════════════════════════════════════════
   RESPONSIVE: Small mobile (<480px)
   ═══════════════════════════════════════════════════ */
@media (max-width: 480px) {
  .sta-topbar__actions .sta-toast {
    display: none;
  }

  .sta-body {
    padding: 12px;
  }

  .sta-section {
    padding: 14px;
    border-radius: var(--sta-radius-sm);
  }

  .sta-topbar__title {
    font-size: 14px;
  }

  .sta-input-row {
    flex-direction: column;
    align-items: stretch;
  }

  .sta-input-row .sta-btn {
    width: 100%;
    justify-content: center;
  }
}
</style>
