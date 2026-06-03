<template>
  <div class="page-container">
    <div class="search-bar">
      <el-form :model="query" inline>
        <el-form-item><el-input v-model="query.keyword" placeholder="标题/内容" clearable style="width:200px;" /></el-form-item>
        <el-form-item><el-select v-model="query.community_id" placeholder="小区" clearable style="width:160px;"><el-option v-for="c in communities" :key="c.id" :label="c.name" :value="c.id" /></el-select></el-form-item>
        <el-form-item><el-select v-model="query.status" placeholder="状态" clearable style="width:120px;"><el-option label="待处理" :value="1" /><el-option label="处理中" :value="2" /><el-option label="已处理" :value="3" /><el-option label="已关闭" :value="4" /><el-option label="已评价" :value="5" /></el-select></el-form-item>
        <el-form-item><el-select v-model="query.type" placeholder="类型" clearable style="width:120px;"><el-option label="投诉" :value="1" /><el-option label="建议" :value="2" /><el-option label="表扬" :value="3" /><el-option label="咨询" :value="4" /></el-select></el-form-item>
        <el-form-item>
          <el-button type="primary" @click="loadData">搜索</el-button>
          <el-button @click="resetQuery">重置</el-button>
        </el-form-item>
      </el-form>
    </div>
    <el-card shadow="never" class="table-card">
      <el-table :data="list" v-loading="loading" stripe border>
        <el-table-column type="index" label="#" width="50" />
        <el-table-column prop="id" label="ID" width="60" />
        <el-table-column prop="title" label="标题" width="180" />
        <el-table-column prop="community_name" label="小区" width="120" />
        <el-table-column prop="owner_name" label="业主" width="100" />
        <el-table-column prop="room_number" label="房间" width="100" />
        <el-table-column prop="type" label="类型" width="80"><template #default="{row}"><el-tag :type="typeTag[row.type]||'info'">{{ typeMap[row.type]||'未知' }}</el-tag></template></el-table-column>
        <el-table-column prop="status" label="状态" width="90"><template #default="{row}"><el-tag :type="statusType[row.status]||'info'">{{ statusMap[row.status]||'未知' }}</el-tag></template></el-table-column>
        <el-table-column prop="handler_name" label="处理人" width="100"><template #default="{row}">{{ row.handler_name||'-' }}</template></el-table-column>
        <el-table-column prop="create_time" label="提交时间" width="170" />
        <el-table-column label="操作" width="260" fixed="right">
          <template #default="{row}">
            <el-button size="small" type="primary" @click="showDetail(row)">详情</el-button>
            <el-button v-if="row.status!==3" size="small" type="warning" @click="openHandle(row)">处理</el-button>
            <el-button size="small" type="danger" @click="handleDelete(row)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>
      <div class="pagination">
        <el-pagination v-model:current-page="query.page" v-model:page-size="query.limit" :total="total" :page-sizes="[15,30,50,100]" layout="total,sizes,prev,pager,next" @current-change="loadData" @size-change="loadData" />
      </div>
    </el-card>

    <el-dialog v-model="detailVisible" title="投诉建议详情" width="600px" destroy-on-close>
      <el-descriptions :column="2" border v-if="detail">
        <el-descriptions-item label="标题">{{ detail.title }}</el-descriptions-item>
        <el-descriptions-item label="类型"><el-tag :type="typeTag[detail.type]||'info'">{{ typeMap[detail.type]||'未知' }}</el-tag></el-descriptions-item>
        <el-descriptions-item label="业主">{{ detail.owner_name }}</el-descriptions-item>
        <el-descriptions-item label="房间">{{ detail.room_number }}</el-descriptions-item>
        <el-descriptions-item label="状态"><el-tag :type="statusType[detail.status]||'info'">{{ statusMap[detail.status]||'未知' }}</el-tag></el-descriptions-item>
        <el-descriptions-item label="处理人">{{ detail.handler_name||'-' }}</el-descriptions-item>
        <el-descriptions-item label="内容" :span="2">{{ detail.content }}</el-descriptions-item>
        <el-descriptions-item label="处理结果" :span="2">{{ detail.handle_content||'-' }}</el-descriptions-item>
      </el-descriptions>
    </el-dialog>

    <el-dialog v-model="handleVisible" title="处理投诉建议" width="480px" destroy-on-close>
      <el-form label-width="80px">
        <el-form-item label="处理结果"><el-input v-model="handleContent" type="textarea" rows="4" placeholder="请输入处理结果" /></el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="handleVisible = false">取消</el-button>
        <el-button type="primary" @click="submitHandle" :loading="handleLoading">确定</el-button>
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
const handleVisible = ref(false)
const handleLoading = ref(false)
const communities = ref<any[]>([])
const detail = ref<any>(null)
const handleId = ref(0)
const handleContent = ref('')

const statusMap: Record<number, string> = { 1: '待处理', 2: '处理中', 3: '已处理', 4: '已关闭', 5: '已评价' }
const statusType: Record<number, string> = { 1: 'danger', 2: 'warning', 3: 'success', 4: 'info', 5: '' }
const typeMap: Record<number, string> = { 1: '投诉', 2: '建议', 3: '表扬', 4: '咨询' }
const typeTag: Record<number, string> = { 1: 'danger', 2: 'warning', 3: 'success', 4: 'primary' }

const query = reactive({ keyword: '', community_id: undefined as any, status: undefined as any, type: undefined as any, page: 1, limit: 15 })

function resetQuery() { query.keyword = ''; query.community_id = undefined; query.status = undefined; query.type = undefined; query.page = 1; loadData() }

async function loadData() {
  loading.value = true
  try {
    const r = await apiGet('/admin/complaint/list', { ...query })
    list.value = r.data?.list || r.data || []
    total.value = r.count || r.data?.total || list.value.length
  } catch { list.value = []; total.value = 0 }
  finally { loading.value = false }
}

async function showDetail(row: any) {
  try {
    const r = await apiGet('/admin/complaint/detail', { id: row.id })
    detail.value = r.data
    detailVisible.value = true
  } catch {}
}

function openHandle(row: any) {
  handleId.value = row.id
  handleContent.value = ''
  handleVisible.value = true
}

async function submitHandle() {
  if (!handleContent.value.trim()) { ElMessage.warning('请输入处理结果'); return }
  handleLoading.value = true
  try {
    await apiPost('/admin/complaint/handle', { id: handleId.value, handle_content: handleContent.value, status: 3 })
    ElMessage.success('处理成功')
    handleVisible.value = false
    loadData()
  } finally { handleLoading.value = false }
}

async function handleDelete(row: any) {
  try {
    await ElMessageBox.confirm(`确定删除该${typeMap[row.type]||'记录'}吗？`, '提示', { type: 'warning' })
    await apiPost('/admin/complaint/delete', { id: row.id })
    ElMessage.success('删除成功')
    loadData()
  } catch {}
}

onMounted(async () => {
  try { const r = await apiGet('/admin/community/list', { limit: 999 }); communities.value = r.data?.list || r.data || [] } catch {}
  loadData()
})
</script>

<style scoped>
.search-bar { background:#fff;border-radius:8px;padding:16px 20px;margin-bottom:16px;border:1px solid #e2e8f0; }
.table-card { border-radius:8px;border:1px solid #e2e8f0; }
.pagination { margin-top:16px;display:flex;justify-content:flex-end; }
</style>
