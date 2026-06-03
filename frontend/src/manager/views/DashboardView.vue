<template>
  <div class="dashboard">
    <!-- Top Bar -->
    <header>
      <div class="header-left">
        <h1>🏘️ {{ communityName || '小区经理工作台' }}</h1>
      </div>
      <div class="header-right">
        <span class="user-info">{{ managerName }}</span>
        <button class="btn-logout" @click="logout">退出</button>
      </div>
    </header>

    <!-- Tab Nav -->
    <nav class="tab-nav">
      <button v-for="t in tabs" :key="t.key" :class="{ active: activeTab === t.key }" @click="activeTab = t.key; loadTabData()">
        {{ t.icon }} {{ t.label }}
      </button>
    </nav>

    <div v-if="loading" class="loading">数据加载中...</div>
    <div v-else-if="error" class="error">{{ error }}</div>

    <!-- Dashboard -->
    <template v-else-if="activeTab === 'dashboard'">
      <div class="stats-row">
        <div class="stat-card" v-for="s in statCards" :key="s.label">
          <div class="stat-val">{{ s.value }}</div>
          <div class="stat-label">{{ s.label }}</div>
        </div>
      </div>

      <div class="grid2">
        <div class="panel" v-if="income.months?.length">
          <h3>📈 收入趋势</h3>
          <div class="bar-chart">
            <div class="bar" v-for="(m,i) in income.months" :key="i" :style="{height:(income.values[i]/maxIncome*100)+'%'}">
              <span class="bar-val">¥{{ income.values[i] }}</span>
              <span class="bar-label">{{ m }}</span>
            </div>
          </div>
        </div>

        <div class="panel" v-if="repair">
          <h3>🔧 报修统计</h3>
          <div class="stat-grid">
            <div class="mini-stat"><strong>{{ repair.pending }}</strong><small>待处理</small></div>
            <div class="mini-stat"><strong>{{ repair.processing }}</strong><small>处理中</small></div>
            <div class="mini-stat"><strong>{{ repair.finished }}</strong><small>已完成</small></div>
            <div class="mini-stat"><strong>{{ repair.total }}</strong><small>总工单</small></div>
          </div>
        </div>

        <div class="panel" v-if="owner">
          <h3>👥 业主统计</h3>
          <div class="big-num">{{ owner.total }}<small>业主总数</small></div>
          <div class="big-num secondary">{{ owner.newThisMonth }}<small>本月新增</small></div>
          <div class="big-num tertiary">{{ owner.wxBound }}<small>微信绑定</small></div>
        </div>

        <div class="panel" v-if="todos.length">
          <h3>⏳ 待处理事项</h3>
          <div class="todo-list">
            <div v-for="t in todos" :key="t.title" class="todo-item">
              <span class="todo-icon">{{ t.icon }}</span>
              <span class="todo-title">{{ t.title }}</span>
              <span class="todo-count">{{ t.count }}条</span>
            </div>
          </div>
        </div>

        <div class="panel" v-if="chargeRate">
          <h3>💰 收费率</h3>
          <div class="charge-wrap">
            <div class="charge-big">{{ chargeRate.rate }}%</div>
            <div class="charge-bar-bg"><div class="charge-bar" :style="{width:chargeRate.rate+'%'}"></div></div>
            <div class="charge-detail">已收 ¥{{ chargeRate.paid || 0 }} / 应收 ¥{{ chargeRate.total || 0 }}</div>
          </div>
        </div>
      </div>
    </template>

    <!-- Lists -->
    <template v-else>
      <div class="list-toolbar" v-if="activeTab==='owner'">
        <input v-model="listKeyword" placeholder="搜索姓名/手机号..." @keyup.enter="loadTabData" />
        <button class="btn-search" @click="loadTabData">搜索</button>
      </div>
      <div class="list-toolbar" v-else>
        <select v-model="listStatus" @change="loadTabData">
          <option value="0">全部</option>
          <option v-if="activeTab==='bill'" value="1">未缴</option>
          <option v-if="activeTab==='bill'" value="2">部分缴纳</option>
          <option v-if="activeTab==='bill'" value="3">已缴清</option>
          <option v-if="activeTab==='repair'" value="1">待派修</option>
          <option v-if="activeTab==='repair'" value="2">待接单</option>
          <option v-if="activeTab==='repair'" value="3">处理中</option>
          <option v-if="activeTab==='repair'" value="4">已完成</option>
          <option v-if="activeTab==='complaint'" value="1">待处理</option>
          <option v-if="activeTab==='complaint'" value="2">处理中</option>
          <option v-if="activeTab==='complaint'" value="3">已解决</option>
        </select>
      </div>

      <div class="list-table-wrap">
        <table v-if="listData.length" class="list-table">
          <thead>
            <tr>
              <template v-if="activeTab==='owner'">
                <th>姓名</th><th>手机</th><th>类型</th><th>房间</th><th>注册时间</th>
              </template>
              <template v-else-if="activeTab==='bill'">
                <th>业主</th><th>房间</th><th>项目</th><th>金额</th><th>已付</th><th>状态</th><th>日期</th>
              </template>
              <template v-else-if="activeTab==='repair'">
                <th>业主</th><th>房间</th><th>问题描述</th><th>类型</th><th>状态</th><th>报修时间</th>
              </template>
              <template v-else-if="activeTab==='complaint'">
                <th>业主</th><th>内容</th><th>类型</th><th>状态</th><th>投诉时间</th>
              </template>
            </tr>
          </thead>
          <tbody>
            <template v-if="activeTab==='owner'">
              <tr v-for="row in listData" :key="row.id">
                <td>{{ row.realname }}</td><td>{{ row.phone }}</td>
                <td><span class="tag">{{ row.type==1?'业主':row.type==2?'家属':'租户' }}</span></td>
                <td>{{ row.rooms || '-' }}</td><td>{{ row.create_time }}</td>
              </tr>
            </template>
            <template v-else-if="activeTab==='bill'">
              <tr v-for="row in listData" :key="row.id">
                <td>{{ row.owner_name }}</td><td>{{ row.room_number }}</td>
                <td>{{ row.charge_item_name }}</td><td>¥{{ row.total_amount }}</td><td>¥{{ row.paid_amount }}</td>
                <td><span :class="'tag tag-'+row.status">{{ row.status==1?'未缴':row.status==2?'部分缴纳':'已缴清' }}</span></td>
                <td>{{ row.create_time?.substring(0,10) }}</td>
              </tr>
            </template>
            <template v-else-if="activeTab==='repair'">
              <tr v-for="row in listData" :key="row.id">
                <td>{{ row.owner_name }}</td><td>{{ row.room_number }}</td>
                <td class="text-ellipsis">{{ row.description }}</td>
                <td>{{ row.repair_type }}</td>
                <td><span :class="'tag tag-'+row.status">{{ statusMap.repair[row.status]||row.status }}</span></td>
                <td>{{ row.create_time?.substring(0,10) }}</td>
              </tr>
            </template>
            <template v-else-if="activeTab==='complaint'">
              <tr v-for="row in listData" :key="row.id">
                <td>{{ row.owner_name }}</td>
                <td class="text-ellipsis">{{ row.content }}</td>
                <td>{{ row.complaint_type }}</td>
                <td><span :class="'tag tag-'+row.status">{{ statusMap.complaint[row.status]||row.status }}</span></td>
                <td>{{ row.create_time?.substring(0,10) }}</td>
              </tr>
            </template>
          </tbody>
        </table>
        <div v-else class="empty">暂无数据</div>
      </div>
      <div class="pagination" v-if="listTotal > limit">
        <button :disabled="page <= 1" @click="page--; loadTabData()">上一页</button>
        <span>第 {{ page }} / {{ Math.ceil(listTotal/limit) }} 页 (共{{ listTotal }}条)</span>
        <button :disabled="page >= Math.ceil(listTotal/limit)" @click="page++; loadTabData()">下一页</button>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { createApi, createAuth } from '@/shared/api.js'

