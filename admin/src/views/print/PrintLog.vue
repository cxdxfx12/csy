<template>
  <div class="print-log-page">
    <!-- 页面头部 -->
    <div class="page-hero">
      <div class="hero-content">
        <div class="hero-left">
          <div class="hero-icon">
            <el-icon :size="28"><DocumentCopy /></el-icon>
          </div>
          <div class="hero-text">
            <h1>打印日志看板</h1>
            <p>实时追踪所有打印操作，掌握单据输出动态与历史记录</p>
          </div>
        </div>
        <div class="hero-actions">
          <el-button size="large" plain class="btn-export" @click="handleExport">
            <el-icon><Download /></el-icon>
            <span>导出日志</span>
          </el-button>
        </div>
      </div>
    </div>

    <!-- 统计看板 -->
    <div class="kpi-grid">
      <div class="kpi-card kpi-today">
        <div class="kpi-icon-wrap">
          <div class="kpi-icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
            <el-icon :size="20"><Sunny /></el-icon>
          </div>
        </div>
        <div class="kpi-body">
          <span class="kpi-value">{{ stats.today }}</span>
          <span class="kpi-label">今日打印</span>
        </div>
        <div class="kpi-badge">
          <el-tag size="small" type="danger" effect="light" round>当日</el-tag>
        </div>
      </div>
      <div class="kpi-card kpi-week">
        <div class="kpi-icon-wrap">
          <div class="kpi-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
            <el-icon :size="20"><Calendar /></el-icon>
          </div>
        </div>
        <div class="kpi-body">
          <span class="kpi-value">{{ stats.thisWeek }}</span>
          <span class="kpi-label">本周打印</span>
        </div>
        <div class="kpi-badge">
          <el-tag size="small" type="primary" effect="light" round>周</el-tag>
        </div>
      </div>
      <div class="kpi-card kpi-month">
        <div class="kpi-icon-wrap">
          <div class="kpi-icon" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
            <el-icon :size="20"><DataAnalysis /></el-icon>
          </div>
        </div>
        <div class="kpi-body">
          <span class="kpi-value">{{ stats.thisMonth }}</span>
          <span class="kpi-label">本月打印</span>
        </div>
        <div class="kpi-badge">
          <el-tag size="small" type="success" effect="light" round>月</el-tag>
        </div>
      </div>
      <div class="kpi-card kpi-total">
        <div class="kpi-icon-wrap">
          <div class="kpi-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
            <el-icon :size="20"><TrendCharts /></el-icon>
          </div>
        </div>
        <div class="kpi-body">
          <span class="kpi-value">{{ stats.total }}</span>
          <span class="kpi-label">累计打印</span>
        </div>
        <div class="kpi-badge">
          <el-tag size="small" type="warning" effect="light" round>总计</el-tag>
        </div>
      </div>
    </div>

    <!-- 筛选栏 -->
    <div class="filter-panel">
      <div class="filter-row">
        <div class="filter-left">
          <el-date-picker
            v-model="query.dateRange"
            type="daterange"
            range-separator="至"
            start-placeholder="开始日期"
            end-placeholder="结束日期"
            class="date-picker"
            @change="loadData"
          />
          <el-input v-model="query.keyword" placeholder="搜索标题或模板编码..." clearable class="search-input" @keyup.enter="loadData" @clear="loadData">
            <template #prefix><el-icon><Search /></el-icon></template>
          </el-input>
          <el-select v-model="query.community_id" placeholder="所属小区" clearable class="filter-sel" @change="loadData">
            <el-option v-for="c in communities" :key="c.id" :label="c.name" :value="c.id" />
          </el-select>
          <el-select v-model="query.business_type" placeholder="业务类型" clearable class="filter-sel" @change="loadData">
            <el-option label="收据打印" value="receipt" />
            <el-option label="催缴通知" value="notice" />
            <el-option label="合同" value="contract" />
            <el-option label="表单" value="form" />
            <el-option label="其他" value="other" />
          </el-select>
        </div>
        <div class="filter-right">
          <el-button @click="resetFilter" text>
            <el-icon><Refresh /></el-icon>重置
          </el-button>
        </div>
      </div>
    </div>

    <!-- 日志表格 -->
    <el-card shadow="never" class="log-card">
      <!-- 快捷筛选标签 -->
      <div class="quick-filters">
        <el-button
          v-for="f in quickFilters"
          :key="f.key"
          :type="activeQuickFilter === f.key ? 'primary' : ''"
          size="small"
          round
          @click="applyQuickFilter(f.key)"
        >
          {{ f.label }}
        </el-button>
      </div>

      <el-table :data="list" v-loading="loading" stripe class="log-table">
        <el-table-column type="index" label="#" width="55" />
        <el-table-column prop="id" label="日志ID" width="80" />
        <el-table-column prop="title" label="打印标题" min-width="200">
          <template #default="{row}">
            <div class="log-title-cell">
              <el-icon :size="16" class="title-dot"><CircleCheckFilled /></el-icon>
              <span>{{ row.title || '-' }}</span>
            </div>
          </template>
        </el-table-column>
        <el-table-column prop="template_code" label="模板编码" width="160">
          <template #default="{row}">
            <el-tag size="small" effect="plain" type="info" v-if="row.template_code">{{ row.template_code }}</el-tag>
            <span v-else class="text-muted">-</span>
          </template>
        </el-table-column>
        <el-table-column prop="business_type" label="业务类型" width="120">
          <template #default="{row}">
            <el-tag :type="businessTypeColor(row.business_type)" size="small" effect="light" round>
              {{ businessTypeLabel(row.business_type) }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="print_count" label="打印份数" width="100" align="center">
          <template #default="{row}">
            <span class="count-badge">{{ row.print_count || 1 }}</span>
          </template>
        </el-table-column>
        <el-table-column prop="community_id" label="小区" width="130">
          <template #default="{row}">
            {{ getCommunityName(row.community_id) }}
          </template>
        </el-table-column>
        <el-table-column prop="operator_id" label="操作员ID" width="100" />
        <el-table-column prop="create_time" label="打印时间" width="175" sortable>
          <template #default="{row}">
            <div class="time-cell">
              <el-icon :size="14"><Clock /></el-icon>
              <span>{{ row.create_time || '-' }}</span>
            </div>
          </template>
        </el-table-column>
        <el-table-column label="操作" width="80" fixed="right">
          <template #default="{row}">
            <el-button link type="primary" size="small" @click="showDetail(row)">
              <el-icon><View /></el-icon>
            </el-button>
          </template>
        </el-table-column>
      </el-table>

      <!-- 空状态 -->
      <div v-if="list.length === 0 && !loading" class="empty-state-inline">
        <el-icon :size="48"><DocumentRemove /></el-icon>
        <p>暂无打印记录</p>
      </div>

      <!-- 分页 -->
      <div class="pagination-wrap">
        <el-pagination
          v-model:current-page="query.page"
          v-model:page-size="query.limit"
          :total="total"
          :page-sizes="[15, 30, 50, 100]"
          layout="total, sizes, prev, pager, next, jumper"
          background
        />
      </div>
    </el-card>

    <!-- 详情弹窗 -->
    <el-dialog v-model="detailVisible" title="打印记录详情" width="500px" destroy-on-close class="detail-dialog">
      <div class="detail-body" v-if="detailRow">
        <div class="detail-item">
          <span class="dl">日志ID</span>
          <span class="dv">{{ detailRow.id }}</span>
        </div>
        <div class="detail-item">
          <span class="dl">打印标题</span>
          <span class="dv highlight">{{ detailRow.title || '-' }}</span>
        </div>
        <div class="detail-item">
          <span class="dl">模板编码</span>
          <span class="dv">{{ detailRow.template_code || '-' }}</span>
        </div>
        <div class="detail-item">
          <span class="dl">业务类型</span>
          <span class="dv">
            <el-tag :type="businessTypeColor(detailRow.business_type)" size="small">{{ businessTypeLabel(detailRow.business_type) }}</el-tag>
          </span>
        </div>
        <div class="detail-item">
          <span class="dl">关联业务ID</span>
          <span class="dv">{{ detailRow.business_id || '-' }}</span>
        </div>
        <div class="detail-item">
          <span class="dl">打印份数</span>
          <span class="dv">{{ detailRow.print_count || 1 }}</span>
        </div>
        <div class="detail-item">
          <span class="dl">所属小区</span>
          <span class="dv">{{ getCommunityName(detailRow.community_id) }}</span>
        </div>
        <div class="detail-item">
          <span class="dl">操作员ID</span>
          <span class="dv">{{ detailRow.operator_id || '-' }}</span>
        </div>
        <div class="detail-item">
          <span class="dl">打印时间</span>
          <span class="dv">{{ detailRow.create_time || '-' }}</span>
        </div>
      </div>
      <template #footer>
        <el-button @click="detailVisible = false">关闭</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { apiGet } from '@/utils/request'
import { ElMessage } from 'element-plus'
import {
  DocumentCopy, Download, Sunny, Calendar, DataAnalysis, TrendCharts,
  Search, Refresh, CircleCheckFilled, Clock, View, DocumentRemove
} from '@element-plus/icons-vue'

const list = ref<any[]>([])
const total = ref(0)
const loading = ref(false)
const communities = ref<any[]>([])
const detailVisible = ref(false)
const detailRow = ref<any>(null)
const activeQuickFilter = ref('all')

const query = reactive({
  page: 1, limit: 15, keyword: '', community_id: undefined as any,
  business_type: '', dateRange: null as any
})

const quickFilters = [
  { key: 'all', label: '全部' },
  { key: 'today', label: '今天' },
  { key: 'week', label: '本周' },
  { key: 'month', label: '本月' },
]

const stats = computed(() => {
  const data = list.value || []
  const now = new Date()
  const today = now.toISOString().slice(0, 10)
  const weekStart = new Date(now.getTime() - now.getDay() * 86400000).toISOString().slice(0, 10)
  const monthStart = now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0')
  return {
    total: total.value,
    today: data.filter((d: any) => d.create_time && d.create_time.startsWith(today)).length,
    thisWeek: data.filter((d: any) => d.create_time && d.create_time >= weekStart).length,
    thisMonth: data.filter((d: any) => d.create_time && d.create_time.startsWith(monthStart)).length,
  }
})

function getCommunityName(id: number) {
  const c = communities.value.find((c: any) => c.id == id)
  return c?.name || (id ? 'ID:' + id : '-')
}

function businessTypeLabel(type: string) {
  const map: Record<string, string> = { receipt: '收据打印', notice: '催缴通知', contract: '合同', form: '表单' }
  return map[type] || type || '其他'
}

function businessTypeColor(type: string) {
  const map: Record<string, string> = { receipt: 'success', notice: 'warning', contract: 'danger', form: 'info' }
  return map[type] || 'info'
}

function applyQuickFilter(key: string) {
  activeQuickFilter.value = key
  const now = new Date()
  const today = now.toISOString().slice(0, 10)
  query.dateRange = null
  if (key === 'today') {
    query.dateRange = [new Date(today), new Date(today)]
  } else if (key === 'week') {
    const weekStart = new Date(now.getTime() - now.getDay() * 86400000)
    weekStart.setHours(0, 0, 0, 0)
    query.dateRange = [weekStart, new Date(today + ' 23:59:59')]
  } else if (key === 'month') {
    const monthStart = new Date(now.getFullYear(), now.getMonth(), 1)
    query.dateRange = [monthStart, new Date(today + ' 23:59:59')]
  }
  query.page = 1
  loadData()
}

async function loadCommunities() {
  try {
    const res = await apiGet('/admin/community/listAll')
    if (res && res.code === 0) communities.value = res.data || []
  } catch (_) {}
}

async function loadData() {
  loading.value = true
  try {
    const params: any = { page: query.page, limit: query.limit }
    if (query.keyword) params.keyword = query.keyword
    if (query.community_id) params.community_id = query.community_id
    if (query.business_type) params.business_type = query.business_type
    if (query.dateRange && query.dateRange.length === 2) {
      params.start_date = formatDate(query.dateRange[0])
      params.end_date = formatDate(query.dateRange[1])
    }
    const res = await apiGet('/admin/print/printLogList', params)
    if (res && res.code === 0) { list.value = res.data || []; total.value = res.count || 0 }
  } catch (_) { list.value = []; total.value = 0 }
  finally { loading.value = false }
}

function formatDate(d: Date | string) {
  if (typeof d === 'string') return d.slice(0, 10)
  return d.toISOString().slice(0, 10)
}

function resetFilter() {
  query.keyword = ''
  query.community_id = undefined
  query.business_type = ''
  query.dateRange = null
  query.page = 1
  activeQuickFilter.value = 'all'
  loadData()
}

function showDetail(row: any) {
  detailRow.value = row
  detailVisible.value = true
}

function handleExport() {
  ElMessage.info('导出功能开发中，敬请期待')
}

onMounted(() => {
  loadCommunities()
  loadData()
})

watch([() => query.page, () => query.limit], () => {
  loadData()
})
</script>

<style scoped>
.print-log-page {
  min-height: calc(100vh - 100px);
  padding: 0;
}

/* 页面头部 */
.page-hero {
  background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 50%, #2563eb 100%);
  border-radius: 16px;
  padding: 28px 32px;
  margin-bottom: 24px;
  position: relative;
  overflow: hidden;
}
.page-hero::after {
  content: '';
  position: absolute;
  right: 40px;
  top: -30px;
  width: 160px;
  height: 160px;
  border-radius: 50%;
  border: 2px dashed rgba(255,255,255,0.08);
}
.hero-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
  position: relative;
  z-index: 1;
}
.hero-left {
  display: flex;
  align-items: center;
  gap: 20px;
}
.hero-icon {
  width: 56px;
  height: 56px;
  border-radius: 14px;
  background: rgba(255,255,255,0.12);
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  backdrop-filter: blur(10px);
}
.hero-text h1 {
  margin: 0;
  color: #fff;
  font-size: 22px;
  font-weight: 700;
  letter-spacing: 0.5px;
}
.hero-text p {
  margin: 6px 0 0;
  color: rgba(255,255,255,0.7);
  font-size: 13px;
}
.btn-export {
  height: 42px;
  padding: 0 24px;
  font-weight: 600;
  font-size: 14px;
  border-radius: 10px;
  color: #fff;
  border: 1.5px solid rgba(255,255,255,0.3);
  background: rgba(255,255,255,0.08);
  backdrop-filter: blur(10px);
}
.btn-export:hover {
  background: rgba(255,255,255,0.18);
  border-color: rgba(255,255,255,0.5);
  transform: translateY(-1px);
}

