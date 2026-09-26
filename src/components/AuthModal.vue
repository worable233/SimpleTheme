<script setup lang="ts">
/**
 * AuthModal — 登录 / 注册 / 找回密码 / 重置密码弹窗
 *
 * 外壳与控件已迁移到 src/ui（StModal / StInput / StButton / StCheckbox /
 * StAlert / StSpinner / StStack）；本文件只保留业务逻辑与表单拼装。
 * 焦点陷阱 / ESC / 遮罩点击 / 滚动锁定 / aria-modal 由 StModal 内部的
 * reka-ui Dialog 负责，本组件不再自建。
 */
import { ref, onMounted, computed } from 'vue'
import { useRoute } from 'vue-router'
import {
  apiLogin,
  apiRegister,
  apiLostPassword,
  apiValidateResetKey,
  apiResetPassword,
} from '@/lib/api-auth'
import { useAuth } from '@/composables/useAuth'
import { getThemeConfig } from '@/lib/theme-config'
import { StAlert, StButton, StCheckbox, StInput, StModal, StSpinner, StStack } from '@/ui'
import AppIcon from '@/components/AppIcon.vue'

const emit = defineEmits<{ close: [] }>()

/** StModal 的受控开关；关闭动作统一由 @close 上抛给父组件（原语义不变） */
const showModal = ref(true)

const { setLoggedIn } = useAuth()

/** 从 REST 错误响应中提取可读消息 */
function apiErrorMessage(e: unknown): string | undefined {
  return (e as { response?: { data?: { message?: string } } })?.response?.data?.message
}

// 弹窗当前显示的标签页
type AuthTab = 'login' | 'register' | 'lostpassword' | 'resetpassword' | 'message'
const activeTab = ref<AuthTab>('login')
const tabTitle = computed(() => {
  const map: Record<AuthTab, string> = {
    login: '登录',
    register: '注册',
    lostpassword: '找回密码',
    resetpassword: '重置密码',
    message: '',
  }
  return map[activeTab.value]
})

const canRegister = computed(() => {
  return getThemeConfig()?.features?.registration !== false
})

// 表单数据
const log = ref('')
const pwd = ref('')
const showLoginPassword = ref(false)
const rememberme = ref(true)
const regUser = ref('')
const regEmail = ref('')
const lostUser = ref('')
const resetPass1 = ref('')
const resetPass2 = ref('')
const showResetPassword = ref(false)
const showResetPasswordConfirm = ref(false)

// 状态
const loading = ref(false)
const errorMsg = ref('')
const successMsg = ref('')

// 密码重置 — 从 URL 参数读取 key 和 login
const route = useRoute()
const resetKey = ref('')
const resetLogin = ref('')

onMounted(() => {
  // 检查 URL 中是否有重置密码参数
  if (route.query.action === 'resetpass' && route.query.key && route.query.login) {
    resetKey.value = route.query.key as string
    resetLogin.value = route.query.login as string
    activeTab.value = 'resetpassword'
    // 验证密钥
    validateResetKey()
    // 清除 URL 参数
    const url = new URL(window.location.href)
    url.searchParams.delete('action')
    url.searchParams.delete('key')
    url.searchParams.delete('login')
    window.history.replaceState({}, '', url.toString())
  }

  // 检查是否有 checkemail 状态
  if (route.query.checkemail) {
    if (route.query.checkemail === 'confirm') {
      activeTab.value = 'message'
      successMsg.value = '密码重置邮件已发送，请检查您的邮箱中的确认链接。'
    } else if (route.query.checkemail === 'registered') {
      activeTab.value = 'message'
      successMsg.value = '注册成功！请检查您的邮箱中的确认邮件，然后登录。'
    }
  }

  // 检查 loggedout 状态
  if (route.query.loggedout) {
    activeTab.value = 'message'
    successMsg.value = '您已成功退出登录。'
  }
})

