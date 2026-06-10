<template>
  <div class="mr-page">
    <!-- Tabs -->
    <div class="mr-tabs">
      <div class="mrt-item" :class="{ active: tab === 'order' }" @click="switchTab('order')"><Icon icon="ph:clipboard-text-duotone" /> 工单</div>
      <div class="mrt-item" :class="{ active: tab === 'worker' }" @click="switchTab('worker')"><Icon icon="ph:hard-hat-duotone" /> 人员</div>
    </div>

    <!-- Search -->
    <div class="mr-search">
      <Icon icon="ph:magnifying-glass" class="mrs-icon" />
      <input v-model="query.keyword" :placeholder="tab==='order'?'工单号/报修人/电话...':'姓名/电话...'" @keyup.enter="loadData" />
      <select v-model="statusFilter" @change="loadData" v-if="tab === 'order'">
        <option value="">全部状态</option>
        <option value="1">待处理</option>
        <option value="2">已派单</option>
        <option value="3">处理中</option>
        <option value="4">已完成</option>
        <option value="6">已关闭</option>
      </select>
    </div>

    <!-- Stats for order tab -->
    <div class="mr-summary" v-if="tab === 'order'">
      <div class="mrs-card" v-for="s in orderStats" :key="s.label" :style="{ borderTopColor: s.color }">
        <span class="mrs-val" :style="{ color: s.color }">{{ s.count }}</span>
        <span class="mrs-label">{{ s.label }}</span>
      </div>
    </div>

    <!-- List -->
    <div class="mr-list">
      <!-- Order Cards -->
      <div class="mr-card" v-if="tab === 'order'" v-for="item in list" :key="item.id" @click="showDetail(item)">
        <div class="mrc-hdr">
          <span class="mrc-no">{{ item.order_no }}</span>
          <el-tag :type="statusType[item.status]||'info'" size="small">{{ statusMap[item.status]||'未知' }}</el-tag>
        </div>
        <div class="mrc-body">
          <span class="mrc-reporter"><Icon icon="ph:user" /> {{ item.reporter }} · {{ item.reporter_phone }}</span>
          <span class="mrc-room"><Icon icon="ph:house" /> {{ item.community_name }} {{ item.room_number }}</span>
          <span class="mrc-type"><Icon icon="ph:wrench" /> {{ item.type_name }} <template v-if="item.worker_name">· {{ item.worker_name }}</template></span>
        </div>
        <div class="mrc-time">{{ item.create_time }}</div>
      </div>

      <!-- Worker Cards -->
      <div class="mr-card" v-if="tab === 'worker'" v-for="item in list" :key="item.id">
        <div class="mrc-left">
          <div class="mrc-avatar" style="background:#dc262614;color:#dc2626"><Icon icon="ph:hard-hat-duotone" /></div>
          <div class="mrc-info">
            <span class="mrc-name">{{ item.name }}</span>
            <span class="mrc-sub"><Icon icon="ph:phone" /> {{ item.phone }}</span>
            <span class="mrc-sub" v-if="item.skill">{{ item.skill }}</span>
          </div>
        </div>
        <el-tag :type="item.status===1?'success':'info'" size="small">{{ item.status===1?'在职':'离职' }}</el-tag>
      </div>

      <div class="mr-loading" v-if="loading"><Icon icon="ph:spinner" class="spin" /> 加载中...</div>
      <div class="mr-empty" v-if="!loading && !list.length">
        <Icon icon="ph:wrench-duotone" />
        <span>暂无数据</span>
      </div>
    </div>

    <div class="mr-more" v-if="list.length < total" @click="loadMore">
      <Icon icon="ph:arrows-down" /> 加载更多 ({{ list.length }}/{{ total }})
    </div>

    <!-- Detail Panel -->
    <Teleport to="body">
      <Transition name="slide-up">
        <div class="mr-overlay" v-if="detail" @click.self="detail = null">
          <div class="mr-panel">
            <div class="mrp-hdl"><span class="mrp-title">工单详情</span><Icon icon="ph:x" class="mrp-close" @click="detail = null" /></div>
            <div class="mrp-body">
              <div class="mrp-row"><label>工单号</label><span>{{ detail.order_no }}</span></div>
              <div class="mrp-row"><label>状态</label><el-tag :type="statusType[detail.status]||'info'" size="small">{{ statusMap[detail.status] }}</el-tag></div>
              <div class="mrp-row"><label>报修人</label><span>{{ detail.reporter }}</span></div>
              <div class="mrp-row"><label>电话</label><a :href="'tel:'+detail.reporter_phone">{{ detail.reporter_phone }}</a></div>
              <div class="mrp-row"><label>房间</label><span>{{ detail.building_name }} {{ detail.room_number }}</span></div>
              <div class="mrp-row"><label>维修人</label><span>{{ detail.worker_name || '—' }}</span></div>
              <div class="mrp-row" v-if="detail.description"><label>描述</label><span style="text-align:right;flex:1;">{{ detail.description }}</span></div>
            </div>
            <div class="mrp-actions">
              <button v-if="detail.status==1" class="mrpa-btn assign" @click="openAssign(detail);detail=null"><Icon icon="ph:user-plus" /> 派单</button>
              <button v-if="detail.status!=6" class="mrpa-btn close" @click="openClose(detail);detail=null"><Icon icon="ph:prohibit" /> 关闭</button>
              <button class="mrpa-btn danger" @click="handleDelete(detail);detail=null"><Icon icon="ph:trash" /> 删除</button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- Assign Dialog -->
    <Teleport to="body">
      <Transition name="slide-up">
        <div class="mr-overlay" v-if="assignVisible" @click.self="assignVisible = false">
          <div class="mr-panel">
            <div class="mrp-hdl"><span class="mrp-title">派单</span><Icon icon="ph:x" class="mrp-close" @click="assignVisible = false" /></div>
            <div class="mrp-body">
              <div class="mrp-field"><label>维修人员</label><select v-model="assignWorkerId"><option value="">选择人员</option><option v-for="w in workers" :key="w.id" :value="w.id">{{ w.name }}</option></select></div>
            </div>
            <div class="mrp-actions">
              <button class="mrpa-btn cancel" @click="assignVisible = false">取消</button>
              <button class="mrpa-btn assign" @click="submitAssign" :disabled="assignLoading"><Icon icon="ph:spinner" class="spin" v-if="assignLoading" /> 确定</button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { Icon } from '@iconify/vue'
