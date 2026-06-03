<template>
  <div class="page">
    <header>
      <h1>物业员工端</h1>
      <button class="btn-sm" @click="logout">退出</button>
    </header>
    <div class="user-card" v-if="profile">
      <span class="avatar">👷</span>
      <div>
        <strong>{{ profile.realname || profile.username }}</strong>
        <small>{{ profile.phone || '--' }}</small>
      </div>
    </div>
    <div class="grid">
      <router-link to="/repair" class="card"><span>🔧</span><label>报修处理</label></router-link>
      <router-link to="/meter" class="card"><span>📊</span><label>抄表录入</label></router-link>
      <router-link to="/charge" class="card"><span>💰</span><label>移动收费</label></router-link>
      <router-link to="/patrol" class="card"><span>🛡️</span><label>安保巡更</label></router-link>
      <router-link to="/visitor" class="card"><span>👤</span><label>访客登记</label></router-link>
      <router-link to="/order" class="card"><span>📋</span><label>工单管理</label></router-link>
    </div>
  </div>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { createApi, createAuth } from '@/shared/api.js'
const router = useRouter()
const api = createApi('/api/staff', 'staff_token')
const auth = createAuth('staff_token')
const profile = ref(null)

onMounted(async () => {
  const res = await api('/profile/info')
  if (res.code === 0) profile.value = res.data
})

function logout() {
  auth.removeToken()
  router.replace('/login')
}
</script>
<style scoped>
.page{padding:16px}
header{display:flex;justify-content:space-between;align-items:center;margin-bottom:16px}
header h1{font-size:20px;color:#1f2937}
.btn-sm{background:none;border:1px solid #e5e7eb;padding:6px 14px;border-radius:6px;font-size:13px;color:#6b7280;cursor:pointer}
.user-card{background:#fff;border-radius:12px;padding:16px;display:flex;align-items:center;gap:12px;margin-bottom:20px;box-shadow:0 1px 3px rgba(0,0,0,.06)}
.avatar{font-size:36px}
.user-card strong{display:block;font-size:16px}
.user-card small{color:#9ca3af;font-size:13px}
.grid{display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px}
.card{background:#fff;border-radius:12px;padding:20px 12px;text-align:center;text-decoration:none;box-shadow:0 1px 3px rgba(0,0,0,.06);transition:transform .2s}
.card:active{transform:scale(.97)}
.card span{font-size:32px;display:block;margin-bottom:8px}
.card label{font-size:13px;color:#4b5563}
</style>
