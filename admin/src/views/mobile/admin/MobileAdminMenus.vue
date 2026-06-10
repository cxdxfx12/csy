<template>
  <div class="menus-page">
    <!-- 搜索栏 -->
    <div class="search-bar" :class="{ focused: searchFocused }">
      <Icon icon="ph:magnifying-glass-duotone" class="sb-icon" />
      <input
        v-model="searchText"
        placeholder="搜功能、找模块..."
        @focus="searchFocused = true"
        @blur="searchFocused = false"
      />
      <Icon
        v-if="searchText"
        icon="ph:x-circle-fill"
        class="sb-clear"
        @click="searchText = ''"
      />
    </div>

    <!-- 热门入口 -->
    <div v-if="!searchText" class="hot-section">
      <div class="hot-scroll">
        <div class="hot-chip" v-for="h in hotEntries" :key="h.name" @click="jump(h.route)">
          <Icon :icon="h.icon" class="hc-icon" />
          {{ h.name }}
        </div>
      </div>
    </div>

    <!-- 菜单分类 -->
    <div class="cat-section" v-for="cat in filteredMenus" :key="cat.name">
      <div class="cat-header">
        <div class="ch-icon" :style="{ background: cat.color + '14', color: cat.color }">
          <Icon :icon="cat.icon" />
        </div>
        <span class="ch-title">{{ cat.name }}</span>
        <span class="ch-count">{{ cat.children.length }}项</span>
      </div>
      <div class="menu-grid">
        <div
          class="menu-card"
          v-for="m in cat.children"
          :key="m.name"
          @click="jump(m.route)"
        >
          <div class="mc-icon" :style="{ background: m.color + '14', color: m.color }">
            <Icon :icon="m.icon" />
          </div>
          <span class="mc-name">{{ m.name }}</span>
        </div>
      </div>
    </div>

    <!-- 空状态 -->
    <div v-if="!filteredMenus.length" class="empty-state">
      <Icon icon="ph:folder-open-duotone" class="es-icon" />
      <span class="es-text">没有匹配的功能</span>
      <span v-if="searchText" class="es-hint">换个关键词试试</span>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { Icon } from '@iconify/vue'
import { useUserStore } from '@/stores/user'
import { getMenuIcon, getMenuColor } from '@/utils/mobileIcons'

const router = useRouter()
const userStore = useUserStore()
const searchText = ref('')
const searchFocused = ref(false)

// 热门入口
const hotEntries = [
  { name: '工单管理', icon: 'ph:clipboard-text-duotone', route: '/repair/order' },
  { name: '缴费记录', icon: 'ph:credit-card-duotone', route: '/charge/payment' },
  { name: '业主信息', icon: 'ph:users-duotone', route: '/owner/index' },
  { name: '访客登记', icon: 'ph:identification-badge-duotone', route: '/security/visitor' },
  { name: '楼栋管理', icon: 'ph:building-office-duotone', route: '/property/building' },
  { name: '账单管理', icon: 'ph:file-text-duotone', route: '/charge/bill' },
  { name: '公告列表', icon: 'ph:newspaper-duotone', route: '/notice/index' },
  { name: '车辆管理', icon: 'ph:car-duotone', route: '/parking/vehicle' },
]

// 从用户菜单构建分类
const menuCategories = computed(() => {
  const userMenus = userStore.menus
  if (!userMenus?.length) return getDefaultMenus()
  return buildCatTree(userMenus)
})

const filteredMenus = computed(() => {
  if (!searchText.value.trim()) return menuCategories.value
  const kw = searchText.value.toLowerCase()
  return menuCategories.value
    .map(cat => ({
      ...cat,
      children: cat.children.filter((m: any) => m.name.toLowerCase().includes(kw)),
    }))
    .filter(cat => cat.children.length)
})

