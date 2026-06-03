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
        <span class="community-tag" v-if="profile.community_name">{{ profile.community_name }}</span>
      </div>
    </div>
    <div class="grid">
      <router-link to="/repair" class="card"><span>🔧</span><label>报修处理</label><b v-if="badges.repair>0">{{ badges.repair > 99 ? '99+' : badges.repair }}</b></router-link>
      <router-link to="/meter" class="card"><span>📊</span><label>抄表录入</label></router-link>
      <router-link to="/charge" class="card"><span>💰</span><label>移动收费</label><b v-if="badges.charge>0">{{ badges.charge > 99 ? '99+' : badges.charge }}</b></router-link>
      <router-link to="/patrol" class="card"><span>🛡️</span><label>安保巡更</label></router-link>
      <router-link to="/visitor" class="card"><span>👤</span><label>访客登记</label></router-link>
      <router-link to="/order" class="card"><span>📋</span><label>工单管理</label><b v-if="badges.order>0">{{ badges.order > 99 ? '99+' : badges.order }}</b></router-link>
    </div>
  </div>
</template>
<script setup>
import { ref, reactive, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { createApi, createAuth } from '@/shared/api.js'
const router = useRouter()
const api = createApi('/api/staff', 'staff_token')
const auth = createAuth('staff_token')
const profile = ref(null)
const badges = reactive({ repair: 0, charge: 0, order: 0 })
let timer = null

onMounted(async () => {
  const res = await api('/profile/info')
  if (res.code === 0) profile.value = res.data
  fetchBadges()
  timer = setInterval(fetchBadges, 30000)
})

onUnmounted(() => {
  if (timer) clearInterval(timer)
})

async function fetchBadges() {
  try {
    const res = await api('/badge/counts')
    if (res.code === 0) {
      badges.repair = res.data.repair || 0
      badges.charge = res.data.charge || 0
      badges.order  = res.data.order  || 0
    }
  } catch (e) { /* 静默 */ }
}

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
.community-tag{display:inline-block;margin-top:4px;padding:2px 10px;background:#e0f2fe;color:#0369a1;font-size:12px;border-radius:10px;font-weight:500}
.grid{display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px}
.card{background:#fff;border-radius:12px;padding:20px 12px;text-align:center;text-decoration:none;box-shadow:0 1px 3px rgba(0,0,0,.06);transition:transform .2s;position:relative}
.card:active{transform:scale(.97)}
.card span{font-size:32px;display:block;margin-bottom:8px}
.card label{font-size:13px;color:#4b5563}
/* 卡片角标 */
.card b{position:absolute;top:8px;right:8px;min-width:20px;height:20px;line-height:20px;padding:0 6px;border-radius:10px;background:#ef4444;color:#fff;font-size:11px;font-weight:700;text-align:center;white-space:nowrap}
</style>
