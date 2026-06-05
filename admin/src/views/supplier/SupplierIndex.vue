<template>
  <div class="page-container">
    <el-card shadow="never">
      <template #header>
        <div class="card-header">
          <span>供应商名录</span>
          <el-button type="primary" size="small" @click="openForm()">添加供应商</el-button>
        </div>
      </template>

      <el-form :model="query" inline>
        <el-form-item><el-input v-model="query.keyword" placeholder="名称/联系人/电话" clearable style="width:200px;" /></el-form-item>
        <el-form-item><el-select v-model="query.category" placeholder="类别" clearable style="width:130px;"><el-option label="工程维修" value="工程维修" /><el-option label="保洁服务" value="保洁服务" /><el-option label="绿化养护" value="绿化养护" /><el-option label="安保服务" value="安保服务" /><el-option label="设备供应商" value="设备供应商" /><el-option label="物资采购" value="物资采购" /><el-option label="其他" value="其他" /></el-select></el-form-item>
        <el-form-item><el-select v-model="query.status" placeholder="状态" clearable style="width:110px;"><el-option label="正常" :value="1" /><el-option label="停用" :value="0" /></el-select></el-form-item>
        <el-form-item><el-button type="primary" @click="loadData">查询</el-button><el-button @click="resetQuery">重置</el-button></el-form-item>
      </el-form>

      <el-table :data="list" v-loading="loading" stripe border style="width:100%;">
        <el-table-column prop="name" label="供应商名称" width="180" />
        <el-table-column prop="category" label="类别" width="110"><template #default="{row}"><el-tag>{{ row.category }}</el-tag></template></el-table-column>
        <el-table-column prop="contact_person" label="联系人" width="100" />
        <el-table-column prop="contact_phone" label="联系电话" width="130" />
        <el-table-column prop="email" label="邮箱" width="170" show-overflow-tooltip />
        <el-table-column prop="rating" label="评分" width="100"><template #default="{row}"><el-rate :model-value="Number(row.rating) || 0" disabled show-score text-color="#ff9900" /></template></el-table-column>
        <el-table-column prop="status" label="状态" width="80"><template #default="{row}"><el-tag :type="row.status===1?'success':'danger'">{{ row.status===1?'正常':'停用' }}</el-tag></template></el-table-column>
        <el-table-column prop="create_time" label="登记时间" width="110" />
        <el-table-column fixed="right" label="操作" width="160"><template #default="{row}">
          <el-button size="small" type="primary" link @click="openForm(row)">编辑</el-button>
          <el-button size="small" link @click="viewDetail(row)">详情</el-button>
          <el-popconfirm title="确定删除?" @confirm="handleDelete(row.id)"><template #reference><el-button size="small" type="danger" link>删除</el-button></template></el-popconfirm>
        </template></el-table-column>
      </el-table>
      <div style="margin-top:16px;text-align:right;"><el-pagination v-model:current-page="query.page" v-model:page-size="query.limit" :total="total" :page-sizes="[10,15,30]" layout="total,sizes,prev,pager,next" @current-change="loadData" @size-change="loadData" /></div>
    </el-card>

    <el-dialog v-model="dialogVisible" :title="formTitle" width="560px" destroy-on-close>
      <el-form :model="form" :rules="rules" ref="formRef" label-width="100px">
        <el-form-item label="供应商名称" prop="name"><el-input v-model="form.name" placeholder="供应商名称" /></el-form-item>
        <el-row :gutter="20">
          <el-col :span="12"><el-form-item label="类别" prop="category"><el-select v-model="form.category" style="width:100%;"><el-option label="工程维修" value="工程维修" /><el-option label="保洁服务" value="保洁服务" /><el-option label="绿化养护" value="绿化养护" /><el-option label="安保服务" value="安保服务" /><el-option label="设备供应商" value="设备供应商" /><el-option label="物资采购" value="物资采购" /><el-option label="其他" value="其他" /></el-select></el-form-item></el-col>
          <el-col :span="12"><el-form-item label="状态"><el-radio-group v-model="form.status"><el-radio :value="1">正常</el-radio><el-radio :value="0">停用</el-radio></el-radio-group></el-form-item></el-col>
        </el-row>
        <el-row :gutter="20">
          <el-col :span="12"><el-form-item label="联系人"><el-input v-model="form.contact_person" placeholder="联系人" /></el-form-item></el-col>
          <el-col :span="12"><el-form-item label="联系电话"><el-input v-model="form.contact_phone" placeholder="联系电话" /></el-form-item></el-col>
        </el-row>
        <el-form-item label="邮箱"><el-input v-model="form.email" placeholder="邮箱" /></el-form-item>
        <el-form-item label="地址"><el-input v-model="form.address" placeholder="地址" /></el-form-item>
        <el-form-item label="备注"><el-input v-model="form.remark" type="textarea" rows="2" /></el-form-item>
      </el-form>
      <template #footer><el-button @click="dialogVisible=false">取消</el-button><el-button type="primary" @click="submitForm" :loading="submitting">确定</el-button></template>
    </el-dialog>

    <el-dialog v-model="detailVisible" title="供应商详情" width="600px">
      <el-descriptions :column="2" border v-if="detail">
        <el-descriptions-item label="供应商名称" :span="2">{{ detail.name }}</el-descriptions-item>
        <el-descriptions-item label="类别">{{ detail.category }}</el-descriptions-item>
        <el-descriptions-item label="状态"><el-tag :type="detail.status===1?'success':'danger'">{{ detail.status===1?'正常':'停用' }}</el-tag></el-descriptions-item>
        <el-descriptions-item label="联系人">{{ detail.contact_person||'-' }}</el-descriptions-item>
        <el-descriptions-item label="联系电话">{{ detail.contact_phone||'-' }}</el-descriptions-item>
        <el-descriptions-item label="邮箱" :span="2">{{ detail.email||'-' }}</el-descriptions-item>
        <el-descriptions-item label="地址" :span="2">{{ detail.address||'-' }}</el-descriptions-item>
        <el-descriptions-item label="综合评分"><el-rate :model-value="Number(detail.avg_rating) || 0" disabled show-score /></el-descriptions-item>
        <el-descriptions-item label="采购次数">{{ detail.purchase_count }}</el-descriptions-item>
        <el-descriptions-item label="采购总额">¥{{ detail.purchase_total||0 }}</el-descriptions-item>
        <el-descriptions-item label="合同数量">{{ detail.contract_count }}</el-descriptions-item>
        <el-descriptions-item label="备注" :span="2">{{ detail.remark||'-' }}</el-descriptions-item>
        <el-descriptions-item label="登记时间">{{ detail.create_time }}</el-descriptions-item>
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
const formTitle = ref('添加供应商')
const detail = ref<any>(null)

