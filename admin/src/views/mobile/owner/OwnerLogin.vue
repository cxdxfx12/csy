<template>
  <div class="ol-page">
    <div class="ol-hero">
      🏠
      <h1>业主服务</h1>
      <p>大圣物业 · 智慧社区</p>
    </div>
    <div class="ol-form">
      <select v-model="selectedCommunity" class="ol-select">
        <option :value="0">请选择小区（可选）</option>
        <option v-for="c in communities" :key="c.id" :value="c.id">{{ c.name }}</option>
      </select>
      <div class="ol-input">
        <span>📱</span>
        <input v-model="phone" placeholder="手机号" />
      </div>
      <div class="ol-input">
        <span>🔑</span>
        <input v-model="password" placeholder="密码" type="password" />
      </div>
      <button class="ol-btn" style="background:#e2e8f0;color:#4a5568;margin-bottom:10px;" @click="goRegister">没有账号？去注册</button>
      <button class="ol-btn" :disabled="loading" @click="login">{{ loading ? '登录中...' : '登 录' }}</button>
      <div class="ol-divider"><span>或</span></div>
      <button class="ol-wx-btn" :disabled="loading" @click="doWechatLogin">
        <span>💬</span> 微信一键登录
      </button>
      <div v-if="err" class="ol-err">{{ err }}</div>
    </div>
  </div>
</template>
<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { ElMessage } from 'element-plus'

const router = useRouter()
const route = useRoute()
const phone = ref('')
const password = ref('')
const loading = ref(false)
const err = ref('')
const communities = ref<any[]>([])
const selectedCommunity = ref(0)

onMounted(() => {
  const q = route.query
  // 微信 OAuth 回调带回 token（自动注册或已有账号）
  if (q.wechat_token) {
    localStorage.setItem('owner_token', q.wechat_token as string)
    ElMessage.success('微信登录成功')
    router.replace('/mobile/owner/home')
  }
  fetchCommunities()
})

async function login() {
  if (!phone.value || !password.value) {
    err.value = '请输入手机号和密码'
    return
  }
  loading.value = true
  err.value = ''
  try {
    const r = await fetch('/api/login', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `phone=${encodeURIComponent(phone.value)}&password=${encodeURIComponent(password.value)}`,
    })
    const d = await r.json()
    if (d.code === 0) {
      localStorage.setItem('owner_token', d.data.token)
      if (selectedCommunity.value) localStorage.setItem('owner_community_id', String(selectedCommunity.value))
      ElMessage.success('登录成功')
      router.push('/mobile/owner/home')
    } else {
      err.value = d.msg || '登录失败'
    }
  } catch (e: any) {
    err.value = '网络错误'
  } finally {
    loading.value = false
  }
}

function goRegister() {
  router.push('/mobile/owner/register')
}

async function doWechatLogin() {
  try {
    const cid = selectedCommunity.value ? `&community_id=${selectedCommunity.value}` : ''
    const r = await fetch('/api/wechatOAuth?json=1&redirect=' + encodeURIComponent('/admin/mobile/owner/login') + cid)
    const d = await r.json()
    if (d.code === 0 && d.data.oauth_url) {
      location.href = d.data.oauth_url
    } else {
      err.value = d.msg || '获取微信授权链接失败'
    }
  } catch {
    err.value = '网络请求失败'
  }
}

async function fetchCommunities() {
  try {
    const r = await fetch('/api/communityList')
    const d = await r.json()
    if (d.code === 0) communities.value = d.data || []
  } catch {}
}
</script>
<style scoped>
.ol-page { min-height: 100vh; background: #f7f8fc; display: flex; flex-direction: column; align-items: center; padding: 80px 24px; }
.ol-hero { text-align: center; margin-bottom: 36px; }
.ol-hero h1 { font-size: 24px; font-weight: 700; color: #1a202c; margin-top: 8px; }
.ol-hero p { color: #a0aec0; font-size: 13px; margin-top: 4px; }
.ol-hero > div:first-child { font-size: 56px; }
.ol-form { width: 100%; max-width: 340px; }
.ol-select { width: 100%; height: 48px; background: #fff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 0 14px; font-size: 15px; margin-bottom: 14px; outline: none; color: #333; cursor: pointer; appearance: auto; }
.ol-input { display: flex; align-items: center; background: #fff; border-radius: 10px; padding: 0 14px; margin-bottom: 14px; height: 48px; gap: 10px; border: 1px solid #e2e8f0; }
.ol-input span { font-size: 18px; }
.ol-input input { flex: 1; background: transparent; border: none; outline: none; color: #1a202c; font-size: 15px; height: 48px; }
.ol-btn { width: 100%; height: 48px; background: linear-gradient(135deg, #3182ce, #2b6cb0); color: #fff; border: none; border-radius: 10px; font-size: 16px; font-weight: 600; cursor: pointer; }
.ol-err { color: #e53e3e; text-align: center; margin-top: 12px; }
.ol-divider { display: flex; align-items: center; margin: 16px 0; color: #a0aec0; font-size: 12px; }
.ol-divider::before, .ol-divider::after { content: ''; flex: 1; height: 1px; background: #e2e8f0; }
.ol-divider span { padding: 0 12px; }
.ol-wx-btn { width: 100%; height: 48px; background: #07c160; color: #fff; border: none; border-radius: 10px; font-size: 15px; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 6px; margin-top: 10px; }
.ol-wx-btn:active { background: #06ad56; }
</style>
