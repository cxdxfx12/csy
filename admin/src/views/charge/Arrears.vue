<template>
  <div class="page-container">
    <div class="search-bar">
      <el-form :model="query" inline>
        <el-form-item>
          <el-input v-model="query.keyword" placeholder="房间号/楼栋/业主/电话" clearable style="width:220px;" />
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
        <div class="stat-item"><span class="stat-label">欠费房间</span><span class="stat-value danger">{{ stats.roomCount }}</span></div>
        <div class="stat-item"><span class="stat-label">欠费总额</span><span class="stat-value danger">¥{{ stats.totalArrears }}</span></div>
        <div class="stat-item"><span class="stat-label">欠费账单</span><span class="stat-value warning">{{ stats.billCount }}</span></div>
      </div>
    </div>

    <el-card shadow="never" class="table-card">
      <el-table :data="list" v-loading="loading" stripe border>
        <el-table-column type="index" label="#" width="50" />
        <el-table-column prop="community_name" label="小区" width="100" />
        <el-table-column prop="building_name" label="楼栋" width="80" />
        <el-table-column prop="room_number" label="房间号" width="100" />
        <el-table-column prop="owner_name" label="业主" width="90">
          <template #default="{ row }">{{ row.owner_name || '-' }}</template>
        </el-table-column>
        <el-table-column prop="owner_phone" label="电话" width="120" />
        <el-table-column prop="bill_count" label="账单数" width="80" align="center" />
        <el-table-column prop="total_amount" label="应收总额" width="110">
          <template #default="{ row }">¥{{ row.total_amount }}</template>
        </el-table-column>
        <el-table-column prop="paid_amount" label="已付" width="100">
          <template #default="{ row }">¥{{ row.paid_amount }}</template>
        </el-table-column>
        <el-table-column prop="arrears_amount" label="欠费金额" width="120">
          <template #default="{ row }">
            <span style="color:#e53e3e;font-weight:700;">¥{{ row.arrears_amount }}</span>
          </template>
        </el-table-column>
        <el-table-column prop="last_dunning_time" label="最近催单" width="160">
          <template #default="{ row }">{{ row.last_dunning_time || '未催单' }}</template>
        </el-table-column>
        <el-table-column label="操作" width="280" fixed="right">
          <template #default="{ row }">
            <el-button size="small" type="warning" @click="doDunning(row)">📢 催单</el-button>
            <el-button size="small" @click="doSmsDunning(row)">📱 短信</el-button>
            <el-button size="small" type="success" @click="doWechatDunning(row)">💬 公众号</el-button>
            <el-dropdown trigger="click" style="margin-left:6px;">
              <el-button size="small" type="info">更多<el-icon class="el-icon--right"><arrow-down /></el-icon></el-button>
              <template #dropdown>
                <el-dropdown-menu>
                  <el-dropdown-item @click="showHistory(row)">📋 催单记录</el-dropdown-item>
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

    <!-- 催单确认弹窗 -->
    <el-dialog v-model="dunningVisible" title="催单确认" width="550px" destroy-on-close>
      <div class="dunning-info" v-if="dunningDetail">
        <el-descriptions :column="2" border size="small">
          <el-descriptions-item label="房间">{{ dunningDetail.room_number }}</el-descriptions-item>
          <el-descriptions-item label="业主">{{ dunningDetail.owner_name }}</el-descriptions-item>
          <el-descriptions-item label="电话">{{ dunningDetail.owner_phone || '-' }}</el-descriptions-item>
          <el-descriptions-item label="欠费账单数">{{ dunningDetail.bill_count }} 条</el-descriptions-item>
          <el-descriptions-item label="应收总额">¥{{ dunningDetail.total_amount }}</el-descriptions-item>
          <el-descriptions-item label="已付总额">¥{{ dunningDetail.paid_amount }}</el-descriptions-item>
          <el-descriptions-item label="欠费金额">
            <span style="color:#e53e3e;font-weight:700;font-size:16px;">¥{{ dunningDetail.arrears_amount }}</span>
          </el-descriptions-item>
        </el-descriptions>
        <h4 style="margin:16px 0 8px;">欠费账单明细</h4>
        <el-table :data="dunningDetail.bill_details || []" size="small" border max-height="200">
          <el-table-column prop="bill_no" label="账单号" width="140" />
          <el-table-column prop="charge_item_name" label="收费项目" width="100" />
          <el-table-column prop="bill_period" label="账期" width="80" />
          <el-table-column prop="total_amount" label="应收" width="80">
            <template #default="{ row: b }">¥{{ b.total_amount }}</template>
          </el-table-column>
          <el-table-column prop="paid_amount" label="已付" width="80">
            <template #default="{ row: b }">¥{{ b.paid_amount }}</template>
          </el-table-column>
          <el-table-column prop="arrears" label="欠费" width="80">
            <template #default="{ row: b }">
              <span style="color:#e53e3e;">¥{{ b.arrears }}</span>
            </template>
          </el-table-column>
          <el-table-column prop="due_date" label="到期日" width="100" />
        </el-table>
        <el-input v-model="dunningRemark" placeholder="催单备注（可选）" style="margin-top:12px;" />
      </div>
      <template #footer>
        <el-button @click="dunningVisible = false">取消</el-button>
        <el-button type="primary" @click="confirmDunning" :loading="dunningSubmitting">确认催单</el-button>
      </template>
    </el-dialog>

    <!-- 催单历史弹窗 -->
    <el-dialog v-model="historyVisible" title="催单历史" width="600px" destroy-on-close>
      <el-table :data="historyList" v-loading="historyLoading" size="small" border>
        <el-table-column type="index" label="#" width="50" />
        <el-table-column prop="create_time" label="催单时间" width="160" />
        <el-table-column prop="arrears_amount" label="欠费金额" width="100">
          <template #default="{ row }">¥{{ row.arrears_amount }}</template>
        </el-table-column>
        <el-table-column prop="bill_count" label="账单数" width="70" />
        <el-table-column prop="admin_name" label="操作人" width="90" />
        <el-table-column prop="remark" label="备注" min-width="120" />
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
const currentDunningRoomId = ref(0)
const historyVisible = ref(false)
const historyList = ref<any[]>([])
const historyLoading = ref(false)

