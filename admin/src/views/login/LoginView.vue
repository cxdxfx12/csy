<template>
  <div class="login-page">
    <div class="login-card">
      <div class="login-left">
        <img :src="welcomeGirlImg" alt="" class="welcome-girl" />
        <div class="brand">
          <span class="brand-emoji">🐵</span>
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
    <!-- 主题切换浮动按钮 -->
    <div class="theme-fab">
      <button class="theme-fab-btn" @click.stop="themeOpen = !themeOpen">🎨</button>
      <div class="theme-panel" v-if="themeOpen" @click.stop>
        <div class="theme-panel-title">选择主题</div>
        <div v-for="t in themeList" :key="t.id" class="theme-item" :class="{active: currentTheme === t.id}" @click="switchTheme(t.id)">
          <span class="theme-swatch" :style="{background: `linear-gradient(135deg, ${t.preview[0]}, ${t.preview[1]})`}"></span>
          <span class="theme-name">{{ t.icon }} {{ t.name }}</span>
          <span v-if="currentTheme === t.id" class="theme-check">✓</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { ElMessage } from 'element-plus'
import welcomeGirlImg from '@/assets/images/welcome-girl.jpg'
import { themes as themeList, current as currentTheme, applyTheme } from '@/stores/theme'

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
const themeOpen = ref(false)

function switchTheme(id: string) {
  applyTheme(id)
  themeOpen.value = false
}

function closeThemePanel(e: MouseEvent) {
  if (themeOpen.value && !(e.target as HTMLElement).closest('.theme-fab')) {
    themeOpen.value = false
  }
}

// 检测微信 OAuth 回调
onMounted(() => {
  document.addEventListener('click', closeThemePanel)
  const q = route.query
  // 已有绑定 → OAuth 回调带 token
  if (q.wechat_token) {
    const token = q.wechat_token as string
    router.replace({ query: {} })
    localStorage.setItem('admin_token', token)
    ElMessage.success('微信登录成功')
    router.replace('/')
    return
  }
  // 未绑定 → 进入绑定流程
  if (q.action === 'wx_bind' && q.wx_openid) {
    wxOpenid.value = q.wx_openid as string
    showBind.value = true
    router.replace({ query: {} })
    return
  }
  fetchCaptcha()
  fetchCommunities()
})

