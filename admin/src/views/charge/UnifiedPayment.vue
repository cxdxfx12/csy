<template>
  <div class="page-container">
    <div class="search-bar">
      <el-form :model="query" inline>
        <el-form-item>
          <el-input v-model="query.keyword" placeholder="支付单号/商户单号/渠道" clearable style="width:220px" />
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
      <div class="table-toolbar"><el-button type="primary" @click="openForm()">+ 新增支付</el-button></div>
      <el-table :data="list" v-loading="loading" stripe border highlight-current-row>
        <el-table-column type="index" label="#" width="50" />
        <el-table-column prop="payment_no" label="支付单号" width="170" />
        <el-table-column prop="out_trade_no" label="商户单号" width="170" show-overflow-tooltip />
        <el-table-column label="支付方式" width="100">
          <template #default="{ row }">{{ row.pay_method === 1 ? '微信' : row.pay_method === 2 ? '支付宝' : '其他' }}</template>
        </el-table-column>
        <el-table-column prop="pay_channel" label="渠道" width="90" />
        <el-table-column prop="business_type" label="业务类型" width="100" />
        <el-table-column label="金额" width="100" align="right">
          <template #default="{ row }">¥{{ row.amount }}</template>
        </el-table-column>
        <el-table-column label="实付" width="100" align="right">
          <template #default="{ row }">¥{{ row.actual_amount }}</template>
        </el-table-column>
        <el-table-column label="状态" width="90">
          <template #default="{ row }">
            <el-tag v-if="row.status === 2" type="success">已支付</el-tag>
            <el-tag v-else-if="row.status === 3" type="danger">已退款</el-tag>
            <el-tag v-else-if="row.status === 4" type="info">已关闭</el-tag>
            <el-tag v-else type="warning">待支付</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="pay_time" label="支付时间" width="150" />
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

    <el-dialog v-model="dialogVisible" :title="editId ? '编辑支付' : '新增支付'" width="650px" destroy-on-close @opened="onDialogOpen">
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
            <el-form-item label="支付单号">
              <el-input v-model="form.payment_no" placeholder="系统内部单号" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="商户单号">
              <el-input v-model="form.out_trade_no" placeholder="第三方商户单号" />
            </el-form-item>
          </el-col>
        </el-row>
        <el-row :gutter="16">
          <el-col :span="12">
            <el-form-item label="支付方式">
              <el-select v-model="form.pay_method" style="width:100%">
                <el-option label="微信支付" :value="1" />
                <el-option label="支付宝" :value="2" />
                <el-option label="其他" :value="3" />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="支付渠道">
              <el-select v-model="form.pay_channel" style="width:100%" filterable allow-create>
                <el-option label="公众号支付" value="公众号支付" />
                <el-option label="小程序支付" value="小程序支付" />
                <el-option label="扫码支付" value="扫码支付" />
                <el-option label="APP支付" value="APP支付" />
                <el-option label="H5支付" value="H5支付" />
              </el-select>
            </el-form-item>
          </el-col>
        </el-row>
        <el-row :gutter="16">
          <el-col :span="12">
            <el-form-item label="业务类型">
              <el-select v-model="form.business_type" style="width:100%" filterable allow-create>
                <el-option label="物业费" value="物业费" />
                <el-option label="水费" value="水费" />
                <el-option label="电费" value="电费" />
                <el-option label="停车费" value="停车费" />
                <el-option label="押金" value="押金" />
                <el-option label="维修费" value="维修费" />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="业务ID">
              <el-input v-model="form.business_id" placeholder="关联业务单据ID" />
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
            <el-form-item label="实付金额">
              <el-input-number v-model="form.actual_amount" :min="0" :precision="2" style="width:100%" controls-position="right" />
            </el-form-item>
          </el-col>
          <el-col :span="8">
            <el-form-item label="状态">
              <el-select v-model="form.status" style="width:100%">
                <el-option label="待支付" :value="0" />
                <el-option label="支付中" :value="1" />
                <el-option label="已支付" :value="2" />
                <el-option label="已退款" :value="3" />
                <el-option label="已关闭" :value="4" />
              </el-select>
            </el-form-item>
          </el-col>
        </el-row>
        <el-row :gutter="16">
          <el-col :span="12">
            <el-form-item label="支付时间">
              <el-date-picker v-model="form.pay_time" type="datetime" placeholder="支付时间" style="width:100%" value-format="YYYY-MM-DD HH:mm:ss" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="过期时间">
              <el-date-picker v-model="form.expire_time" type="datetime" placeholder="过期时间" style="width:100%" value-format="YYYY-MM-DD HH:mm:ss" />
            </el-form-item>
          </el-col>
        </el-row>
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

function resetForm() { Object.keys(form).forEach(k => delete form[k]); form.status = 0; form.pay_method = 1; form.amount = 0; form.actual_amount = 0 }

async function loadData() {
  loading.value = true
  try {
    const res = await apiGet('/admin/charge/unifiedPaymentList', { page: query.page, limit: query.limit, keyword: query.keyword, community_id: query.community_id || undefined })
    if (res.code === 0) { list.value = res.data || []; total.value = res.count || 0 }
  } finally { loading.value = false }
}

function resetQuery() { query.keyword = ''; query.community_id = undefined; query.page = 1; loadData() }

function openForm(row?: any) {
  if (row) { editId.value = row.id; Object.assign(form, { ...row, status: row.status ?? 0, amount: Number(row.amount) || 0, actual_amount: row.actual_amount ? Number(row.actual_amount) : 0 }) }
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
  const url = editId.value ? '/admin/charge/unifiedPaymentEdit' : '/admin/charge/unifiedPaymentAdd'
  submitting.value = true
  try {
    const res = await apiPost(url, { ...form, id: editId.value || undefined })
    if (res.code === 0) { ElMessage.success(res.msg); dialogVisible.value = false; loadData() }
  } finally { submitting.value = false }
}

async function handleDelete(row: any) {
  await ElMessageBox.confirm('确定删除？', '提示', { type: 'warning' })
  const res = await apiPost('/admin/charge/unifiedPaymentDelete', { id: row.id })
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
