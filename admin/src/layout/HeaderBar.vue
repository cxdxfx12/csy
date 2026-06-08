<template>
  <div class="header-left">
    <el-button @click="appStore.toggleSidebar" :icon="appStore.sidebarCollapsed ? 'Expand' : 'Fold'" text />
    <el-breadcrumb separator="/">
      <el-breadcrumb-item :to="{ path: '/dashboard' }">工作台</el-breadcrumb-item>
      <el-breadcrumb-item v-if="route.meta.title">{{ route.meta.title as string }}</el-breadcrumb-item>
    </el-breadcrumb>
  </div>
  <div class="header-center">
    <el-tooltip content="智慧物业数据大屏" placement="bottom" effect="dark">
      <el-button type="primary" :icon="'DataAnalysis'" round size="default" @click="$router.push('/bigscreen')" class="bigscreen-btn">
        数据大屏
      </el-button>
    </el-tooltip>
  </div>
  <div class="header-right">
    <!-- 通知铃铛 -->
    <el-popover placement="bottom-end" :width="360" trigger="click" popper-class="notify-popover" @show="onBellOpen">
      <template #reference>
        <el-badge :value="totalBadge" :hidden="totalBadge === 0" :max="99" class="notice-badge">
          <el-button :icon="'Bell'" text style="font-size:18px;" />
        </el-badge>
      </template>
      <div class="notify-panel">
        <div class="notify-header">
          <span>📬 待处理提醒</span>
          <span class="notify-dismiss-all" @click="dismissAll">全部标为已读</span>
        </div>
        <div v-if="notifyItems.length === 0" class="notify-empty">✅ 暂无待处理事项</div>
        <div v-else class="notify-list">
          <div v-for="item in notifyItems" :key="item.key" class="notify-item" @click="goRoute(item.route, item.key)">
            <span class="notify-icon">{{ item.icon }}</span>
            <div class="notify-body">
              <span class="notify-label">{{ item.label }}</span>
              <span class="notify-desc">{{ item.desc }}</span>
            </div>
            <el-badge :value="item.count" class="notify-count" :type="item.badgeType" />
          </div>
        </div>
      </div>
    </el-popover>
    <el-dropdown trigger="click">
      <span class="user-info">
        <el-avatar :size="32" :src="userStore.userInfo?.avatar || undefined" icon="UserFilled" />
        <span class="user-name">{{ userStore.userInfo?.nickname || '管理员' }}</span>
        <el-tag v-if="userStore.userInfo?.community_name" size="small" type="warning" class="community-tag">{{ userStore.userInfo.community_name }}</el-tag>
      </span>
      <template #dropdown>
        <el-dropdown-menu>
          <el-dropdown-item @click="$router.push('/profile')">
            <el-icon><User /></el-icon> 个人中心
          </el-dropdown-item>
          <el-dropdown-item divided @click="handleLogout">
            <el-icon><SwitchButton /></el-icon> 退出登录
          </el-dropdown-item>
        </el-dropdown-menu>
      </template>
    </el-dropdown>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useUserStore } from '@/stores/user'
import { useAppStore } from '@/stores/app'
import { apiGet } from '@/utils/request'
import { ElMessage } from 'element-plus'

const router = useRouter()
const route = useRoute()
const userStore = useUserStore()
const appStore = useAppStore()

// 通知角标
const badgeCounts = ref<Record<string, number>>({ bill: 0, repair: 0, complaint: 0, order: 0, vote: 0, activity: 0 })
const badgeRaw = ref<Record<string, number>>({ bill: 0, repair: 0, complaint: 0, order: 0, vote: 0, activity: 0 })
let timer: any = null
let sseConnection: EventSource | null = null
let sseReconnectTimer: any = null
const SEEN_KEY = 'admin_badge_seen'

const notifyMeta: Record<string, { icon: string; label: string; route: string; badgeType: any }> = {
  bill:      { icon: '💰', label: '待缴账单',   route: '/charge/bill',      badgeType: 'danger' },
  repair:    { icon: '🔧', label: '待处理报修', route: '/repair/order',     badgeType: 'warning' },
  complaint: { icon: '📝', label: '待处理投诉', route: '/complaint/index',    badgeType: 'danger' },
  order:     { icon: '📋', label: '进行中工单', route: '/repair/order',     badgeType: 'primary' },
  vote:      { icon: '🗳', label: '进行中投票', route: '/owner/vote',        badgeType: 'success' },
  activity:  { icon: '🎉', label: '报名中活动', route: '/owner/activity',    badgeType: 'warning' },
}

const totalBadge = computed(() => {
  return Object.values(badgeCounts.value).reduce((a, b) => a + (typeof b === 'number' ? b : 0), 0)
})

const notifyItems = computed(() => {
  return Object.entries(badgeCounts.value)
    .filter(([, v]) => v > 0)
    .map(([key, count]) => {
      const meta = notifyMeta[key]
      return {
        key,
        icon: meta.icon,
        label: meta.label,
        route: meta.route,
        count,
        badgeType: meta.badgeType,
        desc: `共 ${count} 条`,
      }
    })
})

async function fetchBadges() {
  try {
    const res = await apiGet<Record<string, number>>('/admin/badge/counts')
    const d = res.data || res || {}
    // 按 category 读取
    const keys = ['bill', 'repair', 'complaint', 'order', 'vote', 'activity']
    keys.forEach(k => { badgeRaw.value[k] = d[k] || 0 })

    const seen = JSON.parse(localStorage.getItem(SEEN_KEY) || '{}')
    keys.forEach(k => { badgeCounts.value[k] = Math.max(0, (d[k] || 0) - Number(seen[k] || 0)) })
  } catch { /* 静默 */ }
}

