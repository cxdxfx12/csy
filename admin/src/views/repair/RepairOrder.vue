<template>
  <div class="page-container">
    <div class="search-bar">
      <el-form :model="query" inline>
        <el-form-item><el-input v-model="query.keyword" placeholder="工单号/报修人/电话" clearable style="width:200px;" /></el-form-item>
        <el-form-item><el-select v-model="query.community_id" placeholder="小区" clearable style="width:160px;"><el-option v-for="c in communities" :key="c.id" :label="c.name" :value="c.id" /></el-select></el-form-item>
        <el-form-item><el-select v-model="query.status" placeholder="状态" clearable style="width:120px;"><el-option v-for="(label,val) in statusMap" :key="val" :label="label" :value="Number(val)" /></el-select></el-form-item>
        <el-form-item>
          <el-button type="primary" @click="loadData">搜索</el-button>
          <el-button @click="resetQuery">重置</el-button>
        </el-form-item>
      </el-form>
    </div>
    <el-card shadow="never" class="table-card">
      <el-table :data="list" v-loading="loading" stripe border>
        <el-table-column type="index" label="#" width="50" />
        <el-table-column prop="order_no" label="工单号" width="150" />
        <el-table-column prop="reporter" label="报修人" width="100" />
        <el-table-column prop="community_name" label="小区" width="120" />
        <el-table-column prop="room_number" label="房间" width="100" />
        <el-table-column prop="type_name" label="类型" width="100" />
        <el-table-column prop="status" label="状态" width="90"><template #default="{row}"><el-tag :type="statusType[row.status]||'info'">{{ statusMap[row.status]||'未知' }}</el-tag></template></el-table-column>
        <el-table-column prop="worker_name" label="维修人" width="100"><template #default="{row}">{{ row.worker_name||'-' }}</template></el-table-column>
        <el-table-column prop="create_time" label="报修时间" width="170" />
        <el-table-column label="操作" width="260" fixed="right">
          <template #default="{row}">
            <el-button size="small" type="primary" @click="showDetail(row)">详情</el-button>
            <el-button v-if="row.status==1" size="small" type="warning" @click="openAssign(row)">派单</el-button>
            <el-button v-if="row.status!=6" size="small" type="info" @click="openClose(row)">关闭</el-button>
            <el-button size="small" type="danger" @click="handleDelete(row)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>
      <div class="pagination">
        <el-pagination v-model:current-page="query.page" v-model:page-size="query.limit" :total="total" :page-sizes="[15,30,50,100]" layout="total,sizes,prev,pager,next" @change="loadData" />
      </div>
    </el-card>

    <el-dialog v-model="detailVisible" title="工单详情" width="600px" destroy-on-close>
      <el-descriptions :column="2" border v-if="detail">
        <el-descriptions-item label="工单号">{{ detail.order_no }}</el-descriptions-item>
        <el-descriptions-item label="状态"><el-tag :type="statusType[detail.status]||'info'">{{ statusMap[detail.status]||'未知' }}</el-tag></el-descriptions-item>
        <el-descriptions-item label="报修人">{{ detail.reporter }}</el-descriptions-item>
        <el-descriptions-item label="电话">{{ detail.reporter_phone }}</el-descriptions-item>
        <el-descriptions-item label="房间">{{ detail.building_name }} {{ detail.room_number }}</el-descriptions-item>
        <el-descriptions-item label="维修人">{{ detail.worker_name||'-' }}</el-descriptions-item>
        <el-descriptions-item label="描述" :span="2">{{ detail.description }}</el-descriptions-item>
      </el-descriptions>
    </el-dialog>

    <el-dialog v-model="assignVisible" title="派单" width="400px" destroy-on-close>
      <el-form label-width="80px">
        <el-form-item label="维修人员"><el-select v-model="assignWorkerId" placeholder="选择维修人员" style="width:100%;"><el-option v-for="w in workers" :key="w.id" :label="w.name" :value="w.id" /></el-select></el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="assignVisible = false">取消</el-button>
        <el-button type="primary" @click="submitAssign" :loading="assignLoading">确定</el-button>
      </template>
    </el-dialog>

    <el-dialog v-model="closeVisible" title="关闭工单" width="400px" destroy-on-close>
      <el-form label-width="80px">
        <el-form-item label="备注"><el-input v-model="closeRemark" type="textarea" rows="3" placeholder="关闭原因" /></el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="closeVisible = false">取消</el-button>
        <el-button type="primary" @click="submitClose" :loading="closeLoading">确定</el-button>
      </template>
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
const detailVisible = ref(false)
const assignVisible = ref(false)
const closeVisible = ref(false)
const assignLoading = ref(false)
const closeLoading = ref(false)
const communities = ref<any[]>([])
const workers = ref<any[]>([])
const detail = ref<any>(null)
const assignOrderId = ref(0)
const assignWorkerId = ref('')
const closeOrderId = ref(0)
const closeRemark = ref('')

