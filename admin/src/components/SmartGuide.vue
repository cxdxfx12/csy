<template>
  <div class="smart-guide">
    <!-- 浮动按钮：猴子 -->
    <div class="sg-fab" :class="{ active: isOpen }" @click="togglePanel">
      <div class="sg-avatar-wrap">
        <img src="@/assets/images/monkey-guide.png" class="sg-avatar" alt="大圣导航" />
        <div class="sg-tail"></div>
      </div>
      <span class="sg-fab-label" v-if="!isOpen">导航</span>
    </div>

    <!-- 面板 -->
    <transition name="sg-panel">
      <div class="sg-panel" v-if="isOpen">
        <div class="sg-panel-header">
          <div class="sg-panel-title">
            <img src="@/assets/images/monkey-guide.png" class="sg-title-icon" alt="" />
            大圣导航助手
          </div>
          <button class="sg-panel-close" @click="togglePanel">
            <el-icon><Close /></el-icon>
          </button>
        </div>

        <!-- 搜索框 -->
        <div class="sg-search-box">
          <el-input
            v-model="searchQuery"
            placeholder="搜索业主、房号、工单、账单、车牌、公告..."
            :prefix-icon="Search"
            size="default"
            clearable
            @input="onSearchInput"
            @keyup.enter="doSearch"
          />
          <div class="sg-search-tags">
            <span class="sg-tag" @click="quickSearch('业主')">业主</span>
            <span class="sg-tag" @click="quickSearch('房号')">房号</span>
            <span class="sg-tag" @click="quickSearch('工单')">工单</span>
            <span class="sg-tag" @click="quickSearch('账单')">账单</span>
            <span class="sg-tag" @click="quickSearch('车牌')">车牌</span>
            <span class="sg-tag" @click="quickSearch('公告')">公告</span>
          </div>
        </div>

        <!-- 面板内容 -->
        <div class="sg-panel-body">
          <!-- 加载中 -->
          <div v-if="loading" class="sg-loading">
            <el-icon class="sg-loading-icon"><Loading /></el-icon>
            <span>正在搜索...</span>
          </div>

          <!-- 有搜索内容时 -->
          <template v-else-if="searchQuery.trim()">
            <!-- 数据结果 -->
            <template v-if="dataResults.length">
              <div class="sg-section-title">
                <el-icon><DataAnalysis /></el-icon>
                数据结果 ({{ dataResults.length }})
              </div>
              <div class="sg-data-results">
                <div
                  class="sg-data-item"
                  v-for="item in dataResults"
                  :key="item.type + '-' + item.id"
                  @click="navigateToData(item)"
                >
                  <div class="sg-data-badge" :class="'badge-' + item.type">
                    {{ item.typeName }}
                  </div>
                  <div class="sg-data-info">
                    <div class="sg-data-title">
                      <el-icon class="sg-data-icon"><component :is="item.icon || 'Document'" /></el-icon>
                      {{ item.title }}
                    </div>
                    <div class="sg-data-subtitle" v-html="item.subtitle"></div>
                  </div>
                  <el-icon class="sg-data-arrow"><ArrowRight /></el-icon>
                </div>
              </div>
            </template>

            <!-- 菜单结果 -->
            <template v-if="menuResults.length">
              <div class="sg-section-title" :style="dataResults.length ? 'margin-top:12px' : ''">
                <el-icon><Menu /></el-icon>
                菜单 ({{ menuResults.length }})
              </div>
              <div class="sg-menu-results">
                <div
                  class="sg-menu-item"
                  v-for="item in menuResults"
                  :key="item.route"
                  @click="navigateToMenu(item)"
                >
                  <el-icon class="sg-menu-icon"><component :is="item.iconComp" /></el-icon>
                  <div class="sg-menu-info">
                    <span class="sg-menu-name">{{ item.name }}</span>
                    <span class="sg-menu-path">{{ item.parentName }}</span>
                  </div>
                  <el-icon class="sg-menu-arrow"><ArrowRight /></el-icon>
                </div>
              </div>
            </template>

            <!-- 空状态 -->
            <div v-if="!dataResults.length && !menuResults.length" class="sg-empty">
              <el-icon :size="40"><Search /></el-icon>
              <p>未找到匹配结果</p>
              <p class="sg-empty-hint">
                试试搜索：业主姓名、房号、工单号、账单号、车牌、手机号...
              </p>
            </div>
          </template>

          <!-- 无搜索内容时：快捷入口 -->
          <template v-else>
            <div class="sg-section-title">
              <el-icon><Compass /></el-icon>
              快捷导航
            </div>
            <div class="sg-quick-grid">
              <div
                class="sg-quick-item"
                v-for="q in quickLinks"
                :key="q.route"
                @click="navigateToMenu(q)"
              >
                <el-icon :size="20"><component :is="q.iconComp" /></el-icon>
                <span>{{ q.name }}</span>
              </div>
            </div>

            <div class="sg-section-title" style="margin-top:16px">
              <el-icon><ChatLineRound /></el-icon>
              常见问题
            </div>
            <div class="sg-faq-list">
              <div
                class="sg-faq-item"
                v-for="faq in faqList"
                :key="faq.q"
                @click="askQuestion(faq)"
              >
                <span class="sg-faq-q">{{ faq.q }}</span>
                <el-icon><ArrowRight /></el-icon>
              </div>
            </div>
          </template>
        </div>
      </div>
    </transition>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useUserStore } from '@/stores/user'
