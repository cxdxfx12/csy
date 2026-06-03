<template>
  <div class="page-container">
    <div class="search-bar">
      <el-form :model="query" inline>
        <el-form-item>
          <el-input v-model="query.keyword" placeholder="发票号/抬头/内容" clearable style="width:220px" />
        </el-form-item>
        <el-form-item>
          <el-select v-model="query.community_id" placeholder="小区" clearable style="width:160px">
            <el-option v-for="c in communities" :key="c.id" :label="c.name" :value="c.id" />
          </el-select>
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="loadData">搜索</el-button>
          <el-button @click="resetQuery">重置</el-button>
        </el-form-item>
      </el-form>
    </div>
    <el-card shadow="never" class="table-card">
      <div class="table-toolbar"><el-button type="primary" @click="openForm()">+ 新增发票</el-button></div>
      <el-table :data="list" v-loading="loading" stripe border highlight-current-row>
        <el-table-column type="index" label="#" width="50" />
        <el-table-column prop="invoice_no" label="发票号" width="160" />
        <el-table-column prop="invoice_code" label="发票代码" width="140" />
        <el-table-column label="发票类型" width="110">
          <template #default="{ row }">{{ row.invoice_type === 1 ? '增值税普通' : row.invoice_type === 2 ? '增值税专用' : '电子发票' }}</template>
        </el-table-column>
        <el-table-column label="抬头类型" width="90">
          <template #default="{ row }">{{ row.title_type === 1 ? '企业' : '个人' }}</template>
        </el-table-column>
        <el-table-column prop="title_name" label="抬头名称" width="160" show-overflow-tooltip />
        <el-table-column prop="tax_id" label="税号" width="150" />
        <el-table-column label="金额" width="100" align="right">
          <template #default="{ row }">¥{{ row.amount }}</template>
        </el-table-column>
        <el-table-column label="含税合计" width="100" align="right">
          <template #default="{ row }">¥{{ row.total_amount }}</template>
        </el-table-column>
        <el-table-column label="状态" width="90">
          <template #default="{ row }">
            <el-tag v-if="row.status === 1" type="success">已开票</el-tag>
            <el-tag v-else-if="row.status === 2" type="danger">已作废</el-tag>
            <el-tag v-else type="info">待开票</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="issue_time" label="开票时间" width="150" />
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

    <el-dialog v-model="dialogVisible" :title="editId ? '编辑发票' : '新增发票'" width="650px" destroy-on-close @opened="onDialogOpen">
      <el-form :model="form" ref="formRef" label-width="90px">
        <el-row :gutter="16">
          <el-col :span="12">
            <el-form-item label="小区">
              <el-select v-model="form.community_id" placeholder="选择小区" style="width:100%" @change="onCommunityChange" filterable clearable>
                <el-option v-for="c in communities" :key="c.id" :label="c.name" :value="c.id" />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="业主">
              <el-select v-model="form.owner_id" placeholder="选择业主" style="width:100%" filterable clearable>
                <el-option v-for="o in ownerList" :key="o.id" :label="o.realname + ' (' + o.phone + ')'" :value="o.id" />
              </el-select>
            </el-form-item>
          </el-col>
        </el-row>
        <el-row :gutter="16">
          <el-col :span="12">
            <el-form-item label="发票号">
              <el-input v-model="form.invoice_no" placeholder="发票号码" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="发票代码">
              <el-input v-model="form.invoice_code" placeholder="发票代码" />
            </el-form-item>
          </el-col>
        </el-row>
        <el-row :gutter="16">
          <el-col :span="12">
            <el-form-item label="发票类型">
              <el-select v-model="form.invoice_type" style="width:100%">
                <el-option label="增值税普通发票" :value="1" />
                <el-option label="增值税专用发票" :value="2" />
                <el-option label="电子发票" :value="3" />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="抬头类型">
              <el-select v-model="form.title_type" style="width:100%">
                <el-option label="企业" :value="1" />
                <el-option label="个人" :value="2" />
              </el-select>
            </el-form-item>
          </el-col>
        </el-row>
        <el-row :gutter="16">
          <el-col :span="12">
            <el-form-item label="抬头名称">
              <el-input v-model="form.title_name" placeholder="公司全称或个人姓名" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="税号">
              <el-input v-model="form.tax_id" placeholder="统一社会信用代码" />
            </el-form-item>
          </el-col>
        </el-row>
        <el-row :gutter="16">
          <el-col :span="8">
            <el-form-item label="金额">
              <el-input-number v-model="form.amount" :min="0" :precision="2" style="width:100%" controls-position="right" />
            </el-form-item>
          </el-col>
          <el-col :span="8">
            <el-form-item label="税率(%)">
              <el-input-number v-model="form.tax_rate" :min="0" :max="100" :precision="2" style="width:100%" controls-position="right" />
            </el-form-item>
          </el-col>
          <el-col :span="8">
            <el-form-item label="合计金额">
              <el-input-number v-model="form.total_amount" :min="0" :precision="2" style="width:100%" controls-position="right" />
            </el-form-item>
          </el-col>
        </el-row>
        <el-row :gutter="16">
          <el-col :span="12">
            <el-form-item label="开票时间">
              <el-date-picker v-model="form.issue_time" type="datetime" placeholder="开票时间" style="width:100%" value-format="YYYY-MM-DD HH:mm:ss" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="状态">
              <el-select v-model="form.status" style="width:100%">
                <el-option label="待开票" :value="0" />
                <el-option label="已开票" :value="1" />
                <el-option label="已作废" :value="2" />
              </el-select>
            </el-form-item>
          </el-col>
        </el-row>
        <el-form-item label="发票内容">
          <el-input v-model="form.content" placeholder="如：2024年物业费" />
        </el-form-item>
        <el-form-item label="备注">
          <el-input v-model="form.remark" type="textarea" :rows="2" />
        </el-form-item>
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
const communities = ref<any[]>([])
const ownerList = ref<any[]>([])

