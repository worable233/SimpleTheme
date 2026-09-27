<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import {
  StButton,
  StCard,
  StEmpty,
  StFormItem,
  StGrid,
  StIcon,
  StInput,
  StNumberInput,
  StSelect,
  StSpinner,
  StStack,
  StSwitch,
  StTag,
  useToast,
} from '@/ui'
import { useAdminFields } from '../useAdminFields'
import type { AdminSettings } from '../api'

const props = defineProps<{
  settings: AdminSettings
  defaults: AdminSettings
}>()

const emit = defineEmits<{
  (e: 'update', key: string, value: unknown): void
}>()

const fields = useAdminFields(
  () => props.settings,
  () => props.defaults,
)

const toast = useToast()

const config = window.SimpleThemeConfig
function routeUrl(
  key: 'smtp_test' | 'mail_queue' | 'email_templates' | 'email_preview',
  fallback: string,
) {
  return config?.routes?.[key] || fallback
}
function restHeaders(json = false): Record<string, string> {
  const headers: Record<string, string> = { 'X-Requested-With': 'XMLHttpRequest' }
  if (json) headers['Content-Type'] = 'application/json'
  if (config?.restNonce) headers['X-WP-Nonce'] = config.restNonce
  return headers
}

const smtpEnabled = computed(() => fields.checked('smtp_enabled'))
const passwordSet = computed(() => fields.text('smtp_password') === '********')

const encryptionOptions = [
  { value: 'none', label: '无' },
  { value: 'ssl', label: 'SSL' },
  { value: 'tls', label: 'TLS' },
]

/* ===== 邮件模板 ===== */
interface MailTemplate {
  id: string
  name: string
  description: string
}
const templates = ref<MailTemplate[]>([])
const currentTemplate = ref('')
const previewHtml = ref('')
const previewLoading = ref(false)

async function fetchTemplates() {
  try {
    const res = await fetch(
      routeUrl('email_templates', '/wp-json/simple-theme/v1/email-templates'),
      {
        credentials: 'same-origin',
        headers: restHeaders(),
      },
    )
    if (!res.ok) return
    const data = await res.json()
    templates.value = data.templates || []
    currentTemplate.value = fields.text('email_template', data.current || 'simple')
  } catch {
    toast.error('获取模板列表失败')
  }
}

async function loadPreview(id: string) {
  if (!id) return
  previewLoading.value = true
  try {
    const res = await fetch(
      `${routeUrl('email_preview', '/wp-json/simple-theme/v1/email-template-preview')}?template=${encodeURIComponent(id)}`,
      { credentials: 'same-origin', headers: restHeaders() },
    )
    if (res.ok) {
      const data = await res.json()
      previewHtml.value = data.html || ''
    }
  } catch {
    toast.error('加载预览失败')
  } finally {
    previewLoading.value = false
  }
}

function selectTemplate(id: string) {
  currentTemplate.value = id
  emit('update', 'email_template', id)
  void loadPreview(id)
}

/* ===== 测试邮件 ===== */
const testEmail = ref('')
const testStatus = ref<'idle' | 'sending' | 'success' | 'error'>('idle')
const testMessage = ref('')

async function sendTest() {
  if (!testEmail.value) return
  testStatus.value = 'sending'
  testMessage.value = ''
  try {
    const res = await fetch(routeUrl('smtp_test', '/wp-json/simple-theme/v1/smtp-test'), {
      method: 'POST',
      credentials: 'same-origin',
      headers: restHeaders(true),
      body: JSON.stringify({ to: testEmail.value }),
    })
    const data = await res.json()
    if (data.success) {
      testStatus.value = 'success'
      toast.success(data.message || '测试邮件已发送，请检查收件箱。')
    } else {
      testStatus.value = 'error'
      const debug = data.debug ? ` (${data.debug})` : ''
      testMessage.value = debug
      toast.error((data.message || '发送失败') + debug)
      if (data.timeout_hint) toast.warning(data.timeout_hint)
      else if (data.ssl_ca_hint) toast.warning(data.ssl_ca_hint)
      else if (data.from_hint) toast.warning(data.from_hint)
    }
  } catch (err) {
    testStatus.value = 'error'
    testMessage.value = ''
    toast.error('请求失败: ' + (err instanceof Error ? err.message : String(err)))
  }
}