import { apiGet } from '@/utils/request'
import {
  Search, Close, ArrowRight, Compass, ChatLineRound,
  DataAnalysis, Menu, Loading
} from '@element-plus/icons-vue'
import type { MenuItem } from '@/types/api'

const router = useRouter()
const userStore = useUserStore()

const isOpen = ref(false)
const searchQuery = ref('')
const loading = ref(false)
const dataResults = ref<any[]>([])

// 防抖定时器
let debounceTimer: any = null

// ========== 图标映射 ==========
const iconMap: Record<string, any> = {
  dashboard: 'DataAnalysis', system: 'Setting', admin: 'User', role: 'Key', menu: 'Menu',
  config: 'Tools', log: 'Document', property: 'HomeFilled', community: 'OfficeBuilding',
  building: 'School', room: 'House', owner: 'UserFilled', family: 'Avatar', vote: 'Tickets',
  activity: 'Trophy', notice: 'Bell', complaint: 'Warning', signup: 'Calendar',
  charge: 'Money', item: 'Goods', bill: 'Document', payment: 'Wallet', meter: 'Odometer',
  finance: 'TrendCharts', arrears: 'Warning', deposit: 'Box', invoice: 'Tickets',
  repair: 'Tools', worker: 'User', security: 'Lock', visitor: 'UserFilled',
  patrol: 'View', 'patrol-route': 'MapLocation', 'access-card': 'Postcard',
  accessConfig: 'Setting', accessDevice: 'Cpu', parking: 'Van', space: 'Grid',
  vehicle: 'Van', record: 'List', equipment: 'SetUp', maintain: 'SetUp',
  print: 'Printer', receipt: 'Document', staff: 'UserFilled', attendance: 'Calendar',
  schedule: 'Timer', salary: 'Coin', supplier: 'Shop', purchase: 'ShoppingCart',
  contract: 'Document', evaluation: 'Star', payment_config: 'CreditCard',
  wechat: 'ChatDotSquare', sms: 'Message', template: 'Document',
  lease: 'OfficeBuilding', decoration: 'Brush', iot: 'Monitor', ai: 'MagicStick',
  assistant: 'MagicStick', device: 'Cpu', elevator: 'Connection',
  surveillanceConfig: 'Monitor', gateConfig: 'Setting', PushDevice: 'Iphone',
  SseEvent: 'Notification', pushConfig: 'Setting', ServiceVendor: 'Phone',
  Deposit: 'Box', Invoice: 'Tickets', InvoiceInfo: 'Tickets',
  UnifiedPayment: 'CreditCard', dunning: 'Bell', ParkingFeeRule: 'Coin',
  ParkingPayment: 'Wallet', GateConfig: 'Setting', GateDevice: 'Cpu',
  Notification: 'Bell', message: 'ChatDotRound', Device: 'Cpu',
  DeviceEvent: 'Notification', Elevator: 'Connection', ElevatorFault: 'Warning',
  ElevatorInspection: 'View', PrintTemplate: 'Document', PrintLog: 'List',
  SmsTemplate: 'Document', SmsSend: 'Promotion', SmsLog: 'List',
  LeaseProperty: 'House', LeaseTenant: 'User', LeaseContract: 'Document',
  LeasePayment: 'Wallet', LeaseTermination: 'CircleClose',
  DecorationApply: 'EditPen', DecorationInspect: 'View',
  DecorationViolation: 'WarningFilled', DecorationWorker: 'User',
}

