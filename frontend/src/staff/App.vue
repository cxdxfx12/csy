<template>
  <div id="staff-app">
    <router-view />
    <GlobalToast />
    <!-- 新消息弹窗（仅登录状态显示） -->
    <div class="notify-popup" :class="{show:notifyShow}" v-if="isLoggedIn" @click="goNotify">
      <span class="notify-icon">🔔</span>
      <div class="notify-body">
        <strong>{{ notifyTitle }}</strong>
        <small>{{ notifyText }}</small>
      </div>
      <button class="notify-close" @click.stop="notifyShow=false">✕</button>
    </div>
    <!-- 右上角通知小字（仅登录状态显示） -->
    <div class="pillar-popup" :class="{show:pillarShow}" v-if="isLoggedIn" @click="goPillar">
      <span>📬</span><span>{{ pillarMsg }}</span>
      <button class="pillar-close" @click.stop="pillarShow=false">✕</button>
    </div>
    <nav class="tab-bar" v-if="$route.path !== '/login'">
      <router-link to="/home" class="tab"><span>🏠</span><em>首页</em></router-link>
      <router-link to="/repair" class="tab" @click="dismissBadge('repair')"><span>🔧</span><em>报修</em><b v-if="badges.repair>0">{{ badges.repair > 99 ? '99+' : badges.repair }}</b></router-link>
      <router-link to="/meter" class="tab"><span>📊</span><em>抄表</em></router-link>
      <router-link to="/charge" class="tab" @click="dismissBadge('charge')"><span>💰</span><em>收费</em><b v-if="badges.charge>0">{{ badges.charge > 99 ? '99+' : badges.charge }}</b></router-link>
      <router-link to="/patrol" class="tab"><span>🛡️</span><em>巡更</em></router-link>
      <router-link to="/visitor" class="tab"><span>👤</span><em>访客</em></router-link>
      <router-link to="/order" class="tab" @click="dismissBadge('order')"><span>📋</span><em>工单</em><b v-if="badges.order>0">{{ badges.order > 99 ? '99+' : badges.order }}</b></router-link>
      <router-link to="/complaint" class="tab" @click="dismissBadge('complaint')"><span>📢</span><em>投诉</em><b v-if="badges.complaint>0">{{ badges.complaint > 99 ? '99+' : badges.complaint }}</b></router-link>
    </nav>
  </div>