/* ===== 邮件队列 ===== */
interface QueueItem {
  id: number
  to_email: string
  subject: string
  status: string
  retry_count: number
  max_retries: number
  created_at: string
  error_message?: string
}
const queueStats = ref<Record<string, number>>({ pending: 0, processing: 0, sent: 0, failed: 0 })
const queueItems = ref<QueueItem[]>([])
const queueLoading = ref(false)

const queueStatLabels: Array<[string, string]> = [
  ['pending', '待发送'],
  ['processing', '发送中'],
  ['sent', '已发送'],
  ['failed', '失败'],
]
const statusLabels: Record<string, string> = {
  pending: '待发送',
  processing: '发送中',
  sent: '已发送',
  failed: '失败',
}
const statusType: Record<string, 'warning' | 'primary' | 'success' | 'error'> = {
  pending: 'warning',
  processing: 'primary',
  sent: 'success',
  failed: 'error',
}

async function fetchQueue() {
  queueLoading.value = true
  try {
    const res = await fetch(routeUrl('mail_queue', '/wp-json/simple-theme/v1/mail-queue'), {
      credentials: 'same-origin',
      headers: restHeaders(),
    })
    if (res.ok) {
      const data = await res.json()
      queueStats.value = data.stats || {}
      queueItems.value = data.items || []
    }
  } catch {
    toast.error('获取队列数据失败')
  } finally {
    queueLoading.value = false
  }
}

async function retryMail(id: number) {
  try {
    const res = await fetch(
      `${routeUrl('mail_queue', '/wp-json/simple-theme/v1/mail-queue')}/retry/${id}`,
      {
        method: 'POST',
        credentials: 'same-origin',
        headers: restHeaders(true),
      },
    )
    if (res.ok) {
      toast.success('已加入重试队列')
      await fetchQueue()
    } else {
      toast.error('重试失败')
    }
  } catch {
    toast.error('重试请求失败')
  }
}

async function clearQueue() {
  try {
    const res = await fetch(
      `${routeUrl('mail_queue', '/wp-json/simple-theme/v1/mail-queue')}/clear`,
      {
        method: 'POST',
        credentials: 'same-origin',
        headers: restHeaders(true),
      },
    )
    if (res.ok) {
      await fetchQueue()
      toast.success('已完成记录已清空')
    } else {
      toast.error('清空失败')
    }
  } catch {
    toast.error('清空请求失败')
  }
}

onMounted(async () => {
  await fetchTemplates()
  if (currentTemplate.value) void loadPreview(currentTemplate.value)
  void fetchQueue()
})
</script>

