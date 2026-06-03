<template>
  <div class="page-container">
    <div class="search-bar">
      <el-form :model="query" inline>
        <el-form-item><el-input v-model="query.keyword" placeholder="车位号" clearable style="width:200px;" /></el-form-item>
        <el-form-item><el-select v-model="query.community_id" placeholder="小区" clearable style="width:160px;"><el-option v-for="c in communities" :key="c.id" :label="c.name" :value="c.id" /></el-select></el-form-item>
        <el-form-item><el-select v-model="query.status" placeholder="状态" clearable style="width:120px;"><el-option label="空闲" :value="1" /><el-option label="已售" :value="2" /><el-option label="已租" :value="3" /></el-select></el-form-item>
        <el-form-item>
          <el-button type="primary" @click="loadData">搜索</el-button>
          <el-button @click="resetQuery">重置</el-button>
        </el-form-item>
      </el-form>
    </div>
    <el-card shadow="never" class="table-card">
      <div class="table-toolbar"><el-button type="primary" @click="openForm()">添加车位</el-button></div>
      <el-table :data="list" v-loading="loading" stripe border>
        <el-table-column type="index" label="#" width="50" />
        <el-table-column prop="id" label="ID" width="60" />
        <el-table-column prop="space_no" label="车位号" width="120" />
        <el-table-column prop="community_name" label="小区" width="140" />
        <el-table-column prop="area" label="面积" width="80" />
        <el-table-column prop="type" label="类型" width="100"><template #default="{row}">{{ typeMap[row.type]||row.type }}</template></el-table-column>
        <el-table-column prop="status" label="状态" width="90"><template #default="{row}"><el-tag :type="statusType[row.status]||'info'">{{ statusMap[row.status]||'未知' }}</el-tag></template></el-table-column>
        <el-table-column prop="owner_name" label="业主" width="120" />
        <el-table-column prop="price" label="价格" width="100"><template #default="{row}">¥{{ row.price }}</template></el-table-column>
        <el-table-column prop="create_time" label="创建时间" width="170" />
        <el-table-column label="操作" width="200" fixed="right">
          <template #default="{row}">
            <el-button size="small" @click="openForm(row)">编辑</el-button>
            <el-button size="small" type="danger" @click="handleDelete(row)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>
      <div class="pagination">
        <el-pagination v-model:current-page="query.page" v-model:page-size="query.limit" :total="total" :page-sizes="[15,30,50,100]" layout="total,sizes,prev,pager,next" @change="loadData" />
      </div>
    </el-card>

    <el-dialog v-model="dialogVisible" :title="formTitle" width="560px" destroy-on-close>
      <el-form :model="form" :rules="rules" ref="formRef" label-width="100px">
        <el-form-item label="所属小区" prop="community_id"><el-select v-model="form.community_id" placeholder="选择小区" style="width:100%;"><el-option v-for="c in communities" :key="c.id" :label="c.name" :value="c.id" /></el-select></el-form-item>
        <el-form-item label="车位号" prop="space_no"><el-input v-model="form.space_no" placeholder="车位编号" /></el-form-item>
        <el-form-item label="面积"><el-input-number v-model="form.area" :min="0" :precision="2" style="width:100%;" /></el-form-item>
        <el-form-item label="类型">
          <el-select v-model="form.type" style="width:100%;"><el-option label="产权车位" :value="1" /><el-option label="租赁车位" :value="2" /><el-option label="临时车位" :value="3" /></el-select>
        </el-form-item>
        <el-form-item label="状态">
          <el-select v-model="form.status" style="width:100%;"><el-option label="空闲" :value="1" /><el-option label="已售" :value="2" /><el-option label="已租" :value="3" /></el-select>
        </el-form-item>
        <el-form-item label="价格"><el-input-number v-model="form.price" :min="0" :precision="2" style="width:100%;" /></el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" @click="submitForm" :loading="submitting">确定</el-button>
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
const dialogVisible = ref(false)
const submitting = ref(false)
const formRef = ref<any>(null)
const formTitle = ref('添加车位')
const communities = ref<any[]>([])

const typeMap: Record<number, string> = { 1: '产权车位', 2: '租赁车位', 3: '临时车位' }
const statusMap: Record<number, string> = { 1: '空闲', 2: '已售', 3: '已租' }
const statusType: Record<number, string> = { 1: 'success', 2: 'warning', 3: 'primary' }

const query = reactive({ keyword: '', community_id: undefined as any, status: undefined as any, page: 1, limit: 15 })
const form = reactive<any>({ id: 0, community_id: '', space_no: '', area: 0, type: 1, status: 1, price: 0 })
const rules = { space_no: [{ required: true, message: '请输入车位号', trigger: 'blur' }], community_id: [{ required: true, message: '请选择小区', trigger: 'change' }] }

function resetQuery() { query.keyword = ''; query.community_id = undefined; query.status = undefined; query.page = 1; loadData() }

async function loadData() {
  loading.value = true
  try {
    const r = await apiGet('/admin/parking/spaceList', { ...query })
    list.value = r.data?.list || r.data || []
    total.value = r.count || r.data?.total || list.value.length
  } catch { list.value = []; total.value = 0 }
  finally { loading.value = false }
}

function openForm(row?: any) {
  formTitle.value = row ? '编辑车位' : '添加车位'
  Object.assign(form, row || { id: 0, community_id: '', space_no: '', area: 0, type: 1, status: 1, price: 0 })
  dialogVisible.value = true
}

async function submitForm() {
  const valid = await formRef.value?.validate().catch(() => false)
  if (!valid) return
  submitting.value = true
  try {
    const url = form.id ? '/admin/parking/spaceEdit' : '/admin/parking/spaceAdd'
    await apiPost(url, { ...form })
    ElMessage.success(form.id ? '修改成功' : '添加成功')
    dialogVisible.value = false
    loadData()
  } finally { submitting.value = false }
}

async function handleDelete(row: any) {
  try {
    await ElMessageBox.confirm(`确定删除车位 "${row.space_no}" 吗？`, '提示', { type: 'warning' })
    await apiPost('/admin/parking/spaceDelete', { id: row.id })
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
.table-toolbar { margin-bottom:16px; }
.pagination { margin-top:16px;display:flex;justify-content:flex-end; }
</style>
