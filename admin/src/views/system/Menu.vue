<template>
  <div class="page-container">
    <div class="search-bar">
      <el-form :model="query" inline>
        <el-form-item><el-input v-model="query.keyword" placeholder="菜单名称" clearable style="width:200px;" /></el-form-item>
        <el-form-item>
          <el-button type="primary" @click="loadData">搜索</el-button>
          <el-button @click="resetQuery">重置</el-button>
        </el-form-item>
      </el-form>
    </div>
    <el-card shadow="never" class="table-card">
      <div class="table-toolbar"><el-button type="primary" @click="openForm()">添加菜单</el-button></div>
      <el-table :data="list" v-loading="loading" stripe border row-key="id" :tree-props="{ children: 'children' }">
        <el-table-column prop="name" label="菜单名称" width="180" />
        <el-table-column prop="route" label="路由" width="180" />
        <el-table-column prop="permission" label="权限标识" width="180" />
        <el-table-column prop="sort" label="排序" width="70" />
        <el-table-column prop="status" label="状态" width="80"><template #default="{row}"><el-tag :type="row.status===1?'success':'danger'">{{ row.status===1?'正常':'禁用' }}</el-tag></template></el-table-column>
        <el-table-column label="操作" width="200" fixed="right">
          <template #default="{row}">
            <el-button size="small" @click="openForm(row)">编辑</el-button>
            <el-button size="small" type="danger" @click="handleDelete(row)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>
    </el-card>

    <el-dialog v-model="dialogVisible" :title="formTitle" width="560px" destroy-on-close>
      <el-form :model="form" :rules="rules" ref="formRef" label-width="100px">
        <el-form-item label="上级菜单"><el-select v-model="form.parent_id" placeholder="顶级菜单" clearable style="width:100%;"><el-option v-for="m in flatMenus" :key="m.id" :label="m.name" :value="m.id" /></el-select></el-form-item>
        <el-form-item label="菜单名称" prop="name"><el-input v-model="form.name" placeholder="菜单名称" /></el-form-item>
        <el-form-item label="路由"><el-input v-model="form.route" placeholder="路由路径" /></el-form-item>
        <el-form-item label="权限标识"><el-input v-model="form.permission" placeholder="如：system:admin" /></el-form-item>
        <el-form-item label="图标"><el-input v-model="form.icon" placeholder="图标类名" /></el-form-item>
        <el-form-item label="排序"><el-input-number v-model="form.sort" :min="0" style="width:100%;" /></el-form-item>
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
import { ref, reactive, onMounted, computed } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { apiGet, apiPost } from '@/utils/request'

const list = ref<any[]>([])
const loading = ref(false)
const dialogVisible = ref(false)
const submitting = ref(false)
const formRef = ref<any>(null)
const formTitle = ref('添加菜单')

const query = reactive({ keyword: '' })
const form = reactive<any>({ id: 0, parent_id: undefined, name: '', route: '', permission: '', icon: '', sort: 0, status: 1 })
const rules = { name: [{ required: true, message: '请输入菜单名称', trigger: 'blur' }] }

function resetQuery() { query.keyword = ''; loadData() }

function flatten(arr: any[]): any[] {
  return arr.reduce((acc, cur) => {
    acc.push(cur)
    if (cur.children?.length) acc.push(...flatten(cur.children))
    return acc
  }, [])
}

const flatMenus = computed(() => flatten(list.value))

async function loadData() {
  loading.value = true
  try {
    const r = await apiGet('/admin/menu/list')
    list.value = r.data || []
  } catch { list.value = [] }
  finally { loading.value = false }
}

function openForm(row?: any) {
  formTitle.value = row ? '编辑菜单' : '添加菜单'
  Object.assign(form, row || { id: 0, parent_id: undefined, name: '', route: '', permission: '', icon: '', sort: 0, status: 1 })
  dialogVisible.value = true
}

async function submitForm() {
  const valid = await formRef.value?.validate().catch(() => false)
  if (!valid) return
  submitting.value = true
  try {
    const url = form.id ? '/admin/menu/edit' : '/admin/menu/add'
    await apiPost(url, { ...form })
    ElMessage.success(form.id ? '修改成功' : '添加成功')
    dialogVisible.value = false
    loadData()
  } finally { submitting.value = false }
}

async function handleDelete(row: any) {
  try {
    await ElMessageBox.confirm(`确定删除菜单 "${row.name}" 吗？`, '提示', { type: 'warning' })
    await apiPost('/admin/menu/delete', { id: row.id })
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
</style>