const router = useRouter()
const api = createApi('/api/manager', 'manager_token')
const auth = createAuth('manager_token')

const loading = ref(true)
const error = ref('')
const activeTab = ref('dashboard')
const communityName = ref('')
const managerName = ref('')

const tabs = [
  { key: 'dashboard', label: '驾驶舱', icon: '📊' },
  { key: 'owner', label: '业主', icon: '👥' },
  { key: 'bill', label: '账单', icon: '💰' },
  { key: 'repair', label: '报修', icon: '🔧' },
  { key: 'complaint', label: '投诉', icon: '📢' },
]

const statusMap = {
  repair: { 1:'待派修', 2:'待接单', 3:'处理中', 4:'已完成', 5:'已关闭' },
  complaint: { 1:'待处理', 2:'处理中', 3:'已解决', 4:'已关闭' }
}

// Dashboard data
const stats = ref(null)
const income = ref({ months: [], values: [] })
const repair = ref(null)
const owner = ref(null)
const todos = ref([])
const chargeRate = ref(null)

const maxIncome = computed(() => Math.max(...income.value.values, 1))
const statCards = computed(() => {
  if (!stats.value) return []
  const s = stats.value
  return [
    { label: '房产总数', value: s.total_rooms || 0 },
    { label: '业主总数', value: s.total_owners || 0 },
    { label: '本月收入', value: '¥' + (s.month_income || 0) },
    { label: '待收金额', value: '¥' + (s.unpaid_amount || 0) },
    { label: '收费率', value: (s.charge_rate || 0) + '%' },
    { label: '待处理工单', value: s.pending_repairs || 0 },
  ]
})