function onBellOpen() {
  // 打开时暂不自动清，等用户点击具体项
}

function goRoute(path: string, key: string) {
  dismissBadge(key)
  router.push(path)
}

function dismissBadge(key: string) {
  const seen = JSON.parse(localStorage.getItem(SEEN_KEY) || '{}')
  seen[key] = badgeRaw.value[key] || 0
  localStorage.setItem(SEEN_KEY, JSON.stringify(seen))
  badgeCounts.value[key] = 0
}

function dismissAll() {
  const seen: Record<string, number> = {}
  Object.keys(notifyMeta).forEach(k => { seen[k] = badgeRaw.value[k] || 0 })
  localStorage.setItem(SEEN_KEY, JSON.stringify(seen))
  Object.keys(notifyMeta).forEach(k => { badgeCounts.value[k] = 0 })
}

function handleLogout() {
  userStore.logout()
  router.push('/login')
}

onMounted(() => {
  fetchBadges()
  timer = setInterval(fetchBadges, 30000)
  connectSSE()
})

onUnmounted(() => {
  if (timer) clearInterval(timer)
  disconnectSSE()
})

// ===== SSE 实时推送 =====
function connectSSE() {
  const token = localStorage.getItem('admin_token') || localStorage.getItem('token')
  if (!token) return

  try {
    const baseUrl = import.meta.env?.VITE_API_BASE || window.location.origin
    const url = `${baseUrl}/api/admin/sse/stream?token=${encodeURIComponent(token)}`
    sseConnection = new EventSource(url)

    sseConnection.addEventListener('connected', () => {
      console.log('[Admin SSE] 实时推送已连接')
      if (sseReconnectTimer) { clearTimeout(sseReconnectTimer); sseReconnectTimer = null }
    })

    sseConnection.addEventListener('repair_new', (e: any) => {
      try {
        const data = JSON.parse(e.data)
        console.log('[Admin SSE] 新报修:', data)
        fetchBadges()
        ElMessage.info({ message: `🔧 ${data.title}: ${data.content}`, duration: 5000, showClose: true })
      } catch (_) {}
    })

    sseConnection.addEventListener('repair_assign', (e: any) => {
      try {
        const data = JSON.parse(e.data)
        console.log('[Admin SSE] 派单通知:', data)
        fetchBadges()
      } catch (_) {}
    })

    sseConnection.addEventListener('heartbeat', () => {})

    sseConnection.addEventListener('timeout', () => {
      console.log('[Admin SSE] 连接刷新')
      sseConnection?.close()
    })

    sseConnection.addEventListener('error', () => {
      console.warn('[Admin SSE] 连接断开')
      sseConnection?.close()
    })

    sseConnection.onerror = () => {
      sseConnection?.close()
      sseReconnectTimer = setTimeout(connectSSE, 3000)
    }
  } catch (e) {
    console.warn('[Admin SSE] 连接失败，使用轮询模式')
  }
}

function disconnectSSE() {
  if (sseConnection) { sseConnection.close(); sseConnection = null }
  if (sseReconnectTimer) { clearTimeout(sseReconnectTimer); sseReconnectTimer = null }
}
</script>

<style scoped>
.header-left { display: flex; align-items: center; gap: 14px; }
.header-center { position: absolute; left: 50%; transform: translateX(-50%); }
.bigscreen-btn { font-weight: 600; background: linear-gradient(135deg, #06b6d4, #3b82f6); border: none; box-shadow: 0 2px 8px rgba(6,182,212,0.3); transition: all 0.3s; }
.bigscreen-btn:hover { box-shadow: 0 4px 16px rgba(6,182,212,0.45); transform: translateY(-1px); }
.header-right { margin-left: auto; display: flex; align-items: center; gap: 16px; }
.user-info { display: flex; align-items: center; gap: 8px; cursor: pointer; padding: 4px 12px 4px 6px; border-radius: 24px; transition: all 0.2s; }
.user-info:hover { background: #f1f5f9; }
.user-name { font-size: 14px; color: #334155; font-weight: 600; }
.community-tag { margin-left: 4px; }
.notice-badge { margin-right: 8px; }
</style>

<style>
/* 弹窗内容不在 scoped 中生效 */
.notify-popover { padding: 0 !important; }
.notify-panel { max-height: 420px; overflow-y: auto; }
.notify-header { display: flex; justify-content: space-between; align-items: center; padding: 14px 16px; border-bottom: 1px solid #f0f0f0; font-size: 14px; font-weight: 600; }
.notify-dismiss-all { font-size: 12px; color: #409EFF; cursor: pointer; font-weight: 400; }
.notify-dismiss-all:hover { color: #337ecc; }
.notify-empty { padding: 32px 16px; text-align: center; color: #999; font-size: 14px; }
.notify-list { padding: 4px 0; }
.notify-item { display: flex; align-items: center; gap: 12px; padding: 12px 16px; cursor: pointer; transition: background 0.15s; }
.notify-item:hover { background: #f5f7fa; }
.notify-item .notify-icon { font-size: 20px; width: 28px; text-align: center; flex-shrink: 0; }
.notify-item .notify-body { flex: 1; min-width: 0; display: flex; flex-direction: column; }
.notify-item .notify-label { font-size: 14px; color: #333; font-weight: 500; }
.notify-item .notify-desc { font-size: 12px; color: #999; }
.notify-count { flex-shrink: 0; }
</style>
