<template>
  <div class="login-page">
    <div class="login-card">
      <div class="login-left">
        <img src="/admin/assets/images/welcome-girl.png" alt="" class="welcome-girl" />
        <div class="brand">
          <img src="/admin/assets/images/monkey-icon.png" alt="" class="brand-icon-img" />
          <p>杭州喵喵至家网络有限公司 · 智慧社区物业管理平台</p>
        </div>
      </div>
      <div class="login-right">
        <!-- 密码登录 -->
        <template v-if="!showBind">
          <h2>欢迎回来</h2>
          <p class="subtitle">请登录您的管理账户</p>
          <div>
            <div style="margin-bottom:16px;">
              <select v-model="selectedCommunity" class="ni community-select">
                <option :value="0">请选择小区（可选）</option>
                <option v-for="c in communities" :key="c.id" :value="c.id">{{ c.name }}</option>
              </select>
            </div>
            <div style="margin-bottom:16px;"><input v-model="username" placeholder="管理员账号" class="ni" @keyup.enter="handleLogin" /></div>
            <div style="margin-bottom:16px;"><input v-model="password" type="password" placeholder="登录密码" class="ni" @keyup.enter="handleLogin" /></div>
            <div style="margin-bottom:16px;display:flex;gap:8px;align-items:center;">
              <input v-model="captchaCode" placeholder="验证码" class="ni" style="flex:1;" @keyup.enter="handleLogin" maxlength="4" />
              <img v-if="captchaImage" :src="captchaImage" class="captcha-img" @click="fetchCaptcha" title="点击刷新" />
              <div v-else class="captcha-placeholder" @click="fetchCaptcha" title="点击刷新">
                {{ captchaLoading ? '加载中...' : (captchaError || '点击获取') }}
              </div>
            </div>
            <button class="lb" :disabled="loading" @click="handleLogin">{{ loading ? '登录中...' : '🚀 登 录' }}</button>
            <div v-if="error" class="error-msg">{{ error }}</div>
          </div>
          <div class="divider"><span>或</span></div>
          <button class="btn-wechat" :disabled="loading" @click="handleWechatLogin">
            <span class="wx-icon">💬</span> 微信一键登录
          </button>
          <!-- 二维码扫码弹窗 -->
          <div v-if="showQrModal" class="qr-overlay" @click.self="closeQrModal">
            <div class="qr-modal">
              <div class="qr-close" @click="closeQrModal">✕</div>
              <h3>微信扫码登录</h3>
              <p class="qr-sub">请使用手机微信扫描下方二维码</p>
              <div class="qr-img-box">
                <img v-if="qrImageUrl" :src="qrImageUrl" alt="微信扫码" class="qr-img" />
                <div v-else class="qr-loading">生成二维码中...</div>
              </div>
              <div class="qr-status" :class="qrStatus">
                <span v-if="qrStatus === 'pending'">⏳ 等待扫码授权...</span>
                <span v-else-if="qrStatus === 'ok'">✅ 登录成功，正在跳转...</span>
                <span v-else-if="qrStatus === 'need_bind'">⚠️ 该微信未绑定账号，请先绑定</span>
                <span v-else-if="qrStatus === 'expired'">⌛ 二维码已过期，请重新生成</span>
                <span v-else-if="qrStatus === 'error'">{{ qrErrorMsg }}</span>
              </div>
              <button v-if="qrStatus === 'expired' || qrStatus === 'error'" class="qr-refresh-btn" @click="handleWechatLogin">🔄 重新生成</button>
            </div>
          </div>
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
const captchaLoading = ref(false)
const captchaError = ref('')
const loading = ref(false)
const error = ref('')
const showBind = ref(false)
const bindUsername = ref('')
const bindPassword = ref('')
const wxOpenid = ref('')
const communities = ref<any[]>([])
const selectedCommunity = ref(0)

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
  fetchCommunities()
})

async function fetchCaptcha() {
  try {
    captchaLoading.value = true
    captchaError.value = ''
    const res = await fetch('/api/admin/captcha')
    const data = await res.json()
    if (data.code === 0) {
      captchaKey.value = data.data.key
      captchaImage.value = data.data.image
      captchaCode.value = ''
    } else {
      captchaError.value = data.msg || '验证码加载失败'
    }
  } catch (e: any) {
    captchaError.value = '网络异常，请检查连接后刷新'
  } finally {
    captchaLoading.value = false
  }
}