<template>
  <StStack :gap="5">
    <StCard title="邮件模板" subtitle="所有通过 WordPress 发出的邮件将使用选中模板渲染。">
      <div class="mail-templates">
        <button
          v-for="tpl in templates"
          :key="tpl.id"
          type="button"
          class="mail-template"
          :class="{ 'is-active': currentTemplate === tpl.id }"
          @click="selectTemplate(tpl.id)"
        >
          <span class="mail-template__radio" :class="{ 'is-checked': currentTemplate === tpl.id }">
            <span v-if="currentTemplate === tpl.id" class="mail-template__dot" />
          </span>
          <span class="mail-template__info">
            <span class="mail-template__name">{{ tpl.name }}</span>
            <span class="mail-template__desc">{{ tpl.description }}</span>
          </span>
        </button>
      </div>
    </StCard>

    <StCard
      title="邮件预览"
      :subtitle="
        templates.find((t) => t.id === currentTemplate)?.name
          ? `当前预览：${templates.find((t) => t.id === currentTemplate)?.name}`
          : '选择一个模板查看预览'
      "
    >
      <div v-if="previewLoading" class="mail-preview__state">
        <StSpinner size="medium" description="加载预览中…" />
      </div>
      <div v-else-if="previewHtml" class="mail-preview__frame">
        <iframe :srcdoc="previewHtml" title="邮件预览" sandbox="allow-same-origin" />
      </div>
      <StEmpty v-else description="请选择一个模板查看预览" />
    </StCard>

    <StCard title="SMTP 服务" subtitle="配置 SMTP 发送邮件，用于密码重置、通知等功能。">
      <StSwitch
        :model-value="smtpEnabled"
        @update:model-value="emit('update', 'smtp_enabled', $event)"
      >
        启用 SMTP
      </StSwitch>
    </StCard>

    <div class="mail-smtp" :class="{ 'is-disabled': !smtpEnabled }">
      <StStack :gap="5">
        <StCard title="服务器设置" subtitle="填写 SMTP 服务器连接信息。">
          <StGrid :cols="2" :gap="4">
            <StFormItem label="SMTP 服务器">
              <StInput
                :model-value="fields.text('smtp_host')"
                placeholder="smtp.example.com"
                aria-label="SMTP 服务器"
                @update:model-value="emit('update', 'smtp_host', $event)"
              />
            </StFormItem>
            <StFormItem label="端口">
              <StNumberInput
                :model-value="fields.number('smtp_port', 587)"
                :min="1"
                :max="65535"
                aria-label="SMTP 端口"
                @update:model-value="emit('update', 'smtp_port', $event ?? 587)"
              />
            </StFormItem>
            <StFormItem label="加密方式">
              <StSelect
                :model-value="fields.text('smtp_encryption', 'tls')"
                :options="encryptionOptions"
                aria-label="加密方式"
                @update:model-value="emit('update', 'smtp_encryption', $event)"
              />
            </StFormItem>
            <StFormItem
              label="连接超时（秒）"
              description="PHPMailer 等待响应的最大秒数，SSL 建议 30 秒以上。"
            >
              <StNumberInput
                :model-value="fields.number('smtp_timeout', 30)"
                :min="1"
                :max="120"
                aria-label="连接超时"
                @update:model-value="emit('update', 'smtp_timeout', $event ?? 30)"
              />
            </StFormItem>
          </StGrid>
        </StCard>

        <StCard title="身份验证" subtitle="大多数服务商需要登录凭据。">
          <StStack :gap="4">
            <StSwitch
              :model-value="fields.checked('smtp_auth', true)"
              @update:model-value="emit('update', 'smtp_auth', $event)"
            >
              启用身份验证
            </StSwitch>
            <StGrid v-if="fields.checked('smtp_auth', true)" :cols="2" :gap="4">
              <StFormItem label="用户名">
                <StInput
                  :model-value="fields.text('smtp_username')"
                  aria-label="SMTP 用户名"
                  @update:model-value="emit('update', 'smtp_username', $event)"
                />
              </StFormItem>
              <StFormItem
                label="密码"
                :description="passwordSet ? '已设置密码，输入新值将替换。' : undefined"
              >
                <StInput
                  :model-value="passwordSet ? '' : fields.text('smtp_password')"
                  type="password"
                  placeholder="输入新密码以修改"
                  aria-label="SMTP 密码"
                  @update:model-value="emit('update', 'smtp_password', $event)"
                />
              </StFormItem>
            </StGrid>
          </StStack>
        </StCard>

        <StCard title="发件人信息" subtitle="自定义发件人地址和名称（可选）。">
          <StGrid :cols="2" :gap="4">
            <StFormItem label="发件人邮箱">
              <StInput
                :model-value="fields.text('smtp_from_email')"
                placeholder="noreply@example.com"
                aria-label="发件人邮箱"
                @update:model-value="emit('update', 'smtp_from_email', $event)"
              />
            </StFormItem>
            <StFormItem label="发件人名称">
              <StInput
                :model-value="fields.text('smtp_from_name')"
                placeholder="站点名称"
                aria-label="发件人名称"
                @update:model-value="emit('update', 'smtp_from_name', $event)"
              />
            </StFormItem>
          </StGrid>
        </StCard>

        <StCard title="测试邮件" subtitle="保存设置后，发送一封测试邮件验证配置是否生效。">
          <StStack :gap="3">
            <StFormItem label="收件地址">
              <div class="mail-test">
                <StInput
                  v-model="testEmail"
                  placeholder="输入您的邮箱地址"
                  aria-label="测试收件地址"
                  @keyup.enter="sendTest"
                />
                <StButton
                  :type="testStatus === 'success' ? 'success' : 'primary'"
                  :loading="testStatus === 'sending'"
                  :disabled="!testEmail"
                  @click="sendTest"
                >
                  {{ testStatus === 'success' ? '已发送' : '发送测试' }}
                </StButton>
              </div>
            </StFormItem>
            <p v-if="testMessage" class="mail-test__error">错误详情：{{ testMessage }}</p>
          </StStack>
        </StCard>

        <StCard
          title="队列设置"
          subtitle="启用后邮件进入队列逐个发送，避免阻塞页面响应；失败会按配置自动重试。"
        >
          <StStack :gap="4">
            <StSwitch
              :model-value="fields.checked('smtp_queue_enabled', true)"
              @update:model-value="emit('update', 'smtp_queue_enabled', $event)"
            >
              启用邮件队列
            </StSwitch>
            <StGrid :cols="2" :gap="4">
              <StFormItem label="失败重试次数" description="最多重试次数（0 = 不重试）。">
                <StNumberInput
                  :model-value="fields.number('smtp_queue_retry_count', 3)"
                  :min="0"
                  :max="20"
                  aria-label="失败重试次数"
                  @update:model-value="emit('update', 'smtp_queue_retry_count', $event ?? 3)"
                />
              </StFormItem>
              <StFormItem label="重试间隔（秒）" description="范围 60-3600 秒。">
                <StNumberInput
                  :model-value="fields.number('smtp_queue_retry_interval', 300)"
                  :min="60"
                  :max="3600"
                  :step="30"
                  aria-label="重试间隔"
                  @update:model-value="emit('update', 'smtp_queue_retry_interval', $event ?? 300)"
                />
              </StFormItem>
            </StGrid>
          </StStack>
        </StCard>
      </StStack>
    </div>

    <StCard title="邮件队列" subtitle="查看队列中的发送状态与记录。">
      <StStack :gap="4">
        <StGrid :cols="4" :gap="3">
          <div v-for="[key, label] in queueStatLabels" :key="key" class="mail-queue__stat">
            <span class="mail-queue__value">{{ queueStats[key] ?? 0 }}</span>
            <span class="mail-queue__label">{{ label }}</span>
          </div>
        </StGrid>

        <StStack direction="horizontal" gap="2" wrap>
          <StButton size="small" :loading="queueLoading" @click="fetchQueue">
            <template #icon><StIcon name="refresh" :size="16" /></template>
            刷新
          </StButton>
          <StButton v-if="queueItems.length > 0" size="small" @click="clearQueue">
            清空已完成记录
          </StButton>
        </StStack>

        <StEmpty v-if="queueItems.length === 0 && !queueLoading" description="暂无邮件队列记录。" />

        <div v-else class="mail-queue__list">
          <div v-for="item in queueItems" :key="item.id" class="mail-queue__item">
            <div class="mail-queue__main">
              <div class="mail-queue__to">{{ item.to_email }}</div>
              <div class="mail-queue__subject">{{ item.subject }}</div>
              <div class="mail-queue__meta">
                <StTag :type="statusType[item.status] || 'default'" size="tiny" round>
                  {{ statusLabels[item.status] || item.status }}
                </StTag>
                <span v-if="item.retry_count > 0"
                  >重试 {{ item.retry_count }}/{{ item.max_retries }}</span
                >
                <span>{{ item.created_at }}</span>
              </div>
              <div v-if="item.error_message" class="mail-queue__error">
                {{ item.error_message }}
              </div>
            </div>
            <StButton v-if="item.status === 'failed'" size="tiny" @click="retryMail(item.id)">
              重试
            </StButton>
          </div>
        </div>
      </StStack>
    </StCard>
  </StStack>
