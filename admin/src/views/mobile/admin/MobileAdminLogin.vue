<template>
  <!-- 高端精品登录页 -->
  <div class="login-page">
    <!-- 背景装饰 -->
    <div class="bg-decor">
      <div class="bg-circle c1"></div>
      <div class="bg-circle c2"></div>
      <div class="bg-circle c3"></div>
    </div>

    <!-- 主内容 -->
    <div class="login-card">
      <!-- Logo区 -->
      <div class="logo-zone">
        <div class="logo-ring">
          <img :src="monkeyLogo" class="logo-img" />
        </div>
        <h1 class="app-name">大圣物业</h1>
        <p class="app-desc">智慧管理平台 · 管理员端</p>
      </div>

      <!-- 表单 -->
      <div class="form-zone">
        <!-- 账号 -->
        <div class="input-group">
          <Icon icon="ph:user-duotone" class="input-icon" />
          <input
            v-model="username"
            type="text"
            placeholder="管理员账号"
            autocomplete="username"
            @keyup.enter="focusPassword"
          />
        </div>

        <!-- 密码 -->
        <div class="input-group">
          <Icon icon="ph:lock-duotone" class="input-icon" />
          <input
            ref="pwdRef"
            v-model="password"
            :type="showPwd ? 'text' : 'password'"
            placeholder="登录密码"
            autocomplete="current-password"
            @keyup.enter="focusCaptcha"
          />
          <Icon
            :icon="showPwd ? 'ph:eye-duotone' : 'ph:eye-closed-duotone'"
            class="input-eye"
            @click="showPwd = !showPwd"
          />
        </div>

        <!-- 验证码 -->
        <div class="input-group captcha-row">
          <Icon icon="ph:shield-check-duotone" class="input-icon" />
          <input
            ref="captchaRef"
            v-model="captchaCode"
            type="text"
            maxlength="4"
            placeholder="验证码"
            autocomplete="off"
            @keyup.enter="login"
          />
          <div class="captcha-img" @click="fetchCaptcha">
            <img v-if="captchaImage" :src="captchaImage" class="ci-pic" />
            <Icon v-else icon="ph:arrows-clockwise-duotone" class="ci-refresh" :class="{ spinning: captchaLoading }" />
          </div>
        </div>

        <!-- 登录按钮 -->
        <button class="login-btn" :class="{ loading }" :disabled="loading" @click="login">
          <span v-if="!loading">登 录</span>
          <Icon v-else icon="ph:spinner" class="btn-spin" />
          <span v-if="loading">登录中...</span>
        </button>

        <!-- 错误提示 -->
        <Transition name="fade">
          <div v-if="err" class="err-msg">
            <Icon icon="ph:warning-circle-fill" class="err-icon" />
            {{ err }}
          </div>
        </Transition>
      </div>

      <!-- 底部链接 -->
      <div class="bottom-links">
        <span @click="goPc">PC 端管理</span>
      </div>
    </div>

    <!-- 版本号 -->
    <div class="version">v2.0 · 杭州喵喵至家</div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { ElMessage } from 'element-plus'
import { Icon } from '@iconify/vue'
import monkeyLogo from '@/assets/images/monkey-ico.png'
import { useUserStore } from '@/stores/user'

const router = useRouter()
const route = useRoute()
const userStore = useUserStore()

const username = ref('admin')
const password = ref('')
const captchaCode = ref('')
const captchaImage = ref('')
const captchaKey = ref('')
const loading = ref(false)
const err = ref('')
const captchaLoading = ref(false)
const showPwd = ref(false)
const pwdRef = ref<HTMLInputElement>()
const captchaRef = ref<HTMLInputElement>()

onMounted(() => {
  // 微信回调
  const q = route.query
  if (q.wechat_token) {
    const token = q.wechat_token as string
    router.replace({ query: {} })
    localStorage.setItem('admin_token', token)
    userStore.token = token
    ElMessage.success('微信登录成功')
    router.replace('/mobile/admin/dashboard')
    return
  }
  // 已登录
  if (userStore.token) {
    router.replace('/mobile/admin/dashboard')
    return
  }
  fetchCaptcha()
})

function focusPassword() { pwdRef.value?.focus() }
function focusCaptcha() { captchaRef.value?.focus() }

async function fetchCaptcha() {
  try {
    captchaLoading.value = true
    const r = await fetch('/api/admin/captcha')
    const d = await r.json()
    if (d.code === 0 && d.data) {
      captchaImage.value = d.data.image
      captchaKey.value = d.data.key
    }
  } catch { /* ignore */ }
  finally { captchaLoading.value = false }
}

async function login() {
  err.value = ''
  if (!username.value || !password.value) {
    err.value = '请输入账号和密码'
    return
  }
  if (!captchaCode.value) {
    err.value = '请输入验证码'
    return
  }
  loading.value = true
  try {
    await userStore.login(username.value, password.value, captchaCode.value || undefined, captchaKey.value || undefined)
    ElMessage.success('登录成功')
    router.replace('/mobile/admin/dashboard')
  } catch (e: any) {
    err.value = e?.msg || e?.message || '登录失败，请重试'
    fetchCaptcha()
  } finally {
    loading.value = false
  }
}

