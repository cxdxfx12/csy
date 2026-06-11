<template>
  <div class="page-container">
    <div class="search-bar">
      <el-form :model="query" inline>
        <el-form-item label="关键字">
          <el-input v-model="query.keyword" placeholder="公告标题" clearable @keyup.enter="loadData" />
        </el-form-item>
        <el-form-item label="小区">
          <el-select v-model="query.community_id" placeholder="全部小区" clearable style="width:180px">
            <el-option v-for="c in communities" :key="c.id" :label="c.name" :value="c.id" />
          </el-select>
        </el-form-item>
        <el-form-item label="状态">
          <el-select v-model="query.status" placeholder="全部状态" clearable style="width:120px">
            <el-option label="草稿" :value="1" />
            <el-option label="已发布" :value="2" />
            <el-option label="已撤回" :value="3" />
          </el-select>
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="loadData"><el-icon><Search /></el-icon>搜索</el-button>
          <el-button @click="resetQuery">重置</el-button>
        </el-form-item>
      </el-form>
    </div>

    <el-card shadow="never" class="table-card">
      <div class="table-toolbar">
        <el-button type="primary" @click="openForm()"><el-icon><Plus /></el-icon>发布公告</el-button>
      </div>
      <el-table :data="list" v-loading="loading" stripe border>
        <el-table-column prop="id" label="ID" width="70" align="center" />
        <el-table-column label="置顶" width="70" align="center">
          <template #default="{ row }">
            <el-tag v-if="row.top_status == 1" type="danger" size="small" effect="dark">置顶</el-tag>
            <span v-else class="text-muted">-</span>
          </template>
        </el-table-column>
        <el-table-column prop="title" label="公告标题" min-width="200" show-overflow-tooltip>
          <template #default="{ row }">
            <span :class="{ 'top-title': row.top_status == 1 }">{{ row.title }}</span>
          </template>
        </el-table-column>
        <el-table-column prop="community_name" label="小区" width="120" />
        <el-table-column label="分类" width="100" align="center">
          <template #default="{ row }">
            <el-tag :type="noticeTypeTag(row.type)" size="small" effect="plain">
              {{ noticeTypeLabel(row.type) }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="等级" width="80" align="center">
          <template #default="{ row }">
            <el-tag v-if="row.level == 3" type="danger" size="small" effect="dark">紧急</el-tag>
            <el-tag v-else-if="row.level == 2" type="warning" size="small" effect="plain">重要</el-tag>
            <span v-else class="text-muted">普通</span>
          </template>
        </el-table-column>
        <el-table-column label="状态" width="90" align="center">
          <template #default="{ row }">
            <el-tag :type="noticeStatusType(row.status)" size="small" effect="dark">
              {{ noticeStatusLabel(row.status) }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="published_by" label="发布人" width="100" />
        <el-table-column label="发布时间" width="170">
          <template #default="{ row }">
            {{ row.publish_time ? row.publish_time.slice(0, 16) : '-' }}
          </template>
        </el-table-column>
        <el-table-column label="操作" width="260" fixed="right" align="center">
          <template #default="{ row }">
            <el-button v-if="row.status === 2" type="warning" size="small" link @click="handlePublish(row, 3)">撤回</el-button>
            <el-button v-if="row.status !== 2" type="success" size="small" link @click="handlePublish(row, 2)">发布</el-button>
            <el-button type="primary" size="small" link @click="showDetail(row)">详情</el-button>
            <el-button type="primary" size="small" link @click="openForm(row)">编辑</el-button>
            <el-popconfirm title="确定删除该公告吗？" @confirm="handleDelete(row)">
              <template #reference>
                <el-button type="danger" size="small" link>删除</el-button>
              </template>
            </el-popconfirm>
          </template>
        </el-table-column>
      </el-table>
      <div class="pagination">
        <el-pagination v-model:current-page="query.page" v-model:page-size="query.limit" :page-sizes="[10,15,20,50]"
          layout="total,sizes,prev,pager,next,jumper" :total="total" @current-change="loadData" @size-change="loadData" />
      </div>
    </el-card>

    <!-- 新增/编辑弹窗 -->
    <el-dialog v-model="dialogVisible" :title="formTitle" width="650px" destroy-on-close>
      <el-form :model="form" :rules="rules" ref="formRef" label-width="90px">
        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="所属小区" prop="community_id">
              <el-select v-model="form.community_id" placeholder="请选择小区" style="width:100%">
                <el-option :value="0" label="全局公告" />
                <el-option v-for="c in communities" :key="c.id" :label="c.name" :value="c.id" />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="公告类型">
              <el-select v-model="form.type" placeholder="类型" style="width:100%">
                <el-option label="小区公告" :value="1" />
                <el-option label="通知" :value="2" />
                <el-option label="温馨提示" :value="3" />
                <el-option label="活动" :value="4" />
                <el-option label="其他" :value="5" />
              </el-select>
            </el-form-item>
          </el-col>
        </el-row>
        <el-form-item label="公告标题" prop="title">
          <el-input v-model="form.title" placeholder="请输入公告标题" maxlength="100" show-word-limit />
        </el-form-item>
        <el-form-item label="公告内容" prop="content">
          <el-input v-model="form.content" type="textarea" :rows="6" placeholder="请输入公告内容..." />
        </el-form-item>
        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="紧急程度">
              <el-radio-group v-model="form.level">
                <el-radio :value="1">普通</el-radio>
                <el-radio :value="2">重要</el-radio>
                <el-radio :value="3">紧急</el-radio>
              </el-radio-group>
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="是否置顶">
              <el-switch v-model="form.top_status" :active-value="1" :inactive-value="0" active-text="置顶" />
            </el-form-item>
          </el-col>
        </el-row>
      </el-form>
      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" @click="submitForm" :loading="submitting">确定</el-button>
      </template>
    </el-dialog>

    <!-- 详情弹窗 -->
    <el-dialog v-model="detailVisible" title="公告详情" width="700px" destroy-on-close>
      <template v-if="detail">
        <div class="detail-header">
          <h2>{{ detail.title }}</h2>
          <div class="detail-meta">
            <el-tag :type="noticeTypeTag(detail.type)" size="small">{{ noticeTypeLabel(detail.type) }}</el-tag>
            <el-tag v-if="detail.level == 3" type="danger" size="small" effect="dark">紧急</el-tag>
            <el-tag v-else-if="detail.level == 2" type="warning" size="small">重要</el-tag>
            <el-tag :type="noticeStatusType(detail.status)" size="small" effect="dark">{{ noticeStatusLabel(detail.status) }}</el-tag>
            <span>发布人：{{ detail.published_by || '-' }}</span>
            <span v-if="detail.publish_time">发布时间：{{ detail.publish_time.slice(0, 16) }}</span>
          </div>
        </div>
        <div class="detail-content" style="white-space:pre-wrap;line-height:1.8;min-height:100px;padding:16px 0;">
          {{ detail.content || '暂无内容' }}
        </div>
      </template>
      <template #footer>
        <el-button @click="detailVisible = false">关闭</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { Search, Plus } from '@element-plus/icons-vue'
import { apiGet, apiPost } from '@/utils/request'

const list = ref<any[]>([])
const total = ref(0)
const loading = ref(false)
const dialogVisible = ref(false)
const detailVisible = ref(false)
const submitting = ref(false)
const formRef = ref<any>(null)
const formTitle = ref('发布公告')
const isEdit = ref(false)
const communities = ref<any[]>([])
const detail = ref<any>(null)

const query = reactive({ keyword: '', community_id: '', status: '', page: 1, limit: 15 })
const form = reactive<any>({ id: 0, community_id: 0, title: '', type: 1, level: 1, content: '', top_status: 0 })
const rules = {
  title: [{ required: true, message: '请输入公告标题', trigger: 'blur' }],
  content: [{ required: true, message: '请输入公告内容', trigger: 'blur' }],
}

const noticeStatusLabel = (s: number) => ({ 1: '草稿', 2: '已发布', 3: '已撤回' } as any)[s] || '未知'
const noticeStatusType = (s: number) => ({ 1: 'info', 2: 'success', 3: 'warning' } as any)[s] || 'info'
const noticeTypeLabel = (t: number) => ({ 1: '小区公告', 2: '通知', 3: '温馨提示', 4: '活动', 5: '其他' } as any)[t] || '未知'
const noticeTypeTag = (t: number) => ({ 1: '', 2: 'primary', 3: 'success', 4: 'warning', 5: 'info' } as any)[t] || 'info'

onMounted(() => { loadCommunities(); loadData() })

async function loadCommunities() {
  const res = await apiGet<any[]>('/admin/community/list', { limit: 100 })
  communities.value = res.data || []
}
async function loadData() {
  loading.value = true
  try {
    const res = await apiGet<any>('/admin/notice/list', {
      ...query,
      community_id: query.community_id || undefined,
      status: query.status || undefined,
    })
    list.value = (res.data as any)?.list || (res.data as any) || []
    total.value = (res.data as any)?.total || res.count || 0
  } finally { loading.value = false }
}
function resetQuery() { query.keyword = ''; query.community_id = ''; query.status = ''; query.page = 1; loadData() }

function openForm(row?: any) {
  isEdit.value = !!row
  formTitle.value = row ? '编辑公告' : '发布公告'
  if (row) {
    Object.assign(form, {
      id: row.id, community_id: row.community_id, title: row.title,
      type: row.type || 1, level: row.level || 1, content: row.content, top_status: row.top_status || 0,
    })
    dialogVisible.value = true
    return
  }
  Object.assign(form, { id: 0, community_id: 0, title: '', type: 1, level: 1, content: '', top_status: 0 })
  dialogVisible.value = true
}
async function submitForm() {
  try { await formRef.value?.validate() } catch { return }
  submitting.value = true
  try {
    if (isEdit.value) await apiPost('/admin/notice/edit', form)
    else await apiPost('/admin/notice/add', form)
    ElMessage.success(isEdit.value ? '修改成功' : '添加成功')
    dialogVisible.value = false; loadData()
  } finally { submitting.value = false }
}
async function handlePublish(row: any, status: number) {
  await apiPost('/admin/notice/publish', { id: row.id, status })
  ElMessage.success(status === 2 ? '公告已发布' : '公告已撤回')
  loadData()
}
function showDetail(row: any) {
  detail.value = { ...row }
  detailVisible.value = true
}
async function handleDelete(row: any) {
  await apiPost('/admin/notice/delete', { id: row.id })
  ElMessage.success('删除成功')
  loadData()
}
</script>

<style scoped>
.page-container { padding: 0; }
.search-bar { background: #fff; border-radius: 8px; padding: 16px 20px; margin-bottom: 16px; border: 1px solid #e2e8f0; }
.table-card { border-radius: 8px; border: 1px solid #e2e8f0; }
.table-toolbar { margin-bottom: 16px; }
.pagination { margin-top: 16px; display: flex; justify-content: flex-end; }
.top-title { font-weight: 700; }
.text-muted { color: #bbb; }
.detail-header { border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; margin-bottom: 8px; }
.detail-header h2 { margin: 0 0 12px 0; font-size: 20px; color: #1a1a1a; }
.detail-meta { display: flex; gap: 12px; align-items: center; flex-wrap: wrap; font-size: 13px; color: #666; }
</style>
