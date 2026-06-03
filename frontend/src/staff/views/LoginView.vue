<template>
  <div class="login-page">
    <div class="login-card">
      <div class="logo">🐒</div>
      <h1>物业员工端</h1>
      <p class="sub">大晟智慧物业管理平台</p>
      <input v-model="username" type="text" placeholder="请输入用户名" @keyup.enter="doLogin" />
      <input v-model="password" type="password" placeholder="请输入密码" @keyup.enter="doLogin" />
      <button class="btn-primary" @click="doLogin" :disabled="loading">
        {{ loading ? '登录中...' : '登 录' }}
      </button>
    </div>
  </div>
</template>
<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { createApi, createAuth } from '@/shared/api.js'
import { showToast } from '@/shared/utils.js'

const router = useRouter()
const api = createApi('/api/staff', 'staff_token')
const auth = createAuth('staff_token')
const username = ref('')
const password = ref('')
const loading = ref(false)

async function doLogin() {
  if (!username.value || !password.value) return showToast('请输入用户名和密码')
  loading.value = true
  const res = await api('/login', { method: 'POST', body: JSON.stringify({ username: username.value, password: password.value }) })
  loading.value = false
  if (res.code === 0) {
    auth.setToken(res.data.token)
    router.replace('/home')
  } else {
    showToast(res.msg || '登录失败')
  }
}
</script>
<style scoped>
.login-page{display:flex;align-items:center;justify-content:center;min-height:100vh;background:linear-gradient(135deg,#667eea,#764ba2)}
.login-card{background:#fff;border-radius:16px;padding:40px 32px;width:90%;max-width:380px;text-align:center;box-shadow:0 20px 60px rgba(0,0,0,.15)}
.logo{font-size:56px;margin-bottom:8px}
h1{font-size:22px;color:#1f2937;margin-bottom:4px}
.sub{color:#9ca3af;font-size:13px;margin-bottom:28px}
input{width:100%;height:48px;border:1px solid #e5e7eb;border-radius:10px;padding:0 16px;font-size:15px;margin-bottom:16px;outline:none;transition:border .2s}
input:focus{border-color:#667eea}
.btn-primary{width:100%;height:48px;background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;border:none;border-radius:10px;font-size:16px;font-weight:600;cursor:pointer;transition:opacity .2s}
.btn-primary:disabled{opacity:.6}
</style>
