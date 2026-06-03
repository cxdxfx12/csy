<template>
  <div class="page-container">
    <el-card shadow="never">
      <template #header>
        <div class="card-header">
          <span>员工档案</span>
          <el-button type="primary" size="small" @click="openForm()">添加员工</el-button>
        </div>
      </template>

      <el-form :model="query" inline>
        <el-form-item><el-input v-model="query.keyword" placeholder="姓名/工号/手机" clearable @clear="loadData" style="width:200px;" /></el-form-item>
        <el-form-item><el-select v-model="query.community_id" placeholder="所属小区" clearable @change="loadData" style="width:150px;"><el-option v-for="c in communities" :key="c.id" :label="c.name" :value="c.id" /></el-select></el-form-item>
        <el-form-item><el-select v-model="query.status" placeholder="状态" clearable @change="loadData" style="width:110px;"><el-option label="在职" :value="1" /><el-option label="离职" :value="0" /></el-select></el-form-item>
        <el-form-item><el-button type="primary" @click="loadData">查询</el-button><el-button @click="resetQuery">重置</el-button></el-form-item>
      </el-form>

      <el-table :data="list" v-loading="loading" stripe border style="width:100%;">
        <el-table-column prop="job_no" label="工号" width="100" />
        <el-table-column prop="realname" label="姓名" width="100" />
        <el-table-column prop="gender" label="性别" width="70"><template #default="{row}">{{ row.gender===1?'男':row.gender===2?'女':'-' }}</template></el-table-column>
        <el-table-column prop="department" label="部门" width="110" />
        <el-table-column prop="position" label="岗位" width="110" />
        <el-table-column prop="phone" label="手机号" width="120" />
        <el-table-column prop="community_name" label="所属小区" width="130" />
        <el-table-column prop="base_salary" label="基本工资" width="100"><template #default="{row}">¥{{ row.base_salary||0 }}</template></el-table-column>
        <el-table-column prop="entry_date" label="入职日期" width="110" />
        <el-table-column prop="status" label="状态" width="80"><template #default="{row}"><el-tag :type="row.status===1?'success':'danger'">{{ row.status===1?'在职':'离职' }}</el-tag></template></el-table-column>
        <el-table-column fixed="right" label="操作" width="160"><template #default="{row}">
          <el-button size="small" type="primary" link @click="openForm(row)">编辑</el-button>
          <el-button size="small" link @click="viewDetail(row)">详情</el-button>
          <el-popconfirm title="确定删除?" @confirm="handleDelete(row.id)"><template #reference><el-button size="small" type="danger" link>删除</el-button></template></el-popconfirm>
        </template></el-table-column>
      </el-table>
      <div style="margin-top:16px;text-align:right;"><el-pagination v-model:current-page="query.page" v-model:page-size="query.limit" :total="total" :page-sizes="[10,15,30]" layout="total,sizes,prev,pager,next" @current-change="loadData" @size-change="loadData" /></div>
    </el-card>

    <el-dialog v-model="dialogVisible" :title="formTitle" width="620px" destroy-on-close>
      <el-form :model="form" :rules="rules" ref="formRef" label-width="110px">
        <el-row :gutter="20">
          <el-col :span="12"><el-form-item label="工号" prop="job_no"><el-input v-model="form.job_no" placeholder="工号" /></el-form-item></el-col>
          <el-col :span="12"><el-form-item label="姓名" prop="realname"><el-input v-model="form.realname" placeholder="姓名" /></el-form-item></el-col>
        </el-row>
        <el-row :gutter="20">
          <el-col :span="12"><el-form-item label="性别"><el-radio-group v-model="form.gender"><el-radio :value="1">男</el-radio><el-radio :value="2">女</el-radio></el-radio-group></el-form-item></el-col>
          <el-col :span="12"><el-form-item label="手机号"><el-input v-model="form.phone" placeholder="手机号" maxlength="11" /></el-form-item></el-col>
        </el-row>
        <el-row :gutter="20">
          <el-col :span="12"><el-form-item label="部门" prop="department"><el-select v-model="form.department" style="width:100%;"><el-option label="安保部" value="安保部" /><el-option label="保洁部" value="保洁部" /><el-option label="工程部" value="工程部" /><el-option label="客服部" value="客服部" /><el-option label="绿化部" value="绿化部" /><el-option label="财务部" value="财务部" /><el-option label="行政部" value="行政部" /></el-select></el-form-item></el-col>
          <el-col :span="12"><el-form-item label="岗位" prop="position"><el-input v-model="form.position" placeholder="岗位名称" /></el-form-item></el-col>
        </el-row>
        <el-row :gutter="20">
          <el-col :span="12"><el-form-item label="所属小区"><el-select v-model="form.community_id" clearable style="width:100%;"><el-option v-for="c in communities" :key="c.id" :label="c.name" :value="c.id" /></el-select></el-form-item></el-col>
          <el-col :span="12"><el-form-item label="基本工资"><el-input-number v-model="form.base_salary" :min="0" :precision="2" style="width:100%;" /></el-form-item></el-col>
        </el-row>
        <el-row :gutter="20">
          <el-col :span="12"><el-form-item label="入职日期"><el-date-picker v-model="form.entry_date" type="date" style="width:100%;" value-format="YYYY-MM-DD" /></el-form-item></el-col>
          <el-col :span="12"><el-form-item label="状态"><el-radio-group v-model="form.status"><el-radio :value="1">在职</el-radio><el-radio :value="0">离职</el-radio></el-radio-group></el-form-item></el-col>
        </el-row>
        <el-form-item label="身份证号"><el-input v-model="form.id_card" placeholder="身份证号" /></el-form-item>
        <el-form-item label="紧急联系人"><el-input v-model="form.emergency_contact" placeholder="姓名和电话" /></el-form-item>
        <el-form-item label="备注"><el-input v-model="form.remark" type="textarea" rows="2" /></el-form-item>
      </el-form>
      <template #footer><el-button @click="dialogVisible=false">取消</el-button><el-button type="primary" @click="submitForm" :loading="submitting">确定</el-button></template>
    </el-dialog>

    <el-dialog v-model="detailVisible" title="员工详情" width="560px">
      <el-descriptions :column="2" border v-if="detail">
        <el-descriptions-item label="工号">{{ detail.job_no }}</el-descriptions-item>
        <el-descriptions-item label="姓名">{{ detail.realname }}</el-descriptions-item>
        <el-descriptions-item label="性别">{{ detail.gender===1?'男':detail.gender===2?'女':'-' }}</el-descriptions-item>
        <el-descriptions-item label="手机号">{{ detail.phone }}</el-descriptions-item>
        <el-descriptions-item label="部门">{{ detail.department }}</el-descriptions-item>
        <el-descriptions-item label="岗位">{{ detail.position }}</el-descriptions-item>
        <el-descriptions-item label="所属小区">{{ detail.community_name }}</el-descriptions-item>
        <el-descriptions-item label="基本工资">¥{{ detail.base_salary||0 }}</el-descriptions-item>
        <el-descriptions-item label="入职日期">{{ detail.entry_date }}</el-descriptions-item>
        <el-descriptions-item label="状态"><el-tag :type="detail.status===1?'success':'danger'">{{ detail.status===1?'在职':'离职' }}</el-tag></el-descriptions-item>
        <el-descriptions-item label="身份证号" :span="2">{{ detail.id_card||'-' }}</el-descriptions-item>
        <el-descriptions-item label="紧急联系人" :span="2">{{ detail.emergency_contact||'-' }}</el-descriptions-item>
        <el-descriptions-item label="备注" :span="2">{{ detail.remark||'-' }}</el-descriptions-item>
        <el-descriptions-item label="创建时间">{{ detail.create_time }}</el-descriptions-item>
        <el-descriptions-item label="更新时间">{{ detail.update_time }}</el-descriptions-item>
      </el-descriptions>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { apiGet, apiPost } from '@/utils/request'