</template>

<style scoped>
/* ===== 免责：SMTP 关闭时整段置灰，仍可滚动查看但不可编辑 ===== */
.mail-smtp.is-disabled {
  opacity: 0.5;
  pointer-events: none;
  user-select: none;
}

/* ===== 模板选择 ===== */
.mail-templates {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.mail-template {
  display: flex;
  align-items: flex-start;
  gap: 14px;
  padding: 16px;
  border: 1px solid var(--border);
  border-radius: var(--radius-large);
  background-color: var(--card);
  text-align: left;
  font-family: inherit;
  cursor: pointer;
  transition:
    border-color var(--transition-fast),
    background-color var(--transition-fast);
}

.mail-template:hover {
  border-color: var(--primary);
}

.mail-template.is-active {
  border-color: var(--primary);
  background-color: color-mix(in srgb, var(--primary) 6%, transparent);
}

.mail-template__radio {
  display: inline-flex;
  flex: none;
  align-items: center;
  justify-content: center;
  width: 20px;
  height: 20px;
  margin-top: 2px;
  border: 2px solid var(--border);
  border-radius: var(--radius-full);
  transition: border-color var(--transition-fast);
}

.mail-template__radio.is-checked {
  border-color: var(--primary);
}

.mail-template__dot {
  width: 10px;
  height: 10px;
  border-radius: var(--radius-full);
  background-color: var(--primary);
}

.mail-template__info {
  display: flex;
  min-width: 0;
  flex-direction: column;
  gap: 4px;
}

.mail-template__name {
  font-size: 14px;
  font-weight: 600;
  color: var(--foreground);
}

.mail-template__desc {
  font-size: 13px;
  line-height: 1.5;
  color: var(--secondary);
}

/* ===== 预览 ===== */
.mail-preview__state {
  display: flex;
  justify-content: center;
  padding: 40px 16px;
}

.mail-preview__frame {
  border: 1px solid var(--border);
  border-radius: var(--radius-medium);
  overflow: hidden;
}

.mail-preview__frame iframe {
  display: block;
  width: 100%;
  height: 520px;
  border: none;
  background-color: #fff;
}

/* ===== 测试 ===== */
.mail-test {
  display: flex;
  gap: 8px;
  align-items: center;
  flex-wrap: wrap;
}

.mail-test > :first-child {
  flex: 1;
  min-width: 200px;
}

.mail-test__error {
  margin: 0;
  font-size: 13px;
  color: var(--danger);
  word-break: break-word;
}

/* ===== 队列 ===== */
.mail-queue__stat {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 14px 8px;
  border-radius: var(--radius-medium);
  background-color: var(--accent);
}

.mail-queue__value {
  font-size: 24px;
  font-weight: 700;
  line-height: 1.2;
  color: var(--foreground);
}

.mail-queue__label {
  margin-top: 4px;
  font-size: 12px;
  color: var(--secondary);
}

.mail-queue__list {
  display: flex;
  max-height: 400px;
  flex-direction: column;
  gap: 8px;
  overflow-y: auto;
}

.mail-queue__item {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 8px;
  padding: 12px;
  border-radius: var(--radius-medium);
  background-color: var(--accent);
}

.mail-queue__main {
  min-width: 0;
  flex: 1;
}

.mail-queue__to {
  font-size: 13px;
  font-weight: 600;
  color: var(--foreground);
  word-break: break-all;
}

.mail-queue__subject {
  margin-top: 2px;
  overflow: hidden;
  font-size: 12px;
  color: var(--secondary);
  text-overflow: ellipsis;
  white-space: nowrap;
}

.mail-queue__meta {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-top: 6px;
  font-size: 11px;
  color: var(--secondary);
}

.mail-queue__error {
  margin-top: 4px;
  font-size: 11px;
  color: var(--danger);
  word-break: break-word;
}
</style>
