<script setup lang="ts">
import { onMounted, onUnmounted, ref } from 'vue'
import { fetchSettings, saveSettings } from './api'
import type { AdminSettings } from './api'
import { StButton, StIcon, StSpinner, StTabPane, StTabs, StToast, useToast } from '@/ui'
import SettingsAppearance from './components/SettingsAppearance.vue'
import SettingsHome from './components/SettingsHome.vue'
import SettingsSidebar from './components/SettingsSidebar.vue'
import SettingsComments from './components/SettingsComments.vue'
import SettingsFooter from './components/SettingsFooter.vue'
import SettingsAdvanced from './components/SettingsAdvanced.vue'
import SettingsMail from './components/SettingsMail.vue'

const settings = ref<AdminSettings>({})
const defaults = ref<AdminSettings>({})
const activeTab = ref('appearance')
const loading = ref(true)
const saving = ref(false)
const dirty = ref(false)
const saved = ref(false)
const error = ref('')
const toast = useToast()

const radiusMap: Record<'small' | 'medium' | 'large', { medium: string; large: string }> = {
  small: { medium: '0.25rem', large: '0.5rem' },
  medium: { medium: '0.375rem', large: '0.75rem' },
  large: { medium: '0.625rem', large: '1rem' },
}

const shadowMap: Record<
  'none' | 'small' | 'medium' | 'large',
  { small: string; medium: string; large: string }
> = {
  none: { small: 'none', medium: 'none', large: 'none' },
  small: {
    small: '0 1px 2px 0 rgb(0 0 0 / 0.06)',
    medium: '0 2px 4px rgb(0 0 0 / 0.08)',
    large: '0 6px 12px rgb(0 0 0 / 0.1)',
  },
  medium: {
    small: '0 2px 4px rgb(0 0 0 / 0.1)',
    medium: '0 6px 18px rgb(0 0 0 / 0.12)',
    large: '0 12px 28px rgb(0 0 0 / 0.16)',
  },
  large: {
    small: '0 4px 10px rgb(0 0 0 / 0.12)',
    medium: '0 10px 24px rgb(0 0 0 / 0.16)',
    large: '0 18px 40px rgb(0 0 0 / 0.2)',
  },
}

function normalizePixel(value: unknown, fallback: number, min: number, max: number) {
  const parsed = Number(value)
  if (!Number.isFinite(parsed)) return fallback
  return Math.min(max, Math.max(min, Math.round(parsed)))
}

function str(data: AdminSettings, key: string, fallback = '') {
  const v = data[key] ?? defaults.value[key] ?? fallback
  return String(v)
}

/** 保存前实时预览：把当前表单值写进 :root 令牌，改色/改宽无需保存即可看到效果。 */
function syncThemeTokens(data: AdminSettings) {
  const root = document.documentElement
  const radiusKey = str(data, 'radius', 'medium') as keyof typeof radiusMap
  const shadowKey = str(data, 'shadow', 'small') as keyof typeof shadowMap
  const radius = radiusMap[radiusKey] || radiusMap.medium
  const shadow = shadowMap[shadowKey] || shadowMap.small

  root.style.setProperty('--primary', str(data, 'primary_color', '#333333'))
  root.style.setProperty('--radius-medium', radius.medium)
  root.style.setProperty('--radius-large', radius.large)
  root.style.setProperty('--shadow-small', shadow.small)
  root.style.setProperty('--shadow-medium', shadow.medium)
  root.style.setProperty('--shadow-large', shadow.large)
  root.style.setProperty(
    '--container-max',
    `${normalizePixel(data.container_max_width, 1500, 960, 2000)}px`,
  )
  root.style.setProperty(
    '--article-max-width',
    `${normalizePixel(data.article_max_width, 900, 680, 1200)}px`,
  )

  const palette: Record<string, unknown> = {
    '--theme-bg-light': data.background_light,
    '--theme-bg-dark': data.background_dark,
    '--theme-card-light': data.card_light,
    '--theme-card-dark': data.card_dark,
    '--theme-fg-light': data.foreground_light,
    '--theme-fg-dark': data.foreground_dark,
    '--theme-accent-light': data.accent_light,
    '--theme-accent-dark': data.accent_dark,
    '--theme-border-light': data.border_light,
    '--theme-border-dark': data.border_dark,
  }
  for (const [name, value] of Object.entries(palette)) {
    if (typeof value === 'string' && value) root.style.setProperty(name, value)
    else root.style.removeProperty(name)
  }
}

/**
 * WordPress 后台默认仅提供浅色。仅当开启「后台美化」后，设置页才跟随前端
 * 明暗设置（localStorage 优先，否则跟随系统）；未开启时固定浅色，避免与
 * WP 原生后台割裂。
 */
