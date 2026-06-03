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
    <el-badge :is-dot="true" class="notice-badge">
      <el-button :icon="'Bell'" text style="font-size:18px;" />
    </el-badge>
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
