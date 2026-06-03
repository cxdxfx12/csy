<template>
  <div class="sl-page">
    <div class="sl-hero">
      <div class="sl-icon">🏢</div>
      <h1>物业员工端</h1>
      <p>大圣物业管理系统</p>
    </div>
    <div class="sl-form">
      <div class="sl-input">
        <span class="sli-icon">👤</span>
        <input v-model="username" placeholder="员工账号" />
      </div>
      <div class="sl-input">
        <span class="sli-icon">🔒</span>
        <input v-model="password" type="password" placeholder="登录密码" />
      </div>
      <button class="sl-btn" :disabled="loading" @click="handleLogin">{{ loading ? '登录中...' : '🚀 登 录' }}</button>
      <div v-if="error" class="sl-error">{{ error }}</div>
    </div>
  </div>
</template>
<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'

const router = useRouter()
const username = ref('admin')
const password = ref('admin123')
const loading = ref(false)
const error = ref('')

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
</script>
<style scoped>
.sl-page { min-height: 100vh; background: linear-gradient(180deg, #2b6cb0 0%, #2c5282 100%); display: flex; flex-direction: column; align-items: center; padding: 60px 24px; }
.sl-hero { text-align: center; margin-bottom: 40px; }
.sl-icon { font-size: 64px; margin-bottom: 8px; }
.sl-hero h1 { color: #fff; font-size: 24px; font-weight: 700; }
.sl-hero p { color: rgba(255, 255, 255, 0.7); font-size: 14px; margin-top: 4px; }
.sl-form { width: 100%; max-width: 360px; }
.sl-input { display: flex; align-items: center; background: rgba(255, 255, 255, 0.15); border-radius: 10px; padding: 0 16px; margin-bottom: 14px; height: 48px; }
.sli-icon { font-size: 18px; margin-right: 10px; }
.sl-input input { flex: 1; background: transparent; border: none; outline: none; color: #fff; font-size: 15px; height: 48px; }
.sl-input input::placeholder { color: rgba(255, 255, 255, 0.5); }
.sl-btn { width: 100%; height: 48px; background: #fff; color: #2b6cb0; border: none; border-radius: 10px; font-size: 16px; font-weight: 600; cursor: pointer; margin-top: 8px; }
.sl-error { color: #fc8181; text-align: center; margin-top: 12px; font-size: 14px; }
</style>
