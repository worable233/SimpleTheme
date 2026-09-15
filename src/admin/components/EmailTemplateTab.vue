<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { StCard } from '@/ui'

interface Template {
  id: string
  name: string
  description: string
}

const props = defineProps<{
  settings: Record<string, unknown>
  defaults: Record<string, unknown>
}>()

const emit = defineEmits<{
  (e: 'update', key: string, value: unknown): void
  (e: 'toast', message: string, type: 'success' | 'error' | 'warning'): void
}>()

const templates = ref<Template[]>([])
const currentTemplate = ref('')
const previewHtml = ref('')
const previewLoading = ref(false)

async function fetchTemplates() {
  const config = window.SimpleThemeConfig
  const url = config?.routes?.email_templates || '/wp-json/simple-theme/v1/email-templates'
  const nonce = config?.restNonce || ''
  const headers: Record<string, string> = { 'X-Requested-With': 'XMLHttpRequest' }
  if (nonce) headers['X-WP-Nonce'] = nonce

  try {
    const res = await fetch(url, { credentials: 'same-origin', headers })
    if (res.ok) {
      const data = await res.json()
      templates.value = data.templates || []
      currentTemplate.value = (props.settings.email_template as string) || data.current || 'simple'
    }
  } catch {
    emit('toast', '获取模板列表失败', 'error')
  }
}

async function loadPreview(id: string) {
  const config = window.SimpleThemeConfig
  const url = config?.routes?.email_preview || '/wp-json/simple-theme/v1/email-template-preview'
  const nonce = config?.restNonce || ''
  const headers: Record<string, string> = { 'X-Requested-With': 'XMLHttpRequest' }
  if (nonce) headers['X-WP-Nonce'] = nonce

  previewLoading.value = true
  try {
    const res = await fetch(`${url}?template=${id}`, { credentials: 'same-origin', headers })
    if (res.ok) {
      const data = await res.json()
      previewHtml.value = data.html || ''
    }
  } catch {
    emit('toast', '加载预览失败', 'error')
  } finally {
    previewLoading.value = false
  }
}

function selectTemplate(id: string) {
  currentTemplate.value = id
  emit('update', 'email_template', id)
  loadPreview(id)
}

onMounted(async () => {
  await fetchTemplates()
  if (currentTemplate.value) {
    loadPreview(currentTemplate.value)
  }
})
</script>

<template>
  <StCard
    title="邮件模板"
    subtitle="选择邮件模板样式，所有通过 WordPress 发出的邮件（评论回复通知、密码重置等）将使用选中模板渲染。"
  >
    <div class="email-template-grid">
      <div
        v-for="tpl in templates"
        :key="tpl.id"
        class="email-template-card"
        :class="{ 'email-template-card--active': currentTemplate === tpl.id }"
        @click="selectTemplate(tpl.id)"
      >
        <div class="email-template-card__selector">
          <div
            class="email-template-card__radio"
            :class="{ 'email-template-card__radio--checked': currentTemplate === tpl.id }"
          >
            <div v-if="currentTemplate === tpl.id" class="email-template-card__dot"></div>
          </div>
        </div>
        <div class="email-template-card__info">
          <div class="email-template-card__name">{{ tpl.name }}</div>
          <div class="email-template-card__desc">{{ tpl.description }}</div>
        </div>
      </div>
    </div>
  </StCard>

  <!-- Preview -->
  <StCard
    title="预览"
    :subtitle="'当前预览: ' + (templates.find((t) => t.id === currentTemplate)?.name || '')"
  >
    <div v-if="previewLoading" class="email-preview-loading">加载预览中...</div>
    <div v-else-if="previewHtml" class="email-preview-wrapper">
      <iframe
        class="email-preview-frame"
        :srcdoc="previewHtml"
        title="邮件预览"
        sandbox="allow-same-origin"
      ></iframe>
    </div>
    <div v-else class="email-preview-empty">请选择一个模板查看预览</div>
  </StCard>
</template>

<style scoped>
.email-template-grid {
  display: flex;
  flex-direction: column;
  gap: 12px;
  max-width: 560px;
}

.email-template-card {
  display: flex;
  align-items: flex-start;
  gap: 16px;
  padding: 20px;
  border: 2px solid var(--border);
  border-radius: var(--radius-large);
  cursor: pointer;
  transition: all 0.2s ease;
  background: var(--card);
}
.email-template-card:hover {
  border-color: var(--primary);
}
.email-template-card--active {
  border-color: var(--primary);
  background: var(--muted);
}

.email-template-card__selector {
  padding-top: 2px;
  flex-shrink: 0;
}
.email-template-card__radio {
  width: 20px;
  height: 20px;
  border-radius: 50%;
  border: 2px solid var(--border);
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease;
}
.email-template-card__radio--checked {
  border-color: var(--primary);
}
.email-template-card__dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  background: var(--primary);
}

.email-template-card__info {
  flex: 1;
  min-width: 0;
}
.email-template-card__name {
  font-size: 15px;
  font-weight: 600;
  color: var(--foreground);
  margin-bottom: 4px;
}
.email-template-card__desc {
  font-size: 13px;
  color: var(--muted-foreground);
  line-height: 1.5;
}

.email-preview-loading,
.email-preview-empty {
  text-align: center;
  padding: 48px 16px;
  color: var(--muted-foreground);
  font-size: 14px;
}

.email-preview-wrapper {
  border: 1px solid var(--border);
  border-radius: var(--radius-medium);
  overflow: hidden;
}

.email-preview-frame {
  width: 100%;
  height: 520px;
  border: none;
  display: block;
  background: var(--card);
}
</style>