// ========== 菜单搜索 ==========
interface FlatMenu {
  name: string
  route: string
  parentName: string
  iconComp: string
}

const flatMenus = computed<FlatMenu[]>(() => {
  const result: FlatMenu[] = []
  function walk(items: MenuItem[], parentName: string) {
    for (const item of items) {
      const route = item.route || ''
      if (route.startsWith('/') && item.type === 2) {
        const key = route.split('/').filter(Boolean).pop() || ''
        const parentKey = route.split('/').filter(Boolean)[0] || ''
        const iconComp = iconMap[key] || iconMap[parentKey] || 'Menu'
        result.push({ name: item.name, route, parentName: parentName || '根菜单', iconComp })
      }
      if (item.children?.length) walk(item.children, item.name)
    }
  }
  walk(userStore.menus, '')
  return result
})

const menuResults = computed(() => {
  const q = searchQuery.value.trim().toLowerCase()
  if (!q) return []
  return flatMenus.value.filter(m =>
    m.name.toLowerCase().includes(q) ||
    m.parentName.toLowerCase().includes(q) ||
    m.route.toLowerCase().includes(q)
  ).slice(0, 8)
})

// ========== 快捷导航 ==========
const quickLinks = computed(() => {
  const links: FlatMenu[] = []
  for (const menu of userStore.menus) {
    if (menu.children?.length) {
      const first = menu.children[0]
      if (first.route?.startsWith('/')) {
        const key = first.route.split('/').filter(Boolean).pop() || ''
        const parentKey = first.route.split('/').filter(Boolean)[0] || ''
        links.push({
          name: menu.name, route: first.route,
          parentName: menu.name, iconComp: iconMap[parentKey] || iconMap[key] || 'Menu'
        })
      }
    }
    if (links.length >= 8) break
  }
  return links
})

// ========== 常见问题 ==========
const faqList = [
  { q: '怎么创建账单？', a: '进入 <b>收费管理 → 账单管理</b>，点击"新增账单"按钮，选择小区、楼栋、房间后填写收费项目即可生成账单。' },
  { q: '如何添加业主？', a: '进入 <b>业主管理 → 业主管理</b>，点击"新增业主"，填写姓名、手机号、关联房间等信息后保存即可。业主也可以通过微信小程序自助注册。' },
  { q: '报修工单怎么处理？', a: '进入 <b>报修管理 → 工单管理</b>，可查看所有工单。点击工单可查看详情，指派维修人员、更新处理进度，完成后关闭工单。' },
  { q: '门禁卡怎么发放？', a: '进入 <b>安防管理 → 门禁卡</b>，点击"新增门禁卡"，选择业主、绑定房间和门禁设备后保存。业主即可刷卡通行。' },
  { q: '怎样配置支付方式？', a: '进入 <b>支付配置</b>，配置微信支付商户号和密钥。配置完成后业主即可通过微信小程序在线缴费。' },
  { q: '停车费率怎么设置？', a: '进入 <b>停车管理 → 停车费率</b>，新增费率规则，设置按小时/按天/封顶金额等，保存后自动生效。' },
  { q: '如何查看财务报表？', a: '进入 <b>收费管理 → 财务流水</b>，可按时间段、小区、收费项目筛选查看收支明细，支持导出Excel。' },
  { q: '怎么发公告通知？', a: '进入 <b>公告管理 → 公告列表</b>，点击"新增公告"，填写标题和内容，选择发布范围后发布。业主将在微信小程序收到推送。' },
]

// ========== 搜索逻辑 ==========
function onSearchInput() {
  if (debounceTimer) clearTimeout(debounceTimer)
  const q = searchQuery.value.trim()
  if (!q) {
    dataResults.value = []
    loading.value = false
    return
  }
  loading.value = true
  debounceTimer = setTimeout(() => doSearch(), 300)
}

async function doSearch() {
  const q = searchQuery.value.trim()
  if (!q) {
    dataResults.value = []
    loading.value = false
    return
  }
  try {
    const res = await apiGet<{ results: any[]; total: number }>('/admin/search/global', { keyword: q })
    dataResults.value = res.data?.results || []
  } catch (e) {
    dataResults.value = []
  } finally {
    loading.value = false
  }
}

function quickSearch(text: string) {
  searchQuery.value = text
  onSearchInput()
}

// ========== 导航 ==========
function navigateToData(item: any) {
  if (item.route) {
    router.push(item.route)
    closePanel()
  }
}

