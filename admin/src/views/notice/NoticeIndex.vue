<template>
  <div class="page-container">
    <div class="search-bar">
      <el-form :model="query" inline>
        <el-form-item><el-input v-model="query.keyword" placeholder="标题" clearable style="width:200px;" /></el-form-item>
        <el-form-item><el-select v-model="query.community_id" placeholder="小区" clearable style="width:160px;"><el-option v-for="c in communities" :key="c.id" :label="c.name" :value="c.id" /></el-select></el-form-item>
        <el-form-item><el-select v-model="query.status" placeholder="状态" clearable style="width:120px;"><el-option label="草稿" :value="1" /><el-option label="已发布" :value="2" /><el-option label="已撤回" :value="3" /></el-select></el-form-item>
        <el-form-item>
          <el-button type="primary" @click="loadData">搜索</el-button>
          <el-button @click="resetQuery">重置</el-button>
        </el-form-item>
      </el-form>
    </div>
    <el-card shadow="never" class="table-card">
      <div class="table-toolbar"><el-button type="primary" @click="openForm()">发布公告</el-button></div>
      <el-table :data="list" v-loading="loading" stripe border>
        <el-table-column type="index" label="#" width="50" />
        <el-table-column prop="id" label="ID" width="60" />
        <el-table-column prop="title" label="标题" width="200" />
        <el-table-column prop="community_name" label="小区" width="120" />
        <el-table-column prop="category" label="分类" width="100" />
        <el-table-column prop="top_status" label="置顶" width="80"><template #default="{row}"><el-tag v-if="row.top_status===1" type="danger">置顶</el-tag><span v-else>-</span></template></el-table-column>
        <el-table-column prop="status" label="状态" width="90"><template #default="{row}"><el-tag :type="statusType[row.status]||'info'">{{ statusMap[row.status]||'未知' }}</el-tag></template></el-table-column>
        <el-table-column prop="published_by" label="发布人" width="100" />
        <el-table-column prop="publish_time" label="发布时间" width="170" />
        <el-table-column label="操作" width="280" fixed="right">
          <template #default="{row}">
            <el-button v-if="row.status===1" size="small" type="success" @click="handlePublish(row,2)">发布</el-button>
            <el-button v-if="row.status===2" size="small" type="warning" @click="handlePublish(row,3)">撤回</el-button>
            <el-button size="small" @click="openForm(row)">编辑</el-button>
            <el-button size="small" type="danger" @click="handleDelete(row)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>
      <div class="pagination">
        <el-pagination v-model:current-page="query.page" v-model:page-size="query.limit" :total="total" :page-sizes="[15,30,50,100]" layout="total,sizes,prev,pager,next" @current-change="loadData" @size-change="loadData" />
      </div>
    </el-card>

    <el-dialog v-model="dialogVisible" :title="formTitle" width="640px" destroy-on-close>
      <el-form :model="form" :rules="rules" ref="formRef" label-width="100px">
        <el-form-item label="所属小区" prop="community_id"><el-select v-model="form.community_id" placeholder="选择小区" style="width:100%;"><el-option v-for="c in communities" :key="c.id" :label="c.name" :value="c.id" /></el-select></el-form-item>
        <el-form-item label="标题" prop="title"><el-input v-model="form.title" placeholder="公告标题" /></el-form-item>
        <el-form-item label="分类"><el-input v-model="form.category" placeholder="如：通知、活动" /></el-form-item>
        <el-form-item label="内容" prop="content"><el-input v-model="form.content" type="textarea" rows="6" placeholder="公告内容" /></el-form-item>
        <el-form-item label="置顶"><el-switch v-model="form.top_status" :active-value="1" :inactive-value="0" /></el-form-item>
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
const formTitle = ref('发布公告')
const communities = ref<any[]>([])

const statusMap: Record<number, string> = { 1: '草稿', 2: '已发布', 3: '已撤回' }
const statusType: Record<number, string> = { 1: 'info', 2: 'success', 3: 'danger' }

const query = reactive({ keyword: '', community_id: undefined as any, status: undefined as any, page: 1, limit: 15 })
const form = reactive<any>({ id: 0, community_id: '', title: '', category: '', content: '', top_status: 0 })
const rules = { title: [{ required: true, message: '请输入标题', trigger: 'blur' }], content: [{ required: true, message: '请输入内容', trigger: 'blur' }], community_id: [{ required: true, message: '请选择小区', trigger: 'change' }] }

function resetQuery() { query.keyword = ''; query.community_id = undefined; query.status = undefined; query.page = 1; loadData() }

async function loadData() {
  loading.value = true
  try {
    const r = await apiGet('/admin/notice/list', { ...query })
    list.value = r.data?.list || r.data || []
    total.value = r.count || r.data?.total || list.value.length
  } catch { list.value = []; total.value = 0 }
  finally { loading.value = false }
}

function openForm(row?: any) {
  formTitle.value = row ? '编辑公告' : '发布公告'
  Object.assign(form, row || { id: 0, community_id: '', title: '', category: '', content: '', top_status: 0 })
  dialogVisible.value = true
}

async function submitForm() {
  const valid = await formRef.value?.validate().catch(() => false)
  if (!valid) return
  submitting.value = true
  try {
    const url = form.id ? '/admin/notice/edit' : '/admin/notice/add'
    await apiPost(url, { ...form })
    ElMessage.success(form.id ? '修改成功' : '发布成功')
    dialogVisible.value = false
    loadData()
  } finally { submitting.value = false }
}

async function handlePublish(row: any, status: number) {
  try {
    const msg = status === 2 ? '确定发布该公告吗？' : '确定撤回该公告吗？'
    await ElMessageBox.confirm(msg, '提示', { type: 'warning' })
    await apiPost('/admin/notice/publish', { id: row.id, status })
    ElMessage.success(status === 2 ? '发布成功' : '已撤回')
    loadData()
  } catch {}
}

async function handleDelete(row: any) {
  try {
    await ElMessageBox.confirm(`确定删除公告 "${row.title}" 吗？`, '提示', { type: 'warning' })
    await apiPost('/admin/notice/delete', { id: row.id })
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
