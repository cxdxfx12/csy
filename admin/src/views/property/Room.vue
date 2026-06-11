<template>
  <div class="page-container">
    <div class="search-bar">
      <el-form :model="query" inline>
        <el-form-item><el-input v-model="query.keyword" placeholder="房间号" clearable style="width:180px;" /></el-form-item>
        <el-form-item><el-select v-model="query.community_id" placeholder="小区" clearable style="width:160px;"><el-option v-for="c in communities" :key="c.id" :label="c.name" :value="c.id" /></el-select></el-form-item>
        <el-form-item><el-select v-model="query.building_id" placeholder="楼栋" clearable style="width:160px;"><el-option v-for="b in buildings" :key="b.id" :label="b.name" :value="b.id" /></el-select></el-form-item>
        <el-form-item>
          <el-button type="primary" @click="loadData">搜索</el-button>
          <el-button @click="resetQuery">重置</el-button>
          <el-button type="success" @click="batchDialogVisible = true">批量生成</el-button>
        </el-form-item>
      </el-form>
    </div>
    <el-card shadow="never" class="table-card">
      <div class="table-toolbar"><el-button type="primary" @click="openForm()">添加房间</el-button></div>
      <el-table :data="list" v-loading="loading" stripe border>
        <el-table-column type="index" label="#" width="50" />
        <el-table-column prop="id" label="ID" width="60" />
        <el-table-column prop="room_number" label="房间号" width="120" />
        <el-table-column prop="community_name" label="小区" width="140" />
        <el-table-column prop="building_name" label="楼栋" width="100" />
        <el-table-column prop="unit" label="单元" width="80" />
        <el-table-column prop="floor" label="楼层" width="70" />
        <el-table-column prop="area" label="面积" width="80" />
        <el-table-column prop="layout" label="户型" width="100" />
        <el-table-column prop="status" label="状态" width="90">
          <template #default="{row}">
            <el-tag :type="row.status===1?'info':row.status===2?'success':'warning'">{{ statusMap[row.status]||'未知' }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="owner_name" label="业主" width="120" />
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
        <el-form-item label="所属楼栋" prop="building_id"><el-select v-model="form.building_id" placeholder="选择楼栋" style="width:100%;" :disabled="!!form.id"><el-option v-for="b in buildings" :key="b.id" :label="b.name" :value="b.id" /></el-select></el-form-item>
        <el-form-item label="房间号" prop="room_number"><el-input v-model="form.room_number" placeholder="如 101" :disabled="!!form.id" /></el-form-item>
        <el-form-item label="单元"><el-input v-model="form.unit" placeholder="如 1单元" :disabled="!!form.id" /></el-form-item>
        <el-form-item label="楼层"><el-input-number v-model="form.floor" :min="1" style="width:100%;" :disabled="!!form.id" /></el-form-item>
        <el-form-item label="面积"><el-input-number v-model="form.area" :min="0" :precision="2" style="width:100%;" /></el-form-item>
        <el-form-item label="户型"><el-input v-model="form.layout" placeholder="如 三室两厅" /></el-form-item>
        <el-form-item label="状态">
          <el-select v-model="form.status" style="width:100%;">
            <el-option label="空置" :value="1" />
            <el-option label="已售" :value="2" />
            <el-option label="已租" :value="3" />
          </el-select>
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" @click="submitForm" :loading="submitting">确定</el-button>
      </template>
    </el-dialog>

    <el-dialog v-model="batchDialogVisible" title="批量生成房间" width="480px" destroy-on-close>
      <el-form :model="batchForm" ref="batchFormRef" label-width="100px">
        <el-form-item label="所属楼栋" prop="building_id"><el-select v-model="batchForm.building_id" placeholder="选择楼栋" style="width:100%;"><el-option v-for="b in buildings" :key="b.id" :label="b.name" :value="b.id" /></el-select></el-form-item>
        <el-form-item label="起始楼层"><el-input-number v-model="batchForm.start_floor" :min="1" style="width:100%;" /></el-form-item>
        <el-form-item label="结束楼层"><el-input-number v-model="batchForm.end_floor" :min="1" style="width:100%;" /></el-form-item>
        <el-form-item label="每层户数"><el-input-number v-model="batchForm.rooms_per_floor" :min="1" style="width:100%;" /></el-form-item>
        <el-form-item label="单元数"><el-input-number v-model="batchForm.unit_count" :min="1" style="width:100%;" /></el-form-item>
        <el-form-item label="前缀"><el-input v-model="batchForm.prefix" placeholder="房间号前缀" /></el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="batchDialogVisible = false">取消</el-button>
        <el-button type="primary" @click="submitBatch" :loading="batchSubmitting">确定</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, watch } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { apiGet, apiPost } from '@/utils/request'

