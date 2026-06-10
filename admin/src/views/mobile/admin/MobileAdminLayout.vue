<template>
  <div class="mal-wrap">
    <!-- 顶部栏 -->
    <header class="mal-header">
      <div class="malh-left" @click="toggleSidebar">
        <span class="malh-avatar"><img :src="monkeyLogo" class="malh-avatar-img" /></span>
      </div>
      <div class="malh-title">{{ pageTitle }}</div>
      <div class="malh-right" @click="router.push('/mobile/admin/messages')">
        <span v-if="unreadCount" class="malh-badge">{{ unreadCount > 99 ? '99+' : unreadCount }}</span>
        <span style="font-size:20px;">🔔</span>
      </div>
    </header>

    <!-- 侧滑菜单 -->
    <div class="mal-overlay" v-if="sidebarOpen" @click="sidebarOpen = false"></div>
    <nav class="mal-sidebar" :class="{ open: sidebarOpen }">
      <div class="mals-head">
        <img :src="monkeyLogo" class="mals-logo" />
        <div>
          <div class="mals-name">{{ userStore.userInfo?.nickname || userStore.userInfo?.username || '管理员' }}</div>
          <div class="mals-role">{{ userStore.userInfo?.role || '系统管理' }}</div>
        </div>
      </div>
      <div class="mals-list">
        <div class="mals-item" @click="navigate('/mobile/admin/dashboard'); sidebarOpen=false">
          <span class="mals-icon">📊</span> 控制台
        </div>
        <div class="mals-item" @click="navigate('/mobile/admin/menus'); sidebarOpen=false">
          <span class="mals-icon">📋</span> 功能菜单
        </div>
        <div class="mals-item" @click="navigate('/mobile/admin/messages'); sidebarOpen=false">
          <span class="mals-icon">🔔</span> 消息通知
        </div>
        <div class="mals-item" @click="navigate('/mobile/admin/profile'); sidebarOpen=false">
          <span class="mals-icon">👤</span> 个人中心
        </div>
        <div class="mals-divider"></div>
        <div class="mals-item" @click="goPC">
          <span class="mals-icon">💻</span> 切换到PC版
        </div>
        <div class="mals-item danger" @click="logout">
          <span class="mals-icon">🚪</span> 退出登录
        </div>
      </div>
    </nav>

    <!-- 内容区 -->
    <div class="mal-body">
      <router-view />
    </div>

    <!-- 底部Tab -->
    <footer class="mal-tab">
      <div class="malt-item" :class="{ active: currentTab === 'dashboard' }" @click="navigate('/mobile/admin/dashboard')">
        <div class="malt-icon">📊</div>
        <div class="malt-label">控制台</div>
      </div>
      <div class="malt-item" :class="{ active: currentTab === 'menus' }" @click="navigate('/mobile/admin/menus')">
        <div class="malt-icon">📋</div>
        <div class="malt-label">功能</div>
      </div>
      <div class="malt-item" :class="{ active: currentTab === 'messages' }" @click="navigate('/mobile/admin/messages')">
        <div class="malt-icon">
          🔔
          <span v-if="unreadCount" class="malt-badge">{{ unreadCount > 99 ? '99+' : unreadCount }}</span>
        </div>
        <div class="malt-label">消息</div>
      </div>
      <div class="malt-item" :class="{ active: currentTab === 'profile' }" @click="navigate('/mobile/admin/profile')">
        <div class="malt-icon">👤</div>
        <div class="malt-label">我的</div>
      </div>
    </footer>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ElMessage, ElMessageBox } from 'element-plus'
import { useUserStore } from '@/stores/user'
import monkeyLogo from '@/assets/images/monkey-ico.png'

const route = useRoute()
const router = useRouter()
const userStore = useUserStore()

const sidebarOpen = ref(false)
const unreadCount = ref(0)

// 当前Tab
const currentTab = computed(() => {
  const p = route.path
  if (p.includes('/dashboard')) return 'dashboard'
  if (p.includes('/menus')) return 'menus'
  if (p.includes('/messages')) return 'messages'
  if (p.includes('/profile')) return 'profile'
  return 'dashboard'
})

// 页面标题
const pageTitle = computed(() => {
  const map: Record<string, string> = {
    dashboard: '控制台',
    menus: '功能菜单',
    messages: '消息通知',
    profile: '个人中心',
  }
  return map[currentTab.value] || '管理后台'
})

