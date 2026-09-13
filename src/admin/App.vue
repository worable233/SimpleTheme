<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { fetchSettings, saveSettings } from './api'
import type { AdminSettings } from './api'
import AdminSelect from './components/AdminSelect.vue'
import AdminSwitch from './components/AdminSwitch.vue'
import AdminColorPicker from './components/AdminColorPicker.vue'

type Tab = { key: string; label: string }

const tabs: Tab[] = [
  { key: 'appearance', label: '外观' },
  { key: 'home', label: '首页' },
  { key: 'advanced', label: '高级' },
]

const settings = ref<AdminSettings>({})
const defaults = ref<AdminSettings>({})
const activeTab = ref('appearance')
const loading = ref(true)
const saving = ref(false)
const dirty = ref(false)
const saved = ref(false)
const error = ref('')
const toast = ref('')
let toastTimer: ReturnType<typeof setTimeout> | null = null

const activeLabel = computed(() => tabs.find((tab) => tab.key === activeTab.value)?.label || '')

const lightColors: Array<[string, string]> = [
  ['background_light', '背景色'],
  ['card_light', '卡片背景'],
  ['foreground_light', '文字颜色'],
  ['accent_light', '强调色'],
  ['border_light', '边框色'],
]
const darkColors: Array<[string, string]> = [
  ['background_dark', '背景色'],
  ['card_dark', '卡片背景'],
  ['foreground_dark', '文字颜色'],
  ['accent_dark', '强调色'],
  ['border_dark', '边框色'],
]
const colorGroups: Array<[string, Array<[string, string]>]> = [
  ['浅色模式', lightColors],
  ['深色模式', darkColors],
]

const radiusOptions = [
  { value: 'small', label: '小' },
  { value: 'medium', label: '中' },
  { value: 'large', label: '大' },
]

const shadowOptions = [
  { value: 'none', label: '无' },
  { value: 'small', label: '轻' },
  { value: 'medium', label: '中' },
  { value: 'large', label: '重' },
]

const redirectEnabledOptions = [
  { value: 'enabled', label: '开启' },
  { value: 'disabled', label: '关闭' },
]

const redirectTargetOptions = [
  { value: '_self', label: '当前窗口（_self）' },
  { value: '_blank', label: '新标签页（_blank）' },
  { value: '_parent', label: '父框架（_parent）' },
  { value: '_top', label: '整个窗口（_top）' },
]

function value(key: string, fallback: unknown = '') {
  return settings.value[key] ?? defaults.value[key] ?? fallback
}

function text(key: string, fallback = '') {
  return String(value(key, fallback))
}

function number(key: string, fallback: number) {
  const parsed = Number(value(key, fallback))
  return Number.isFinite(parsed) ? parsed : fallback
}

function checked(key: string, fallback = false) {
  return Boolean(value(key, fallback))
}

const radiusMap: Record<'small' | 'medium' | 'large', { medium: string; large: string }> = {
  small: { medium: '0.25rem', large: '0.5rem' },
  medium: { medium: '0.375rem', large: '0.75rem' },
  large: { medium: '0.625rem', large: '1rem' },
}

const shadowMap: Record<'none' | 'small' | 'medium' | 'large', { small: string; medium: string; large: string }> = {
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
  root.style.setProperty('--container-max', `${normalizePixel(data.container_max_width, 1500, 960, 2000)}px`)
  root.style.setProperty('--article-max-width', `${normalizePixel(data.article_max_width, 900, 680, 1200)}px`)

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

function syncThemeMode() {
  let theme = localStorage.getItem('theme')
  if (theme !== 'light' && theme !== 'dark') {
    theme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'
  }
  document.documentElement.setAttribute('data-theme', theme)
  document.documentElement.style.colorScheme = theme
}

const homeTextFields: Array<[string, string]> = [
  ['posts_title', '文章区块标题'],
  ['posts_subtitle', '文章区块副标题'],
  ['shuoshuo_title', '说说区块标题'],
  ['shuoshuo_subtitle', '说说区块副标题'],
]

const homeNumberFields: Array<[string, string, number, number]> = [
  ['home_post_count', '首页文章数量', 3, 20],
  ['home_shuoshuo_count', '首页说说数量', 0, 12],
  ['shuoshuo_page_size', '说说每页数量', 6, 24],
]

function update(key: string, next: unknown) {
  settings.value[key] = next
  dirty.value = true
  saved.value = false
}

function showToast(message: string) {
  toast.value = message
  if (toastTimer) clearTimeout(toastTimer)
  toastTimer = setTimeout(() => { toast.value = '' }, 2600)
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
    setTimeout(() => { saved.value = false }, 2200)
  } catch (err) {
    showToast('保存失败: ' + (err instanceof Error ? err.message : String(err)))
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
  if (toastTimer) clearTimeout(toastTimer)
})
</script>