onUnmounted(() => {
  document.removeEventListener('click', closeThemePanel)
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
const qrStatus = ref('pending')
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
      qrImageUrl.value = 'https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=' + encodeURIComponent(oauthUrl)
      qrStatus.value = 'pending'
      showQrModal.value = true
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
    if (count > 150) {
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
.login-page { height:100vh;display:flex;align-items:center;justify-content:center;background:var(--login-bg);position:relative;overflow:hidden; }
.login-card { display:flex;background:var(--bg-card);border-radius:20px;box-shadow:var(--shadow-modal);overflow:hidden;width:800px;min-height:480px;border:1px solid var(--border-1); }
.login-left { flex:1;background:var(--login-left-bg);padding:40px;display:flex;flex-direction:column;justify-content:center;align-items:center;position:relative;overflow:hidden; }
.welcome-girl { width:100%;max-width:320px;height:auto;object-fit:contain;margin-bottom:16px;border-radius:12px; }
.brand { text-align:center;z-index:2; }
.brand-emoji { font-size:48px;display:block;margin-bottom:8px; }
.brand h1 { color:#fff;font-size:22px;font-weight:700;margin-bottom:4px; }
.brand p { color:rgba(255,255,255,0.7);font-size:13px; }
.login-right { width:360px;padding:40px;display:flex;flex-direction:column;justify-content:center; }
.login-right h2 { font-size:22px;font-weight:700;color:var(--text-1);margin-bottom:4px; }
.subtitle { color:var(--text-4);font-size:14px;margin-bottom:24px; }
.ni { width:100%;height:40px;border:1px solid var(--border-input);border-radius:6px;padding:0 12px;font-size:14px;outline:none;box-sizing:border-box;background:var(--bg-input);color:var(--text-1); }
.ni:focus { border-color:var(--login-focus-border);box-shadow:0 0 0 3px rgba(var(--accent-rgb),0.1); }
.community-select { appearance: auto; background:var(--bg-input); color:var(--text-1); cursor:pointer; }
.community-select option { color:var(--text-1); background:var(--bg-card); }
.captcha-img { height:40px;width:120px;border-radius:6px;border:1px solid var(--border-input);cursor:pointer;flex-shrink:0; }
.captcha-placeholder { height:40px;width:120px;border-radius:6px;border:1px dashed var(--border-input);cursor:pointer;flex-shrink:0;display:flex;align-items:center;justify-content:center;font-size:12px;color:var(--color-danger);background:var(--bg-input); }
.lb { width:100%;height:44px;background:var(--login-btn-bg);color:#fff;border:none;border-radius:10px;font-size:16px;cursor:pointer; }
.lb:hover { box-shadow:0 4px 14px var(--login-btn-shadow); }
.lb:disabled { opacity:0.6;cursor:not-allowed; }
.error-msg { color:var(--color-danger);margin-top:12px;text-align:center;font-size:14px; }
.divider { display:flex;align-items:center;margin:20px 0;color:var(--text-5);font-size:12px; }
.divider::before, .divider::after { content:'';flex:1;height:1px;background:var(--border-1); }
.divider span { padding:0 12px; }
.btn-wechat { width:100%;height:44px;background:#07c160;color:#fff;border:none;border-radius:10px;font-size:15px;font-weight:600;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:6px; }
.btn-wechat:hover { background:#06ad56; }
.btn-wechat:disabled { opacity:0.6;cursor:not-allowed; }
.wx-icon { font-size:18px; }
.qr-overlay { position:fixed;inset:0;background:rgba(0,0,0,.55);display:flex;align-items:center;justify-content:center;z-index:9999; }
.qr-modal { background:var(--bg-card);border-radius:16px;padding:32px 36px;text-align:center;max-width:360px;width:90%;position:relative;box-shadow:var(--shadow-modal); }
.qr-close { position:absolute;top:12px;right:16px;cursor:pointer;font-size:20px;color:var(--text-5);line-height:1; }
.qr-close:hover { color:var(--text-1); }
.qr-modal h3 { margin:0 0 6px;font-size:20px;color:var(--text-1); }
.qr-sub { margin:0 0 20px;font-size:13px;color:var(--text-4); }
.qr-img-box { width:220px;height:220px;margin:0 auto 16px;border-radius:12px;overflow:hidden;border:3px solid var(--border-1);display:flex;align-items:center;justify-content:center; }
.qr-img { width:100%;height:100%;object-fit:contain; }
.qr-loading { color:var(--text-5);font-size:14px; }
.qr-status { font-size:14px;padding:8px 12px;border-radius:8px; }
.qr-status.pending { color:#d97706;background:#fffbeb; }
.qr-status.ok { color:#059669;background:#ecfdf5; }
.qr-status.expired,
.qr-status.error { color:#dc2626;background:#fef2f2; }
.qr-status.need_bind { color:#7c3aed;background:#f5f3ff; }
.qr-refresh-btn { margin-top:12px;padding:8px 24px;background:var(--accent);color:#fff;border:none;border-radius:8px;font-size:13px;cursor:pointer; }
.qr-refresh-btn:hover { opacity:0.9; }
.back-link { display:inline-block;margin-top:16px;color:var(--accent);font-size:13px;cursor:pointer;text-align:center;width:100%; }
.back-link:hover { text-decoration:underline; }

/* ===== 主题切换浮动按钮 ===== */
.theme-fab { position:fixed;bottom:24px;right:24px;z-index:10000 }
.theme-fab-btn { width:44px;height:44px;border-radius:50%;border:1px solid var(--border-1);background:var(--bg-card);cursor:pointer;font-size:20px;display:flex;align-items:center;justify-content:center;box-shadow:var(--shadow-card-hover);transition:all .25s }
.theme-fab-btn:hover { transform:scale(1.1);box-shadow:var(--shadow-modal) }
.theme-panel { position:absolute;bottom:calc(100% + 8px);right:0;width:200px;background:var(--bg-card);border:1px solid var(--border-1);border-radius:12px;padding:8px;box-shadow:var(--shadow-modal);animation:panelIn .2s ease-out }
@keyframes panelIn{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:translateY(0)}}
.theme-panel-title { font-size:12px;color:var(--text-5);padding:6px 10px 8px;font-weight:600;text-transform:uppercase;letter-spacing:.5px }
.theme-item { display:flex;align-items:center;gap:10px;padding:8px 10px;border-radius:8px;cursor:pointer;transition:all .2s }
.theme-item:hover { background:var(--bg-table-hover) }
.theme-item.active { background:rgba(var(--accent-rgb),.1) }
.theme-swatch { width:24px;height:24px;border-radius:6px;flex-shrink:0;border:2px solid rgba(255,255,255,.1) }
.theme-name { flex:1;font-size:13px;color:var(--text-2);font-weight:500 }
.theme-check { color:var(--accent);font-weight:700;font-size:14px }
</style>
