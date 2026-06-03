<template>
  <div class="premium-page">
    <!-- 催缴概览 -->
    <div class="kpi-row">
      <div class="kpi-card kpi-1">
        <div class="kpi-ring"></div>
        <div class="kpi-value">{{ stats.totalRecords }}</div>
        <div class="kpi-title">催缴总次数</div>
        <div class="kpi-icon"><el-icon><BellFilled /></el-icon></div>
      </div>
      <div class="kpi-card kpi-2">
        <div class="kpi-ring"></div>
        <div class="kpi-value">¥{{ fmtNum(stats.totalArrears) }}</div>
        <div class="kpi-title">催缴累计欠费</div>
        <div class="kpi-icon"><el-icon><Coin /></el-icon></div>
      </div>
      <div class="kpi-card kpi-3">
        <div class="kpi-ring"></div>
        <div class="kpi-value">{{ stats.totalPaid }}笔</div>
        <div class="kpi-title">已缴清账单</div>
        <div class="kpi-icon"><el-icon><CircleCheckFilled /></el-icon></div>
      </div>
      <div class="kpi-card kpi-4">
        <div class="kpi-ring"></div>
        <div class="kpi-value">{{ stats.recoveryRate }}%</div>
        <div class="kpi-title">催缴回收率</div>
        <div class="kpi-icon"><el-icon><TrendCharts /></el-icon></div>
      </div>
    </div>

    <!-- 渠道分布 + 搜索 -->
    <div class="mid-row">
      <div class="channel-bar">
        <div class="channel-section-title">催缴渠道分布</div>
        <div class="channel-items">
          <div class="channel-item" v-for="ch in channelStats" :key="ch.key" @click="query.channel = ch.key === query.channel ? '' : ch.key; loadData()">
            <div class="ch-bar-fill" :style="{ width: ch.pct + '%', background: ch.color }"></div>
            <div class="ch-content">
              <div class="ch-icon-wrap" :style="{ background: ch.color }">
                <el-icon v-if="ch.key==='sms'"><Iphone /></el-icon>
                <el-icon v-else-if="ch.key==='wechat'"><ChatDotRound /></el-icon>
                <el-icon v-else><User /></el-icon>
              </div>
              <div class="ch-info">
                <div class="ch-label">{{ ch.label }}</div>
                <div class="ch-count">{{ ch.count }} 次</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="filter-card">
        <div class="filter-inner">
          <el-input v-model="query.keyword" placeholder="搜索房间号/业主/楼栋" clearable prefix-icon="Search" class="f-input" @keyup.enter="loadData" />
          <el-select v-model="query.channel" placeholder="全部渠道" clearable class="f-select" @change="loadData">
            <el-option label="手动催缴" value="manual" />
            <el-option label="短信催缴" value="sms" />
            <el-option label="公众号催缴" value="wechat" />
          </el-select>
          <el-button type="primary" @click="loadData" :icon="Search">查询</el-button>
          <el-button @click="resetQuery" :icon="RefreshRight">重置</el-button>
        </div>
      </div>
    </div>

    <!-- 催缴记录表格 -->
    <el-card shadow="never" class="table-panel">
      <template #header>
        <div class="panel-head">
          <span class="panel-title"><span class="dot"></span>催缴记录明细</span>
          <span class="panel-badge">共 {{ total }} 条</span>
        </div>
      </template>

      <el-table :data="list" v-loading="loading" :header-cell-style="headerStyle" row-class-name="dun-row">
        <el-table-column type="index" label="#" width="55" align="center" />
        <el-table-column label="房间信息" min-width="160">
          <template #default="{ row }">
            <div class="room-cell">
              <div class="room-icon"><el-icon><OfficeBuilding /></el-icon></div>
              <div>
                <div class="room-num">{{ row.room_number || '—' }}</div>
                <div class="room-bld">{{ row.building_name || '—' }}</div>
              </div>
            </div>
          </template>
        </el-table-column>
        <el-table-column label="业主" width="130">
          <template #default="{ row }">
            <div>
              <div class="owner-name">{{ row.owner_name || '—' }}</div>
              <div class="owner-phone">{{ row.owner_phone || '—' }}</div>
            </div>
          </template>
        </el-table-column>
        <el-table-column label="应收总额" width="120" align="right" sortable="custom">
          <template #default="{ row }">
            <span class="amount-normal">¥{{ fmtNum(row.total_amount) }}</span>
          </template>
        </el-table-column>
        <el-table-column label="已付" width="110" align="right">
          <template #default="{ row }">
            <span class="amount-paid">¥{{ fmtNum(row.paid_amount) }}</span>
          </template>
        </el-table-column>
        <el-table-column label="欠费金额" width="130" align="right">
          <template #default="{ row }">
            <span class="amount-due">¥{{ fmtNum(row.arrears_amount) }}</span>
          </template>
        </el-table-column>
        <el-table-column label="账单数" width="85" align="center">
          <template #default="{ row }">
            <el-tag type="warning" size="small" effect="light" round>{{ row.bill_count || 0 }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column label="催缴渠道" width="110" align="center">
          <template #default="{ row }">
            <div class="channel-chip" :class="'ch-'+row.channel">
              <span class="ch-dot"></span>{{ channelLabel(row.channel) }}
            </div>
          </template>
        </el-table-column>
        <el-table-column prop="remark" label="备注" min-width="140" show-overflow-tooltip>
          <template #default="{ row }">
            <span class="remark-txt">{{ row.remark || '—' }}</span>
          </template>
        </el-table-column>
        <el-table-column label="操作人/时间" width="160">
          <template #default="{ row }">
            <div class="op-cell">
              <span>{{ row.admin_name || '—' }}</span>
              <span class="op-time">{{ row.create_time }}</span>
            </div>
          </template>
        </el-table-column>
      </el-table>

      <div class="pagi-end">
        <el-pagination v-model:current-page="query.page" v-model:page-size="query.limit" :total="total"
          :page-sizes="[15, 30, 50]" layout="total, sizes, prev, pager, next, jumper" background
          @current-change="loadData" @size-change="loadData" />
      </div>
    </el-card>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, computed } from 'vue'