// 验证重置密钥
async function validateResetKey() {
  loading.value = true
  errorMsg.value = ''
  try {
    const result = await apiValidateResetKey(resetKey.value, resetLogin.value)
    if (!result.success) {
      errorMsg.value = result.message || '重置链接无效或已过期'
      activeTab.value = 'lostpassword'
    }
  } catch (e) {
    errorMsg.value = apiErrorMessage(e) || '验证失败，请重新请求重置链接'
    activeTab.value = 'lostpassword'
  } finally {
    loading.value = false
  }
}

// 登录
async function handleLogin() {
  if (!log.value || !pwd.value) {
    errorMsg.value = '请输入用户名和密码'
    return
  }
  loading.value = true
  errorMsg.value = ''
  successMsg.value = ''
  try {
    const result = await apiLogin(log.value, pwd.value, rememberme.value)
    if (result.success && result.user && result.rest_nonce) {
      setLoggedIn(
        {
          id: result.user.id,
          displayName: result.user.display_name,
          avatar: result.user.avatar,
          email: result.user.email,
          url: result.user.url,
        },
        result.rest_nonce,
        result.redirect_to || '/wp-admin/',
        result.logout_url,
      )
      emit('close')
    } else {
      errorMsg.value = result.message || '登录失败'
    }
  } catch (e) {
    errorMsg.value = apiErrorMessage(e) || '网络错误，请稍后重试'
  } finally {
    loading.value = false
  }
}

// 注册
async function handleRegister() {
  if (!regUser.value || !regEmail.value) {
    errorMsg.value = '请填写用户名和邮箱'
    return
  }
  loading.value = true
  errorMsg.value = ''
  successMsg.value = ''
  try {
    const result = await apiRegister(regUser.value, regEmail.value)
    if (result.success) {
      activeTab.value = 'message'
      successMsg.value = result.message || '注册成功！请检查您的邮箱。'
    } else {
      errorMsg.value = result.message || '注册失败'
    }
  } catch (e) {
    errorMsg.value = apiErrorMessage(e) || '网络错误，请稍后重试'
  } finally {
    loading.value = false
  }
}

// 找回密码
async function handleLostPassword() {
  if (!lostUser.value) {
    errorMsg.value = '请输入用户名或邮箱地址'
    return
  }
  loading.value = true
  errorMsg.value = ''
  successMsg.value = ''
  try {
    const result = await apiLostPassword(lostUser.value)
    if (result.success) {
      activeTab.value = 'message'
      successMsg.value = result.message || '密码重置邮件已发送，请检查您的邮箱。'
    } else {
      errorMsg.value = result.message || '请求失败'
    }
  } catch (e) {
    errorMsg.value = apiErrorMessage(e) || '网络错误，请稍后重试'
  } finally {
    loading.value = false
  }
}

// 重置密码
async function handleResetPassword() {
  if (!resetPass1.value || !resetPass2.value) {
    errorMsg.value = '请填写新密码'
    return
  }
  if (resetPass1.value !== resetPass2.value) {
    errorMsg.value = '两次输入的密码不一致'
    return
  }
  if (resetPass1.value.length < 6) {
    errorMsg.value = '密码长度至少为 6 个字符'
    return
  }
  loading.value = true
  errorMsg.value = ''
  successMsg.value = ''
  try {
    const result = await apiResetPassword(
      resetKey.value,
      resetLogin.value,
      resetPass1.value,
      resetPass2.value,
    )
    if (result.success) {
      activeTab.value = 'message'
      successMsg.value = result.message || '密码已重置，请使用新密码登录。'
    } else {
      errorMsg.value = result.message || '密码重置失败'
    }
  } catch (e) {
    errorMsg.value = apiErrorMessage(e) || '网络错误，请稍后重试'
  } finally {
    loading.value = false
  }
}

// 切换标签
function switchTo(tab: AuthTab) {
  activeTab.value = tab
  errorMsg.value = ''
  successMsg.value = ''
}
</script>

