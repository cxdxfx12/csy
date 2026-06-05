<template>
  <div class="premium-page">
    <!-- 统计卡片 -->
    <div class="stat-grid">
      <div class="stat-card c1">
        <div class="stat-icon"><el-icon><UserFilled /></el-icon></div>
        <div class="stat-body">
          <div class="stat-num">{{ stats.total }}</div>
          <div class="stat-label">总报名人次</div>
        </div>
        <div class="stat-bar"></div>
      </div>
      <div class="stat-card c2">
        <div class="stat-icon"><el-icon><Calendar /></el-icon></div>
        <div class="stat-body">
          <div class="stat-num">{{ stats.active }}</div>
          <div class="stat-label">进行中活动</div>
        </div>
        <div class="stat-bar"></div>
      </div>
      <div class="stat-card c3">
        <div class="stat-icon"><el-icon><TrendCharts /></el-icon></div>
        <div class="stat-body">
          <div class="stat-num">{{ stats.today }}</div>
          <div class="stat-label">今日新增报名</div>
        </div>
        <div class="stat-bar"></div>
      </div>
      <div class="stat-card c4">
        <div class="stat-icon"><el-icon><DataLine /></el-icon></div>
        <div class="stat-body">
          <div class="stat-num">{{ stats.rate }}%</div>
          <div class="stat-label">满额率均值</div>
        </div>
        <div class="stat-bar"></div>
      </div>
    </div>

    <!-- 搜索与筛选 -->
    <div class="filter-panel">
      <div class="filter-left">
        <el-input v-model="query.keyword" placeholder="搜索姓名/手机号/活动名称" clearable prefix-icon="Search" class="kw-input" @keyup.enter="loadData" />
        <el-select v-model="query.activity_id" placeholder="全部活动" clearable filterable class="act-select">
          <el-option v-for="a in activities" :key="a.id" :label="a.title" :value="a.id">
            <div class="act-option">
              <span>{{ a.title }}</span>
              <el-tag :type="actStatusTag(a.status)" size="small" effect="plain">{{ actStatusText(a.status) }}</el-tag>
            </div>
          </el-option>
        </el-select>
      </div>
      <div class="filter-right">
        <el-button type="primary" @click="loadData" :icon="Search">查询</el-button>
        <el-button @click="resetQuery" :icon="RefreshRight">重置</el-button>
      </div>
    </div>

    <!-- 活动报名总览卡片区 -->
    <div class="activity-cards" v-if="activities.length">
      <div class="section-title">
        <span class="title-dot"></span>热门活动报名概况
      </div>
      <div class="act-card-grid">
        <div v-for="act in topActivities" :key="act.id" class="act-card" :class="'status-'+act.status">
          <div class="act-card-header">
            <h4 class="act-card-title">{{ act.title }}</h4>
            <el-tag :type="actStatusTag(act.status)" size="small" effect="dark" round>{{ actStatusText(act.status) }}</el-tag>
          </div>
          <div class="act-card-meta">
            <span><el-icon><Location /></el-icon> {{ act.community_name || '全社区' }}</span>
          </div>
          <div class="act-card-progress">
            <div class="progress-info">
              <span>{{ act.current_participants || 0 }} / {{ act.max_participants || '不限' }} 人</span>
              <span>{{ act.max_participants ? Math.round((act.current_participants||0)/act.max_participants*100) + '%' : '—' }}</span>
            </div>
            <div class="progress-track">
              <div class="progress-fill" :style="{ width: act.max_participants ? Math.min(100, (act.current_participants||0)/act.max_participants*100) + '%' : '0%' }"></div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- 报名明细表格 -->
    <el-card shadow="never" class="table-panel">
      <template #header>
        <div class="panel-header">
          <span class="panel-title"><span class="title-dot"></span>报名明细</span>
          <span class="panel-count">共 {{ total }} 条记录</span>
        </div>
      </template>

      <el-table :data="list" v-loading="loading" style="width:100%" :header-cell-style="{ background: '#f8fafc', color: '#475569', fontWeight: 600, fontSize: '13px', borderBottom: '2px solid #e2e8f0' }">
        <el-table-column type="index" label="#" width="55" align="center" />
        <el-table-column label="活动信息" min-width="200">
          <template #default="{ row }">
            <div class="user-cell">
              <div class="cell-avatar" :style="{ background: avatarColor(row.activity_title) }">{{ (row.activity_title || '活')[0] }}</div>
              <div class="cell-info">
                <div class="cell-name">{{ row.activity_title }}</div>
                <div class="cell-sub">{{ row.create_time }}</div>
              </div>
            </div>
          </template>
        </el-table-column>
        <el-table-column prop="name" label="报名人" width="110" />
        <el-table-column prop="phone" label="手机号码" width="130" />
        <el-table-column label="备注" min-width="160">
          <template #default="{ row }">
            <span class="remark-text">{{ row.remark || '—' }}</span>
          </template>
        </el-table-column>
        <el-table-column prop="create_time" label="报名时间" width="170" sortable />
        <el-table-column label="操作" width="90" fixed="right" align="center">
          <template #default="{ row }">
            <el-popconfirm title="确认取消该报名记录？" confirm-button-text="确认取消" cancel-button-text="再看看" @confirm="handleCancel(row)">
              <template #reference>
                <el-button size="small" type="danger" plain round>取消</el-button>
              </template>
            </el-popconfirm>
          </template>
        </el-table-column>
      </el-table>

      <div class="pagi-wrap">
        <el-pagination v-model:current-page="query.page" v-model:page-size="query.limit" :total="total"
          :page-sizes="[10, 20, 30, 50]" layout="total, sizes, prev, pager, next, jumper" background
          @current-change="loadData" @size-change="loadData" />
      </div>
    </el-card>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, computed } from 'vue'
