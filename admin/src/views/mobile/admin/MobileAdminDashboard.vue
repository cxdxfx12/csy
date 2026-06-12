<template>
  <div class="dash-page">
    <!-- 欢迎卡片 -->
    <div class="welcome-card">
      <div class="wc-bg"></div>
      <div class="wc-content">
        <div class="wc-left">
          <div class="wc-greeting">{{ greeting }}</div>
          <div class="wc-name">{{ userStore.userInfo?.nickname || userStore.userInfo?.username || '管理员' }}</div>
          <div class="wc-sub">{{ subtitle }}</div>
        </div>
        <div class="wc-right">
          <img :src="monkeyLogo" class="wc-avatar" />
        </div>
      </div>
    </div>

    <!-- 统计卡片 -->
    <div class="stats-grid">
      <div
        class="stat-card"
        v-for="(s, i) in stats"
        :key="i"
        :style="{ '--accent': s.color }"
        @click="s.action?.()"
      >
        <div class="sc-icon"><Icon :icon="s.icon" /></div>
        <div class="sc-value">{{ s.value }}</div>
        <div class="sc-label">{{ s.label }}</div>
      </div>
    </div>

    <!-- 快捷操作 -->
    <div class="section">
      <div class="sec-header">
        <span class="sec-title">快捷操作</span>
      </div>
      <div class="quick-grid">
        <div class="qk-item" v-for="q in quickActions" :key="q.label" @click="q.action()">
          <div class="qk-icon" :style="{ background: q.color }">
            <Icon :icon="q.icon" />
          </div>
          <span class="qk-label">{{ q.label }}</span>
        </div>
      </div>
    </div>

    <!-- 最近动态 -->
    <div class="section">
      <div class="sec-header">
        <span class="sec-title">最近动态</span>
        <span class="sec-more" @click="router.push('/mobile/admin/messages')">
          查看全部 <Icon icon="ph:caret-right" class="more-arrow" />
        </span>
      </div>
      <div class="feed-card">
        <div v-if="activities.length" class="feed-list">
          <div class="feed-item" v-for="(a, i) in activities" :key="i">
            <div class="fi-dot" :style="{ background: a.color }"></div>
            <div class="fi-main">
              <div class="fi-title">{{ a.title }}</div>
              <div class="fi-time">{{ a.time }}</div>
            </div>
          </div>
        </div>
        <div v-else class="feed-empty">
          <Icon icon="ph:clock-clockwise-duotone" class="fe-icon" />
          <span>暂无动态</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { Icon } from '@iconify/vue'
import { useUserStore } from '@/stores/user'
import monkeyLogo from '@/assets/images/monkey-ico.png'

const router = useRouter()
const userStore = useUserStore()

const greeting = computed(() => {
  const h = new Date().getHours()
  if (h < 6) return '夜深了'
  if (h < 9) return '早上好'
  if (h < 12) return '上午好'
  if (h < 14) return '中午好'
  if (h < 18) return '下午好'
  return '晚上好'
})

const subtitle = computed(() => {
  const h = new Date().getHours()
  if (h < 9) return '新的一天，从查看数据开始 🌅'
  if (h < 12) return '效率满满，加油冲刺 ☀️'
  if (h < 14) return '午间小憩，下午更高效 🌤'
  if (h < 18) return '保持专注，今天也要精彩 🌈'
  return '回顾今天，规划明日 🌙'
})

const stats = ref([
  { icon: 'ph:buildings-duotone', label: '管理小区', value: 0, color: '#6366f1', action: () => router.push('/mobile/admin/menus') },
  { icon: 'ph:users-duotone', label: '业主总数', value: 0, color: '#059669', action: () => router.push('/mobile/admin/menus') },
  { icon: 'ph:currency-circle-dollar-duotone', label: '本月实收', value: '¥0', color: '#ea580c', action: () => router.push('/mobile/admin/menus') },
  { icon: 'ph:clipboard-text-duotone', label: '待处理工单', value: 0, color: '#dc2626', action: () => router.push('/mobile/admin/menus') },
])

const quickActions = [
  { icon: 'ph:buildings-duotone', label: '房产管理', color: 'rgba(99,102,241,.12)', action: () => goMenu('房产') },
  { icon: 'ph:users-duotone', label: '业主信息', color: 'rgba(5,150,105,.12)', action: () => goMenu('业主') },
  { icon: 'ph:currency-circle-dollar-duotone', label: '收费管理', color: 'rgba(234,88,12,.12)', action: () => goMenu('收费') },
  { icon: 'ph:clipboard-text-duotone', label: '工单处理', color: 'rgba(220,38,38,.12)', action: () => goMenu('报修') },
  { icon: 'ph:megaphone-duotone', label: '公告发布', color: 'rgba(217,70,239,.12)', action: () => goMenu('公告') },
  { icon: 'ph:shield-check-duotone', label: '安防巡更', color: 'rgba(124,58,237,.12)', action: () => goMenu('安防') },
  { icon: 'ph:key-duotone', label: '门禁管理', color: 'rgba(14,116,144,.12)', action: () => goMenu('门禁') },
  { icon: 'ph:car-duotone', label: '停车管理', color: 'rgba(37,99,235,.12)', action: () => goMenu('停车') },
]

const activities = ref<any[]>([])

function goMenu(keyword: string) {
  router.push('/mobile/admin/menus')
}

