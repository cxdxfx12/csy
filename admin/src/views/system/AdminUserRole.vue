<template>
  <div class="page-container">
    <div class="search-bar">
      <el-form :model="query" inline>
        <el-form-item><el-input v-model="query.keyword" placeholder="关键字" clearable style="width:200px" /></el-form-item>
        <el-form-item><el-button type="primary" @click="loadData">搜索</el-button><el-button @click="resetQuery">重置</el-button></el-form-item>
      </el-form>
    </div>
    <el-card shadow="never" class="table-card">
      <div class="table-toolbar"><el-button type="primary" @click="openForm()">添加管理员角色</el-button></div>
      <el-table :data="list" v-loading="loading" stripe border>
        <el-table-column type="index" label="#" width="50" />
        <el-table-column prop="id" label="ID" width="60" />
        <el-table-column prop="admin_id" label="操作人ID" width="120" />
        <el-table-column prop="role_id" label="角色ID" width="120" />
<el-table-column label="操作" width="160" fixed="right">
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

    <el-dialog v-model="dialogVisible" :title="editId ? '编辑管理员角色' : '添加管理员角色'" width="600px" destroy-on-close>
      <el-form :model="form" ref="formRef" label-width="100px">
        <el-form-item label="操作人ID"><el-input-number v-model="form.admin_id" :min="0" style="width:100%" /></el-form-item>
        <el-form-item label="角色ID"><el-input-number v-model="form.role_id" :min="0" style="width:100%" /></el-form-item>

      </el-form>
      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" @click="handleSubmit">确定</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { apiGet, apiPost } from '@/utils/request'
import { ElMessage, ElMessageBox } from 'element-plus'
import { formatTime } from '@/utils/format'

const list = ref<any[]>([])
const total = ref(0)
const loading = ref(false)
const dialogVisible = ref(false)
const editId = ref(0)
const formRef = ref()
const query = reactive({ page: 1, limit: 15, keyword: '' })
const form = reactive<any>({})

const apiBase = '/admin/system/'  // eslint 占位

async function loadData() {
  loading.value = true
  try {
    const res = await apiGet('/admin/system/adminUserRoleList', { params: { page: query.page, limit: query.limit, keyword: query.keyword } })
    if (res.code === 0) { list.value = res.data.list; total.value = res.data.total }
  } finally { loading.value = false }
}

function resetQuery() { query.keyword = ''; query.page = 1; loadData() }

function openForm(row?: any) {
  if (row) { editId.value = row.id; Object.assign(form, row) }
  else { editId.value = 0; Object.keys(form).forEach(k => delete form[k]) }
  dialogVisible.value = true
}

async function handleSubmit() {
  const url = editId.value ? '/admin/system/adminUserRoleEdit' : '/admin/system/adminUserRoleAdd'
  const res = await apiPost(url, { ...form, id: editId.value || undefined })
  if (res.code === 0) { ElMessage.success(res.msg); dialogVisible.value = false; loadData() }
}

async function handleDelete(row: any) {
  await ElMessageBox.confirm('确定删除？', '提示', { type: 'warning' })
  const res = await apiPost('/admin/system/adminUserRoleDelete', { id: row.id })
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