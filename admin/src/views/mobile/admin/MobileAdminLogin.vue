<template>
  <div class="mal-page">
    <div class="mal-hero">
      <img :src="monkeyLogo" class="mal-logo" alt="大圣物业" />
      <h1>管理后台</h1>
      <p>大圣物业 · 智慧管理</p>
    </div>
    <div class="mal-form">
      <div class="mal-input">
        <span class="mal-input-icon">👤</span>
        <input v-model="username" placeholder="管理员账号" @keyup.enter="login" />
      </div>
      <div class="mal-input">
        <span class="mal-input-icon">🔒</span>
        <input v-model="password" placeholder="登录密码" type="password" @keyup.enter="login" />
      </div>
      <div class="mal-captcha">
        <input v-model="captchaCode" placeholder="验证码" maxlength="4" @keyup.enter="login" />
        <img v-if="captchaImage" :src="captchaImage" @click="fetchCaptcha" title="点击刷新" class="mal-captcha-img" />
        <div v-else class="mal-captcha-placeholder" @click="fetchCaptcha">
          {{ captchaLoading ? '...' : '点击获取' }}
        </div>
      </div>
      <button class="mal-btn" :disabled="loading" @click="login">
        {{ loading ? '登录中...' : '登 录' }}
      </button>
      <div v-if="err" class="mal-err">{{ err }}</div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { ElMessage } from 'element-plus'
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

onMounted(() => {
  // 微信 OAuth 回调
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
  // 已登录直接跳转
  if (userStore.token) {
    router.replace('/mobile/admin/dashboard')
    return
  }
  fetchCaptcha()
})

async function fetchCaptcha() {
  try {
    captchaLoading.value = true
    const r = await fetch('/api/admin/captcha')
    const d = await r.json()
    if (d.code === 0 && d.data) {
      captchaImage.value = d.data.image
      captchaKey.value = d.data.key
    }
  } catch {}
  finally { captchaLoading.value = false }
}

async function login() {
  if (!username.value || !password.value) {
    err.value = '请输入账号和密码'
    return
  }
  loading.value = true
  err.value = ''
  try {
    await userStore.login(username.value, password.value, captchaCode.value || undefined, captchaKey.value || undefined)
    ElMessage.success('登录成功')
    router.replace('/mobile/admin/dashboard')
  } catch (e: any) {
    err.value = e?.msg || '登录失败'
    fetchCaptcha()
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.mal-page { min-height: 100vh; background: linear-gradient(180deg, #1a365d 0%, #2d6aa0 40%, #f7f8fc 40%); display: flex; flex-direction: column; align-items: center; padding: 80px 24px 40px; }
.mal-hero { text-align: center; margin-bottom: 32px; }
.mal-logo { width: 72px; height: 72px; border-radius: 16px; object-fit: contain; }
.mal-hero h1 { font-size: 22px; font-weight: 700; color: #fff; margin-top: 10px; text-shadow: 0 1px 3px rgba(0,0,0,0.2); }
.mal-hero p { color: rgba(255,255,255,0.75); font-size: 13px; margin-top: 4px; }
.mal-form { width: 100%; max-width: 340px; background: #fff; border-radius: 16px; padding: 28px 22px; box-shadow: 0 8px 30px rgba(0,0,0,0.12); }
.mal-input { display: flex; align-items: center; background: #f7f8fc; border-radius: 12px; padding: 0 14px; margin-bottom: 14px; height: 50px; gap: 10px; border: 1px solid #e2e8f0; transition: border .2s; }
.mal-input:focus-within { border-color: #3182ce; }
.mal-input-icon { font-size: 18px; }
.mal-input input { flex: 1; background: transparent; border: none; outline: none; color: #1a202c; font-size: 15px; height: 50px; }
.mal-captcha { display: flex; gap: 10px; margin-bottom: 14px; align-items: center; }
.mal-captcha input { flex: 1; height: 50px; background: #f7f8fc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 0 14px; font-size: 15px; outline: none; }
.mal-captcha input:focus { border-color: #3182ce; }
.mal-captcha-img { height: 50px; border-radius: 10px; cursor: pointer; border: 1px solid #e2e8f0; min-width: 100px; }
.mal-captcha-placeholder { height: 50px; min-width: 100px; border-radius: 10px; border: 1px dashed #cbd5e0; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 13px; color: #718096; background: #f7fafc; }
.mal-btn { width: 100%; height: 50px; background: linear-gradient(135deg, #3182ce, #2b6cb0); color: #fff; border: none; border-radius: 12px; font-size: 16px; font-weight: 600; cursor: pointer; letter-spacing: 2px; }
.mal-btn:active { opacity: 0.85; }
.mal-btn:disabled { opacity: 0.6; }
.mal-err { color: #e53e3e; text-align: center; margin-top: 12px; font-size: 13px; }
</style>
