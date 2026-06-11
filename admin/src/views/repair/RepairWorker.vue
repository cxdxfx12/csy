<template>
  <div class="page-container">
    <div class="search-bar">
      <el-form :model="query" inline>
        <el-form-item><el-input v-model="query.keyword" placeholder="姓名/手机" clearable style="width:200px;" /></el-form-item>
        <el-form-item><el-select v-model="query.community_id" placeholder="小区" clearable style="width:160px;"><el-option v-for="c in communities" :key="c.id" :label="c.name" :value="c.id" /></el-select></el-form-item>
        <el-form-item>
          <el-button type="primary" @click="loadData">搜索</el-button>
          <el-button @click="resetQuery">重置</el-button>
        </el-form-item>
      </el-form>
    </div>
    <el-card shadow="never" class="table-card">
      <div class="table-toolbar"><el-button type="primary" @click="openForm()">添加维修人员</el-button></div>
      <el-table :data="list" v-loading="loading" stripe border>
        <el-table-column type="index" label="#" width="50" />
        <el-table-column prop="id" label="ID" width="60" />
        <el-table-column prop="staff_job_no" label="员工工号" width="110" />
        <el-table-column prop="name" label="姓名" width="100" />
        <el-table-column prop="staff_department" label="部门" width="100" />
        <el-table-column prop="staff_position" label="岗位" width="100" />
        <el-table-column prop="phone" label="手机" width="130" />
        <el-table-column prop="community_name" label="小区" width="120" />
        <el-table-column prop="specialty" label="专长" width="150" />
        <el-table-column prop="status" label="状态" width="80"><template #default="{row}"><el-tag :type="row.status===1?'success':'danger'">{{ row.status===1?'在职':'离职' }}</el-tag></template></el-table-column>
        <el-table-column prop="order_count" label="工单数" width="80" />
        <el-table-column prop="create_time" label="入职时间" width="170" />
        <el-table-column label="操作" width="200" fixed="right">
          <template #default="{row}">
            <el-button size="small" @click="openForm(row)">编辑</el-button>
            <el-button size="small" type="danger" @click="handleDelete(row)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>
      <div class="pagination">
        <el-pagination v-model:current-page="query.page" v-model:page-size="query.limit" :total="total" :page-sizes="[15,30,50,100]" layout="total,sizes,prev,pager,next" @current-change="loadData" @size-change="loadData" />
      </div>
    </el-card>

      <el-dialog v-model="dialogVisible" :title="formTitle" width="560px" destroy-on-close @opened="loadStaffList">
      <el-form :model="form" :rules="rules" ref="formRef" label-width="100px">
        <el-form-item label="关联员工" prop="staff_id">
          <el-select v-model="form.staff_id" filterable placeholder="选择员工档案中的员工" style="width:100%;" @change="onStaffChange">
            <el-option v-for="s in staffList" :key="s.id" :label="`${s.job_no} - ${s.realname} (${s.department}-${s.position})`" :value="s.id">
              <span>{{ s.job_no }}</span>
              <span style="margin-left:8px;font-weight:600;">{{ s.realname }}</span>
              <span style="margin-left:8px;color:#999;">{{ s.department }}-{{ s.position }}</span>
            </el-option>
          </el-select>
        </el-form-item>
        <el-form-item label="姓名"><el-input v-model="form.name" placeholder="自动从员工档案获取" disabled /></el-form-item>
        <el-form-item label="手机号"><el-input v-model="form.phone" placeholder="自动从员工档案获取" disabled /></el-form-item>
        <el-form-item label="登录密码">
          <el-input v-model="form.password" type="password" :placeholder="form.id ? '留空不修改密码' : '设置登录密码'" show-password autocomplete="new-password" />
        </el-form-item>
        <el-form-item label="所属小区"><el-input v-model="form.community_name" placeholder="自动从员工档案获取" disabled /></el-form-item>
        <el-form-item label="专长"><el-input v-model="form.specialty" placeholder="如：水电、门窗、家电、管道" /></el-form-item>
        <el-form-item label="状态">
          <el-radio-group v-model="form.status"><el-radio :value="1">在职</el-radio><el-radio :value="0">离职</el-radio></el-radio-group>
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

