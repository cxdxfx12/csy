<template>
  <div class="page-container">
    <div class="search-bar">
      <el-form :model="query" inline>
        <el-form-item><el-input v-model="query.keyword" placeholder="卡号/姓名" clearable style="width:200px;" /></el-form-item>
        <el-form-item><el-select v-model="query.community_id" placeholder="小区" clearable style="width:160px;"><el-option v-for="c in communities" :key="c.id" :label="c.name" :value="c.id" /></el-select></el-form-item>
        <el-form-item><el-select v-model="query.status" placeholder="状态" clearable style="width:120px;"><el-option label="正常" :value="1" /><el-option label="挂失" :value="2" /><el-option label="注销" :value="3" /></el-select></el-form-item>
        <el-form-item>
          <el-button type="primary" @click="loadData">搜索</el-button>
          <el-button @click="resetQuery">重置</el-button>
        </el-form-item>
      </el-form>
    </div>
    <el-card shadow="never" class="table-card">
      <div class="table-toolbar"><el-button type="primary" @click="openForm()">添加门禁卡</el-button></div>
      <el-table :data="list" v-loading="loading" stripe border>
        <el-table-column type="index" label="#" width="50" />
        <el-table-column prop="id" label="ID" width="60" />
        <el-table-column prop="card_no" label="卡号" width="150" />
        <el-table-column prop="community_name" label="小区" width="120" />
        <el-table-column prop="owner_name" label="持卡人" width="120" />
        <el-table-column prop="holder_type" label="类型" width="100"><template #default="{row}">{{ typeMap[row.holder_type]||row.holder_type }}</template></el-table-column>
        <el-table-column prop="status" label="状态" width="90"><template #default="{row}"><el-tag :type="statusType[row.status]||'info'">{{ statusMap[row.status]||'未知' }}</el-tag></template></el-table-column>
        <el-table-column prop="effective_date" label="发卡日期" width="110" />
        <el-table-column prop="expire_date" label="有效期至" width="110" />
        <el-table-column prop="create_time" label="创建时间" width="170" />
        <el-table-column label="操作" width="200" fixed="right">
          <template #default="{row}">
            <el-button size="small" @click="openForm(row)">编辑</el-button>
            <el-button size="small" type="danger" @click="handleDelete(row)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>
      <div class="pagination">
        <el-pagination v-model:current-page="query.page" v-model:page-size="query.limit" :total="total" :page-sizes="[15,30,50,100]" layout="total,sizes,prev,pager,next" @change="loadData" />
      </div>
    </el-card>

    <el-dialog v-model="dialogVisible" :title="formTitle" width="560px" destroy-on-close>
      <el-form :model="form" :rules="rules" ref="formRef" label-width="100px">
        <el-form-item label="所属小区" prop="community_id"><el-select v-model="form.community_id" placeholder="选择小区" style="width:100%;"><el-option v-for="c in communities" :key="c.id" :label="c.name" :value="c.id" /></el-select></el-form-item>
        <el-form-item label="卡号" prop="card_no"><el-input v-model="form.card_no" placeholder="门禁卡号" /></el-form-item>
        <el-form-item label="持卡人" prop="owner_id"><el-select v-model="form.owner_id" placeholder="选择业主" clearable style="width:100%;" @change="onOwnerChange"><el-option v-for="o in owners" :key="o.id" :label="o.realname" :value="o.id" /></el-select></el-form-item>
        <el-form-item label="持有人姓名"><el-input v-model="form.holder_name" placeholder="持卡人姓名" /></el-form-item>
        <el-form-item label="持有人手机"><el-input v-model="form.holder_phone" placeholder="持卡人手机" /></el-form-item>
        <el-form-item label="类型">
          <el-select v-model="form.holder_type" style="width:100%;"><el-option label="业主" :value="1" /><el-option label="家属" :value="2" /><el-option label="租户" :value="3" /><el-option label="物业人员" :value="4" /></el-select>
        </el-form-item>
        <el-form-item label="状态">
          <el-select v-model="form.status" style="width:100%;"><el-option label="正常" :value="1" /><el-option label="挂失" :value="0" /><el-option label="注销" :value="2" /></el-select>
        </el-form-item>
        <el-form-item label="发卡日期"><ElDatePicker v-model="form.effective_date" type="date" placeholder="选择日期" style="width:100%;" value-format="YYYY-MM-DD" /></el-form-item>
        <el-form-item label="有效期至"><ElDatePicker v-model="form.expire_date" type="date" placeholder="选择日期" style="width:100%;" value-format="YYYY-MM-DD" /></el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" @click="submitForm" :loading="submitting">确定</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox, ElDatePicker } from 'element-plus'