import { apiGet } from '@/utils/request'
import { BellFilled, Coin, CircleCheckFilled, TrendCharts, Search, RefreshRight, Iphone, ChatDotRound, User, OfficeBuilding } from '@element-plus/icons-vue'

const list = ref<any[]>([])
const total = ref(0)
const loading = ref(false)
const query = reactive({ page: 1, limit: 15, keyword: '', channel: '' })

const stats = ref({ totalRecords: 0, totalArrears: 0, totalPaid: 0, recoveryRate: 0 })
const headerStyle = { background: '#f8fafc', color: '#475569', fontWeight: 600, fontSize: '13px', borderBottom: '2px solid #e2e8f0' }

const channelStats = computed(() => {
  const raw = list.value
  const totalCount = raw.length || 1
  const map: Record<string, number> = { manual: 0, sms: 0, wechat: 0 }
  raw.forEach(r => { if (map[r.channel] !== undefined) map[r.channel]++ })
  return [
    { key: 'manual', label: '人工催缴', count: map.manual, pct: Math.round(map.manual / totalCount * 100), color: 'linear-gradient(135deg, #6366f1, #8b5cf6)' },
    { key: 'sms', label: '短信催缴', count: map.sms, pct: Math.round(map.sms / totalCount * 100), color: 'linear-gradient(135deg, #f59e0b, #f97316)' },
    { key: 'wechat', label: '公众号催缴', count: map.wechat, pct: Math.round(map.wechat / totalCount * 100), color: 'linear-gradient(135deg, #22c55e, #10b981)' },
  ]
})

function channelLabel(ch: string) {
  const m: Record<string, string> = { manual: '人工', sms: '短信', wechat: '公众号' }
  return m[ch] || '人工'
}

function fmtNum(v: any) {
  const n = parseFloat(v)
  if (isNaN(n)) return '0'
  return n.toFixed(2)
}

function calcStats() {
  stats.value.totalRecords = total.value
  const arr = list.value || []
  stats.value.totalArrears = arr.reduce((s, r) => s + (parseFloat(r.arrears_amount) || 0), 0)
  stats.value.totalPaid = arr.filter(r => parseFloat(r.paid_amount) >= parseFloat(r.total_amount)).length
  stats.value.recoveryRate = stats.value.totalRecords ? Math.round(stats.value.totalPaid / stats.value.totalRecords * 100) : 0
}

async function loadData() {
  loading.value = true
  try {
    const res = await apiGet('/admin/charge/dunningList', {
      page: query.page, limit: query.limit, keyword: query.keyword, channel: query.channel
    })
    if (res.code === 0) { list.value = res.data?.list || []; total.value = res.data?.total || 0; calcStats() }
  } finally { loading.value = false }
}

function resetQuery() { query.keyword = ''; query.channel = ''; query.page = 1; loadData() }
onMounted(loadData)
</script>

<style scoped>
.premium-page { animation: fadeSlideIn 0.4s ease; }
@keyframes fadeSlideIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

