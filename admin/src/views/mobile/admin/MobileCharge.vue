<template>
  <div class="mc-page">
    <!-- Tabs -->
    <div class="mc-tabs">
      <div class="mct-item" :class="{ active: tab === 'bill' }" @click="switchTab('bill')"><Icon icon="ph:file-text-duotone" /> 账单</div>
      <div class="mct-item" :class="{ active: tab === 'payment' }" @click="switchTab('payment')"><Icon icon="ph:credit-card-duotone" /> 缴费</div>
      <div class="mct-item" :class="{ active: tab === 'item' }" @click="switchTab('item')"><Icon icon="ph:list-numbers-duotone" /> 项目</div>
    </div>

    <!-- Search -->
    <div class="mc-search">
      <Icon icon="ph:magnifying-glass" class="mcs-icon" />
      <input v-model="query.keyword" :placeholder="searchPlaceholder" @keyup.enter="loadData" />
      <select v-model="query.community_id" @change="loadData" v-if="tab !== 'item'">
        <option value="">全部小区</option>
        <option v-for="c in communities" :key="c.id" :value="c.id">{{ c.name }}</option>
      </select>
      <select v-model="query.status" @change="loadData" v-if="tab === 'bill'">
        <option value="">全部状态</option>
        <option value="1">待缴</option>
        <option value="2">部分缴纳</option>
        <option value="3">已缴</option>
      </select>
    </div>

    <!-- Summary cards for bill tab -->
    <div class="mc-summary" v-if="tab === 'bill'">
      <div class="mcs-card mcs-warn"><span class="mcs-val">{{ summary.pending }}</span><span class="mcs-label">待缴</span></div>
      <div class="mcs-card mcs-info"><span class="mcs-val">{{ summary.partial }}</span><span class="mcs-label">部分</span></div>
      <div class="mcs-card mcs-ok"><span class="mcs-val">{{ summary.paid }}</span><span class="mcs-label">已缴</span></div>
      <div class="mcs-card mcs-total"><span class="mcs-val">¥{{ summary.amount }}</span><span class="mcs-label">总金额</span></div>
    </div>

    <!-- List -->
    <div class="mc-list">
      <!-- Bill Cards -->
      <div class="mc-card" v-if="tab === 'bill'" v-for="item in list" :key="item.id" @click="showDetail(item)">
        <div class="mcc-left">
          <div class="mcc-icon" :style="{ background: statusBg[item.status], color: statusColor[item.status] }">
            <Icon :icon="statusIcon[item.status]" />
          </div>
          <div class="mcc-info">
            <span class="mcc-name">{{ item.owner_name || '无业主' }}</span>
            <span class="mcc-sub">{{ item.room_number }} · {{ item.charge_item_name }}</span>
            <span class="mcc-no">{{ item.bill_no }}</span>
          </div>
        </div>
        <div class="mcc-right">
          <span class="mcc-amount" :style="{ color: item.status===3?'#059669':'#ea580c' }">¥{{ item.total_amount }}</span>
          <el-tag :type="item.status===3?'success':item.status===2?'warning':'info'" size="small">{{ statusMap[item.status] }}</el-tag>
        </div>
      </div>

      <!-- Payment Cards -->
      <div class="mc-card" v-if="tab === 'payment'" v-for="item in list" :key="item.id">
        <div class="mcc-left">
          <div class="mcc-icon" style="background:#8b5cf614;color:#8b5cf6"><Icon icon="ph:credit-card-duotone" /></div>
          <div class="mcc-info">
            <span class="mcc-name">{{ item.owner_name || '—' }}</span>
            <span class="mcc-sub">{{ item.room_number }} · {{ item.pay_method }}</span>
            <span class="mcc-no">{{ item.payment_no }}</span>
          </div>
        </div>
        <div class="mcc-right">
          <span class="mcc-amount" style="color:#059669">¥{{ item.amount }}</span>
          <span class="mcc-time">{{ item.pay_time?.slice(0,10) }}</span>
        </div>
      </div>

      <!-- Charge Item Cards -->
      <div class="mc-card" v-if="tab === 'item'" v-for="item in list" :key="item.id">
        <div class="mcc-left">
          <div class="mcc-icon" :style="{ background: item.type===1?'#ea580c14':'#0891b214', color: item.type===1?'#ea580c':'#0891b2' }">
            <Icon :icon="item.type===1?'ph:drop-duotone':'ph:house-duotone'" />
          </div>
          <div class="mcc-info">
            <span class="mcc-name">{{ item.name }}</span>
            <span class="mcc-sub">{{ item.type===1?'抄表类':'固定类' }} · {{ item.community_name || '全局' }}</span>
          </div>
        </div>
        <div class="mcc-right">
          <span class="mcc-amount">¥{{ item.unit_price }}/{{ item.unit || '户' }}</span>
        </div>
      </div>

      <div class="mc-loading" v-if="loading"><Icon icon="ph:spinner" class="spin" /> 加载中...</div>
      <div class="mc-empty" v-if="!loading && !list.length">
        <Icon icon="ph:currency-circle-dollar-duotone" />
        <span>暂无数据</span>
      </div>
    </div>

    <div class="mc-more" v-if="list.length < total" @click="loadMore">
      <Icon icon="ph:arrows-down" /> 加载更多 ({{ list.length }}/{{ total }})
    </div>

    <!-- Bill Detail -->
    <Teleport to="body">
      <Transition name="slide-up">
        <div class="mc-overlay" v-if="detail" @click.self="detail = null">
          <div class="mc-panel">
            <div class="mcp-hdl"><span class="mcp-title">{{ tab==='bill'?'账单详情':'缴费详情' }}</span><Icon icon="ph:x" class="mcp-close" @click="detail = null" /></div>
            <div class="mcp-body">
              <div class="mcp-row"><label>账单号</label><span>{{ detail.bill_no || detail.payment_no }}</span></div>
              <div class="mcp-row"><label>业主</label><span>{{ detail.owner_name }}</span></div>
              <div class="mcp-row"><label>房间</label><span>{{ detail.room_number }}</span></div>
              <div class="mcp-row" v-if="detail.charge_item_name"><label>收费项目</label><span>{{ detail.charge_item_name }}</span></div>
              <div class="mcp-row" v-if="detail.bill_period"><label>账期</label><span>{{ detail.bill_period }}</span></div>
              <div class="mcp-row" v-if="detail.total_amount"><label>总金额</label><span class="mcp-amount">¥{{ detail.total_amount }}</span></div>
              <div class="mcp-row" v-if="detail.paid_amount !== undefined"><label>已缴</label><span style="color:#059669">¥{{ detail.paid_amount }}</span></div>
              <div class="mcp-row" v-if="detail.status"><label>状态</label><el-tag :type="detail.status===3?'success':detail.status===2?'warning':'info'" size="small">{{ statusMap[detail.status] }}</el-tag></div>
              <div class="mcp-row" v-if="detail.due_date"><label>到期日</label><span>{{ detail.due_date }}</span></div>
              <div class="mcp-row" v-if="detail.amount"><label>金额</label><span style="color:#059669;font-weight:700">¥{{ detail.amount }}</span></div>
              <div class="mcp-row" v-if="detail.pay_method"><label>支付方式</label><span>{{ detail.pay_method }}</span></div>
              <div class="mcp-row" v-if="detail.pay_time"><label>支付时间</label><span>{{ detail.pay_time }}</span></div>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import { Icon } from '@iconify/vue'
