<script setup lang="ts">
import { computed, ref, onMounted } from 'vue'
import AppCard from './AppCard.vue'
import AppToggle from './AppToggle.vue'

const props = defineProps<{
  settings: Record<string, unknown>
  defaults: Record<string, unknown>
}>()

const emit = defineEmits<{
  (e: 'update', key: string, value: unknown): void
  (e: 'toast', message: string, type: 'success' | 'error' | 'warning'): void
}>()

const smtpEnabled = computed(() => !!props.settings.smtp_enabled)

const testEmail = ref('')
const testStatus = ref<'idle' | 'sending' | 'success' | 'error'>('idle')
const testMessage = ref('')

async function sendTest() {
  if (!testEmail.value) return
  testStatus.value = 'sending'
  testMessage.value = ''
  try {
    const config = window.SimpleThemeConfig
    const url = config?.routes?.smtp_test || '/wp-json/simple-theme/v1/smtp-test'
    const nonce = config?.restNonce || ''
    const headers: Record<string, string> = {
      'Content-Type': 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
    }
    if (nonce) headers['X-WP-Nonce'] = nonce

    const res = await fetch(url, {
      method: 'POST',
      credentials: 'same-origin',
      headers,
      body: JSON.stringify({ to: testEmail.value }),
    })
    const data = await res.json()
    if (data.success) {
      testStatus.value = 'success'
      emit('toast', data.message || '测试邮件已发送，请检查收件箱。', 'success')
    } else {
      testStatus.value = 'error'
      const debug = data.debug ? ` (${data.debug})` : ''
      testMessage.value = debug
      emit('toast', (data.message || '发送失败') + debug, 'error')
      if (data.timeout_hint) {
        emit('toast', data.timeout_hint, 'warning')
      } else if (data.ssl_ca_hint) {
        emit('toast', data.ssl_ca_hint, 'warning')
      }
    }
  } catch (e) {
    testStatus.value = 'error'
    testMessage.value = ''
    emit('toast', '请求失败: ' + (e instanceof Error ? e.message : String(e)), 'error')
  }
}

// Queue state
const queueStats = ref<Record<string, number>>({ pending: 0, processing: 0, sent: 0, failed: 0 })
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
const queueItems = ref<QueueItem[]>([])
const queueLoading = ref(false)

async function fetchQueue() {
  const config = window.SimpleThemeConfig
  const url = config?.routes?.mail_queue || '/wp-json/simple-theme/v1/mail-queue'
  const nonce = config?.restNonce || ''
  const headers: Record<string, string> = { 'X-Requested-With': 'XMLHttpRequest' }
  if (nonce) headers['X-WP-Nonce'] = nonce

  queueLoading.value = true
  try {
    const res = await fetch(url, { credentials: 'same-origin', headers })
    if (res.ok) {
      const data = await res.json()
      queueStats.value = data.stats || {}
      queueItems.value = data.items || []
    }
  } catch {
    emit('toast', '获取队列数据失败', 'error')
  } finally {
    queueLoading.value = false
  }
}

async function retryMail(id: number) {
  const config = window.SimpleThemeConfig
  const url = config?.routes?.mail_queue || '/wp-json/simple-theme/v1/mail-queue'
  const nonce = config?.restNonce || ''
  const headers: Record<string, string> = {
    'Content-Type': 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
  }
  if (nonce) headers['X-WP-Nonce'] = nonce

  try {
    const res = await fetch(`${url}/retry/${id}`, { method: 'POST', credentials: 'same-origin', headers })
    if (res.ok) {
      emit('toast', '已加入重试队列', 'success')
      await fetchQueue()
    } else {
      emit('toast', '重试失败', 'error')
    }
  } catch {
    emit('toast', '重试请求失败', 'error')
  }
}