const list = ref<any[]>([])
const total = ref(0)
const loading = ref(false)
const dialogVisible = ref(false)
const batchDialogVisible = ref(false)
const submitting = ref(false)
const batchSubmitting = ref(false)
const formRef = ref<any>(null)
const batchFormRef = ref<any>(null)
const formTitle = ref('添加房间')
const communities = ref<any[]>([])
const buildings = ref<any[]>([])
const allBuildings = ref<any[]>([])
const statusMap: Record<number, string> = { 1: '空置', 2: '已售', 3: '已租' }

const query = reactive({ keyword: '', community_id: undefined as any, building_id: undefined as any, page: 1, limit: 15 })
const form = reactive<any>({ id: 0, building_id: '', room_number: '', unit: '', floor: 1, area: 0, layout: '', status: 1 })
const batchForm = reactive({ building_id: '', start_floor: 1, end_floor: 1, rooms_per_floor: 2, unit_count: 1, prefix: '' })
const rules = { room_number: [{ required: true, message: '请输入房间号', trigger: 'blur' }], building_id: [{ required: true, message: '请选择楼栋', trigger: 'change' }] }

function resetQuery() { query.keyword = ''; query.community_id = undefined; query.building_id = undefined; query.page = 1; loadData() }

async function loadData() {
  loading.value = true
  try {
    const r = await apiGet('/admin/room/list', { ...query })
    list.value = r.data?.list || r.data || []
    total.value = r.count || r.data?.total || list.value.length
  } catch { list.value = []; total.value = 0 }
  finally { loading.value = false }
}

function openForm(row?: any) {
  formTitle.value = row ? '编辑房间' : '添加房间'
  Object.assign(form, row || { id: 0, building_id: '', room_number: '', unit: '', floor: 1, area: 0, layout: '', status: 1 })
  dialogVisible.value = true
}

async function submitForm() {
  try { await formRef.value?.validate() } catch { return }
  submitting.value = true
  try {
    const url = form.id ? '/admin/room/edit' : '/admin/room/add'
    await apiPost(url, { ...form })
    ElMessage.success(form.id ? '修改成功' : '添加成功')
    dialogVisible.value = false
    loadData()
  } finally { submitting.value = false }
}

async function submitBatch() {
  if (!batchForm.building_id) { ElMessage.warning('请选择楼栋'); return }
  batchSubmitting.value = true
  try {
    const r = await apiPost('/admin/room/batchAdd', { ...batchForm })
    ElMessage.success(r.msg || '批量生成成功')
    batchDialogVisible.value = false
    loadData()
  } finally { batchSubmitting.value = false }
}

async function handleDelete(row: any) {
  try {
    await ElMessageBox.confirm(`确定删除房间 "${row.room_number}" 吗？`, '提示', { type: 'warning' })
    await apiPost('/admin/room/delete', { id: row.id })
    ElMessage.success('删除成功')
    loadData()
  } catch {}
}

watch(() => query.community_id, (val) => {
  buildings.value = val ? allBuildings.value.filter((b: any) => b.community_id == val) : [...allBuildings.value]
  query.building_id = undefined
})

onMounted(async () => {
  try {
    const rc = await apiGet('/admin/community/list', { limit: 999 })
    communities.value = rc.data?.list || rc.data || []
    const rb = await apiGet('/admin/building/list', { limit: 999 })
    allBuildings.value = rb.data?.list || rb.data || []
    buildings.value = [...allBuildings.value]
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