const list = ref<any[]>([])
const total = ref(0)
const loading = ref(false)
const dialogVisible = ref(false)
const submitting = ref(false)
const formRef = ref<any>(null)
const formTitle = ref('添加维修人员')
const communities = ref<any[]>([])
const staffList = ref<any[]>([])

const query = reactive({ keyword: '', community_id: undefined as any, page: 1, limit: 15 })
const form = reactive<any>({ id: 0, staff_id: undefined, name: '', phone: '', password: '', community_name: '', specialty: '', status: 1 })
const rules = { staff_id: [{ required: true, message: '请选择关联员工', trigger: 'change' }] }

function resetQuery() { query.keyword = ''; query.community_id = undefined; query.page = 1; loadData() }

async function loadData() {
  loading.value = true
  try {
    const r = await apiGet('/admin/repair/workerList', { ...query })
    list.value = r.data?.list || r.data || []
    total.value = r.count || r.data?.total || list.value.length
  } catch { list.value = []; total.value = 0 }
  finally { loading.value = false }
}

async function loadStaffList() {
  try {
    const params: any = {}
    if (query.community_id) params.community_id = query.community_id
    // 编辑时也需要把当前关联的员工包含进去
    if (form.staff_id && form.id) params.include_id = form.staff_id
    const r = await apiGet('/admin/repair/staffList', params)
    staffList.value = r.data || []
  } catch { staffList.value = [] }
}

function onStaffChange(val: number) {
  const selected = staffList.value.find((s: any) => s.id === val)
  if (selected) {
    form.name = selected.realname
    form.phone = selected.phone
    form.community_name = communities.value.find((c: any) => c.id === selected.community_id)?.name || ''
  }
}

function openForm(row?: any) {
  formTitle.value = row ? '编辑维修人员' : '添加维修人员'
  if (row) {
    Object.assign(form, {
      id: row.id, staff_id: row.staff_id || undefined,
      name: row.name, phone: row.phone,
      password: '', // 编辑时不展示旧密码
      community_name: row.community_name || '',
      specialty: row.type || row.specialty || '', status: row.status
    })
  } else {
    Object.assign(form, { id: 0, staff_id: undefined, name: '', phone: '', password: '', community_name: '', specialty: '', status: 1 })
  }
  dialogVisible.value = true
}

async function submitForm() {
  try { await formRef.value?.validate() } catch { return }
  submitting.value = true
  try {
    const url = form.id ? '/admin/repair/workerEdit' : '/admin/repair/workerAdd'
    await apiPost(url, { ...form })
    ElMessage.success(form.id ? '修改成功' : '添加成功')
    dialogVisible.value = false
    loadData()
  } finally { submitting.value = false }
}

async function handleDelete(row: any) {
  try {
    await ElMessageBox.confirm(`确定删除维修人员 "${row.name}" 吗？`, '提示', { type: 'warning' })
    await apiPost('/admin/repair/workerDelete', { id: row.id })
    ElMessage.success('删除成功')
    loadData()
  } catch {}
}

onMounted(async () => {
  try { const r = await apiGet('/admin/community/list', { limit: 999 }); communities.value = r.data?.list || r.data || [] } catch {}
  loadData()
})
</script>

<style scoped>
.search-bar { background:#fff;border-radius:8px;padding:16px 20px;margin-bottom:16px;border:1px solid #e2e8f0; }
.table-card { border-radius:8px;border:1px solid #e2e8f0; }
.table-toolbar { margin-bottom:16px; }
.pagination { margin-top:16px;display:flex;justify-content:flex-end; }
</style>