async function fetchCommunities() {
  try {
    const res = await fetch('/api/communityList')
    const data = await res.json()
    if (data.code === 0) communities.value = data.data || []
  } catch { /* ignore */ }
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
      if (selectedCommunity.value) localStorage.setItem('admin_community_id', String(selectedCommunity.value))
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

const showQrModal = ref(false)
const qrImageUrl = ref('')
const qrStatus = ref('pending')  // pending | ok | expired | error | need_bind
const qrErrorMsg = ref('')
let pollTimer: any = null

async function handleWechatLogin() {
  error.value = ''
  try {
    const cid = selectedCommunity.value ? `&community_id=${selectedCommunity.value}` : ''
    const res = await fetch(`/api/admin/wechatOAuth?json=1${cid}`)
    const data = await res.json()
    if (data.code === 0 && data.data.oauth_url) {
      const oauthUrl = data.data.oauth_url
      const sessionKey = data.data.session_key || ''
      // 生成二维码图片
      qrImageUrl.value = 'https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=' + encodeURIComponent(oauthUrl)
      qrStatus.value = 'pending'
      showQrModal.value = true
      // 开始轮询
      if (sessionKey) {
        startPolling(sessionKey)
      }
    } else {
      error.value = data.msg || '获取微信授权链接失败'
    }
  } catch (e: any) {
    error.value = '网络请求失败: ' + (e.message || '')
  }
}

function startPolling(sessionKey: string) {
  let count = 0
  pollTimer = setInterval(async () => {
    count++
    if (count > 150) { // 5分钟后过期
      qrStatus.value = 'expired'
      clearInterval(pollTimer!)
      pollTimer = null
      return
    }
    try {
      const res = await fetch('/api/admin/wechatLoginStatus?session=' + encodeURIComponent(sessionKey))
      const d = await res.json()
      if (d.code === 0) {
        if (d.data.status === 'ok') {
          qrStatus.value = 'ok'
          clearInterval(pollTimer!)
          pollTimer = null
          localStorage.setItem('admin_token', d.data.token)
          if (selectedCommunity.value) localStorage.setItem('admin_community_id', String(selectedCommunity.value))
          qrErrorMsg.value = '登录成功'
          setTimeout(() => {
            showQrModal.value = false
            ElMessage.success('微信登录成功')
            router.push('/')
          }, 1000)
        } else if (d.data.status === 'need_bind') {
          qrStatus.value = 'need_bind'
          clearInterval(pollTimer!)
          pollTimer = null
          qrErrorMsg.value = '该微信未绑定管理员账号，请先使用账号密码登录后绑定'
        } else if (d.data.status === 'expired') {
          qrStatus.value = 'expired'
          clearInterval(pollTimer!)
          pollTimer = null
        }
        // pending: keep polling
      }
    } catch (e) {
      // ignore polling errors
    }
  }, 2000)
}

function closeQrModal() {
  showQrModal.value = false
  if (pollTimer) {
    clearInterval(pollTimer)
    pollTimer = null
  }
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
      if (selectedCommunity.value) localStorage.setItem('admin_community_id', String(selectedCommunity.value))
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
.login-left { flex:1;background:linear-gradient(135deg,#2b6cb0,#3182ce);padding:40px;display:flex;flex-direction:column;justify-content:center;align-items:center;position:relative;overflow:hidden; }
.welcome-girl { width:100%;max-width:320px;height:auto;object-fit:contain;margin-bottom:16px;border-radius:12px; }
.brand { text-align:center;z-index:2; }
.brand-icon-img { width:64px;height:64px;object-fit:contain;margin-bottom:8px; }
.brand h1 { color:#fff;font-size:22px;font-weight:700;margin-bottom:4px; }
.brand p { color:rgba(255,255,255,0.7);font-size:13px; }
.login-right { width:360px;padding:40px;display:flex;flex-direction:column;justify-content:center; }
.login-right h2 { font-size:22px;font-weight:700;color:#1a202c;margin-bottom:4px; }
.subtitle { color:#a0aec0;font-size:14px;margin-bottom:24px; }
.ni { width:100%;height:40px;border:1px solid #d9d9d9;border-radius:6px;padding:0 12px;font-size:14px;outline:none;box-sizing:border-box; }
.ni:focus { border-color:#3182ce;box-shadow:0 0 0 3px rgba(49,130,206,0.1); }
.community-select { appearance: auto; background:#fff; color:#333; cursor:pointer; }
.community-select option { color:#333; }
.captcha-img { height:40px;width:120px;border-radius:6px;border:1px solid #d9d9d9;cursor:pointer;flex-shrink:0; }
.captcha-placeholder { height:40px;width:120px;border-radius:6px;border:1px dashed #d9d9d9;cursor:pointer;flex-shrink:0;display:flex;align-items:center;justify-content:center;font-size:12px;color:#ff4d4f;background:#fff2f0; }
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
.qr-overlay { position:fixed;inset:0;background:rgba(0,0,0,.55);display:flex;align-items:center;justify-content:center;z-index:9999; }
.qr-modal { background:#fff;border-radius:16px;padding:32px 36px;text-align:center;max-width:360px;width:90%;position:relative;box-shadow:0 20px 60px rgba(0,0,0,.3); }
.qr-close { position:absolute;top:12px;right:16px;cursor:pointer;font-size:20px;color:#999;line-height:1; }
.qr-close:hover { color:#333; }
.qr-modal h3 { margin:0 0 6px;font-size:20px;color:#1e293b; }
.qr-sub { margin:0 0 20px;font-size:13px;color:#64748b; }
.qr-img-box { width:220px;height:220px;margin:0 auto 16px;border-radius:12px;overflow:hidden;border:3px solid #e2e8f0;display:flex;align-items:center;justify-content:center; }
.qr-img { width:100%;height:100%;object-fit:contain; }
.qr-loading { color:#94a3b8;font-size:14px; }
.qr-status { font-size:14px;padding:8px 12px;border-radius:8px; }
.qr-status.pending { color:#d97706;background:#fffbeb; }
.qr-status.ok { color:#059669;background:#ecfdf5; }
.qr-status.expired,
.qr-status.error { color:#dc2626;background:#fef2f2; }
.qr-status.need_bind { color:#7c3aed;background:#f5f3ff; }
.qr-refresh-btn { margin-top:12px;padding:8px 24px;background:#3b82f6;color:#fff;border:none;border-radius:8px;font-size:13px;cursor:pointer; }
.qr-refresh-btn:hover { background:#2563eb; }
.back-link { display:inline-block;margin-top:16px;color:#3182ce;font-size:13px;cursor:pointer;text-align:center;width:100%; }
.back-link:hover { text-decoration:underline; }
</style>
