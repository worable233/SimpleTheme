<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { fetchSettings, saveSettings } from './api'
import type { AdminSettings } from './api'
import {
  StButton,
  StCard,
  StColorPicker,
  StFormItem,
  StGrid,
  StInput,
  StNumberInput,
  StSelect,
  StStack,
  StSwitch,
  StToast,
  useToast,
} from '@/ui'

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
const toast = useToast()

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

const ipLocationApiOptions = [
  { value: 'xinyew', label: '鑫烨（百度渠道）' },
  { value: 'ip.sb', label: 'IP.SB（海外）' },
  { value: 'ip-api.com', label: 'ip-api.com（海外）' },
]

const ipLocationCacheOptions = [
  { value: 'enabled', label: '开启' },
  { value: 'disabled', label: '关闭' },
]

function value(key: string, fallback: unknown = '') {
  return settings.value[key] ?? defaults.value[key] ?? fallback
}

function text(key: string, fallback = '') {
  return String(value(key, fallback))
}

/** 旧 AdminColorPicker 有 fallback prop（空值时显示兜底色），StColorPicker 没有。
 *  这里由调用方兜出非空色值，保证「未设置」不会出现在本该有默认色的位置。 */
function colorText(key: string, fallback: string) {
  return text(key, fallback) || fallback
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
    setTimeout(() => { saved.value = false }, 2200)
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
    <div v-if="loading" class="admin-app__loading">
      正在加载设置...
    </div>

    <div v-else-if="error" class="admin-app__error">
      <section class="admin-app__error-card">
        <h1 class="admin-app__error-title">设置加载失败</h1>
        <p class="admin-app__error-desc">{{ error }}</p>
        <StButton type="primary" @click="load">重试</StButton>
      </section>
    </div>

    <div v-else class="admin-app__layout">
      <aside class="admin-app__aside">
        <div class="admin-app__brand">
          <strong class="admin-app__brand-name">Simple Theme</strong>
          <span class="admin-app__brand-sub">主题设置</span>
        </div>
        <nav class="admin-app__tabs">
          <StButton
            v-for="tab in tabs"
            :key="tab.key"
            :type="activeTab === tab.key ? 'primary' : 'default'"
            :secondary="activeTab !== tab.key"
            block
            @click="activeTab = tab.key"
          >
            {{ tab.label }}
          </StButton>
        </nav>
      </aside>

      <main class="admin-app__main">
        <header class="admin-app__topbar">
          <div>
            <h1 class="admin-app__topbar-title">{{ activeLabel }}</h1>
            <p class="admin-app__topbar-desc">配置主题的展示与交互行为</p>
          </div>
          <StButton
            :type="saved ? 'success' : 'primary'"
            :disabled="saving || !dirty"
            @click="save"
          >
            {{ saving ? '保存中...' : saved ? '已保存' : '保存设置' }}
          </StButton>
        </header>

        <div class="admin-app__content">
          <template v-if="activeTab === 'appearance'">
            <StCard title="基础样式" subtitle="前台和设置页面共用这套主题变量。">
              <StGrid :cols="2" :gap="4">
                <StFormItem label="主色">
                  <StColorPicker
                    :model-value="colorText('primary_color', '#333333')"
                    aria-label="主色"
                    @update:model-value="update('primary_color', $event)"
                  />
                </StFormItem>
                <StFormItem label="圆角">
                  <StSelect
                    :model-value="text('radius', 'medium')"
                    :options="radiusOptions"
                    aria-label="圆角"
                    @update:model-value="update('radius', $event)"
                  />
                </StFormItem>
                <StFormItem label="阴影">
                  <StSelect
                    :model-value="text('shadow', 'small')"
                    :options="shadowOptions"
                    aria-label="阴影"
                    @update:model-value="update('shadow', $event)"
                  />
                </StFormItem>
                <StFormItem label="容器最大宽度（px）">
                  <StNumberInput
                    :model-value="number('container_max_width', 1500)"
                    :min="960"
                    :max="2000"
                    :step="10"
                    aria-label="容器最大宽度（px）"
                    @update:model-value="update('container_max_width', $event ?? 1500)"
                  />
                </StFormItem>
                <StFormItem label="文章最大宽度（px）">
                  <StNumberInput
                    :model-value="number('article_max_width', 900)"
                    :min="680"
                    :max="1200"
                    :step="10"
                    aria-label="文章最大宽度（px）"
                    @update:model-value="update('article_max_width', $event ?? 900)"
                  />
                </StFormItem>
              </StGrid>
            </StCard>

            <StCard v-for="[title, colors] in colorGroups" :key="title" :title="title">
              <StGrid :cols="3" :gap="4">
                <StFormItem
                  v-for="[key, label] in colors"
                  :key="key"
                  :label="label"
                  label-placement="left"
                >
                  <StColorPicker
                    :model-value="colorText(key, String(defaults[key] || '#ffffff'))"
                    :aria-label="label"
                    @update:model-value="update(key, $event)"
                  />
                </StFormItem>
              </StGrid>
            </StCard>
          </template>

          <template v-else-if="activeTab === 'home'">
            <StCard title="首页内容" subtitle="控制首页区块标题、数量和显示状态。">
              <StStack :gap="4">
                <StSwitch
                  :model-value="checked('show_shuoshuo_section', true)"
                  @update:model-value="update('show_shuoshuo_section', $event)"
                >
                  显示说说板块
                </StSwitch>
                <StGrid :cols="2" :gap="4">
                  <StFormItem v-for="[key, label] in homeTextFields" :key="key" :label="label">
                    <StInput
                      :model-value="text(key)"
                      :aria-label="label"
                      @update:model-value="update(key, $event)"
                    />
                  </StFormItem>
                  <StFormItem
                    v-for="[key, label, min, max] in homeNumberFields"
                    :key="key"
                    :label="label"
                  >
                    <StNumberInput
                      :model-value="number(key, Number(defaults[key] || min))"
                      :min="min"
                      :max="max"
                      :aria-label="label"
                      @update:model-value="update(key, $event ?? Number(defaults[key] || min))"
                    />
                  </StFormItem>
                </StGrid>
              </StStack>
            </StCard>
          </template>

          <template v-else>
            <StCard title="外链跳转" subtitle="控制文章外链确认页的自动跳转行为。">
              <StStack :gap="4">
                <StGrid :cols="2" :gap="4">
                  <StFormItem label="自动跳转">
                    <StSelect
                      :model-value="checked('external_redirect_enabled', true) ? 'enabled' : 'disabled'"
                      :options="redirectEnabledOptions"
                      aria-label="自动跳转"
                      @update:model-value="update('external_redirect_enabled', $event === 'enabled')"
                    />
                  </StFormItem>
                  <StFormItem label="等待时间（秒）">
                    <StNumberInput
                      :model-value="number('external_redirect_delay', 5)"
                      :min="1"
                      :max="30"
                      aria-label="等待时间（秒）"
                      @update:model-value="update('external_redirect_delay', $event ?? 5)"
                    />
                  </StFormItem>
                </StGrid>
                <StFormItem label="目标窗口">
                  <StSelect
                    :model-value="text('external_redirect_target', '_self')"
                    :options="redirectTargetOptions"
                    aria-label="目标窗口"
                    @update:model-value="update('external_redirect_target', $event)"
                  />
                </StFormItem>
              </StStack>
            </StCard>

            <StCard title="通知与评论">
              <StStack :gap="4">
                <StGrid :cols="2" :gap="3">
                  <StSwitch
                    :model-value="checked('cookie_consent_enabled')"
                    @update:model-value="update('cookie_consent_enabled', $event)"
                  >
                    启用 Cookie 同意横幅
                  </StSwitch>
                  <StSwitch
                    :model-value="checked('comment_captcha_enabled')"
                    @update:model-value="update('comment_captcha_enabled', $event)"
                  >
                    启用评论验证码
                  </StSwitch>
                  <StSwitch
                    :model-value="checked('comment_show_private', true)"
                    @update:model-value="update('comment_show_private', $event)"
                  >
                    允许私密评论
                  </StSwitch>
                  <StSwitch
                    :model-value="checked('comment_show_markdown', true)"
                    @update:model-value="update('comment_show_markdown', $event)"
                  >
                    支持 Markdown
                  </StSwitch>
                </StGrid>
                <StFormItem label="Cookie 提示文字">
                  <StInput
                    :model-value="text('cookie_consent_message')"
                    aria-label="Cookie 提示文字"
                    @update:model-value="update('cookie_consent_message', $event)"
                  />
                </StFormItem>
              </StStack>
            </StCard>

            <StCard
              title="IP 归属地"
              subtitle="评论提交时 WordPress 已记录访客 IP，前台显示时按此处配置解析并缓存。"
            >
              <StStack :gap="4">
                <StGrid :cols="2" :gap="4">
                  <StFormItem label="解析接口">
                    <StSelect
                      :model-value="text('ip_location_api', 'xinyew')"
                      :options="ipLocationApiOptions"
                      aria-label="解析接口"
                      @update:model-value="update('ip_location_api', $event)"
                    />
                  </StFormItem>
                  <StFormItem label="结果缓存">
                    <StSelect
                      :model-value="checked('ip_location_cache', true) ? 'enabled' : 'disabled'"
                      :options="ipLocationCacheOptions"
                      aria-label="结果缓存"
                      @update:model-value="update('ip_location_cache', $event === 'enabled')"
                    />
                  </StFormItem>
                </StGrid>
              </StStack>
            </StCard>
          </template>
        </div>
      </main>
    </div>

    <StToast placement="bottom-right" />
  </div>
</template>

<style scoped>
/* ===== 页面骨架（原先靠 Tailwind 工具类，现收敛为 scoped CSS + 令牌） =====
   与前台 src/App.vue 的做法一致：页面级几何不新增组件，只落 scoped CSS。 */
.admin-app {
  min-height: 100vh;
  background-color: var(--background);
  color: var(--foreground);
  font-family: var(--font-sans);
}

.admin-app__loading {
  display: flex;
  min-height: 100vh;
  align-items: center;
  justify-content: center;
  color: var(--secondary);
}

.admin-app__error {
  display: flex;
  min-height: 100vh;
  align-items: center;
  justify-content: center;
  padding: 24px;
}

.admin-app__error-card {
  width: 100%;
  max-width: 28rem; /* max-w-md */
  padding: 32px;
  border: 1px solid var(--border);
  border-radius: var(--radius-large);
  background-color: var(--card);
  text-align: center;
  box-shadow: var(--shadow-small);
}

.admin-app__error-title {
  margin: 0;
  font-size: 18px;
  font-weight: 600;
}

.admin-app__error-desc {
  margin: 12px 0 20px;
  font-size: 13px;
  color: var(--secondary);
}

.admin-app__layout {
  display: flex;
  flex-direction: column;
  min-height: 100vh;
}

.admin-app__aside {
  width: 100%;
  flex-shrink: 0;
  border-bottom: 1px solid var(--border);
  background-color: var(--card);
}

.admin-app__brand {
  padding: 20px;
  border-bottom: 1px solid var(--border);
}

.admin-app__brand-name {
  display: block;
  font-size: 13px;
}

.admin-app__brand-sub {
  font-size: 12px;
  color: var(--secondary);
}

.admin-app__tabs {
  display: flex;
  gap: 4px;
  padding: 12px;
  overflow-x: auto;
}

.admin-app__main {
  flex: 1;
  min-width: 0;
}

.admin-app__topbar {
  position: sticky;
  top: 46px;
  z-index: 10;
  display: flex;
  min-height: 64px;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  padding: 12px 20px;
  border-bottom: 1px solid var(--border);
  background-color: color-mix(in srgb, var(--background) 90%, transparent);
  backdrop-filter: blur(24px);
}

.admin-app__topbar-title {
  margin: 0;
  font-size: 16px;
  font-weight: 600;
}

.admin-app__topbar-desc {
  margin: 0;
  font-size: 12px;
  color: var(--secondary);
}

.admin-app__content {
  display: flex;
  flex-direction: column;
  gap: 20px; /* space-y-5 */
  max-width: 64rem; /* max-w-5xl */
  margin: 0 auto;
  padding: 20px;
}

/* lg (62.5rem / 1000px)：侧栏转为固定宽纵向栏，整体横向排布 */
@media (min-width: 62.5rem) {
  .admin-app__layout {
    flex-direction: row;
  }

  .admin-app__aside {
    width: 15rem; /* w-60 */
    border-right: 1px solid var(--border);
    border-bottom: none;
  }

  .admin-app__tabs {
    display: block;
  }
}

/* 783px：顶栏贴合 WordPress 管理栏高度 */
@media (min-width: 48.9375rem) {
  .admin-app__topbar {
    top: 32px;
  }
}

/* sm (37.5rem / 600px)：顶栏与内容区使用更大内边距 */
@media (min-width: 37.5rem) {
  .admin-app__topbar {
    padding-left: 32px;
    padding-right: 32px;
  }

  .admin-app__content {
    padding: 32px;
  }
}
</style>