const query = reactive({ keyword: '', category: '', status: undefined as any, page: 1, limit: 15 })
const form = reactive<any>({ id: 0, name: '', category: '', contact_person: '', contact_phone: '', email: '', address: '', remark: '', status: 1 })
const rules = { name: [{ required: true, message: '请输入名称' }], category: [{ required: true, message: '请选择类别' }] }

function resetQuery() { Object.assign(query, { keyword: '', category: '', status: undefined, page: 1 }); loadData() }

async function loadData() {
  loading.value = true
  try {
    const res = await apiGet('/admin/Supplier/lists', { ...query })
    const raw = res.data.list || res.data
    const data = Array.isArray(raw) ? raw : []
    list.value = data.map((item: any) => ({ ...item, rating: Number(item.rating) || 0 }))
    total.value = res.data.total || list.value.length
  } catch { list.value = []; total.value = 0 } finally { loading.value = false }
}

function openForm(row?: any) {
  formTitle.value = row ? '编辑供应商' : '添加供应商'
  Object.assign(form, row || { id: 0, name: '', category: '', contact_person: '', contact_phone: '', email: '', address: '', remark: '', status: 1 })
  dialogVisible.value = true
}

async function submitForm() {
  const valid = await formRef.value?.validate().catch(() => false)
  if (!valid) return
  submitting.value = true
  try {
    const url = form.id ? '/admin/Supplier/edit' : '/admin/Supplier/add'
    await apiPost(url, { ...form })
    ElMessage.success(form.id ? '修改成功' : '添加成功')
    dialogVisible.value = false; loadData()
  } finally { submitting.value = false }
}

async function handleDelete(id: number) { await apiPost('/admin/Supplier/delete', { id }); ElMessage.success('删除成功'); loadData() }

async function viewDetail(row: any) {
  const res = await apiGet('/admin/Supplier/detail', { id: row.id })
  const d = res.data
  if (d) { d.avg_rating = Number(d.avg_rating) || 0; d.rating = Number(d.rating) || 0 }
  detail.value = d
  detailVisible.value = true
}

onMounted(() => { loadData() })
</script>