import { apiGet, apiPost } from '@/utils/request'

const list = ref<any[]>([])
const total = ref(0)
const loading = ref(false)
const dialogVisible = ref(false)
const submitting = ref(false)
const formRef = ref<any>(null)
const formTitle = ref('添加门禁卡')
const communities = ref<any[]>([])
const owners = ref<any[]>([])

const typeMap: Record<number, string> = { 1: '业主', 2: '家属', 3: '租户', 4: '物业人员' }
const statusMap: Record<number, string> = { 1: '正常', 0: '挂失', 2: '注销' }
const statusType: Record<number, string> = { 1: 'success', 0: 'warning', 2: 'danger' }

const query = reactive({ keyword: '', community_id: undefined as any, status: undefined as any, page: 1, limit: 15 })
const form = reactive<any>({ id: 0, community_id: '', card_no: '', owner_id: undefined, holder_name: '', holder_phone: '', holder_type: 1, status: 1, effective_date: '', expire_date: '' })

function onOwnerChange(val: any) {
  const o = owners.value.find((x: any) => x.id == val)
  if (o) { form.holder_name = o.realname; form.holder_phone = o.phone }
}
const rules = { card_no: [{ required: true, message: '请输入卡号', trigger: 'blur' }], community_id: [{ required: true, message: '请选择小区', trigger: 'change' }] }

function resetQuery() { query.keyword = ''; query.community_id = undefined; query.status = undefined; query.page = 1; loadData() }

async function loadData() {
  loading.value = true
  try {
    const r = await apiGet('/admin/security/accessCardList', { ...query })
    list.value = r.data?.list || r.data || []
    total.value = r.count || r.data?.total || list.value.length
  } catch { list.value = []; total.value = 0 }
  finally { loading.value = false }
}

function openForm(row?: any) {
  formTitle.value = row ? '编辑门禁卡' : '添加门禁卡'
  Object.assign(form, row || { id: 0, community_id: '', card_no: '', owner_id: undefined, holder_name: '', holder_phone: '', holder_type: 1, status: 1, effective_date: '', expire_date: '' })
  dialogVisible.value = true
}

async function submitForm() {
  const valid = await formRef.value?.validate().catch(() => false)
  if (!valid) return
  submitting.value = true
  try {
    const url = form.id ? '/admin/security/accessCardEdit' : '/admin/security/accessCardAdd'
    await apiPost(url, { ...form })
    ElMessage.success(form.id ? '修改成功' : '添加成功')
    dialogVisible.value = false
    loadData()
  } finally { submitting.value = false }
}

async function handleDelete(row: any) {
  try {
    await ElMessageBox.confirm(`确定删除门禁卡 "${row.card_no}" 吗？`, '提示', { type: 'warning' })
    await apiPost('/admin/security/accessCardDelete', { id: row.id })
    ElMessage.success('删除成功')
    loadData()
  } catch {}
}

onMounted(async () => {
  try {
    const rc = await apiGet('/admin/community/list', { limit: 999 })
    communities.value = rc.data?.list || rc.data || []
    const ro = await apiGet('/admin/owner/list', { limit: 999 })
    owners.value = ro.data?.list || ro.data || []
  } catch {}
  loadData()
})
</script>

<style scoped>
.search-bar { background:#fff;border-radius:8px;padding:16px 20px;margin-bottom:16px;border:1px solid #e2e8f0; }
.table-card { border-radius:8px;border:1px solid #e2e8f0; }
.table-toolbar { margin-bottom:16px; }
.pagination { margin-top:16px;display:flex;justify-content:flex-end; }
</style>
