<script setup lang="ts">
import { ref, watch, onMounted, computed } from 'vue'
import { useRoute } from 'vue-router'
import { apiLogin, apiRegister, apiLostPassword, apiValidateResetKey, apiResetPassword } from '@/lib/api-auth'
import { useAuth } from '@/composables/useAuth'
import { getThemeConfig } from '@/lib/theme-config'

const emit = defineEmits<{ close: [] }>()

const { setLoggedIn } = useAuth()

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
const rememberme = ref(true)
const regUser = ref('')
const regEmail = ref('')
const lostUser = ref('')
const resetPass1 = ref('')
const resetPass2 = ref('')

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
  } catch (e: any) {
    errorMsg.value = e?.response?.data?.message || '验证失败，请重新请求重置链接'
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
        },
        result.rest_nonce,
        result.redirect_to || '/wp-admin/',
      )
      emit('close')
      // 刷新页面以同步所有 cookie 和状态
      window.location.reload()
    } else {
      errorMsg.value = result.message || '登录失败'
    }
  } catch (e: any) {
    errorMsg.value = e?.response?.data?.message || '网络错误，请稍后重试'
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
  } catch (e: any) {
    errorMsg.value = e?.response?.data?.message || '网络错误，请稍后重试'
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
  } catch (e: any) {
    errorMsg.value = e?.response?.data?.message || '网络错误，请稍后重试'
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
    const result = await apiResetPassword(resetKey.value, resetLogin.value, resetPass1.value, resetPass2.value)
    if (result.success) {
      activeTab.value = 'message'
      successMsg.value = result.message || '密码已重置，请使用新密码登录。'
    } else {
      errorMsg.value = result.message || '密码重置失败'
    }
  } catch (e: any) {
    errorMsg.value = e?.response?.data?.message || '网络错误，请稍后重试'
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

// 点击背景关闭
function onBackdropClick(e: MouseEvent) {
  if ((e.target as HTMLElement).classList.contains('auth-modal__backdrop')) {
    emit('close')
  }
}

// ESC 关闭
function onKeydown(e: KeyboardEvent) {
  if (e.key === 'Escape') emit('close')
}

onMounted(() => {
  document.addEventListener('keydown', onKeydown)
})

// 打开弹窗时禁止 body 滚动
import { onUnmounted } from 'vue'
onMounted(() => {
  document.body.style.overflow = 'hidden'
})
onUnmounted(() => {
  document.body.style.overflow = ''
  document.removeEventListener('keydown', onKeydown)
})
</script>

<template>
  <Teleport to="body">
    <div class="auth-modal__backdrop" @click="onBackdropClick">
      <div class="auth-modal__container" @click.stop>
        <!-- 标题 -->
        <div class="auth-modal__header">
          <h3 class="auth-modal__title">{{ tabTitle }}</h3>
          <button class="auth-modal__close" @click="$emit('close')" aria-label="关闭">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20">
              <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
            </svg>
          </button>
        </div>

        <!-- 错误 / 成功消息 -->
        <div v-if="errorMsg" class="auth-modal__error">{{ errorMsg }}</div>
        <div v-if="successMsg" class="auth-modal__success">{{ successMsg }}</div>

        <!-- 加载中 -->
        <div v-if="loading && activeTab === 'resetpassword' && !errorMsg" class="auth-modal__loading">
          <div class="auth-modal__spinner"></div>
          <p>正在验证...</p>
        </div>

        <!-- ===== 消息页面（注册成功/发送邮件成功） ===== -->
        <div v-if="activeTab === 'message'" class="auth-modal__message">
          <div class="auth-modal__message-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="48" height="48">
              <path d="M22 2L11 13"/><path d="M22 2L15 22L11 13L2 9L22 2Z"/>
            </svg>
          </div>
          <p>{{ successMsg }}</p>
          <button class="auth-btn auth-btn--primary" @click="$emit('close')">知道了</button>
        </div>

        <!-- ===== 登录表单 ===== -->
        <form v-if="activeTab === 'login'" @submit.prevent="handleLogin" class="auth-modal__form">
          <div class="auth-field">
            <label for="auth-log">用户名或邮箱</label>
            <input id="auth-log" v-model="log" type="text" autocomplete="username" placeholder="输入用户名或邮箱" required />
          </div>
          <div class="auth-field">
            <label for="auth-pwd">密码</label>
            <input id="auth-pwd" v-model="pwd" type="password" autocomplete="current-password" placeholder="输入密码" required />
          </div>
          <div class="auth-field auth-field--checkbox">
            <label>
              <input v-model="rememberme" type="checkbox" />
              <span>记住我</span>
            </label>
          </div>
          <button type="submit" class="auth-btn auth-btn--primary" :disabled="loading">
            {{ loading ? '登录中...' : '登录' }}
          </button>
          <div class="auth-modal__links">
            <a href="#" @click.prevent="switchTo('lostpassword')">忘记密码？</a>
            <a v-if="canRegister" href="#" @click.prevent="switchTo('register')">注册账号</a>
          </div>
        </form>

        <!-- ===== 注册表单 ===== -->
        <form v-if="activeTab === 'register'" @submit.prevent="handleRegister" class="auth-modal__form">
          <div class="auth-field">
            <label for="auth-reg-user">用户名</label>
            <input id="auth-reg-user" v-model="regUser" type="text" autocomplete="username" placeholder="输入用户名" required />
          </div>
          <div class="auth-field">
            <label for="auth-reg-email">邮箱</label>
            <input id="auth-reg-email" v-model="regEmail" type="email" autocomplete="email" placeholder="输入邮箱" required />
          </div>
          <button type="submit" class="auth-btn auth-btn--primary" :disabled="loading">
            {{ loading ? '注册中...' : '注册' }}
          </button>
          <div class="auth-modal__links">
            <span>已有账号？</span>
            <a href="#" @click.prevent="switchTo('login')">登录</a>
          </div>
        </form>

        <!-- ===== 找回密码表单 ===== -->
        <form v-if="activeTab === 'lostpassword'" @submit.prevent="handleLostPassword" class="auth-modal__form">
          <p class="auth-modal__desc">输入您的用户名或邮箱地址，我们将向您发送重置密码的链接。</p>
          <div class="auth-field">
            <label for="auth-lost-user">用户名或邮箱</label>
            <input id="auth-lost-user" v-model="lostUser" type="text" autocomplete="username" placeholder="输入用户名或邮箱" required />
          </div>
          <button type="submit" class="auth-btn auth-btn--primary" :disabled="loading">
            {{ loading ? '发送中...' : '发送重置邮件' }}
          </button>
          <div class="auth-modal__links">
            <a href="#" @click.prevent="switchTo('login')">返回登录</a>
          </div>
        </form>

        <!-- ===== 重置密码表单 ===== -->
        <form v-if="activeTab === 'resetpassword'" @submit.prevent="handleResetPassword" class="auth-modal__form">
          <p class="auth-modal__desc">请设置您的新密码。</p>
          <div class="auth-field">
            <label for="auth-reset-pass1">新密码</label>
            <input id="auth-reset-pass1" v-model="resetPass1" type="password" autocomplete="new-password" placeholder="输入新密码" required minlength="6" />
          </div>
          <div class="auth-field">
            <label for="auth-reset-pass2">确认新密码</label>
            <input id="auth-reset-pass2" v-model="resetPass2" type="password" autocomplete="new-password" placeholder="再次输入新密码" required minlength="6" />
          </div>
          <button type="submit" class="auth-btn auth-btn--primary" :disabled="loading">
            {{ loading ? '重置中...' : '重置密码' }}
          </button>
          <div class="auth-modal__links">
            <a href="#" @click.prevent="switchTo('login')">返回登录</a>
          </div>
        </form>
      </div>
    </div>
  </Teleport>
</template>

<style scoped>
/* ========== Backdrop ========== */
.auth-modal__backdrop {
  position: fixed;
  inset: 0;
  z-index: 10000;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 16px;
  animation: auth-fade-in 0.2s ease;
}

@keyframes auth-fade-in {
  from { opacity: 0; }
  to { opacity: 1; }
}

/* ========== Container ========== */
.auth-modal__container {
  background: var(--card, #ffffff);
  border-radius: var(--radius-large, 12px);
  box-shadow: var(--shadow-large, 0 12px 28px rgba(0, 0, 0, 0.16));
  width: 100%;
  max-width: 420px;
  max-height: 90vh;
  overflow-y: auto;
  animation: auth-slide-up 0.25s ease;
}

@keyframes auth-slide-up {
  from { transform: translateY(20px); opacity: 0; }
  to { transform: translateY(0); opacity: 1; }
}

/* ========== Header ========== */
.auth-modal__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 20px 24px 0;
}

.auth-modal__title {
  font-size: 20px;
  font-weight: 650;
  color: var(--foreground, #333);
  margin: 0;
  letter-spacing: -0.3px;
}

.auth-modal__close {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  border-radius: 50%;
  border: none;
  background: var(--muted, #f5f5f5);
  color: var(--foreground, #333);
  cursor: pointer;
  transition: background var(--transition-fast, 0.15s ease);
}

.auth-modal__close:hover {
  background: var(--border, #e2e2e2);
}

/* ========== Messages ========== */
.auth-modal__error {
  margin: 16px 24px 0;
  padding: 10px 14px;
  background: rgba(221, 36, 36, 0.08);
  color: var(--danger, #dd2424);
  border-radius: var(--radius-medium, 6px);
  font-size: 13px;
  line-height: 1.5;
}

.auth-modal__success {
  margin: 16px 24px 0;
  padding: 10px 14px;
  background: rgba(103, 194, 58, 0.08);
  color: var(--success, #67c23a);
  border-radius: var(--radius-medium, 6px);
  font-size: 13px;
  line-height: 1.5;
}

/* ========== Loading ========== */
.auth-modal__loading {
  padding: 40px 24px;
  text-align: center;
  color: var(--muted-foreground, #999);
}

.auth-modal__spinner {
  width: 32px;
  height: 32px;
  border: 3px solid var(--border, #e2e2e2);
  border-top-color: var(--primary, #333);
  border-radius: 50%;
  animation: auth-spin 0.7s linear infinite;
  margin: 0 auto 12px;
}

@keyframes auth-spin {
  to { transform: rotate(360deg); }
}

/* ========== Message page ========== */
.auth-modal__message {
  padding: 40px 24px;
  text-align: center;
}

.auth-modal__message-icon {
  color: var(--primary, #333);
  margin-bottom: 16px;
}

.auth-modal__message p {
  color: var(--foreground, #333);
  font-size: 14px;
  line-height: 1.6;
  margin: 0 0 24px;
}

/* ========== Form ========== */
.auth-modal__form {
  padding: 20px 24px 24px;
}

.auth-modal__desc {
  font-size: 13px;
  color: var(--muted-foreground, #999);
  margin: 0 0 16px;
  line-height: 1.5;
}

/* ========== Fields ========== */
.auth-field {
  margin-bottom: 16px;
}

.auth-field label {
  display: block;
  font-size: 13px;
  font-weight: 500;
  color: var(--foreground, #333);
  margin-bottom: 6px;
}

.auth-field input[type="text"],
.auth-field input[type="password"],
.auth-field input[type="email"] {
  width: 100%;
  box-sizing: border-box;
  border: 1px solid var(--input, #e2e2e2);
  border-radius: var(--radius-medium, 6px);
  padding: 10px 14px;
  font-size: 14px;
  font-family: inherit;
  color: var(--foreground, #333);
  background: var(--card, #ffffff);
  transition: border-color var(--transition-fast, 0.15s ease), box-shadow var(--transition-fast, 0.15s ease);
  line-height: 1.5;
}

.auth-field input:focus {
  border-color: var(--primary, #333);
  box-shadow: 0 0 0 3px color-mix(in srgb, var(--primary) 20%, transparent);
  outline: none;
}

.auth-field--checkbox label {
  display: flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
  font-weight: 400;
}

.auth-field--checkbox input[type="checkbox"] {
  accent-color: var(--primary, #333);
  width: 16px;
  height: 16px;
}

/* ========== Button ========== */
.auth-btn {
  width: 100%;
  padding: 10px 24px;
  border: none;
  border-radius: var(--radius-medium, 6px);
  font-size: 14px;
  font-weight: 500;
  font-family: inherit;
  cursor: pointer;
  transition: all var(--transition-fast, 0.15s ease);
  line-height: 1.5;
}

.auth-btn--primary {
  background: var(--primary, #333);
  color: var(--primary-foreground, #ffffff);
}

.auth-btn--primary:hover:not(:disabled) {
  opacity: 0.9;
  transform: translateY(-1px);
}

.auth-btn--primary:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* ========== Links ========== */
.auth-modal__links {
  display: flex;
  justify-content: space-between;
  margin-top: 16px;
  font-size: 13px;
}

.auth-modal__links a {
  color: var(--primary, #333);
  text-decoration: none;
  transition: opacity var(--transition-fast, 0.15s ease);
}

.auth-modal__links a:hover {
  opacity: 0.7;
  text-decoration: underline;
}

.auth-modal__links span {
  color: var(--muted-foreground, #999);
}

/* ========== Responsive ========== */
@media (max-width: 480px) {
  .auth-modal__container {
    max-width: 100%;
    border-radius: var(--radius-medium, 6px);
  }

  .auth-modal__header {
    padding: 16px 16px 0;
  }

  .auth-modal__form {
    padding: 16px;
  }

  .auth-modal__error,
  .auth-modal__success {
    margin: 12px 16px 0;
  }
}
</style>