/* KPI 卡片 */
.kpi-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
  margin-bottom: 24px;
}
.kpi-card {
  background: #fff;
  border-radius: 14px;
  padding: 20px 24px;
  border: 1px solid #e8ecf1;
  display: flex;
  align-items: center;
  gap: 16px;
  transition: all 0.3s ease;
  position: relative;
  overflow: hidden;
}
.kpi-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  width: 4px;
  height: 100%;
}
.kpi-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(0,0,0,0.08);
  border-color: #c8d6e5;
}
.kpi-today::before { background: linear-gradient(180deg, #f093fb, #f5576c); }
.kpi-week::before { background: linear-gradient(180deg, #4facfe, #00f2fe); }
.kpi-month::before { background: linear-gradient(180deg, #43e97b, #38f9d7); }
.kpi-total::before { background: linear-gradient(180deg, #667eea, #764ba2); }
.kpi-icon {
  width: 44px;
  height: 44px;
  border-radius: 11px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  flex-shrink: 0;
}
.kpi-body {
  flex: 1;
  min-width: 0;
}
.kpi-value {
  font-size: 26px;
  font-weight: 800;
  color: #1a202c;
  line-height: 1;
  display: block;
}
.kpi-label {
  font-size: 12px;
  color: #718096;
  margin-top: 4px;
  display: block;
}
.kpi-badge {
  position: absolute;
  top: 12px;
  right: 16px;
}

/* 筛选面板 */
.filter-panel {
  background: #fff;
  border-radius: 14px;
  padding: 16px 20px;
  margin-bottom: 20px;
  border: 1px solid #e8ecf1;
}
.filter-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 12px;
}
.filter-left {
  display: flex;
  gap: 10px;
  flex: 1;
  flex-wrap: wrap;
}
.date-picker {
  width: 260px;
}
.date-picker :deep(.el-input__wrapper) {
  border-radius: 10px;
}
.search-input {
  width: 220px;
}
.search-input :deep(.el-input__wrapper) {
  border-radius: 10px;
}
.filter-sel {
  width: 150px;
}
.filter-sel :deep(.el-input__wrapper) {
  border-radius: 10px;
}
.filter-right {
  flex-shrink: 0;
}

/* 快捷筛选 */
.quick-filters {
  display: flex;
  gap: 8px;
  margin-bottom: 16px;
  padding-bottom: 12px;
  border-bottom: 1px solid #f0f2f5;
}

/* 日志表格 */
.log-card {
  border-radius: 14px;
  border: 1px solid #e8ecf1;
  overflow: hidden;
}
.log-table :deep(.el-table__header th) {
  background: #f8fafc;
  font-weight: 600;
  color: #475569;
  font-size: 13px;
}
.log-table :deep(.el-table__row) {
  transition: background 0.2s;
}
.log-table :deep(.el-table__row:hover) {
  background: #f8faff;
}
.log-title-cell {
  display: flex;
  align-items: center;
  gap: 8px;
}
.title-dot {
  color: #2563eb;
  flex-shrink: 0;
}
.count-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 28px;
  height: 26px;
  padding: 0 8px;
  background: #f0f5ff;
  color: #2563eb;
  border-radius: 13px;
  font-weight: 700;
  font-size: 13px;
}
.time-cell {
  display: flex;
  align-items: center;
  gap: 6px;
  color: #64748b;
  font-size: 13px;
}
.text-muted {
  color: #cbd5e1;
}

/* 详情弹窗 */
.detail-dialog :deep(.el-dialog__header) {
  border-bottom: 1px solid #f0f2f5;
  padding: 20px 24px;
}
.detail-dialog :deep(.el-dialog__body) {
  padding: 24px;
}
.detail-body {
  display: flex;
  flex-direction: column;
  gap: 14px;
}
.detail-item {
  display: flex;
  align-items: center;
  padding: 10px 16px;
  background: #f8fafc;
  border-radius: 10px;
  border: 1px solid #f0f2f5;
}
.detail-item .dl {
  width: 100px;
  font-size: 13px;
  color: #64748b;
  font-weight: 500;
  flex-shrink: 0;
}
.detail-item .dv {
  font-size: 14px;
  color: #1a202c;
  font-weight: 500;
}
.detail-item .dv.highlight {
  color: #2563eb;
  font-weight: 600;
}

/* 空状态 */
.empty-state-inline {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 60px 20px;
  color: #cbd5e1;
}
.empty-state-inline p {
  color: #94a3b8;
  font-size: 14px;
  margin-top: 12px;
}

/* 分页 */
.pagination-wrap {
  display: flex;
  justify-content: flex-end;
  margin-top: 16px;
}

/* 响应式 */
@media (max-width: 1200px) {
  .kpi-grid { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 768px) {
  .kpi-grid { grid-template-columns: 1fr 1fr; }
  .filter-left { flex-direction: column; }
  .filter-left > * { width: 100% !important; }
}
</style>
