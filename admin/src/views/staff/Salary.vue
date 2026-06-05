<template>
  <div class="page-container">
    <el-card shadow="never">
      <template #header>
        <div class="card-header">
          <span>工资管理</span>
          <div>
            <el-button type="warning" size="small" @click="batchGenerate">批量生成</el-button>
            <el-button type="primary" size="small" @click="openForm()">添加工资</el-button>
          </div>
        </div>
      </template>

      <el-form :model="query" inline>
        <el-form-item><el-select v-model="query.community_id" placeholder="小区" clearable style="width:150px;" @change="onCommunityChange"><el-option v-for="c in communities" :key="c.id" :label="c.name" :value="c.id" /></el-select></el-form-item>
        <el-form-item><el-input v-model="query.keyword" placeholder="姓名/工号" clearable style="width:180px;" /></el-form-item>
        <el-form-item><el-input v-model="query.month" placeholder="月份(如2025-06)" clearable style="width:160px;" /></el-form-item>
        <el-form-item><el-select v-model="query.status" placeholder="发放状态" clearable style="width:120px;"><el-option label="未发放" :value="0" /><el-option label="已发放" :value="1" /></el-select></el-form-item>
        <el-form-item><el-button type="primary" @click="loadData">查询</el-button><el-button @click="resetQuery">重置</el-button></el-form-item>
      </el-form>

      <el-table :data="list" v-loading="loading" stripe border style="width:100%;" show-summary :summary-method="getSummaries">
        <el-table-column prop="job_no" label="工号" width="90" />
        <el-table-column prop="staff_name" label="姓名" width="90" />
        <el-table-column prop="community_name" label="小区" width="120" />
        <el-table-column prop="salary_month" label="月份" width="90" />
        <el-table-column prop="base_salary" label="基本工资" width="100"><template #default="{row}">¥{{ row.base_salary||0 }}</template></el-table-column>
        <el-table-column prop="bonus" label="奖金" width="90"><template #default="{row}">¥{{ row.bonus||0 }}</template></el-table-column>
        <el-table-column prop="overtime_pay" label="加班费" width="90"><template #default="{row}">¥{{ row.overtime_pay||0 }}</template></el-table-column>
        <el-table-column prop="subsidy" label="补贴" width="90"><template #default="{row}">¥{{ row.subsidy||0 }}</template></el-table-column>
        <el-table-column prop="deduction" label="扣款" width="90"><template #default="{row}">¥{{ row.deduction||0 }}</template></el-table-column>
        <el-table-column prop="social_insurance" label="社保" width="90"><template #default="{row}">¥{{ row.social_insurance||0 }}</template></el-table-column>
        <el-table-column prop="net_salary" label="实发" width="100"><template #default="{row}"><b style="color:#409eff;">¥{{ row.net_salary||0 }}</b></template></el-table-column>
        <el-table-column prop="status" label="状态" width="85"><template #default="{row}"><el-tag :type="row.status===1?'success':'warning'">{{ row.status===1?'已发放':'未发放' }}</el-tag></template></el-table-column>
        <el-table-column prop="pay_time" label="发放时间" width="110" />
        <el-table-column fixed="right" label="操作" width="160"><template #default="{row}">
          <el-button size="small" type="primary" link @click="openForm(row)">编辑</el-button>
          <el-button v-if="row.status!==1" size="small" type="success" link @click="handlePay(row.id)">发放</el-button>
          <el-popconfirm title="确定删除?" @confirm="handleDelete(row.id)"><template #reference><el-button size="small" type="danger" link>删除</el-button></template></el-popconfirm>
        </template></el-table-column>
      </el-table>
      <div style="margin-top:16px;text-align:right;"><el-pagination v-model:current-page="query.page" v-model:page-size="query.limit" :total="total" :page-sizes="[10,15,30]" layout="total,sizes,prev,pager,next" @current-change="loadData" @size-change="loadData" /></div>
    </el-card>

    <el-dialog v-model="dialogVisible" :title="formTitle" width="560px" destroy-on-close>
      <el-form :model="form" :rules="rules" ref="formRef" label-width="100px">
        <el-form-item label="员工" prop="staff_id"><el-select v-model="form.staff_id" filterable placeholder="选择员工" style="width:100%;"><el-option v-for="s in filteredStaff" :key="s.id" :label="s.realname+'('+s.job_no+')'" :value="s.id" /></el-select></el-form-item>
        <el-form-item label="月份" prop="salary_month"><el-input v-model="form.salary_month" placeholder="如:2025-06" /></el-form-item>
        <el-row :gutter="15">
          <el-col :span="12"><el-form-item label="基本工资"><el-input-number v-model="form.base_salary" :min="0" :precision="2" style="width:100%;" /></el-form-item></el-col>
          <el-col :span="12"><el-form-item label="奖金"><el-input-number v-model="form.bonus" :min="0" :precision="2" style="width:100%;" /></el-form-item></el-col>
        </el-row>
        <el-row :gutter="15">
          <el-col :span="12"><el-form-item label="加班费"><el-input-number v-model="form.overtime_pay" :min="0" :precision="2" style="width:100%;" /></el-form-item></el-col>
          <el-col :span="12"><el-form-item label="补贴"><el-input-number v-model="form.subsidy" :min="0" :precision="2" style="width:100%;" /></el-form-item></el-col>
        </el-row>
        <el-row :gutter="15">
          <el-col :span="12"><el-form-item label="扣款"><el-input-number v-model="form.deduction" :min="0" :precision="2" style="width:100%;" /></el-form-item></el-col>
          <el-col :span="12"><el-form-item label="社保扣缴"><el-input-number v-model="form.social_insurance" :min="0" :precision="2" style="width:100%;" /></el-form-item></el-col>
        </el-row>
        <el-form-item label="实发合计"><el-input v-model="computedSalary" disabled style="width:200px;" /><span style="margin-left:10px;color:#999;font-size:12px;">= 基本+奖金+加班+补贴-扣款-社保</span></el-form-item>
        <el-form-item label="备注"><el-input v-model="form.remark" type="textarea" rows="2" /></el-form-item>
      </el-form>
      <template #footer><el-button @click="dialogVisible=false">取消</el-button><el-button type="primary" @click="submitForm" :loading="submitting">确定</el-button></template>
    </el-dialog>

    <el-dialog v-model="batchVisible" title="批量生成工资" width="450px">
      <el-form label-width="100px">
        <el-form-item label="选择月份"><el-input v-model="batchMonth" placeholder="如:2025-06" /></el-form-item>
        <el-form-item label="说明"><span style="color:#999;font-size:13px;">将为所有在职员工生成当月工资记录，基本工资取自员工档案。</span></el-form-item>
      </el-form>
      <template #footer><el-button @click="batchVisible=false">取消</el-button><el-button type="primary" @click="submitBatchGenerate" :loading="submitting">确定生成</el-button></template>
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
const formTitle = ref('添加工资')
const staffList = ref<any[]>([])
const communities = ref<any[]>([])
const batchMonth = ref('')