const query = reactive({ page: 1, limit: 15, keyword: '', community_id: undefined as any })
const form = reactive<any>({})

function resetForm() { Object.keys(form).forEach(k => delete form[k]); form.invoice_type = 1; form.title_type = 1; form.status = 0; form.amount = 0; form.tax_rate = 0; form.total_amount = 0 }

async function loadData() {
  loading.value = true
  try {
    const res = await apiGet('/admin/charge/invoiceList', { page: query.page, limit: query.limit, keyword: query.keyword, community_id: query.community_id || undefined })
    if (res.code === 0) { list.value = res.data || []; total.value = res.count || 0 }
  } finally { loading.value = false }
}

function resetQuery() { query.keyword = ''; query.community_id = undefined; query.page = 1; loadData() }

function openForm(row?: any) {
  if (row) { editId.value = row.id; Object.assign(form, { ...row }) }
  else { editId.value = 0; resetForm() }
  dialogVisible.value = true
}

async function onDialogOpen() { if (form.community_id) await onCommunityChange(form.community_id) }

async function onCommunityChange(cid: any) {
  if (!cid) { ownerList.value = []; return }
  const oRes = await apiGet('/admin/owner/list', { community_id: cid, limit: 999 })
  ownerList.value = oRes.data?.list || oRes.data || []
}

async function handleSubmit() {
  const url = editId.value ? '/admin/charge/invoiceEdit' : '/admin/charge/invoiceAdd'
  submitting.value = true
  try {
    const res = await apiPost(url, { ...form, id: editId.value || undefined })
    if (res.code === 0) { ElMessage.success(res.msg); dialogVisible.value = false; loadData() }
  } finally { submitting.value = false }
}

async function handleDelete(row: any) {
  await ElMessageBox.confirm('确定删除？', '提示', { type: 'warning' })
  const res = await apiPost('/admin/charge/invoiceDelete', { id: row.id })
  if (res.code === 0) { ElMessage.success('删除成功'); loadData() }
}

onMounted(async () => {
  try { const r = await apiGet('/admin/community/list', { limit: 999 }); communities.value = r.data?.list || r.data || [] } catch { }
  loadData()
})
</script>

<style scoped>
.page-container { padding: 16px; }
.search-bar { margin-bottom: 12px; }
.table-toolbar { margin-bottom: 12px; }
.pagination { margin-top: 12px; display: flex; justify-content: flex-end; }
</style>