function toggleSidebar() { sidebarOpen.value = !sidebarOpen.value }

function navigate(path: string) {
  if (route.path !== path) router.push(path)
}

async function logout() {
  try {
    await ElMessageBox.confirm('确定要退出登录吗？', '提示', { confirmButtonText: '确定', cancelButtonText: '取消', type: 'warning' })
  } catch { return }
  userStore.logout()
  ElMessage.success('已退出')
  router.replace('/mobile/admin/login')
}

function goPC() {
  router.push('/dashboard')
}

// 获取未读消息数
async function fetchUnread() {
  try {
    const r = await fetch('/api/admin/notification/unreadCount', {
      headers: { Authorization: `Bearer ${userStore.token}` },
    })
    const d = await r.json()
    if (d.code === 0) unreadCount.value = d.data || 0
  } catch {}
}

watch(() => userStore.token, (v) => { if (v) fetchUnread() }, { immediate: true })
</script>

<style scoped>
.mal-wrap { max-width: 480px; margin: 0 auto; min-height: 100vh; background: #f0f2f5; position: relative; }

/* 顶部栏 */
.mal-header { position: fixed; top: 0; left: 50%; transform: translateX(-50%); width: 100%; max-width: 480px; height: 48px; background: linear-gradient(135deg, #1a365d, #2b6cb0); display: flex; align-items: center; padding: 0 12px; z-index: 200; }
.malh-left { width: 36px; cursor: pointer; display: flex; align-items: center; }
.malh-avatar-img { width: 28px; height: 28px; border-radius: 6px; object-fit: contain; }
.malh-title { flex: 1; text-align: center; font-size: 16px; font-weight: 600; color: #fff; }
.malh-right { width: 36px; text-align: right; cursor: pointer; position: relative; }
.malh-badge { position: absolute; top: -6px; right: -6px; min-width: 18px; height: 18px; line-height: 18px; text-align: center; font-size: 10px; font-weight: 700; color: #fff; background: #e53e3e; border-radius: 9px; padding: 0 5px; z-index: 1; }

/* 侧滑菜单 */
.mal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.4); z-index: 298; }
.mal-sidebar { position: fixed; top: 0; left: 0; bottom: 0; width: 280px; max-width: 80vw; background: #fff; z-index: 299; transform: translateX(-100%); transition: transform .25s ease; display: flex; flex-direction: column; }
.mal-sidebar.open { transform: translateX(0); }
.mals-head { display: flex; align-items: center; gap: 12px; padding: 20px 16px; background: linear-gradient(135deg, #1a365d, #2b6cb0); }
.mals-logo { width: 40px; height: 40px; border-radius: 10px; object-fit: contain; }
.mals-name { color: #fff; font-size: 15px; font-weight: 600; }
.mals-role { color: rgba(255,255,255,0.7); font-size: 12px; margin-top: 2px; }
.mals-list { flex: 1; overflow-y: auto; padding: 8px 0; }
.mals-divider { height: 1px; background: #e2e8f0; margin: 8px 16px; }
.mals-item { display: flex; align-items: center; gap: 12px; padding: 14px 20px; font-size: 14px; color: #2d3748; cursor: pointer; }
.mals-item:active { background: #f7f8fc; }
.mals-item.danger { color: #e53e3e; }
.mals-icon { font-size: 18px; width: 24px; text-align: center; }

/* 内容区 */
.mal-body { padding-top: 48px; padding-bottom: 60px; min-height: 100vh; }

/* 底部Tab */
.mal-tab { position: fixed; bottom: 0; left: 50%; transform: translateX(-50%); width: 100%; max-width: 480px; height: 56px; background: #fff; border-top: 1px solid #e2e8f0; display: flex; z-index: 200; padding-bottom: env(safe-area-inset-bottom); }
.malt-item { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 2px; cursor: pointer; color: #a0aec0; transition: color .2s; }
.malt-item.active { color: #2b6cb0; }
.malt-icon { font-size: 22px; position: relative; }
.malt-label { font-size: 11px; }
.malt-badge { position: absolute; top: -8px; right: -14px; min-width: 18px; height: 18px; line-height: 18px; text-align: center; font-size: 10px; font-weight: 700; color: #fff; background: #e53e3e; border-radius: 9px; padding: 0 5px; box-sizing: border-box; }
</style>
