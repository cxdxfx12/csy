<template>
  <div class="login-page">
    <div class="login-card">
      <div class="logo">🐒</div>
      <h1>大圣物业管理平台</h1>
      <p class="sub">小区经理工作台</p>

      <!-- 用户名密码登录 -->
      <div v-if="!showRegister">
        <select v-model="communityId" class="comm-select">
          <option :value="0">请选择小区（可选）</option>
          <option v-for="c in communities" :key="c.id" :value="c.id">{{ c.name }}</option>
        </select>
        <input v-model="username" type="text" placeholder="请输入用户名" @keyup.enter="doLogin" />
        <input v-model="password" type="password" placeholder="请输入密码" @keyup.enter="doLogin" />
        <button class="btn-primary" @click="doLogin" :disabled="loading">
          {{ loading ? '登录中...' : '登 录' }}
        </button>
        <div class="divider"><span>或</span></div>
        <button class="btn-wechat" @click="doWechatLogin" :disabled="loading">
          <span class="wx-icon">💬</span> {{ loading ? '跳转中...' : '微信一键登录' }}
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
const communities = ref([])
const communityId = ref(0)

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
    regForm.value.community_id = parseInt(q.wx_cid) || communityId.value || 0
    showRegister.value = true
  }
  fetchCommunities()
})

async function doLogin() {
  if (!username.value || !password.value) return showToast('请输入用户名和密码')
  loading.value = true
  const res = await api('/login', { method: 'POST', body: JSON.stringify({ username: username.value, password: password.value }) })
  loading.value = false
  if (res.code === 0) {
    auth.setToken(res.data.token)
    if (communityId.value) localStorage.setItem('manager_community_id', communityId.value)
    router.replace('/dashboard')
  } else {
    showToast(res.msg || '登录失败')
  }
}

function doWechatLogin() {
  loading.value = true
  // 跳转微信 OAuth，后端自动检测小区（前端先获取 OAuth URL 再直接跳转，避免 302 跨域问题）
  const baseUrl = `${window.location.origin}/index.php/api/manager/wechatOAuth`
  const cid = communityId.value ? `&community_id=${communityId.value}` : ''
  const redirect = '/manager.html#/login'
  fetch(`${baseUrl}?json=1&redirect=${encodeURIComponent(redirect)}${cid}`)
    .then(res => res.json())
    .then(data => {
      if (data.code === 0 && data.data.oauth_url) {
        window.location.href = data.data.oauth_url
      } else {
        loading.value = false
        showToast(data.msg || '获取微信授权链接失败', 4000)
      }
    })
    .catch(() => {
      loading.value = false
      showToast('网络请求失败', 4000)
    })
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

async function fetchCommunities() {
  try {
    const r = await fetch('/api/communityList')
    const d = await r.json()
    if (d.code === 0) communities.value = d.data
  } catch {}
}
</script>

<style scoped>
.login-page{display:flex;align-items:center;justify-content:center;min-height:100vh;background:radial-gradient(ellipse at 30% 20%,#312e8140 0%,transparent 50%),radial-gradient(ellipse at 70% 80%,#1e1b4b60 0%,transparent 50%),#0a0c1a}
.login-card{background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.08);border-radius:24px;padding:44px 32px;width:90%;max-width:380px;text-align:center;backdrop-filter:blur(30px);box-shadow:0 25px 80px rgba(0,0,0,.4),0 0 60px rgba(99,102,241,.08)}
.logo{font-size:56px;margin-bottom:8px;filter:drop-shadow(0 4px 12px rgba(99,102,241,.3))}
h1{font-size:22px;background:linear-gradient(135deg,#e0e7ff,#c4b5fd);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;margin-bottom:4px;font-weight:700}
.sub{color:#64748b;font-size:13px;margin-bottom:20px}
.comm-select{width:100%;height:50px;background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.1);border-radius:12px;padding:0 16px;font-size:15px;margin-bottom:16px;outline:none;color:#e2e8f0;cursor:pointer;appearance:auto;transition:all .2s}
.comm-select:focus{border-color:#818cf8;box-shadow:0 0 0 3px rgba(129,140,248,.1)}
.comm-select option{background:#1e1b4b;color:#e2e8f0}
input{width:100%;height:50px;background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.1);border-radius:12px;padding:0 16px;font-size:15px;margin-bottom:16px;outline:none;color:#e2e8f0;transition:all .2s}
input::placeholder{color:#475569}
input:focus{border-color:#818cf8;box-shadow:0 0 0 3px rgba(129,140,248,.1)}
.btn-primary{width:100%;height:50px;background:linear-gradient(135deg,#6366f1,#8b5cf6);color:#fff;border:none;border-radius:12px;font-size:16px;font-weight:600;cursor:pointer;transition:all .2s;box-shadow:0 4px 20px rgba(99,102,241,.35)}
.btn-primary:hover{transform:translateY(-1px);box-shadow:0 6px 25px rgba(99,102,241,.45)}
.btn-primary:disabled{opacity:.5;transform:none}
.divider{display:flex;align-items:center;margin:20px 0;color:#475569;font-size:12px}
.divider::before,.divider::after{content:'';flex:1;height:1px;background:rgba(255,255,255,.08)}
.divider span{padding:0 12px}
.btn-wechat{width:100%;height:50px;background:linear-gradient(135deg,#07c160,#06ad56);color:#fff;border:none;border-radius:12px;font-size:15px;font-weight:600;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:6px;transition:all .2s;box-shadow:0 4px 16px rgba(7,193,96,.3)}
.btn-wechat:hover{transform:translateY(-1px);box-shadow:0 6px 20px rgba(7,193,96,.4)}
.btn-wechat:disabled{opacity:.5;transform:none}
.wx-icon{font-size:18px}
.reg-title{color:#cbd5e1;font-size:15px;margin-bottom:16px;font-weight:600}
.back-link{display:inline-block;margin-top:16px;color:#818cf8;font-size:13px;cursor:pointer;text-decoration:none;transition:color .2s}
.back-link:hover{color:#a5b4fc}
</style>
