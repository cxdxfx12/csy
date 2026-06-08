<template>
  <div id="owner-app">
    <router-view />
    <GlobalToast />
    <!-- 新消息弹窗 -->
    <div v-if="notifyShow" class="notify-popup show" @click="goNotify">
      <span class="notify-icon">🔔</span>
      <div class="notify-body">
        <strong>{{ notifyTitle }}</strong>
        <small>{{ notifyText }}</small>
      </div>
      <button class="notify-close" @click.stop="notifyShow=false">✕</button>
    </div>
    <!-- 右上角通知小字 -->
    <div v-if="pillarShow" class="pillar-popup show" @click="goPillar">
      <span>📬</span><span>{{ pillarMsg }}</span>
      <button class="pillar-close" @click.stop="pillarShow=false">✕</button>
    </div>
    <AiChatWidget v-if="$route.path !== '/login' && $route.path !== '/register'" />
    <nav class="tab-bar" v-if="$route.path !== '/login' && $route.path !== '/register'">
      <router-link to="/home" class="tab"><span>🏠</span><em>首页</em></router-link>
      <router-link to="/room" class="tab"><span>🏢</span><em>房产</em></router-link>
      <router-link to="/bill" class="tab" @click="dismissBadge('bill')"><span>💰</span><em>账单</em><b v-if="badges.bill>0">{{ badges.bill > 99 ? '99+' : badges.bill }}</b></router-link>
      <router-link to="/repair" class="tab" @click="dismissBadge('repair')"><span>🔧</span><em>报修</em><b v-if="badges.repair>0">{{ badges.repair > 99 ? '99+' : badges.repair }}</b></router-link>
      <router-link to="/complaint" class="tab" @click="dismissBadge('complaint')"><span>📝</span><em>投诉</em><b v-if="badges.complaint>0">{{ badges.complaint > 99 ? '99+' : badges.complaint }}</b></router-link>
      <router-link to="/notice" class="tab" @click="dismissBadge('notice')"><span>📢</span><em>公告</em><b v-if="badges.notice>0">{{ badges.notice > 99 ? '99+' : badges.notice }}</b></router-link>
      <router-link to="/vehicle" class="tab"><span>🚗</span><em>车辆</em></router-link>
      <router-link to="/vote" class="tab" @click="dismissBadge('vote')"><span>🗳</span><em>投票</em><b v-if="badges.vote>0">{{ badges.vote > 99 ? '99+' : badges.vote }}</b></router-link>
      <router-link to="/activity" class="tab" @click="dismissBadge('activity')"><span>🎉</span><em>活动</em><b v-if="badges.activity>0">{{ badges.activity > 99 ? '99+' : badges.activity }}</b></router-link>
    </nav>
  </div>
