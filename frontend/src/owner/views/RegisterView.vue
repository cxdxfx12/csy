<template>
  <div class="page">
    <div class="card">
      <div class="logo">🏠</div>
      <h1>业主注册</h1>
      <input v-model="form.name" placeholder="真实姓名" />
      <input v-model="form.phone" type="tel" placeholder="手机号码" maxlength="11" />
      <input v-model="form.password" type="password" placeholder="设置密码（6-20位）" />
      <select v-model="form.community_id" class="comm-select">
        <option :value="0">请选择所在小区</option>
        <option v-for="c in communities" :key="c.id" :value="c.id">{{ c.name }}</option>
      </select>
      <input v-model="form.room_no" placeholder="房号（如 1-101）" />
      <button class="btn-primary" @click="doRegister" :disabled="loading">
        {{ loading ? '注册中...' : '注 册' }}
      </button>
      <p class="link" @click="$router.push('/login')">已有账号？去登录</p>
    </div>
  </div>
</template>
<script setup>
import { reactive, ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { createApi } from '@/shared/api.js'
import { showToast } from '@/shared/utils.js'

const router = useRouter()
const api = createApi('/api/api', 'owner_token')
const communities = ref([])
const form = reactive({ name: '', phone: '', password: '', community_id: 0, room_no: '' })
const loading = ref(false)

onMounted(() => {
  fetch('/api/communityList').then(r => r.json()).then(d => {
    if (d.code === 0) communities.value = d.data || []
  }).catch(() => {})
})

async function doRegister() {
  if (!form.name || !form.phone || !form.password) return showToast('请填写完整信息')
  if (!form.community_id) return showToast('请选择所在小区')
  if (form.phone.length !== 11) return showToast('请输入正确的手机号')
  loading.value = true
  const res = await api('/register', { method: 'POST', body: JSON.stringify(form) })
  loading.value = false
  showToast(res.code === 0 ? '注册成功，请登录' : (res.msg || '注册失败'))
  if (res.code === 0) router.replace('/login')
}
</script>
<style scoped>
.page{display:flex;align-items:center;justify-content:center;min-height:100vh;background:#f5f7fa;padding:20px}
.card{background:#fff;border-radius:16px;padding:32px 28px;width:100%;max-width:380px;text-align:center;box-shadow:0 4px 16px rgba(0,0,0,.08)}
.logo{font-size:48px;margin-bottom:8px}
h1{font-size:20px;color:#1f2937;margin-bottom:20px}
input{width:100%;height:46px;border:1px solid #e5e7eb;border-radius:10px;padding:0 16px;font-size:14px;margin-bottom:14px;outline:none}
input:focus{border-color:#667eea}
.comm-select{width:100%;height:46px;border:1px solid #e5e7eb;border-radius:10px;padding:0 14px;font-size:14px;margin-bottom:14px;outline:none;background:#fff;color:#333;cursor:pointer;appearance:auto}
.comm-select:focus{border-color:#667eea}
.btn-primary{width:100%;height:46px;background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;border:none;border-radius:10px;font-size:15px;font-weight:600;cursor:pointer}
.btn-primary:disabled{opacity:.6}
.link{margin-top:14px;font-size:13px;color:#667eea;cursor:pointer}
</style>
