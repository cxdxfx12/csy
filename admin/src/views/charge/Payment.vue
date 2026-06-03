<template>
  <div class="page-container">
    <div class="search-bar">
      <el-form :model="query" inline>
        <el-form-item>
          <el-input v-model="query.keyword" placeholder="流水号/姓名/房间" clearable style="width:220px" />
        </el-form-item>
        <el-form-item>
          <el-select v-model="query.community_id" placeholder="小区" clearable style="width:160px">
            <el-option v-for="c in communities" :key="c.id" :label="c.name" :value="c.id" />
          </el-select>
        </el-form-item>
        <el-form-item>
          <el-select v-model="query.pay_method" placeholder="支付方式" clearable style="width:130px">
            <el-option label="现金" :value="1" /><el-option label="微信" :value="2" />
            <el-option label="支付宝" :value="3" /><el-option label="银行卡" :value="4" />
            <el-option label="POS刷卡" :value="5" /><el-option label="其他" :value="6" />
          </el-select>
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="loadData">搜索</el-button>
          <el-button @click="resetQuery">重置</el-button>
        </el-form-item>
      </el-form>
    </div>
    <el-card shadow="never" class="table-card">
      <div class="table-toolbar"><el-button type="primary" @click="openForm()">+ 新增缴费</el-button></div>
      <el-table :data="list" v-loading="loading" stripe border highlight-current-row>
        <el-table-column type="index" label="#" width="50" />
        <el-table-column prop="payment_no" label="流水号" width="170" />
        <el-table-column prop="bill_no" label="关联账单" width="160" show-overflow-tooltip />
        <el-table-column prop="owner_name" label="业主" width="90" />
        <el-table-column prop="room_number" label="房间" width="110">
          <template #default="{ row }">{{ row.building_name }} {{ row.room_number }}</template>
        </el-table-column>
        <el-table-column prop="community_name" label="小区" width="110" />
        <el-table-column label="金额" width="100" align="right">
          <template #default="{ row }">¥{{ row.amount }}</template>
        </el-table-column>
        <el-table-column label="支付方式" width="90">
          <template #default="{ row }">{{ methodMap[row.pay_method] || row.pay_method }}</template>
        </el-table-column>
        <el-table-column prop="trade_no" label="交易流水" width="160" show-overflow-tooltip />
        <el-table-column prop="pay_time" label="缴费时间" width="155" />
        <el-table-column label="操作" width="150" fixed="right">
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

    <el-dialog v-model="dialogVisible" :title="editId ? '编辑缴费' : '新增缴费'" width="650px" destroy-on-close @opened="onDialogOpen">
      <el-form :model="form" ref="formRef" label-width="90px" :rules="rules">
        <el-row :gutter="16">
          <el-col :span="12">
            <el-form-item label="小区" prop="community_id">
              <el-select v-model="form.community_id" placeholder="选择小区" style="width:100%" @change="onCommunityChange" filterable clearable>
                <el-option v-for="c in communities" :key="c.id" :label="c.name" :value="c.id" />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="业主" prop="owner_id">
              <el-select v-model="form.owner_id" placeholder="选择业主" style="width:100%" filterable clearable>
                <el-option v-for="o in ownerList" :key="o.id" :label="o.realname + ' (' + o.phone + ')'" :value="o.id" />
              </el-select>
            </el-form-item>
          </el-col>
        </el-row>
        <el-row :gutter="16">
          <el-col :span="12">
            <el-form-item label="房间" prop="room_id">
              <el-select v-model="form.room_id" placeholder="选择房间" style="width:100%" filterable clearable>
                <el-option v-for="r in roomList" :key="r.id" :label="r.building_name + ' - ' + r.room_number" :value="r.id" />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="账单" prop="bill_id">
              <el-select v-model="form.bill_id" placeholder="关联账单（可选）" style="width:100%" filterable clearable>
                <el-option v-for="b in billList" :key="b.id" :label="b.bill_no + ' (¥' + b.total_amount + ')'" :value="b.id" />
              </el-select>
            </el-form-item>
          </el-col>
        </el-row>
        <el-row :gutter="16">
          <el-col :span="12">
            <el-form-item label="流水号" prop="payment_no">
              <el-input v-model="form.payment_no" placeholder="留空自动生成" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="金额" prop="amount">
              <el-input-number v-model="form.amount" :min="0" :precision="2" :step="100" style="width:100%" controls-position="right" />
            </el-form-item>
          </el-col>
        </el-row>
        <el-row :gutter="16">
          <el-col :span="12">
            <el-form-item label="支付方式" prop="pay_method">
              <el-select v-model="form.pay_method" style="width:100%">
                <el-option v-for="(v,k) in methodMap" :key="k" :label="v" :value="Number(k)" />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="交易流水号" prop="trade_no">
              <el-input v-model="form.trade_no" placeholder="第三方交易号" />
            </el-form-item>
          </el-col>
        </el-row>
        <el-row :gutter="16">
          <el-col :span="12">
            <el-form-item label="付款账户" prop="pay_account">
              <el-input v-model="form.pay_account" placeholder="微信号/支付宝号/银行卡号" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="缴费时间" prop="pay_time">
              <el-date-picker v-model="form.pay_time" type="datetime" placeholder="选择时间" style="width:100%" value-format="YYYY-MM-DD HH:mm:ss" />
            </el-form-item>
          </el-col>
        </el-row>
        <el-form-item label="备注">
          <el-input v-model="form.remark" type="textarea" :rows="2" placeholder="备注信息" />
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
import { ElMessage, ElMessageBox } from 'element-plus'
import { apiGet, apiPost } from '@/utils/request'

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
const billList = ref<any[]>([])

