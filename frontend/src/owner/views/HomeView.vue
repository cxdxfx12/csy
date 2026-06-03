<template>
  <div class="page">
    <header>
      <h1>业主服务</h1>
      <button class="btn-sm" @click="logout">退出</button>
    </header>
    <div class="user-card" v-if="profile">
      <span class="avatar">👤</span>
      <div>
        <strong>{{ profile.realname || profile.userInfo?.realname || profile.name || '业主' }}</strong>
        <small>{{ profile.phone || profile.userInfo?.phone || '--' }}</small>
        <span class="community-tag" v-if="profile.community_name">{{ profile.community_name }}</span>
      </div>
    </div>
    <div class="banner" v-if="banners.length">
      <div class="banner-text">{{ banners[0]?.title || '欢迎使用智慧物业' }}</div>
    </div>
    <div class="grid">
      <router-link to="/room" class="card"><span>🏢</span><label>我的房产</label></router-link>
      <router-link to="/bill" class="card"><span>💰</span><label>物业账单</label><b v-if="badges.bill>0">{{ badges.bill > 99 ? '99+' : badges.bill }}</b></router-link>
      <router-link to="/repair" class="card"><span>🔧</span><label>报事报修</label><b v-if="badges.repair>0">{{ badges.repair > 99 ? '99+' : badges.repair }}</b></router-link>
      <router-link to="/complaint" class="card"><span>📝</span><label>投诉建议</label></router-link>
      <router-link to="/notice" class="card"><span>📢</span><label>社区公告</label><b v-if="badges.notice>0">{{ badges.notice > 99 ? '99+' : badges.notice }}</b></router-link>
      <router-link to="/visitor" class="card"><span>👤</span><label>访客邀请</label></router-link>
    </div>
    <div class="notice-list" v-if="notices.length">
      <h3>最新公告</h3>
      <div v-for="n in notices.slice(0,3)" :key="n.id" class="n-item">{{ n.title }}</div>
    </div>
  </div>
</template>
<script setup>
import { ref, reactive, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { createApi, createAuth } from '@/shared/api.js'
const router = useRouter()
const api = createApi('/api/api', 'owner_token')
const auth = createAuth('owner_token')
const profile = ref(null)
const banners = ref([])
const notices = ref([])
const badges = reactive({ bill: 0, repair: 0, notice: 0 })
let timer = null

onMounted(async () => {
  const [p, b, n] = await Promise.all([api('/index/myInfo'), api('/index/banner'), api('/index/notice')])
  if (p.code === 0) profile.value = p.data
  if (b.code === 0) banners.value = Array.isArray(b.data) ? b.data : (b.data?.list || [])
  if (n.code === 0) notices.value = Array.isArray(n.data) ? n.data : (n.data?.list || [])
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
      badges.bill   = res.data.bill   || 0
      badges.repair = res.data.repair || 0
      badges.notice = res.data.notice || 0
    }
  } catch (e) { /* 静默 */ }
}

function logout() { auth.removeToken(); router.replace('/login') }
</script>
<style scoped>
.page{padding:16px}
header{display:flex;justify-content:space-between;align-items:center;margin-bottom:16px}
header h1{font-size:20px}
.btn-sm{background:none;border:1px solid #e5e7eb;padding:6px 14px;border-radius:6px;font-size:13px;color:#6b7280;cursor:pointer}
.user-card{background:#fff;border-radius:12px;padding:16px;display:flex;align-items:center;gap:12px;margin-bottom:16px;box-shadow:0 1px 3px rgba(0,0,0,.06)}
.avatar{font-size:36px}
.user-card strong{display:block;font-size:16px}
.user-card small{color:#9ca3af;font-size:13px}
.community-tag{display:inline-block;margin-top:4px;padding:2px 10px;background:#fff7ed;color:#c2410c;font-size:12px;border-radius:10px;font-weight:500}
.banner{background:linear-gradient(135deg,#667eea,#764ba2);border-radius:12px;padding:24px 18px;margin-bottom:20px;color:#fff;font-size:16px;font-weight:600}
.grid{display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px}
.card{background:#fff;border-radius:12px;padding:20px 12px;text-align:center;text-decoration:none;box-shadow:0 1px 3px rgba(0,0,0,.06);transition:transform .2s;position:relative}
.card:active{transform:scale(.97)}
.card span{font-size:32px;display:block;margin-bottom:8px}
.card label{font-size:13px;color:#4b5563}
.card b{position:absolute;top:8px;right:8px;min-width:20px;height:20px;line-height:20px;padding:0 6px;border-radius:10px;background:#ef4444;color:#fff;font-size:11px;font-weight:700;text-align:center;white-space:nowrap}
.notice-list{margin-top:24px}
.notice-list h3{font-size:16px;margin-bottom:12px}
.n-item{background:#fff;padding:12px 14px;border-radius:8px;margin-bottom:8px;font-size:14px;box-shadow:0 1px 2px rgba(0,0,0,.04)}
</style>