function buildCatTree(menus: any[]): any[] {
  const catMap = new Map<string, any>()
  for (const m of menus) {
    if (m.children?.length) {
      catMap.set(m.name, {
        name: m.name,
        color: getMenuColor(m.name),
        icon: getMenuIcon(m.name),
        children: m.children.map((c: any) => ({
          name: c.name,
          route: c.route,
          icon: getMenuIcon(c.name),
          color: getMenuColor(c.name),
        })),
      })
    } else if (m.route && m.route !== '#') {
      // 未分类的放入上级或"其他"
      const parentName = m.parentName || '其他'
      if (!catMap.has(parentName)) {
        catMap.set(parentName, {
          name: parentName || '其他',
          color: '#64748b',
          icon: 'ph:link-duotone',
          children: [],
        })
      }
      catMap.get(parentName)!.children.push({
        name: m.name,
        route: m.route,
        icon: getMenuIcon(m.name),
        color: getMenuColor(m.name),
      })
    }
  }
  return Array.from(catMap.values())
}

function getDefaultMenus() {
  return [
    {
      name: '系统管理', color: '#6366f1', icon: 'ph:gear-duotone',
      children: [
        { name: '用户管理', route: '/system/admin', icon: 'ph:user-duotone', color: '#6366f1' },
        { name: '角色管理', route: '/system/role', icon: 'ph:user-gear-duotone', color: '#6366f1' },
        { name: '菜单管理', route: '/system/menu', icon: 'ph:list-bullets-duotone', color: '#6366f1' },
        { name: '系统配置', route: '/system/config', icon: 'ph:sliders-duotone', color: '#6366f1' },
        { name: '操作日志', route: '/system/log', icon: 'ph:scroll-duotone', color: '#6366f1' },
      ],
    },
    {
      name: '房产管理', color: '#0891b2', icon: 'ph:buildings-duotone',
      children: [
        { name: '小区管理', route: '/property/community', icon: 'ph:buildings-duotone', color: '#0891b2' },
        { name: '楼栋管理', route: '/property/building', icon: 'ph:building-office-duotone', color: '#0891b2' },
        { name: '房间管理', route: '/property/room', icon: 'ph:house-duotone', color: '#0891b2' },
      ],
    },
    {
      name: '业主管理', color: '#059669', icon: 'ph:users-duotone',
      children: [
        { name: '业主信息', route: '/owner/index', icon: 'ph:user-duotone', color: '#059669' },
        { name: '家庭成员', route: '/owner/family', icon: 'ph:users-three-duotone', color: '#059669' },
        { name: '业主投票', route: '/owner/vote', icon: 'ph:vote-duotone', color: '#059669' },
        { name: '社区活动', route: '/owner/activity', icon: 'ph:confetti-duotone', color: '#059669' },
        { name: '公告通知', route: '/owner/notice', icon: 'ph:megaphone-duotone', color: '#d946ef' },
        { name: '投诉建议', route: '/owner/complaint', icon: 'ph:chat-centered-text-duotone', color: '#d946ef' },
      ],
    },
    {
      name: '收费管理', color: '#ea580c', icon: 'ph:currency-circle-dollar-duotone',
      children: [
        { name: '收费项目', route: '/charge/item', icon: 'ph:file-text-duotone', color: '#ea580c' },
        { name: '账单管理', route: '/charge/bill', icon: 'ph:file-text-duotone', color: '#ea580c' },
        { name: '缴费记录', route: '/charge/payment', icon: 'ph:credit-card-duotone', color: '#ea580c' },
        { name: '财务流水', route: '/charge/finance', icon: 'ph:chart-line-up-duotone', color: '#ea580c' },
        { name: '欠费管理', route: '/charge/arrears', icon: 'ph:warning-diamond-duotone', color: '#ea580c' },
      ],
    },
    {
      name: '报修管理', color: '#dc2626', icon: 'ph:wrench-duotone',
      children: [
        { name: '工单管理', route: '/repair/order', icon: 'ph:clipboard-text-duotone', color: '#dc2626' },
        { name: '维修人员', route: '/repair/worker', icon: 'ph:hard-hat-duotone', color: '#dc2626' },
      ],
    },
  ]
}