// List data
const listData = ref([])
const listTotal = ref(0)
const listKeyword = ref('')
const listStatus = ref(0)
const page = ref(1)
const limit = 15

const apiMap = {
  owner: '/manager/owner/list',
  bill: '/manager/bill/list',
  repair: '/manager/repair/list',
  complaint: '/manager/complaint/list',
}

onMounted(loadAll)

async function loadAll() {
  loading.value = true
  try {
    const [infoRes, statRes, incomeRes, repairRes, ownerRes, todoRes, chargeRes] = await Promise.all([
      api('/dashboard/communityInfo'),
      api('/dashboard/statistics'),
      api('/dashboard/incomeTrend'),
      api('/dashboard/repairStats'),
      api('/dashboard/ownerStats'),
      api('/dashboard/pendingTodo'),
      api('/dashboard/chargeRate'),
    ])
    if (infoRes.code === 0) {
      communityName.value = infoRes.data.name || ''
      managerName.value = infoRes.data.contact || ''
    }
    if (statRes.code === 0) stats.value = statRes.data
    if (incomeRes.code === 0) income.value = { months: incomeRes.data.months || [], values: incomeRes.data.income || [] }
    if (repairRes.code === 0) repair.value = repairRes.data
    if (ownerRes.code === 0) owner.value = ownerRes.data
    if (todoRes.code === 0) todos.value = Array.isArray(todoRes.data) ? todoRes.data : (todoRes.data?.list || [])
    if (chargeRes.code === 0) chargeRate.value = chargeRes.data
  } catch (e) {
    error.value = '数据加载失败：' + e.message
  }
  loading.value = false
}

async function loadTabData() {
  if (activeTab.value === 'dashboard') return loadAll()
  loading.value = true
  try {
    const params = { page: page.value, limit }
    if (activeTab.value === 'owner' && listKeyword.value) params.keyword = listKeyword.value
    if (activeTab.value !== 'owner' && listStatus.value) params.status = listStatus.value
    const query = Object.entries(params).map(([k, v]) => `${k}=${encodeURIComponent(v)}`).join('&')
    const res = await api(`${apiMap[activeTab.value]}?${query}`)
    if (res.code === 0) {
      listData.value = res.data?.list || res.data || []
      listTotal.value = res.count || 0
    } else {
      listData.value = []
      listTotal.value = 0
    }
  } catch { listData.value = []; listTotal.value = 0 }
  loading.value = false
}

function logout() {
  auth.removeToken()
  router.replace('/login')
}
</script>

