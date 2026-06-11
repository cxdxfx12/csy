<template>
  <div id="manager-app">
    <router-view />
    <GlobalToast />
    <SmartGuide v-if="isLoggedIn" />
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
  </div>
</template>
<script setup>
import { ref, reactive, computed, onBeforeMount, onUnmounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { createApi } from '@/shared/api.js'
import { playNotificationSound, pillarMsg, pillarShow, pillarRoute, showPillar, hidePillar } from '@/shared/utils.js'
import './themes.css'
import { useTheme } from './stores/useTheme.js'
import SmartGuide from './components/SmartGuide.vue'

useTheme()

const route = useRoute()
const router = useRouter()
const api = createApi('/api/manager', 'manager_token')

// 登录状态下才显示大圣导航助手
const isLoggedIn = computed(() => !!localStorage.getItem('manager_token') && route.path !== '/login')

// 角标数字
const badges = reactive({ repair: 0, bill: 0, owner: 0, complaint: 0, vote: 0, activity: 0 })
let lastBadges = null
let timer = null

// 新消息弹窗
const notifyShow = ref(false)
const notifyTitle = ref('')
const notifyText = ref('')
const notifyRoute = ref('')

async function fetchBadges() {
  try {
    const res = await api('/dashboard/pendingTodo')
    if (res.code !== 0) return
    const todos = Array.isArray(res.data) ? res.data : (res.data?.list || [])
    // 将 todo 按类型归类计数
    const d = { repair: 0, bill: 0, owner: 0, complaint: 0, vote: 0, activity: 0 }
    let totalCount = 0
    todos.forEach(t => {
      const key = t.key || t.type || ''
      if (key === 'repair') d.repair += (t.count || 0)
      else if (key === 'bill') d.bill += (t.count || 0)
      else if (key === 'owner') d.owner += (t.count || 0)
      else if (key === 'complaint') d.complaint += (t.count || 0)
      else if (key === 'vote') d.vote += (t.count || 0)
      else if (key === 'activity') d.activity += (t.count || 0)
      totalCount += (t.count || 0)
    })
    Object.keys(badges).forEach(k => { badges[k] = d[k] || 0 })

    // 构造成长列表
    const growth = []
    const labelMap = { repair: '待修理', bill: '待缴费', owner: '待审核业主', complaint: '待处理投诉', vote: '待投票', activity: '待报名' }
    const routeMap = { repair: '/dashboard?tab=repair', bill: '/dashboard?tab=bill', owner: '/dashboard?tab=owner', complaint: '/dashboard?tab=complaint', vote: '/dashboard?tab=vote', activity: '/dashboard?tab=activity' }

    if (lastBadges) {
      Object.keys(d).forEach(k => {
        if (d[k] > (lastBadges[k] || 0)) {
          growth.push({ key: k, title: `新${labelMap[k]}`, text: `+${d[k] - lastBadges[k]} 条`, route: routeMap[k] })
        }
      })
    } else {
      // 首次加载，有非零项就提示
      Object.keys(d).forEach(k => {
        if (d[k] > 0) {
          growth.push({ key: k, title: labelMap[k], text: `${d[k]} 条`, route: routeMap[k] })
        }
      })
    }

    if (growth.length > 0) {
      const g = growth[0]
      notifyTitle.value = g.title
      notifyText.value = g.text
      notifyRoute.value = g.route
      notifyShow.value = true
      setTimeout(() => { notifyShow.value = false }, 5000)

      // 🔔 播放声音
      playNotificationSound()

      // 右上角小字
      showPillar(`您有 ${totalCount} 条待处理提醒`, '/dashboard')
    }

    lastBadges = { ...d }
  } catch (e) { /* 静默 */ }
}

function goNotify() {
  if (!isLoggedIn.value) return
  if (notifyRoute.value) {
    router.push(notifyRoute.value)
  }
  notifyShow.value = false
}

function goPillar() {
  if (!isLoggedIn.value) return
  if (pillarRoute.value) {
    router.push(pillarRoute.value)
  }
  hidePillar()
}

onBeforeMount(() => {
  // 处理 OAuth 回调带回的 wechat_token（App.vue 层面兜底）
  const q = route.query
  if (q.wechat_token) {
    localStorage.setItem('manager_token', q.wechat_token)
    router.replace('/dashboard')
    return
  }
  if (localStorage.getItem('manager_token') && route.path === '/login') router.replace('/dashboard')
  // 仅在已登录状态下拉取角标
  if (isLoggedIn.value) {
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
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:var(--bg-page);color:var(--text-2);-webkit-tap-highlight-color:transparent;overflow-x:hidden;transition:background .4s,color .3s}
/* 背景图案 */
body::before{content:'';position:fixed;top:0;left:0;width:100%;height:100%;pointer-events:none;z-index:-1;background:var(--bg-page-pattern);transition:background .4s}
#manager-app{min-height:100vh;position:relative}
/* 通知弹窗 */
.notify-popup{position:fixed;top:-80px;left:12px;right:12px;background:var(--bg-notify);color:#fff;border-radius:16px;padding:14px 44px 14px 16px;display:flex;align-items:center;gap:10px;z-index:999;transition:top .4s cubic-bezier(.4,0,.2,1),background .3s;backdrop-filter:var(--glass-blur-lg);-webkit-backdrop-filter:var(--glass-blur-lg);border:1px solid var(--border-1);box-shadow:0 8px 32px rgba(0,0,0,.25),var(--shine-top);cursor:pointer}
.notify-popup::before{content:'';position:absolute;top:0;left:15%;right:15%;height:1px;background:var(--highlight-line);opacity:var(--highlight-opacity);transition:opacity .3s}
.notify-popup.show{top:48px}
.notify-icon{font-size:22px;flex-shrink:0}
.notify-body{flex:1;min-width:0}
.notify-body strong{display:block;font-size:14px;margin-bottom:2px}
.notify-body small{display:block;font-size:12px;color:var(--text-3);overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.notify-close{position:absolute;top:10px;right:12px;background:none;border:none;color:var(--text-3);font-size:16px;cursor:pointer;padding:0;line-height:1}
/* 右上角通知小字 */
.pillar-popup{position:fixed;top:16px;right:12px;background:var(--bg-pillar);color:#fff;border-radius:22px;padding:8px 36px 8px 14px;font-size:12px;z-index:1000;display:flex;align-items:center;gap:6px;opacity:0;transform:translateX(20px);transition:all .35s cubic-bezier(.4,0,.2,1);cursor:pointer;backdrop-filter:var(--glass-blur-xs);-webkit-backdrop-filter:var(--glass-blur-xs);border:1px solid var(--border-1);box-shadow:0 4px 20px var(--accent-shadow),var(--shine-top);max-width:85vw;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.pillar-popup::before{content:'';position:absolute;top:0;left:10%;right:10%;height:1px;background:var(--highlight-line);opacity:var(--highlight-opacity)}
.pillar-popup.show{opacity:1;transform:translateX(0)}
.pillar-close{position:absolute;top:50%;right:10px;transform:translateY(-50%);background:none;border:none;color:rgba(255,255,255,.7);font-size:14px;cursor:pointer;padding:0;line-height:1}
</style>
