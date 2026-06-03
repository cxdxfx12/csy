<template>
  <div class="ol-page">
    <div class="ol-hero">
      🏠
      <h1>业主服务</h1>
      <p>大圣物业 · 智慧社区</p>
    </div>
    <div class="ol-form">
      <div class="ol-input">
        <span>📱</span>
        <input v-model="phone" placeholder="手机号" />
      </div>
      <div class="ol-input">
        <span>🔑</span>
        <input v-model="password" placeholder="密码" type="password" />
      </div>
      <button class="ol-btn" style="background:#e2e8f0;color:#4a5568;margin-bottom:14px;" @click="goRegister">没有账号？去注册</button>
      <button class="ol-btn" :disabled="loading" @click="login">{{ loading ? '登录中...' : '🚀 登 录' }}</button>
      <div v-if="err" class="ol-err">{{ err }}</div>
    </div>
  </div>
</template>
<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'

const router = useRouter()
const phone = ref('13800138000')
const password = ref('123456')
const loading = ref(false)
const err = ref('')

async function login() {
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
</script>
<style scoped>
.ol-page { min-height: 100vh; background: #f7f8fc; display: flex; flex-direction: column; align-items: center; padding: 80px 24px; }
.ol-hero { text-align: center; margin-bottom: 36px; }
.ol-hero h1 { font-size: 24px; font-weight: 700; color: #1a202c; margin-top: 8px; }
.ol-hero p { color: #a0aec0; font-size: 13px; margin-top: 4px; }
.ol-hero > div:first-child { font-size: 56px; }
.ol-form { width: 100%; max-width: 340px; }
.ol-input { display: flex; align-items: center; background: #fff; border-radius: 10px; padding: 0 14px; margin-bottom: 14px; height: 48px; gap: 10px; border: 1px solid #e2e8f0; }
.ol-input span { font-size: 18px; }
.ol-input input { flex: 1; background: transparent; border: none; outline: none; color: #1a202c; font-size: 15px; height: 48px; }
.ol-btn { width: 100%; height: 48px; background: linear-gradient(135deg, #3182ce, #2b6cb0); color: #fff; border: none; border-radius: 10px; font-size: 16px; font-weight: 600; cursor: pointer; }
.ol-err { color: #e53e3e; text-align: center; margin-top: 12px; }
</style>
