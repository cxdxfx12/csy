<template>
  <div class="login-page">
    <div class="login-card">
      <div class="logo">🐒</div>
      <h1>物业员工端</h1>
      <p class="sub">大圣智慧物业管理平台</p>
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
      <!-- 微信绑定已有账号 -->
      <template v-if="showBind">
        <p class="bind-tip">该微信尚未绑定员工账号，请输入已有账号进行绑定</p>
        <input v-model="bindUser" type="text" placeholder="管理员账号 / 维修工手机号" />
        <input v-model="bindPwd" type="password" placeholder="登录密码" />
        <button class="btn-primary" @click="doBind" :disabled="bindLoading" style="margin-top:8px">
          {{ bindLoading ? '绑定中...' : '绑定并登录' }}
        </button>
        <p class="bind-guide">没有账号？请联系小区管理员申请开通</p>
        <button class="btn-back" @click="showBind=false;wxOpenid='';wxCid=0">返回登录</button>
      </template>
    </div>
  </div>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { createApi, createAuth } from '@/shared/api.js'
import { showToast } from '@/shared/utils.js'

const router = useRouter()
const route = useRoute()
const api = createApi('/api/staff', 'staff_token')
const auth = createAuth('staff_token')
const username = ref('')
const password = ref('')
const loading = ref(false)
const showBind = ref(false)
const bindUser = ref('')
const bindPwd = ref('')
const bindLoading = ref(false)
const wxOpenid = ref('')
const wxCid = ref(0)
const communities = ref([])
const communityId = ref(0)

onMounted(() => {
  const q = route.query
  // 已绑定 → callback 带 token
  if (q.wechat_token) {
    auth.setToken(q.wechat_token)
    router.replace('/home')
    return
  }
  // 未绑定 → 进入绑定流程
  if (q.action === 'wx_bind' && q.wx_openid) {
    wxOpenid.value = q.wx_openid
    wxCid.value = parseInt(q.wx_cid) || 0
    showBind.value = true
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
    if (communityId.value) localStorage.setItem('staff_community_id', communityId.value)
    router.replace('/home')
  } else {
    showToast(res.msg || '登录失败')
  }
}

function doWechatLogin() {
  loading.value = true
  const cid = communityId.value ? `&community_id=${communityId.value}` : ''
  fetch(`/index.php/staff/wechatOAuth?json=1&redirect=/staff.html%23/login${cid}`)
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

async function doBind() {
  if (!bindUser.value || !bindPwd.value) return showToast('请输入用户名和密码')
  bindLoading.value = true
  const res = await api('/wechatBind', {
    method: 'POST',
    body: JSON.stringify({ openid: wxOpenid.value, username: bindUser.value, password: bindPwd.value }),
  })
  bindLoading.value = false
  if (res.code === 0) {
    auth.setToken(res.data.token)
    if (communityId.value) localStorage.setItem('staff_community_id', communityId.value)
    router.replace('/home')
  } else {
    showToast(res.msg || '绑定失败')
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
.login-page{display:flex;align-items:center;justify-content:center;min-height:100vh;background:linear-gradient(135deg,#667eea,#764ba2)}
.login-card{background:#fff;border-radius:16px;padding:40px 32px;width:90%;max-width:380px;text-align:center;box-shadow:0 20px 60px rgba(0,0,0,.15)}
.logo{font-size:56px;margin-bottom:8px}
h1{font-size:22px;color:#1f2937;margin-bottom:4px}
.sub{color:#9ca3af;font-size:13px;margin-bottom:20px}
.comm-select{width:100%;height:48px;border:1px solid #e5e7eb;border-radius:10px;padding:0 14px;font-size:15px;margin-bottom:16px;outline:none;background:#fff;color:#333;cursor:pointer;appearance:auto}
.comm-select:focus{border-color:#667eea}
input{width:100%;height:48px;border:1px solid #e5e7eb;border-radius:10px;padding:0 16px;font-size:15px;margin-bottom:16px;outline:none;transition:border .2s}
input:focus{border-color:#667eea}
.btn-primary{width:100%;height:48px;background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;border:none;border-radius:10px;font-size:16px;font-weight:600;cursor:pointer;transition:opacity .2s}
.btn-primary:disabled{opacity:.6}
.divider{display:flex;align-items:center;margin:20px 0;color:#9ca3af;font-size:12px}
.divider::before,.divider::after{content:'';flex:1;height:1px;background:#e5e7eb}
.divider span{padding:0 12px}
.btn-wechat{width:100%;height:48px;background:#07c160;color:#fff;border:none;border-radius:10px;font-size:15px;font-weight:600;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:6px}
.btn-wechat:active{background:#06ad56}
.btn-wechat:disabled{opacity:.6}
.wx-icon{font-size:20px}
.bind-tip{color:#666;font-size:13px;margin-top:20px;margin-bottom:8px}
.bind-guide{color:#999;font-size:12px;margin-top:12px;text-align:center}
.btn-back{width:100%;height:40px;background:transparent;color:#999;border:1px solid #e5e7eb;border-radius:10px;font-size:14px;cursor:pointer;margin-top:8px}
.btn-back:active{background:#f3f4f6}
</style>