async function clearQueue() {
  const config = window.SimpleThemeConfig
  const url = config?.routes?.mail_queue || '/wp-json/simple-theme/v1/mail-queue'
  const nonce = config?.restNonce || ''
  const headers: Record<string, string> = {
    'Content-Type': 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
  }
  if (nonce) headers['X-WP-Nonce'] = nonce

  try {
    const res = await fetch(`${url}/clear`, { method: 'POST', credentials: 'same-origin', headers })
    if (res.ok) {
      const data = await res.json()
      queueStats.value = data.stats || {}
      queueItems.value = []
      emit('toast', '已完成记录已清空', 'success')
    } else {
      emit('toast', '清空失败', 'error')
    }
  } catch {
    emit('toast', '清空请求失败', 'error')
  }
}

onMounted(() => {
  fetchQueue()
})

const statusLabels: Record<string, string> = {
  pending: '待发送',
  processing: '发送中',
  sent: '已发送',
  failed: '失败',
}

const statusColors: Record<string, string> = {
  pending: '#f59e0b',
  processing: '#3b82f6',
  sent: '#16a34a',
  failed: '#dc2626',
}
</script>

<template>
  <!-- Enable SMTP -->
  <AppCard title="SMTP 服务" description="配置 SMTP 发送邮件，用于 WordPress 的密码重置、通知等功能。">
    <div class="xh-field xh-field--compact">
      <AppToggle
        :modelValue="!!settings.smtp_enabled"
        label="启用 SMTP"
        @update:modelValue="emit('update', 'smtp_enabled', $event)"
      />
    </div>
  </AppCard>

  <!-- SMTP Settings -->
  <div class="smtp-sections" :class="{ 'smtp-sections--disabled': !smtpEnabled }">
    <AppCard title="服务器设置" description="填写 SMTP 服务器连接信息。">
      <div class="xh-grid">
        <div class="xh-field xh-field--compact">
          <label class="xh-field__label">SMTP 服务器</label>
          <input
            type="text"
            class="xh-input"
            placeholder="smtp.example.com"
            :value="(settings.smtp_host as string) || ''"
            @input="emit('update', 'smtp_host', ($event.target as HTMLInputElement).value)"
          />
        </div>
        <div class="xh-field xh-field--compact">
          <label class="xh-field__label">端口</label>
          <input
            type="number"
            class="xh-input xh-input--number"
            min="1" max="65535"
            :value="(settings.smtp_port as number) || 587"
            @input="emit('update', 'smtp_port', Number(($event.target as HTMLInputElement).value))"
          />
        </div>
        <div class="xh-field xh-field--compact">
          <label class="xh-field__label">加密方式</label>
          <select
            class="xh-select"
            :value="(settings.smtp_encryption as string) || 'tls'"
            @change="emit('update', 'smtp_encryption', ($event.target as HTMLSelectElement).value)"
          >
            <option value="none">无</option>
            <option value="ssl">SSL</option>
            <option value="tls">TLS</option>
          </select>
        </div>
        <div class="xh-field xh-field--compact">
          <label class="xh-field__label">连接超时（秒）</label>
          <input
            type="number"
            class="xh-input xh-input--number"
            min="1" max="120"
            :value="(settings.smtp_timeout as number) ?? 30"
            @input="emit('update', 'smtp_timeout', Number(($event.target as HTMLInputElement).value))"
          />
          <p class="xh-field__desc">PHPMailer 等待服务器响应的最大秒数，范围 1-120，SSL 连接建议 30 秒以上。</p>
        </div>
      </div>
    </AppCard>

    <!-- Authentication -->
    <AppCard title="身份验证" description="配置 SMTP 登录凭据（大多数服务商需要）。">
      <div class="xh-field xh-field--compact">
        <AppToggle
          :modelValue="!!settings.smtp_auth"
          label="启用身份验证"
          @update:modelValue="emit('update', 'smtp_auth', $event)"
        />
      </div>
      <template v-if="settings.smtp_auth">
        <div class="xh-grid" style="margin-top: 16px;">
          <div class="xh-field xh-field--compact">
            <label class="xh-field__label">用户名</label>
            <input
              type="text"
              class="xh-input"
              :value="(settings.smtp_username as string) || ''"
              @input="emit('update', 'smtp_username', ($event.target as HTMLInputElement).value)"
            />
          </div>
          <div class="xh-field xh-field--compact">
            <label class="xh-field__label">密码</label>
            <input
              type="password"
              class="xh-input"
              placeholder="输入新密码以修改"
              :value="(settings.smtp_password as string) === '********' ? '' : (settings.smtp_password as string)"
              @input="emit('update', 'smtp_password', ($event.target as HTMLInputElement).value)"
            />
            <p class="xh-field__desc" v-if="(settings.smtp_password as string) === '********'">已设置密码，输入新值将替换。</p>
          </div>
        </div>
      </template>
    </AppCard>

    <!-- From -->
    <AppCard title="发件人信息" description="自定义发件人地址和名称（可选）。">
      <div class="xh-grid">
        <div class="xh-field xh-field--compact">
          <label class="xh-field__label">发件人邮箱</label>
          <input
            type="email"
            class="xh-input"
            placeholder="noreply@example.com"
            :value="(settings.smtp_from_email as string) || ''"
            @input="emit('update', 'smtp_from_email', ($event.target as HTMLInputElement).value)"
          />
        </div>
        <div class="xh-field xh-field--compact">
          <label class="xh-field__label">发件人名称</label>
          <input
            type="text"
            class="xh-input"
            :placeholder="(defaults.smtp_from_name as string) || '站点名称'"
            :value="(settings.smtp_from_name as string) || ''"
            @input="emit('update', 'smtp_from_name', ($event.target as HTMLInputElement).value)"
          />
        </div>
      </div>
    </AppCard>

    <!-- Test Email -->
    <AppCard title="测试邮件" description="保存设置后，发送一封测试邮件验证 SMTP 配置是否生效。">
      <div class="xh-field">
        <label class="xh-field__label">收件地址</label>
        <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
          <input
            type="email"
            class="xh-input"
            style="flex: 1; min-width: 200px;"
            placeholder="输入您的邮箱地址"
            v-model="testEmail"
            @keyup.enter="sendTest"
          />
          <button
            class="xh-btn"
            :class="{ 'xh-btn--primary': testStatus !== 'sending' }"
            :disabled="!testEmail || testStatus === 'sending'"
            @click="sendTest"
          >
            {{ testStatus === 'sending' ? '发送中...' : '发送测试' }}
          </button>
        </div>
        <p
          v-if="testMessage && testStatus === 'error'"
          class="xh-field__desc"
          style="color: var(--xh-error, #dc2626); margin-top: 8px; word-break: break-word;"
        >
          错误详情: {{ testMessage }}
        </p>
      </div>
    </AppCard>

    <!-- Queue Settings -->
    <AppCard title="队列设置" description="启用后，邮件将进入队列逐个发送，避免阻塞页面响应。失败时会根据配置自动重试。">
      <div class="xh-field xh-field--compact">
        <AppToggle
          :modelValue="!!settings.smtp_queue_enabled"
          label="启用邮件队列"
          @update:modelValue="emit('update', 'smtp_queue_enabled', $event)"
        />
      </div>
      <div class="xh-grid" style="margin-top: 16px;">
        <div class="xh-field xh-field--compact">
          <label class="xh-field__label">失败重试次数</label>
          <input
            type="number"
            class="xh-input xh-input--number"
            min="0" max="20"
            :value="(settings.smtp_queue_retry_count as number) ?? 3"
            @input="emit('update', 'smtp_queue_retry_count', Number(($event.target as HTMLInputElement).value))"
          />
          <p class="xh-field__desc">最多重试次数（0 = 不重试），实际重试次数不会超过配置值。</p>
        </div>
        <div class="xh-field xh-field--compact">
          <label class="xh-field__label">重试间隔（秒）</label>
          <input
            type="number"
            class="xh-input xh-input--number"
            min="60" max="3600" step="30"
            :value="(settings.smtp_queue_retry_interval as number) ?? 300"
            @input="emit('update', 'smtp_queue_retry_interval', Number(($event.target as HTMLInputElement).value))"
          />
          <p class="xh-field__desc">失败后等待多少秒再次发送。范围 60-3600 秒。</p>
        </div>
      </div>
    </AppCard>
  </div>

  <!-- Queue Status -->
  <AppCard title="邮件队列" description="查看邮件队列中的发送状态和记录。">
    <div class="queue-stats">
      <div class="queue-stat" v-for="(label, key) in { pending: '待发送', processing: '发送中', sent: '已发送', failed: '失败' }" :key="key">
        <span class="queue-stat__value" :style="{ color: statusColors[key] }">{{ queueStats[key] ?? 0 }}</span>
        <span class="queue-stat__label">{{ label }}</span>
      </div>
    </div>

    <div class="queue-actions">
      <button class="xh-btn" @click="fetchQueue" :disabled="queueLoading">{{ queueLoading ? '刷新中...' : '刷新' }}</button>
      <button class="xh-btn" @click="clearQueue" v-if="queueItems.length > 0">清空已完成记录</button>
    </div>

    <div v-if="queueItems.length === 0 && !queueLoading" class="queue-empty">
      暂无邮件队列记录。
    </div>

    <div v-else class="queue-list">
      <div v-for="item in queueItems" :key="item.id" class="queue-item">
        <div class="queue-item__main">
          <div class="queue-item__to">{{ item.to_email }}</div>
          <div class="queue-item__subject">{{ item.subject }}</div>
          <div class="queue-item__meta">
            <span class="queue-item__status" :style="{ background: statusColors[item.status] || '#999' }">
              {{ statusLabels[item.status] || item.status }}
            </span>
            <span v-if="item.retry_count > 0">重试 {{ item.retry_count }}/{{ item.max_retries }}</span>
            <span>{{ item.created_at }}</span>
          </div>
          <div v-if="item.error_message" class="queue-item__error">{{ item.error_message }}</div>
        </div>
        <button
          v-if="item.status === 'failed'"
          class="xh-btn xh-btn--small"
          @click="retryMail(item.id)"
        >
          重试
        </button>
      </div>
    </div>
  </AppCard>