const query = reactive({ keyword: '', community_id: undefined as any, page: 1, limit: 15 })

// 汇总统计
const stats = computed(() => {
  const roomCount = list.value.length
  let totalArrears = 0, billCount = 0
  list.value.forEach(r => { totalArrears += Number(r.arrears_amount) || 0; billCount += Number(r.bill_count) || 0 })
  return { roomCount, totalArrears: totalArrears.toFixed(2), billCount }
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

// ========== 手动催单 ==========
function doDunning(row: any) {
  currentDunningRoomId.value = row.room_id
  dunningRemark.value = ''
  dunningDetail.value = null
  dunningVisible.value = true
}

async function confirmDunning() {
  if (!currentDunningRoomId.value) return
  dunningSubmitting.value = true
  try {
    const r = await apiPost('/admin/charge/arrearsDunning', {
      room_id: currentDunningRoomId.value,
      remark: dunningRemark.value
    })
    dunningDetail.value = r.data
    ElMessage.success(r.msg || '催单成功')
    dunningVisible.value = false
    loadData()
  } finally {
    dunningSubmitting.value = false
  }
}

// ========== 短信催缴 ==========
async function doSmsDunning(row: any) {
  if (!row.owner_phone) {
    ElMessage.warning('该房间业主未登记手机号，无法发送短信')
    return
  }
  try {
    await ElMessageBox.confirm(
      `确认向「${row.owner_name || '业主'}」(${row.owner_phone}) 发送短信催缴？\n\n欠费金额：¥${row.arrears_amount}   |   账单数：${row.bill_count}`,
      '短信催缴',
      { confirmButtonText: '发送短信', type: 'info' }
    )
  } catch { return }

  try {
    const r = await apiPost('/admin/charge/arrearsSmsDunning', { room_id: row.room_id })
    ElMessage.success({ message: r.msg || '短信已发送', duration: 3000 })
    loadData()
  } catch {}
}

// ========== 公众号催缴 ==========
async function doWechatDunning(row: any) {
  try {
    await ElMessageBox.confirm(
      `确认向「${row.owner_name || '业主'}」推送公众号模板消息？\n\n欠费金额：¥${row.arrears_amount}   |   账单数：${row.bill_count}`,
      '公众号催缴',
      { confirmButtonText: '推送消息', type: 'info' }
    )
  } catch { return }

  try {
    const r = await apiPost('/admin/charge/arrearsWechatDunning', { room_id: row.room_id })
    ElMessage.success({ message: r.msg || '模板消息已推送', duration: 3000 })
    loadData()
  } catch {}
}

// ========== 催单历史 ==========
async function showHistory(row: any) {
  historyVisible.value = true
  historyList.value = []
  historyLoading.value = true
  try {
    const r = await apiGet('/admin/charge/arrearsHistory', { room_id: row.room_id })
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