import { apiGet, apiPost } from '@/utils/request'

const tab = ref('order')
const list = ref<any[]>([])
const total = ref(0)
const loading = ref(false)
const detail = ref<any>(null)
const workers = ref<any[]>([])
const assignVisible = ref(false)
const assignLoading = ref(false)
const assignOrderId = ref(0)
const assignWorkerId = ref('')

const statusMap: Record<number, string> = { 1: '待处理', 2: '已派单', 3: '处理中', 4: '已完成', 5: '已评价', 6: '已关闭' }
const statusType: Record<number, string> = { 1: 'info', 2: 'warning', 3: 'primary', 4: 'success', 5: 'success', 6: 'danger' }
const orderStats = reactive([
  { label: '待处理', count: 0, color: '#64748b' },
  { label: '已派单', count: 0, color: '#ea580c' },
  { label: '处理中', count: 0, color: '#3b82f6' },
  { label: '已完成', count: 0, color: '#059669' },
])

const query = reactive({ keyword: '', page: 1, limit: 20 })
const statusFilter = ref('')

function switchTab(t: string) {
  tab.value = t
  query.keyword = ''
  query.page = 1
  statusFilter.value = ''
  loadData()
}

async function loadData() {
  loading.value = true
  try {
    let url = ''
    const params: any = { keyword: query.keyword, page: query.page, limit: query.limit }
    if (tab.value === 'order') {
      url = '/admin/repair/orderList'
      if (statusFilter.value) params.status = statusFilter.value
    } else {
      url = '/admin/repair/workerList'
    }
    const r = await apiGet(url, params)
    list.value = r.data?.list || r.data || []
    total.value = r.count || r.data?.total || list.value.length

    if (tab.value === 'order') {
      const stats = (r.data as any)?._summary || r._summary
      orderStats[0].count = stats?.pending ?? 0
      orderStats[1].count = stats?.assigned ?? 0
      orderStats[2].count = stats?.processing ?? 0
      orderStats[3].count = stats?.completed ?? 0
    }
  } catch { list.value = []; total.value = 0 }
  finally { loading.value = false }
}

function loadMore() { query.page++; loadData() }

async function showDetail(row: any) {
  try { const r = await apiGet('/admin/repair/orderDetail', { id: row.id }); detail.value = r.data } catch {}
}

function openAssign(row: any) { assignOrderId.value = row.id; assignWorkerId.value = ''; assignVisible.value = true }
async function submitAssign() {
  if (!assignWorkerId.value) { alert('请选择维修人员'); return }
  assignLoading.value = true
  try { await apiPost('/admin/repair/orderAssign', { id: assignOrderId.value, worker_id: assignWorkerId.value }); assignVisible.value = false; query.page = 1; loadData() } catch {} finally { assignLoading.value = false }
}

async function openClose(row: any) {
  if (!confirm('确定关闭该工单吗？')) return
  try { await apiPost('/admin/repair/orderClose', { id: row.id }); loadData() } catch {}
}

async function handleDelete(row: any) {
  if (!confirm(`确定删除工单"${row.order_no}"吗？`)) return
  try { await apiPost('/admin/repair/orderDelete', { id: row.id }); loadData() } catch {}
}

onMounted(async () => {
  try { const r = await apiGet('/admin/repair/workerList', { limit: 999 }); workers.value = r.data?.list || r.data || [] } catch {}
  loadData()
})
</script>