function syncThemeMode() {
  if (!settings.value.admin_theme_enabled) {
    document.documentElement.setAttribute('data-theme', 'light')
    document.documentElement.style.colorScheme = 'light'
    return
  }
  let theme = localStorage.getItem('theme')
  if (theme !== 'light' && theme !== 'dark') {
    theme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'
  }
  document.documentElement.setAttribute('data-theme', theme)
  document.documentElement.style.colorScheme = theme
}

function update(key: string, next: unknown) {
  settings.value[key] = next
  dirty.value = true
  saved.value = false
  syncThemeTokens(settings.value)
  if (key === 'admin_theme_enabled') syncThemeMode()
}

/** 原内联 toast 固定停留 2600ms；这里用 duration 对齐，迁移不改变可见节奏 */
function showToast(message: string, type: 'success' | 'error' = 'success') {
  toast.show({ type, title: message, duration: 2600 })
}

async function load() {
  loading.value = true
  error.value = ''
  try {
    const data = await fetchSettings()
    settings.value = data.settings || {}
    defaults.value = data.defaults || {}
    syncThemeMode()
    syncThemeTokens(settings.value)
  } catch (err) {
    error.value = err instanceof Error ? err.message : String(err)
  } finally {
    loading.value = false
  }
}

async function save() {
  saving.value = true
  try {
    settings.value = await saveSettings(settings.value)
    dirty.value = false
    saved.value = true
    showToast('设置已保存')
    setTimeout(() => {
      saved.value = false
    }, 2200)
  } catch (err) {
    showToast('保存失败: ' + (err instanceof Error ? err.message : String(err)), 'error')
  } finally {
    saving.value = false
  }
}

function beforeUnload(event: BeforeUnloadEvent) {
  if (dirty.value) {
    event.preventDefault()
    event.returnValue = ''
  }
}

onMounted(() => {
  window.addEventListener('beforeunload', beforeUnload)
  syncThemeMode()
  void load()
})

onUnmounted(() => {
  window.removeEventListener('beforeunload', beforeUnload)
  toast.clear()
})
</script>

<template>
  <div class="admin-app">
    <div v-if="loading" class="admin-app__state">
      <StSpinner size="large" description="正在加载设置…" />
    </div>

    <div v-else-if="error" class="admin-app__state">
      <section class="admin-app__error-card">
        <span class="admin-app__error-icon"><StIcon name="alert-triangle" :size="22" /></span>
        <h1 class="admin-app__error-title">设置加载失败</h1>
        <p class="admin-app__error-desc">{{ error }}</p>
        <StButton type="primary" @click="load">重试</StButton>
      </section>
    </div>

    <template v-else>
      <header class="admin-app__bar">
        <div class="admin-app__bar-inner">
          <div class="admin-app__brand">
            <span class="admin-app__brand-mark"><StIcon name="palette" :size="17" /></span>
            <span class="admin-app__brand-text">
              <strong class="admin-app__brand-name">Simple Theme</strong>
              <span class="admin-app__brand-sub">主题设置</span>
            </span>
          </div>
          <div class="admin-app__bar-actions">
            <span v-if="dirty" class="admin-app__dirty">有未保存的更改</span>
            <StButton
              :type="saved ? 'success' : 'primary'"
              :loading="saving"
              :disabled="!dirty"
              @click="save"
            >
              {{ saved ? '已保存' : '保存设置' }}
            </StButton>
          </div>
        </div>
      </header>

      <main class="admin-app__main">
        <StTabs v-model:value="activeTab" type="line" class="admin-app__tabs">
          <StTabPane value="appearance" label="外观" icon="palette">
            <SettingsAppearance :settings="settings" :defaults="defaults" @update="update" />
          </StTabPane>
          <StTabPane value="home" label="首页" icon="home">
            <SettingsHome :settings="settings" :defaults="defaults" @update="update" />
          </StTabPane>
          <StTabPane value="sidebar" label="侧栏" icon="layout-sidebar">
            <SettingsSidebar :settings="settings" :defaults="defaults" @update="update" />
          </StTabPane>
          <StTabPane value="comments" label="评论" icon="message-circle">
            <SettingsComments :settings="settings" :defaults="defaults" @update="update" />
          </StTabPane>
          <StTabPane value="footer" label="页脚" icon="world">
            <SettingsFooter :settings="settings" :defaults="defaults" @update="update" />
          </StTabPane>
          <StTabPane value="advanced" label="高级" icon="adjustments">
            <SettingsAdvanced :settings="settings" :defaults="defaults" @update="update" />
          </StTabPane>
          <StTabPane value="mail" label="邮件" icon="mail">
            <SettingsMail :settings="settings" :defaults="defaults" @update="update" />
          </StTabPane>
        </StTabs>
      </main>
    </template>

    <StToast placement="bottom-right" />
  </div>
