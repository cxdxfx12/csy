<template>
  <div class="page-container">
    <div class="search-bar">
      <el-form :model="query" inline>
        <el-form-item><el-input v-model="query.keyword" placeholder="姓名/手机" clearable style="width:200px;" /></el-form-item>
        <el-form-item><el-select v-model="query.community_id" placeholder="小区" clearable style="width:160px;"><el-option v-for="c in communities" :key="c.id" :label="c.name" :value="c.id" /></el-select></el-form-item>
        <el-form-item><el-select v-model="query.status" placeholder="状态" clearable style="width:120px;"><el-option label="待进入" :value="1" /><el-option label="已进入" :value="2" /><el-option label="已离开" :value="3" /></el-select></el-form-item>
        <el-form-item>
          <el-button type="primary" @click="loadData">搜索</el-button>
          <el-button @click="resetQuery">重置</el-button>
        </el-form-item>
      </el-form>
    </div>
    <el-card shadow="never" class="table-card">
      <div class="table-toolbar"><el-button type="primary" @click="openForm()">访客登记</el-button></div>
      <el-table :data="list" v-loading="loading" stripe border>
        <el-table-column type="index" label="#" width="50" />
        <el-table-column prop="id" label="ID" width="60" />
        <el-table-column prop="name" label="访客姓名" width="120" />
        <el-table-column prop="phone" label="手机" width="130" />
        <el-table-column prop="id_card" label="身份证" width="180" />
        <el-table-column prop="owner_name" label="受访业主" width="120" />
        <el-table-column prop="room_number" label="房间" width="100" />
        <el-table-column prop="community_name" label="小区" width="120" />
        <el-table-column prop="visit_purpose" label="来访事由" width="120" />
        <el-table-column prop="status" label="状态" width="90"><template #default="{row}"><el-tag :type="statusType[row.status]||'info'">{{ statusMap[row.status]||'未知' }}</el-tag></template></el-table-column>
        <el-table-column prop="create_time" label="登记时间" width="170" />
        <el-table-column label="操作" width="240" fixed="right">
          <template #default="{row}">
            <el-button v-if="row.status!==3" size="small" type="warning" @click="handleLeave(row)">标记离开</el-button>
            <el-button size="small" @click="openForm(row)">编辑</el-button>
            <el-button size="small" type="danger" @click="handleDelete(row)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>
      <div class="pagination">
        <el-pagination v-model:current-page="query.page" v-model:page-size="query.limit" :total="total" :page-sizes="[15,30,50,100]" layout="total,sizes,prev,pager,next" @current-change="loadData" @size-change="loadData" />
      </div>
    </el-card>

    <el-dialog v-model="dialogVisible" :title="formTitle" width="560px" destroy-on-close>
      <el-form :model="form" :rules="rules" ref="formRef" label-width="100px">
        <el-form-item label="访客姓名" prop="name"><el-input v-model="form.name" placeholder="访客姓名" /></el-form-item>
        <el-form-item label="手机号" prop="phone"><el-input v-model="form.phone" placeholder="手机号" maxlength="11" /></el-form-item>
        <el-form-item label="身份证"><el-input v-model="form.id_card" placeholder="身份证号码" maxlength="18" /></el-form-item>
        <el-form-item label="受访业主" prop="owner_id"><el-select v-model="form.owner_id" placeholder="选择业主" clearable style="width:100%;" @change="onOwnerChange"><el-option v-for="o in owners" :key="o.id" :label="o.realname + ' ' + o.phone" :value="o.id" /></el-select></el-form-item>
        <el-form-item label="来访事由"><el-input v-model="form.visit_purpose" placeholder="来访事由" /></el-form-item>
        <el-form-item label="预计人数"><el-input-number v-model="form.visitor_count" :min="1" style="width:100%;" /></el-form-item>
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
import { ElMessage, ElMessageBox } from 'element-plus'
import { apiGet, apiPost } from '@/utils/request'

const list = ref<any[]>([])
const total = ref(0)
const loading = ref(false)
const dialogVisible = ref(false)
const submitting = ref(false)
const formRef = ref<any>(null)
const formTitle = ref('访客登记')
const communities = ref<any[]>([])
const owners = ref<any[]>([])

const statusMap: Record<number, string> = { 1: '待进入', 2: '已进入', 3: '已离开' }
const statusType: Record<number, string> = { 1: 'info', 2: 'success', 3: 'danger' }

const query = reactive({ keyword: '', community_id: undefined as any, status: undefined as any, page: 1, limit: 15 })
const form = reactive<any>({ id: 0, name: '', phone: '', id_card: '', owner_id: undefined, room_id: undefined, visit_purpose: '', visitor_count: 1 })
const rules = { name: [{ required: true, message: '请输入访客姓名', trigger: 'blur' }], phone: [{ required: true, message: '请输入手机号', trigger: 'blur' }] }

function resetQuery() { query.keyword = ''; query.community_id = undefined; query.status = undefined; query.page = 1; loadData() }

async function loadData() {
  loading.value = true
  try {
    const r = await apiGet('/admin/security/visitorList', { ...query })
    list.value = r.data?.list || r.data || []
    total.value = r.count || r.data?.total || list.value.length
  } catch { list.value = []; total.value = 0 }
  finally { loading.value = false }
}

async function onOwnerChange(val: any) {
  const o = owners.value.find((x: any) => x.id == val)
  form.room_id = o?.room_id || undefined
}

function openForm(row?: any) {
  formTitle.value = row ? '编辑访客' : '访客登记'
  Object.assign(form, row || { id: 0, name: '', phone: '', id_card: '', owner_id: undefined, room_id: undefined, visit_purpose: '', visitor_count: 1 })
  dialogVisible.value = true
}

async function submitForm() {
  const valid = await formRef.value?.validate().catch(() => false)
  if (!valid) return
  submitting.value = true
  try {
    const url = form.id ? '/admin/security/visitorEdit' : '/admin/security/visitorAdd'
    await apiPost(url, { ...form })
    ElMessage.success(form.id ? '修改成功' : '登记成功')
    dialogVisible.value = false
    loadData()
  } finally { submitting.value = false }
}

async function handleLeave(row: any) {
  try {
    await ElMessageBox.confirm(`确定标记访客 "${row.name}" 已离开吗？`, '提示', { type: 'warning' })
    await apiPost('/admin/security/visitorLeave', { id: row.id })
    ElMessage.success('已标记离开')
    loadData()
  } catch {}
}

async function handleDelete(row: any) {
  try {
    await ElMessageBox.confirm(`确定删除访客记录 "${row.name}" 吗？`, '提示', { type: 'warning' })
    await apiPost('/admin/security/visitorDelete', { id: row.id })
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