<template>
  <StModal v-model:show="showModal" :title="tabTitle" size="small" @close="emit('close')">
    <StStack gap="4">
      <!-- 错误 / 成功消息 -->
      <StAlert v-if="errorMsg" type="error">{{ errorMsg }}</StAlert>
      <StAlert v-if="successMsg" type="success">{{ successMsg }}</StAlert>

      <!-- 加载中 -->
      <div v-if="loading && activeTab === 'resetpassword' && !errorMsg" class="auth-status">
        <span class="auth-status__spinner"><StSpinner :size="32" /></span>
        <p class="auth-status__text">正在验证...</p>
      </div>

      <!-- ===== 消息页面（注册成功/发送邮件成功） ===== -->
      <div v-if="activeTab === 'message'" class="auth-message">
        <div class="auth-message__icon">
          <AppIcon name="send" :size="48" />
        </div>
        <p class="auth-message__text">{{ successMsg }}</p>
        <StButton type="primary" block @click="emit('close')">知道了</StButton>
      </div>

      <!-- ===== 登录表单 ===== -->
      <form v-if="activeTab === 'login'" @submit.prevent="handleLogin">
        <StStack gap="4">
          <label class="auth-field">
            <span class="auth-field__label">用户名或邮箱</span>
            <StInput
              v-model="log"
              type="text"
              autocomplete="username"
              placeholder="输入用户名或邮箱"
            />
          </label>
          <label class="auth-field">
            <span class="auth-field__label">密码</span>
            <StInput
              v-model="pwd"
              :type="showLoginPassword ? 'text' : 'password'"
              autocomplete="current-password"
              placeholder="输入密码"
            >
              <template #suffix>
                <StButton
                  quaternary
                  circle
                  size="small"
                  :aria-label="showLoginPassword ? '隐藏密码' : '显示密码'"
                  :title="showLoginPassword ? '隐藏密码' : '显示密码'"
                  @click="showLoginPassword = !showLoginPassword"
                >
                  <AppIcon :name="showLoginPassword ? 'eye-off' : 'eye'" :size="18" />
                </StButton>
              </template>
            </StInput>
          </label>
          <StCheckbox v-model="rememberme">记住我</StCheckbox>
          <StButton type="primary" block attr-type="submit" :disabled="loading">
            {{ loading ? '登录中...' : '登录' }}
          </StButton>
          <div class="auth-links">
            <a href="#" class="auth-link" @click.prevent="switchTo('lostpassword')">忘记密码？</a>
            <a
              v-if="canRegister"
              href="#"
              class="auth-link"
              @click.prevent="switchTo('register')"
              >注册账号</a
            >
          </div>
        </StStack>
      </form>

      <!-- ===== 注册表单 ===== -->
      <form v-if="activeTab === 'register'" @submit.prevent="handleRegister">
        <StStack gap="4">
          <label class="auth-field">
            <span class="auth-field__label">用户名</span>
            <StInput
              v-model="regUser"
              type="text"
              autocomplete="username"
              placeholder="输入用户名"
            />
          </label>
          <label class="auth-field">
            <span class="auth-field__label">邮箱</span>
            <StInput v-model="regEmail" type="email" autocomplete="email" placeholder="输入邮箱" />
          </label>
          <StButton type="primary" block attr-type="submit" :disabled="loading">
            {{ loading ? '注册中...' : '注册' }}
          </StButton>
          <div class="auth-links">
            <span class="auth-links__hint">已有账号？</span>
            <a href="#" class="auth-link" @click.prevent="switchTo('login')">登录</a>
          </div>
        </StStack>
      </form>

      <!-- ===== 找回密码表单 ===== -->
      <form v-if="activeTab === 'lostpassword'" @submit.prevent="handleLostPassword">
        <StStack gap="4">
          <p class="auth-hint">输入您的用户名或邮箱地址，我们将向您发送重置密码的链接。</p>
          <label class="auth-field">
            <span class="auth-field__label">用户名或邮箱</span>
            <StInput
              v-model="lostUser"
              type="text"
              autocomplete="username"
              placeholder="输入用户名或邮箱"
            />
          </label>
          <StButton type="primary" block attr-type="submit" :disabled="loading">
            {{ loading ? '发送中...' : '发送重置邮件' }}
          </StButton>
          <div class="auth-links">
            <a href="#" class="auth-link" @click.prevent="switchTo('login')">返回登录</a>
          </div>
        </StStack>
      </form>

      <!-- ===== 重置密码表单 ===== -->
      <form v-if="activeTab === 'resetpassword'" @submit.prevent="handleResetPassword">
        <StStack gap="4">
          <p class="auth-hint">请设置您的新密码。</p>
          <label class="auth-field">
            <span class="auth-field__label">新密码</span>
            <StInput
              v-model="resetPass1"
              :type="showResetPassword ? 'text' : 'password'"
              autocomplete="new-password"
              placeholder="输入新密码"
            >
              <template #suffix>
                <StButton
                  quaternary
                  circle
                  size="small"
                  :aria-label="showResetPassword ? '隐藏密码' : '显示密码'"
                  :title="showResetPassword ? '隐藏密码' : '显示密码'"
                  @click="showResetPassword = !showResetPassword"
                >
                  <AppIcon :name="showResetPassword ? 'eye-off' : 'eye'" :size="18" />
                </StButton>
              </template>
            </StInput>
          </label>
          <label class="auth-field">
            <span class="auth-field__label">确认新密码</span>
            <StInput
              v-model="resetPass2"
              :type="showResetPasswordConfirm ? 'text' : 'password'"
              autocomplete="new-password"
              placeholder="再次输入新密码"
            >
              <template #suffix>
                <StButton
                  quaternary
                  circle
                  size="small"
                  :aria-label="showResetPasswordConfirm ? '隐藏密码' : '显示密码'"
                  :title="showResetPasswordConfirm ? '隐藏密码' : '显示密码'"
                  @click="showResetPasswordConfirm = !showResetPasswordConfirm"
                >
                  <AppIcon :name="showResetPasswordConfirm ? 'eye-off' : 'eye'" :size="18" />
                </StButton>
              </template>
            </StInput>
          </label>
          <StButton type="primary" block attr-type="submit" :disabled="loading">
            {{ loading ? '重置中...' : '重置密码' }}
          </StButton>
          <div class="auth-links">
            <a href="#" class="auth-link" @click.prevent="switchTo('login')">返回登录</a>
          </div>
        </StStack>
      </form>
    </StStack>
  </StModal>
