<template>
  <div class="sl-page">
    <div class="sl-hero">
      <div class="sl-icon">🏢</div>
      <h1>物业员工端</h1>
      <p>大圣物业管理系统</p>
    </div>
    <div class="sl-form">
      <select v-model="selectedCommunity" class="sl-select">
        <option :value="0">请选择小区（可选）</option>
        <option v-for="c in communities" :key="c.id" :value="c.id">{{ c.name }}</option>
      </select>
      <div class="sl-input">
        <span class="sli-icon">👤</span>
        <input v-model="username" placeholder="员工账号" />
      </div>
      <div class="sl-input">
        <span class="sli-icon">🔒</span>
        <input v-model="password" type="password" placeholder="登录密码" />
      </div>
      <button class="sl-btn" :disabled="loading" @click="handleLogin">{{ loading ? '登录中...' : '登 录' }}</button>
      <div class="sl-divider"><span>或</span></div>
      <button class="sl-wx-btn" :disabled="loading" @click="doWechatLogin">
        <span class="sli-icon">💬</span> 微信一键登录
      </button>
      <!-- 绑定已有账号 -->
      <template v-if="showBind">
        <p class="sl-bind-tip">该微信尚未绑定员工账号，请输入已有账号进行绑定</p>
        <div class="sl-input">
          <span class="sli-icon">👤</span>
          <input v-model="bindUser" placeholder="管理员账号" />
        </div>
        <div class="sl-input">
          <span class="sli-icon">🔒</span>
          <input v-model="bindPwd" type="password" placeholder="登录密码" />
        </div>
        <button class="sl-btn" :disabled="bindLoading" @click="doBind">
          {{ bindLoading ? '绑定中...' : '绑定并登录' }}
        </button>
      </template>
      <div v-if="error" class="sl-error">{{ error }}</div>
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
const loading = ref(false)
const error = ref('')
const showBind = ref(false)
const bindUser = ref('')
const bindPwd = ref('')
const bindLoading = ref(false)
const wxOpenid = ref('')
const communities = ref<any[]>([])
const selectedCommunity = ref(0)

onMounted(() => {
  const q = route.query
  // 已绑定 → callback 带 token
  if (q.wechat_token) {
    localStorage.setItem('staff_token', q.wechat_token as string)
    ElMessage.success('微信登录成功')
    router.replace('/mobile/staff/home')
    return
  }
  // 未绑定 → 进入绑定流程
  if (q.action === 'wx_bind' && q.wx_openid) {
    wxOpenid.value = q.wx_openid as string
    showBind.value = true
  }
  fetchCommunities()
})

async function handleLogin() {
  if (!username.value || !password.value) {
    error.value = '请输入账号和密码'
    return
  }
  loading.value = true
  error.value = ''
  try {
    const r = await fetch('/api/staff/login', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `username=${encodeURIComponent(username.value)}&password=${encodeURIComponent(password.value)}`,
    })
    const d = await r.json()
    if (d.code === 0) {
      localStorage.setItem('staff_token', d.data.token)
      if (selectedCommunity.value) localStorage.setItem('staff_community_id', String(selectedCommunity.value))
      ElMessage.success('登录成功')
      router.push('/mobile/staff/home')
    } else {
      error.value = d.msg || '登录失败'
    }
  } catch (e: any) {
    error.value = '网络错误'
  } finally {
    loading.value = false
  }
}

async function doWechatLogin() {
  try {
    const cid = selectedCommunity.value ? `&community_id=${selectedCommunity.value}` : ''
    const r = await fetch('/api/staff/wechatOAuth?json=1&redirect=' + encodeURIComponent('/admin/mobile/staff/login') + cid)
    const d = await r.json()
    if (d.code === 0 && d.data.oauth_url) {
      location.href = d.data.oauth_url
    } else {
      error.value = d.msg || '获取微信授权链接失败'
    }
  } catch {
    error.value = '网络请求失败'
  }
}

async function doBind() {
  if (!bindUser.value || !bindPwd.value) {
    error.value = '请输入用户名和密码'
    return
  }
  bindLoading.value = true
  error.value = ''
  try {
    const r = await fetch('/api/staff/wechatBind', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `openid=${encodeURIComponent(wxOpenid.value)}&username=${encodeURIComponent(bindUser.value)}&password=${encodeURIComponent(bindPwd.value)}`,
    })
    const d = await r.json()
    if (d.code === 0) {
      localStorage.setItem('staff_token', d.data.token)
      if (selectedCommunity.value) localStorage.setItem('staff_community_id', String(selectedCommunity.value))
      ElMessage.success('绑定成功')
      router.replace('/mobile/staff/home')
    } else {
      error.value = d.msg || '绑定失败'
    }
  } catch {
    error.value = '网络错误'
  } finally {
    bindLoading.value = false
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
.sl-page { min-height: 100vh; background: linear-gradient(180deg, #2b6cb0 0%, #2c5282 100%); display: flex; flex-direction: column; align-items: center; padding: 60px 24px; }
.sl-hero { text-align: center; margin-bottom: 40px; }
.sl-icon { font-size: 64px; margin-bottom: 8px; }
.sl-hero h1 { color: #fff; font-size: 24px; font-weight: 700; }
.sl-hero p { color: rgba(255, 255, 255, 0.7); font-size: 14px; margin-top: 4px; }
.sl-form { width: 100%; max-width: 360px; }
.sl-select { width: 100%; height: 48px; background: rgba(255,255,255,0.15); border: none; border-radius: 10px; padding: 0 16px; font-size: 15px; margin-bottom: 14px; outline: none; color: #fff; cursor: pointer; appearance: auto; }
.sl-select option { background: #2b6cb0; color: #fff; }
.sl-input { display: flex; align-items: center; background: rgba(255, 255, 255, 0.15); border-radius: 10px; padding: 0 16px; margin-bottom: 14px; height: 48px; }
.sli-icon { font-size: 18px; margin-right: 10px; }
.sl-input input { flex: 1; background: transparent; border: none; outline: none; color: #fff; font-size: 15px; height: 48px; }
.sl-input input::placeholder { color: rgba(255, 255, 255, 0.5); }
.sl-btn { width: 100%; height: 48px; background: #fff; color: #2b6cb0; border: none; border-radius: 10px; font-size: 16px; font-weight: 600; cursor: pointer; margin-top: 8px; }
.sl-error { color: #fc8181; text-align: center; margin-top: 12px; font-size: 14px; }
.sl-divider { display: flex; align-items: center; margin: 18px 0; color: rgba(255,255,255,0.4); font-size: 12px; }
.sl-divider::before, .sl-divider::after { content: ''; flex: 1; height: 1px; background: rgba(255,255,255,0.2); }
.sl-divider span { padding: 0 12px; }
.sl-wx-btn { width: 100%; height: 48px; background: #07c160; color: #fff; border: none; border-radius: 10px; font-size: 15px; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 6px; }
.sl-wx-btn:active { background: #06ad56; }
.sl-bind-tip { color: rgba(255,255,255,0.7); font-size: 13px; margin-top: 20px; margin-bottom: 8px; text-align: center; }
</style>