<style scoped>
.dashboard{padding:20px;max-width:1200px;margin:0 auto}
header{display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;flex-wrap:wrap;gap:10px}
header h1{font-size:22px;color:#f1f5f9}
.header-right{display:flex;align-items:center;gap:12px}
.user-info{color:#94a3b8;font-size:13px}
.btn-logout{background:rgba(255,255,255,.1);border:1px solid #334155;color:#94a3b8;padding:8px 18px;border-radius:8px;cursor:pointer;font-size:13px}
.btn-logout:hover{background:rgba(255,255,255,.15)}

.tab-nav{display:flex;gap:6px;margin-bottom:20px;overflow-x:auto}
.tab-nav button{background:#1e293b;border:1px solid #334155;color:#94a3b8;padding:10px 18px;border-radius:10px;cursor:pointer;font-size:14px;white-space:nowrap;transition:.2s}
.tab-nav button:hover{background:#334155;color:#e2e8f0}
.tab-nav button.active{background:#10b981;border-color:#10b981;color:#fff}

.loading,.error{text-align:center;padding:80px 0;color:#64748b;font-size:16px}
.error{color:#f87171}

.stats-row{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:14px;margin-bottom:24px}
.stat-card{background:#1e293b;border:1px solid #334155;border-radius:14px;padding:20px;text-align:center;border-top:3px solid #10b981}
.stat-val{font-size:28px;font-weight:700;color:#f1f5f9;margin-bottom:4px}
.stat-label{font-size:13px;color:#64748b}

.grid2{display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:16px}
.panel{background:#1e293b;border:1px solid #334155;border-radius:14px;padding:20px}
.panel h3{font-size:16px;color:#e2e8f0;margin-bottom:16px}

.bar-chart{display:flex;align-items:flex-end;justify-content:center;height:160px;gap:8px;padding-top:10px}
.bar{flex:1;max-width:50px;background:linear-gradient(180deg,#10b981,#34d399);border-radius:6px 6px 0 0;position:relative;min-height:4px;transition:height .5s}
.bar-val{position:absolute;top:-20px;left:50%;transform:translateX(-50%);font-size:11px;color:#94a3b8;white-space:nowrap}
.bar-label{position:absolute;bottom:-22px;left:50%;transform:translateX(-50%);font-size:10px;color:#64748b}

.stat-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.mini-stat{background:#0f172a;border-radius:10px;padding:14px;text-align:center}
.mini-stat strong{display:block;font-size:22px;color:#f1f5f9;margin-bottom:4px}
.mini-stat small{font-size:12px;color:#64748b}

.big-num{font-size:36px;font-weight:700;color:#f1f5f9;margin-bottom:16px}
.big-num small{display:block;font-size:13px;font-weight:400;color:#64748b;margin-top:2px}
.big-num.secondary{font-size:28px;color:#34d399}
.big-num.tertiary{font-size:28px;color:#818cf8}

.todo-list{display:flex;flex-direction:column;gap:8px}
.todo-item{display:flex;align-items:center;gap:10px;padding:10px;background:#0f172a;border-radius:8px}
.todo-icon{font-size:18px}
.todo-title{flex:1;font-size:14px;color:#e2e8f0}
.todo-count{background:#1e293b;color:#34d399;padding:3px 10px;border-radius:12px;font-size:12px;font-weight:600}

.charge-wrap{text-align:center}
.charge-big{font-size:42px;font-weight:700;color:#10b981;margin-bottom:10px}
.charge-bar-bg{height:10px;background:#0f172a;border-radius:5px;overflow:hidden;margin-bottom:8px}
.charge-bar{height:100%;background:linear-gradient(90deg,#10b981,#34d399);border-radius:5px;transition:width .5s}
.charge-detail{font-size:13px;color:#64748b}

/* List */
.list-toolbar{display:flex;gap:10px;margin-bottom:16px}
.list-toolbar input{flex:1;max-width:280px;height:40px;background:#1e293b;border:1px solid #334155;border-radius:8px;padding:0 12px;color:#e2e8f0;font-size:14px;outline:none}
.list-toolbar input:focus{border-color:#10b981}
.list-toolbar select{height:40px;background:#1e293b;border:1px solid #334155;border-radius:8px;padding:0 12px;color:#e2e8f0;font-size:14px;outline:none}
.btn-search{height:40px;padding:0 20px;background:#10b981;border:none;border-radius:8px;color:#fff;font-size:14px;cursor:pointer}

.list-table-wrap{overflow-x:auto}
.list-table{width:100%;border-collapse:collapse}
.list-table th{text-align:left;padding:12px 10px;background:#1e293b;color:#94a3b8;font-size:13px;font-weight:500;border-bottom:1px solid #334155;white-space:nowrap}
.list-table td{padding:10px;border-bottom:1px solid #1e293b;color:#e2e8f0;font-size:14px}
.list-table tbody tr:hover{background:#1e293b}
.text-ellipsis{max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.tag{display:inline-block;padding:2px 8px;border-radius:4px;font-size:12px;background:#334155;color:#94a3b8}
.tag-1{background:#f59e0b22;color:#f59e0b}
.tag-2{background:#3b82f622;color:#3b82f6}
.tag-3{background:#10b98122;color:#10b981}
.tag-4{background:#10b98122;color:#10b981}
.tag-5{background:#ef444422;color:#ef4444}

.empty{text-align:center;padding:60px 0;color:#64748b;font-size:15px}

.pagination{display:flex;align-items:center;justify-content:center;gap:12px;margin-top:16px}
.pagination button{height:36px;padding:0 16px;background:#1e293b;border:1px solid #334155;border-radius:8px;color:#e2e8f0;cursor:pointer;font-size:13px}
.pagination button:disabled{opacity:.4;cursor:default}
.pagination span{color:#64748b;font-size:13px}
</style>
