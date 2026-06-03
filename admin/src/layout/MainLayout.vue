<template>
  <el-container class="main-container">
    <el-aside :width="appStore.sidebarCollapsed ? '64px' : '240px'" class="main-sidebar">
      <Sidebar />
    </el-aside>
    <el-container>
      <el-header class="main-header" :style="{ left: appStore.sidebarCollapsed ? '64px' : '240px' }">
        <HeaderBar />
      </el-header>
      <TabsView />
      <el-main class="main-content">
        <!-- 高档页面横幅 -->
        <div class="premium-banner" v-if="currentTitle">
          <div class="banner-bg-layer"></div>
          <div class="banner-glow glow-1"></div>
          <div class="banner-glow glow-2"></div>
          <div class="banner-inner">
            <div class="banner-text">
              <h1 class="banner-title">{{ currentTitle }}</h1>
              <p class="banner-breadcrumb">
                <span>工作台</span>
                <span class="sep">/</span>
                <span>{{ currentTitle }}</span>
              </p>
            </div>
            <div class="banner-decoration">
              <div class="deco-circle c1"></div>
              <div class="deco-circle c2"></div>
              <div class="deco-circle c3"></div>
            </div>
          </div>
        </div>
        <router-view />
      </el-main>
    </el-container>
  </el-container>
</template>

<script setup lang="ts">
import { onMounted, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useUserStore } from '@/stores/user'
import { useAppStore } from '@/stores/app'
import Sidebar from './Sidebar.vue'
import HeaderBar from './HeaderBar.vue'
import TabsView from './TabsView.vue'

const userStore = useUserStore()
const appStore = useAppStore()
const router = useRouter()
const route = useRoute()

const currentTitle = computed(() => (route.meta.title as string) || '')

onMounted(async () => {
  try {
    await userStore.fetchInfo()
  } catch {
    router.push('/login')
  }
})
</script>

<style scoped>
.main-container { height: 100vh; }
.main-sidebar { background: #fff; border-right: 1px solid #e2e8f0; transition: width 0.3s; overflow: hidden; display: flex; flex-direction: column; }
.main-header { position: fixed; top: 0; right: 0; height: 60px; background: #fff; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; padding: 0 20px; z-index: 100; transition: left 0.3s; }
.main-content { margin-top: 60px; padding: 20px; background: #f0f2f5; min-height: calc(100vh - 60px); }

/* ===== 高档页面横幅 ===== */
.premium-banner {
  position: relative;
  border-radius: 14px;
  overflow: hidden;
  margin-bottom: 18px;
  padding: 26px 30px;
  min-height: 72px;
  display: flex;
  align-items: center;
}
.banner-bg-layer {
  position: absolute; inset: 0;
  background: linear-gradient(135deg, #0f1b35 0%, #1a3a6b 25%, #2563eb 50%, #6d28d9 80%, #4c1d95 100%);
}
.banner-bg-layer::after {
  content: '';
  position: absolute; inset: 0;
  background: url("data:image/svg+xml,%3Csvg width='60' height='60' xmlns='http://www.w3.org/2000/svg'%3E%3Cdefs%3E%3Cpattern id='grid' width='60' height='60' patternUnits='userSpaceOnUse'%3E%3Cpath d='M 60 0 L 0 0 0 60' fill='none' stroke='rgba(255,255,255,0.03)' stroke-width='1'/%3E%3C/pattern%3E%3C/defs%3E%3Crect width='60' height='60' fill='url(%23grid)'/%3E%3C/svg%3E");
}
.banner-glow {
  position: absolute;
  border-radius: 50%;
  filter: blur(60px);
  opacity: 0.25;
}
.glow-1 { width: 220px; height: 220px; background: #3b82f6; top: -80px; right: -40px; animation: bannerFloat 6s ease-in-out infinite; }
.glow-2 { width: 160px; height: 160px; background: #8b5cf6; bottom: -60px; left: 20%; animation: bannerFloat 8s ease-in-out infinite reverse; }
@keyframes bannerFloat {
  0%, 100% { transform: translate(0, 0) scale(1); }
  50% { transform: translate(15px, -10px) scale(1.1); }
}
.banner-inner {
  position: relative; z-index: 2;
  display: flex; align-items: center; justify-content: space-between;
  width: 100%;
}
.banner-text { flex: 1; }
.banner-title {
  font-size: 21px; font-weight: 700; color: #fff;
  margin: 0 0 4px; letter-spacing: 0.6px;
  text-shadow: 0 2px 8px rgba(0,0,0,0.18);
}
.banner-breadcrumb {
  font-size: 12px; color: rgba(255,255,255,0.7); margin: 0;
  display: flex; align-items: center; gap: 6px;
}
.banner-breadcrumb .sep { opacity: 0.4; }
.banner-decoration {
  display: flex; gap: 10px; flex-shrink: 0;
}
.deco-circle {
  width: 10px; height: 10px; border-radius: 50%;
  background: rgba(255,255,255,0.3);
  animation: decoPulse 2s ease-in-out infinite;
}
.c2 { animation-delay: 0.3s; width: 14px; height: 14px; }
.c3 { animation-delay: 0.6s; }
@keyframes decoPulse {
  0%, 100% { opacity: 0.3; transform: scale(1); }
  50% { opacity: 0.8; transform: scale(1.3); }
}
</style>
