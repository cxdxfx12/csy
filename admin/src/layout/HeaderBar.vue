<template>
  <div class="header-left">
    <el-button @click="appStore.toggleSidebar" :icon="appStore.sidebarCollapsed ? 'Expand' : 'Fold'" text />
    <el-breadcrumb separator="/">
      <el-breadcrumb-item :to="{ path: '/dashboard' }">工作台</el-breadcrumb-item>
      <el-breadcrumb-item v-if="route.meta.title">{{ route.meta.title as string }}</el-breadcrumb-item>
    </el-breadcrumb>
  </div>
  <div class="header-right">
    <el-badge :is-dot="true" class="notice-badge">
      <el-button :icon="'Bell'" text style="font-size:18px;" />
    </el-badge>
    <el-dropdown trigger="click">
      <span class="user-info">
        <el-avatar :size="32" :src="userStore.userInfo?.avatar || undefined" icon="UserFilled" />
        <span class="user-name">{{ userStore.userInfo?.nickname || '管理员' }}</span>
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
import { useRouter, useRoute } from 'vue-router'
import { useUserStore } from '@/stores/user'
import { useAppStore } from '@/stores/app'

const router = useRouter()
const route = useRoute()
const userStore = useUserStore()
const appStore = useAppStore()

function handleLogout() {
  userStore.logout()
  router.push('/login')
}
</script>

<style scoped>
.header-left { display: flex; align-items: center; gap: 12px; }
.header-right { margin-left: auto; display: flex; align-items: center; gap: 16px; }
.user-info { display: flex; align-items: center; gap: 8px; cursor: pointer; }
.user-name { font-size: 14px; color: #333; font-weight: 500; }
.notice-badge { margin-right: 8px; }
</style>
