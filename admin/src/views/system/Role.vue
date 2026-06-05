<template>
  <div class="page-container">
    <div class="search-bar">
      <el-form :model="query" inline>
        <el-form-item><el-input v-model="query.keyword" placeholder="名称/编码" clearable style="width:200px;" /></el-form-item>
        <el-form-item>
          <el-button type="primary" @click="loadData">搜索</el-button>
          <el-button @click="resetQuery">重置</el-button>
        </el-form-item>
      </el-form>
    </div>
    <el-card shadow="never" class="table-card">
      <div class="table-toolbar"><el-button type="primary" @click="openForm()">添加角色</el-button></div>
      <el-table :data="list" v-loading="loading" stripe border>
        <el-table-column type="index" label="#" width="50" />
        <el-table-column prop="id" label="ID" width="60" />
        <el-table-column prop="name" label="角色名称" width="150" />
        <el-table-column prop="code" label="编码" width="130" />
        <el-table-column prop="description" label="描述" show-overflow-tooltip />
        <el-table-column prop="status" label="状态" width="80"><template #default="{row}"><el-tag :type="row.status===1?'success':'danger'">{{ row.status===1?'正常':'禁用' }}</el-tag></template></el-table-column>
        <el-table-column label="操作" width="280" fixed="right">
          <template #default="{row}">
            <el-button size="small" @click="openForm(row)">编辑</el-button>
            <el-button size="small" type="primary" @click="openPermission(row)">权限</el-button>
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
        <el-form-item label="角色名称" prop="name"><el-input v-model="form.name" placeholder="角色名称" /></el-form-item>
        <el-form-item label="编码" prop="code"><el-input v-model="form.code" placeholder="角色编码" /></el-form-item>
        <el-form-item label="描述"><el-input v-model="form.description" type="textarea" rows="3" placeholder="角色描述" /></el-form-item>
        <el-form-item label="状态">
          <el-radio-group v-model="form.status"><el-radio :value="1">正常</el-radio><el-radio :value="0">禁用</el-radio></el-radio-group>
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" @click="submitForm" :loading="submitting">确定</el-button>
      </template>
    </el-dialog>

    <el-dialog v-model="permVisible" title="权限设置" width="480px" destroy-on-close>
      <el-tree ref="permTreeRef" :data="menus" :props="{ label: 'name', children: 'children' }" show-checkbox node-key="id" :default-checked-keys="checkedIds" />
      <template #footer>
        <el-button @click="permVisible = false">取消</el-button>
        <el-button type="primary" @click="submitPermission" :loading="permSubmitting">保存</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, nextTick, watch } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { apiGet, apiPost } from '@/utils/request'

const list = ref<any[]>([])
const total = ref(0)
const loading = ref(false)
const dialogVisible = ref(false)
const permVisible = ref(false)
const submitting = ref(false)
const permSubmitting = ref(false)
const formRef = ref<any>(null)
const permTreeRef = ref<any>(null)
const formTitle = ref('添加角色')
const menus = ref<any[]>([])
const checkedIds = ref<number[]>([])
const permRoleId = ref(0)

const query = reactive({ keyword: '', page: 1, limit: 15 })
const form = reactive<any>({ id: 0, name: '', code: '', description: '', status: 1 })
const rules = { name: [{ required: true, message: '请输入角色名称', trigger: 'blur' }], code: [{ required: true, message: '请输入编码', trigger: 'blur' }] }

function resetQuery() { query.keyword = ''; query.page = 1; loadData() }

async function loadData() {
  loading.value = true
  try {
    const r = await apiGet('/admin/role/list', { ...query })
    list.value = r.data?.list || r.data || []
    total.value = r.count || r.data?.total || list.value.length
  } catch { list.value = []; total.value = 0 }
  finally { loading.value = false }
}

function openForm(row?: any) {
  formTitle.value = row ? '编辑角色' : '添加角色'
  Object.assign(form, row || { id: 0, name: '', code: '', description: '', status: 1 })
  dialogVisible.value = true
}

async function submitForm() {
  const valid = await formRef.value?.validate().catch(() => false)
  if (!valid) return
  submitting.value = true
  try {
    const url = form.id ? '/admin/role/edit' : '/admin/role/add'
    await apiPost(url, { ...form })
    ElMessage.success(form.id ? '修改成功' : '添加成功')
    dialogVisible.value = false
    loadData()
  } finally { submitting.value = false }
}

async function openPermission(row: any) {
  permRoleId.value = row.id
  try {
    const r = await apiGet('/admin/role/permission', { role_id: row.id })
    menus.value = r.data?.menus || []
    checkedIds.value = r.data?.checkedMenuIds || []
    permVisible.value = true
  } catch {}
}

// 弹窗打开后，等树组件完全挂载再设置选中状态
watch(permVisible, (val) => {
  if (!val) return
  nextTick(() => {
    setTimeout(() => {
      permTreeRef.value?.setCheckedKeys(checkedIds.value)
    }, 50)
  })
})

async function submitPermission() {
  const ids = permTreeRef.value?.getCheckedKeys() || []
  const halfIds = permTreeRef.value?.getHalfCheckedKeys() || []
  permSubmitting.value = true
  try {
    await apiPost('/admin/role/savePermission', { role_id: permRoleId.value, menu_ids: [...ids, ...halfIds] })
    ElMessage.success('权限保存成功')
    permVisible.value = false
  } finally { permSubmitting.value = false }
}

async function handleDelete(row: any) {
  try {
    await ElMessageBox.confirm(`确定删除角色 "${row.name}" 吗？`, '提示', { type: 'warning' })
    await apiPost('/admin/role/delete', { id: row.id })
    ElMessage.success('删除成功')
    loadData()
  } catch {}
}

onMounted(loadData)
</script>

<style scoped>
.search-bar { background:#fff;border-radius:8px;padding:16px 20px;margin-bottom:16px;border:1px solid #e2e8f0; }
.table-card { border-radius:8px;border:1px solid #e2e8f0; }
.table-toolbar { margin-bottom:16px; }
.pagination { margin-top:16px;display:flex;justify-content:flex-end; }
</style>
