<template>
  <div class="login-page">
    <div class="login-card">
      <div class="logo">🐒</div>
      <h1>业主服务端</h1>
      <p class="sub">大圣智慧物业</p>
      <input v-model="phone" type="tel" placeholder="请输入手机号" maxlength="11" @keyup.enter="doLogin" />
      <input v-model="password" type="password" placeholder="请输入密码" @keyup.enter="doLogin" />
      <button class="btn-primary" @click="doLogin" :disabled="loading">
        {{ loading ? '登录中...' : '登 录' }}
      </button>
      <div class="divider"><span>或</span></div>
      <button class="btn-wechat" @click="doWechatLogin" :disabled="wcLoading">
        <span class="wc-icon">💬</span> {{ wcLoading ? '跳转中...' : '微信一键登录' }}
      </button>
      <p class="link" @click="$router.push('/register')">还没有账号？立即注册</p>
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
const api = createApi('/api/api', 'owner_token')
const auth = createAuth('owner_token')
const phone = ref('')
const password = ref('')
const loading = ref(false)
const wcLoading = ref(false)

async function doLogin() {
  if (!phone.value || !password.value) return showToast('请输入手机号和密码')
  loading.value = true
  const res = await api('/ownerLogin', { method: 'POST', body: JSON.stringify({ phone: phone.value, password: password.value }) })
  loading.value = false
  if (res.code === 0) {
    auth.setToken(res.data.token)
    router.replace('/home')
  } else {
    showToast(res.msg || '用户名或密码错误')
  }
}

// 微信登录：跳转至公众号 OAuth
async function doWechatLogin() {
  // 优先读取 URL 上或 localStorage 里的小区ID
  let communityId = route.query.cid || localStorage.getItem('wx_community_id') || ''
  if (!communityId) {
    communityId = prompt('请输入小区ID（可联系物业获取）：') || ''
    if (!communityId) return
  }
  localStorage.setItem('wx_community_id', communityId)
  wcLoading.value = true

  // 跳转到服务端 OAuth 入口，回调会回到当前页
  const oauthUrl = `http://dasheng.local/index.php/api/wechatOAuth?community_id=${communityId}&redirect=/owner.html%23/login`
  window.location.href = oauthUrl
}

// 页面挂载时检测 wechat_token（OAuth 回调带回来的）
onMounted(() => {
  const token = route.query.wechat_token
  if (token) {
    auth.setToken(token)
    // 清除 URL 上的 token 参数
    const q = { ...route.query }
    delete q.wechat_token
    router.replace({ path: '/home' })
  }
})
</script>
<style scoped>
.login-page{display:flex;align-items:center;justify-content:center;min-height:100vh;background:linear-gradient(135deg,#667eea,#764ba2)}
.login-card{background:#fff;border-radius:16px;padding:40px 32px;width:90%;max-width:380px;text-align:center;box-shadow:0 20px 60px rgba(0,0,0,.15)}
.logo{font-size:56px;margin-bottom:8px}
h1{font-size:22px;color:#1f2937;margin-bottom:4px}
.sub{color:#9ca3af;font-size:13px;margin-bottom:28px}
input{width:100%;height:48px;border:1px solid #e5e7eb;border-radius:10px;padding:0 16px;font-size:15px;margin-bottom:16px;outline:none;transition:border .2s}
input:focus{border-color:#667eea}
.btn-primary{width:100%;height:48px;background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;border:none;border-radius:10px;font-size:16px;font-weight:600;cursor:pointer}
.btn-primary:disabled{opacity:.6}
.divider{display:flex;align-items:center;margin:20px 0;color:#ccc;font-size:13px}
.divider::before,.divider::after{content:'';flex:1;height:1px;background:#e5e7eb}
.divider span{padding:0 12px}
.btn-wechat{width:100%;height:48px;background:#07c160;color:#fff;border:none;border-radius:10px;font-size:15px;font-weight:600;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:6px}
.btn-wechat:active{background:#06ad56}
.btn-wechat:disabled{opacity:.6}
.wc-icon{font-size:20px}
.link{margin-top:16px;font-size:13px;color:#667eea;cursor:pointer}
</style>
