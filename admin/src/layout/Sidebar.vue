<template>
  <div class="sidebar-logo">
    <span class="logo-text">🏢 大圣物业</span>
  </div>
  <div class="sidebar-menu-wrap">
    <el-scrollbar>
      <el-menu :default-active="route.path" :collapse="appStore.sidebarCollapsed" router class="sidebar-menu">
        <template v-for="menu in menuList" :key="menu.id">
          <el-sub-menu v-if="menu.children?.length" :index="menu.route">
            <template #title>
              <el-icon><component :is="menuIcon(menu)" /></el-icon>
              <span>{{ menu.name }}</span>
            </template>
            <el-menu-item v-for="child in menu.children" :key="child.id" :index="child.route">
              <el-icon><component :is="menuIcon(child)" /></el-icon>
              <span>{{ child.name }}</span>
            </el-menu-item>
          </el-sub-menu>
          <el-menu-item v-else :index="menu.route">
            <el-icon><component :is="menuIcon(menu)" /></el-icon>
            <span>{{ menu.name }}</span>
          </el-menu-item>
        </template>
      </el-menu>
    </el-scrollbar>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { useUserStore } from '@/stores/user'
import { useAppStore } from '@/stores/app'
import type { MenuItem } from '@/types/api'

const route = useRoute()
const userStore = useUserStore()
const appStore = useAppStore()

const menuList = computed(() => userStore.menus)

const iconMap: Record<string, string> = {
  dashboard: 'DataAnalysis', system: 'Setting', admin: 'User', role: 'Security', menu: 'Menu',
  config: 'Tools', log: 'DocumentCopy', property: 'HomeFilled', community: 'HomeFilled',
  building: 'OfficeBuilding', room: 'Door', owner: 'UserFilled', charge: 'Money',
  bill: 'List', payment: 'CreditCard', meter: 'DataBoard', finance: 'TrendCharts',
  item: 'PriceTag', repair: 'Tools', worker: 'User', order: 'Document',
  security: 'Shield', visitor: 'User', patrol: 'Van', access_card: 'CreditCard',
  parking: 'TakeawayBox', space: 'TakeawayBox', vehicle: 'Bus', record: 'List',
  notice: 'Bell', equipment: 'SetUp', maintain: 'Tools', complaint: 'Warning',
  print: 'Printer', receipt: 'List', arrears: 'Bell', index: 'DataAnalysis', profile: 'User',
  staff: 'UserFilled', attendance: 'Calendar', schedule: 'Clock', salary: 'Money',
  supplier: 'Shop', purchase: 'ShoppingCart', contract: 'Document', evaluation: 'StarFilled',
  vote: 'TrendCharts', activity: 'Sunny', owner_notice: 'Bell', owner_complaint: 'Warning',
}

function menuIcon(menu: MenuItem): string {
  const p = (menu.permission || menu.route || '').split(':').pop()?.split('/').pop() || ''
  return iconMap[p] || 'Menu'
}
</script>

<style scoped>
.sidebar-logo { height: 48px; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #2b6cb0, #3182ce); flex-shrink: 0; }
.logo-text { color: #fff; font-size: 15px; font-weight: 700; letter-spacing: 1px; }
.sidebar-menu-wrap { flex: 1; min-height: 0; }
.sidebar-menu { border-right: none; }
.el-menu { border-right: none; }
/* 紧凑菜单项 */
.el-menu :deep(.el-menu-item),
.el-menu :deep(.el-sub-menu .el-sub-menu__title) {
  height: 44px;
  line-height: 44px;
  font-size: 13px;
}
.el-menu :deep(.el-sub-menu .el-menu .el-menu-item) {
  height: 40px;
  line-height: 40px;
}
</style>
