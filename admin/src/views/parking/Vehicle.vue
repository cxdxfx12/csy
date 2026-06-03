<template>
  <div class="page-container">
    <div class="search-bar">
      <el-form :model="query" inline>
        <el-form-item><el-input v-model="query.keyword" placeholder="车牌/品牌/型号" clearable style="width:200px;" /></el-form-item>
        <el-form-item><el-select v-model="query.community_id" placeholder="小区" clearable style="width:160px;"><el-option v-for="c in communities" :key="c.id" :label="c.name" :value="c.id" /></el-select></el-form-item>
        <el-form-item>
          <el-button type="primary" @click="loadData">搜索</el-button>
          <el-button @click="resetQuery">重置</el-button>
        </el-form-item>
      </el-form>
    </div>
    <el-card shadow="never" class="table-card">
      <div class="table-toolbar"><el-button type="primary" @click="openForm()">添加车辆</el-button></div>
      <el-table :data="list" v-loading="loading" stripe border>
        <el-table-column type="index" label="#" width="50" />
        <el-table-column prop="id" label="ID" width="60" />
        <el-table-column prop="plate_number" label="车牌号" width="120" />
        <el-table-column prop="owner_name" label="车主" width="100" />
        <el-table-column prop="brand" label="品牌" width="120" />
        <el-table-column prop="model" label="型号" width="120" />
        <el-table-column prop="color" label="颜色" width="80" />
        <el-table-column prop="space_no" label="绑定车位" width="120" />
        <el-table-column prop="create_time" label="登记时间" width="170" />
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
        <el-form-item label="车牌号" prop="plate_number"><el-input v-model="form.plate_number" placeholder="如：京A12345" /></el-form-item>
        <el-form-item label="车主"><el-select v-model="form.owner_id" placeholder="选择业主" clearable style="width:100%;"><el-option v-for="o in owners" :key="o.id" :label="o.realname" :value="o.id" /></el-select></el-form-item>
        <el-form-item label="品牌"><el-input v-model="form.brand" placeholder="车辆品牌" /></el-form-item>
        <el-form-item label="型号"><el-input v-model="form.model" placeholder="车辆型号" /></el-form-item>
        <el-form-item label="颜色"><el-input v-model="form.color" placeholder="车辆颜色" /></el-form-item>
        <el-form-item label="绑定车位"><el-select v-model="form.parking_space_id" placeholder="选择车位" clearable style="width:100%;"><el-option v-for="s in spaces" :key="s.id" :label="s.space_no" :value="s.id" /></el-select></el-form-item>
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
const formTitle = ref('添加车辆')
const communities = ref<any[]>([])
const owners = ref<any[]>([])
const spaces = ref<any[]>([])

const query = reactive({ keyword: '', community_id: undefined as any, page: 1, limit: 15 })
const form = reactive<any>({ id: 0, community_id: '', plate_number: '', owner_id: undefined, brand: '', model: '', color: '', parking_space_id: undefined })
const rules = { plate_number: [{ required: true, message: '请输入车牌号', trigger: 'blur' }], community_id: [{ required: true, message: '请选择小区', trigger: 'change' }] }

function resetQuery() { query.keyword = ''; query.community_id = undefined; query.page = 1; loadData() }

async function loadData() {
  loading.value = true
  try {
    const r = await apiGet('/admin/parking/vehicleList', { ...query })
    list.value = r.data?.list || r.data || []
    total.value = r.count || r.data?.total || list.value.length
  } catch { list.value = []; total.value = 0 }
  finally { loading.value = false }
}

function openForm(row?: any) {
  formTitle.value = row ? '编辑车辆' : '添加车辆'
  Object.assign(form, row || { id: 0, community_id: '', plate_number: '', owner_id: undefined, brand: '', model: '', color: '', parking_space_id: undefined })
  dialogVisible.value = true
}

async function submitForm() {
  const valid = await formRef.value?.validate().catch(() => false)
  if (!valid) return
  submitting.value = true
  try {
    const url = form.id ? '/admin/parking/vehicleEdit' : '/admin/parking/vehicleAdd'
    await apiPost(url, { ...form })
    ElMessage.success(form.id ? '修改成功' : '添加成功')
    dialogVisible.value = false
    loadData()
  } finally { submitting.value = false }
}

async function handleDelete(row: any) {
  try {
    await ElMessageBox.confirm(`确定删除车辆 "${row.plate_number}" 吗？`, '提示', { type: 'warning' })
    await apiPost('/admin/parking/vehicleDelete', { id: row.id })
    ElMessage.success('删除成功')
    loadData()
  } catch {}
}

onMounted(async () => {
  try {
    const rc = await apiGet('/admin/community/list', { limit: 999 })
    communities.value = rc.data?.list || rc.data || []
    const ro = await apiGet('/admin/owner/list', { limit: 999 })
    owners.value = ro.data?.list || ro.data || []
    const rs = await apiGet('/admin/parking/spaceList', { limit: 999 })
    spaces.value = rs.data?.list || rs.data || []
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
