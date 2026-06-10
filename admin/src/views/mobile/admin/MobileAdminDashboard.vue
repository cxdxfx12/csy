<template>
  <div class="mad-page">
    <!-- 欢迎区 -->
    <div class="mad-welcome">
      <div class="madw-info">
        <div class="madw-greeting">{{ greeting }}</div>
        <div class="madw-name">{{ userStore.userInfo?.nickname || userStore.userInfo?.username || '管理员' }}</div>
        <div class="madw-sub">今天也是个好天气，一起加油吧 ☀️</div>
      </div>
      <img :src="monkeyLogo" class="madw-avatar" />
    </div>

    <!-- 统计卡片 -->
    <div class="mad-stats">
      <div class="mads-card" v-for="(s, i) in stats" :key="i" :style="{ background: s.bg }">
        <div class="madsc-icon">{{ s.icon }}</div>
        <div class="madsc-value">{{ s.value }}</div>
        <div class="madsc-label">{{ s.label }}</div>
      </div>
    </div>

    <!-- 快捷入口 -->
    <div class="mad-section">
      <h3 class="mad-sec-title">快捷入口</h3>
      <div class="mad-quick">
        <div class="madq-item" v-for="q in quickActions" :key="q.label" @click="q.action()">
          <div class="madqi-icon" :style="{ background: q.color }">{{ q.icon }}</div>
          <div class="madqi-label">{{ q.label }}</div>
        </div>
      </div>
    </div>

    <!-- 最近动态 -->
    <div class="mad-section">
      <h3 class="mad-sec-title">最近动态</h3>
      <div class="mad-feed" v-if="activities.length">
        <div class="madf-item" v-for="(a, i) in activities" :key="i">
          <div class="madfi-dot" :style="{ background: a.color }"></div>
          <div class="madfi-content">
            <div class="madfi-title">{{ a.title }}</div>
            <div class="madfi-time">{{ a.time }}</div>
          </div>
        </div>
      </div>
      <div class="mad-empty" v-else>暂无动态</div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useUserStore } from '@/stores/user'
import monkeyLogo from '@/assets/images/monkey-ico.png'

const router = useRouter()
const userStore = useUserStore()

const greeting = computed(() => {
  const h = new Date().getHours()
  if (h < 6) return '夜深了 🌙'
  if (h < 9) return '早上好 🌅'
  if (h < 12) return '上午好 ☀️'
  if (h < 14) return '中午好 🌤'
  if (h < 18) return '下午好 🌈'
  return '晚上好 🌙'
})

const stats = ref([
  { icon: '🏘️', label: '管理小区', value: 0, bg: '#ebf8ff' },
  { icon: '👤', label: '业主总数', value: 0, bg: '#f0fff4' },
  { icon: '💰', label: '本月收费', value: '¥0', bg: '#fffbeb' },
  { icon: '🔧', label: '待处理工单', value: 0, bg: '#fff5f5' },
])

const quickActions = [
  { icon: '🏘️', label: '小区管理', color: '#3182ce', action: () => router.push('/mobile/admin/menus') },
  { icon: '👤', label: '业主信息', color: '#38a169', action: () => router.push('/mobile/admin/menus') },
  { icon: '💰', label: '收费管理', color: '#dd6b20', action: () => router.push('/mobile/admin/menus') },
  { icon: '🔧', label: '工单处理', color: '#e53e3e', action: () => router.push('/mobile/admin/menus') },
  { icon: '📝', label: '公告发布', color: '#6b46c1', action: () => router.push('/mobile/admin/menus') },
  { icon: '📊', label: '数据报表', color: '#319795', action: () => router.push('/mobile/admin/menus') },
  { icon: '🔑', label: '门禁管理', color: '#d53f8c', action: () => router.push('/mobile/admin/menus') },
  { icon: '🚗', label: '停车管理', color: '#2b6cb0', action: () => router.push('/mobile/admin/menus') },
]

const activities = ref<any[]>([])

onMounted(async () => {
  try {
    const r = await fetch('/api/admin/dashboard/statistics', {
      headers: { Authorization: `Bearer ${userStore.token}` },
    })
    const d = await r.json()
    if (d.code === 0 && d.data) {
      stats.value = d.data
    }
  } catch {}

  try {
    const r = await fetch('/api/admin/log?pageSize=5', {
      headers: { Authorization: `Bearer ${userStore.token}` },
    })
    const d = await r.json()
    if (d.code === 0 && d.data?.list) {
      activities.value = d.data.list.map((item: any) => ({
        title: item.msg || item.action || '操作记录',
        time: item.create_time || '',
        color: item.status === 1 ? '#38a169' : '#718096',
      }))
    }
  } catch {}
})
</script>

<style scoped>
.mad-page { padding: 0 0 20px; }
.mad-welcome { display: flex; align-items: center; justify-content: space-between; padding: 18px 16px; background: linear-gradient(135deg, #1a365d, #2b6cb0); }
.madw-info { flex: 1; }
.madw-greeting { color: rgba(255,255,255,0.75); font-size: 13px; }
.madw-name { color: #fff; font-size: 18px; font-weight: 700; margin-top: 2px; }
.madw-sub { color: rgba(255,255,255,0.6); font-size: 12px; margin-top: 4px; }
.madw-avatar { width: 48px; height: 48px; border-radius: 12px; object-fit: contain; background: rgba(255,255,255,0.2); padding: 4px; }

/* 统计 */
.mad-stats { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; padding: 12px; }
.mads-card { border-radius: 14px; padding: 16px 14px; }
.madsc-icon { font-size: 24px; margin-bottom: 6px; }
.madsc-value { font-size: 20px; font-weight: 700; color: #1a202c; }
.madsc-label { font-size: 12px; color: #718096; margin-top: 2px; }

/* 区域 */
.mad-section { padding: 0 12px; margin-top: 8px; }
.mad-sec-title { font-size: 15px; font-weight: 700; color: #1a202c; margin-bottom: 10px; padding-left: 2px; }

/* 快捷入口 */
.mad-quick { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; }
.madq-item { display: flex; flex-direction: column; align-items: center; gap: 6px; cursor: pointer; padding: 8px 4px; border-radius: 12px; background: #fff; }
.madq-item:active { background: #edf2f7; }
.madqi-icon { width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 22px; }
.madqi-label { font-size: 11px; color: #4a5568; }

/* 动态 */
.mad-feed { background: #fff; border-radius: 14px; overflow: hidden; }
.madf-item { display: flex; align-items: flex-start; gap: 12px; padding: 14px; border-bottom: 1px solid #f7f8fc; }
.madf-item:last-child { border-bottom: none; }
.madfi-dot { width: 8px; height: 8px; border-radius: 50%; margin-top: 6px; flex-shrink: 0; }
.madfi-title { font-size: 13px; color: #2d3748; }
.madfi-time { font-size: 11px; color: #a0aec0; margin-top: 2px; }
.mad-empty { padding: 24px; text-align: center; color: #a0aec0; font-size: 13px; background: #fff; border-radius: 14px; }
</style>
