<template>
  <div class="page-container">
    <el-card shadow="never">
      <template #header>
        <div class="card-header">
          <span>考勤管理</span>
          <div>
            <el-button type="primary" size="small" @click="openBatch">批量打卡</el-button>
            <el-button type="success" size="small" @click="openForm()">新增记录</el-button>
          </div>
        </div>
      </template>

      <el-form :model="query" inline>
        <el-form-item><el-select v-model="query.community_id" placeholder="小区" clearable style="width:150px;" @change="onCommunityChange"><el-option v-for="c in communities" :key="c.id" :label="c.name" :value="c.id" /></el-select></el-form-item>
        <el-form-item><el-input v-model="query.keyword" placeholder="姓名/工号" clearable style="width:180px;" /></el-form-item>
        <el-form-item><el-date-picker v-model="query.date_start" type="date" placeholder="开始日期" value-format="YYYY-MM-DD" style="width:140px;" /></el-form-item>
        <el-form-item><el-date-picker v-model="query.date_end" type="date" placeholder="结束日期" value-format="YYYY-MM-DD" style="width:140px;" /></el-form-item>
        <el-form-item><el-select v-model="query.type" placeholder="类型" clearable style="width:110px;"><el-option label="出勤" :value="1" /><el-option label="迟到" :value="2" /><el-option label="早退" :value="3" /><el-option label="请假" :value="4" /><el-option label="旷工" :value="5" /></el-select></el-form-item>
        <el-form-item><el-button type="primary" @click="loadData">查询</el-button><el-button @click="resetQuery">重置</el-button></el-form-item>
      </el-form>

      <el-table :data="list" v-loading="loading" stripe border style="width:100%;">
        <el-table-column prop="job_no" label="工号" width="90" />
        <el-table-column prop="staff_name" label="姓名" width="90" />
        <el-table-column prop="community_name" label="小区" width="120" />
        <el-table-column prop="attendance_date" label="日期" width="110" />
        <el-table-column prop="sign_in_time" label="签到时间" width="110" />
        <el-table-column prop="sign_out_time" label="签退时间" width="110" />
        <el-table-column prop="type" label="考勤状态" width="100"><template #default="{row}"><el-tag :type="typeTag[row.type]||'info'">{{ typeMap[row.type]||'未知' }}</el-tag></template></el-table-column>
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
        <el-form-item label="日期" prop="attendance_date"><el-date-picker v-model="form.attendance_date" type="date" style="width:100%;" value-format="YYYY-MM-DD" /></el-form-item>
        <el-form-item label="签到时间"><el-time-picker v-model="form.sign_in_time" format="HH:mm" value-format="HH:mm" style="width:100%;" /></el-form-item>
        <el-form-item label="签退时间"><el-time-picker v-model="form.sign_out_time" format="HH:mm" value-format="HH:mm" style="width:100%;" /></el-form-item>
        <el-form-item label="状态" prop="type"><el-select v-model="form.type" style="width:100%;"><el-option label="出勤" :value="1" /><el-option label="迟到" :value="2" /><el-option label="早退" :value="3" /><el-option label="请假" :value="4" /><el-option label="旷工" :value="5" /></el-select></el-form-item>
        <el-form-item label="备注"><el-input v-model="form.remark" type="textarea" rows="2" /></el-form-item>
      </el-form>
      <template #footer><el-button @click="dialogVisible=false">取消</el-button><el-button type="primary" @click="submitForm" :loading="submitting">确定</el-button></template>
    </el-dialog>

    <el-dialog v-model="batchVisible" title="批量打卡" width="480px" destroy-on-close>
      <el-form :model="batchForm" label-width="100px">
        <el-form-item label="选择员工"><el-select v-model="batchForm.staff_ids" multiple filterable placeholder="选择员工" style="width:100%;"><el-option v-for="s in filteredStaff" :key="s.id" :label="s.realname+'('+s.job_no+')'" :value="s.id" /></el-select></el-form-item>
        <el-form-item label="日期"><el-date-picker v-model="batchForm.attendance_date" type="date" style="width:100%;" value-format="YYYY-MM-DD" /></el-form-item>
        <el-form-item label="考勤状态"><el-select v-model="batchForm.type" style="width:100%;"><el-option label="出勤" :value="1" /><el-option label="迟到" :value="2" /><el-option label="早退" :value="3" /><el-option label="请假" :value="4" /></el-select></el-form-item>
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
const formTitle = ref('新增考勤')
const staffList = ref<any[]>([])
const communities = ref<any[]>([])
const typeMap: Record<number,string> = { 1: '出勤', 2: '迟到', 3: '早退', 4: '请假', 5: '旷工' }
const typeTag: Record<number,string> = { 1: 'success', 2: 'warning', 3: 'warning', 4: 'info', 5: 'danger' }

