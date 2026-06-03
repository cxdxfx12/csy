<template>
  <div class="page-container">
    <el-card shadow="never">
      <template #header>
        <div class="card-header">
          <span>服务评价</span>
          <el-button type="primary" size="small" @click="openForm()">新增评价</el-button>
        </div>
      </template>

      <el-form :model="query" inline>
        <el-form-item><el-input v-model="query.keyword" placeholder="供应商/评价人" clearable style="width:200px;" /></el-form-item>
        <el-form-item><el-select v-model="query.supplier_id" filterable placeholder="供应商" clearable style="width:180px;"><el-option v-for="s in suppliers" :key="s.id" :label="s.name" :value="s.id" /></el-select></el-form-item>
        <el-form-item><el-select v-model="query.rating" placeholder="评分" clearable style="width:100px;"><el-option label="5星" :value="5" /><el-option label="4星" :value="4" /><el-option label="3星" :value="3" /><el-option label="2星" :value="2" /><el-option label="1星" :value="1" /></el-select></el-form-item>
        <el-form-item><el-button type="primary" @click="loadData">查询</el-button><el-button @click="resetQuery">重置</el-button></el-form-item>
      </el-form>

      <el-table :data="list" v-loading="loading" stripe border style="width:100%;">
        <el-table-column prop="supplier_name" label="供应商" width="180" />
        <el-table-column prop="rating" label="评分" width="180"><template #default="{row}"><el-rate v-model="row.rating" disabled show-score text-color="#ff9900" /></template></el-table-column>
        <el-table-column prop="evaluator" label="评价人" width="100" />
        <el-table-column prop="content" label="评价内容" show-overflow-tooltip />
        <el-table-column prop="create_time" label="评价时间" width="110" />
        <el-table-column fixed="right" label="操作" width="130"><template #default="{row}">
          <el-button size="small" type="primary" link @click="openForm(row)">编辑</el-button>
          <el-popconfirm title="确定删除?" @confirm="handleDelete(row.id)"><template #reference><el-button size="small" type="danger" link>删除</el-button></template></el-popconfirm>
        </template></el-table-column>
      </el-table>
      <div style="margin-top:16px;text-align:right;"><el-pagination v-model:current-page="query.page" v-model:page-size="query.limit" :total="total" :page-sizes="[10,15,30]" layout="total,sizes,prev,pager,next" @change="loadData" /></div>
    </el-card>

    <el-dialog v-model="dialogVisible" :title="formTitle" width="500px" destroy-on-close>
      <el-form :model="form" :rules="rules" ref="formRef" label-width="100px">
        <el-form-item label="供应商" prop="supplier_id"><el-select v-model="form.supplier_id" filterable style="width:100%;"><el-option v-for="s in suppliers" :key="s.id" :label="s.name" :value="s.id" /></el-select></el-form-item>
        <el-form-item label="综合评分" prop="rating"><el-rate v-model="form.rating" show-score style="line-height:32px;" /></el-form-item>
        <el-form-item label="评价人"><el-input v-model="form.evaluator" placeholder="评价人" /></el-form-item>
        <el-form-item label="评价内容"><el-input v-model="form.content" type="textarea" rows="4" placeholder="请详细描述服务质量和体验" /></el-form-item>
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
const formTitle = ref('新增评价')
const suppliers = ref<any[]>([])

const query = reactive({ keyword: '', supplier_id: undefined as any, rating: undefined as any, page: 1, limit: 15 })
const form = reactive<any>({ id: 0, supplier_id: '', rating: 5, evaluator: '', content: '' })
const rules = { supplier_id: [{ required: true, message: '请选择供应商' }], rating: [{ required: true, message: '请评分' }] }

function resetQuery() { Object.assign(query, { keyword: '', supplier_id: undefined, rating: undefined, page: 1 }); loadData() }

async function loadData() {
  loading.value = true
  try {
    const res = await apiGet('/admin/Evaluation/lists', { ...query })
    list.value = res.data.list || res.data
    total.value = res.data.total || list.value.length
  } catch { list.value = []; total.value = 0 } finally { loading.value = false }
}

function openForm(row?: any) {
  formTitle.value = row ? '编辑评价' : '新增评价'
  Object.assign(form, row || { id: 0, supplier_id: '', rating: 5, evaluator: '', content: '' })
  dialogVisible.value = true
}

async function submitForm() {
  const valid = await formRef.value?.validate().catch(() => false)
  if (!valid) return
  submitting.value = true
  try {
    const url = form.id ? '/admin/Evaluation/edit' : '/admin/Evaluation/add'
    await apiPost(url, { ...form })
    ElMessage.success(form.id ? '修改成功' : '评价成功')
    dialogVisible.value = false; loadData()
  } finally { submitting.value = false }
}

async function handleDelete(id: number) { await apiPost('/admin/Evaluation/delete', { id }); ElMessage.success('删除成功'); loadData() }

onMounted(async () => {
  try { const r = await apiGet('/admin/Supplier/lists', { page: 1, limit: 500 }); suppliers.value = r.data.list || r.data } catch {}
  loadData()
})
</script>
