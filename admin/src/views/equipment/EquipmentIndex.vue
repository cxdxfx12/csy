<template>
  <div class="page-container">
    <div class="search-bar">
      <el-form :model="query" inline>
        <el-form-item><el-input v-model="query.keyword" placeholder="名称/编码/型号" clearable style="width:200px;" /></el-form-item>
        <el-form-item><el-select v-model="query.community_id" placeholder="小区" clearable style="width:160px;"><el-option v-for="c in communities" :key="c.id" :label="c.name" :value="c.id" /></el-select></el-form-item>
        <el-form-item><el-select v-model="query.category" placeholder="分类" clearable style="width:130px;"><el-option label="电梯" :value="1" /><el-option label="消防" :value="2" /><el-option label="安防" :value="3" /><el-option label="照明" :value="4" /><el-option label="给排水" :value="5" /><el-option label="供电" :value="6" /><el-option label="燃气" :value="7" /><el-option label="空调" :value="8" /><el-option label="其他" :value="9" /></el-select></el-form-item>
        <el-form-item><el-select v-model="query.status" placeholder="状态" clearable style="width:120px;"><el-option label="正常" :value="1" /><el-option label="维修中" :value="2" /><el-option label="报废" :value="3" /><el-option label="停用" :value="4" /></el-select></el-form-item>
        <el-form-item>
          <el-button type="primary" @click="loadData">搜索</el-button>
          <el-button @click="resetQuery">重置</el-button>
        </el-form-item>
      </el-form>
    </div>
    <el-card shadow="never" class="table-card">
      <div class="table-toolbar"><el-button type="primary" @click="openForm()">添加设备</el-button></div>
      <el-table :data="list" v-loading="loading" stripe border>
        <el-table-column type="index" label="#" width="50" />
        <el-table-column prop="id" label="ID" width="60" />
        <el-table-column prop="name" label="名称" width="150" />
        <el-table-column prop="code" label="编码" width="120" />
        <el-table-column prop="community_name" label="小区" width="140" />
        <el-table-column prop="category" label="分类" width="100"><template #default="{row}">{{ categoryMap[row.category]||row.category }}</template></el-table-column>
        <el-table-column prop="model" label="型号" width="120" />
        <el-table-column prop="status" label="状态" width="90"><template #default="{row}"><el-tag :type="statusType[row.status]||'info'">{{ statusMap[row.status]||'未知' }}</el-tag></template></el-table-column>
        <el-table-column prop="location" label="位置" width="120" />
        <el-table-column prop="create_time" label="创建时间" width="170" />
        <el-table-column label="操作" width="200" fixed="right">
          <template #default="{row}">
            <el-button size="small" @click="openForm(row)">编辑</el-button>
            <el-button size="small" type="danger" @click="handleDelete(row)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>
      <div class="pagination">
        <el-pagination v-model:current-page="query.page" v-model:page-size="query.limit" :total="total" :page-sizes="[15,30,50,100]" layout="total,sizes,prev,pager,next" @current-change="loadData" @size-change="loadData" />
      </div>
    </el-card>

    <el-dialog v-model="dialogVisible" :title="formTitle" width="560px" destroy-on-close>
      <el-form :model="form" :rules="rules" ref="formRef" label-width="100px">
        <el-form-item label="所属小区" prop="community_id"><el-select v-model="form.community_id" placeholder="选择小区" style="width:100%;"><el-option v-for="c in communities" :key="c.id" :label="c.name" :value="c.id" /></el-select></el-form-item>
        <el-form-item label="名称" prop="name"><el-input v-model="form.name" placeholder="设备名称" /></el-form-item>
        <el-form-item label="编码"><el-input v-model="form.code" placeholder="设备编码" /></el-form-item>
        <el-form-item label="分类" prop="category">
          <el-select v-model="form.category" placeholder="选择分类" style="width:100%;"><el-option label="电梯" :value="1" /><el-option label="消防" :value="2" /><el-option label="安防" :value="3" /><el-option label="照明" :value="4" /><el-option label="给排水" :value="5" /><el-option label="供电" :value="6" /><el-option label="燃气" :value="7" /><el-option label="空调" :value="8" /><el-option label="其他" :value="9" /></el-select>
        </el-form-item>
        <el-form-item label="型号"><el-input v-model="form.model" placeholder="设备型号" /></el-form-item>
        <el-form-item label="位置"><el-input v-model="form.location" placeholder="安装位置" /></el-form-item>
        <el-form-item label="状态">
          <el-select v-model="form.status" style="width:100%;"><el-option label="正常" :value="1" /><el-option label="维修中" :value="2" /><el-option label="报废" :value="3" /><el-option label="停用" :value="4" /></el-select>
        </el-form-item>
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
const formTitle = ref('添加设备')
const communities = ref<any[]>([])

const categoryMap: Record<number, string> = { 1: '电梯', 2: '消防', 3: '安防', 4: '照明', 5: '给排水', 6: '供电', 7: '燃气', 8: '空调', 9: '其他' }
const statusMap: Record<number, string> = { 1: '正常', 2: '维修中', 3: '报废', 4: '停用' }
const statusType: Record<number, string> = { 1: 'success', 2: 'warning', 3: 'danger', 4: 'info' }

const query = reactive({ keyword: '', community_id: undefined as any, category: undefined as any, status: undefined as any, page: 1, limit: 15 })
const form = reactive<any>({ id: 0, community_id: '', name: '', code: '', category: 1, model: '', location: '', status: 1 })
const rules = { name: [{ required: true, message: '请输入名称', trigger: 'blur' }], community_id: [{ required: true, message: '请选择小区', trigger: 'change' }] }

function resetQuery() { query.keyword = ''; query.community_id = undefined; query.category = undefined; query.status = undefined; query.page = 1; loadData() }

async function loadData() {
  loading.value = true
  try {
    const r = await apiGet('/admin/equipment/list', { ...query })
    list.value = r.data?.list || r.data || []
    total.value = r.count || r.data?.total || list.value.length
  } catch { list.value = []; total.value = 0 }
  finally { loading.value = false }
}

function openForm(row?: any) {
  formTitle.value = row ? '编辑设备' : '添加设备'
  Object.assign(form, row || { id: 0, community_id: '', name: '', code: '', category: 1, model: '', location: '', status: 1 })
  dialogVisible.value = true
}

async function submitForm() {
  const valid = await formRef.value?.validate().catch(() => false)
  if (!valid) return
  submitting.value = true
  try {
    const url = form.id ? '/admin/equipment/edit' : '/admin/equipment/add'
    await apiPost(url, { ...form })
    ElMessage.success(form.id ? '修改成功' : '添加成功')
    dialogVisible.value = false
    loadData()
  } finally { submitting.value = false }
}

async function handleDelete(row: any) {
  try {
    await ElMessageBox.confirm(`确定删除设备 "${row.name}" 吗？`, '提示', { type: 'warning' })
    await apiPost('/admin/equipment/delete', { id: row.id })
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
