<template>
  <div class="page-container">
    <el-card shadow="never">
      <template #header>
        <div class="card-header">
          <span>排班管理</span>
          <div>
            <el-button type="primary" size="small" @click="openBatch">批量排班</el-button>
            <el-button type="success" size="small" @click="openForm()">新增排班</el-button>
          </div>
        </div>
      </template>

      <el-form :model="query" inline>
        <el-form-item><el-select v-model="query.community_id" placeholder="小区" clearable style="width:150px;" @change="onCommunityChange"><el-option v-for="c in communities" :key="c.id" :label="c.name" :value="c.id" /></el-select></el-form-item>
        <el-form-item><el-input v-model="query.keyword" placeholder="姓名/工号" clearable style="width:180px;" /></el-form-item>
        <el-form-item><el-date-picker v-model="query.date_start" type="date" placeholder="开始日期" value-format="YYYY-MM-DD" style="width:140px;" /></el-form-item>
        <el-form-item><el-date-picker v-model="query.date_end" type="date" placeholder="结束日期" value-format="YYYY-MM-DD" style="width:140px;" /></el-form-item>
        <el-form-item><el-select v-model="query.shift" placeholder="班次" clearable style="width:110px;"><el-option label="早班" value="早班" /><el-option label="中班" value="中班" /><el-option label="晚班" value="晚班" /></el-select></el-form-item>
        <el-form-item><el-button type="primary" @click="loadData">查询</el-button><el-button @click="resetQuery">重置</el-button></el-form-item>
      </el-form>

      <el-table :data="list" v-loading="loading" stripe border style="width:100%;">
        <el-table-column prop="job_no" label="工号" width="90" />
        <el-table-column prop="staff_name" label="姓名" width="90" />
        <el-table-column prop="community_name" label="小区" width="120" />
        <el-table-column prop="schedule_date" label="日期" width="110" />
        <el-table-column prop="shift" label="班次" width="80"><template #default="{row}"><el-tag :type="row.shift==='早班'?'success':row.shift==='中班'?'warning':'info'">{{ row.shift }}</el-tag></template></el-table-column>
        <el-table-column prop="start_time" label="上班时间" width="100" />
        <el-table-column prop="end_time" label="下班时间" width="100" />
        <el-table-column prop="work_area" label="工作区域" width="130" show-overflow-tooltip />
        <el-table-column prop="remark" label="备注" show-overflow-tooltip />
        <el-table-column fixed="right" label="操作" width="130"><template #default="{row}">
          <el-button size="small" type="primary" link @click="openForm(row)">编辑</el-button>
          <el-popconfirm title="确定删除?" @confirm="handleDelete(row.id)"><template #reference><el-button size="small" type="danger" link>删除</el-button></template></el-popconfirm>
        </template></el-table-column>
      </el-table>
      <div style="margin-top:16px;text-align:right;"><el-pagination v-model:current-page="query.page" v-model:page-size="query.limit" :total="total" :page-sizes="[10,15,30]" layout="total,sizes,prev,pager,next" @current-change="loadData" @size-change="loadData" /></div>
    </el-card>

    <el-dialog v-model="dialogVisible" :title="formTitle" width="480px" destroy-on-close>
      <el-form :model="form" :rules="rules" ref="formRef" label-width="100px">
        <el-form-item label="员工" prop="staff_id"><el-select v-model="form.staff_id" filterable placeholder="选择员工" style="width:100%;"><el-option v-for="s in filteredStaff" :key="s.id" :label="s.realname+'('+s.job_no+')'" :value="s.id" /></el-select></el-form-item>
        <el-form-item label="日期" prop="schedule_date"><el-date-picker v-model="form.schedule_date" type="date" style="width:100%;" value-format="YYYY-MM-DD" /></el-form-item>
        <el-form-item label="班次" prop="shift"><el-select v-model="form.shift" style="width:100%;"><el-option label="早班" value="早班" /><el-option label="中班" value="中班" /><el-option label="晚班" value="晚班" /></el-select></el-form-item>
        <el-form-item label="上班时间"><el-time-picker v-model="form.start_time" format="HH:mm" value-format="HH:mm" style="width:100%;" /></el-form-item>
        <el-form-item label="下班时间"><el-time-picker v-model="form.end_time" format="HH:mm" value-format="HH:mm" style="width:100%;" /></el-form-item>
        <el-form-item label="工作区域"><el-input v-model="form.work_area" placeholder="如:大门岗/巡逻岗/中控室" /></el-form-item>
        <el-form-item label="备注"><el-input v-model="form.remark" type="textarea" rows="2" /></el-form-item>
      </el-form>
      <template #footer><el-button @click="dialogVisible=false">取消</el-button><el-button type="primary" @click="submitForm" :loading="submitting">确定</el-button></template>
    </el-dialog>

    <el-dialog v-model="batchVisible" title="批量排班" width="520px" destroy-on-close>
      <el-form :model="batchForm" label-width="100px">
        <el-form-item label="选择员工"><el-select v-model="batchForm.staff_ids" multiple filterable placeholder="选择员工" style="width:100%;"><el-option v-for="s in filteredStaff" :key="s.id" :label="s.realname+'('+s.job_no+')'" :value="s.id" /></el-select></el-form-item>
        <el-form-item label="日期范围">
          <el-row :gutter="10" style="width:100%;"><el-col :span="12"><el-date-picker v-model="batchForm.date_start" type="date" placeholder="开始" value-format="YYYY-MM-DD" style="width:100%;" /></el-col><el-col :span="12"><el-date-picker v-model="batchForm.date_end" type="date" placeholder="结束" value-format="YYYY-MM-DD" style="width:100%;" /></el-col></el-row>
        </el-form-item>
        <el-form-item label="班次"><el-select v-model="batchForm.shift" style="width:100%;"><el-option label="早班" value="早班" /><el-option label="中班" value="中班" /><el-option label="晚班" value="晚班" /></el-select></el-form-item>
      </el-form>
      <template #footer><el-button @click="batchVisible=false">取消</el-button><el-button type="primary" @click="submitBatch" :loading="submitting">确定</el-button></template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { apiGet, apiPost } from '@/utils/request'

