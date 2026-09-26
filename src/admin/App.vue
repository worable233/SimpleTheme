<script setup lang="ts">
import { onMounted, onUnmounted, ref } from 'vue'
import { fetchSettings, saveSettings } from './api'
import type { AdminSettings } from './api'
import {
  StButton,
  StCard,
  StColorPicker,
  StFormItem,
  StGrid,
  StIcon,
  StInput,
  StNumberInput,
  StSelect,
  StSpinner,
  StStack,
  StSwitch,
  StTabPane,
  StTabs,
  StToast,
  useToast,
} from '@/ui'

const settings = ref<AdminSettings>({})
const defaults = ref<AdminSettings>({})
const activeTab = ref('appearance')
const loading = ref(true)
const saving = ref(false)
const dirty = ref(false)
const saved = ref(false)
const error = ref('')
const toast = useToast()

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
            <StStack :gap="5">
              <StCard title="基础样式" subtitle="前台与设置页共用的主题变量。">
                <StGrid :cols="3" :gap="4">
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
                </StGrid>
              </StCard>

              <StCard title="布局" subtitle="页面容器的宽度约束（单位 px）。">
                <StGrid :cols="2" :gap="4">
                  <StFormItem label="容器最大宽度">
                    <StNumberInput
                      :model-value="number('container_max_width', 1500)"
                      :min="960"
                      :max="2000"
                      :step="10"
                      aria-label="容器最大宽度"
                      @update:model-value="update('container_max_width', $event ?? 1500)"
                    />
                  </StFormItem>
                  <StFormItem label="文章最大宽度">
                    <StNumberInput
                      :model-value="number('article_max_width', 900)"
                      :min="680"
                      :max="1200"
                      :step="10"
                      aria-label="文章最大宽度"
                      @update:model-value="update('article_max_width', $event ?? 900)"
                    />
                  </StFormItem>
                </StGrid>
              </StCard>

              <StCard title="配色" subtitle="浅色与深色两套语义色板。">
                <StGrid :cols="2" :gap="6">
                  <div
                    v-for="[title, colors] in colorGroups"
                    :key="title"
                    class="admin-app__palette"
                  >
                    <h3 class="admin-app__palette-title">{{ title }}</h3>
                    <StStack :gap="3">
                      <StFormItem
                        v-for="[key, label] in colors"
                        :key="key"
                        :label="label"
                        label-placement="left"
                        label-width="64px"
                      >
                        <StColorPicker
                          :model-value="colorText(key, String(defaults[key] || '#ffffff'))"
                          :aria-label="label"
                          @update:model-value="update(key, $event)"
                        />
                      </StFormItem>
                    </StStack>
                  </div>
                </StGrid>
              </StCard>
            </StStack>
          </StTabPane>

          <StTabPane value="home" label="首页" icon="home">
            <StStack :gap="5">
              <StCard title="首页内容" subtitle="控制首页区块的标题、数量与显示状态。">
                <StStack :gap="5">
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
                  </StGrid>
                  <StGrid :cols="3" :gap="4">
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
            </StStack>
          </StTabPane>

          <StTabPane value="advanced" label="高级" icon="settings">
            <StStack :gap="5">
              <StCard title="外链跳转" subtitle="文章外链确认页的自动跳转行为。">
                <StGrid :cols="3" :gap="4">
                  <StFormItem label="自动跳转">
                    <StSelect
                      :model-value="
                        checked('external_redirect_enabled', true) ? 'enabled' : 'disabled'
                      "
                      :options="redirectEnabledOptions"
                      aria-label="自动跳转"
                      @update:model-value="
                        update('external_redirect_enabled', $event === 'enabled')
                      "
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
                  <StFormItem label="目标窗口">
                    <StSelect
                      :model-value="text('external_redirect_target', '_self')"
                      :options="redirectTargetOptions"
                      aria-label="目标窗口"
                      @update:model-value="update('external_redirect_target', $event)"
                    />
                  </StFormItem>
                </StGrid>
              </StCard>

              <StCard title="评论与通知" subtitle="评论表单与 Cookie 同意条的行为。">
                <StStack :gap="5">
                  <StGrid :cols="2" :gap="4">
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
              </StCard>
            </StStack>
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
  min-height: 100vh;
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

/* StTabs 根节点会继承父级 scope 标记，可直接命中 */
.admin-app__tabs {
  width: 100%;
}

.admin-app__palette {
  min-width: 0;
}

.admin-app__palette-title {
  margin: 0 0 12px;
  font-size: 13px;
  font-weight: 600;
  line-height: 1.4;
  color: var(--foreground);
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