</template>
<script setup>
import { ref, reactive, computed, onBeforeMount, onUnmounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { createApi } from '@/shared/api.js'
import { playNotificationSound, pillarMsg, pillarShow, pillarRoute, showPillar, hidePillar } from '@/shared/utils.js'

const route = useRoute()
const router = useRouter()
const api = createApi('/api/staff', 'staff_token')

// 是否已登录（有 token 且不在登录页）
const isLoggedIn = computed(() => !!localStorage.getItem('staff_token') && route.path !== '/login')

// 角标数字
const badges = reactive({ repair: 0, charge: 0, order: 0, complaint: 0, vote: 0, activity: 0 })
const badgesRaw = reactive({ repair: 0, charge: 0, order: 0, complaint: 0, vote: 0, activity: 0 })

// 新消息弹窗
const notifyShow = ref(false)
const notifyTitle = ref('')
const notifyText = ref('')
const notifyRoute = ref('')
let lastBadges = null // 记录上一次的角标快照，用于对比
let timer = null
let sseConnection = null
let sseReconnectTimer = null

// SSE 实时推送连接
function connectSSE() {
  const token = localStorage.getItem('staff_token')
  if (!token) return

  try {
    const baseUrl = import.meta.env?.VITE_API_BASE || window.location.origin
    const url = `${baseUrl}/api/staff/sse/stream?token=${encodeURIComponent(token)}`
    sseConnection = new EventSource(url)

    sseConnection.addEventListener('connected', () => {
      console.log('[SSE] 实时推送已连接')
      // 清除重连定时器
      if (sseReconnectTimer) { clearTimeout(sseReconnectTimer); sseReconnectTimer = null }
    })

    sseConnection.addEventListener('repair_assign', (e) => {
      try {
        const data = JSON.parse(e.data)
        console.log('[SSE] 新派单:', data)
        // 刷新角标
        fetchBadges()
        // 弹窗提醒
        notifyTitle.value = data.title || '新派单工单'
        notifyText.value = data.content || ''
        notifyRoute.value = '/repair'
        notifyShow.value = true
        setTimeout(() => { notifyShow.value = false }, 5000)
        playNotificationSound()
        showPillar(data.title || '您有新的派单', '/repair')
      } catch (_) {}
    })

    sseConnection.addEventListener('heartbeat', () => {
      // 心跳，保持连接无需操作
    })

    sseConnection.addEventListener('timeout', () => {
      console.log('[SSE] 连接刷新，即将重连...')
      sseConnection?.close()
    })

    sseConnection.addEventListener('error', (e) => {
      console.warn('[SSE] 连接断开，3秒后重连...')
      sseConnection?.close()
    })

    sseConnection.onerror = () => {
      sseConnection?.close()
      // 3秒后重连
      sseReconnectTimer = setTimeout(connectSSE, 3000)
    }

  } catch (e) {
    console.warn('[SSE] 连接失败，将使用轮询模式')
  }
}

function disconnectSSE() {
  if (sseConnection) {
    sseConnection.close()
    sseConnection = null
  }
  if (sseReconnectTimer) {
    clearTimeout(sseReconnectTimer)
    sseReconnectTimer = null
  }
}

// 轮询角标
async function fetchBadges() {
  try {
    const res = await api('/badge/counts')
    if (res.code !== 0) return
    const d = res.data
    const keys = ['repair','charge','order','complaint','vote','activity']

    // 记录原始值
    keys.forEach(k => { badgesRaw[k] = d[k] || 0 })

    // 应用已读消除
    const seen = JSON.parse(localStorage.getItem('staff_badge_seen') || '{}')
    keys.forEach(k => { badges[k] = Math.max(0, (d[k] || 0) - (seen[k] || 0)) })

    // 对比是否有新增
    const growth = []
    if (lastBadges) {
      if (d.repair > (lastBadges.repair || 0)) growth.push({ key: 'repair', title: '新报修单', text: d.last_repair ? `房间 ${d.last_repair.room_number || '--'} ${d.last_repair.title || ''}` : `+${d.repair - lastBadges.repair} 条待接单`, route: '/repair' })
      if (d.charge > (lastBadges.charge || 0)) growth.push({ key: 'charge', title: '新待缴账单', text: d.last_bill ? `${d.last_bill.owner_name || '--'} ¥${d.last_bill.total_amount || 0}` : `+${d.charge - lastBadges.charge} 条未缴费`, route: '/charge' })
      if (d.order  > (lastBadges.order  || 0)) growth.push({ key: 'order',  title: '新工单',       text: `+${d.order - lastBadges.order} 条进行中`, route: '/order' })
      if (d.complaint > (lastBadges.complaint || 0)) growth.push({ key: 'complaint', title: '新投诉', text: d.last_complaint ? (d.last_complaint.content || '').substring(0,30) : `+${d.complaint - lastBadges.complaint} 条待处理`, route: '/complaint' })
      if (d.vote > (lastBadges.vote || 0)) growth.push({ key: 'vote', title: '新投票', text: `+${d.vote - lastBadges.vote} 个进行中`, route: '/vote' })
      if (d.activity > (lastBadges.activity || 0)) growth.push({ key: 'activity', title: '新活动', text: `+${d.activity - lastBadges.activity} 个报名中`, route: '/activity' })
    } else {
      // 首次加载：所有非零项都视为"待处理"
      if (d.repair   > 0) growth.push({ key: 'repair',   title: '待接报修单', text: d.last_repair   ? `房间 ${d.last_repair.room_number || '--'}` : `${d.repair} 条待接单`,     route: '/repair' })
      if (d.charge   > 0) growth.push({ key: 'charge',   title: '待缴账单',   text: d.last_bill     ? `${d.last_bill.owner_name || '--'} ¥${d.last_bill.total_amount || 0}` : `${d.charge} 条未缴费`, route: '/charge' })
      if (d.order    > 0) growth.push({ key: 'order',    title: '进行中工单', text: `${d.order} 条进行中`, route: '/order' })
      if (d.complaint > 0) growth.push({ key: 'complaint', title: '待处理投诉', text: d.last_complaint ? (d.last_complaint.content || '').substring(0,30) : `${d.complaint} 条待处理`, route: '/complaint' })
      if (d.vote     > 0) growth.push({ key: 'vote',     title: '进行中投票', text: `${d.vote} 个进行中`, route: '/vote' })
      if (d.activity  > 0) growth.push({ key: 'activity', title: '报名中活动', text: `${d.activity} 个报名中`, route: '/activity' })
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
  if (!isLoggedIn.value) return
  if (pillarRoute.value) {
    const key = pillarRoute.value.substring(1)
    if (['repair','charge','order','complaint'].includes(key)) dismissBadge(key)
    router.push(pillarRoute.value)
  }
  hidePillar()
}

// 点击弹窗跳转
function goNotify() {
  if (!isLoggedIn.value) return
  if (notifyRoute.value) {
    const key = notifyRoute.value.substring(1)
    if (['repair','charge','order','complaint'].includes(key)) dismissBadge(key)
    router.push(notifyRoute.value)
  }
  notifyShow.value = false
}

// 点击阅读后角标消失
function dismissBadge(key) {
  const seen = JSON.parse(localStorage.getItem('staff_badge_seen') || '{}')
  seen[key] = badgesRaw[key] || 0
  localStorage.setItem('staff_badge_seen', JSON.stringify(seen))
  badges[key] = 0
}

onBeforeMount(() => {
  if (localStorage.getItem('staff_token') && route.path === '/login') router.replace('/home')
  // 仅在已登录（非登录页）状态下才拉取角标和建立SSE
  if (isLoggedIn.value) {
    fetchBadges()
    timer = setInterval(fetchBadges, 30000)
    connectSSE()
  }
})

onUnmounted(() => {
  if (timer) clearInterval(timer)
  disconnectSSE()
})
</script>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background:#f5f7fa; color:#333; -webkit-tap-highlight-color:transparent}
#staff-app{min-height:100vh;padding-bottom:68px}
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
/* 右上角通知小字 */
.pillar-popup{position:fixed;top:16px;right:12px;background:#2563eb;color:#fff;border-radius:20px;padding:8px 36px 8px 14px;font-size:12px;z-index:1000;display:flex;align-items:center;gap:6px;opacity:0;transform:translateX(20px);transition:opacity .3s,transform .3s;cursor:pointer;box-shadow:0 2px 12px rgba(37,99,235,.4);max-width:85vw;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.pillar-popup.show{opacity:1;transform:translateX(0)}
.pillar-close{position:absolute;top:50%;right:10px;transform:translateY(-50%);background:none;border:none;color:rgba(255,255,255,.7);font-size:14px;cursor:pointer;padding:0;line-height:1}
</style>