const query = reactive({ community_id: undefined as any, keyword: '', date_start: '', date_end: '', type: undefined as any, page: 1, limit: 15 })
const form = reactive<any>({ id: 0, staff_id: '', attendance_date: '', sign_in_time: '', sign_out_time: '', type: 1, remark: '' })
const batchForm = reactive({ staff_ids: [] as any[], attendance_date: '', type: 1 })
const rules = { staff_id: [{ required: true, message: '请选择员工' }], attendance_date: [{ required: true, message: '请选择日期' }], type: [{ required: true, message: '请选择状态' }] }

function resetQuery() { Object.assign(query, { community_id: undefined, keyword: '', date_start: '', date_end: '', type: undefined, page: 1 }); loadData() }

// 小区切换时重新加载员工列表
function onCommunityChange() {
  loadStaffList()
  loadData()
}

async function loadStaffList() {
  const params: any = { page: 1, limit: 500 }
  if (query.community_id) params.community_id = query.community_id
  try { const r = await apiGet('/admin/staff/lists', params); staffList.value = r.data.list || r.data } catch {}
}

async function loadData() {
  loading.value = true
  try {
    const res = await apiGet('/admin/attendance/lists', { ...query })
    list.value = res.data.list || res.data
    total.value = res.data.total || list.value.length
  } catch { list.value = []; total.value = 0 } finally { loading.value = false }
}

function openForm(row?: any) {
  formTitle.value = row ? '编辑考勤' : '新增考勤'
  Object.assign(form, row || { id: 0, staff_id: '', attendance_date: '', sign_in_time: '', sign_out_time: '', type: 1, remark: '' })
  dialogVisible.value = true
}

async function submitForm() {
  const valid = await formRef.value?.validate().catch(() => false)
  if (!valid) return
  submitting.value = true
  try {
    const url = form.id ? '/admin/attendance/edit' : '/admin/attendance/add'
    await apiPost(url, { ...form })
    ElMessage.success(form.id ? '修改成功' : '添加成功')
    dialogVisible.value = false; loadData()
  } finally { submitting.value = false }
}

function openBatch() { batchForm.attendance_date = ''; batchForm.staff_ids = []; batchVisible.value = true }

async function submitBatch() {
  if (!batchForm.staff_ids.length) { ElMessage.warning('请选择员工'); return }
  if (!batchForm.attendance_date) { ElMessage.warning('请选择日期'); return }
  submitting.value = true
  try { await apiPost('/admin/attendance/batch', { ...batchForm }); ElMessage.success('批量打卡成功'); batchVisible.value = false; loadData() } finally { submitting.value = false }
}

async function handleDelete(id: number) { await apiPost('/admin/attendance/delete', { id }); ElMessage.success('删除成功'); loadData() }

onMounted(async () => {
  try { const r = await apiGet('/admin/community/list', {}); communities.value = r.data.list || r.data } catch {}
  try { const r = await apiGet('/admin/staff/lists', { page: 1, limit: 500 }); staffList.value = r.data.list || r.data } catch {}
  loadData()
})
</script>
