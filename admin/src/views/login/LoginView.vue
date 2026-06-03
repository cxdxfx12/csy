<template>
  <div class="login-page">
    <div class="login-card">
      <div class="login-left">
        <div class="brand">
          <div class="brand-icon">🏢</div>
          <h1>大圣物业管理平台</h1>
          <p>杭州喵喵至家网络有限公司 · 智慧社区物业管理平台</p>
        </div>
      </div>
      <div class="login-right">
        <h2>欢迎回来</h2>
        <p class="subtitle">请登录您的管理账户</p>
        <div>
          <div style="margin-bottom:16px;"><input v-model="username" placeholder="管理员账号" class="ni" /></div>
          <div style="margin-bottom:16px;"><input v-model="password" type="password" placeholder="登录密码" class="ni" /></div>
          <button class="lb" :disabled="loading" @click="handleLogin">{{ loading ? '登录中...' : '🚀 登 录' }}</button>
          <div v-if="error" style="color:#e53e3e;margin-top:12px;text-align:center;font-size:14px;">{{ error }}</div>
        </div>
      </div>
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
  error.value = ''
  loading.value = true
  try {
    const res = await fetch('/api/admin/login', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `username=${encodeURIComponent(username.value)}&password=${encodeURIComponent(password.value)}`,
    })
    const data = await res.json()
    if (data.code === 0) {
      localStorage.setItem('admin_token', data.data.token)
      ElMessage.success('登录成功')
      router.push('/')
    } else {
      error.value = data.msg || '登录失败'
    }
  } catch (e: any) {
    error.value = '网络请求失败: ' + (e.message || '')
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.login-page { height:100vh;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#2b6cb0,#2c5282); }
.login-card { display:flex;background:#fff;border-radius:20px;box-shadow:0 25px 50px rgba(0,0,0,0.15);overflow:hidden;width:800px;min-height:480px; }
.login-left { flex:1;background:linear-gradient(135deg,#2b6cb0,#3182ce);padding:40px;display:flex;flex-direction:column;justify-content:center; }
.brand-icon { font-size:48px;margin-bottom:8px; }
.brand h1 { color:#fff;font-size:22px;font-weight:700;margin-bottom:4px; }
.brand p { color:rgba(255,255,255,0.7);font-size:13px; }
.login-right { width:360px;padding:40px;display:flex;flex-direction:column;justify-content:center; }
.login-right h2 { font-size:22px;font-weight:700;color:#1a202c;margin-bottom:4px; }
.subtitle { color:#a0aec0;font-size:14px;margin-bottom:24px; }
.ni { width:100%;height:40px;border:1px solid #d9d9d9;border-radius:6px;padding:0 12px;font-size:14px;outline:none;box-sizing:border-box; }
.ni:focus { border-color:#3182ce;box-shadow:0 0 0 3px rgba(49,130,206,0.1); }
.lb { width:100%;height:44px;background:linear-gradient(135deg,#2b6cb0,#3182ce);color:#fff;border:none;border-radius:10px;font-size:16px;cursor:pointer; }
.lb:hover { box-shadow:0 4px 14px rgba(43,108,176,0.3); }
.lb:disabled { opacity:0.6;cursor:not-allowed; }
</style>
