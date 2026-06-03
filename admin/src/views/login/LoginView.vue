<template>
  <div class="login-page">
    <div class="login-card">
      <div class="login-left">
        <div class="brand">
          <div class="brand-icon">🐒</div>
          <h1>大圣物业管理平台</h1>
          <p>杭州喵喵至家网络有限公司 · 智慧社区物业管理平台</p>
        </div>
      </div>
      <div class="login-right">
        <!-- 密码登录 -->
        <template v-if="!showBind">
          <h2>欢迎回来</h2>
          <p class="subtitle">请登录您的管理账户</p>
          <div>
            <div style="margin-bottom:16px;"><input v-model="username" placeholder="管理员账号" class="ni" @keyup.enter="handleLogin" /></div>
            <div style="margin-bottom:16px;"><input v-model="password" type="password" placeholder="登录密码" class="ni" @keyup.enter="handleLogin" /></div>
            <div style="margin-bottom:16px;display:flex;gap:8px;">
              <input v-model="captchaCode" placeholder="验证码" class="ni" style="flex:1;" @keyup.enter="handleLogin" maxlength="4" />
              <img :src="captchaImage" class="captcha-img" @click="fetchCaptcha" title="点击刷新" />
            </div>
            <button class="lb" :disabled="loading" @click="handleLogin">{{ loading ? '登录中...' : '🚀 登 录' }}</button>
            <div v-if="error" class="error-msg">{{ error }}</div>
          </div>
          <div class="divider"><span>或</span></div>
          <button class="btn-wechat" :disabled="loading" @click="handleWechatLogin">
            <span class="wx-icon">💬</span> 微信一键登录
          </button>
        </template>

        <!-- 微信绑定 -->
        <template v-else>
          <h2>绑定管理员账号</h2>
          <p class="subtitle">该微信尚未绑定管理账号，请输入已有管理员账号绑定</p>
          <div>
            <div style="margin-bottom:16px;"><input v-model="bindUsername" placeholder="管理员账号" class="ni" /></div>
            <div style="margin-bottom:16px;"><input v-model="bindPassword" type="password" placeholder="登录密码" class="ni" /></div>
            <button class="lb" :disabled="loading" @click="handleBind">{{ loading ? '绑定中...' : '🔗 绑定并登录' }}</button>
            <div v-if="error" class="error-msg">{{ error }}</div>
          </div>
          <a class="back-link" @click="showBind = false">← 返回密码登录</a>
        </template>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { ElMessage } from 'element-plus'

const router = useRouter()
const route = useRoute()
const username = ref('')
const password = ref('')
const captchaCode = ref('')
const captchaKey = ref('')
const captchaImage = ref('')
const loading = ref(false)
const error = ref('')
const showBind = ref(false)
const bindUsername = ref('')
const bindPassword = ref('')
const wxOpenid = ref('')

// 检测微信 OAuth 回调
onMounted(() => {
  const q = route.query
  // 已有绑定 → OAuth 回调带 token
  if (q.wechat_token) {
    localStorage.setItem('admin_token', q.wechat_token as string)
    ElMessage.success('微信登录成功')
    router.replace('/')
    return
  }
  // 未绑定 → 进入绑定流程
  if (q.action === 'wx_bind' && q.wx_openid) {
    wxOpenid.value = q.wx_openid as string
    showBind.value = true
    // 清除 URL 参数
    router.replace({ query: {} })
    return
  }
  fetchCaptcha()
})

async function fetchCaptcha() {
  try {
    const res = await fetch('/api/admin/captcha')
    const data = await res.json()
    if (data.code === 0) {
      captchaKey.value = data.data.key
      captchaImage.value = data.data.image
      captchaCode.value = ''
    }
  } catch (e) {
    // ignore
  }
}