// 高频模块路由映射：直接跳转到专用手机页面
const mobileRouteMap: Record<string, string> = {
  '/property/community': '/mobile/admin/property',
  '/property/building': '/mobile/admin/property',
  '/property/room': '/mobile/admin/property',
  '/owner/index': '/mobile/admin/owner',
  '/owner/family': '/mobile/admin/owner',
  '/charge/bill': '/mobile/admin/charge',
  '/charge/payment': '/mobile/admin/charge',
  '/charge/item': '/mobile/admin/charge',
  '/repair/order': '/mobile/admin/repair',
  '/repair/worker': '/mobile/admin/repair',
  '/notice/index': '/mobile/admin/notice',
  '/system/admin': '/mobile/admin/system',
  '/system/role': '/mobile/admin/system',
  '/system/menu': '/mobile/admin/system',
  '/system/config': '/mobile/admin/system',
  '/system/log': '/mobile/admin/system',
  '/system/PushDevice': '/mobile/admin/system',
  '/system/SseEvent': '/mobile/admin/system',
  '/system/pushConfig': '/mobile/admin/system',
  '/system/ServiceVendor': '/mobile/admin/system',
}

function jump(route: string) {
  if (!route || route === '#') return
  // 高频模块：跳转到专用手机端页面
  if (mobileRouteMap[route]) {
    router.push(mobileRouteMap[route])
    return
  }
  // 其他模块：用 _mobile_view 壳渲染 PC 页面
  sessionStorage.setItem('_mobile_view', '1')
  router.push(route)
}
</script>

<style scoped>
.menus-page { padding: 14px; }

/* 搜索栏 */
.search-bar {
  display: flex;
  align-items: center;
  gap: 10px;
  background: #fff;
  border-radius: 16px;
  padding: 0 16px;
  height: 48px;
  border: 1.5px solid transparent;
  transition: all .25s;
  box-shadow: 0 1px 3px rgba(0,0,0,.04);
}
.search-bar.focused { border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,.08); }
.sb-icon { font-size: 18px; color: #94a3b8; flex-shrink: 0; transition: color .25s; }
.search-bar.focused .sb-icon { color: #6366f1; }
.search-bar input {
  flex: 1;
  background: transparent;
  border: none;
  outline: none;
  font-size: 14px;
  color: #0f172a;
  height: 48px;
}
.search-bar input::placeholder { color: #94a3b8; }
.sb-clear { font-size: 18px; color: #cbd5e1; cursor: pointer; flex-shrink: 0; }
.sb-clear:active { color: #94a3b8; }

/* 热门入口 */
.hot-section { margin-top: 14px; overflow: hidden; }
.hot-scroll {
  display: flex;
  gap: 8px;
  overflow-x: auto;
  padding: 2px 0 6px;
  -webkit-overflow-scrolling: touch;
  scrollbar-width: none;
}
.hot-scroll::-webkit-scrollbar { display: none; }
.hot-chip {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 10px 16px;
  background: #fff;
  border-radius: 24px;
  font-size: 13px;
  font-weight: 500;
  color: #334155;
  white-space: nowrap;
  cursor: pointer;
  flex-shrink: 0;
  border: 1px solid rgba(0,0,0,.05);
  transition: all .2s;
}
.hot-chip:active { background: #f1f5f9; transform: scale(.96); }
.hc-icon { font-size: 18px; color: #6366f1; }

/* 分类区域 */
.cat-section { margin-top: 20px; }
.cat-header {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 12px;
}
.ch-icon {
  width: 32px; height: 32px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 18px;
}
.ch-title { font-size: 16px; font-weight: 700; color: #0f172a; }
.ch-count { font-size: 11px; color: #94a3b8; background: #f1f5f9; padding: 2px 8px; border-radius: 10px; }

/* 菜单网格 */
.menu-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 10px;
}
.menu-card {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 10px;
  padding: 18px 8px 14px;
  background: #fff;
  border-radius: 16px;
  cursor: pointer;
  border: 1px solid rgba(0,0,0,.03);
  transition: all .2s;
}
.menu-card:active { transform: scale(.95); background: #f8fafc; }
.mc-icon {
  width: 48px; height: 48px;
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
  transition: transform .2s;
}
.menu-card:active .mc-icon { transform: scale(1.08); }
.mc-name { font-size: 12px; color: #334155; font-weight: 500; text-align: center; line-height: 1.3; }

/* 空状态 */
.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  padding: 60px 20px;
  color: #cbd5e1;
}
.es-icon { font-size: 48px; }
.es-text { font-size: 14px; color: #94a3b8; }
.es-hint { font-size: 12px; color: #cbd5e1; }
</style>
