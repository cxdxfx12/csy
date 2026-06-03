<template>
  <div class="sse-page">
    <!-- 页面头部 -->
    <div class="page-hero">
      <div class="hero-left">
        <div class="hero-icon"><el-icon :size="28"><DataLine /></el-icon></div>
        <div class="hero-text">
          <h2>实时事件监控</h2>
          <p>Server-Sent Events 实时推送事件流水，监控系统通知投递状态</p>
        </div>
      </div>
      <div class="hero-badge">
        <span class="live-dot" />
        <span class="live-text">实时监控中</span>
      </div>
    </div>

    <!-- 统计卡片 -->
    <div class="stats-grid">
      <div class="stat-card total">
        <div class="sc-icon-wrap"><el-icon :size="22"><Odometer /></el-icon></div>
        <div class="sc-body">
          <span class="sc-value">{{ stats.total }}</span>
          <span class="sc-label">事件总数</span>
        </div>
        <div class="sc-spark">
          <span class="sc-trend up" v-if="stats.total > 0">● 活跃</span>
          <span class="sc-trend idle" v-else>● 空闲</span>
        </div>
      </div>
      <div class="stat-card read">
        <div class="sc-icon-wrap"><el-icon :size="22"><Reading /></el-icon></div>
        <div class="sc-body">
          <span class="sc-value">{{ stats.read }}</span>
          <span class="sc-label">已读事件</span>
        </div>
        <div class="sc-spark">
          <span class="sc-trend up">{{ stats.readRate }}% 已读率</span>
        </div>
      </div>
      <div class="stat-card unread">
        <div class="sc-icon-wrap"><el-icon :size="22"><BellFilled /></el-icon></div>
        <div class="sc-body">
          <span class="sc-value" style="color:#e53e3e">{{ stats.unread }}</span>
          <span class="sc-label">未读事件</span>
        </div>
        <div class="sc-spark">
          <span class="sc-trend warn" v-if="stats.unread > 0">⚠ 待处理</span>
          <span class="sc-trend ok" v-else>✓ 已清空</span>
        </div>
      </div>
      <div class="stat-card today">
        <div class="sc-icon-wrap"><el-icon :size="22"><Timer /></el-icon></div>
        <div class="sc-body">
          <span class="sc-value">{{ stats.today }}</span>
          <span class="sc-label">今日新增</span>
        </div>
        <div class="sc-spark">
          <span class="sc-trend info">今日累计</span>
        </div>
      </div>
    </div>

    <!-- 事件类型分布 -->
    <div class="event-type-bar">
      <span class="type-label">事件类型分布：</span>
      <div class="type-tags">
        <template v-for="t in eventTypes" :key="t.key">
          <el-tag :type="t.tagType" effect="light" size="default" :disable-transitions="false" @click="query.event_type = query.event_type === t.key ? '' : t.key; loadData()">
            {{ t.label }}
            <span class="type-count">({{ typeCount(t.key) }})</span>
          </el-tag>
        </template>
      </div>
    </div>

    <!-- 搜索栏 -->
    <div class="search-bar">
      <el-form :model="query" inline>
        <el-form-item>
          <el-input v-model="query.keyword" placeholder="搜索标题/内容..." clearable style="width:260px" prefix-icon="Search" @keyup.enter="loadData" />
        </el-form-item>
        <el-form-item>
          <el-select v-model="query.event_type" placeholder="事件类型" clearable style="width:140px">
            <el-option label="系统通知" value="system" />
            <el-option label="缴费提醒" value="payment" />
            <el-option label="工单更新" value="repair" />
            <el-option label="投诉回复" value="complaint" />
            <el-option label="访客通知" value="visitor" />
            <el-option label="活动推送" value="activity" />
          </el-select>
        </el-form-item>
        <el-form-item>
          <el-select v-model="query.is_read" placeholder="阅读状态" clearable style="width:120px">
            <el-option label="已读" :value="1" />
            <el-option label="未读" :value="0" />
          </el-select>
        </el-form-item>
        <el-form-item>
          <el-date-picker v-model="dateRange" type="daterange" range-separator="→" start-placeholder="开始" end-placeholder="结束" value-format="YYYY-MM-DD" style="width:250px" @change="onDateChange" />
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="loadData"><el-icon><Search /></el-icon> 搜索</el-button>
          <el-button @click="resetQuery">重置</el-button>
        </el-form-item>
      </el-form>
    </div>

    <!-- 事件列表 -->
    <el-card shadow="never" class="table-card">
      <template #header>
        <div class="card-header-bar">
          <span><el-icon><List /></el-icon> 事件流水</span>
          <el-button-group>
            <el-button :type="autoRefresh ? 'primary' : 'default'" size="small" @click="toggleAutoRefresh">
              <el-icon><Refresh /></el-icon> {{ autoRefresh ? '自动刷新中' : '自动刷新' }}
            </el-button>
            <el-button size="small" @click="loadData"><el-icon><RefreshRight /></el-icon> 手动刷新</el-button>
          </el-button-group>
        </div>
      </template>
      <el-table :data="list" v-loading="loading" stripe class="modern-table" row-key="id">
        <el-table-column type="index" label="#" width="55" />
        <el-table-column label="事件类型" width="120" align="center">
          <template #default="{ row }">
            <el-tag :type="eventTagType(row.event_type)" effect="light" round size="small">{{ eventLabel(row.event_type) }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column label="事件标题" min-width="200">
          <template #default="{ row }">
            <div class="event-title-cell">
              <span class="unread-marker" v-if="row.is_read != 1" />
              <span :class="{ 'event-title-unread': row.is_read != 1 }">{{ row.title || '无标题' }}</span>
            </div>
          </template>
        </el-table-column>
        <el-table-column label="事件内容" min-width="280" show-overflow-tooltip>
          <template #default="{ row }">
            <span class="event-content">{{ row.content || '-' }}</span>
          </template>
        </el-table-column>
        <el-table-column label="目标用户" width="140" align="center">
          <template #default="{ row }">
            <div class="user-cell">
              <el-avatar :size="24" :style="{ background: avatarColor(row.user_id) }">
                {{ (row.user_type || 'U').charAt(0).toUpperCase() }}
              </el-avatar>
              <span class="user-text">{{ row.user_type || '-' }} #{{ row.user_id }}</span>
            </div>
          </template>
        </el-table-column>
        <el-table-column label="阅读状态" width="90" align="center">
          <template #default="{ row }">
            <el-tag :type="row.is_read == 1 ? 'success' : 'warning'" effect="dark" round size="small">
              {{ row.is_read == 1 ? '已读' : '未读' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="创建时间" width="175" sortable prop="create_time">
          <template #default="{ row }">
            <span class="time-text">{{ row.create_time || '-' }}</span>
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
import { ref, reactive, computed, onMounted, onUnmounted } from 'vue'
import { apiGet } from '@/utils/request'
import { DataLine, Odometer, Reading, BellFilled, Timer, Search, Refresh, RefreshRight, List } from '@element-plus/icons-vue'

const list = ref<any[]>([])
const total = ref(0)
const loading = ref(false)
const dateRange = ref<any[]>([])
const autoRefresh = ref(false)
let refreshTimer: any = null

const query = reactive({ page: 1, limit: 15, keyword: '', event_type: '', is_read: '' as any, start_date: '', end_date: '' })
const stats = reactive({ total: 0, read: 0, unread: 0, today: 0, readRate: '0' })

const eventTypes = [
  { key: 'system', label: '系统通知', tagType: '' as any },
  { key: 'payment', label: '缴费提醒', tagType: 'warning' as any },
  { key: 'repair', label: '工单更新', tagType: 'primary' as any },
  { key: 'complaint', label: '投诉回复', tagType: 'danger' as any },
  { key: 'visitor', label: '访客通知', tagType: 'success' as any },
  { key: 'activity', label: '活动推送', tagType: 'info' as any },
]

function eventLabel(v: string) {
  const m: Record<string, string> = { system: '系统', payment: '缴费', repair: '工单', complaint: '投诉', visitor: '访客', activity: '活动' }
  return m[v] || v || '未知'
}
function eventTagType(v: string) {
  const m: Record<string, string> = { system: '', payment: 'warning', repair: 'primary', complaint: 'danger', visitor: 'success', activity: 'info' }
  return m[v] || 'info'
}
function typeCount(key: string) { return list.value.filter((e: any) => e.event_type === key).length }
function avatarColor(id: number) {
  const colors = ['#667eea','#48bb78','#ed8936','#38bdf8','#e53e3e','#a855f7','#ec4899','#14b8a6']
  return colors[id % colors.length]
}

async function loadData() {
  loading.value = true
  try {
    const res = await apiGet('/admin/system/sseEventList', {
      page: query.page, limit: query.limit,
      keyword: query.keyword, event_type: query.event_type,
      is_read: query.is_read, start_date: query.start_date, end_date: query.end_date,
    })
    if (res.code === 0) { list.value = res.data?.list || res.data || []; total.value = res.data?.total || res.count || 0 }
    else { list.value = []; total.value = 0 }
    calcStats()
  } catch { list.value = []; total.value = 0 }
  finally { loading.value = false }
}

function calcStats() {
  stats.total = total.value
  stats.read = (list.value || []).filter((e: any) => e.is_read == 1).length
  stats.unread = (list.value || []).filter((e: any) => e.is_read != 1).length
  stats.readRate = stats.total > 0 ? ((stats.read / stats.total) * 100).toFixed(1) : '0'
  const today = new Date().toISOString().slice(0, 10)
  stats.today = (list.value || []).filter((e: any) => (e.create_time || '').startsWith(today)).length
}

function resetQuery() {
  query.keyword = ''; query.event_type = ''; query.is_read = ''; query.start_date = ''; query.end_date = ''; query.page = 1
  dateRange.value = []
  loadData()
}

function onDateChange(v: any) {
  if (v && v.length === 2) { query.start_date = v[0]; query.end_date = v[1] }
  else { query.start_date = ''; query.end_date = '' }
}

function toggleAutoRefresh() {
  autoRefresh.value = !autoRefresh.value
  if (autoRefresh.value) {
    refreshTimer = setInterval(() => { query.page = 1; loadData() }, 8000)
  } else {
    clearInterval(refreshTimer)
    refreshTimer = null
  }
}

onMounted(() => loadData())
onUnmounted(() => { if (refreshTimer) clearInterval(refreshTimer) })
</script>

<style scoped>
.sse-page { max-width: 1400px; margin: 0 auto; }
.page-hero {
  display: flex; align-items: center; justify-content: space-between;
  background: linear-gradient(135deg, #0ea5e9 0%, #6366f1 100%);
  border-radius: 14px; padding: 28px 32px; margin-bottom: 20px; color: #fff; flex-wrap: wrap; gap: 20px;
}
.hero-left { display: flex; align-items: center; gap: 16px; }
.hero-icon { width: 56px; height: 56px; background: rgba(255,255,255,.2); border-radius: 14px; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(4px); }
.hero-text h2 { margin: 0 0 4px; font-size: 22px; font-weight: 700; }
.hero-text p { margin: 0; font-size: 13px; opacity: .8; }
.hero-badge { display: flex; align-items: center; gap: 8px; background: rgba(255,255,255,.15); padding: 8px 16px; border-radius: 20px; backdrop-filter: blur(4px); }
.live-dot { width: 10px; height: 10px; background: #4ade80; border-radius: 50%; animation: pulse 1.5s infinite; }
@keyframes pulse { 0%,100% { opacity:1; box-shadow: 0 0 0 0 rgba(74,222,128,.4); } 50% { box-shadow: 0 0 0 8px rgba(74,222,128,0); } }
.live-text { font-size: 13px; font-weight: 600; }

.stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 20px; }
.stat-card { background: #fff; border-radius: 12px; padding: 20px; border: 1px solid #e2e8f0; display: flex; align-items: center; gap: 16px; }
.sc-icon-wrap { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.stat-card.total .sc-icon-wrap { background: #eef0ff; color: #667eea; }
.stat-card.read .sc-icon-wrap { background: #e6fffa; color: #48bb78; }
.stat-card.unread .sc-icon-wrap { background: #fff5f5; color: #e53e3e; }
.stat-card.today .sc-icon-wrap { background: #fffaf0; color: #ed8936; }
.sc-body { display: flex; flex-direction: column; min-width: 0; }
.sc-value { font-size: 26px; font-weight: 800; color: #2d3748; line-height: 1.2; }
.sc-label { font-size: 13px; color: #a0aec0; margin-top: 2px; }
.sc-spark { margin-left: auto; flex-shrink: 0; }
.sc-trend { font-size: 11px; font-weight: 600; white-space: nowrap; }
.sc-trend.up { color: #48bb78; }
.sc-trend.idle { color: #a0aec0; }
.sc-trend.warn { color: #e53e3e; }
.sc-trend.ok { color: #48bb78; }
.sc-trend.info { color: #667eea; }

.event-type-bar { background: #fff; border-radius: 10px; padding: 14px 20px; margin-bottom: 16px; border: 1px solid #e2e8f0; display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
.type-label { font-size: 13px; font-weight: 600; color: #4a5568; white-space: nowrap; }
.type-tags { display: flex; gap: 8px; flex-wrap: wrap; }
.type-tags .el-tag { cursor: pointer; transition: all .2s; }
.type-tags .el-tag:hover { transform: translateY(-1px); box-shadow: 0 2px 8px rgba(0,0,0,.1); }
.type-count { font-size: 11px; margin-left: 2px; opacity: .7; }

.search-bar { background: #fff; border-radius: 10px; padding: 16px 20px; margin-bottom: 16px; border: 1px solid #e2e8f0; }
.table-card { border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden; }
.card-header-bar { display: flex; align-items: center; justify-content: space-between; font-weight: 600; }
.pagination-wrap { padding: 16px 0 0; display: flex; justify-content: flex-end; }

.event-title-cell { display: flex; align-items: center; gap: 8px; }
.unread-marker { width: 7px; height: 7px; border-radius: 50%; background: #667eea; flex-shrink: 0; }
.event-title-unread { font-weight: 600; color: #2d3748; }
.event-content { font-size: 13px; color: #718096; }
.user-cell { display: flex; align-items: center; gap: 6px; justify-content: center; }
.user-text { font-size: 12px; color: #4a5568; }
.time-text { font-size: 13px; color: #718096; }

.modern-table :deep(th) { background: #f8fafc !important; font-weight: 600; color: #2d3748; font-size: 13px; }
.modern-table :deep(td) { font-size: 13px; }
</style>