async function handleLogin() {
  if (!username.value || !password.value) {
    error.value = '请输入用户名和密码'
    return
  }
  if (!captchaCode.value) {
    error.value = '请输入验证码'
    return
  }
  error.value = ''
  loading.value = true
  try {
    const res = await fetch('/api/admin/login', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `username=${encodeURIComponent(username.value)}&password=${encodeURIComponent(password.value)}&captcha=${encodeURIComponent(captchaCode.value)}&captchaKey=${encodeURIComponent(captchaKey.value)}`,
    })
    const data = await res.json()
    if (data.code === 0) {
      localStorage.setItem('admin_token', data.data.token)
      ElMessage.success('登录成功')
      router.push('/')
    } else {
      error.value = data.msg || '登录失败'
      fetchCaptcha()
    }
  } catch (e: any) {
    error.value = '网络请求失败: ' + (e.message || '')
  } finally {
    loading.value = false
  }
}

function handleWechatLogin() {
  let communityId = localStorage.getItem('wx_community_id')
  if (!communityId) {
    communityId = prompt('请输入小区ID（可在后台小区管理中查看）：', '')
    if (!communityId) return
    localStorage.setItem('wx_community_id', communityId)
  }
  location.href = `/api/admin/wechatOAuth?community_id=${communityId}`
}

async function handleBind() {
  if (!bindUsername.value || !bindPassword.value) {
    error.value = '请输入用户名和密码'
    return
  }
  error.value = ''
  loading.value = true
  try {
    const res = await fetch('/api/admin/wechatBind', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `openid=${encodeURIComponent(wxOpenid.value)}&username=${encodeURIComponent(bindUsername.value)}&password=${encodeURIComponent(bindPassword.value)}`,
    })
    const data = await res.json()
    if (data.code === 0) {
      localStorage.setItem('admin_token', data.data.token)
      ElMessage.success('绑定成功')
      router.push('/')
    } else {
      error.value = data.msg || '绑定失败'
    }
  } catch (e: any) {
    error.value = '网络请求失败: ' + (e.message || '')
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.login-page { height:100vh;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#2b6cb0,#2c5282); }
.login-card { display:flex;background:#fff;border-radius:20px;box-shadow:0 25px 50px rgba(0,0,0,0.15);overflow:hidden;width:800px;min-height:480px; }
.login-left { flex:1;background:linear-gradient(135deg,#2b6cb0,#3182ce);padding:40px;display:flex;flex-direction:column;justify-content:center; }
.brand-icon { font-size:48px;margin-bottom:8px; }
.brand h1 { color:#fff;font-size:22px;font-weight:700;margin-bottom:4px; }
.brand p { color:rgba(255,255,255,0.7);font-size:13px; }
.login-right { width:360px;padding:40px;display:flex;flex-direction:column;justify-content:center; }
.login-right h2 { font-size:22px;font-weight:700;color:#1a202c;margin-bottom:4px; }
.subtitle { color:#a0aec0;font-size:14px;margin-bottom:24px; }
.ni { width:100%;height:40px;border:1px solid #d9d9d9;border-radius:6px;padding:0 12px;font-size:14px;outline:none;box-sizing:border-box; }
.ni:focus { border-color:#3182ce;box-shadow:0 0 0 3px rgba(49,130,206,0.1); }
.captcha-img { height:40px;width:120px;border-radius:6px;border:1px solid #d9d9d9;cursor:pointer;flex-shrink:0; }
.lb { width:100%;height:44px;background:linear-gradient(135deg,#2b6cb0,#3182ce);color:#fff;border:none;border-radius:10px;font-size:16px;cursor:pointer; }
.lb:hover { box-shadow:0 4px 14px rgba(43,108,176,0.3); }
.lb:disabled { opacity:0.6;cursor:not-allowed; }
.error-msg { color:#e53e3e;margin-top:12px;text-align:center;font-size:14px; }
.divider { display:flex;align-items:center;margin:20px 0;color:#a0aec0;font-size:12px; }
.divider::before, .divider::after { content:'';flex:1;height:1px;background:#e2e8f0; }
.divider span { padding:0 12px; }
.btn-wechat { width:100%;height:44px;background:#07c160;color:#fff;border:none;border-radius:10px;font-size:15px;font-weight:600;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:6px; }
.btn-wechat:hover { background:#06ad56; }
.btn-wechat:disabled { opacity:0.6;cursor:not-allowed; }
.wx-icon { font-size:18px; }
.back-link { display:inline-block;margin-top:16px;color:#3182ce;font-size:13px;cursor:pointer;text-align:center;width:100%; }
.back-link:hover { text-decoration:underline; }
</style>
