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
    <!-- 主题切换器 -->
    <div class="theme-switcher">
      <button class="theme-trigger" @click.stop="themeOpen = !themeOpen" title="切换主题">🎨</button>
      <div class="theme-panel" v-if="themeOpen" @click.stop>
        <div class="theme-panel-title">选择主题</div>
        <div v-for="t in themeList" :key="t.id" class="theme-item" :class="{active: currentTheme === t.id}" @click="switchTheme(t.id)">
          <span class="theme-swatch" :style="{background: `linear-gradient(135deg, ${t.preview[0]}, ${t.preview[1]})`}"></span>
          <span class="theme-name">{{ t.icon }} {{ t.name }}</span>
          <span v-if="currentTheme === t.id" class="theme-check">✓</span>
        </div>
      </div>
    </div>
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
import { themes as themeList, current as currentTheme, applyTheme } from '@/stores/theme'

const router = useRouter()
const route = useRoute()
const userStore = useUserStore()
const appStore = useAppStore()

const themeOpen = ref(false)
function switchTheme(id: string) {
  applyTheme(id)
  themeOpen.value = false
}
function closeThemePanel(e: MouseEvent) {
  if (themeOpen.value && !(e.target as HTMLElement).closest('.theme-switcher')) {
    themeOpen.value = false
  }
}

// 通知角标
const badgeCounts = ref<Record<string, number>>({ bill: 0, repair: 0, complaint: 0, order: 0, vote: 0, activity: 0 })
const badgeRaw = ref<Record<string, number>>({ bill: 0, repair: 0, complaint: 0, order: 0, vote: 0, activity: 0 })
let timer: any = null
let sseConnection: EventSource | null = null
let sseReconnectTimer: any = null
let sseRetryCount = 0
const SSE_MAX_RETRIES = 10
const SSE_BASE_DELAY = 2000
const SSE_MAX_DELAY = 60000
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
    const keys = ['bill', 'repair', 'complaint', 'order', 'vote', 'activity']
    keys.forEach(k => { badgeRaw.value[k] = d[k] || 0 })

    const seen = JSON.parse(localStorage.getItem(SEEN_KEY) || '{}')
    keys.forEach(k => { badgeCounts.value[k] = Math.max(0, (d[k] || 0) - Number(seen[k] || 0)) })
  } catch { /* 静默 */ }
}

function onBellOpen() {
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
  document.addEventListener('click', closeThemePanel)
  fetchBadges()
  timer = setInterval(fetchBadges, 30000)
  connectSSE()
})

onUnmounted(() => {
  document.removeEventListener('click', closeThemePanel)
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
      sseRetryCount = 0
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

    sseConnection.onerror = () => {
      sseConnection?.close()
      sseRetryCount++
      if (sseRetryCount <= SSE_MAX_RETRIES) {
        const delay = Math.min(SSE_BASE_DELAY * Math.pow(2, sseRetryCount - 1), SSE_MAX_DELAY)
        console.warn(`[Admin SSE] 连接断开，${delay/1000}s 后重试 (${sseRetryCount}/${SSE_MAX_RETRIES})`)
        sseReconnectTimer = setTimeout(connectSSE, delay)
      } else {
        console.warn('[Admin SSE] 重试次数已用尽，停止连接')
      }
    }
  } catch (e) {
    console.warn('[Admin SSE] 连接失败，使用轮询模式')
  }
}

function disconnectSSE() {
  sseRetryCount = SSE_MAX_RETRIES + 1 // 阻止重连
  if (sseConnection) { sseConnection.close(); sseConnection = null }
  if (sseReconnectTimer) { clearTimeout(sseReconnectTimer); sseReconnectTimer = null }
}
</script>

<style scoped>
.header-left { display: flex; align-items: center; gap: 14px; }
.header-center { position: absolute; left: 50%; transform: translateX(-50%); }
.bigscreen-btn { font-weight: 600; background: var(--accent-gradient); border: none; box-shadow: 0 2px 8px var(--accent-shadow); transition: all 0.3s; }
.bigscreen-btn:hover { box-shadow: 0 4px 16px var(--accent-shadow); transform: translateY(-1px); }
.header-right { margin-left: auto; display: flex; align-items: center; gap: 16px; }
.user-info { display: flex; align-items: center; gap: 8px; cursor: pointer; padding: 4px 12px 4px 6px; border-radius: 24px; transition: all 0.2s; }
.user-info:hover { background: var(--bg-badge-hover); }
.user-name { font-size: 14px; color: var(--text-2); font-weight: 600; }
.community-tag { margin-left: 4px; }
.notice-badge { margin-right: 8px; }

/* ===== 主题切换器 ===== */
.theme-switcher { position: relative; z-index: 9999; }
.theme-trigger { width:36px;height:36px;background:var(--bg-input);border:1px solid var(--border-1);border-radius:8px;cursor:pointer;font-size:16px;display:flex;align-items:center;justify-content:center;transition:all .25s;color:var(--text-3) }
.theme-trigger:hover { border-color:var(--accent);background:var(--bg-table-hover);transform:scale(1.05) }
.theme-panel { position:absolute;top:calc(100% + 8px);right:0;width:200px;background:var(--bg-card);border:1px solid var(--border-1);border-radius:12px;padding:8px;box-shadow:var(--shadow-modal);animation:panelIn .2s ease-out;z-index:10000 }
@keyframes panelIn{from{opacity:0;transform:translateY(-8px)}to{opacity:1;transform:translateY(0)}}
.theme-panel-title { font-size:12px;color:var(--text-5);padding:6px 10px 8px;font-weight:600;text-transform:uppercase;letter-spacing:.5px }
.theme-item { display:flex;align-items:center;gap:10px;padding:8px 10px;border-radius:8px;cursor:pointer;transition:all .2s }
.theme-item:hover { background:var(--bg-table-hover) }
.theme-item.active { background:rgba(var(--accent-rgb),.1) }
.theme-swatch { width:24px;height:24px;border-radius:6px;flex-shrink:0;border:2px solid rgba(255,255,255,.1) }
.theme-name { flex:1;font-size:13px;color:var(--text-2);font-weight:500 }
.theme-check { color:var(--accent);font-weight:700;font-size:14px }
</style>

<style>
/* 弹窗内容不在 scoped 中生效 */
.notify-popover { padding: 0 !important; background: var(--bg-card) !important; border: 1px solid var(--border-2) !important; }
.notify-panel { max-height: 420px; overflow-y: auto; }
.notify-header { display: flex; justify-content: space-between; align-items: center; padding: 14px 16px; border-bottom: 1px solid var(--border-2); font-size: 14px; font-weight: 600; color: var(--text-1); }
.notify-dismiss-all { font-size: 12px; color: var(--accent); cursor: pointer; font-weight: 400; }
.notify-dismiss-all:hover { opacity: 0.8; }
.notify-empty { padding: 32px 16px; text-align: center; color: var(--text-5); font-size: 14px; }
.notify-list { padding: 4px 0; }
.notify-item { display: flex; align-items: center; gap: 12px; padding: 12px 16px; cursor: pointer; transition: background 0.15s; }
.notify-item:hover { background: var(--bg-table-hover); }
.notify-item .notify-icon { font-size: 20px; width: 28px; text-align: center; flex-shrink: 0; }
.notify-item .notify-body { flex: 1; min-width: 0; display: flex; flex-direction: column; }
.notify-item .notify-label { font-size: 14px; color: var(--text-2); font-weight: 500; }
.notify-item .notify-desc { font-size: 12px; color: var(--text-5); }
.notify-count { flex-shrink: 0; }
</style>
