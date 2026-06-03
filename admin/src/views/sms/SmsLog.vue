<template>
  <div class="sms-page">
    <!-- 页面头部 -->
    <div class="page-header">
      <div class="header-left">
        <div class="header-icon"><el-icon :size="26"><DocumentCopy /></el-icon></div>
        <div class="header-info">
          <h2>短信发送日志</h2>
          <p>查看所有短信发送记录、状态与统计数据</p>
        </div>
      </div>
    </div>

    <!-- 统计卡片 -->
    <div class="stats-row">
      <div class="stat-card total">
        <div class="stat-card-icon"><el-icon :size="24"><Odometer /></el-icon></div>
        <div class="stat-card-body">
          <div class="stat-card-value">{{ stats.total }}</div>
          <div class="stat-card-label">发送总数</div>
        </div>
      </div>
      <div class="stat-card success">
        <div class="stat-card-icon"><el-icon :size="24"><CircleCheck /></el-icon></div>
        <div class="stat-card-body">
          <div class="stat-card-value">{{ stats.success }}</div>
          <div class="stat-card-label">发送成功</div>
        </div>
      </div>
      <div class="stat-card fail">
        <div class="stat-card-icon"><el-icon :size="24"><CircleClose /></el-icon></div>
        <div class="stat-card-body">
          <div class="stat-card-value">{{ stats.fail }}</div>
          <div class="stat-card-label">发送失败</div>
        </div>
      </div>
      <div class="stat-card rate">
        <div class="stat-card-icon"><el-icon :size="24"><TrendCharts /></el-icon></div>
        <div class="stat-card-body">
          <div class="stat-card-value">{{ stats.rate }}%</div>
          <div class="stat-card-label">成功率</div>
        </div>
      </div>
    </div>

    <!-- 搜索栏 -->
    <div class="search-card">
      <el-form :model="query" inline>
        <el-form-item>
          <el-input v-model="query.keyword" placeholder="手机号/模板/内容" clearable style="width:240px" prefix-icon="Search" @keyup.enter="loadData" />
        </el-form-item>
        <el-form-item>
          <el-select v-model="query.status" placeholder="发送状态" clearable style="width:130px">
            <el-option label="发送成功" :value="1" />
            <el-option label="发送失败" :value="2" />
            <el-option label="待发送" :value="0" />
          </el-select>
        </el-form-item>
        <el-form-item>
          <el-date-picker v-model="dateRange" type="daterange" range-separator="至" start-placeholder="开始日期" end-placeholder="结束日期" value-format="YYYY-MM-DD" style="width:260px" @change="onDateChange" />
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="loadData"><el-icon><Search /></el-icon> 搜索</el-button>
          <el-button @click="resetQuery">重置</el-button>
        </el-form-item>
      </el-form>
    </div>

    <!-- 日志列表 -->
    <el-card shadow="never" class="table-card">
      <el-table :data="list" v-loading="loading" stripe border highlight-current-row class="modern-table">
        <el-table-column type="index" label="#" width="55" />
        <el-table-column prop="phone" label="手机号码" width="140" align="center">
          <template #default="{ row }">
            <el-tag effect="plain" type="info" size="small">{{ row.phone }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="template_id" label="模板ID" width="160" show-overflow-tooltip>
          <template #default="{ row }">
            <span class="mono-text">{{ row.template_id || '-' }}</span>
          </template>
        </el-table-column>
        <el-table-column prop="content" label="短信内容" min-width="260" show-overflow-tooltip>
          <template #default="{ row }">
            <span style="color:#4a5568;font-size:13px">{{ row.content || '-' }}</span>
          </template>
        </el-table-column>
        <el-table-column label="发送状态" width="110" align="center">
          <template #default="{ row }">
            <el-tag v-if="row.status == 1" type="success" effect="light" round size="small"><el-icon style="margin-right:4px"><CircleCheck /></el-icon> 成功</el-tag>
            <el-tag v-else-if="row.status == 2" type="danger" effect="light" round size="small"><el-icon style="margin-right:4px"><CircleClose /></el-icon> 失败</el-tag>
            <el-tag v-else type="info" effect="light" round size="small"><el-icon style="margin-right:4px"><Clock /></el-icon> 待发送</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="result" label="返回结果" width="180" show-overflow-tooltip>
          <template #default="{ row }">
            <span v-if="row.result" :style="{ color: row.status == 1 ? '#48bb78' : '#e53e3e', fontSize:'12px' }">{{ row.result }}</span>
            <span v-else style="color:#cbd5e0">-</span>
          </template>
        </el-table-column>
        <el-table-column prop="create_time" label="发送时间" width="170" sortable />
        <el-table-column label="操作" width="100" align="center" fixed="right">
          <template #default="{ row }">
            <el-popconfirm v-if="row.status == 2" title="确定重发此短信？" @confirm="resend(row)">
              <template #reference>
                <el-button size="small" type="warning" link><el-icon><RefreshRight /></el-icon> 重发</el-button>
              </template>
            </el-popconfirm>
            <span v-else style="color:#cbd5e0;font-size:12px">-</span>
          </template>
        </el-table-column>
      </el-table>
      <div class="pagination-wrap">
        <el-pagination v-model:current-page="query.page" v-model:page-size="query.limit" :total="total" :page-sizes="[15,30,50,100]" layout="total,sizes,prev,pager,next" @update:current-page="loadData" @update:page-size="loadData" />
      </div>
    </el-card>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { apiGet, apiPost } from '@/utils/request'
import { DocumentCopy, Odometer, CircleCheck, CircleClose, TrendCharts, Clock, RefreshRight, Search } from '@element-plus/icons-vue'

const list = ref<any[]>([])
const total = ref(0)
const loading = ref(false)
const dateRange = ref<any[]>([])

const query = reactive({ page: 1, limit: 15, keyword: '', status: '' as any, start_date: '', end_date: '' })
const stats = reactive({ total: 0, success: 0, fail: 0, rate: '0' })

async function loadData() {
  loading.value = true
  try {
    const res = await apiGet('/admin/sms/smsLogList', {
      page: query.page, limit: query.limit,
      keyword: query.keyword, status: query.status,
      start_date: query.start_date, end_date: query.end_date,
    })
    if (res.code === 0) { list.value = res.data?.list || res.data || []; total.value = res.data?.total || res.count || 0 }
  } finally { loading.value = false }
}

async function loadStats() {
  try {
    const res = await apiGet('/admin/sms/smsLogStats', {
      start_date: query.start_date, end_date: query.end_date,
    })
    if (res.code === 0) {
      const d = res.data
      stats.total = d.total || 0
      stats.success = d.success || 0
      stats.fail = d.fail || 0
      stats.rate = stats.total > 0 ? ((stats.success / stats.total) * 100).toFixed(1) : '0'
    }
  } catch { /* silent */ }
}

function resetQuery() {
  query.keyword = ''; query.status = ''; query.start_date = ''; query.end_date = ''; query.page = 1
  dateRange.value = []
  loadData(); loadStats()
}

function onDateChange(v: any) {
  if (v && v.length === 2) {
    query.start_date = v[0]; query.end_date = v[1]
  } else {
    query.start_date = ''; query.end_date = ''
  }
}

async function resend(row: any) {
  try {
    const res = await apiPost('/admin/sms/resend', { id: row.id, phone: row.phone, content: row.content })
    if (res.code === 0) ElMessage.success('重发成功'); loadData(); loadStats()
  } catch { }
}

onMounted(() => { loadData(); loadStats() })
</script>

<style scoped>
.sms-page { max-width: 1400px; margin: 0 auto; }
.page-header {
  display: flex; align-items: center; justify-content: space-between;
  background: linear-gradient(135deg, #a18cd1 0%, #fbc2eb 100%);
  border-radius: 12px; padding: 24px 28px; margin-bottom: 20px; color: #1a202c; flex-wrap: wrap; gap:16px;
}
.header-left { display: flex; align-items: center; gap: 16px; }
.header-icon { width: 48px; height: 48px; background: rgba(255,255,255,.5); border-radius: 12px; display: flex; align-items: center; justify-content: center; }
.header-info h2 { margin: 0 0 4px; font-size: 20px; font-weight: 700; }
.header-info p { margin: 0; font-size: 13px; opacity: .6; }

.stats-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 16px; }
.stat-card { background: #fff; border-radius: 10px; padding: 20px; border: 1px solid #e2e8f0; display: flex; align-items: center; gap: 16px; }
.stat-card.total .stat-card-icon { color: #667eea; background: #eef0ff; }
.stat-card.success .stat-card-icon { color: #48bb78; background: #e6fffa; }
.stat-card.fail .stat-card-icon { color: #fc8181; background: #fff5f5; }
.stat-card.rate .stat-card-icon { color: #ed8936; background: #fffaf0; }
.stat-card-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.stat-card-value { font-size: 24px; font-weight: 700; color: #2d3748; line-height: 1.2; }
.stat-card-label { font-size: 13px; color: #a0aec0; margin-top: 2px; }

.search-card { background: #fff; border-radius: 10px; padding: 16px 20px; margin-bottom: 16px; border: 1px solid #e2e8f0; }
.table-card { border-radius: 10px; border: 1px solid #e2e8f0; overflow: hidden; }
.pagination-wrap { padding: 16px 0 0; display: flex; justify-content: flex-end; }
.mono-text { font-family: 'SF Mono', 'Fira Code', monospace; background: #f7fafc; padding: 2px 8px; border-radius: 4px; font-size: 12px; color: #4a5568; }
.modern-table :deep(th) { background: #f8fafc !important; font-weight: 600; color: #2d3748; }
</style>
