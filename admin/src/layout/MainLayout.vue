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
        <router-view />
      </el-main>
    </el-container>
  </el-container>
</template>

<script setup lang="ts">
import { onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useUserStore } from '@/stores/user'
import { useAppStore } from '@/stores/app'
import Sidebar from './Sidebar.vue'
import HeaderBar from './HeaderBar.vue'
import TabsView from './TabsView.vue'

const userStore = useUserStore()
const appStore = useAppStore()
const router = useRouter()

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
.main-content { margin-top: 60px; padding: 20px; background: #f7f8fc; min-height: calc(100vh - 60px); }
</style>