onMounted(async () => {
  // 获取统计数据
  try {
    const r = await fetch('/api/admin/dashboard/statistics', {
      headers: { Authorization: `Bearer ${userStore.token}` },
    })
    const d = await r.json()
    if (d.code === 0 && d.data) {
      const data = d.data
      stats.value = [
        { icon: 'ph:buildings-duotone', label: '管理小区', value: data.communityCount || data.community_count || data[0]?.value || 0, color: '#6366f1', action: () => goMenu('房产') },
        { icon: 'ph:users-duotone', label: '业主总数', value: data.ownerCount || data.owner_count || data[1]?.value || 0, color: '#059669', action: () => goMenu('业主') },
        { icon: 'ph:currency-circle-dollar-duotone', label: '本月实收', value: data.monthIncome || data.month_income ? ('¥' + (data.monthIncome || data.month_income)) : (data[2]?.value || '¥0'), color: '#ea580c', action: () => goMenu('收费') },
        { icon: 'ph:clipboard-text-duotone', label: '待处理工单', value: data.pendingOrders || data.pending_orders || data[4]?.value || 0, color: '#dc2626', action: () => goMenu('报修') },
      ]
    }
  } catch { /* ignore */ }

  // 获取最近动态
  try {
    const r = await fetch('/api/admin/log?pageSize=5', {
      headers: { Authorization: `Bearer ${userStore.token}` },
    })
    const d = await r.json()
    if (d.code === 0 && d.data?.list) {
      activities.value = d.data.list.map((item: any) => ({
        title: item.msg || item.action || '操作记录',
        time: item.create_time || '',
        color: item.status === 1 ? '#059669' : '#64748b',
      }))
    }
  } catch { /* ignore */ }
})
</script>

<style scoped>
.dash-page { padding-bottom: 20px; }

/* ===== 欢迎卡片 ===== */
.welcome-card {
  position: relative;
  margin: 14px;
  border-radius: 20px;
  overflow: hidden;
}
.wc-bg {
  position: absolute;
  inset: 0;
  background: linear-gradient(135deg, #1e293b 0%, #334155 50%, #1e293b 100%);
}
.wc-bg::after {
  content: '';
  position: absolute;
  right: -30px; top: -30px;
  width: 140px; height: 140px;
  border-radius: 50%;
  background: rgba(99,102,241,.1);
}
.wc-content {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 22px 20px;
}
.wc-left { flex: 1; min-width: 0; }
.wc-greeting { font-size: 13px; color: rgba(255,255,255,.55); letter-spacing: 1px; }
.wc-name { font-size: 22px; font-weight: 800; color: #f1f5f9; margin-top: 4px; }
.wc-sub { font-size: 12px; color: rgba(255,255,255,.4); margin-top: 6px; }
.wc-avatar {
  width: 56px; height: 56px;
  border-radius: 16px;
  object-fit: contain;
  background: rgba(255,255,255,.12);
  padding: 8px;
  flex-shrink: 0;
}

/* ===== 统计卡片 ===== */
.stats-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 10px;
  padding: 0 14px;
  margin-top: 4px;
}
.stat-card {
  background: #fff;
  border-radius: 18px;
  padding: 16px;
  cursor: pointer;
  position: relative;
  overflow: hidden;
  border: 1px solid rgba(0,0,0,.04);
  transition: transform .15s;
}
.stat-card:active { transform: scale(.97); }
.stat-card::before {
  content: '';
  position: absolute;
  top: 0; left: 0;
  width: 4px; height: 100%;
  background: var(--accent, #6366f1);
}
.sc-icon { font-size: 26px; color: var(--accent, #6366f1); margin-bottom: 10px; }
.sc-value { font-size: 22px; font-weight: 800; color: #0f172a; }
.sc-label { font-size: 12px; color: #64748b; margin-top: 4px; }

/* ===== 区域 ===== */
.section { padding: 0 14px; margin-top: 18px; }
.sec-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 12px;
}
.sec-title { font-size: 16px; font-weight: 700; color: #0f172a; }
.sec-more {
  font-size: 12px; color: #3b82f6;
  cursor: pointer;
  display: flex; align-items: center; gap: 2px;
}
.more-arrow { font-size: 12px; }

/* ===== 快捷操作 ===== */
.quick-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 10px;
}
.qk-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  cursor: pointer;
  padding: 14px 4px;
  border-radius: 16px;
  background: #fff;
  border: 1px solid rgba(0,0,0,.03);
  transition: transform .15s;
}
.qk-item:active { transform: scale(.95); }
.qk-icon {
  width: 46px; height: 46px;
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
  color: #334155;
}
.qk-label { font-size: 11px; color: #475569; font-weight: 500; }

/* ===== 动态 ===== */
.feed-card {
  background: #fff;
  border-radius: 18px;
  overflow: hidden;
  border: 1px solid rgba(0,0,0,.03);
}
.feed-item {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 14px 16px;
  border-bottom: 1px solid #f1f5f9;
}
.feed-item:last-child { border-bottom: none; }
.fi-dot { width: 8px; height: 8px; border-radius: 50%; margin-top: 7px; flex-shrink: 0; }
.fi-title { font-size: 13px; color: #334155; line-height: 1.5; }
.fi-time { font-size: 11px; color: #94a3b8; margin-top: 4px; }
.feed-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  padding: 32px;
  color: #94a3b8;
  font-size: 13px;
}
.fe-icon { font-size: 32px; opacity: .4; }
</style>