<template>
  <div class="min-h-screen bg-background font-sans text-foreground">
    <div v-if="loading" class="flex min-h-screen items-center justify-center text-secondary">
      正在加载设置...
    </div>

    <div v-else-if="error" class="flex min-h-screen items-center justify-center p-6">
      <section class="w-full max-w-md rounded-large border border-border bg-card p-8 text-center shadow-small">
        <h1 class="m-0 text-xl font-semibold">设置加载失败</h1>
        <p class="mt-3 mb-5 text-sm text-secondary">{{ error }}</p>
        <button class="rounded-medium bg-primary px-5 py-2.5 text-sm text-primary-foreground" @click="load">
          重试
        </button>
      </section>
    </div>

    <div v-else class="flex min-h-screen flex-col lg:flex-row">
      <aside class="w-full shrink-0 border-b border-border bg-card lg:w-60 lg:border-r lg:border-b-0">
        <div class="border-b border-border px-5 py-5">
          <strong class="block text-sm">Simple Theme</strong>
          <span class="text-xs text-secondary">主题设置</span>
        </div>
        <nav class="flex gap-1 overflow-x-auto p-3 lg:block">
          <button
            v-for="tab in tabs"
            :key="tab.key"
            class="mb-1 block shrink-0 rounded-medium px-3.5 py-2.5 text-left text-sm transition-colors"
            :class="activeTab === tab.key ? 'bg-accent font-medium text-primary' : 'text-secondary hover:bg-muted hover:text-foreground'"
            @click="activeTab = tab.key"
          >
            {{ tab.label }}
          </button>
        </nav>
      </aside>

      <main class="min-w-0 flex-1">
        <header class="sticky top-[46px] z-10 flex min-h-16 items-center justify-between gap-4 border-b border-border bg-background/90 px-5 py-3 backdrop-blur-xl min-[783px]:top-[32px] sm:px-8">
          <div>
            <h1 class="m-0 text-lg font-semibold">{{ activeLabel }}</h1>
            <p class="m-0 text-xs text-secondary">配置主题的展示与交互行为</p>
          </div>
          <button
            class="shrink-0 rounded-medium px-4 py-2 text-sm font-medium transition-opacity disabled:cursor-not-allowed disabled:opacity-45"
            :class="saved ? 'bg-success text-white' : 'bg-primary text-primary-foreground'"
            :disabled="saving || !dirty"
            @click="save"
          >
            {{ saving ? '保存中...' : saved ? '已保存' : '保存设置' }}
          </button>
        </header>

        <div class="mx-auto max-w-5xl space-y-5 p-5 sm:p-8">
          <template v-if="activeTab === 'appearance'">
            <section class="rounded-large border border-border bg-card p-5 shadow-small sm:p-6">
              <h2 class="m-0 text-base font-semibold">基础样式</h2>
              <p class="mt-1 mb-5 text-sm text-secondary">前台和设置页面共用这套主题变量。</p>
              <div class="grid gap-4 sm:grid-cols-2">
                <label class="block text-sm"><span class="mb-2 block text-secondary">主色</span><AdminColorPicker :model-value="text('primary_color', '#333333')" :fallback="'#333333'" label="主色" @update:model-value="update('primary_color', $event)"></AdminColorPicker></label>
                <label class="block text-sm"><span class="mb-2 block text-secondary">圆角</span><AdminSelect :model-value="text('radius', 'medium')" :options="radiusOptions" label="圆角" @update:model-value="update('radius', $event)"></AdminSelect></label>
                <label class="block text-sm"><span class="mb-2 block text-secondary">阴影</span><AdminSelect :model-value="text('shadow', 'small')" :options="shadowOptions" label="阴影" @update:model-value="update('shadow', $event)"></AdminSelect></label>
                <label class="block text-sm"><span class="mb-2 block text-secondary">容器最大宽度（px）</span><input type="number" min="960" max="2000" step="10" class="w-full rounded-medium border border-input bg-card px-3 py-2.5 text-sm" :value="number('container_max_width', 1500)" @input="update('container_max_width', Number(($event.target as HTMLInputElement).value))"></label>
                <label class="block text-sm"><span class="mb-2 block text-secondary">文章最大宽度（px）</span><input type="number" min="680" max="1200" step="10" class="w-full rounded-medium border border-input bg-card px-3 py-2.5 text-sm" :value="number('article_max_width', 900)" @input="update('article_max_width', Number(($event.target as HTMLInputElement).value))"></label>
              </div>
            </section>

            <section v-for="[title, colors] in colorGroups" :key="title" class="rounded-large border border-border bg-card p-5 shadow-small sm:p-6">
              <h2 class="m-0 text-base font-semibold">{{ title }}</h2>
              <div class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <label v-for="[key, label] in colors" :key="key" class="flex items-center justify-between gap-3 rounded-medium border border-border p-3 text-sm">
                  <span>{{ label }}</span>
                  <AdminColorPicker class="w-40 shrink-0" :model-value="text(key, String(defaults[key] || '#ffffff'))" :fallback="String(defaults[key] || '#ffffff')" :label="label" @update:model-value="update(key, $event)"></AdminColorPicker>
                </label>
              </div>
            </section>
          </template>

          <template v-else-if="activeTab === 'home'">
            <section class="rounded-large border border-border bg-card p-5 shadow-small sm:p-6">
              <h2 class="m-0 text-base font-semibold">首页内容</h2>
              <p class="mt-1 mb-5 text-sm text-secondary">控制首页区块标题、数量和显示状态。</p>
              <div class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2"><AdminSwitch :model-value="checked('show_shuoshuo_section', true)" label="显示说说板块" @update:model-value="update('show_shuoshuo_section', $event)"></AdminSwitch></div>
                <label v-for="[key, label] in homeTextFields" :key="key" class="block text-sm"><span class="mb-2 block text-secondary">{{ label }}</span><input type="text" class="w-full rounded-medium border border-input bg-card px-3 py-2.5 text-sm" :value="text(key)" @input="update(key, ($event.target as HTMLInputElement).value)"></label>
                <label v-for="[key, label, min, max] in homeNumberFields" :key="key" class="block text-sm"><span class="mb-2 block text-secondary">{{ label }}</span><input type="number" :min="min" :max="max" class="w-full rounded-medium border border-input bg-card px-3 py-2.5 text-sm" :value="number(key, Number(defaults[key] || min))" @input="update(key, Number(($event.target as HTMLInputElement).value))"></label>
              </div>
            </section>
          </template>

          <template v-else>
            <section class="rounded-large border border-border bg-card p-5 shadow-small sm:p-6">
              <h2 class="m-0 text-base font-semibold">外链跳转</h2>
              <p class="mt-1 mb-5 text-sm text-secondary">控制文章外链确认页的自动跳转行为。</p>
              <div class="grid gap-4 sm:grid-cols-2">
                <label class="block text-sm"><span class="mb-2 block text-secondary">自动跳转</span><AdminSelect :model-value="checked('external_redirect_enabled', true) ? 'enabled' : 'disabled'" :options="redirectEnabledOptions" label="自动跳转" @update:model-value="update('external_redirect_enabled', $event === 'enabled')"></AdminSelect></label>
                <label class="block text-sm"><span class="mb-2 block text-secondary">等待时间（秒）</span><input type="number" min="1" max="30" class="w-full rounded-medium border border-input bg-card px-3 py-2.5 text-sm" :value="number('external_redirect_delay', 5)" @input="update('external_redirect_delay', Number(($event.target as HTMLInputElement).value))"></label>
                <label class="block text-sm sm:col-span-2"><span class="mb-2 block text-secondary">目标窗口</span><AdminSelect :model-value="text('external_redirect_target', '_self')" :options="redirectTargetOptions" label="目标窗口" @update:model-value="update('external_redirect_target', $event)"></AdminSelect></label>
              </div>
            </section>

            <section class="rounded-large border border-border bg-card p-5 shadow-small sm:p-6">
              <h2 class="m-0 text-base font-semibold">通知与评论</h2>
              <div class="mt-5 grid gap-3 sm:grid-cols-2">
                <AdminSwitch :model-value="checked('cookie_consent_enabled')" label="启用 Cookie 同意横幅" @update:model-value="update('cookie_consent_enabled', $event)"></AdminSwitch>
                <AdminSwitch :model-value="checked('comment_captcha_enabled')" label="启用评论验证码" @update:model-value="update('comment_captcha_enabled', $event)"></AdminSwitch>
                <AdminSwitch :model-value="checked('comment_show_private', true)" label="允许私密评论" @update:model-value="update('comment_show_private', $event)"></AdminSwitch>
                <AdminSwitch :model-value="checked('comment_show_markdown', true)" label="支持 Markdown" @update:model-value="update('comment_show_markdown', $event)"></AdminSwitch>
              </div>
              <label class="mt-4 block text-sm"><span class="mb-2 block text-secondary">Cookie 提示文字</span><input type="text" class="w-full rounded-medium border border-input bg-card px-3 py-2.5 text-sm" :value="text('cookie_consent_message')" @input="update('cookie_consent_message', ($event.target as HTMLInputElement).value)"></label>
            </section>
          </template>
        </div>
      </main>
    </div>

    <Transition name="toast">
      <div v-if="toast" class="fixed right-5 bottom-5 z-50 rounded-medium border border-border bg-card px-4 py-3 text-sm text-foreground shadow-large">
        {{ toast }}
      </div>
    </Transition>
  </div>
</template>
