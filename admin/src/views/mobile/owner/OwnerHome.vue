<template>
  <MobileLayout title="业主服务" :showTab="true" :tabs="ownerTabs">
    <div class="pd">
      <div class="oh-user">
        👤 {{ user?.name || '业主' }}
        <span class="oh-room">{{ user?.room_names || '未绑定房产' }}</span>
      </div>
      <div class="oh-quick">
        <div v-for="q in quick" :key="q.t" class="ohq-item" @click="$router.push(q.path)">
          <div class="ohq-icon">{{ q.icon }}</div>
          <div>{{ q.t }}</div>
        </div>
      </div>
      <div class="sh-section-title">📢 最新公告</div>
      <div v-for="n in notices" class="so-card">
        <div class="so-header">
          <span>{{ n.title }}</span>
          <span style="font-size:12px;color:#a0aec0;">{{ formatDate(n.create_time) }}</span>
        </div>
        <div class="so-body">{{ n.summary || truncate(n.content, 60) }}</div>
      </div>
      <div v-if="!notices.length" style="text-align:center;color:#a0aec0;padding:30px;">暂无公告</div>
    </div>
  </MobileLayout>
</template>
<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import MobileLayout from '../MobileLayout.vue'

interface QuickItem { icon: string; t: string; path: string }

const user = ref<any>({})
const notices = ref<any[]>([])
const noticeBadge = ref(0)

const ownerTabs = computed(() => [
  { path: '/mobile/owner/home', icon: '🏠', label: '首页' },
  { path: '/mobile/owner/bill', icon: '💰', label: '账单' },
  { path: '/mobile/owner/repair', icon: '🔧', label: '报修' },
  { path: '/mobile/owner/notice', icon: '📢', label: '公告', badge: noticeBadge.value },
])

const quick: QuickItem[] = [
  { icon: '💰', t: '费用查询', path: '/mobile/owner/bill' },
  { icon: '🔧', t: '在线报修', path: '/mobile/owner/repair' },
  { icon: '🚗', t: '车辆管理', path: '/mobile/owner/vehicle' },
  { icon: '🚶', t: '访客邀请', path: '/mobile/owner/visitor' },
  { icon: '🗣️', t: '投诉建议', path: '/mobile/owner/complaint' },
]

function formatDate(dateStr: string) {
  return dateStr?.substring(0, 10)
}

function truncate(str: string, len: number) {
  return str?.substring(0, len)
}

onMounted(async () => {
  const token = localStorage.getItem('owner_token')
  try {
    const r = await fetch('/api/index', {
      headers: { Authorization: 'Bearer ' + token },
    })
    const d = await r.json()
    if (d.code === 0) {
      user.value = d.data.user
      notices.value = d.data.notices || []
    }
  } catch (e) {}

  // 获取角标数量
  try {
    const badgeRes = await fetch('/api/badge/counts', {
      headers: { Authorization: 'Bearer ' + token },
    })
    const badgeData = await badgeRes.json()
    if (badgeData.code === 0) {
      const currentCount = badgeData.data.notice || 0
      const lastSeen = parseInt(localStorage.getItem('owner_notice_last_read') || '0', 10)
      noticeBadge.value = Math.max(0, currentCount - lastSeen)
    }
  } catch (e) {}
})
</script>
<style scoped>
.pd { padding: 4px 0; }
.oh-user { font-size: 18px; font-weight: 700; color: #1a202c; margin-bottom: 16px; padding: 16px; background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; }
.oh-room { font-size: 13px; color: #a0aec0; font-weight: 400; }
.oh-quick { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; margin-bottom: 20px; }
.ohq-item { background: #fff; border-radius: 12px; padding: 16px 8px; text-align: center; border: 1px solid #e2e8f0; cursor: pointer; font-size: 12px; color: #4a5568; }
.ohq-icon { font-size: 28px; margin-bottom: 6px; }
.sh-section-title { font-size: 16px; font-weight: 600; color: #1a202c; margin-bottom: 12px; }
.so-card { background: #fff; border-radius: 12px; padding: 14px 16px; margin-bottom: 10px; border: 1px solid #e2e8f0; }
.so-header { display: flex; justify-content: space-between; margin-bottom: 6px; }
.so-header span:first-child { font-weight: 600; color: #1a202c; font-size: 14px; }
.so-body { font-size: 13px; color: #4a5568; line-height: 1.5; }
</style>