function navigateToMenu(item: FlatMenu) {
  const resolved = router.resolve(item.route)
  if (resolved.name) {
    router.push(item.route)
    closePanel()
  }
}

function askQuestion(faq: { q: string; a: string }) {
  searchQuery.value = faq.q
  // 不触发搜索，只是展示知识库回答
  // 实际可以通过输入框展示FAQ答案
}

function togglePanel() {
  isOpen.value = !isOpen.value
  if (!isOpen.value) closePanel()
}

function closePanel() {
  searchQuery.value = ''
  dataResults.value = []
  loading.value = false
}
</script>

<style scoped>
/* ===== 浮动按钮 ===== */
.sg-fab {
  position: fixed;
  right: 24px;
  bottom: 80px;
  z-index: 10000;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
  cursor: pointer;
  transition: transform 0.3s;
}
.sg-fab:hover { transform: scale(1.08); }
.sg-fab.active { transform: scale(0.92); }

.sg-avatar-wrap {
  position: relative;
  width: 56px;
  height: 56px;
  border-radius: 50%;
  background: linear-gradient(135deg, #C68642, #8B5E3C);
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 4px 16px rgba(139,94,60,0.4);
  overflow: visible;
}
.sg-avatar {
  width: 48px;
  height: 48px;
  object-fit: contain;
  border-radius: 50%;
}

/* 摇尾巴动画 */
.sg-tail {
  position: absolute;
  right: -6px;
  bottom: 2px;
  width: 18px;
  height: 18px;
  border: 3px solid transparent;
  border-right-color: #8B5E3C;
  border-bottom-color: #8B5E3C;
  border-radius: 0 0 60% 0;
  transform-origin: left top;
  animation: sgWagTail 0.7s ease-in-out infinite;
}
@keyframes sgWagTail {
  0%, 100% { transform: rotate(-10deg); }
  50% { transform: rotate(30deg); }
}

.sg-fab-label {
  font-size: 10px;
  color: var(--text-3);
  white-space: nowrap;
  background: var(--bg-card);
  padding: 2px 8px;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.15);
  font-weight: 600;
}

/* ===== 面板 ===== */
.sg-panel {
  position: fixed;
  right: 20px;
  bottom: 150px;
  width: 420px;
  max-height: 600px;
  background: var(--bg-card);
  border-radius: 16px;
  box-shadow: 0 8px 32px rgba(0,0,0,0.25), 0 0 0 1px var(--border-2);
  z-index: 10001;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.sg-panel-enter-active { animation: sgPanelIn 0.25s ease; }
.sg-panel-leave-active { animation: sgPanelOut 0.2s ease; }
@keyframes sgPanelIn {
  from { opacity: 0; transform: translateY(16px) scale(0.96); }
  to { opacity: 1; transform: translateY(0) scale(1); }
}
@keyframes sgPanelOut {
  from { opacity: 1; transform: translateY(0) scale(1); }
  to { opacity: 0; transform: translateY(16px) scale(0.96); }
}

.sg-panel-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 16px;
  background: linear-gradient(135deg, #8B5E3C, #C68642);
  color: #fff;
}
.sg-panel-title {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 15px;
  font-weight: 600;
}
.sg-title-icon {
  width: 26px;
  height: 26px;
  border-radius: 50%;
  object-fit: cover;
  background: #fff;
}
.sg-panel-close {
  background: rgba(255,255,255,0.2);
  border: none;
  color: #fff;
  width: 28px;
  height: 28px;
  border-radius: 50%;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background 0.2s;
}
.sg-panel-close:hover { background: rgba(255,255,255,0.35); }

/* ===== 搜索框 ===== */
.sg-search-box {
  padding: 12px 14px;
  border-bottom: 1px solid var(--border-2);
}
.sg-search-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  margin-top: 8px;
}
.sg-tag {
  font-size: 11px;
  padding: 3px 10px;
  border-radius: 12px;
  background: var(--bg-toolbar);
  color: var(--text-3);
  cursor: pointer;
  transition: all 0.2s;
  border: 1px solid var(--border-2);
}
.sg-tag:hover {
  background: var(--accent);
  color: #fff;
  border-color: var(--accent);
}

/* ===== 面板内容 ===== */
.sg-panel-body {
  flex: 1;
  overflow-y: auto;
  padding: 10px 14px 14px;
}

.sg-section-title {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  color: var(--text-3);
  margin-bottom: 8px;
  font-weight: 600;
}

