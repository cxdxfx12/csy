<template>
  <div class="page-container">
    <div class="search-bar">
      <el-form :model="query" inline>
        <el-form-item><el-input v-model="query.keyword" placeholder="房间号/表号/抄表人" clearable style="width:200px;" /></el-form-item>
        <el-form-item><el-select v-model="query.community_id" placeholder="小区" clearable style="width:160px;"><el-option v-for="c in communities" :key="c.id" :label="c.name" :value="c.id" /></el-select></el-form-item>
        <el-form-item><el-select v-model="query.type" placeholder="表类型" clearable style="width:120px;"><el-option label="水表" :value="1" /><el-option label="电表" :value="2" /><el-option label="燃气表" :value="3" /></el-select></el-form-item>
        <el-form-item><el-date-picker v-model="query.date_range" type="daterange" range-separator="至" start-placeholder="开始日期" end-placeholder="结束日期" value-format="YYYY-MM-DD" style="width:240px;" /></el-form-item>
        <el-form-item>
          <el-button type="primary" @click="loadData">搜索</el-button>
          <el-button @click="resetQuery">重置</el-button>
        </el-form-item>
      </el-form>
    </div>
    <el-card shadow="never" class="table-card">
      <div class="table-toolbar"><el-button type="primary" @click="openForm()">添加抄表</el-button></div>
      <el-table :data="list" v-loading="loading" stripe border>
        <el-table-column type="index" label="#" width="50" />
        <el-table-column prop="reading_date" label="抄表日期" width="110" />
        <el-table-column prop="room_number" label="房间号" width="100" />
        <el-table-column prop="building_name" label="楼栋" width="100" />
        <el-table-column prop="meter_no" label="表号" width="100" />
        <el-table-column prop="type" label="类型" width="80"><template #default="{row}">{{ typeMap[row.type]||row.type }}</template></el-table-column>
        <el-table-column prop="previous_reading" label="上期读数" width="100" />
        <el-table-column prop="current_reading" label="本期读数" width="100" />
        <el-table-column prop="usage_amount" label="用量" width="80" />
        <el-table-column prop="reading_by" label="抄表人" width="90" />
        <el-table-column prop="status" label="状态" width="80"><template #default="{row}"><el-tag :type="row.status===2?'danger':'success'" size="small">{{ row.status===2?'异常':'正常' }}</el-tag></template></el-table-column>
        <el-table-column prop="remark" label="备注" min-width="120" show-overflow-tooltip />
        <el-table-column label="操作" width="160" fixed="right">
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

    <el-dialog v-model="dialogVisible" :title="formTitle" width="560px" destroy-on-close @open="onDialogOpen">
      <el-form :model="form" :rules="rules" ref="formRef" label-width="100px">
        <el-form-item label="小区" prop="community_id"><el-select v-model="form.community_id" placeholder="选择小区" style="width:100%;" @change="onCommunityChange"><el-option v-for="c in communities" :key="c.id" :label="c.name" :value="c.id" /></el-select></el-form-item>
        <el-form-item label="房间" prop="room_id"><el-select v-model="form.room_id" placeholder="选择房间" filterable style="width:100%;" :loading="roomsLoading"><el-option v-for="r in rooms" :key="r.id" :label="r.building_name + ' - ' + r.room_number" :value="r.id" /></el-select></el-form-item>
        <el-form-item label="表类型" prop="type"><el-select v-model="form.type" placeholder="选择类型" style="width:100%;"><el-option label="水表" :value="1" /><el-option label="电表" :value="2" /><el-option label="燃气表" :value="3" /></el-select></el-form-item>
        <el-form-item label="表号"><el-input v-model="form.meter_no" placeholder="如 WH-001" maxlength="100" /></el-form-item>
        <el-row :gutter="16">
          <el-col :span="12"><el-form-item label="上期读数" prop="previous_reading"><el-input-number v-model="form.previous_reading" :min="0" :precision="2" style="width:100%;" /></el-form-item></el-col>
          <el-col :span="12"><el-form-item label="本期读数" prop="current_reading"><el-input-number v-model="form.current_reading" :min="0" :precision="2" style="width:100%;" /></el-form-item></el-col>
        </el-row>
        <el-form-item label="用量"><el-input :model-value="computedUsage" readonly placeholder="自动计算" /><span style="margin-left:8px;color:#909399;white-space:nowrap;">= 本期 - 上期</span></el-form-item>
        <el-form-item label="抄表日期" prop="reading_date"><el-date-picker v-model="form.reading_date" type="date" placeholder="选择日期" value-format="YYYY-MM-DD" style="width:100%;" /></el-form-item>
        <el-form-item label="抄表人"><el-input v-model="form.reading_by" placeholder="抄表人姓名" maxlength="100" /></el-form-item>
        <el-form-item label="状态"><el-radio-group v-model="form.status"><el-radio :value="1">正常</el-radio><el-radio :value="2">异常</el-radio></el-radio-group></el-form-item>
        <el-form-item label="备注"><el-input v-model="form.remark" type="textarea" :rows="2" placeholder="异常原因等" maxlength="500" show-word-limit /></el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" @click="submitForm" :loading="submitting">确定</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, computed } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { apiGet, apiPost } from '@/utils/request'