const query = reactive({ community_id: undefined as any, keyword: '', month: '', status: undefined as any, page: 1, limit: 15 })
const form = reactive<any>({ id: 0, staff_id: '', salary_month: '', base_salary: 0, bonus: 0, overtime_pay: 0, subsidy: 0, deduction: 0, social_insurance: 0, net_salary: 0, remark: '' })
const rules = { staff_id: [{ required: true, message: '请选择员工' }], salary_month: [{ required: true, message: '请输入月份' }] }

const computedSalary = computed(() => {
  return '¥' + Math.round((Number(form.base_salary||0) + Number(form.bonus||0) + Number(form.overtime_pay||0) + Number(form.subsidy||0) - Number(form.deduction||0) - Number(form.social_insurance||0)) * 100) / 100
})

function resetQuery() { Object.assign(query, { community_id: undefined, keyword: '', month: '', status: undefined, page: 1 }); loadData() }

function onCommunityChange() { loadStaffList(); loadData() }

async function loadStaffList() {
  const params: any = { page: 1, limit: 500 }
  if (query.community_id) params.community_id = query.community_id
  try { const r = await apiGet('/admin/Staff/lists', params); staffList.value = r.data.list || r.data } catch {}
}

async function loadData() {
  loading.value = true
  try {
    const res = await apiGet('/admin/Salary/lists', { ...query })
    list.value = res.data.list || res.data
    total.value = res.data.total || list.value.length
  } catch { list.value = []; total.value = 0 } finally { loading.value = false }
}

function openForm(row?: any) {
  formTitle.value = row ? '编辑工资' : '添加工资'
  Object.assign(form, row || { id: 0, staff_id: '', salary_month: '', base_salary: 0, bonus: 0, overtime_pay: 0, subsidy: 0, deduction: 0, social_insurance: 0, net_salary: 0, remark: '' })
  dialogVisible.value = true
}

async function submitForm() {
  const valid = await formRef.value?.validate().catch(() => false)
  if (!valid) return
  submitting.value = true
  try {
    const url = form.id ? '/admin/Salary/edit' : '/admin/Salary/add'
    await apiPost(url, { ...form })
    ElMessage.success(form.id ? '修改成功' : '添加成功')
    dialogVisible.value = false; loadData()
  } finally { submitting.value = false }
}

async function handlePay(id: number) { await apiPost('/admin/Salary/pay', { id }); ElMessage.success('已发放'); loadData() }

async function handleDelete(id: number) { await apiPost('/admin/Salary/delete', { id }); ElMessage.success('删除成功'); loadData() }

function batchGenerate() { batchMonth.value = ''; batchVisible.value = true }

async function submitBatchGenerate() {
  if (!batchMonth.value) { ElMessage.warning('请输入月份'); return }
  submitting.value = true
  try { await apiPost('/admin/Salary/batchGenerate', { salary_month: batchMonth.value }); ElMessage.success('批量生成成功'); batchVisible.value = false; loadData() } finally { submitting.value = false }
}

function getSummaries(param: any) {
  const { columns, data } = param
  const sums: string[] = []
  columns.forEach((col: any, i: number) => {
    if (i === 0) { sums[i] = '合计'; return }
    const moneyCols = ['base_salary','bonus','overtime_pay','subsidy','deduction','social_insurance','net_salary']
    if (moneyCols.includes(col.property)) {
      sums[i] = '¥' + data.reduce((prev: number, cur: any) => prev + (Number(cur[col.property]) || 0), 0).toFixed(2)
    } else { sums[i] = '' }
  })
  return sums
}

onMounted(async () => {
  try { const r = await apiGet('/admin/Community/lists', {}); communities.value = r.data.list || r.data } catch {}
  try { const r = await apiGet('/admin/Staff/lists', { page: 1, limit: 500 }); staffList.value = r.data.list || r.data } catch {}
  loadData()
})
</script>