</template>

<style scoped>
/* ===== 页面骨架（原先靠 Tailwind 工具类，现收敛为 scoped CSS + 令牌） =====
   与前台 src/App.vue 的做法一致：页面级几何不新增组件，只落 scoped CSS。 */
.admin-app {
  min-height: calc(100vh - var(--sta-topbar-height, 0px));
  background-color: var(--background);
  color: var(--foreground);
  font-family: var(--font-sans);
}

/* ===== 加载 / 错误态 ===== */
.admin-app__state {
  display: flex;
  min-height: 60vh;
  align-items: center;
  justify-content: center;
  padding: 24px;
  color: var(--secondary);
}

.admin-app__error-card {
  display: flex;
  width: 100%;
  max-width: 26rem;
  flex-direction: column;
  align-items: center;
  gap: 12px;
  padding: 32px;
  border: 1px solid var(--border);
  border-radius: var(--radius-large);
  background-color: var(--card);
  text-align: center;
  box-shadow: var(--shadow-small);
}

.admin-app__error-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 44px;
  height: 44px;
  border-radius: var(--radius-full);
  background-color: color-mix(in srgb, var(--danger) 12%, transparent);
  color: var(--danger);
}

.admin-app__error-title {
  margin: 0;
  font-size: 16px;
  font-weight: 600;
}

.admin-app__error-desc {
  margin: 0;
  font-size: 13px;
  color: var(--secondary);
  overflow-wrap: anywhere;
}

/* ===== 顶栏：品牌 + 保存 ===== */
.admin-app__bar {
  position: sticky;
  top: var(--wp-admin--admin-bar--height, 0);
  z-index: 20;
  border-bottom: 1px solid var(--border);
  background-color: color-mix(in srgb, var(--card) 82%, transparent);
  backdrop-filter: blur(12px);
}

/* 开启后台美化后，设置页渲染在 shell 内容区内。此时页面滚动容器在桌面端是
   <html>、在窄屏（≤782px，WP 让 body 变成滚动容器）是 <body>；而窄屏 body 的
   padding-top 已经把内容让到顶栏之下，所以 sticky 偏移要分开写：桌面顶到顶栏
   下沿，窄屏用 0。两者都写 48 会在窄屏再叠加一次，工具条下移并盖住内容。 */
body.sta-theme-active .admin-app__bar {
  top: var(--sta-topbar-height, 0px);
}

@media (max-width: 782px) {
  body.sta-theme-active .admin-app__bar {
    top: 0;
  }
}

/* 顶栏内容与下方内容列同宽同轴，避免左右贴边造成两套对齐 */
.admin-app__bar-inner {
  display: flex;
  min-height: 60px;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  width: 100%;
  max-width: 46rem;
  margin: 0 auto;
  padding: 0 20px;
}

.admin-app__brand {
  display: flex;
  min-width: 0;
  align-items: center;
  gap: 10px;
}

.admin-app__brand-mark {
  display: inline-flex;
  flex: none;
  align-items: center;
  justify-content: center;
  width: 30px;
  height: 30px;
  border-radius: var(--radius-medium);
  background-color: color-mix(in srgb, var(--primary) 10%, transparent);
  color: var(--primary);
}

.admin-app__brand-text {
  display: flex;
  min-width: 0;
  flex-direction: column;
  line-height: 1.3;
}

.admin-app__brand-name {
  font-size: 14px;
  font-weight: 600;
}

.admin-app__brand-sub {
  font-size: 12px;
  color: var(--secondary);
}

.admin-app__bar-actions {
  display: flex;
  flex: none;
  align-items: center;
  gap: 12px;
}

.admin-app__dirty {
  font-size: 12px;
  color: var(--secondary);
}

/* ===== 内容列 ===== */
.admin-app__main {
  width: 100%;
  max-width: 46rem;
  margin: 0 auto;
  padding: 24px 20px 72px;
}

/* 7 个标签页在窄列里需要允许换行，否则会被压缩到只剩图标 */
.admin-app__tabs :deep(.st-tabs__list) {
  flex-wrap: wrap;
}

/* sm (37.5rem / 600px)：顶栏与内容区使用更大内边距 */
@media (min-width: 37.5rem) {
  .admin-app__bar-inner {
    padding: 0 32px;
  }

  .admin-app__main {
    padding: 32px 32px 96px;
  }
}
</style>