const list = ref<any[]>([])
const total = ref(0)
const loading = ref(false)
const dialogVisible = ref(false)
const submitting = ref(false)
const formRef = ref<any>(null)
const formTitle = ref('添加抄表')
const communities = ref<any[]>([])
const rooms = ref<any[]>([])
const roomsLoading = ref(false)

const typeMap: Record<number, string> = { 1: '水表', 2: '电表', 3: '燃气表' }

const query = reactive({ keyword: '', community_id: undefined as any, type: undefined as any, date_range: null as any, page: 1, limit: 15 })
const form = reactive<any>({ id: 0, community_id: '', room_id: '', type: 1, meter_no: '', previous_reading: 0, current_reading: 0, reading_date: '', reading_by: '', status: 1, remark: '' })
const rules = {
  community_id: [{ required: true, message: '请选择小区', trigger: 'change' }],
  room_id: [{ required: true, message: '请选择房间', trigger: 'change' }],
  type: [{ required: true, message: '请选择类型', trigger: 'change' }],
  previous_reading: [{ required: true, message: '请输入上期读数', trigger: 'blur' }],
  current_reading: [{ required: true, message: '请输入本期读数', trigger: 'blur' }],
  reading_date: [{ required: true, message: '请选择抄表日期', trigger: 'change' }],
}

const computedUsage = computed(() => {
  const curr = Number(form.current_reading) || 0
  const prev = Number(form.previous_reading) || 0
  return (curr - prev).toFixed(2)
})

function resetQuery() {
  query.keyword = ''
  query.community_id = undefined
  query.type = undefined
  query.date_range = null
  query.page = 1
  loadData()
}

function buildQuery() {
  const q: any = { ...query }
  if (q.date_range && q.date_range.length === 2) {
    q.date_start = q.date_range[0]
    q.date_end = q.date_range[1]
  }
  delete q.date_range
  return q
}

async function loadData() {
  loading.value = true
  try {
    const r = await apiGet('/admin/charge/meterList', buildQuery())
    list.value = r.data?.list || r.data || []
    total.value = r.count || r.data?.total || list.value.length
  } catch { list.value = []; total.value = 0 }
  finally { loading.value = false }
}

function onDialogOpen() {
  // 打开对话框时，如果还没加载房间就加载全部
  if (rooms.value.length === 0) {
    loadRooms(0)
  }
}

function openForm(row?: any) {
  formTitle.value = row ? '编辑抄表' : '添加抄表'
  if (row) {
    Object.assign(form, {
      id: row.id, community_id: row.community_id || row.room_community_id || '',
      room_id: row.room_id, type: row.type, meter_no: row.meter_no || '',
      previous_reading: row.previous_reading, current_reading: row.current_reading,
      reading_date: row.reading_date || '', reading_by: row.reading_by || '',
      status: row.status || 1, remark: row.remark || '',
    })
    // 编辑时加载对应小区的房间
    if (form.community_id) loadRooms(form.community_id)
  } else {
    const defaults = { id: 0, community_id: '', room_id: '', type: 1, meter_no: '', previous_reading: 0, current_reading: 0, reading_date: '', reading_by: '', status: 1, remark: '' }
    Object.assign(form, defaults)
    rooms.value = []
  }
  dialogVisible.value = true
}

async function loadRooms(communityId: number) {
  roomsLoading.value = true
  try {
    const rr = await apiGet('/admin/room/select', { community_id: communityId || '' })
    rooms.value = rr.data || []
  } finally { roomsLoading.value = false }
}

function onCommunityChange(id: number) {
  form.room_id = ''
  if (id) {
    loadRooms(id)
  } else {
    rooms.value = []
  }
}

async function submitForm() {
  const valid = await formRef.value?.validate().catch(() => false)
  if (!valid) return
  submitting.value = true
  try {
    const url = form.id ? '/admin/charge/meterEdit' : '/admin/charge/meterAdd'
    await apiPost(url, { ...form })
    ElMessage.success(form.id ? '修改成功' : '添加成功')
    dialogVisible.value = false
    loadData()
  } finally { submitting.value = false }
}

async function handleDelete(row: any) {
  try {
    await ElMessageBox.confirm('确定删除该抄表记录吗？', '提示', { type: 'warning' })
    await apiPost('/admin/charge/meterDelete', { id: row.id })
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
</script>

<style scoped>
.search-bar { background:#fff;border-radius:8px;padding:16px 20px;margin-bottom:16px;border:1px solid #e2e8f0; }
.table-card { border-radius:8px;border:1px solid #e2e8f0; }
.table-toolbar { margin-bottom:16px; }
.pagination { margin-top:16px;display:flex;justify-content:flex-end; }
</style>