</template>

<style scoped>
/* ==================== 表单 ==================== */
label.auth-field {
  display: block;
}

.auth-field__label {
  display: block;
  margin-bottom: 6px;
  color: var(--foreground);
  font-size: 13px;
  font-weight: 500;
  line-height: 1.5;
}

.auth-hint {
  margin: 0;
  color: var(--muted-foreground);
  font-size: 13px;
  line-height: 1.5;
}

.auth-links {
  display: flex;
  justify-content: space-between;
  font-size: 13px;
}

.auth-links__hint {
  color: var(--muted-foreground);
}

.auth-link {
  color: var(--primary);
  text-decoration: none;
  transition: opacity 0.15s ease;
}

.auth-link:hover {
  text-decoration: underline;
  opacity: 0.7;
}

/* ==================== 状态区 ==================== */
.auth-status {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
  padding: 24px 0;
  color: var(--muted-foreground);
  text-align: center;
}

.auth-status__spinner {
  color: var(--primary);
}

.auth-status__text {
  margin: 0;
}

/* ==================== 消息页 ==================== */
.auth-message {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 16px;
  padding: 24px 0;
  text-align: center;
}

.auth-message__icon {
  color: var(--primary);
}

.auth-message__text {
  margin: 0;
  color: var(--foreground);
  font-size: 14px;
  line-height: 1.6;
}
</style>
