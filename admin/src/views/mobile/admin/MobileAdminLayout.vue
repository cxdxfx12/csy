<template>
  <div class="layout-wrap">
    <!-- 顶部状态栏 -->
    <header class="top-bar">
      <div class="tb-left" @click="toggleSidebar">
        <div class="avatar-ring">
          <img :src="monkeyLogo" class="avatar-img" />
        </div>
      </div>
      <div class="tb-center">{{ pageTitle }}</div>
      <div class="tb-right" @click="router.push('/mobile/admin/messages')">
        <div class="bell-box">
          <Icon icon="ph:bell-duotone" class="bell-icon" />
          <span v-if="unreadCount" class="bell-badge">{{ unreadCount > 99 ? '99+' : unreadCount }}</span>
        </div>
      </div>
    </header>

    <!-- 侧滑菜单遮罩 -->
    <Transition name="overlay">
      <div v-if="sidebarOpen" class="sidebar-mask" @click="sidebarOpen = false" />
    </Transition>

    <!-- 侧滑菜单 -->
    <Transition name="drawer">
      <nav v-if="sidebarOpen" class="sidebar-drawer">
        <!-- 用户卡片 -->
        <div class="sd-user">
          <img :src="monkeyLogo" class="sdu-avatar" />
          <div class="sdu-info">
            <div class="sdu-name">{{ userStore.userInfo?.nickname || userStore.userInfo?.username || '管理员' }}</div>
            <div class="sdu-badge">
              <Icon icon="ph:shield-check-fill" />
              {{ userStore.userInfo?.role || '系统管理员' }}
            </div>
          </div>
          <Icon icon="ph:x" class="sdu-close" @click="sidebarOpen = false" />
        </div>

        <!-- 菜单项 -->
        <div class="sd-menu">
          <div
            class="sdm-item"
            :class="{ active: currentTab === 'dashboard' }"
            @click="navigate('/mobile/admin/dashboard')"
          >
            <div class="sdmi-icon"><Icon icon="ph:gauge-duotone" /></div>
            <span class="sdmi-label">控制台</span>
            <Icon icon="ph:caret-right" class="sdmi-arrow" />
          </div>
          <div
            class="sdm-item"
            :class="{ active: currentTab === 'menus' }"
            @click="navigate('/mobile/admin/menus')"
          >
            <div class="sdmi-icon"><Icon icon="ph:grid-nine-duotone" /></div>
            <span class="sdmi-label">功能菜单</span>
            <Icon icon="ph:caret-right" class="sdmi-arrow" />
          </div>
          <div
            class="sdm-item"
            :class="{ active: currentTab === 'messages' }"
            @click="navigate('/mobile/admin/messages')"
          >
            <div class="sdmi-icon"><Icon icon="ph:bell-duotone" /></div>
            <span class="sdmi-label">消息通知</span>
            <span v-if="unreadCount" class="sdmi-count">{{ unreadCount > 99 ? '99+' : unreadCount }}</span>
            <Icon icon="ph:caret-right" class="sdmi-arrow" />
          </div>
          <div
            class="sdm-item"
            :class="{ active: currentTab === 'profile' }"
            @click="navigate('/mobile/admin/profile')"
          >
            <div class="sdmi-icon"><Icon icon="ph:user-circle-duotone" /></div>
            <span class="sdmi-label">个人中心</span>
            <Icon icon="ph:caret-right" class="sdmi-arrow" />
          </div>

          <div class="sdm-divider"></div>

          <div class="sdm-item" @click="goPC">
            <div class="sdmi-icon sub"><Icon icon="ph:desktop-duotone" /></div>
            <span class="sdmi-label">切换到PC版</span>
            <Icon icon="ph:caret-right" class="sdmi-arrow" />
          </div>
          <div class="sdm-item danger" @click="logout">
            <div class="sdmi-icon danger"><Icon icon="ph:sign-out-duotone" /></div>
            <span class="sdmi-label">退出登录</span>
            <Icon icon="ph:caret-right" class="sdmi-arrow" />
          </div>
        </div>
      </nav>
    </Transition>

    <!-- 内容区 -->
    <main class="body-area">
      <router-view v-slot="{ Component }">
        <Transition name="page" mode="out-in">
          <component :is="Component" />
        </Transition>
      </router-view>
    </main>

    <!-- 底部Tab -->
    <footer class="bottom-tabs">
      <div
        class="bt-item"
        :class="{ active: currentTab === 'dashboard' }"
        @click="navigate('/mobile/admin/dashboard')"
      >
        <Icon :icon="currentTab === 'dashboard' ? 'ph:gauge-fill' : 'ph:gauge'" class="bt-icon" />
        <span class="bt-label">控制台</span>
      </div>
      <div
        class="bt-item"
        :class="{ active: currentTab === 'menus' }"
        @click="navigate('/mobile/admin/menus')"
      >
        <Icon :icon="currentTab === 'menus' ? 'ph:grid-nine-fill' : 'ph:grid-nine'" class="bt-icon" />
        <span class="bt-label">功能</span>
      </div>
      <div
        class="bt-item"
        :class="{ active: currentTab === 'messages' }"
        @click="navigate('/mobile/admin/messages')"
      >
        <div class="bt-icon-wrap">
          <Icon :icon="currentTab === 'messages' ? 'ph:bell-fill' : 'ph:bell'" class="bt-icon" />
          <span v-if="unreadCount" class="bt-badge">{{ unreadCount > 99 ? '99+' : unreadCount }}</span>
        </div>
        <span class="bt-label">消息</span>
      </div>
      <div
        class="bt-item"
        :class="{ active: currentTab === 'profile' }"
        @click="navigate('/mobile/admin/profile')"
      >
        <Icon :icon="currentTab === 'profile' ? 'ph:user-circle-fill' : 'ph:user-circle'" class="bt-icon" />
        <span class="bt-label">我的</span>
      </div>
    </footer>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Icon } from '@iconify/vue'
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
  sidebarOpen.value = false
  if (route.path !== path) router.push(path)
}

