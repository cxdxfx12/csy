<template>
  <div class="page-container">
    <el-card shadow="never">
      <template #header>
        <div class="card-header">
          <span>合同管理</span>
          <el-button type="primary" size="small" @click="openForm()">添加合同</el-button>
        </div>
      </template>

      <el-form :model="query" inline>
        <el-form-item><el-input v-model="query.keyword" placeholder="合同编号/名称/供应商" clearable style="width:200px;" /></el-form-item>
        <el-form-item><el-select v-model="query.supplier_id" filterable placeholder="供应商" clearable style="width:180px;"><el-option v-for="s in suppliers" :key="s.id" :label="s.name" :value="s.id" /></el-select></el-form-item>
        <el-form-item><el-select v-model="query.status" placeholder="状态" clearable style="width:110px;"><el-option label="执行中" :value="1" /><el-option label="已到期" :value="2" /><el-option label="已终止" :value="3" /></el-select></el-form-item>
        <el-form-item><el-button type="primary" @click="loadData">查询</el-button><el-button @click="resetQuery">重置</el-button></el-form-item>
      </el-form>

      <el-table :data="list" v-loading="loading" stripe border style="width:100%;">
        <el-table-column prop="contract_no" label="合同编号" width="140" />
        <el-table-column prop="title" label="合同名称" width="190" show-overflow-tooltip />
        <el-table-column prop="supplier_name" label="供应商" width="160" />
        <el-table-column prop="contract_amount" label="合同金额" width="110"><template #default="{row}">¥{{ row.contract_amount||0 }}</template></el-table-column>
        <el-table-column prop="status" label="状态" width="90"><template #default="{row}"><el-tag :type="statusTag[row.status]||'info'">{{ statusMap[row.status]||'未知' }}</el-tag></template></el-table-column>
        <el-table-column prop="start_date" label="生效日期" width="110" />
        <el-table-column prop="end_date" label="到期日期" width="110" />
        <el-table-column prop="sign_date" label="签订日期" width="110" />
        <el-table-column fixed="right" label="操作" width="160"><template #default="{row}">
          <el-button size="small" type="primary" link @click="openForm(row)">编辑</el-button>
          <el-button v-if="row.status===1" size="small" type="warning" link @click="handleExpire(row.id)">终止</el-button>
          <el-popconfirm title="确定删除?" @confirm="handleDelete(row.id)"><template #reference><el-button size="small" type="danger" link>删除</el-button></template></el-popconfirm>
        </template></el-table-column>
      </el-table>
      <div style="margin-top:16px;text-align:right;"><el-pagination v-model:current-page="query.page" v-model:page-size="query.limit" :total="total" :page-sizes="[10,15,30]" layout="total,sizes,prev,pager,next" @current-change="loadData" @size-change="loadData" /></div>
    </el-card>

    <el-dialog v-model="dialogVisible" :title="formTitle" width="580px" destroy-on-close>
      <el-form :model="form" :rules="rules" ref="formRef" label-width="100px">
        <el-form-item label="合同名称" prop="title"><el-input v-model="form.title" placeholder="合同名称" /></el-form-item>
        <el-row :gutter="20">
          <el-col :span="12"><el-form-item label="供应商" prop="supplier_id"><el-select v-model="form.supplier_id" filterable style="width:100%;"><el-option v-for="s in suppliers" :key="s.id" :label="s.name" :value="s.id" /></el-select></el-form-item></el-col>
          <el-col :span="12"><el-form-item label="合同金额"><el-input-number v-model="form.contract_amount" :min="0" :precision="2" style="width:100%;" /></el-form-item></el-col>
        </el-row>
        <el-form-item label="合同内容"><el-input v-model="form.content" type="textarea" rows="3" placeholder="合同主要内容概述" /></el-form-item>
        <el-row :gutter="20">
          <el-col :span="12"><el-form-item label="生效日期"><el-date-picker v-model="form.start_date" type="date" style="width:100%;" value-format="YYYY-MM-DD" /></el-form-item></el-col>
          <el-col :span="12"><el-form-item label="到期日期"><el-date-picker v-model="form.end_date" type="date" style="width:100%;" value-format="YYYY-MM-DD" /></el-form-item></el-col>
        </el-row>
        <el-row :gutter="20">
          <el-col :span="12"><el-form-item label="签订日期"><el-date-picker v-model="form.sign_date" type="date" style="width:100%;" value-format="YYYY-MM-DD" /></el-form-item></el-col>
          <el-col :span="12"><el-form-item label="状态"><el-select v-model="form.status" style="width:100%;"><el-option label="执行中" :value="1" /><el-option label="已到期" :value="2" /><el-option label="已终止" :value="3" /></el-select></el-form-item></el-col>
        </el-row>
        <el-form-item label="备注"><el-input v-model="form.remark" type="textarea" rows="2" /></el-form-item>
      </el-form>
      <template #footer><el-button @click="dialogVisible=false">取消</el-button><el-button type="primary" @click="submitForm" :loading="submitting">确定</el-button></template>
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
const submitting = ref(false)
const formRef = ref<any>(null)
const formTitle = ref('添加合同')
const suppliers = ref<any[]>([])
const statusMap: Record<number,string> = { 1: '执行中', 2: '已到期', 3: '已终止' }
const statusTag: Record<number,string> = { 1: 'success', 2: 'warning', 3: 'danger' }

const query = reactive({ keyword: '', supplier_id: undefined as any, status: undefined as any, page: 1, limit: 15 })
const form = reactive<any>({ id: 0, title: '', supplier_id: '', contract_amount: 0, content: '', start_date: '', end_date: '', sign_date: '', remark: '', status: 1 })
const rules = { title: [{ required: true, message: '请输入合同名称' }], supplier_id: [{ required: true, message: '请选择供应商' }] }

function resetQuery() { Object.assign(query, { keyword: '', supplier_id: undefined, status: undefined, page: 1 }); loadData() }

async function loadData() {
  loading.value = true
  try {
    const res = await apiGet('/admin/contract/lists', { ...query })
    list.value = res.data.list || res.data
    total.value = res.data.total || list.value.length
  } catch { list.value = []; total.value = 0 } finally { loading.value = false }
}

function openForm(row?: any) {
  formTitle.value = row ? '编辑合同' : '添加合同'
  Object.assign(form, row || { id: 0, title: '', supplier_id: '', contract_amount: 0, content: '', start_date: '', end_date: '', sign_date: '', remark: '', status: 1 })
  dialogVisible.value = true
}

async function submitForm() {
  try { await formRef.value?.validate() } catch { return }
  submitting.value = true
  try {
    const url = form.id ? '/admin/contract/edit' : '/admin/contract/add'
    await apiPost(url, { ...form })
    ElMessage.success(form.id ? '修改成功' : '添加成功')
    dialogVisible.value = false; loadData()
  } finally { submitting.value = false }
}

async function handleExpire(id: number) { await apiPost('/admin/contract/expire', { id }); ElMessage.success('已终止'); loadData() }

async function handleDelete(id: number) { await apiPost('/admin/contract/delete', { id }); ElMessage.success('删除成功'); loadData() }

onMounted(async () => {
  try { const r = await apiGet('/admin/supplier/lists', { page: 1, limit: 500 }); suppliers.value = r.data.list || r.data } catch {}
  loadData()
})
</script>
