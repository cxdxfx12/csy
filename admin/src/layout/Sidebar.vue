<template>
  <div class="sidebar-logo">
    <span class="logo-text">🏢 大圣物业</span>
  </div>
  <div class="sidebar-menu-wrap">
    <el-scrollbar>
      <el-menu :default-active="route.path" :collapse="appStore.sidebarCollapsed" class="sidebar-menu" @select="handleSelect">
        <template v-for="menu in menuList" :key="menu.id">
          <el-sub-menu v-if="menu.children?.length" :index="String(menu.id)">
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
import { useRoute, useRouter } from 'vue-router'
import { useUserStore } from '@/stores/user'
import { useAppStore } from '@/stores/app'
import type { MenuItem } from '@/types/api'

const route = useRoute()
const router = useRouter()
const userStore = useUserStore()
const appStore = useAppStore()

const menuList = computed(() => userStore.menus)

const iconMap: Record<string, string> = {
  dashboard: 'DataAnalysis', system: 'Setting', admin: 'User', role: 'Key', menu: 'Menu',
  config: 'Tools', log: 'DocumentCopy',   property: 'HomeFilled', community: 'HomeFilled',
  building: 'OfficeBuilding', room: 'House', owner: 'UserFilled', charge: 'Money',
  bill: 'List', payment: 'CreditCard', meter: 'DataBoard', finance: 'TrendCharts',
  item: 'PriceTag', repair: 'Tools', worker: 'User', order: 'Document',
  security: 'Lock', visitor: 'User', patrol: 'Van', access_card: 'CreditCard',
  parking: 'TakeawayBox', space: 'TakeawayBox', vehicle: 'Van', record: 'List',
  notice: 'Bell', equipment: 'SetUp', maintain: 'Tools', complaint: 'Warning',
  print: 'Printer', receipt: 'List', arrears: 'Bell', index: 'DataAnalysis', profile: 'User',
  staff: 'UserFilled', attendance: 'Calendar', schedule: 'Clock', salary: 'Money',
  supplier: 'Shop', purchase: 'ShoppingCart', contract: 'Document', evaluation: 'StarFilled',
  vote: 'TrendCharts', activity: 'Sunny', owner_notice: 'Bell', owner_complaint: 'Warning',
  sms: 'Message', wechat: 'ChatDotSquare', wechat_user: 'User', user: 'User',
  // 新增模块图标
  device: 'Cpu', device_event: 'Warning', elevator: 'Connection', elevator_fault: 'WarningFilled',
  elevator_inspection: 'CircleCheck', lease: 'OfficeBuilding', lease_property: 'House', lease_tenant: 'UserFilled',
  lease_contract: 'Document', lease_payment: 'Money', lease_termination: 'Close', deposit: 'Wallet',
  invoice: 'Tickets', invoice_info: 'Postcard', parking_fee_rule: 'Discount', parking_payment: 'CreditCard',
  print_template: 'Notebook', print_log: 'DocumentCopy', notification: 'Bell', push_device: 'Iphone',
  sse_event: 'DataLine', service_vendor: 'Phone', sms_template: 'Tickets', sms_send: 'ChatLineSquare',

  // 补全模块图标
  dunning: 'WarningFilled', message: 'ChatLineSquare', sms_log: 'DocumentCopy',
  signup: 'List',
}

function menuIcon(menu: MenuItem): string {
  const p = (menu.permission || menu.route || '').split(':').pop()?.split('/').pop() || ''
  return iconMap[p] || 'Menu'
}

function handleSelect(index: string) {
  // index 是子菜单项的 route（以 / 开头）或父级 sub-menu 的 id（纯数字）
  // 只有 index 以 / 开头、与当前路径不同、且路由表中确实存在对应路由时才导航
  if (!index || !index.startsWith('/') || index === route.path) return
  const resolved = router.resolve(index)
  if (resolved.matched.length > 0) {
    router.push(index)
  }
}
</script>

<style scoped>
.sidebar-logo { height: 52px; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #1e3a5f, #2563eb, #4f46e5); flex-shrink: 0; position: relative; overflow: hidden; }
.sidebar-logo::after { content: ''; position: absolute; bottom: 0; left: 10%; width: 80%; height: 1px; background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent); }
.logo-text { color: #fff; font-size: 15px; font-weight: 700; letter-spacing: 1.5px; }
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
