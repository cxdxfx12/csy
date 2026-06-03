<template>
  <div class="ml-page">
    <div class="ml-hero">
      📊
      <h1>领导驾驶舱</h1>
      <p>大圣物业 · 管理后台</p>
    </div>
    <div class="ml-form">
      <div class="ml-input">
        <span>👤</span>
        <input v-model="u" placeholder="经理账号" />
      </div>
      <div class="ml-input">
        <span>🔒</span>
        <input v-model="p" type="password" placeholder="密码" />
      </div>
      <button class="ml-btn" :disabled="loading" @click="login">{{ loading ? '登录中...' : '🚀 登 录' }}</button>
      <div v-if="err" class="ml-err">{{ err }}</div>
    </div>
  </div>
</template>
<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'

const router = useRouter()
const u = ref('admin')
const p = ref('admin123')
const loading = ref(false)
const err = ref('')

async function login() {
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
</script>
<style scoped>
.ml-page { min-height: 100vh; background: linear-gradient(135deg, #1a202c, #2d3748); display: flex; flex-direction: column; align-items: center; padding: 80px 24px; }
.ml-hero { text-align: center; margin-bottom: 36px; font-size: 48px; }
.ml-hero h1 { color: #fff; font-size: 22px; font-weight: 700; margin-top: 8px; }
.ml-hero p { color: rgba(255, 255, 255, 0.6); font-size: 13px; margin-top: 4px; }
.ml-form { width: 100%; max-width: 340px; }
.ml-input { display: flex; align-items: center; background: rgba(255, 255, 255, 0.08); border-radius: 10px; padding: 0 14px; margin-bottom: 14px; height: 48px; gap: 10px; }
.ml-input span { font-size: 18px; }
.ml-input input { flex: 1; background: transparent; border: none; outline: none; color: #fff; font-size: 15px; height: 48px; }
.ml-input input::placeholder { color: rgba(255, 255, 255, 0.4); }
.ml-btn { width: 100%; height: 48px; background: linear-gradient(135deg, #3182ce, #2b6cb0); color: #fff; border: none; border-radius: 10px; font-size: 16px; font-weight: 600; cursor: pointer; }
.ml-err { color: #fc8181; text-align: center; margin-top: 12px; }
</style>
