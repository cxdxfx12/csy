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

      <!-- 版权信息 -->
      <footer class="site-footer">
        <div class="footer-inner">
          <img :src="monkeyIco" alt="" class="footer-logo" />
          <div class="footer-info">
            <span class="footer-brand">
              <strong>杭州喵喵至家网络有限公司</strong>
            </span>
            <span class="footer-sep">·</span>
            <span>倾力打造</span>
            <span class="footer-sep">·</span>
            <strong class="footer-system">大圣智慧物业管理系统</strong>
          </div>
          <div class="footer-meta">
            <span class="footer-phone">
              客服电话：<a href="tel:17771300068">17771300068</a> / <a href="tel:19171045360">19171045360</a>
            </span>
            <span class="footer-sep">|</span>
            <a href="https://www.hbdxm.com" target="_blank">www.hbdxm.com</a>
            <span class="footer-sep">|</span>
            <a href="https://beian.miit.gov.cn/" target="_blank" rel="noopener">鄂ICP备2025153909号</a>
          </div>
        </div>
      </footer>
    </el-container>
    <SmartGuide />
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
import SmartGuide from '@/components/SmartGuide.vue'
import monkeyIco from '@/assets/images/monkey-ico.png'

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
.main-container { min-height: 100vh; }
.main-sidebar { background: var(--bg-sidebar); border-right: 1px solid var(--border-1); transition: width 0.3s, background 0.3s, border-color 0.3s; overflow: hidden; display: flex; flex-direction: column; }
.main-header { position: fixed; top: 0; right: 0; height: 60px; background: var(--bg-header); border-bottom: 1px solid var(--border-1); display: flex; align-items: center; padding: 0 20px; z-index: 9999; transition: left 0.3s, background 0.3s, border-color 0.3s; }
.main-content { margin-top: 60px; padding: 20px; background: var(--bg-content); flex: 1; overflow: auto; transition: background 0.3s; }

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
  background: var(--banner-gradient);
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

/* ===== 版权信息 ===== */
.site-footer {
  background: var(--bg-footer);
  border-top: 2px solid rgba(99, 102, 241, 0.3);
  padding: 16px 24px;
  flex-shrink: 0;
}
.footer-inner {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
  flex-wrap: wrap;
  max-width: 1200px;
  margin: 0 auto;
}
.footer-logo {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  object-fit: contain;
  filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3));
  animation: logoFloat 3s ease-in-out infinite;
}
@keyframes logoFloat {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-2px); }
}
.footer-info {
  font-size: 13px;
  color: rgba(255,255,255,0.75);
  letter-spacing: 0.5px;
}
.footer-brand {
  color: #a5b4fc;
}
.footer-system {
  color: #c7d2fe;
  text-shadow: 0 1px 6px rgba(129,140,248,0.35);
}
.footer-meta {
  font-size: 11.5px;
  color: rgba(255,255,255,0.5);
  display: flex;
  align-items: center;
  gap: 6px;
}
.footer-sep {
  opacity: 0.3;
}
.footer-phone a,
.footer-meta a {
  color: rgba(167, 139, 250, 0.85);
  text-decoration: none;
  transition: color 0.2s;
}
.footer-phone a:hover,
.footer-meta a:hover {
  color: #c4b5fd;
  text-decoration: underline;
}
</style>