</template>

<style scoped>
.smtp-sections--disabled {
  opacity: 0.4;
  pointer-events: none;
  user-select: none;
}

.queue-stats {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 12px;
  margin-bottom: 20px;
}

.queue-stat {
  text-align: center;
  padding: 16px 8px;
  background: var(--xh-primary-light, #f5f5f5);
  border-radius: var(--xh-radius-sm, 8px);
}

.queue-stat__value {
  display: block;
  font-size: 28px;
  font-weight: 700;
  line-height: 1.2;
}

.queue-stat__label {
  display: block;
  font-size: 12px;
  color: var(--xh-text-secondary, #666);
  margin-top: 4px;
}

.queue-actions {
  display: flex;
  gap: 8px;
  margin-bottom: 16px;
}

.queue-empty {
  text-align: center;
  color: var(--xh-text-secondary, #888);
  padding: 24px 0;
  font-size: 14px;
}

.queue-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
  max-height: 400px;
  overflow-y: auto;
}

.queue-item {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 8px;
  padding: 12px;
  background: var(--xh-primary-light, #f9f9f9);
  border-radius: var(--xh-radius-sm, 6px);
}

.queue-item__main {
  min-width: 0;
  flex: 1;
}

.queue-item__to {
  font-weight: 600;
  font-size: 13px;
  color: var(--xh-text, #333);
  word-break: break-all;
}

.queue-item__subject {
  font-size: 12px;
  color: var(--xh-text-secondary, #666);
  margin-top: 2px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.queue-item__meta {
  display: flex;
  gap: 8px;
  align-items: center;
  margin-top: 6px;
  font-size: 11px;
  color: var(--xh-text-secondary, #888);
}

.queue-item__status {
  display: inline-block;
  padding: 1px 8px;
  border-radius: 10px;
  color: #fff;
  font-size: 11px;
  font-weight: 500;
  line-height: 1.6;
}

.queue-item__error {
  margin-top: 4px;
  font-size: 11px;
  color: var(--xh-error, #dc2626);
  word-break: break-word;
}
</style>