<style scoped>
.mr-page { min-height: 100vh; background: #f0f2f5; }
.mr-tabs { display: flex; background: #fff; border-radius: 12px; padding: 4px; margin-bottom: 12px; gap: 4px; }
.mrt-item { flex: 1; display: flex; align-items: center; justify-content: center; gap: 4px; padding: 10px 0; border-radius: 10px; font-size: 13px; font-weight: 500; color: #64748b; cursor: pointer; transition: all .2s; }
.mrt-item.active { background: linear-gradient(135deg, #dc2626, #ef4444); color: #fff; box-shadow: 0 2px 8px rgba(220,38,38,.3); }

.mr-search { display: flex; align-items: center; gap: 8px; background: #fff; border-radius: 12px; padding: 0 12px; height: 42px; margin-bottom: 12px; border: 1.5px solid transparent; transition: border .2s; }
.mr-search:focus-within { border-color: #dc2626; }
.mrs-icon { font-size: 16px; color: #94a3b8; flex-shrink: 0; }
.mr-search input { flex: 1; border: none; outline: none; font-size: 13px; color: #0f172a; background: transparent; height: 42px; }
.mr-search input::placeholder { color: #94a3b8; }
.mr-search select { border: none; background: #f1f5f9; border-radius: 8px; padding: 4px 8px; font-size: 12px; color: #334155; outline: none; max-width: 90px; }

.mr-summary { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; margin-bottom: 12px; }
.mrs-card { background: #fff; border-radius: 12px; padding: 10px 8px; text-align: center; border-top: 3px solid transparent; }
.mrs-val { font-size: 18px; font-weight: 700; display: block; }
.mrs-label { font-size: 10px; color: #94a3b8; }

.mr-list { display: flex; flex-direction: column; gap: 8px; }
.mr-card { background: #fff; border-radius: 14px; padding: 14px; cursor: pointer; border: 1px solid rgba(0,0,0,.03); }
.mr-card:active { transform: scale(.98); }
.mrc-hdr { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; }
.mrc-no { font-size: 13px; font-weight: 600; color: #0f172a; }
.mrc-body { display: flex; flex-direction: column; gap: 4px; }
.mrc-body span { font-size: 12px; color: #64748b; display: flex; align-items: center; gap: 4px; }
.mrc-time { font-size: 10px; color: #94a3b8; margin-top: 8px; text-align: right; }
.mrc-left { display: flex; align-items: center; gap: 12px; }
.mrc-avatar { width: 42px; height: 42px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0; }
.mrc-info { display: flex; flex-direction: column; gap: 2px; }
.mrc-name { font-size: 14px; font-weight: 600; color: #0f172a; }
.mrc-sub { font-size: 12px; color: #64748b; display: flex; align-items: center; gap: 4px; }

.mr-loading, .mr-empty { display: flex; align-items: center; justify-content: center; gap: 8px; padding: 40px 0; color: #94a3b8; font-size: 13px; }
.mr-empty { flex-direction: column; font-size: 30px; }
.mr-empty span { font-size: 13px; }
.mr-more { display: flex; align-items: center; justify-content: center; gap: 6px; padding: 16px; color: #dc2626; font-size: 13px; cursor: pointer; }

.mr-overlay { position: fixed; inset: 0; background: rgba(0,0,0,.4); z-index: 9999; display: flex; align-items: flex-end; }
.mr-panel { background: #fff; border-radius: 20px 20px 0 0; width: 100%; max-height: 80vh; overflow-y: auto; padding-bottom: env(safe-area-inset-bottom); }
.mrp-hdl { display: flex; align-items: center; justify-content: space-between; padding: 16px 20px; border-bottom: 1px solid #f1f5f9; }
.mrp-title { font-size: 16px; font-weight: 700; color: #0f172a; }
.mrp-close { font-size: 20px; color: #94a3b8; cursor: pointer; padding: 4px; }
.mrp-body { padding: 16px 20px; }
.mrp-row { display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid #f8fafc; }
.mrp-row label { font-size: 13px; color: #94a3b8; flex-shrink: 0; }
.mrp-row span, .mrp-row a { font-size: 14px; color: #0f172a; font-weight: 500; text-decoration: none; }
.mrp-field { margin-bottom: 12px; }
.mrp-field label { display: block; font-size: 12px; color: #64748b; margin-bottom: 6px; }
.mrp-field select { width: 100%; padding: 10px 12px; border: 1.5px solid #e2e8f0; border-radius: 10px; font-size: 14px; outline: none; background: #fff; box-sizing: border-box; }
.mrp-actions { display: flex; gap: 10px; padding: 16px 20px; border-top: 1px solid #f1f5f9; }
.mrpa-btn { flex: 1; padding: 12px; border-radius: 12px; border: none; font-size: 14px; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 6px; }
.mrpa-btn.assign { background: #ea580c; color: #fff; }
.mrpa-btn.close { background: #f1f5f9; color: #64748b; }
.mrpa-btn.danger { background: #fef2f2; color: #dc2626; }
.mrpa-btn.cancel { background: #f1f5f9; color: #64748b; }

.slide-up-enter-active { transition: all .25s ease-out; }
.slide-up-leave-active { transition: all .2s ease-in; }
.slide-up-enter-from .mr-panel { transform: translateY(100%); }
.slide-up-leave-to .mr-panel { transform: translateY(100%); }
.spin { animation: spin 1s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }
</style>