import { apiGet, apiPost } from '@/utils/request'
import { ElMessage } from 'element-plus'
import { UserFilled, Calendar, TrendCharts, DataLine, Search, RefreshRight, Location } from '@element-plus/icons-vue'

const list = ref<any[]>([])
const total = ref(0)
const loading = ref(false)
const activities = ref<any[]>([])
const query = reactive({ page: 1, limit: 15, keyword: '', activity_id: '' })

const stats = ref({ total: 0, active: 0, today: 0, rate: 0 })

// 计算统计
function calcStats() {
  stats.value.total = total.value
  const activeActs = activities.value.filter(a => [2, 3].includes(a.status))
  stats.value.active = activeActs.length
  // today's count estimated via fresh data
  stats.value.today = (list.value || []).filter(r => {
    const d = r.create_time
    if (!d) return false
    return d.includes(new Date().toISOString().slice(0, 10))
  }).length
  // rate avg
  const rates = (activeActs || []).filter(a => a.max_participants > 0).map(a => Math.round((a.current_participants || 0) / a.max_participants * 100))
  stats.value.rate = rates.length ? Math.round(rates.reduce((s, v) => s + v, 0) / rates.length) : 0
}

const topActivities = computed(() => {
  return [...activities.value].sort((a, b) => (b.current_participants || 0) - (a.current_participants || 0)).slice(0, 4)
})

function actStatusText(s: number) {
  const m: Record<number, string> = { 1: '草稿', 2: '报名中', 3: '进行中', 4: '已结束', 5: '已取消' }
  return m[s] || '未知'
}
function actStatusTag(s: number): string {
  const m: Record<number, string> = { 1: 'info', 2: 'success', 3: '', 4: 'warning', 5: 'danger' }
  return m[s] || 'info'
}
function avatarColor(title: string): string {
  const colors = ['#667eea', '#f093fb', '#4facfe', '#43e97b', '#fa709a', '#f5576c', '#36d1dc', '#5b86e5', '#f7971e', '#8e2de2']
  let hash = 0
  for (let i = 0; i < (title || '').length; i++) hash = title.charCodeAt(i) + ((hash << 5) - hash)
  return colors[Math.abs(hash) % colors.length]
}

async function loadActivities() {
  try {
    const res = await apiGet('/admin/activity/list', { page: 1, limit: 999 })
    if (res.code === 0) activities.value = (res.data as any)?.list || (Array.isArray(res.data) ? res.data : [])
  } catch (_) { /* ignore */ }
}

async function loadData() {
  loading.value = true
  try {
    const res = await apiGet('/admin/activity/signupList', {
      page: query.page, limit: query.limit, keyword: query.keyword, activity_id: query.activity_id
    })
    if (res.code === 0) { list.value = (res.data as any)?.list || (Array.isArray(res.data) ? res.data : []); total.value = (res.data as any)?.total || res.count || 0; calcStats() }
  } finally { loading.value = false }
}

function resetQuery() { query.keyword = ''; query.activity_id = ''; query.page = 1; loadData() }

async function handleCancel(row: any) {
  await apiPost('/admin/activity/cancelSignup', { id: row.id })
  ElMessage.success('已取消该报名')
  loadData()
}

onMounted(() => {
  Promise.all([loadActivities(), loadData()])
})
</script>

<style scoped>
.premium-page { padding: 0; animation: fadeSlideIn 0.45s ease; }
@keyframes fadeSlideIn { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }

