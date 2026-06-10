<template>
  <div class="mam-page">
    <!-- 搜索 -->
    <div class="mam-search">
      <span>🔍</span>
      <input v-model="searchText" placeholder="搜索功能..." />
    </div>

    <!-- 菜单分类 -->
    <div class="mam-section" v-for="cat in filteredMenus" :key="cat.name">
      <h3 class="mam-sec-title">{{ cat.icon }} {{ cat.name }}</h3>
      <div class="mam-grid">
        <div class="mam-card" v-for="m in cat.children" :key="m.route" @click="jump(m.route)">
          <div class="mamc-icon" :style="{ background: m.color }">{{ m.icon }}</div>
          <div class="mamc-name">{{ m.name }}</div>
        </div>
      </div>
    </div>

    <!-- 无结果 -->
    <div class="mam-empty" v-if="!filteredMenus.length">没有匹配的功能</div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useUserStore } from '@/stores/user'

const router = useRouter()
const userStore = useUserStore()
const searchText = ref('')

// 菜单颜色池
const colors = ['#3182ce','#38a169','#dd6b20','#e53e3e','#6b46c1','#319795','#d53f8c','#2b6cb0','#c05621','#2c7a7b','#b83280','#2a4365']

// 动态菜单转分组
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
  const cats: any[] = []
  let colorIdx = 0
  for (const m of menus) {
    if (m.children?.length) {
      cats.push({
        name: m.name,
        icon: getMenuIcon(m.name),
        children: m.children.map((c: any, i: number) => ({
          name: c.name,
          route: c.route,
          icon: getMenuIcon(c.name),
          color: colors[colorIdx++ % colors.length] + '18',
        })),
      })
    } else if (m.route && m.route !== '#') {
      // 没有子菜单的直接归类
      const other = cats.find(cc => cc.name === '其他')
      if (other) {
        other.children.push({
          name: m.name,
          route: m.route,
          icon: getMenuIcon(m.name),
          color: colors[colorIdx++ % colors.length] + '18',
        })
      } else {
        cats.push({
          name: '其他',
          icon: '📌',
          children: [{
            name: m.name,
            route: m.route,
            icon: getMenuIcon(m.name),
            color: colors[colorIdx++ % colors.length] + '18',
          }],
        })
      }
    }
  }
  return cats
}

function getMenuIcon(name: string): string {
  const n = name || ''
  if (n.includes('系统') || n.includes('用户') || n.includes('角色') || n.includes('菜单')) return '⚙️'
  if (n.includes('房产') || n.includes('小区') || n.includes('楼栋') || n.includes('房间')) return '🏘️'
  if (n.includes('业主') || n.includes('家庭') || n.includes('投票') || n.includes('活动')) return '👤'
  if (n.includes('收费') || n.includes('账单') || n.includes('缴费') || n.includes('财务') || n.includes('欠费') || n.includes('押金') || n.includes('发票') || n.includes('支付')) return '💰'
  if (n.includes('报修') || n.includes('工单') || n.includes('维修')) return '🔧'
  if (n.includes('安防') || n.includes('访客') || n.includes('巡更') || n.includes('门禁')) return '🔑'
  if (n.includes('停车') || n.includes('车辆') || n.includes('车位') || n.includes('道闸')) return '🚗'
  if (n.includes('公告') || n.includes('通知') || n.includes('消息') || n.includes('推送')) return '📝'
  if (n.includes('设备') || n.includes('维保') || n.includes('电梯')) return '⚡'
  if (n.includes('人事') || n.includes('员工') || n.includes('考勤') || n.includes('工资')) return '👔'
  if (n.includes('供应商') || n.includes('采购') || n.includes('合同') || n.includes('评价')) return '🤝'
  if (n.includes('打印') || n.includes('收据') || n.includes('催缴')) return '🖨️'
  if (n.includes('微信') || n.includes('短信') || n.includes('监控')) return '📡'
  if (n.includes('装饰') || n.includes('装修')) return '🛠️'
  if (n.includes('租赁') || n.includes('租客')) return '🏠'
  if (n.includes('投诉')) return '📋'
  if (n.includes('AI') || n.includes('智能')) return '🤖'
  if (n.includes('大屏') || n.includes('数据')) return '📊'
  if (n.includes('SSE') || n.includes('设备')) return '🔌'
  return '📌'
}