</template>
<script setup>
import { ref, reactive, onBeforeMount, onUnmounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { createApi } from '@/shared/api.js'
import { playNotificationSound, pillarMsg, pillarShow, pillarRoute, showPillar, hidePillar } from '@/shared/utils.js'
import AiChatWidget from './components/AiChatWidget.vue'

const route = useRoute()
const router = useRouter()
const api = createApi('/api/api', 'owner_token')

const badges = reactive({ bill: 0, repair: 0, notice: 0, complaint: 0, vote: 0, activity: 0 })
const badgesRaw = reactive({ bill: 0, repair: 0, notice: 0, complaint: 0, vote: 0, activity: 0 })
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
    const keys = ['bill','repair','notice','complaint','vote','activity']

    // 记录原始值
    keys.forEach(k => { badgesRaw[k] = d[k] || 0 })

    // 应用已读消除
    const seen = JSON.parse(localStorage.getItem('badge_seen') || '{}')
    keys.forEach(k => { badges[k] = Math.max(0, (d[k] || 0) - (seen[k] || 0)) })

    // 收集增长项
    const growth = []
    if (lastBadges) {
      if (d.bill   > (lastBadges.bill   || 0)) growth.push({ key: 'bill',   title: '新账单',   text: d.last_bill   ? `¥${d.last_bill.total_amount || 0}` : `+${d.bill - lastBadges.bill} 条待缴`,    route: '/bill' })
      if (d.repair > (lastBadges.repair || 0)) growth.push({ key: 'repair', title: '报修进度', text: d.last_repair ? (d.last_repair.title || '') : `+${d.repair - lastBadges.repair} 条处理中`, route: '/repair' })
      if (d.notice > (lastBadges.notice || 0)) growth.push({ key: 'notice', title: '新公告',   text: d.last_notice ? (d.last_notice.title || '') : `+${d.notice - lastBadges.notice} 条新公告`,    route: '/notice' })
      if (d.complaint > (lastBadges.complaint || 0)) growth.push({ key: 'complaint', title: '投诉更新', text: d.last_complaint ? (d.last_complaint.content || '').substring(0,30) : `+${d.complaint - lastBadges.complaint} 条处理中`, route: '/complaint' })
      if (d.vote > (lastBadges.vote || 0)) growth.push({ key: 'vote', title: '新投票', text: d.last_vote ? d.last_vote.title : `+${d.vote - lastBadges.vote} 个待投`, route: '/vote' })
      if (d.activity > (lastBadges.activity || 0)) growth.push({ key: 'activity', title: '新活动', text: d.last_activity ? d.last_activity.title : `+${d.activity - lastBadges.activity} 个可报名`, route: '/activity' })
    } else {
      // 首次加载：所有非零项都视为"待处理"
      if (d.bill   > 0) growth.push({ key: 'bill',   title: '待缴账单', text: d.last_bill   ? `¥${d.last_bill.total_amount || 0}` : `${d.bill} 条待缴`,      route: '/bill' })
      if (d.repair > 0) growth.push({ key: 'repair', title: '处理中报修', text: d.last_repair ? (d.last_repair.title || '') : `${d.repair} 条处理中`,   route: '/repair' })
      if (d.notice > 0) growth.push({ key: 'notice', title: '新公告',    text: d.last_notice ? (d.last_notice.title || '') : `${d.notice} 条新公告`,     route: '/notice' })
      if (d.complaint > 0) growth.push({ key: 'complaint', title: '处理中投诉', text: d.last_complaint ? (d.last_complaint.content || '').substring(0,30) : `${d.complaint} 条处理中`, route: '/complaint' })
      if (d.vote    > 0) growth.push({ key: 'vote',    title: '待投票',    text: d.last_vote    ? d.last_vote.title    : `${d.vote} 个待投`,        route: '/vote' })
      if (d.activity > 0) growth.push({ key: 'activity', title: '可报名活动', text: d.last_activity ? d.last_activity.title : `${d.activity} 个可报名`,   route: '/activity' })
    }

    if (growth.length > 0) {
      // 弹窗：展示第一条
      const g = growth[0]
      notifyTitle.value = g.title
      notifyText.value = g.text
      notifyRoute.value = g.route
      notifyShow.value = true
      setTimeout(() => { notifyShow.value = false }, 5000)

      // 🔔 播放声音
      playNotificationSound()

      // 右上角小字：显示总数
      showPillar(`您有 ${growth.length} 条待处理提醒`, growth[0].route)
    }

    lastBadges = { ...d }
  } catch (e) { /* 静默 */ }
}

function goPillar() {
  if (pillarRoute.value) {
    const key = pillarRoute.value.substring(1)
    if (['bill','repair','notice','complaint','vote','activity'].includes(key)) dismissBadge(key)
    router.push(pillarRoute.value)
  }
  hidePillar()
}

function goNotify() {
  if (notifyRoute.value) {
    dismissBadge(notifyRoute.value.substring(1))
    router.push(notifyRoute.value)
  }
  notifyShow.value = false
}

// 点击阅读后角标消失
function dismissBadge(key) {
  const seen = JSON.parse(localStorage.getItem('badge_seen') || '{}')
  seen[key] = badgesRaw[key] || 0
  localStorage.setItem('badge_seen', JSON.stringify(seen))
  badges[key] = 0
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
.tab-bar{position:fixed;bottom:0;left:0;right:0;height:56px;background:#fff;display:flex;justify-content:space-around;align-items:center;border-top:1px solid #e5e7eb;z-index:100;padding-bottom:env(safe-area-inset-bottom)}
.tab{display:flex;flex-direction:column;align-items:center;text-decoration:none;color:#999;font-size:9px;min-width:0;position:relative;flex:1}
.tab span{font-size:18px;line-height:1.2}
.tab em{font-style:normal;margin-top:0}
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
/* 右上角通知小字 */
.pillar-popup{position:fixed;top:16px;right:12px;background:#2563eb;color:#fff;border-radius:20px;padding:8px 36px 8px 14px;font-size:12px;z-index:1000;display:flex;align-items:center;gap:6px;opacity:0;transform:translateX(20px);transition:opacity .3s,transform .3s;cursor:pointer;box-shadow:0 2px 12px rgba(37,99,235,.4);max-width:85vw;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.pillar-popup.show{opacity:1;transform:translateX(0)}
.pillar-close{position:absolute;top:50%;right:10px;transform:translateY(-50%);background:none;border:none;color:rgba(255,255,255,.7);font-size:14px;cursor:pointer;padding:0;line-height:1}
</style>
