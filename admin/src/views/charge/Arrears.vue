<template>
  <div class="page-container">
    <div class="search-bar">
      <el-form :model="query" inline>
        <el-form-item>
          <el-input v-model="query.keyword" placeholder="业主姓名/手机/房间号" clearable style="width:220px;" />
        </el-form-item>
        <el-form-item>
          <el-select v-model="query.community_id" placeholder="选择小区" clearable style="width:160px;">
            <el-option v-for="c in communities" :key="c.id" :label="c.name" :value="c.id" />
          </el-select>
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="loadData">搜索</el-button>
          <el-button @click="resetQuery">重置</el-button>
        </el-form-item>
      </el-form>
      <!-- 汇总统计 -->
      <div class="stats-row">
        <div class="stat-item"><span class="stat-label">欠费业主</span><span class="stat-value danger">{{ stats.ownerCount }}</span></div>
        <div class="stat-item"><span class="stat-label">欠费总额</span><span class="stat-value danger">¥{{ stats.totalArrears }}</span></div>
        <div class="stat-item"><span class="stat-label">欠费账单</span><span class="stat-value warning">{{ stats.billCount }}</span></div>
      </div>
    </div>

    <el-card shadow="never" class="table-card">
      <el-table :data="list" v-loading="loading" stripe border>
        <el-table-column type="index" label="#" width="50" />
        <el-table-column prop="owner_name" label="业主姓名" width="100" />
        <el-table-column prop="owner_phone" label="电话" width="130" />
        <el-table-column prop="community_name" label="小区" width="100" />
        <el-table-column label="欠费房产" min-width="180">
          <template #default="{ row }">
            <el-tag v-for="(r, i) in (row.room_list || '').split('、')" :key="i" size="small" type="warning" style="margin-right:4px;margin-bottom:2px;">
              {{ r }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="room_count" label="房产数" width="70" align="center" />
        <el-table-column prop="bill_count" label="账单数" width="80" align="center" />
        <el-table-column label="应收总额" width="110">
          <template #default="{ row }">¥{{ fmt(row.total_amount) }}</template>
        </el-table-column>
        <el-table-column label="已付" width="100">
          <template #default="{ row }">¥{{ fmt(row.paid_amount) }}</template>
        </el-table-column>
        <el-table-column label="欠费金额" width="130">
          <template #default="{ row }">
            <span style="color:#e53e3e;font-weight:700;font-size:15px;">¥{{ fmt(row.arrears_amount) }}</span>
          </template>
        </el-table-column>
        <el-table-column label="最近催缴" width="160">
          <template #default="{ row }">{{ row.last_dunning_time || '未催缴' }}</template>
        </el-table-column>
        <el-table-column label="操作" width="280" fixed="right">
          <template #default="{ row }">
            <el-button size="small" type="warning" @click="doDunning(row)">📢 催缴</el-button>
            <el-button size="small" @click="doSmsDunning(row)">📱 短信</el-button>
            <el-button size="small" type="success" @click="doWechatDunning(row)">💬 公众号</el-button>
            <el-dropdown trigger="click" style="margin-left:6px;">
              <el-button size="small" type="info">更多<el-icon class="el-icon--right"><arrow-down /></el-icon></el-button>
              <template #dropdown>
                <el-dropdown-menu>
                  <el-dropdown-item @click="showHistory(row)">📋 催缴记录</el-dropdown-item>
                </el-dropdown-menu>
              </template>
            </el-dropdown>
          </template>
        </el-table-column>
      </el-table>
      <div class="pagination">
        <el-pagination v-model:current-page="query.page" v-model:page-size="query.limit" :total="total"
          :page-sizes="[15, 30, 50, 100]" layout="total,sizes,prev,pager,next" @current-change="loadData" @size-change="loadData" />
      </div>
    </el-card>

    <!-- 催缴确认弹窗（按业主） -->
    <el-dialog v-model="dunningVisible" :title="dunningTitle" width="600px" destroy-on-close>
      <div class="dunning-info" v-if="dunningDetail">
        <el-descriptions :column="2" border size="small">
          <el-descriptions-item label="业主">{{ dunningDetail.owner_name }}</el-descriptions-item>
          <el-descriptions-item label="电话">{{ dunningDetail.owner_phone || '-' }}</el-descriptions-item>
          <el-descriptions-item label="欠费房产">{{ dunningDetail.room_list || '-' }}</el-descriptions-item>
          <el-descriptions-item label="欠费账单数">{{ dunningDetail.bill_count }} 条</el-descriptions-item>
          <el-descriptions-item label="应收总额">¥{{ fmt(dunningDetail.total_amount) }}</el-descriptions-item>
          <el-descriptions-item label="已付总额">¥{{ fmt(dunningDetail.paid_amount) }}</el-descriptions-item>
          <el-descriptions-item label="欠费金额" :span="2">
            <span style="color:#e53e3e;font-weight:700;font-size:16px;">¥{{ fmt(dunningDetail.arrears_amount) }}</span>
          </el-descriptions-item>
        </el-descriptions>
        <h4 style="margin:16px 0 8px;">欠费账单明细</h4>
        <el-table :data="dunningDetail.bill_details || []" size="small" border max-height="240">
          <el-table-column prop="room_number" label="房间" width="70" />
          <el-table-column prop="bill_no" label="账单号" width="130" />
          <el-table-column prop="charge_item_name" label="收费项目" width="90" />
          <el-table-column prop="bill_period" label="账期" width="80" />
          <el-table-column prop="total_amount" label="应收" width="80">
            <template #default="{ row: b }">¥{{ fmt(b.total_amount) }}</template>
          </el-table-column>
          <el-table-column prop="paid_amount" label="已付" width="80">
            <template #default="{ row: b }">¥{{ fmt(b.paid_amount) }}</template>
          </el-table-column>
          <el-table-column prop="arrears" label="欠费" width="80">
            <template #default="{ row: b }">
              <span style="color:#e53e3e;">¥{{ fmt(b.arrears) }}</span>
            </template>
          </el-table-column>
          <el-table-column prop="due_date" label="到期日" width="100" />
        </el-table>
        <el-input v-model="dunningRemark" placeholder="催缴备注（可选）" style="margin-top:12px;" />
      </div>
      <template #footer>
        <el-button @click="dunningVisible = false">取消</el-button>
        <el-button type="primary" @click="confirmDunning" :loading="dunningSubmitting">确认催缴</el-button>
      </template>
    </el-dialog>

    <!-- 催缴历史弹窗（按业主） -->
    <el-dialog v-model="historyVisible" :title="historyTitle" width="700px" destroy-on-close>
      <el-table :data="historyList" v-loading="historyLoading" size="small" border>
        <el-table-column type="index" label="#" width="50" />
        <el-table-column prop="create_time" label="催缴时间" width="160" />
        <el-table-column prop="room_number" label="房间" width="90" />
        <el-table-column prop="arrears_amount" label="欠费金额" width="110">
          <template #default="{ row }">¥{{ fmt(row.arrears_amount) }}</template>
        </el-table-column>
        <el-table-column prop="bill_count" label="账单数" width="70" align="center" />
        <el-table-column prop="admin_name" label="操作人" width="90" />
        <el-table-column prop="remark" label="备注" min-width="140" />
      </el-table>
      <template #footer><el-button @click="historyVisible = false">关闭</el-button></template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { ArrowDown } from '@element-plus/icons-vue'
import { apiGet, apiPost } from '@/utils/request'

const list = ref<any[]>([])
const total = ref(0)
const loading = ref(false)
const communities = ref<any[]>([])
const dunningVisible = ref(false)
const dunningSubmitting = ref(false)
const dunningDetail = ref<any>(null)
const dunningRemark = ref('')
const dunningTitle = ref('催缴确认')
const currentDunningOwnerId = ref(0)
const historyVisible = ref(false)
const historyList = ref<any[]>([])
const historyLoading = ref(false)
const historyTitle = ref('催缴历史')

const query = reactive({ keyword: '', community_id: undefined as any, page: 1, limit: 15 })

function fmt(v: any) {
  const n = parseFloat(v)
  return isNaN(n) ? '0.00' : n.toFixed(2)
}

// 汇总统计
const stats = computed(() => {
  const ownerCount = list.value.length
  let totalArrears = 0, billCount = 0
  list.value.forEach(r => { totalArrears += Number(r.arrears_amount) || 0; billCount += Number(r.bill_count) || 0 })
  return { ownerCount, totalArrears: totalArrears.toFixed(2), billCount }
})

function resetQuery() { query.keyword = ''; query.community_id = undefined; query.page = 1; loadData() }

async function loadData() {
  loading.value = true
  try {
    const r = await apiGet('/admin/charge/arrearsList', { ...query })
    list.value = r.data?.list || r.data || []
    total.value = r.count || r.data?.total || list.value.length
  } catch { list.value = []; total.value = 0 }
  finally { loading.value = false }
}

// ========== 手动催缴 ==========
function doDunning(row: any) {
  currentDunningOwnerId.value = row.owner_id
  dunningTitle.value = `催缴确认 — ${row.owner_name}`
  dunningRemark.value = ''
  dunningDetail.value = null
  dunningVisible.value = true
}

async function confirmDunning() {
  if (!currentDunningOwnerId.value) return
  dunningSubmitting.value = true
  try {
    const r = await apiPost('/admin/charge/arrearsDunning', {
      owner_id: currentDunningOwnerId.value,
      remark: dunningRemark.value
    })
    dunningDetail.value = r.data
    ElMessage.success(r.msg || '催缴成功')
    dunningVisible.value = false
    loadData()
  } finally {
    dunningSubmitting.value = false
  }
}

// ========== 短信催缴 ==========
async function doSmsDunning(row: any) {
  if (!row.owner_phone) {
    ElMessage.warning('该业主未登记手机号，无法发送短信')
    return
  }
  try {
    await ElMessageBox.confirm(
      `确认向「${row.owner_name}」(${row.owner_phone}) 发送短信催缴？\n\n欠费房产：${row.room_list}\n欠费金额：¥${fmt(row.arrears_amount)}   |   账单数：${row.bill_count}`,
      '短信催缴',
      { confirmButtonText: '发送短信', type: 'info' }
    )
  } catch { return }

  try {
    const r = await apiPost('/admin/charge/arrearsSmsDunning', { owner_id: row.owner_id })
    ElMessage.success({ message: r.msg || '短信已发送', duration: 3000 })
    loadData()
  } catch {}
}

// ========== 公众号催缴 ==========
async function doWechatDunning(row: any) {
  try {
    await ElMessageBox.confirm(
      `确认向「${row.owner_name}」推送公众号模板消息？\n\n欠费房产：${row.room_list}\n欠费金额：¥${fmt(row.arrears_amount)}   |   账单数：${row.bill_count}`,
      '公众号催缴',
      { confirmButtonText: '推送消息', type: 'info' }
    )
  } catch { return }

  try {
    const r = await apiPost('/admin/charge/arrearsWechatDunning', { owner_id: row.owner_id })
    ElMessage.success({ message: r.msg || '模板消息已推送', duration: 3000 })
    loadData()
  } catch {}
}

// ========== 催缴历史 ==========
async function showHistory(row: any) {
  historyTitle.value = `催缴历史 — ${row.owner_name}`
  historyVisible.value = true
  historyList.value = []
  historyLoading.value = true
  try {
    const r = await apiGet('/admin/charge/arrearsHistory', { owner_id: row.owner_id })
    historyList.value = r.data || []
  } finally { historyLoading.value = false }
}

onMounted(async () => {
  try {
    const rc = await apiGet('/admin/community/list', { limit: 999 })
    communities.value = rc.data?.list || rc.data || []
  } catch {}
  loadData()
})
</script>

<style scoped>
.search-bar { background:#fff; border-radius:8px; padding:16px 20px; margin-bottom:16px; border:1px solid #e2e8f0; }
.table-card { border-radius:8px; border:1px solid #e2e8f0; }
.pagination { margin-top:16px; display:flex; justify-content:flex-end; }

.stats-row { display:flex; gap:24px; margin-top:8px; padding-top:12px; border-top:1px solid #f0f0f0; }
.stat-item { display:flex; flex-direction:column; }
.stat-label { font-size:12px; color:#909399; margin-bottom:2px; }
.stat-value { font-size:20px; font-weight:700; }
.stat-value.danger { color:#e53e3e; }
.stat-value.warning { color:#e6a23c; }

.dunning-info { padding:4px 0; }
</style>