async function logout() {
  try {
    await ElMessageBox.confirm('确定要退出登录吗？', '提示', {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'warning',
    })
  } catch { return }
  userStore.logout()
  ElMessage.success('已退出')
  router.replace('/mobile/admin/login')
}

function goPC() {
  sidebarOpen.value = false
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
  } catch { /* ignore */ }
}

watch(() => userStore.token, (v) => { if (v) fetchUnread() }, { immediate: true })
</script>

<style scoped>
.layout-wrap {
  max-width: 480px;
  margin: 0 auto;
  min-height: 100vh;
  min-height: 100dvh;
  background: #f0f2f7;
  position: relative;
  overflow-x: hidden;
}

/* ========== 顶部栏 ========== */
.top-bar {
  position: fixed;
  top: 0;
  left: 50%;
  transform: translateX(-50%);
  width: 100%;
  max-width: 480px;
  height: 52px;
  background: linear-gradient(135deg, #1e293b, #334155);
  backdrop-filter: blur(20px);
  display: flex;
  align-items: center;
  padding: 0 14px;
  z-index: 200;
  gap: 12px;
}
.tb-left { flex-shrink: 0; cursor: pointer; }
.avatar-ring {
  width: 34px; height: 34px;
  border-radius: 10px;
  background: rgba(255,255,255,.1);
  border: 1px solid rgba(255,255,255,.15);
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
}
.avatar-img { width: 26px; height: 26px; border-radius: 7px; object-fit: contain; }
.tb-center {
  flex: 1;
  text-align: center;
  font-size: 17px;
  font-weight: 700;
  color: #f1f5f9;
  letter-spacing: 1px;
}
.tb-right { flex-shrink: 0; cursor: pointer; }
.bell-box { position: relative; width: 34px; height: 34px; display: flex; align-items: center; justify-content: center; }
.bell-icon { font-size: 22px; color: #94a3b8; transition: color .2s; }
.bell-box:active .bell-icon { color: #fff; }
.bell-badge {
  position: absolute;
  top: -2px; right: -4px;
  min-width: 18px; height: 18px;
  line-height: 18px;
  font-size: 10px; font-weight: 700;
  color: #fff; background: #ef4444;
  border-radius: 9px;
  text-align: center;
  padding: 0 5px;
  box-sizing: border-box;
  border: 2px solid #1e293b;
}

/* ========== 侧滑菜单 ========== */
.sidebar-mask {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,.5);
  backdrop-filter: blur(2px);
  z-index: 298;
}
.sidebar-drawer {
  position: fixed;
  top: 0; left: 0; bottom: 0;
  width: 290px;
  max-width: 80vw;
  background: #fff;
  z-index: 299;
  display: flex;
  flex-direction: column;
  box-shadow: 8px 0 40px rgba(0,0,0,.15);
}

/* 用户卡片 */
.sd-user {
  padding: 24px 18px 20px;
  background: linear-gradient(135deg, #1e293b, #334155);
  display: flex;
  align-items: center;
  gap: 14px;
}
.sdu-avatar { width: 48px; height: 48px; border-radius: 14px; object-fit: contain; background: rgba(255,255,255,.15); padding: 6px; }
.sdu-info { flex: 1; min-width: 0; }
.sdu-name { color: #f1f5f9; font-size: 16px; font-weight: 700; }
.sdu-badge {
  display: flex; align-items: center; gap: 4px;
  color: rgba(255,255,255,.6); font-size: 12px; margin-top: 4px;
}
.sdu-close { font-size: 22px; color: rgba(255,255,255,.5); cursor: pointer; flex-shrink: 0; padding: 4px; }
.sdu-close:active { color: #fff; }

/* 菜单 */
.sd-menu { flex: 1; overflow-y: auto; padding: 8px 0; }
.sdm-divider { height: 1px; background: #e2e8f0; margin: 6px 18px; }
.sdm-item {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 14px 20px;
  cursor: pointer;
  transition: background .15s;
}
.sdm-item:active { background: #f1f5f9; }
.sdm-item.active { background: #eff6ff; }
.sdm-item.danger { color: #ef4444; }
.sdmi-icon {
  width: 38px; height: 38px;
  border-radius: 12px;
  background: #f1f5f9;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
  color: #475569;
  flex-shrink: 0;
  transition: all .2s;
}
.sdm-item.active .sdmi-icon { background: #eff6ff; color: #3b82f6; }
.sdmi-icon.sub { background: #f8fafc; color: #94a3b8; }
.sdmi-icon.danger { background: #fef2f2; color: #ef4444; }
.sdmi-label { flex: 1; font-size: 14px; color: #334155; font-weight: 500; }
.sdm-item.danger .sdmi-label { color: #ef4444; }
.sdmi-arrow { font-size: 14px; color: #cbd5e1; flex-shrink: 0; }
.sdmi-count {
  background: #3b82f6; color: #fff;
  font-size: 10px; font-weight: 700;
  min-width: 20px; height: 20px;
  line-height: 20px; text-align: center;
  border-radius: 10px; padding: 0 6px;
}

/* 动画 */
.overlay-enter-active, .overlay-leave-active { transition: opacity .25s ease; }
.overlay-enter-from, .overlay-leave-to { opacity: 0; }
.drawer-enter-active, .drawer-leave-active { transition: transform .25s ease; }
.drawer-enter-from, .drawer-leave-to { transform: translateX(-100%); }

/* ========== 内容区 ========== */
.body-area {
  padding-top: 52px;
  padding-bottom: 64px;
  min-height: 100vh;
  min-height: 100dvh;
}
.page-enter-active, .page-leave-active { transition: opacity .2s ease, transform .2s ease; }
.page-enter-from { opacity: 0; transform: translateX(20px); }
.page-leave-to { opacity: 0; transform: translateX(-20px); }

/* ========== 底部Tab ========== */
.bottom-tabs {
  position: fixed;
  bottom: 0;
  left: 50%;
  transform: translateX(-50%);
  width: 100%;
  max-width: 480px;
  height: 62px;
  background: rgba(255,255,255,.92);
  backdrop-filter: blur(20px);
  -webkit-backdrop-filter: blur(20px);
  border-top: 1px solid rgba(0,0,0,.06);
  display: flex;
  z-index: 200;
  padding-bottom: env(safe-area-inset-bottom, 0);
}
.bt-item {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 3px;
  cursor: pointer;
  color: #94a3b8;
  transition: color .2s;
}
.bt-item.active { color: #3b82f6; }
.bt-icon { font-size: 24px; transition: transform .2s; }
.bt-item:active .bt-icon { transform: scale(.9); }
.bt-item.active .bt-icon { transform: scale(1.05); }
.bt-icon-wrap { position: relative; display: flex; }
.bt-label { font-size: 11px; font-weight: 500; }
.bt-badge {
  position: absolute;
  top: -6px; right: -12px;
  min-width: 18px; height: 18px;
  line-height: 18px;
  font-size: 10px; font-weight: 700;
  color: #fff; background: #ef4444;
  border-radius: 9px;
  text-align: center;
  padding: 0 5px;
  box-sizing: border-box;
}
</style>
