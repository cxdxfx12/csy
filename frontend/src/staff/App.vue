<template>
  <div id="staff-app">
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
    <nav class="tab-bar" v-if="$route.path !== '/login'">
      <router-link to="/home" class="tab"><span>🏠</span><em>首页</em></router-link>
      <router-link to="/repair" class="tab"><span>🔧</span><em>报修</em><b v-if="badges.repair>0">{{ badges.repair > 99 ? '99+' : badges.repair }}</b></router-link>
      <router-link to="/meter" class="tab"><span>📊</span><em>抄表</em></router-link>
      <router-link to="/charge" class="tab"><span>💰</span><em>收费</em><b v-if="badges.charge>0">{{ badges.charge > 99 ? '99+' : badges.charge }}</b></router-link>
      <router-link to="/patrol" class="tab"><span>🛡️</span><em>巡更</em></router-link>
      <router-link to="/visitor" class="tab"><span>👤</span><em>访客</em></router-link>
      <router-link to="/order" class="tab"><span>📋</span><em>工单</em><b v-if="badges.order>0">{{ badges.order > 99 ? '99+' : badges.order }}</b></router-link>
    </nav>
  </div>
</template>
<script setup>
import { ref, reactive, onBeforeMount, onUnmounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { createApi } from '@/shared/api.js'

const route = useRoute()
const router = useRouter()
const api = createApi('/api/staff', 'staff_token')

// 角标数字
const badges = reactive({ repair: 0, charge: 0, order: 0 })

// 新消息弹窗
const notifyShow = ref(false)
const notifyTitle = ref('')
const notifyText = ref('')
const notifyRoute = ref('')
let lastBadges = null // 记录上一次的角标快照，用于对比
let timer = null

// 轮询角标
async function fetchBadges() {
  try {
    const res = await api('/badge/counts')
    if (res.code !== 0) return
    const d = res.data

    // 对比是否有新增
    if (lastBadges) {
      const growth = []
      if (d.repair > (lastBadges.repair || 0)) growth.push({ key: 'repair', title: '新报修单', text: d.last_repair ? `房间 ${d.last_repair.room_number || '--'} ${d.last_repair.title || ''}` : `+${d.repair - lastBadges.repair} 条待接单`, route: '/repair' })
      if (d.charge > (lastBadges.charge || 0)) growth.push({ key: 'charge', title: '新待缴账单', text: d.last_bill ? `${d.last_bill.owner_name || '--'} ¥${d.last_bill.total_amount || 0}` : `+${d.charge - lastBadges.charge} 条未缴费`, route: '/charge' })
      if (d.order  > (lastBadges.order  || 0)) growth.push({ key: 'order',  title: '新工单',       text: `+${d.order - lastBadges.order} 条进行中`,                               route: '/order' })
      // 只弹最新的一条
      if (growth.length > 0) {
        const g = growth[0]
        notifyTitle.value = g.title
        notifyText.value = g.text
        notifyRoute.value = g.route
        notifyShow.value = true
        // 5 秒后自动消失
        setTimeout(() => { notifyShow.value = false }, 5000)
      }
    }

    lastBadges = { ...d }
    badges.repair = d.repair || 0
    badges.charge = d.charge || 0
    badges.order  = d.order  || 0
  } catch (e) {
    // 静默失败
  }
}

// 点击弹窗跳转
function goNotify() {
  if (notifyRoute.value) router.push(notifyRoute.value)
  notifyShow.value = false
}

onBeforeMount(() => {
  if (localStorage.getItem('staff_token') && route.path === '/login') router.replace('/home')
  // 首次加载立即获取，之后每 30 秒轮询
  if (localStorage.getItem('staff_token')) {
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
</style>