/* 统计卡片 */
.stat-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 18px; }
.stat-card { position: relative; border-radius: 14px; padding: 22px 24px; display: flex; align-items: center; gap: 16px; overflow: hidden; cursor: default; transition: transform 0.2s, box-shadow 0.2s; }
.stat-card:hover { transform: translateY(-3px); box-shadow: 0 12px 28px rgba(0,0,0,0.12); }
.stat-card .stat-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 22px; color: #fff; flex-shrink: 0; }
.stat-card .stat-bar { position: absolute; bottom: 0; left: 0; height: 3px; width: 100%; background: rgba(255,255,255,0.3); }
.stat-body { flex: 1; color: #fff; }
.stat-num { font-size: 30px; font-weight: 800; line-height: 1; margin-bottom: 4px; }
.stat-label { font-size: 13px; opacity: 0.85; }

.c1 { background: linear-gradient(135deg, #667eea, #764ba2); }
.c1 .stat-icon { background: rgba(255,255,255,0.2); }
.c2 { background: linear-gradient(135deg, #f093fb, #f5576c); }
.c2 .stat-icon { background: rgba(255,255,255,0.2); }
.c3 { background: linear-gradient(135deg, #4facfe, #00f2fe); }
.c3 .stat-icon { background: rgba(255,255,255,0.2); }
.c4 { background: linear-gradient(135deg, #43e97b, #38f9d7); }
.c4 .stat-icon { background: rgba(255,255,255,0.2); }

/* 筛选 */
.filter-panel { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 18px; flex-wrap: wrap; }
.filter-left { display: flex; gap: 10px; align-items: center; }
.kw-input { width: 260px; }
.act-select { width: 200px; }
.act-option { display: flex; align-items: center; justify-content: space-between; width: 100%; }
.filter-right { display: flex; gap: 8px; }

/* 活动卡片 */
.activity-cards { margin-bottom: 18px; }
.section-title { font-size: 15px; font-weight: 700; color: #1e293b; margin-bottom: 12px; display: flex; align-items: center; gap: 8px; }
.title-dot { width: 8px; height: 8px; border-radius: 50%; background: linear-gradient(135deg, #667eea, #764ba2); display: inline-block; }
.act-card-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; }
.act-card { background: #fff; border-radius: 12px; padding: 18px; border: 1px solid #f1f5f9; transition: all 0.25s; position: relative; overflow: hidden; }
.act-card::before { content: ''; position: absolute; top: 0; left: 0; width: 4px; height: 100%; }
.act-card.status-2::before { background: linear-gradient(180deg, #22c55e, #16a34a); }
.act-card.status-3::before { background: linear-gradient(180deg, #3b82f6, #2563eb); }
.act-card.status-4::before { background: linear-gradient(180deg, #f59e0b, #d97706); }
.act-card.status-5::before { background: linear-gradient(180deg, #ef4444, #dc2626); }
.act-card.status-1::before { background: linear-gradient(180deg, #94a3b8, #64748b); }
.act-card:hover { box-shadow: 0 8px 24px rgba(0,0,0,0.08); transform: translateY(-2px); }
.act-card-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px; }
.act-card-title { font-size: 14px; font-weight: 700; color: #1e293b; margin: 0; line-height: 1.3; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 160px; }
.act-card-meta { font-size: 12px; color: #94a3b8; margin-bottom: 10px; display: flex; align-items: center; gap: 4px; }
.act-card-progress {  }
.progress-info { display: flex; justify-content: space-between; font-size: 12px; color: #64748b; margin-bottom: 6px; }
.progress-track { height: 6px; border-radius: 3px; background: #e2e8f0; overflow: hidden; }
.progress-fill { height: 100%; border-radius: 3px; background: linear-gradient(90deg, #667eea, #764ba2); transition: width 0.6s ease; }

/* 表格面板 */
.table-panel { border-radius: 12px; border: 1px solid #f1f5f9; overflow: hidden; }
.table-panel :deep(.el-card__header) { border-bottom: 1px solid #f1f5f9; padding: 16px 20px; }
.panel-header { display: flex; align-items: center; justify-content: space-between; }
.panel-title { font-size: 15px; font-weight: 700; color: #1e293b; display: flex; align-items: center; gap: 8px; }
.panel-count { font-size: 12px; color: #94a3b8; }
.user-cell { display: flex; align-items: center; gap: 10px; }
.cell-avatar { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #fff; font-weight: 700; font-size: 14px; flex-shrink: 0; }
.cell-info { line-height: 1.3; }
.cell-name { font-size: 13px; font-weight: 600; color: #1e293b; }
.cell-sub { font-size: 11px; color: #94a3b8; }
.remark-text { color: #64748b; font-size: 13px; }
.pagi-wrap { margin-top: 16px; display: flex; justify-content: flex-end; }

@media (max-width: 1400px) { .stat-grid, .act-card-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 900px) { .stat-grid, .act-card-grid { grid-template-columns: 1fr; } }
</style>