import { apiGet, apiPost } from '@/utils/request'

const tab = ref('bill')
const list = ref<any[]>([])
const total = ref(0)
const loading = ref(false)
const communities = ref<any[]>([])
const detail = ref<any>(null)

const statusMap: Record<number, string> = { 1: '待缴', 2: '部分缴纳', 3: '已缴' }
const statusBg: Record<number, string> = { 1: '#fef3c714', 2: '#fef3c714', 3: '#ecfdf514' }
const statusColor: Record<number, string> = { 1: '#ea580c', 2: '#ea580c', 3: '#059669' }
const statusIcon: Record<number, string> = { 1: 'ph:warning-circle-duotone', 2: 'ph:hourglass-duotone', 3: 'ph:check-circle-duotone' }

const query = reactive({ keyword: '', community_id: '' as any, status: '' as any, page: 1, limit: 20 })
const searchPlaceholder = computed(() => tab.value === 'bill' ? '账单号/房间/姓名...' : tab.value === 'payment' ? '流水号/姓名...' : '项目名称...')
const summary = reactive({ pending: 0, partial: 0, paid: 0, amount: '0' })

function switchTab(t: string) {
  tab.value = t
  query.keyword = ''
  query.community_id = ''
  query.status = ''
  query.page = 1
  loadData()
}

async function loadData() {
  loading.value = true
  try {
    let url = ''
    if (tab.value === 'bill') url = '/admin/charge/billList'
    else if (tab.value === 'payment') url = '/admin/charge/paymentList'
    else url = '/admin/charge/itemList'
    const params: any = { keyword: query.keyword, page: query.page, limit: query.limit }
    if (query.community_id) params.community_id = query.community_id
    if (tab.value === 'bill' && query.status) params.status = query.status
    const r = await apiGet(url, params)
    list.value = r.data?.list || r.data || []
    total.value = r.count || r.data?.total || list.value.length

    if (tab.value === 'bill') {
      const stats = (r.data as any)?._summary || r._summary
      summary.pending = stats?.pending ?? 0
      summary.partial = stats?.partial ?? 0
      summary.paid = stats?.paid ?? 0
      summary.amount = stats?.total_amount ?? '0'
    }
  } catch { list.value = []; total.value = 0 }
  finally { loading.value = false }
}

function loadMore() { query.page++; loadData() }

async function showDetail(row: any) {
  try {
    const url = tab.value === 'bill' ? '/admin/charge/billDetail' : '/admin/charge/paymentDetail'
    const r = await apiGet(url, { id: row.id })
    detail.value = r.data
  } catch {}
}

