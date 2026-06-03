<template>
  <div id="owner-app">
    <router-view />
    <GlobalToast />
    <!-- 新消息弹窗 -->
    <div class="notify-popup" :class="{show:notifyShow}" @click="goNotify">
      <span class="notify-icon">🔔</span>
      <div class="notify-body">
        <strong>{{ notifyTitle }}</strong>
        <small>{{ notifyText }}</small>
      </div>
      <button class="notify-close" @click.stop="notifyShow=false">✕</button>
    </div>
    <nav class="tab-bar" v-if="$route.path !== '/login' && $route.path !== '/register'">
      <router-link to="/home" class="tab"><span>🏠</span><em>首页</em></router-link>
      <router-link to="/room" class="tab"><span>🏢</span><em>房产</em></router-link>
      <router-link to="/bill" class="tab"><span>💰</span><em>账单</em><b v-if="badges.bill>0">{{ badges.bill > 99 ? '99+' : badges.bill }}</b></router-link>
      <router-link to="/repair" class="tab"><span>🔧</span><em>报修</em><b v-if="badges.repair>0">{{ badges.repair > 99 ? '99+' : badges.repair }}</b></router-link>
      <router-link to="/notice" class="tab"><span>📢</span><em>公告</em><b v-if="badges.notice>0">{{ badges.notice > 99 ? '99+' : badges.notice }}</b></router-link>
      <router-link to="/visitor" class="tab"><span>👤</span><em>访客</em></router-link>
    </nav>
  </div>
</template>
<script setup>
import { ref, reactive, onBeforeMount, onUnmounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { createApi } from '@/shared/api.js'

const route = useRoute()
const router = useRouter()
const api = createApi('/api/api', 'owner_token')

const badges = reactive({ bill: 0, repair: 0, notice: 0 })
const notifyShow = ref(false)
const notifyTitle = ref('')
const notifyText = ref('')
const notifyRoute = ref('')
let lastBadges = null
let timer = null

async function fetchBadges() {
  try {
    const res = await api('/badge/counts')
    if (res.code !== 0) return
    const d = res.data

    if (lastBadges) {
      const growth = []
      if (d.bill   > (lastBadges.bill   || 0)) growth.push({ key: 'bill',   title: '新账单',   text: d.last_bill   ? `¥${d.last_bill.total_amount || 0}` : `+${d.bill - lastBadges.bill} 条待缴`,    route: '/bill' })
      if (d.repair > (lastBadges.repair || 0)) growth.push({ key: 'repair', title: '报修进度', text: d.last_repair ? (d.last_repair.title || '') : `+${d.repair - lastBadges.repair} 条处理中`, route: '/repair' })
      if (d.notice > (lastBadges.notice || 0)) growth.push({ key: 'notice', title: '新公告',   text: d.last_notice ? (d.last_notice.title || '') : `+${d.notice - lastBadges.notice} 条新公告`,    route: '/notice' })
      if (growth.length > 0) {
        const g = growth[0]
        notifyTitle.value = g.title
        notifyText.value = g.text
        notifyRoute.value = g.route
        notifyShow.value = true
        setTimeout(() => { notifyShow.value = false }, 5000)
      }
    }

    lastBadges = { ...d }
    badges.bill   = d.bill   || 0
    badges.repair = d.repair || 0
    badges.notice = d.notice || 0
  } catch (e) { /* 静默 */ }
}

function goNotify() {
  if (notifyRoute.value) router.push(notifyRoute.value)
  notifyShow.value = false
}

onBeforeMount(() => {
  if (localStorage.getItem('owner_token') && (route.path === '/login' || route.path === '/register')) router.replace('/home')
  const q = new URLSearchParams(window.location.search)
  const wcToken = q.get('wechat_token')
  if (wcToken) {
    localStorage.setItem('owner_token', wcToken)
    const cleanUrl = window.location.origin + window.location.pathname + window.location.hash
    window.history.replaceState({}, '', cleanUrl)
    router.replace('/home')
  }
  if (localStorage.getItem('owner_token')) {
    fetchBadges()
    timer = setInterval(fetchBadges, 30000)
  }
})

onUnmounted(() => {
  if (timer) clearInterval(timer)
})
</script>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:#f5f7fa;color:#333;-webkit-tap-highlight-color:transparent}
#owner-app{min-height:100vh;padding-bottom:68px}
/* 底部导航 */
.tab-bar{position:fixed;bottom:0;left:0;right:0;height:60px;background:#fff;display:flex;justify-content:space-around;align-items:center;border-top:1px solid #e5e7eb;z-index:100;padding-bottom:env(safe-area-inset-bottom)}
.tab{display:flex;flex-direction:column;align-items:center;text-decoration:none;color:#999;font-size:10px;min-width:48px;position:relative}
.tab span{font-size:22px;line-height:1.3}
.tab em{font-style:normal;margin-top:1px}
.tab.router-link-active{color:#2563eb}
.tab.router-link-active span{transform:scale(1.1)}
/* 角标 */
.tab b{position:absolute;top:-2px;right:0;min-width:18px;height:18px;line-height:18px;padding:0 5px;border-radius:10px;background:#ef4444;color:#fff;font-size:11px;font-weight:700;text-align:center;white-space:nowrap;transform:translateX(50%)}
/* 新消息弹窗 */
.notify-popup{position:fixed;top:-80px;left:12px;right:12px;background:#1f2937;color:#fff;border-radius:12px;padding:14px 44px 14px 16px;display:flex;align-items:center;gap:10px;z-index:999;transition:top .3s ease;box-shadow:0 4px 12px rgba(0,0,0,.25);cursor:pointer}
.notify-popup.show{top:48px}
.notify-icon{font-size:22px;flex-shrink:0}
.notify-body{flex:1;min-width:0}
.notify-body strong{display:block;font-size:14px;margin-bottom:2px}
.notify-body small{display:block;font-size:12px;color:#9ca3af;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.notify-close{position:absolute;top:10px;right:12px;background:none;border:none;color:#9ca3af;font-size:16px;cursor:pointer;padding:0;line-height:1}
</style>