function getDefaultMenus() {
  return [
    {
      name: '系统管理', icon: '⚙️',
      children: [
        { name: '用户管理', route: '/system/admin', icon: '👤', color: '#3182ce18' },
        { name: '角色管理', route: '/system/role', icon: '👥', color: '#38a16918' },
        { name: '菜单管理', route: '/system/menu', icon: '📋', color: '#dd6b2018' },
        { name: '系统配置', route: '/system/config', icon: '🔧', color: '#e53e3e18' },
        { name: '操作日志', route: '/system/log', icon: '📜', color: '#6b46c118' },
      ],
    },
    {
      name: '房产管理', icon: '🏘️',
      children: [
        { name: '小区管理', route: '/property/community', icon: '🏘️', color: '#3182ce18' },
        { name: '楼栋管理', route: '/property/building', icon: '🏗️', color: '#38a16918' },
        { name: '房间管理', route: '/property/room', icon: '🏠', color: '#dd6b2018' },
      ],
    },
    {
      name: '业主管理', icon: '👤',
      children: [
        { name: '业主信息', route: '/owner/index', icon: '👤', color: '#3182ce18' },
        { name: '家庭成员', route: '/owner/family', icon: '👨‍👩‍👧', color: '#38a16918' },
        { name: '业主投票', route: '/owner/vote', icon: '🗳️', color: '#dd6b2018' },
        { name: '社区活动', route: '/owner/activity', icon: '🎉', color: '#e53e3e18' },
        { name: '公告通知', route: '/owner/notice', icon: '📢', color: '#6b46c118' },
        { name: '投诉建议', route: '/owner/complaint', icon: '📋', color: '#31979518' },
      ],
    },
    {
      name: '收费管理', icon: '💰',
      children: [
        { name: '收费项目', route: '/charge/item', icon: '📋', color: '#3182ce18' },
        { name: '账单管理', route: '/charge/bill', icon: '📄', color: '#38a16918' },
        { name: '缴费记录', route: '/charge/payment', icon: '💳', color: '#dd6b2018' },
        { name: '财务流水', route: '/charge/finance', icon: '💰', color: '#e53e3e18' },
        { name: '欠费管理', route: '/charge/arrears', icon: '⚠️', color: '#6b46c118' },
      ],
    },
  ]
}

function jump(route: string) {
  if (!route || route === '#') return
  // 标记来自手机端，PC布局会自动隐藏侧边栏适配手机
  sessionStorage.setItem('_mobile_view', '1')
  router.push(route)
}
</script>

<style scoped>
.mam-page { padding: 12px; }
.mam-search { display: flex; align-items: center; gap: 10px; background: #fff; border-radius: 12px; padding: 0 14px; height: 44px; margin-bottom: 14px; border: 1px solid #e2e8f0; }
.mam-search span { font-size: 16px; }
.mam-search input { flex: 1; background: transparent; border: none; outline: none; font-size: 14px; color: #1a202c; }

.mam-section { margin-bottom: 16px; }
.mam-sec-title { font-size: 14px; font-weight: 700; color: #1a202c; margin-bottom: 10px; }
.mam-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; }
.mam-card { display: flex; flex-direction: column; align-items: center; gap: 8px; background: #fff; border-radius: 14px; padding: 16px 8px; cursor: pointer; }
.mam-card:active { background: #edf2f7; }
.mamc-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 24px; }
.mamc-name { font-size: 12px; color: #4a5568; text-align: center; }
.mam-empty { padding: 40px; text-align: center; color: #a0aec0; font-size: 14px; }
</style>
