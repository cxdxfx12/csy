<template>
  <div class="page-container">
    <div class="search-bar">
      <el-form :model="query" inline>
        <el-form-item><el-input v-model="query.keyword" placeholder="账单号/房间/姓名" clearable style="width:200px;" /></el-form-item>
        <el-form-item><el-select v-model="query.community_id" placeholder="小区" clearable style="width:160px;"><el-option v-for="c in communities" :key="c.id" :label="c.name" :value="c.id" /></el-select></el-form-item>
        <el-form-item><el-select v-model="query.status" placeholder="状态" clearable style="width:120px;"><el-option label="待缴" :value="1" /><el-option label="部分缴纳" :value="2" /><el-option label="已缴" :value="3" /></el-select></el-form-item>
        <el-form-item>
          <el-button type="primary" @click="loadData">搜索</el-button>
          <el-button @click="resetQuery">重置</el-button>
          <el-button type="success" @click="genDialogVisible = true">生成账单</el-button>
        </el-form-item>
      </el-form>
    </div>
    <el-card shadow="never" class="table-card">
      <el-table :data="list" v-loading="loading" stripe border>
        <el-table-column type="index" label="#" width="50" />
        <el-table-column prop="bill_no" label="账单号" width="160" />
        <el-table-column prop="owner_name" label="业主" width="100"><template #default="{row}">{{ row.owner_name || '无业主' }}</template></el-table-column>
        <el-table-column prop="room_number" label="房间" width="100" />
        <el-table-column prop="charge_item_name" label="收费项目" width="120" />
        <el-table-column prop="bill_period" label="账期" width="100" />
        <el-table-column prop="total_amount" label="金额" width="100"><template #default="{row}">¥{{ row.total_amount }}</template></el-table-column>
        <el-table-column prop="paid_amount" label="已缴" width="100"><template #default="{row}">¥{{ row.paid_amount }}</template></el-table-column>
        <el-table-column prop="status" label="状态" width="90"><template #default="{row}"><el-tag :type="row.status===3?'success':row.status===2?'warning':'info'">{{ statusMap[row.status]||'未知' }}</el-tag></template></el-table-column>
        <el-table-column prop="due_date" label="到期日" width="110" />
        <el-table-column label="操作" width="200" fixed="right">
          <template #default="{row}">
            <el-button size="small" type="primary" @click="showDetail(row)">详情</el-button>
            <el-button size="small" type="danger" @click="handleDelete(row)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>
      <div class="pagination">
        <el-pagination v-model:current-page="query.page" v-model:page-size="query.limit" :total="total" :page-sizes="[15,30,50,100]" layout="total,sizes,prev,pager,next" @change="loadData" />
      </div>
    </el-card>

    <el-dialog v-model="genDialogVisible" title="生成账单" width="480px" destroy-on-close @open="onGenOpen">
      <el-form :model="genForm" ref="genFormRef" label-width="100px">
        <el-form-item label="小区" prop="community_id"><el-select v-model="genForm.community_id" placeholder="选择小区" style="width:100%;" @change="onGenCommunityChange"><el-option v-for="c in communities" :key="c.id" :label="c.name" :value="c.id" /></el-select></el-form-item>
        <el-form-item label="收费项目" prop="charge_item_id"><el-select v-model="genForm.charge_item_id" placeholder="选择收费项目" style="width:100%;" :loading="chargeItemsLoading"><el-option v-for="i in chargeItems" :key="i.id" :label="i.name" :value="i.id" /></el-select></el-form-item>
        <el-form-item label="账期"><el-input v-model="genForm.period" placeholder="如 2024-06" /></el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="genDialogVisible = false">取消</el-button>
        <el-button type="primary" @click="submitGen" :loading="genSubmitting">确定</el-button>
      </template>
    </el-dialog>

    <el-dialog v-model="detailVisible" title="账单详情" width="600px" destroy-on-close>
      <el-descriptions :column="2" border v-if="detail">
        <el-descriptions-item label="账单号">{{ detail.bill_no }}</el-descriptions-item>
        <el-descriptions-item label="账期">{{ detail.bill_period }}</el-descriptions-item>
        <el-descriptions-item label="业主">{{ detail.owner_name }}</el-descriptions-item>
        <el-descriptions-item label="房间">{{ detail.room_number }}</el-descriptions-item>
        <el-descriptions-item label="收费项目">{{ detail.charge_item_name }}</el-descriptions-item>
        <el-descriptions-item label="到期日">{{ detail.due_date }}</el-descriptions-item>
        <el-descriptions-item label="总金额">¥{{ detail.total_amount }}</el-descriptions-item>
        <el-descriptions-item label="已缴金额">¥{{ detail.paid_amount }}</el-descriptions-item>
        <el-descriptions-item label="状态"><el-tag :type="detail.status===3?'success':detail.status===2?'warning':'info'">{{ statusMap[detail.status]||'未知' }}</el-tag></el-descriptions-item>
      </el-descriptions>
      <h4 style="margin:16px 0 8px;">缴费记录</h4>
      <el-table :data="detail?.payments||[]" size="small" border>
        <el-table-column prop="payment_no" label="流水号" width="160" />
        <el-table-column prop="amount" label="金额" width="100"><template #default="{row}">¥{{ row.amount }}</template></el-table-column>
        <el-table-column prop="pay_method" label="方式" width="90"><template #default="{row}">{{ payMethodMap[row.pay_method]||row.pay_method }}</template></el-table-column>
        <el-table-column prop="pay_time" label="时间" width="160" />
      </el-table>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { apiGet, apiPost } from '@/utils/request'