const methodMap: Record<number, string> = { 1: '现金', 2: '微信', 3: '支付宝', 4: '银行卡', 5: 'POS刷卡', 6: '其他' }

const query = reactive({ keyword: '', community_id: undefined as any, pay_method: undefined as any, page: 1, limit: 15 })
const form = reactive<any>({ community_id: '', owner_id: '', room_id: '', bill_id: '', payment_no: '', amount: 0, pay_method: 1, trade_no: '', pay_account: '', pay_time: '', remark: '' })
const rules = {
  community_id: [{ required: true, message: '请选择小区', trigger: 'change' }],
  owner_id: [{ required: true, message: '请选择业主', trigger: 'change' }],
  amount: [{ required: true, message: '请输入金额', trigger: 'blur' }],
}

function resetForm() {
  Object.assign(form, { community_id: '', owner_id: '', room_id: '', bill_id: '', payment_no: '', amount: 0, pay_method: 1, trade_no: '', pay_account: '', pay_time: '', remark: '' })
  ownerList.value = []; roomList.value = []; billList.value = []
}

function openForm(row?: any) {
  resetForm()
  if (row) {
    editId.value = row.id
    Object.assign(form, {
      community_id: row.community_id, owner_id: row.owner_id, room_id: row.room_id,
      bill_id: row.bill_id, payment_no: row.payment_no, amount: row.amount || 0,
      pay_method: row.pay_method, trade_no: row.trade_no || '',
      pay_account: row.pay_account || '', pay_time: row.pay_time || '', remark: row.remark || ''
    })
  } else {
    editId.value = 0
  }
  dialogVisible.value = true
}

// 延迟加载选择列表（dialog打开后加载）
async function onDialogOpen() {
  if (form.community_id) { await onCommunityChange(form.community_id) }
}

function resetQuery() { query.keyword = ''; query.community_id = undefined; query.pay_method = undefined; query.page = 1; loadData() }

async function loadData() {
  loading.value = true
  try {
    const r = await apiGet('/admin/charge/paymentList', { ...query })
    list.value = (r.code === 0 ? r.data : []) || []
    total.value = r.count || list.value.length
  } catch { list.value = []; total.value = 0 }
  finally { loading.value = false }
}

async function onCommunityChange(cid: any) {
  if (!cid) { ownerList.value = []; roomList.value = []; billList.value = []; return }
  try {
    const oRes = await apiGet('/admin/owner/list', { community_id: cid, limit: 999 })
    ownerList.value = oRes.data?.list || oRes.data || []
    const rRes = await apiGet('/admin/room/list', { community_id: cid, limit: 999 })
    roomList.value = rRes.data?.list || rRes.data || []
    const bRes = await apiGet('/admin/charge/billList', { community_id: cid, limit: 999 })
    billList.value = bRes.data?.list || bRes.data || []
  } catch { ownerList.value = []; roomList.value = []; billList.value = [] }
}

async function handleSubmit() {
  if (!formRef.value) return
  try {
    await formRef.value.validate()
  } catch { return }
  submitting.value = true
  try {
    const url = editId.value ? '/admin/charge/paymentEdit' : '/admin/charge/paymentAdd'
    const res = await apiPost(url, { ...form, id: editId.value || undefined })
    if (res.code === 0) { ElMessage.success(res.msg || '操作成功'); dialogVisible.value = false; loadData() }
    else { ElMessage.error(res.msg || '操作失败') }
  } finally { submitting.value = false }
}

async function handleDelete(row: any) {
  try {
    await ElMessageBox.confirm(`确定删除流水 "${row.payment_no}" 吗？`, '提示', { type: 'warning' })
    await apiPost('/admin/charge/paymentDelete', { id: row.id })
    ElMessage.success('删除成功')
    loadData()
  } catch { }
}

onMounted(async () => {
  try { const r = await apiGet('/admin/community/list', { limit: 999 }); communities.value = r.data?.list || r.data || [] } catch { }
  loadData()
})
</script>

<style scoped>
.page-container { padding: 16px; }
.search-bar { background: #fff; border-radius: 8px; padding: 16px 20px; margin-bottom: 16px; border: 1px solid #e2e8f0; }
.table-card { border-radius: 8px; border: 1px solid #e2e8f0; }
.table-toolbar { margin-bottom: 16px; }
.pagination { margin-top: 16px; display: flex; justify-content: flex-end; }
</style>
