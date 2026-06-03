<template>
  <div class="page-container">
    <div class="search-bar">
      <el-form :model="query" inline>
        <el-form-item>
          <el-input v-model="query.keyword" placeholder="押金编号/类型/备注" clearable style="width:220px" />
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
      <div class="table-toolbar"><el-button type="primary" @click="openForm()">+ 新增押金</el-button></div>
      <el-table :data="list" v-loading="loading" stripe border highlight-current-row>
        <el-table-column type="index" label="#" width="50" />
        <el-table-column prop="deposit_no" label="押金单号" width="160" />
        <el-table-column prop="category" label="押金类型" width="120" />
        <el-table-column label="金额" width="100" align="right">
          <template #default="{ row }">¥{{ row.amount }}</template>
        </el-table-column>
        <el-table-column label="支付方式" width="90">
          <template #default="{ row }">{{ methodMap[row.pay_method] || '-' }}</template>
        </el-table-column>
        <el-table-column prop="pay_time" label="缴纳时间" width="150" />
        <el-table-column label="状态" width="90">
          <template #default="{ row }">
            <el-tag v-if="row.status === 1">已退</el-tag>
            <el-tag v-else-if="row.status === 2" type="warning">部分退</el-tag>
            <el-tag v-else type="info">待退</el-tag>
          </template>
        </el-table-column>
        <el-table-column label="退款金额" width="100" align="right">
          <template #default="{ row }">{{ row.refund_amount ? '¥' + row.refund_amount : '-' }}</template>
        </el-table-column>
        <el-table-column prop="refund_time" label="退款时间" width="150" />
        <el-table-column prop="remark" label="备注" min-width="150" show-overflow-tooltip />
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

    <el-dialog v-model="dialogVisible" :title="editId ? '编辑押金' : '新增押金'" width="650px" destroy-on-close @opened="onDialogOpen">
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
            <el-form-item label="房间">
              <el-select v-model="form.room_id" placeholder="选择房间" style="width:100%" filterable clearable>
                <el-option v-for="r in roomList" :key="r.id" :label="r.building_name + ' - ' + r.room_number" :value="r.id" />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="押金单号">
              <el-input v-model="form.deposit_no" placeholder="留空自动生成" />
            </el-form-item>
          </el-col>
        </el-row>
        <el-row :gutter="16">
          <el-col :span="12">
            <el-form-item label="押金类型">
              <el-select v-model="form.category" placeholder="选择类型" style="width:100%" filterable allow-create>
                <el-option label="装修押金" value="装修押金" />
                <el-option label="租房押金" value="租房押金" />
                <el-option label="水电押金" value="水电押金" />
                <el-option label="门禁卡押金" value="门禁卡押金" />
                <el-option label="车位押金" value="车位押金" />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="金额">
              <el-input-number v-model="form.amount" :min="0" :precision="2" style="width:100%" controls-position="right" />
            </el-form-item>
          </el-col>
        </el-row>
        <el-row :gutter="16">
          <el-col :span="12">
            <el-form-item label="支付方式">
              <el-select v-model="form.pay_method" style="width:100%">
                <el-option v-for="(v,k) in methodMap" :key="k" :label="v" :value="Number(k)" />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="缴纳时间">
              <el-date-picker v-model="form.pay_time" type="datetime" placeholder="选择时间" style="width:100%" value-format="YYYY-MM-DD HH:mm:ss" />
            </el-form-item>
          </el-col>
        </el-row>
        <el-row :gutter="16">
          <el-col :span="12">
            <el-form-item label="状态">
              <el-select v-model="form.status" style="width:100%">
                <el-option label="待退" :value="0" />
                <el-option label="已退" :value="1" />
                <el-option label="部分退" :value="2" />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="退款金额">
              <el-input-number v-model="form.refund_amount" :min="0" :precision="2" style="width:100%" controls-position="right" />
            </el-form-item>
          </el-col>
        </el-row>
        <el-row :gutter="16">
          <el-col :span="12">
            <el-form-item label="退款时间">
              <el-date-picker v-model="form.refund_time" type="datetime" placeholder="选择时间" style="width:100%" value-format="YYYY-MM-DD HH:mm:ss" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="退款原因">
              <el-input v-model="form.refund_reason" placeholder="退款原因" />
            </el-form-item>
          </el-col>
        </el-row>
        <el-form-item label="扣款明细">
          <el-input v-model="form.deduct_detail" placeholder="如：墙面损坏扣200元" />
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
const roomList = ref<any[]>([])

const methodMap: Record<number, string> = { 1: '现金', 2: '微信', 3: '支付宝', 4: '银行卡', 5: 'POS刷卡', 6: '其他' }

const query = reactive({ page: 1, limit: 15, keyword: '', community_id: undefined as any })
const form = reactive<any>({})

function resetForm() { Object.keys(form).forEach(k => delete form[k]); form.status = 0; form.amount = 0; form.pay_method = 1 }

async function loadData() {
  loading.value = true
  try {
    const res = await apiGet('/admin/charge/depositList', { page: query.page, limit: query.limit, keyword: query.keyword, community_id: query.community_id || undefined })
    if (res.code === 0) { list.value = res.data || []; total.value = res.count || 0 }
  } finally { loading.value = false }
}

function resetQuery() { query.keyword = ''; query.community_id = undefined; query.page = 1; loadData() }

function openForm(row?: any) {
  if (row) { editId.value = row.id; Object.assign(form, { ...row, status: row.status ?? 0, amount: Number(row.amount) || 0, refund_amount: row.refund_amount ? Number(row.refund_amount) : undefined }) }
  else { editId.value = 0; resetForm() }
  dialogVisible.value = true
}

async function onDialogOpen() { if (form.community_id) await onCommunityChange(form.community_id) }

async function onCommunityChange(cid: any) {
  if (!cid) { ownerList.value = []; roomList.value = []; return }
  const oRes = await apiGet('/admin/owner/list', { community_id: cid, limit: 999 })
  ownerList.value = oRes.data?.list || oRes.data || []
  const rRes = await apiGet('/admin/room/list', { community_id: cid, limit: 999 })
  roomList.value = rRes.data?.list || rRes.data || []
}

async function handleSubmit() {
  const url = editId.value ? '/admin/charge/depositEdit' : '/admin/charge/depositAdd'
  submitting.value = true
  try {
    const res = await apiPost(url, { ...form, id: editId.value || undefined })
    if (res.code === 0) { ElMessage.success(res.msg); dialogVisible.value = false; loadData() }
  } finally { submitting.value = false }
}

async function handleDelete(row: any) {
  await ElMessageBox.confirm('确定删除？', '提示', { type: 'warning' })
  const res = await apiPost('/admin/charge/depositDelete', { id: row.id })
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
