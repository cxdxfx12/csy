<template>
  <div class="ml-page">
    <div class="ml-hero">
      📊
      <h1>领导驾驶舱</h1>
      <p>大圣物业 · 管理后台</p>
    </div>
    <div class="ml-form">
      <select v-model="selectedCommunity" class="ml-select">
        <option :value="0">请选择小区（可选）</option>
        <option v-for="c in communities" :key="c.id" :value="c.id">{{ c.name }}</option>
      </select>
      <div class="ml-input">
        <span>👤</span>
        <input v-model="u" placeholder="经理账号" />
      </div>
      <div class="ml-input">
        <span>🔒</span>
        <input v-model="p" type="password" placeholder="密码" />
      </div>
      <button class="ml-btn" :disabled="loading" @click="login">{{ loading ? '登录中...' : '登 录' }}</button>
      <div class="ml-divider"><span>或</span></div>
      <button class="ml-wx-btn" :disabled="loading" @click="doWechatLogin">
        <span>💬</span> 微信一键登录
      </button>
      <!-- 微信注册 -->
      <template v-if="showRegister">
        <p class="ml-reg-tip">该微信尚未绑定，请注册小区经理账号</p>
        <div class="ml-input"><span>👤</span><input v-model="regName" placeholder="真实姓名" /></div>
        <div class="ml-input"><span>📱</span><input v-model="regPhone" placeholder="手机号（选填）" /></div>
        <div class="ml-input"><span>🔑</span><input v-model="regUser" placeholder="设置用户名" /></div>
        <div class="ml-input"><span>🔒</span><input v-model="regPwd" type="password" placeholder="设置密码" /></div>
        <button class="ml-btn" :disabled="regLoading" @click="doRegister" style="margin-top:8px">
          {{ regLoading ? '注册中...' : '注册并登录' }}
        </button>
      </template>
      <div v-if="err" class="ml-err">{{ err }}</div>
    </div>
  </div>
</template>
<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { ElMessage } from 'element-plus'

const router = useRouter()
const route = useRoute()
const u = ref('')
const p = ref('')
const loading = ref(false)
const err = ref('')
const showRegister = ref(false)
const wxOpenid = ref('')
const wxCid = ref(0)
const communities = ref<any[]>([])
const selectedCommunity = ref(0)
const regName = ref('')
const regPhone = ref('')
const regUser = ref('')
const regPwd = ref('')
const regLoading = ref(false)

onMounted(() => {
  const q = route.query
  // 已绑定 → callback 带 token
  if (q.wechat_token) {
    localStorage.setItem('manager_token', q.wechat_token as string)
    ElMessage.success('微信登录成功')
    router.replace('/mobile/manager/dashboard')
    return
  }
  // 未绑定 → 进入注册流程
  if (q.action === 'wx_register' && q.wx_openid) {
    wxOpenid.value = q.wx_openid as string
    wxCid.value = parseInt((q.wx_cid as string) || '0') || selectedCommunity.value
    showRegister.value = true
  }
  fetchCommunities()
})

async function login() {
  if (!u.value || !p.value) {
    err.value = '请输入账号和密码'
    return
  }
  loading.value = true
  err.value = ''
  try {
    const r = await fetch('/api/manager/login', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `username=${encodeURIComponent(u.value)}&password=${encodeURIComponent(p.value)}`,
    })
    const d = await r.json()
    if (d.code === 0) {
      localStorage.setItem('manager_token', d.data.token)
      if (selectedCommunity.value) localStorage.setItem('manager_community_id', String(selectedCommunity.value))
      ElMessage.success('登录成功')
      router.push('/mobile/manager/dashboard')
    } else {
      err.value = d.msg || '登录失败'
    }
  } catch (e: any) {
    err.value = '网络错误'
  } finally {
    loading.value = false
  }
}

async function doWechatLogin() {
  try {
    const cid = selectedCommunity.value ? `&community_id=${selectedCommunity.value}` : ''
    const r = await fetch('/api/manager/wechatOAuth?json=1&redirect=' + encodeURIComponent('/admin/mobile/manager/login') + cid)
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

async function doRegister() {
  if (!regName.value) { err.value = '请填写真实姓名'; return }
  if (!regUser.value) { err.value = '请设置用户名'; return }
  if (!regPwd.value || regPwd.value.length < 6) { err.value = '密码至少6位'; return }
  regLoading.value = true
  err.value = ''
  try {
    const r = await fetch('/api/manager/wechatRegister', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({
        openid: wxOpenid.value,
        community_id: String(wxCid.value),
        realname: regName.value,
        phone: regPhone.value,
        username: regUser.value,
        password: regPwd.value,
      }).toString(),
    })
    const d = await r.json()
    if (d.code === 0) {
      localStorage.setItem('manager_token', d.data.token)
      if (selectedCommunity.value) localStorage.setItem('manager_community_id', String(selectedCommunity.value))
      ElMessage.success('注册成功')
      router.replace('/mobile/manager/dashboard')
    } else {
      err.value = d.msg || '注册失败'
    }
  } catch {
    err.value = '网络错误'
  } finally {
    regLoading.value = false
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
.ml-page { min-height: 100vh; background: linear-gradient(135deg, #1a202c, #2d3748); display: flex; flex-direction: column; align-items: center; padding: 80px 24px; }
.ml-hero { text-align: center; margin-bottom: 36px; font-size: 48px; }
.ml-hero h1 { color: #fff; font-size: 22px; font-weight: 700; margin-top: 8px; }
.ml-hero p { color: rgba(255, 255, 255, 0.6); font-size: 13px; margin-top: 4px; }
.ml-form { width: 100%; max-width: 340px; }
.ml-select { width: 100%; height: 48px; background: rgba(255,255,255,0.08); border: none; border-radius: 10px; padding: 0 14px; font-size: 15px; margin-bottom: 14px; outline: none; color: #fff; cursor: pointer; appearance: auto; }
.ml-select option { background: #1a202c; color: #fff; }
.ml-input { display: flex; align-items: center; background: rgba(255, 255, 255, 0.08); border-radius: 10px; padding: 0 14px; margin-bottom: 14px; height: 48px; gap: 10px; }
.ml-input span { font-size: 18px; }
.ml-input input { flex: 1; background: transparent; border: none; outline: none; color: #fff; font-size: 15px; height: 48px; }
.ml-input input::placeholder { color: rgba(255, 255, 255, 0.4); }
.ml-btn { width: 100%; height: 48px; background: linear-gradient(135deg, #3182ce, #2b6cb0); color: #fff; border: none; border-radius: 10px; font-size: 16px; font-weight: 600; cursor: pointer; }
.ml-err { color: #fc8181; text-align: center; margin-top: 12px; }
.ml-divider { display: flex; align-items: center; margin: 18px 0; color: rgba(255,255,255,0.3); font-size: 12px; }
.ml-divider::before, .ml-divider::after { content: ''; flex: 1; height: 1px; background: rgba(255,255,255,0.15); }
.ml-divider span { padding: 0 12px; }
.ml-wx-btn { width: 100%; height: 48px; background: #07c160; color: #fff; border: none; border-radius: 10px; font-size: 15px; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 6px; }
.ml-wx-btn:active { background: #06ad56; }
.ml-reg-tip { color: rgba(255,255,255,0.6); font-size: 13px; margin-top: 20px; margin-bottom: 8px; text-align: center; }
</style>
