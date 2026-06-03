<template>
  <div class="login-page">
    <div class="login-card">
      <div class="logo">🐒</div>
      <h1>大圣物业管理平台</h1>
      <p class="sub">小区经理工作台</p>

      <!-- 用户名密码登录 -->
      <div v-if="!showRegister">
        <input v-model="username" type="text" placeholder="请输入用户名" @keyup.enter="doLogin" />
        <input v-model="password" type="password" placeholder="请输入密码" @keyup.enter="doLogin" />
        <button class="btn-primary" @click="doLogin" :disabled="loading">
          {{ loading ? '登录中...' : '登 录' }}
        </button>
        <div class="divider"><span>或</span></div>
        <button class="btn-wechat" @click="doWechatLogin" :disabled="loading">
          <span class="wx-icon">💬</span> 微信一键登录
        </button>
      </div>

      <!-- 微信注册 -->
      <div v-else>
        <p class="reg-title">微信注册 · 小区经理</p>
        <input v-model="regForm.realname" type="text" placeholder="真实姓名" />
        <input v-model="regForm.phone" type="text" placeholder="手机号（选填）" />
        <input v-model="regForm.username" type="text" placeholder="设置用户名" />
        <input v-model="regForm.password" type="password" placeholder="设置密码" />
        <button class="btn-primary" @click="doRegister" :disabled="loading">
          {{ loading ? '注册中...' : '注 册' }}
        </button>
        <a class="back-link" @click="showRegister = false">← 返回登录</a>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { createApi, createAuth } from '@/shared/api.js'
import { showToast } from '@/shared/utils.js'

const route = useRoute()
const router = useRouter()
const api = createApi('/api/manager', 'manager_token')
const auth = createAuth('manager_token')

const username = ref('')
const password = ref('')
const loading = ref(false)
const showRegister = ref(false)

const regForm = ref({
  openid: '',
  community_id: 0,
  realname: '',
  phone: '',
  username: '',
  password: '',
})

// 检测 URL 上的 wechat_token（OAuth 回调带回）
onMounted(() => {
  const q = route.query
  // 已有绑定 → OAuth 回调带 token
  if (q.wechat_token) {
    auth.setToken(q.wechat_token)
    router.replace('/dashboard')
    return
  }
  // 未绑定 → 进入注册流程
  if (q.action === 'wx_register' && q.wx_openid) {
    regForm.value.openid = q.wx_openid
    regForm.value.community_id = parseInt(q.wx_cid) || 0
    showRegister.value = true
  }
})

async function doLogin() {
  if (!username.value || !password.value) return showToast('请输入用户名和密码')
  loading.value = true
  const res = await api('/login', { method: 'POST', body: JSON.stringify({ username: username.value, password: password.value }) })
  loading.value = false
  if (res.code === 0) {
    auth.setToken(res.data.token)
    router.replace('/dashboard')
  } else {
    showToast(res.msg || '登录失败')
  }
}

function doWechatLogin() {
  // 获取小区 ID
  let communityId = localStorage.getItem('wx_community_id')
  if (!communityId) {
    communityId = prompt('请输入您管理的小区ID：', '')
    if (!communityId) return
    localStorage.setItem('wx_community_id', communityId)
  }
  // 跳转微信 OAuth
  const baseUrl = 'http://dasheng.local/index.php/api/manager/wechatOAuth'
  const redirect = '/manager.html#/login'
  location.href = `${baseUrl}?community_id=${communityId}&redirect=${encodeURIComponent(redirect)}`
}

async function doRegister() {
  const f = regForm.value
  if (!f.realname) return showToast('请填写真实姓名')
  if (!f.username) return showToast('请设置用户名')
  if (!f.password || f.password.length < 6) return showToast('密码至少6位')
  loading.value = true
  const res = await api('/wechatRegister', {
    method: 'POST',
    body: JSON.stringify(f),
  })
  loading.value = false
  if (res.code === 0) {
    auth.setToken(res.data.token)
    router.replace('/dashboard')
  } else {
    showToast(res.msg || '注册失败')
  }
}
</script>

<style scoped>
.login-page{display:flex;align-items:center;justify-content:center;min-height:100vh;background:linear-gradient(135deg,#0f172a,#1e293b)}
.login-card{background:#1e293b;border:1px solid #334155;border-radius:16px;padding:40px 32px;width:90%;max-width:380px;text-align:center}
.logo{font-size:56px;margin-bottom:8px}
h1{font-size:20px;color:#f1f5f9;margin-bottom:4px}
.sub{color:#64748b;font-size:13px;margin-bottom:28px}
input{width:100%;height:48px;background:#0f172a;border:1px solid #334155;border-radius:10px;padding:0 16px;font-size:15px;margin-bottom:16px;outline:none;color:#e2e8f0;transition:border .2s}
input:focus{border-color:#10b981}
.btn-primary{width:100%;height:48px;background:linear-gradient(135deg,#10b981,#059669);color:#fff;border:none;border-radius:10px;font-size:16px;font-weight:600;cursor:pointer}
.btn-primary:disabled{opacity:.6}
.divider{display:flex;align-items:center;margin:20px 0;color:#475569;font-size:12px}
.divider::before,.divider::after{content:'';flex:1;height:1px;background:#334155}
.divider span{padding:0 12px}
.btn-wechat{width:100%;height:48px;background:#07c160;color:#fff;border:none;border-radius:10px;font-size:15px;font-weight:600;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:6px}
.btn-wechat:disabled{opacity:.6}
.wx-icon{font-size:18px}
.reg-title{color:#e2e8f0;font-size:15px;margin-bottom:16px;font-weight:600}
.back-link{display:inline-block;margin-top:16px;color:#10b981;font-size:13px;cursor:pointer;text-decoration:none}
.back-link:hover{text-decoration:underline}
</style>
