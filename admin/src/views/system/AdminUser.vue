<template>
  <div class="page-container">
    <div class="search-bar">
      <el-form :model="query" inline>
        <el-form-item><el-input v-model="query.keyword" placeholder="用户名/昵称/手机" clearable style="width:200px;" /></el-form-item>
        <el-form-item><el-select v-model="query.role_id" placeholder="角色" clearable style="width:140px;"><el-option v-for="r in roles" :key="r.id" :label="r.name" :value="r.id" /></el-select></el-form-item>
        <el-form-item><el-select v-model="query.status" placeholder="状态" clearable style="width:120px;"><el-option label="正常" :value="1" /><el-option label="禁用" :value="0" /></el-select></el-form-item>
        <el-form-item><el-button type="primary" @click="loadData">🔍 搜索</el-button><el-button @click="resetQuery">重置</el-button></el-form-item>
      </el-form>
    </div>
    <el-card shadow="never" class="table-card">
      <div class="table-toolbar"><el-button type="primary" @click="openForm()">✨ 添加管理员</el-button></div>
      <el-table :data="list" v-loading="loading" stripe border @sort-change="handleSort">
        <el-table-column prop="id" label="ID" width="60" sortable />
        <el-table-column prop="username" label="用户名" width="120" />
        <el-table-column prop="nickname" label="昵称" width="140" />
        <el-table-column prop="role_name" label="角色" width="120" />
        <el-table-column prop="phone" label="手机号" width="130" />
        <el-table-column prop="status" label="状态" width="80">
          <template #default="{ row }"><el-tag :type="row.status===1?'success':'danger'">{{ row.status===1?'正常':'禁用' }}</el-tag></template>
        </el-table-column>
        <el-table-column prop="last_login_time" label="最后登录" width="170" :formatter="(r:any)=>formatTime(r.last_login_time)" />
        <el-table-column prop="last_login_ip" label="登录IP" width="140" />
        <el-table-column label="操作" width="200" fixed="right">
          <template #default="{ row }">
            <el-button size="small" @click="openForm(row)">编辑</el-button>
            <el-button size="small" type="warning" @click="changePwd(row)">改密</el-button>
            <el-button v-if="row.id!==1" :type="row.status===1?'danger':'success'" size="small" @click="toggleStatus(row)">{{ row.status===1?'禁用':'启用' }}</el-button>
          </template>
        </el-table-column>
      </el-table>
      <div class="pagination">
        <el-pagination v-model:current-page="query.page" v-model:page-size="query.limit" :total="total" :page-sizes="[15,30,50,100]" layout="total,sizes,prev,pager,next" @change="loadData" />
      </div>
    </el-card>

    <el-dialog v-model="dialogVisible" :title="formTitle" width="560px" destroy-on-close>
      <el-form :model="form" :rules="rules" ref="formRef" label-width="100px">
        <el-form-item label="用户名" prop="username"><el-input v-model="form.username" placeholder="登录用户名" :disabled="!!form.id" /></el-form-item>
        <el-form-item label="昵称" prop="nickname"><el-input v-model="form.nickname" placeholder="显示昵称" /></el-form-item>
        <el-form-item label="手机号"><el-input v-model="form.phone" placeholder="手机号" maxlength="11" /></el-form-item>
        <el-form-item label="角色" prop="role_id"><el-select v-model="form.role_id" placeholder="选择角色" style="width:100%;"><el-option v-for="r in roles" :key="r.id" :label="r.name" :value="r.id" /></el-select></el-form-item>
        <el-form-item label="密码" :prop="form.id ? '' : 'password'"><el-input v-model="form.password" placeholder="密码" type="password" /></el-form-item>
        <el-form-item label="状态">
          <el-radio-group v-model="form.status"><el-radio :label="1">正常</el-radio><el-radio :label="0">禁用</el-radio></el-radio-group>
        </el-form-item>
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
import { formatTime } from '@/utils/format'

const list = ref<any[]>([])
const total = ref(0)
const loading = ref(false)
const dialogVisible = ref(false)
const submitting = ref(false)
const formRef = ref<any>(null)
const formTitle = ref('添加管理员')
const roles = ref<any[]>([])

const query = reactive({ keyword: '', role_id: undefined as any, status: undefined as any, page: 1, limit: 15, sort: '', order: '' })
const form = reactive<any>({ id: 0, username: '', nickname: '', phone: '', role_id: '', password: '', status: 1 })
const rules = { username: [{ required: true, message: '请输入用户名', trigger: 'blur' }], nickname: [{ required: true, message: '请输入昵称', trigger: 'blur' }], role_id: [{ required: true, message: '请选择角色', trigger: 'change' }], password: [{ required: true, message: '请输入密码', trigger: 'blur' }] }

function resetQuery() { Object.assign(query, { keyword: '', role_id: undefined, status: undefined, page: 1 }) }

function handleSort(s: any) { query.sort = s.prop || ''; query.order = s.order === 'ascending' ? 'asc' : s.order === 'descending' ? 'desc' : ''; loadData() }

async function loadData() {
  loading.value = true
  try {
    const res = await apiGet('/admin/AdminUser/lists', { ...query })
    list.value = res.data.list || res.data
    total.value = res.count || res.data.total || list.value.length
  } catch { list.value = []; total.value = 0 }
  finally { loading.value = false }
}

function openForm(row?: any) {
  formTitle.value = row ? '编辑管理员' : '添加管理员'
  Object.assign(form, row || { id: 0, username: '', nickname: '', phone: '', role_id: '', password: '', status: 1 })
  if (row) form.password = ''
  dialogVisible.value = true
}

async function submitForm() {
  const valid = await formRef.value?.validate().catch(() => false)
  if (!valid) return
  submitting.value = true
  try {
    const url = form.id ? '/admin/user/edit' : '/admin/user/add'
    const payload = { ...form }
    if (form.id && !payload.password) delete payload.password
    await apiPost(url, payload)
    ElMessage.success(form.id ? '修改成功' : '添加成功')
    dialogVisible.value = false
    loadData()
  } finally { submitting.value = false }
}

async function changePwd(row: any) {
  ElMessageBox.prompt('请输入新密码', '修改密码', { inputType: 'password', inputValidator: (v: string) => v.length >= 6 || '密码至少6位' }).then(async ({ value }) => {
    await apiPost('/admin/AdminUser/changePassword', { id: row.id, password: value })
    ElMessage.success('密码修改成功')
  }).catch(() => {})
}

async function toggleStatus(row: any) {
  const newStatus = row.status === 1 ? 0 : 1
  await apiPost('/admin/AdminUser/status', { id: row.id, status: newStatus })
  ElMessage.success('操作成功')
  loadData()
}

onMounted(async () => {
  try { const r = await apiGet('/admin/role/list'); roles.value = r.data || [] } catch {}
  loadData()
})
</script>