.kpi-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 18px; }
.kpi-card { background: #fff; border-radius: 14px; padding: 22px 24px; position: relative; overflow: hidden; border: 1px solid #f1f5f9; transition: all 0.25s; }
.kpi-card:hover { transform: translateY(-3px); box-shadow: 0 10px 30px rgba(0,0,0,0.08); }
.kpi-card .kpi-ring { position: absolute; top: -30px; right: -30px; width: 90px; height: 90px; border-radius: 50%; border: 3px solid rgba(99,102,241,0.1); }
.kpi-card .kpi-icon { position: absolute; top: 16px; right: 18px; font-size: 22px; opacity: 0.15; }
.kpi-1 .kpi-icon, .kpi-1 .kpi-ring { color: #6366f1; border-color: rgba(99,102,241,0.1); }
.kpi-2 .kpi-icon, .kpi-2 .kpi-ring { color: #f59e0b; border-color: rgba(245,158,11,0.1); }
.kpi-3 .kpi-icon, .kpi-3 .kpi-ring { color: #22c55e; border-color: rgba(34,197,94,0.1); }
.kpi-4 .kpi-icon, .kpi-4 .kpi-ring { color: #ec4899; border-color: rgba(236,72,153,0.1); }
.kpi-value { font-size: 28px; font-weight: 800; color: #1e293b; margin-bottom: 4px; }
.kpi-title { font-size: 13px; color: #94a3b8; font-weight: 500; }

.mid-row { display: flex; gap: 16px; margin-bottom: 18px; }
.channel-bar { flex: 1; background: #fff; border-radius: 14px; padding: 20px; border: 1px solid #f1f5f9; }
.channel-section-title { font-size: 13px; font-weight: 700; color: #475569; margin-bottom: 14px; }
.channel-items { display: flex; flex-direction: column; gap: 10px; }
.channel-item { position: relative; border-radius: 10px; overflow: hidden; cursor: pointer; transition: all 0.2s; border: 1px solid transparent; }
.channel-item:hover { border-color: #cbd5e1; box-shadow: 0 2px 8px rgba(0,0,0,0.04); }
.ch-bar-fill { position: absolute; top: 0; left: 0; height: 100%; opacity: 0.08; border-radius: 10px; transition: width 0.6s ease; }
.ch-content { position: relative; z-index: 1; display: flex; align-items: center; gap: 12px; padding: 10px 14px; }
.ch-icon-wrap { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 16px; }
.ch-info { flex: 1; }
.ch-label { font-size: 13px; font-weight: 600; color: #334155; }
.ch-count { font-size: 11px; color: #94a3b8; }

.filter-card { flex: 1.8; background: #fff; border-radius: 14px; padding: 20px; border: 1px solid #f1f5f9; display: flex; align-items: center; }
.filter-inner { display: flex; gap: 10px; align-items: center; width: 100%; flex-wrap: wrap; }
.f-input { width: 220px; }
.f-select { width: 150px; }

.table-panel { border-radius: 12px; border: 1px solid #f1f5f9; overflow: hidden; }
.table-panel :deep(.el-card__header) { padding: 14px 20px; border-bottom: 1px solid #f1f5f9; }
.panel-head { display: flex; align-items: center; justify-content: space-between; }
.panel-title { font-size: 15px; font-weight: 700; color: #1e293b; display: flex; align-items: center; gap: 8px; }
.dot { width: 8px; height: 8px; border-radius: 50%; background: linear-gradient(135deg, #6366f1, #8b5cf6); }
.panel-badge { font-size: 12px; color: #94a3b8; background: #f1f5f9; padding: 3px 10px; border-radius: 20px; }

.room-cell { display: flex; align-items: center; gap: 8px; }
.room-icon { width: 32px; height: 32px; border-radius: 8px; background: #f1f5f9; display: flex; align-items: center; justify-content: center; color: #6366f1; font-size: 14px; }
.room-num { font-weight: 600; font-size: 13px; color: #1e293b; }
.room-bld { font-size: 11px; color: #94a3b8; }
.owner-name { font-weight: 600; font-size: 13px; color: #1e293b; }
.owner-phone { font-size: 11px; color: #94a3b8; }
.amount-normal { font-weight: 600; color: #64748b; font-family: 'DIN', 'Monaco', monospace; }
.amount-paid { font-weight: 600; color: #22c55e; font-family: 'DIN', 'Monaco', monospace; }
.amount-due { font-weight: 700; color: #ef4444; font-family: 'DIN', 'Monaco', monospace; font-size: 14px; }

.channel-chip { display: inline-flex; align-items: center; gap: 6px; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
.ch-dot { width: 6px; height: 6px; border-radius: 50%; }
.ch-manual { background: #eef2ff; color: #6366f1; } .ch-manual .ch-dot { background: #6366f1; }
.ch-sms { background: #fff7ed; color: #f59e0b; } .ch-sms .ch-dot { background: #f59e0b; }
.ch-wechat { background: #f0fdf4; color: #22c55e; } .ch-wechat .ch-dot { background: #22c55e; }
.remark-txt { color: #64748b; font-size: 13px; }
.op-cell { line-height: 1.4; }
.op-cell span { display: block; font-size: 12px; color: #475569; }
.op-time { color: #94a3b8 !important; font-size: 11px !important; }
.pagi-end { margin-top: 16px; display: flex; justify-content: flex-end; }

@media (max-width: 1200px) { .kpi-row { grid-template-columns: repeat(2, 1fr); } .mid-row { flex-direction: column; } }
@media (max-width: 768px) { .kpi-row { grid-template-columns: 1fr; } }
</style>
