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
    <!-- 负责楼栋（可折叠） -->
    <div class="building-section" v-if="profile && profile.buildings && profile.buildings.length > 0">
      <div class="section-header" @click="buildingsOpen = !buildingsOpen">
        <span class="section-title">📋 我负责的楼栋</span>
        <span class="section-arrow" :class="{ open: buildingsOpen }">{{ buildingsOpen ? '▲' : '▼' }}</span>
        <span class="building-count">{{ profile.buildings.length }}栋</span>
      </div>
      <transition name="collapse">
        <div class="building-list" v-show="buildingsOpen">
          <div class="building-item" v-for="b in profile.buildings" :key="b.id">
            <span class="building-icon">🏢</span>
            <div class="building-info">
              <strong>{{ b.name }}</strong>
              <small>{{ b.community_name }} · {{ b.floor_count }}层 · {{ b.total_rooms }}户</small>
            </div>
          </div>
        </div>
      </transition>
    </div>
    <div class="grid">
      <router-link to="/repair" class="card" @click="dismissBadge('repair')"><span>🔧</span><label>报修处理</label><b v-if="badges.repair>0">{{ badges.repair > 99 ? '99+' : badges.repair }}</b></router-link>
      <router-link to="/meter" class="card"><span>📊</span><label>抄表录入</label></router-link>
      <router-link to="/charge" class="card" @click="dismissBadge('charge')"><span>💰</span><label>移动收费</label><b v-if="badges.charge>0">{{ badges.charge > 99 ? '99+' : badges.charge }}</b></router-link>
      <router-link to="/patrol" class="card"><span>🛡️</span><label>安保巡更</label></router-link>
      <router-link to="/visitor" class="card"><span>👤</span><label>访客登记</label></router-link>
      <router-link to="/order" class="card" @click="dismissBadge('order')"><span>📋</span><label>工单管理</label><b v-if="badges.order>0">{{ badges.order > 99 ? '99+' : badges.order }}</b></router-link>
      <router-link to="/complaint" class="card" @click="dismissBadge('complaint')"><span>📢</span><label>投诉处理</label><b v-if="badges.complaint>0">{{ badges.complaint > 99 ? '99+' : badges.complaint }}</b></router-link>
      <router-link to="/profile" class="card profile-card"><span>👤</span><label>个人中心</label></router-link>
    </div>
  </div>
</template>
<script setup>
import { ref, reactive, onMounted, onUnmounted } from 'vue'
import { createApi, createAuth } from '@/shared/api.js'
const api = createApi('/api/staff', 'staff_token')
const auth = createAuth('staff_token')
const profile = ref(null)
const badges = reactive({ repair: 0, charge: 0, order: 0, complaint: 0 })
const badgesRaw = reactive({ repair: 0, charge: 0, order: 0, complaint: 0 })
const buildingsOpen = ref(false) // 楼栋列表默认收起
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
      const d = res.data
      const keys = ['repair','charge','order','complaint']
      keys.forEach(k => { badgesRaw[k] = d[k] || 0 })
      const seen = JSON.parse(localStorage.getItem('staff_badge_seen') || '{}')
      keys.forEach(k => { badges[k] = Math.max(0, (d[k] || 0) - (seen[k] || 0)) })
    }
  } catch (e) { /* 静默 */ }
}

function dismissBadge(key) {
  const seen = JSON.parse(localStorage.getItem('staff_badge_seen') || '{}')
  seen[key] = badgesRaw[key] || 0
  localStorage.setItem('staff_badge_seen', JSON.stringify(seen))
  badges[key] = 0
}

function logout() {
  // 清除所有 staff 相关的 localStorage
  localStorage.removeItem('staff_token')
  localStorage.removeItem('staff_community_id')
  localStorage.removeItem('staff_badge_seen')
  // 微信 WebView 会拦截同域 location 跳转，导致页面不刷新
  // 用 setTimeout 确保状态写入，再用 top.location 绕过微信 iframe 封装
  const url = window.location.origin + '/staff.html?logout=' + Date.now()
  setTimeout(() => {
    try { top.location.replace(url) } catch (_) { window.location.replace(url) }
  }, 50)
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
/* 负责楼栋（可折叠） */
.building-section{margin-bottom:20px;background:#fff;border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,.06);overflow:hidden}
.section-header{display:flex;align-items:center;padding:12px 14px;cursor:pointer;-webkit-tap-highlight-color:transparent}
.section-title{font-size:15px;font-weight:600;color:#1f2937;margin-bottom:0;flex:1}
.section-arrow{font-size:11px;color:#9ca3af;margin-right:6px;transition:transform .2s}
.section-arrow.open{transform:rotate(180deg)}
.building-count{font-size:12px;color:#9ca3af;background:#f3f4f6;padding:2px 8px;border-radius:10px}
.building-list{display:flex;flex-direction:column;gap:1px;padding:0 14px 10px}
.collapse-enter-active,.collapse-leave-active{transition:all .25s ease;overflow:hidden}
.collapse-enter-from,.collapse-leave-to{max-height:0;opacity:0}
.collapse-enter-to,.collapse-leave-from{max-height:600px;opacity:1}
.building-item{padding:10px 0;display:flex;align-items:center;gap:10px;border-bottom:1px solid #f3f4f6}
.building-item:last-child{border-bottom:none}
.building-icon{font-size:28px}
.building-info strong{display:block;font-size:14px;color:#1f2937}
.building-info small{font-size:12px;color:#9ca3af}
.grid{display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px}
.card{background:#fff;border-radius:12px;padding:20px 12px;text-align:center;text-decoration:none;box-shadow:0 1px 3px rgba(0,0,0,.06);transition:transform .2s;position:relative}
.card:active{transform:scale(.97)}
.card span{font-size:32px;display:block;margin-bottom:8px}
.card label{font-size:13px;color:#4b5563}
/* 卡片角标 */
.card b{position:absolute;top:8px;right:8px;min-width:20px;height:20px;line-height:20px;padding:0 6px;border-radius:10px;background:#ef4444;color:#fff;font-size:11px;font-weight:700;text-align:center;white-space:nowrap}
</style>