/* ===== 加载中 ===== */
.sg-loading {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 40px 0;
  color: var(--text-4);
  font-size: 13px;
}
.sg-loading-icon {
  animation: sgSpin 1s linear infinite;
}
@keyframes sgSpin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

/* ===== 数据结果 ===== */
.sg-data-results {
  display: flex;
  flex-direction: column;
  gap: 6px;
}
.sg-data-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 12px;
  border-radius: 10px;
  cursor: pointer;
  transition: background 0.2s;
  border: 1px solid transparent;
}
.sg-data-item:hover {
  background: var(--bg-table-hover);
  border-color: var(--border-2);
}

.sg-data-badge {
  font-size: 10px;
  padding: 2px 8px;
  border-radius: 10px;
  font-weight: 600;
  white-space: nowrap;
  flex-shrink: 0;
}
.badge-owner { background: rgba(59,130,246,0.15); color: #3b82f6; }
.badge-room { background: rgba(16,185,129,0.15); color: #10b981; }
.badge-building { background: rgba(139,92,246,0.15); color: #8b5cf6; }
.badge-community { background: rgba(245,158,11,0.15); color: #f59e0b; }
.badge-repair_order { background: rgba(239,68,68,0.15); color: #ef4444; }
.badge-bill { background: rgba(14,165,233,0.15); color: #0ea5e9; }
.badge-payment { background: rgba(99,102,241,0.15); color: #6366f1; }
.badge-vehicle { background: rgba(236,72,153,0.15); color: #ec4899; }
.badge-staff { background: rgba(20,184,166,0.15); color: #14b8a6; }
.badge-notice { background: rgba(249,115,22,0.15); color: #f97316; }
.badge-supplier { background: rgba(168,85,247,0.15); color: #a855f7; }
.badge-access_card { background: rgba(6,182,212,0.15); color: #06b6d4; }

.sg-data-info { flex: 1; min-width: 0; }
.sg-data-title {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 14px;
  color: var(--text-1);
  font-weight: 500;
}
.sg-data-icon { color: var(--accent); font-size: 16px; }
.sg-data-subtitle {
  font-size: 11px;
  color: var(--text-4);
  margin-top: 3px;
  line-height: 1.4;
}
.sg-data-arrow { color: var(--text-5); flex-shrink: 0; }

/* ===== 菜单结果 ===== */
.sg-menu-results {
  display: flex;
  flex-direction: column;
  gap: 4px;
}
.sg-menu-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 8px 12px;
  border-radius: 8px;
  cursor: pointer;
  transition: background 0.2s;
}
.sg-menu-item:hover { background: var(--bg-table-hover); }
.sg-menu-icon { color: var(--accent); font-size: 16px; }
.sg-menu-info { flex: 1; min-width: 0; }
.sg-menu-name { display: block; font-size: 13px; color: var(--text-2); }
.sg-menu-path { display: block; font-size: 11px; color: var(--text-5); margin-top: 1px; }
.sg-menu-arrow { color: var(--text-5); font-size: 12px; }

/* ===== 空状态 ===== */
.sg-empty {
  text-align: center;
  padding: 36px 0;
  color: var(--text-4);
}
.sg-empty-hint {
  font-size: 12px;
  color: var(--text-5);
  margin-top: 8px;
  line-height: 1.6;
}

/* ===== 快捷导航 ===== */
.sg-quick-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 8px;
}
.sg-quick-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
  padding: 12px 4px;
  border-radius: 10px;
  cursor: pointer;
  transition: background 0.2s;
  font-size: 11px;
  color: var(--text-2);
}
.sg-quick-item:hover {
  background: var(--bg-table-hover);
  color: var(--accent);
}
.sg-quick-item .el-icon { color: var(--accent); }

/* ===== 常见问题 ===== */
.sg-faq-list {
  display: flex;
  flex-direction: column;
  gap: 4px;
}
.sg-faq-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 9px 12px;
  border-radius: 8px;
  cursor: pointer;
  transition: background 0.2s;
}
.sg-faq-item:hover { background: var(--bg-table-hover); }
.sg-faq-q { font-size: 13px; color: var(--text-2); }
.sg-faq-item:hover .sg-faq-q { color: var(--accent); }
.sg-faq-item .el-icon { color: var(--text-5); font-size: 12px; }

/* 滚动条 */
.sg-panel-body::-webkit-scrollbar { width: 4px; }
.sg-panel-body::-webkit-scrollbar-thumb { background: var(--border-2); border-radius: 2px; }
</style>
