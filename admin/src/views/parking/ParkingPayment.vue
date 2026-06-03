<template>
  <div class="premium-page">
    <!-- 收费大盘 -->
    <div class="hero-stats">
      <div class="hero-card hc-main">
        <div class="hc-bg"></div>
        <div class="hc-content">
          <div class="hc-row-top">
            <span class="hc-label">停车费总收入</span>
            <el-tag type="success" size="small" effect="dark" round>实时</el-tag>
          </div>
          <div class="hc-big">¥{{ fmtNum(stats.totalRevenue) }}</div>
          <div class="hc-sub">累计 {{ stats.totalCount }} 笔缴费记录</div>
        </div>
      </div>
      <div class="hero-card hc-today">
        <div class="hc-content">
          <div class="hc-label">今日收入</div>
          <div class="hc-mid">¥{{ fmtNum(stats.todayRevenue) }}</div>
          <div class="hc-sub">{{ stats.todayCount }} 笔</div>
        </div>
      </div>
      <div class="hero-card hc-month">
        <div class="hc-content">
          <div class="hc-label">本月收入</div>
          <div class="hc-mid">¥{{ fmtNum(stats.monthRevenue) }}</div>
          <div class="hc-sub">{{ stats.monthCount }} 笔</div>
        </div>
      </div>
      <div class="hero-card hc-avg">
        <div class="hc-content">
          <div class="hc-label">笔均金额</div>
          <div class="hc-mid">¥{{ fmtNum(stats.avgAmount) }}</div>
          <div class="hc-sub">日峰值 ¥{{ fmtNum(stats.peakDay) }}</div>
        </div>
      </div>
    </div>

    <!-- 支付分布 + 筛选 -->
    <div class="dual-row">
      <!-- 支付方式分布 -->
      <div class="method-panel">
        <div class="mp-title">支付方式分布</div>
        <div class="mp-bars">
          <div class="mp-item" v-for="m in payMethods" :key="m.key">
            <div class="mp-icon" :style="{ background: m.color }">
              <el-icon v-if="m.key === 1"><Iphone /></el-icon>
              <el-icon v-else-if="m.key === 2"><CreditCard /></el-icon>
              <el-icon v-else><Wallet /></el-icon>
            </div>
            <div class="mp-info">
              <div class="mp-name">{{ m.label }}</div>
              <div class="mp-track">
                <div class="mp-fill" :style="{ width: m.pct + '%', background: m.color }"></div>
              </div>
            </div>
            <div class="mp-val">¥{{ fmtNum(m.amount) }}</div>
            <div class="mp-pct">{{ m.pct }}%</div>
          </div>
        </div>
      </div>

      <!-- 筛选 -->
      <div class="filter-panel">
        <div class="fp-title">缴费记录查询</div>
        <div class="fp-fields">
          <el-input v-model="query.keyword" placeholder="车牌号/交易号/备注" clearable prefix-icon="Search" class="fp-input" @keyup.enter="loadData" />
          <el-select v-model="query.payment_type" placeholder="支付类型" clearable class="fp-sel" @change="loadData">
            <el-option label="临时停车" :value="1" />
            <el-option label="月租缴费" :value="2" />
            <el-option label="补缴" :value="3" />
          </el-select>
          <el-select v-model="query.pay_method" placeholder="支付方式" clearable class="fp-sel" @change="loadData">
            <el-option label="微信支付" :value="1" />
            <el-option label="支付宝" :value="2" />
            <el-option label="现金" :value="3" />
          </el-select>
          <el-button type="primary" @click="loadData" :icon="Search">查询</el-button>
          <el-button @click="resetQuery" :icon="RefreshRight">重置</el-button>
        </div>
      </div>
    </div>

    <!-- 缴费列表 -->
    <el-card shadow="never" class="table-panel">
      <template #header>
        <div class="panel-head">
          <span class="panel-title"><span class="dot"></span>缴费明細</span>
          <div class="panel-right">
            <span class="pr-count">共 {{ total }} 条</span>
            <el-button size="small" :icon="Download" round>导出</el-button>
          </div>
        </div>
      </template>

      <el-table :data="list" v-loading="loading" :header-cell-style="hdr" stripe>
        <el-table-column type="index" label="#" width="55" align="center" />
        <el-table-column label="车辆信息" min-width="160">
          <template #default="{ row }">
            <div class="car-cell">
              <div class="car-plate">{{ row.plate_no || row.vehicle_plate || '—' }}</div>
              <div class="car-community">{{ row.community_name || row.community_id || '—' }}</div>
            </div>
          </template>
        </el-table-column>
        <el-table-column label="交易单号" min-width="170">
          <template #default="{ row }">
            <span class="trade-no">{{ row.trade_no || '—' }}</span>
          </template>
        </el-table-column>
        <el-table-column label="缴费金额" width="120" align="right" sortable>
          <template #default="{ row }">
            <span class="amt-pay">¥{{ fmtNum(row.amount) }}</span>
          </template>
        </el-table-column>
        <el-table-column label="支付类型" width="100" align="center">
          <template #default="{ row }">
            <el-tag :type="payTypeTag(row.payment_type)" size="small" effect="light" round>{{ payTypeLabel(row.payment_type) }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column label="支付方式" width="100" align="center">
          <template #default="{ row }">
            <div class="pay-method-chip" :class="'pm-'+row.pay_method">
              {{ payMethodLabel(row.pay_method) }}
            </div>
          </template>
        </el-table-column>
        <el-table-column label="停车时长" width="110" align="center">
          <template #default="{ row }">
            <span class="duration">{{ row.duration || '—' }}</span>
          </template>
        </el-table-column>
        <el-table-column label="入场时间" width="170" sortable>
          <template #default="{ row }">{{ row.entry_time || '—' }}</template>
        </el-table-column>
        <el-table-column label="缴费时间" width="170">
          <template #default="{ row }">{{ row.create_time || '—' }}</template>
        </el-table-column>
      </el-table>

      <div class="pagi-box">
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
import { Search, RefreshRight, Download, Iphone, CreditCard, Wallet } from '@element-plus/icons-vue'

const list = ref<any[]>([])
const total = ref(0)
const loading = ref(false)
const hdr = { background: '#f8fafc', color: '#475569', fontWeight: 600, fontSize: '13px', borderBottom: '2px solid #e2e8f0' }
const query = reactive({ page: 1, limit: 15, keyword: '', payment_type: '', pay_method: '' })

const stats = ref({ totalRevenue: 0, totalCount: 0, todayRevenue: 0, todayCount: 0, monthRevenue: 0, monthCount: 0, avgAmount: 0, peakDay: 0 })

const payMethods = computed(() => {
  const raw = list.value || []
  const map: Record<number, { key: number; label: string; amount: number; count: number; color: string }> = {
    1: { key: 1, label: '微信支付', amount: 0, count: 0, color: '#22c55e' },
    2: { key: 2, label: '支付宝', amount: 0, count: 0, color: '#3b82f6' },
    3: { key: 3, label: '现金', amount: 0, count: 0, color: '#f59e0b' },
  }
  raw.forEach(r => {
    const k = parseInt(r.pay_method) || 1
    if (map[k]) { map[k].amount += parseFloat(r.amount) || 0; map[k].count++ }
  })
  const totalAmt = Object.values(map).reduce((s, m) => s + m.amount, 0) || 1
  return Object.values(map).map(m => ({ ...m, pct: Math.round(m.amount / totalAmt * 100) }))
})

function fmtNum(v: any) { const n = parseFloat(v); return isNaN(n) ? '0.00' : n.toFixed(2) }
function payTypeLabel(v: any) { const m: Record<number, string> = { 1: '临停', 2: '月租', 3: '补缴' }; return m[parseInt(v)] || '其他' }
function payTypeTag(v: any): string { const m: Record<number, string> = { 1: '', 2: 'success', 3: 'warning' }; return m[parseInt(v)] || 'info' }
function payMethodLabel(v: any) { const m: Record<number, string> = { 1: '微信', 2: '支付宝', 3: '现金' }; return m[parseInt(v)] || '其他' }

function calcStats() {
  const arr = list.value || []
  const now = new Date()
  const today = now.toISOString().slice(0, 10)
  const month = now.toISOString().slice(0, 7)
  stats.value.totalRevenue = arr.reduce((s, r) => s + (parseFloat(r.amount) || 0), 0)
  stats.value.totalCount = total.value
  stats.value.todayRevenue = arr.filter(r => (r.create_time || '').startsWith(today)).reduce((s, r) => s + (parseFloat(r.amount) || 0), 0)
  stats.value.todayCount = arr.filter(r => (r.create_time || '').startsWith(today)).length
  stats.value.monthRevenue = arr.filter(r => (r.create_time || '').startsWith(month)).reduce((s, r) => s + (parseFloat(r.amount) || 0), 0)
  stats.value.monthCount = arr.filter(r => (r.create_time || '').startsWith(month)).length
  stats.value.avgAmount = stats.value.totalCount ? stats.value.totalRevenue / stats.value.totalCount : 0
  stats.value.peakDay = arr.length ? Math.max(...arr.map(r => parseFloat(r.amount) || 0), 0) : 0
}

async function loadData() {
  loading.value = true
  try {
    const p: any = { page: query.page, limit: query.limit, keyword: query.keyword }
    if (query.payment_type !== '') p.payment_type = query.payment_type
    if (query.pay_method !== '') p.pay_method = query.pay_method
    const res = await apiGet('/admin/parking/parkingPaymentList', p)
    if (res.code === 0) { list.value = res.data?.list || []; total.value = res.data?.total || 0; calcStats() }
  } finally { loading.value = false }
}

function resetQuery() { query.keyword = ''; query.payment_type = ''; query.pay_method = ''; query.page = 1; loadData() }
onMounted(loadData)
</script>

<style scoped>
.premium-page { animation: fadeIn 0.4s; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }

/* 英雄统计 */
.hero-stats { display: grid; grid-template-columns: 2fr 1fr 1fr 1fr; gap: 16px; margin-bottom: 18px; }
.hero-card { border-radius: 16px; padding: 22px; color: #fff; position: relative; overflow: hidden; transition: transform 0.2s; }
.hero-card:hover { transform: translateY(-2px); }
.hc-main { background: linear-gradient(135deg, #1e1b4b 0%, #312e81 30%, #4338ca 70%, #6366f1 100%); }
.hc-main .hc-bg { position: absolute; top: -40px; right: -30px; width: 160px; height: 160px; border-radius: 50%; background: rgba(255,255,255,0.04); }
.hc-main .hc-bg::after { content: ''; position: absolute; top: 30px; left: 30px; width: 100px; height: 100px; border-radius: 50%; background: rgba(255,255,255,0.04); }
.hc-today { background: linear-gradient(135deg, #0c4a6e, #0369a1, #0284c7); }
.hc-month { background: linear-gradient(135deg, #14532d, #15803d, #16a34a); }
.hc-avg { background: linear-gradient(135deg, #4c1d95, #6d28d9, #7c3aed); }
.hc-content { position: relative; z-index: 1; }
.hc-row-top { display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px; }
.hc-label { font-size: 12px; opacity: 0.8; text-transform: uppercase; letter-spacing: 0.5px; }
.hc-big { font-size: 36px; font-weight: 800; line-height: 1.1; }
.hc-mid { font-size: 26px; font-weight: 800; }
.hc-sub { font-size: 12px; opacity: 0.65; margin-top: 4px; }

.dual-row { display: flex; gap: 16px; margin-bottom: 18px; }
.method-panel { flex: 1; background: #fff; border-radius: 14px; padding: 20px; border: 1px solid #f1f5f9; }
.mp-title { font-size: 13px; font-weight: 700; color: #475569; margin-bottom: 14px; }
.mp-bars { display: flex; flex-direction: column; gap: 12px; }
.mp-item { display: flex; align-items: center; gap: 10px; }
.mp-icon { width: 34px; height: 34px; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 15px; flex-shrink: 0; }
.mp-info { flex: 1; }
.mp-name { font-size: 12px; font-weight: 600; color: #334155; margin-bottom: 4px; }
.mp-track { height: 4px; border-radius: 2px; background: #e2e8f0; overflow: hidden; }
.mp-fill { height: 100%; border-radius: 2px; transition: width 0.6s; }
.mp-val { font-size: 13px; font-weight: 700; color: #1e293b; min-width: 70px; text-align: right; }
.mp-pct { font-size: 12px; color: #94a3b8; min-width: 36px; text-align: right; }

.filter-panel { flex: 1.5; background: #fff; border-radius: 14px; padding: 20px; border: 1px solid #f1f5f9; }
.fp-title { font-size: 13px; font-weight: 700; color: #475569; margin-bottom: 12px; }
.fp-fields { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; }
.fp-input { width: 200px; }
.fp-sel { width: 130px; }

.table-panel { border-radius: 12px; border: 1px solid #f1f5f9; }
.table-panel :deep(.el-card__header) { padding: 14px 20px; border-bottom: 1px solid #f1f5f9; }
.panel-head { display: flex; align-items: center; justify-content: space-between; }
.panel-title { font-size: 15px; font-weight: 700; color: #1e293b; display: flex; align-items: center; gap: 8px; }
.dot { width: 8px; height: 8px; border-radius: 50%; background: linear-gradient(135deg, #6366f1, #8b5cf6); }
.panel-right { display: flex; align-items: center; gap: 10px; }
.pr-count { font-size: 12px; color: #94a3b8; }
.car-plate { font-weight: 700; font-size: 14px; color: #1e293b; letter-spacing: 0.5px; }
.car-community { font-size: 11px; color: #94a3b8; }
.trade-no { font-size: 12px; color: #64748b; font-family: monospace; }
.amt-pay { font-weight: 700; color: #22c55e; font-size: 14px; font-family: 'Monaco', monospace; }
.pay-method-chip { display: inline-block; padding: 3px 10px; border-radius: 12px; font-size: 12px; font-weight: 600; }
.pm-1 { background: #f0fdf4; color: #16a34a; }
.pm-2 { background: #eff6ff; color: #2563eb; }
.pm-3 { background: #fff7ed; color: #ea580c; }
.duration { color: #64748b; font-size: 13px; }
.pagi-box { margin-top: 16px; display: flex; justify-content: flex-end; }

@media (max-width: 1200px) { .hero-stats { grid-template-columns: repeat(2, 1fr); } .dual-row { flex-direction: column; } }
@media (max-width: 768px) { .hero-stats { grid-template-columns: 1fr; } }
</style>