const filteredStaff = computed(() => {
  if (!query.community_id) return staffList.value
  return staffList.value.filter((s: any) => s.community_id == query.community_id)
})

const list = ref<any[]>([])
const total = ref(0)
const loading = ref(false)
const dialogVisible = ref(false)
const batchVisible = ref(false)
const submitting = ref(false)
const formRef = ref<any>(null)
const formTitle = ref('新增排班')
const staffList = ref<any[]>([])
const communities = ref<any[]>([])

const query = reactive({ community_id: undefined as any, keyword: '', date_start: '', date_end: '', shift: '', page: 1, limit: 15 })
const form = reactive<any>({ id: 0, staff_id: '', schedule_date: '', shift: '早班', start_time: '', end_time: '', work_area: '', remark: '' })
const batchForm = reactive({ staff_ids: [] as any[], date_start: '', date_end: '', shift: '早班' })
const rules = { staff_id: [{ required: true, message: '请选择员工' }], schedule_date: [{ required: true, message: '请选择日期' }], shift: [{ required: true, message: '请选择班次' }] }

function resetQuery() { Object.assign(query, { community_id: undefined, keyword: '', date_start: '', date_end: '', shift: '', page: 1 }); loadData() }

function onCommunityChange() { loadStaffList(); loadData() }

async function loadStaffList() {
  const params: any = { page: 1, limit: 500 }
  if (query.community_id) params.community_id = query.community_id
  try { const r = await apiGet('/admin/Staff/lists', params); staffList.value = r.data.list || r.data } catch {}
}

async function loadData() {
  loading.value = true
  try {
    const res = await apiGet('/admin/Schedule/lists', { ...query })
    list.value = res.data.list || res.data
    total.value = res.data.total || list.value.length
  } catch { list.value = []; total.value = 0 } finally { loading.value = false }
}

function openForm(row?: any) {
  formTitle.value = row ? '编辑排班' : '新增排班'
  Object.assign(form, row || { id: 0, staff_id: '', schedule_date: '', shift: '早班', start_time: '', end_time: '', work_area: '', remark: '' })
  dialogVisible.value = true
}

async function submitForm() {
  const valid = await formRef.value?.validate().catch(() => false)
  if (!valid) return
  submitting.value = true
  try {
    const url = form.id ? '/admin/Schedule/edit' : '/admin/Schedule/add'
    await apiPost(url, { ...form })
    ElMessage.success(form.id ? '修改成功' : '添加成功')
    dialogVisible.value = false; loadData()
  } finally { submitting.value = false }
}

function openBatch() { batchForm.date_start = ''; batchForm.date_end = ''; batchForm.staff_ids = []; batchVisible.value = true }

async function submitBatch() {
  if (!batchForm.staff_ids.length) { ElMessage.warning('请选择员工'); return }
  if (!batchForm.date_start || !batchForm.date_end) { ElMessage.warning('请选择日期范围'); return }
  submitting.value = true
  try { await apiPost('/admin/Schedule/batch', { ...batchForm }); ElMessage.success('批量排班成功'); batchVisible.value = false; loadData() } finally { submitting.value = false }
}

async function handleDelete(id: number) { await apiPost('/admin/Schedule/delete', { id }); ElMessage.success('删除成功'); loadData() }

onMounted(async () => {
  try { const r = await apiGet('/admin/Community/lists', {}); communities.value = r.data.list || r.data } catch {}
  try { const r = await apiGet('/admin/Staff/lists', { page: 1, limit: 500 }); staffList.value = r.data.list || r.data } catch {}
  loadData()
})
</script>
