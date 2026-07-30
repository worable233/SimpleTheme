<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref, type Component } from 'vue'
import { fetchSettings, saveSettings } from './api'
import type { AdminSettings } from './api'
import AppearanceTab from './components/AppearanceTab.vue'
import HomeTab from './components/HomeTab.vue'
import SidebarTab from './components/SidebarTab.vue'
import AdvancedTab from './components/AdvancedTab.vue'
import AdminThemeTab from './components/AdminThemeTab.vue'
import SmtpTab from './components/SmtpTab.vue'
import EmailTemplateTab from './components/EmailTemplateTab.vue'
import AppToast from './components/AppToast.vue'
import './styles/layers.css'

const settings = ref<AdminSettings>({})
const defaults = ref<AdminSettings>({})
const activeTab = ref('appearance')
const saving = ref(false)
const saved = ref(false)
const loading = ref(true)
const error = ref('')
const isDirty = ref(false)
const mobileMenuOpen = ref(false)
const toastMessage = ref('')
const toastType = ref<'success' | 'error' | 'warning'>('success')
const toastVisible = ref(false)

const tabs = [
  { key: 'appearance', label: '外观样式', sub: 'Appearance.' },
  { key: 'home', label: '首页设置', sub: 'Home.' },
  { key: 'sidebar', label: '侧边栏', sub: 'Sidebar.' },
  { key: 'admin_theme', label: '后台样式', sub: 'Admin.' },
  { key: 'advanced', label: '高级', sub: 'Advanced.' },
  { key: 'smtp', label: 'SMTP', sub: 'Mail.' },
  { key: 'email_template', label: '邮件模板', sub: 'Template.' },
]

const currentSettings = ref<AdminSettings>({})

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

async function loadSettings() {
  loading.value = true
  error.value = ''
  try {
    const data = await fetchSettings()
    defaults.value = data.defaults || {}
    settings.value = data.settings || {}
    currentSettings.value = JSON.parse(JSON.stringify(settings.value))
  } catch (e) {
    error.value = e instanceof Error ? e.message : String(e)
  } finally {
    loading.value = false
  }
}

onMounted(async () => {
  window.addEventListener('beforeunload', handleBeforeUnload)
  window.addEventListener('resize', handleResize)
  await loadSettings()
})

onUnmounted(() => {
  window.removeEventListener('beforeunload', handleBeforeUnload)
  window.removeEventListener('resize', handleResize)
})

function updateSetting(key: string, value: unknown) {
  currentSettings.value[key] = value
  isDirty.value = true
}

async function handleSave() {
  saving.value = true
  try {
    const result = await saveSettings(currentSettings.value)
    settings.value = result
    currentSettings.value = JSON.parse(JSON.stringify(result))
    isDirty.value = false
    saved.value = true
    setTimeout(() => { saved.value = false }, 2000)
    toastMessage.value = `「${activeTabLabel.value}」设置已保存`
    toastType.value = 'success'
    toastVisible.value = true
  } catch (e) {
    toastMessage.value = '保存失败: ' + (e instanceof Error ? e.message : String(e))
    toastType.value = 'error'
    toastVisible.value = true
  } finally {
    saving.value = false
  }
}

function onToastClose() {
  toastVisible.value = false
}

const activeComponent = computed(() => {
  const map: Record<string, Component> = {
    appearance: AppearanceTab,
    home: HomeTab,
    sidebar: SidebarTab,
    admin_theme: AdminThemeTab,
    advanced: AdvancedTab,
    smtp: SmtpTab,
    email_template: EmailTemplateTab,
  }
  return map[activeTab.value]
})

const activeTabLabel = computed(() => {
  return tabs.find(t => t.key === activeTab.value)?.label || ''
})

const activeTabSub = computed(() => {
  return tabs.find(t => t.key === activeTab.value)?.sub || ''
})
</script>

<template>
  <AppToast
    :message="toastMessage"
    :type="toastType"
    :visible="toastVisible"
    @close="onToastClose"
  />

  <!-- Main app -->
  <div id="simple-theme-admin" class="xh-root" v-if="!loading && !error">
    <!-- Mobile backdrop -->
    <div
      class="xh-backdrop"
      :class="{ 'xh-backdrop--visible': mobileMenuOpen }"
      @click="mobileMenuOpen = false"
    ></div>

    <!-- Sidebar -->
    <aside class="xh-sidebar" :class="{ 'xh-sidebar--open': mobileMenuOpen }">
      <div class="xh-sidebar__header">
        <span class="xh-sidebar__brand">S</span>
        <span class="xh-sidebar__brand-text">Simple Theme</span>
        <button class="xh-sidebar__close" @click="mobileMenuOpen = false" aria-label="关闭菜单">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
          </svg>
        </button>
      </div>
      <nav class="xh-sidebar__nav">
        <button
          v-for="tab in tabs"
          :key="tab.key"
          class="xh-nav-item"
          :class="{ 'xh-nav-item--active': activeTab === tab.key }"
          @click="activeTab = tab.key; mobileMenuOpen = false"
        >
          <span>{{ tab.label }}</span>
        </button>
      </nav>
    </aside>

    <!-- Main content -->
    <main class="xh-content">
      <header class="xh-topbar">
        <div class="xh-topbar__left">
          <button class="xh-hamburger" @click="mobileMenuOpen = !mobileMenuOpen" aria-label="打开菜单">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/>
            </svg>
          </button>
          <h2 class="xh-topbar__title">
            {{ activeTabLabel }}
            <span v-if="activeTabSub" class="xh-topbar__sub">{{ activeTabSub }}</span>
          </h2>
        </div>
        <div class="xh-topbar__actions">
          <span v-if="isDirty" class="xh-dirty-badge">未保存</span>
          <button
            class="xh-btn"
            :class="{ 'xh-btn--primary': !saved, 'xh-btn--success': saved }"
            :disabled="saving || !isDirty"
            @click="handleSave"
          >
            <template v-if="saving">
              <svg class="xh-spin" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 6px;"><path d="M21 12a9 9 0 11-6.219-8.56"/></svg>
              保存中...
            </template>
            <template v-else-if="saved">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 6px;"><circle cx="12" cy="12" r="10"/><path d="M8 12l2 2 4-4"/></svg>
              已保存
            </template>
            <template v-else>保存设置</template>
          </button>
        </div>
      </header>

      <div class="xh-body">
        <component
          :is="activeComponent"
          :settings="currentSettings"
          :defaults="defaults"
          @update="updateSetting"
          @toast="(msg: string, type: 'success' | 'error' | 'warning') => { toastMessage = msg; toastType = type; toastVisible = true }"
        />
      </div>
    </main>
  </div>

  <!-- Loading skeleton -->
  <div v-else-if="loading" id="simple-theme-admin" class="xh-root xh-root--centered">
    <div class="xh-skeleton">
      <div class="xh-skeleton__block"></div>
      <div class="xh-skeleton__block"></div>
      <div class="xh-skeleton__block"></div>
      <div class="xh-skeleton__cards">
        <div class="xh-skeleton__card"></div>
        <div class="xh-skeleton__card"></div>
      </div>
    </div>
  </div>

  <!-- Error state -->
  <div v-else id="simple-theme-admin" class="xh-root xh-root--centered">
    <div class="xh-error">
      <div class="xh-error__icon">!</div>
      <h3 class="xh-error__title">加载失败</h3>
      <p class="xh-error__message">{{ error }}</p>
      <button class="xh-btn xh-btn--primary" @click="loadSettings">重试</button>
    </div>
  </div>
</template>