function goPc() {
  router.push('/login')
}
</script>

<style scoped>
.login-page {
  min-height: 100vh;
  min-height: 100dvh;
  background: linear-gradient(160deg, #0f172a 0%, #1e293b 30%, #0f172a 60%, #1a1a2e 100%);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 40px 24px;
  position: relative;
  overflow: hidden;
}

/* 背景装饰 */
.bg-decor { position: absolute; inset: 0; pointer-events: none; }
.bg-circle {
  position: absolute;
  border-radius: 50%;
  opacity: 0.04;
}
.c1 { width: 500px; height: 500px; background: #6366f1; top: -150px; right: -180px; }
.c2 { width: 350px; height: 350px; background: #06b6d4; bottom: -80px; left: -120px; }
.c3 { width: 200px; height: 200px; background: #8b5cf6; top: 40%; left: 60%; }

/* 主卡片 */
.login-card {
  width: 100%;
  max-width: 360px;
  position: relative;
  z-index: 1;
  animation: slideUp .6s ease;
}

@keyframes slideUp {
  from { opacity: 0; transform: translateY(30px); }
  to { opacity: 1; transform: translateY(0); }
}

/* Logo区 */
.logo-zone { text-align: center; margin-bottom: 36px; }
.logo-ring {
  width: 80px; height: 80px;
  border-radius: 24px;
  margin: 0 auto 16px;
  background: linear-gradient(135deg, rgba(99,102,241,.15), rgba(6,182,212,.15));
  backdrop-filter: blur(10px);
  border: 1px solid rgba(99,102,241,.2);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 8px;
}
.logo-img { width: 56px; height: 56px; border-radius: 16px; object-fit: contain; }
.app-name { font-size: 26px; font-weight: 800; color: #f1f5f9; letter-spacing: 2px; margin: 0; }
.app-desc { font-size: 13px; color: #94a3b8; margin: 6px 0 0; letter-spacing: 1px; }

/* 表单 */
.form-zone { display: flex; flex-direction: column; gap: 14px; }

.input-group {
  position: relative;
  display: flex;
  align-items: center;
  background: rgba(30, 41, 59, .7);
  backdrop-filter: blur(10px);
  border: 1px solid rgba(100, 116, 139, .15);
  border-radius: 14px;
  padding: 0 16px;
  height: 52px;
  transition: all .25s;
}
.input-group:focus-within {
  border-color: rgba(99, 102, 241, .5);
  box-shadow: 0 0 0 3px rgba(99,102,241,.1);
  background: rgba(30, 41, 59, .9);
}
.input-icon { font-size: 20px; color: #64748b; flex-shrink: 0; transition: color .25s; }
.input-group:focus-within .input-icon { color: #818cf8; }
.input-group input {
  flex: 1;
  background: transparent;
  border: none;
  outline: none;
  color: #e2e8f0;
  font-size: 15px;
  padding: 0 12px;
  height: 52px;
}
.input-group input::placeholder { color: #475569; }
.input-eye { font-size: 18px; color: #64748b; cursor: pointer; flex-shrink: 0; transition: color .2s; }
.input-eye:hover { color: #94a3b8; }

/* 验证码 */
.captcha-row { padding-right: 8px; }
.captcha-img {
  width: 90px; height: 36px;
  border-radius: 10px;
  overflow: hidden;
  cursor: pointer;
  flex-shrink: 0;
  background: rgba(99,102,241,.08);
  display: flex;
  align-items: center;
  justify-content: center;
  transition: opacity .2s;
}
.captcha-img:active { opacity: .7; }
.ci-pic { width: 100%; height: 100%; object-fit: contain; }
.ci-refresh { font-size: 20px; color: #6366f1; }
.ci-refresh.spinning { animation: spin 1s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }

/* 登录按钮 */
.login-btn {
  width: 100%;
  height: 52px;
  border: none;
  border-radius: 14px;
  background: linear-gradient(135deg, #6366f1, #4f46e5);
  color: #fff;
  font-size: 16px;
  font-weight: 700;
  letter-spacing: 3px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  transition: all .25s;
  margin-top: 4px;
}
.login-btn:active:not(:disabled) { transform: scale(.97); opacity: .9; }
.login-btn:disabled { opacity: .5; cursor: not-allowed; }
.login-btn.loading { background: linear-gradient(135deg, #4f46e5, #4338ca); }
.btn-spin { font-size: 18px; animation: spin 1s linear infinite; }

/* 错误 */
.err-msg {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  color: #f87171;
  font-size: 13px;
  padding: 10px;
  background: rgba(248,113,113,.1);
  border-radius: 10px;
  border: 1px solid rgba(248,113,113,.15);
}
.err-icon { font-size: 16px; flex-shrink: 0; }

/* 过渡 */
.fade-enter-active, .fade-leave-active { transition: all .25s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; transform: translateY(-6px); }

/* 底部链接 */
.bottom-links { text-align: center; margin-top: 28px; }
.bottom-links span { color: #64748b; font-size: 13px; cursor: pointer; transition: color .2s; }
.bottom-links span:hover { color: #94a3b8; }

/* 版本 */
.version { position: absolute; bottom: 24px; color: #475569; font-size: 11px; z-index: 1; }
</style>