const list = ref<any[]>([])
const total = ref(0)
const loading = ref(false)
const genDialogVisible = ref(false)
const detailVisible = ref(false)
const genSubmitting = ref(false)
const communities = ref<any[]>([])
const chargeItems = ref<any[]>([])
const chargeItemsLoading = ref(false)
const detail = ref<any>(null)

const statusMap: Record<number, string> = { 1: '待缴', 2: '部分缴纳', 3: '已缴' }
const payMethodMap: Record<number, string> = { 1: '现金', 2: '微信', 3: '支付宝', 4: '银行卡', 5: '其他' }

const query = reactive({ keyword: '', community_id: undefined as any, status: undefined as any, page: 1, limit: 15 })
const genForm = reactive({ community_id: '', charge_item_id: '', period: '' })

function resetQuery() { query.keyword = ''; query.community_id = undefined; query.status = undefined; query.page = 1; loadData() }

async function loadData() {
  loading.value = true
  try {
    const r = await apiGet('/admin/charge/billList', { ...query })
    list.value = r.data?.list || r.data || []
    total.value = r.count || r.data?.total || list.value.length
  } catch { list.value = []; total.value = 0 }
  finally { loading.value = false }
}

async function submitGen() {
  if (!genForm.community_id || !genForm.charge_item_id) { ElMessage.warning('请选择小区和收费项目'); return }
  genSubmitting.value = true
  try {
    const r = await apiPost('/admin/charge/billGenerate', { ...genForm })
    ElMessage.success(r.msg || '生成成功')
    genDialogVisible.value = false
    loadData()
  } finally { genSubmitting.value = false }
}

async function showDetail(row: any) {
  try {
    const r = await apiGet('/admin/charge/billDetail', { id: row.id })
    detail.value = r.data
    detailVisible.value = true
  } catch {}
}

async function handleDelete(row: any) {
  try {
    await ElMessageBox.confirm(`确定删除账单 "${row.bill_no}" 吗？`, '提示', { type: 'warning' })
    await apiPost('/admin/charge/billDelete', { id: row.id })
    ElMessage.success('删除成功')
    loadData()
  } catch {}
}

onMounted(async () => {
  try {
    const rc = await apiGet('/admin/community/list', { limit: 999 })
    communities.value = rc.data?.list || rc.data || []
  } catch {}
  loadData()
})

function onGenOpen() {
  genForm.community_id = ''
  genForm.charge_item_id = ''
  genForm.period = ''
  chargeItems.value = []
}

async function onGenCommunityChange(id: number) {
  genForm.charge_item_id = ''
  chargeItems.value = []
  if (!id) return
  chargeItemsLoading.value = true
  try {
    const ri = await apiGet('/admin/charge/itemSelect', { community_id: id })
    chargeItems.value = ri.data || []
  } finally { chargeItemsLoading.value = false }
}
</script>

<style scoped>
.search-bar { background:#fff;border-radius:8px;padding:16px 20px;margin-bottom:16px;border:1px solid #e2e8f0; }
.table-card { border-radius:8px;border:1px solid #e2e8f0; }
.table-toolbar { margin-bottom:16px; }
.pagination { margin-top:16px;display:flex;justify-content:flex-end; }
</style>
