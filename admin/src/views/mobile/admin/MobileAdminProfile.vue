<template>
  <div class="map-page">
    <!-- 用户卡片 -->
    <div class="map-card">
      <img :src="monkeyLogo" class="mapc-avatar" />
      <div class="mapc-name">{{ userStore.userInfo?.nickname || userStore.userInfo?.username || '管理员' }}</div>
      <div class="mapc-role">{{ userStore.userInfo?.role || '系统管理员' }}</div>
    </div>

    <!-- 信息列表 -->
    <div class="map-section">
      <div class="maps-item" v-for="item in infoList" :key="item.label">
        <span class="mapi-label">{{ item.label }}</span>
        <span class="mapi-value">{{ item.value || '-' }}</span>
      </div>
    </div>

    <!-- 操作项 -->
    <div class="map-section">
      <div class="maps-row" @click="router.push('/bigscreen')">
        <span class="mapsr-icon">📊</span>
        <span class="mapsr-label">数据大屏</span>
        <span class="mapsr-arrow">›</span>
      </div>
      <div class="maps-row" @click="router.push('/profile')">
        <span class="mapsr-icon">⚙️</span>
        <span class="mapsr-label">个人中心</span>
        <span class="mapsr-arrow">›</span>
      </div>
      <div class="maps-row" @click="goPC">
        <span class="mapsr-icon">💻</span>
        <span class="mapsr-label">切换到PC版</span>
        <span class="mapsr-arrow">›</span>
      </div>
      <div class="maps-row" @click="about">
        <span class="mapsr-icon">ℹ️</span>
        <span class="mapsr-label">关于</span>
        <span class="mapsr-arrow">›</span>
      </div>
    </div>

    <button class="map-logout" @click="handleLogout">退出登录</button>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage, ElMessageBox } from 'element-plus'
import { useUserStore } from '@/stores/user'
import monkeyLogo from '@/assets/images/monkey-ico.png'

const router = useRouter()
const userStore = useUserStore()

const infoList = computed(() => [
  { label: '用户名', value: userStore.userInfo?.username },
  { label: '昵称', value: userStore.userInfo?.nickname },
  { label: '角色', value: userStore.userInfo?.role },
  { label: '管辖小区', value: userStore.userInfo?.community_name || '全部' },
])

async function handleLogout() {
  try {
    await ElMessageBox.confirm('确定要退出吗？', '提示', { confirmButtonText: '确定', cancelButtonText: '取消', type: 'warning' })
  } catch { return }
  userStore.logout()
  ElMessage.success('已退出')
  router.replace('/mobile/admin/login')
}

function goPC() { router.push('/dashboard') }
function about() { ElMessage.info('大圣智慧物业 · 管理后台 v1.0 · 杭州喵喵至家网络有限公司') }
</script>

<style scoped>
.map-page { padding: 12px; }
.map-card { background: linear-gradient(135deg, #1a365d, #2b6cb0); border-radius: 16px; padding: 28px 20px; text-align: center; margin-bottom: 14px; }
.mapc-avatar { width: 64px; height: 64px; border-radius: 14px; object-fit: contain; background: rgba(255,255,255,0.2); padding: 6px; }
.mapc-name { color: #fff; font-size: 18px; font-weight: 700; margin-top: 10px; }
.mapc-role { color: rgba(255,255,255,0.7); font-size: 13px; margin-top: 2px; }

.map-section { background: #fff; border-radius: 14px; margin-bottom: 14px; overflow: hidden; }
.maps-item { display: flex; justify-content: space-between; align-items: center; padding: 14px 16px; border-bottom: 1px solid #f7f8fc; }
.maps-item:last-child { border-bottom: none; }
.mapi-label { font-size: 13px; color: #718096; }
.mapi-value { font-size: 13px; color: #1a202c; font-weight: 500; }

.maps-row { display: flex; align-items: center; gap: 12px; padding: 14px 16px; cursor: pointer; border-bottom: 1px solid #f7f8fc; }
.maps-row:last-child { border-bottom: none; }
.maps-row:active { background: #f7f8fc; }
.mapsr-icon { font-size: 18px; width: 24px; text-align: center; }
.mapsr-label { flex: 1; font-size: 14px; color: #2d3748; }
.mapsr-arrow { font-size: 20px; color: #cbd5e0; }

.map-logout { width: 100%; height: 48px; background: #fff; color: #e53e3e; border: 1px solid #e2e8f0; border-radius: 14px; font-size: 15px; font-weight: 600; cursor: pointer; margin-top: 6px; }
.map-logout:active { background: #fff5f5; }
</style>