onMounted(async () => {
  try { const r = await apiGet('/admin/community/list', { limit: 999 }); communities.value = r.data?.list || r.data || [] } catch {}
  loadData()
})
</script>

<style scoped>
.mc-page { min-height: 100vh; background: #f0f2f5; }
.mc-tabs { display: flex; background: #fff; border-radius: 12px; padding: 4px; margin-bottom: 12px; gap: 4px; }
.mct-item { flex: 1; display: flex; align-items: center; justify-content: center; gap: 4px; padding: 10px 0; border-radius: 10px; font-size: 13px; font-weight: 500; color: #64748b; cursor: pointer; transition: all .2s; }
.mct-item.active { background: linear-gradient(135deg, #ea580c, #f97316); color: #fff; box-shadow: 0 2px 8px rgba(234,88,12,.3); }

.mc-search { display: flex; align-items: center; gap: 8px; background: #fff; border-radius: 12px; padding: 0 12px; height: 42px; margin-bottom: 12px; border: 1.5px solid transparent; transition: border .2s; }
.mc-search:focus-within { border-color: #ea580c; }
.mcs-icon { font-size: 16px; color: #94a3b8; flex-shrink: 0; }
.mc-search input { flex: 1; border: none; outline: none; font-size: 13px; color: #0f172a; background: transparent; height: 42px; }
.mc-search input::placeholder { color: #94a3b8; }
.mc-search select { border: none; background: #f1f5f9; border-radius: 8px; padding: 4px 8px; font-size: 12px; color: #334155; outline: none; max-width: 90px; }

.mc-summary { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; margin-bottom: 12px; }
.mcs-card { background: #fff; border-radius: 12px; padding: 10px 8px; text-align: center; }
.mcs-val { font-size: 16px; font-weight: 700; display: block; }
.mcs-label { font-size: 10px; color: #94a3b8; }
.mcs-warn .mcs-val { color: #ea580c; }
.mcs-info .mcs-val { color: #8b5cf6; }
.mcs-ok .mcs-val { color: #059669; }
.mcs-total .mcs-val { color: #0f172a; font-size: 14px; }

.mc-list { display: flex; flex-direction: column; gap: 8px; }
.mc-card { background: #fff; border-radius: 14px; padding: 14px; display: flex; align-items: center; justify-content: space-between; cursor: pointer; border: 1px solid rgba(0,0,0,.03); }
.mc-card:active { transform: scale(.98); }
.mcc-left { display: flex; align-items: center; gap: 12px; flex: 1; min-width: 0; }
.mcc-icon { width: 42px; height: 42px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0; }
.mcc-info { display: flex; flex-direction: column; gap: 2px; min-width: 0; }
.mcc-name { font-size: 14px; font-weight: 600; color: #0f172a; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.mcc-sub { font-size: 12px; color: #64748b; }
.mcc-no { font-size: 10px; color: #94a3b8; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.mcc-right { display: flex; flex-direction: column; align-items: flex-end; gap: 4px; flex-shrink: 0; }
.mcc-amount { font-size: 16px; font-weight: 700; }
.mcc-time { font-size: 10px; color: #94a3b8; }

.mc-loading, .mc-empty { display: flex; align-items: center; justify-content: center; gap: 8px; padding: 40px 0; color: #94a3b8; font-size: 13px; }
.mc-empty { flex-direction: column; font-size: 30px; }
.mc-empty span { font-size: 13px; }
.mc-more { display: flex; align-items: center; justify-content: center; gap: 6px; padding: 16px; color: #ea580c; font-size: 13px; cursor: pointer; }

.mc-overlay { position: fixed; inset: 0; background: rgba(0,0,0,.4); z-index: 9999; display: flex; align-items: flex-end; }
.mc-panel { background: #fff; border-radius: 20px 20px 0 0; width: 100%; max-height: 80vh; overflow-y: auto; padding-bottom: env(safe-area-inset-bottom); }
.mcp-hdl { display: flex; align-items: center; justify-content: space-between; padding: 16px 20px; border-bottom: 1px solid #f1f5f9; }
.mcp-title { font-size: 16px; font-weight: 700; color: #0f172a; }
.mcp-close { font-size: 20px; color: #94a3b8; cursor: pointer; padding: 4px; }
.mcp-body { padding: 16px 20px; }
.mcp-row { display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid #f8fafc; }
.mcp-row label { font-size: 13px; color: #94a3b8; }
.mcp-row span { font-size: 14px; color: #0f172a; font-weight: 500; }
.mcp-amount { color: #ea580c; font-weight: 700; font-size: 16px; }

.slide-up-enter-active { transition: all .25s ease-out; }
.slide-up-leave-active { transition: all .2s ease-in; }
.slide-up-enter-from .mc-panel { transform: translateY(100%); }
.slide-up-leave-to .mc-panel { transform: translateY(100%); }
.spin { animation: spin 1s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }
</style>
