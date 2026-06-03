<template>
  <div class="page-container">
    <div class="search-bar">
      <el-form :model="query" inline>
        <el-form-item>
          <el-input v-model="query.keyword" placeholder="公司名称/税号" clearable style="width:220px" />
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="loadData">搜索</el-button>
          <el-button @click="resetQuery">重置</el-button>
        </el-form-item>
      </el-form>
    </div>
    <el-card shadow="never" class="table-card">
      <div class="table-toolbar"><el-button type="primary" @click="openForm()">+ 新增抬头</el-button></div>
      <el-table :data="list" v-loading="loading" stripe border highlight-current-row>
        <el-table-column type="index" label="#" width="50" />
        <el-table-column prop="company_name" label="公司名称" width="200" show-overflow-tooltip />
        <el-table-column prop="tax_id" label="税号" width="160" />
        <el-table-column prop="company_address" label="公司地址" width="200" show-overflow-tooltip />
        <el-table-column prop="company_phone" label="公司电话" width="130" />
        <el-table-column prop="bank_name" label="开户行" width="150" show-overflow-tooltip />
        <el-table-column prop="bank_account" label="银行账号" width="170" />
        <el-table-column label="默认" width="80" align="center">
          <template #default="{ row }">
            <el-tag v-if="row.is_default === 1" type="success" size="small">默认</el-tag>
            <el-tag v-else type="info" size="small">否</el-tag>
          </template>
        </el-table-column>
        <el-table-column label="操作" width="160" fixed="right">
          <template #default="{ row }">
            <el-button size="small" @click="openForm(row)">编辑</el-button>
            <el-button size="small" type="danger" @click="handleDelete(row)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>
      <div class="pagination">
        <el-pagination v-model:current-page="query.page" v-model:page-size="query.limit" :total="total"
          :page-sizes="[15,30,50,100]" layout="total,sizes,prev,pager,next" @current-change="loadData" @size-change="loadData" />
      </div>
    </el-card>

    <el-dialog v-model="dialogVisible" :title="editId ? '编辑抬头' : '新增抬头'" width="650px" destroy-on-close @opened="onDialogOpen">
      <el-form :model="form" ref="formRef" label-width="90px">
        <el-row :gutter="16">
          <el-col :span="12">
            <el-form-item label="业主">
              <el-select v-model="form.owner_id" placeholder="选择业主" style="width:100%" filterable clearable>
                <el-option v-for="o in ownerList" :key="o.id" :label="o.realname + ' (' + o.phone + ')'" :value="o.id" />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="是否默认">
              <el-select v-model="form.is_default" style="width:100%">
                <el-option label="是" :value="1" />
                <el-option label="否" :value="0" />
              </el-select>
            </el-form-item>
          </el-col>
        </el-row>
        <el-form-item label="公司名称">
          <el-input v-model="form.company_name" placeholder="营业执照上的公司全称" />
        </el-form-item>
        <el-form-item label="税号">
          <el-input v-model="form.tax_id" placeholder="统一社会信用代码（18位）" maxlength="18" />
        </el-form-item>
        <el-row :gutter="16">
          <el-col :span="12">
            <el-form-item label="公司地址">
              <el-input v-model="form.company_address" placeholder="注册地址" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="公司电话">
              <el-input v-model="form.company_phone" placeholder="公司座机或手机" />
            </el-form-item>
          </el-col>
        </el-row>
        <el-row :gutter="16">
          <el-col :span="12">
            <el-form-item label="开户行">
              <el-input v-model="form.bank_name" placeholder="开户银行全称" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="银行账号">
              <el-input v-model="form.bank_account" placeholder="对公银行账号" />
            </el-form-item>
          </el-col>
        </el-row>
      </el-form>
      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" @click="handleSubmit" :loading="submitting">确定</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { apiGet, apiPost } from '@/utils/request'
import { ElMessage, ElMessageBox } from 'element-plus'

const list = ref<any[]>([])
const total = ref(0)
const loading = ref(false)
const submitting = ref(false)
const dialogVisible = ref(false)
const editId = ref(0)
const formRef = ref()
const ownerList = ref<any[]>([])

const query = reactive({ page: 1, limit: 15, keyword: '' })
const form = reactive<any>({})

function resetForm() { Object.keys(form).forEach(k => delete form[k]); form.is_default = 0 }

async function loadData() {
  loading.value = true
  try {
    const res = await apiGet('/admin/charge/invoiceInfoList', { page: query.page, limit: query.limit, keyword: query.keyword })
    if (res.code === 0) { list.value = res.data || []; total.value = res.count || 0 }
  } finally { loading.value = false }
}

function resetQuery() { query.keyword = ''; query.page = 1; loadData() }

function openForm(row?: any) {
  if (row) { editId.value = row.id; Object.assign(form, { ...row, is_default: row.is_default ?? 0 }) }
  else { editId.value = 0; resetForm() }
  dialogVisible.value = true
}

async function onDialogOpen() {
  const oRes = await apiGet('/admin/owner/list', { limit: 999 })
  ownerList.value = oRes.data?.list || oRes.data || []
}

async function handleSubmit() {
  const url = editId.value ? '/admin/charge/invoiceInfoEdit' : '/admin/charge/invoiceInfoAdd'
  submitting.value = true
  try {
    const res = await apiPost(url, { ...form, id: editId.value || undefined })
    if (res.code === 0) { ElMessage.success(res.msg); dialogVisible.value = false; loadData() }
  } finally { submitting.value = false }
}

async function handleDelete(row: any) {
  await ElMessageBox.confirm('确定删除？', '提示', { type: 'warning' })
  const res = await apiPost('/admin/charge/invoiceInfoDelete', { id: row.id })
  if (res.code === 0) { ElMessage.success('删除成功'); loadData() }
}

onMounted(() => loadData())
</script>

<style scoped>
.page-container { padding: 16px; }
.search-bar { margin-bottom: 12px; }
.table-toolbar { margin-bottom: 12px; }
.pagination { margin-top: 12px; display: flex; justify-content: flex-end; }
</style>