const list = ref<any[]>([])
const total = ref(0)
const loading = ref(false)
const dialogVisible = ref(false)
const detailVisible = ref(false)
const submitting = ref(false)
const formRef = ref<any>(null)
const formTitle = ref('添加员工')
const communities = ref<any[]>([])
const detail = ref<any>(null)

const query = reactive({ keyword: '', community_id: undefined as any, status: undefined as any, page: 1, limit: 15 })
const form = reactive<any>({ id: 0, job_no: '', realname: '', gender: 1, phone: '', department: '', position: '', community_id: '', base_salary: 0, entry_date: '', id_card: '', emergency_contact: '', remark: '', status: 1 })
const rules = { job_no: [{ required: true, message: '请输入工号' }], realname: [{ required: true, message: '请输入姓名' }], department: [{ required: true, message: '请选择部门' }], position: [{ required: true, message: '请输入岗位' }] }

function resetQuery() { Object.assign(query, { keyword: '', community_id: undefined, status: undefined, page: 1 }); loadData() }

async function loadData() {
  loading.value = true
  try {
    const res = await apiGet('/admin/Staff/lists', { ...query })
    list.value = res.data.list || res.data
    total.value = res.data.total || list.value.length
  } catch { list.value = []; total.value = 0 } finally { loading.value = false }
}

function openForm(row?: any) {
  formTitle.value = row ? '编辑员工' : '添加员工'
  Object.assign(form, row || { id: 0, job_no: '', realname: '', gender: 1, phone: '', department: '', position: '', community_id: '', base_salary: 0, entry_date: '', id_card: '', emergency_contact: '', remark: '', status: 1 })
  dialogVisible.value = true
}

async function submitForm() {
  const valid = await formRef.value?.validate().catch(() => false)
  if (!valid) return
  submitting.value = true
  try {
    const url = form.id ? '/admin/Staff/edit' : '/admin/Staff/add'
    await apiPost(url, { ...form })
    ElMessage.success(form.id ? '修改成功' : '添加成功')
    dialogVisible.value = false
    loadData()
  } finally { submitting.value = false }
}

async function handleDelete(id: number) { await apiPost('/admin/Staff/delete', { id }); ElMessage.success('删除成功'); loadData() }

async function viewDetail(row: any) {
  const res = await apiGet('/admin/Staff/detail', { id: row.id })
  detail.value = res.data
  detailVisible.value = true
}

onMounted(async () => {
  try { const r = await apiGet('/admin/Community/lists', { page: 1, limit: 500 }); communities.value = r.data.list || r.data } catch {}
  loadData()
})
</script>