const statusMap: Record<number, string> = { 1: '待处理', 2: '已派单', 3: '处理中', 4: '已完成', 5: '已评价', 6: '已关闭' }
const statusType: Record<number, string> = { 1: 'info', 2: 'warning', 3: 'primary', 4: 'success', 5: 'success', 6: 'danger' }

const query = reactive({ keyword: '', community_id: undefined as any, status: undefined as any, page: 1, limit: 15 })

function resetQuery() { query.keyword = ''; query.community_id = undefined; query.status = undefined; query.page = 1; loadData() }

async function loadData() {
  loading.value = true
  try {
    const r = await apiGet('/admin/repair/orderList', { ...query })
    list.value = r.data?.list || r.data || []
    total.value = r.count || r.data?.total || list.value.length
  } catch { list.value = []; total.value = 0 }
  finally { loading.value = false }
}

async function showDetail(row: any) {
  try {
    const r = await apiGet('/admin/repair/orderDetail', { id: row.id })
    detail.value = r.data
    detailVisible.value = true
  } catch {}
}

function openAssign(row: any) {
  assignOrderId.value = row.id
  assignWorkerId.value = ''
  assignVisible.value = true
}

async function submitAssign() {
  if (!assignWorkerId.value) { ElMessage.warning('请选择维修人员'); return }
  assignLoading.value = true
  try {
    await apiPost('/admin/repair/orderAssign', { id: assignOrderId.value, worker_id: assignWorkerId.value })
    ElMessage.success('派单成功')
    assignVisible.value = false
    loadData()
  } finally { assignLoading.value = false }
}

function openClose(row: any) {
  closeOrderId.value = row.id
  closeRemark.value = ''
  closeVisible.value = true
}

async function submitClose() {
  closeLoading.value = true
  try {
    await apiPost('/admin/repair/orderClose', { id: closeOrderId.value, remark: closeRemark.value })
    ElMessage.success('已关闭')
    closeVisible.value = false
    loadData()
  } finally { closeLoading.value = false }
}

async function handleDelete(row: any) {
  try {
    await ElMessageBox.confirm(`确定删除工单 "${row.order_no}" 吗？`, '提示', { type: 'warning' })
    await apiPost('/admin/repair/orderDelete', { id: row.id })
    ElMessage.success('删除成功')
    loadData()
  } catch {}
}

onMounted(async () => {
  try {
    const rc = await apiGet('/admin/community/list', { limit: 999 })
    communities.value = rc.data?.list || rc.data || []
    const rw = await apiGet('/admin/repair/workerList', { limit: 999 })
    workers.value = rw.data?.list || rw.data || []
  } catch {}
  loadData()
})
</script>

<style scoped>
.search-bar { background:#fff;border-radius:8px;padding:16px 20px;margin-bottom:16px;border:1px solid #e2e8f0; }
.table-card { border-radius:8px;border:1px solid #e2e8f0; }
.table-toolbar { margin-bottom:16px; }
.pagination { margin-top:16px;display:flex;justify-content:flex-end; }
</style>
