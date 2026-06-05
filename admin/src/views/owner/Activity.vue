<template>
  <div class="page-container">
    <div class="search-bar">
      <el-form :model="query" inline>
        <el-form-item label="关键字">
          <el-input v-model="query.keyword" placeholder="活动标题" clearable @keyup.enter="loadData" />
        </el-form-item>
        <el-form-item label="小区">
          <el-select v-model="query.community_id" placeholder="全部小区" clearable style="width:180px">
            <el-option v-for="c in communities" :key="c.id" :label="c.name" :value="c.id" />
          </el-select>
        </el-form-item>
        <el-form-item label="状态">
          <el-select v-model="query.status" placeholder="全部状态" clearable style="width:130px">
            <el-option label="草稿" :value="1" />
            <el-option label="报名中" :value="2" />
            <el-option label="进行中" :value="3" />
            <el-option label="已结束" :value="4" />
            <el-option label="已取消" :value="5" />
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
        <el-button type="primary" @click="openForm()"><el-icon><Plus /></el-icon>新建活动</el-button>
      </div>
      <el-table :data="list" v-loading="loading" stripe border>
        <el-table-column prop="id" label="ID" width="70" align="center" />
        <el-table-column label="封面" width="90" align="center">
          <template #default="{ row }">
            <el-avatar v-if="row.cover_image" :src="row.cover_image" shape="square" size="large" />
            <el-avatar v-else shape="square" size="large" :style="{ background: '#e2e8f0' }">
              <el-icon :size="24"><Picture /></el-icon>
            </el-avatar>
          </template>
        </el-table-column>
        <el-table-column prop="title" label="活动标题" min-width="180" show-overflow-tooltip />
        <el-table-column prop="community_name" label="小区" width="120" />
        <el-table-column prop="location" label="活动地点" width="140" show-overflow-tooltip />
        <el-table-column label="报名情况" width="110" align="center">
          <template #default="{ row }">
            <span :class="{ full: row.max_participants > 0 && row.signup_count >= row.max_participants }">
              {{ row.signup_count }}{{ row.max_participants > 0 ? `/${row.max_participants}` : '' }}
            </span>
          </template>
        </el-table-column>
        <el-table-column label="状态" width="100" align="center">
          <template #default="{ row }">
            <el-tag :type="activityStatusType(row.status)" size="small" effect="dark">
              {{ activityStatusLabel(row.status) }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="活动时间" width="210">
          <template #default="{ row }">
            <div class="time-cell" v-if="row.start_time">
              <div>{{ row.start_time?.slice(0, 16) }}</div>
              <div class="time-arrow">至</div>
              <div>{{ row.end_time?.slice(0, 16) }}</div>
            </div>
            <span v-else class="text-muted">-</span>
          </template>
        </el-table-column>
        <el-table-column label="操作" width="280" fixed="right" align="center">
          <template #default="{ row }">
            <el-button v-if="row.status === 1" type="success" size="small" link @click="handleStatus(row, 'publish')">发布</el-button>
            <el-button v-if="row.status === 2" type="primary" size="small" link @click="handleStatus(row, 'start')">开始</el-button>
            <el-button v-if="row.status === 3" type="warning" size="small" link @click="handleStatus(row, 'complete')">结束</el-button>
            <el-button v-if="[1,2,3].includes(row.status)" type="danger" size="small" link @click="handleStatus(row, 'cancel')">取消</el-button>
            <el-button type="primary" size="small" link @click="viewSignups(row)">报名</el-button>
            <el-button v-if="row.status !== 3 && row.status !== 4" type="primary" size="small" link @click="openForm(row)">编辑</el-button>
            <el-popconfirm title="确定删除该活动吗？" @confirm="handleDelete(row)">
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
    <el-dialog v-model="dialogVisible" :title="formTitle" width="680px" destroy-on-close>
      <el-form :model="form" :rules="rules" ref="formRef" label-width="90px">
        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="所属小区" prop="community_id">
              <el-select v-model="form.community_id" placeholder="请选择小区" style="width:100%">
                <el-option v-for="c in communities" :key="c.id" :label="c.name" :value="c.id" />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="活动状态">
              <el-select v-model="form.status" placeholder="报名中" style="width:100%">
                <el-option label="草稿（暂不发布）" :value="1" />
                <el-option label="报名中（业主可见）" :value="2" />
              </el-select>
            </el-form-item>
          </el-col>
        </el-row>
        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="人数上限">
              <el-input-number v-model="form.max_participants" :min="0" :max="9999" placeholder="0=不限" style="width:100%" controls-position="right" />
            </el-form-item>
          </el-col>
          <el-col :span="12"></el-col>
        </el-row>
        <el-form-item label="活动标题" prop="title">
          <el-input v-model="form.title" placeholder="请输入活动标题" maxlength="100" show-word-limit />
        </el-form-item>
        <el-form-item label="活动地点" prop="location">
          <el-input v-model="form.location" placeholder="例如：小区中心广场、3号楼活动室" />
        </el-form-item>
        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="开始时间" prop="start_time">
              <el-date-picker v-model="form.start_time" type="datetime" placeholder="开始时间" style="width:100%" value-format="YYYY-MM-DD HH:mm:ss" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="结束时间" prop="end_time">
              <el-date-picker v-model="form.end_time" type="datetime" placeholder="结束时间" style="width:100%" value-format="YYYY-MM-DD HH:mm:ss" />
            </el-form-item>
          </el-col>
        </el-row>
        <el-form-item label="封面图片">
          <div class="cover-upload">
            <el-input v-model="form.cover_image" placeholder="封面图片地址（可选）" clearable />
          </div>
        </el-form-item>
        <el-form-item label="活动详情" prop="content">
          <el-input v-model="form.content" type="textarea" :rows="5" placeholder="请输入活动详情描述..." />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" @click="submitForm" :loading="submitting">确定</el-button>
      </template>
    </el-dialog>

    <!-- 报名列表弹窗 -->
    <el-dialog v-model="signupVisible" title="报名记录" width="650px" destroy-on-close>
      <el-table :data="signups" v-loading="signupLoading" stripe border max-height="400">
        <el-table-column prop="owner_name" label="业主姓名" width="110" />
        <el-table-column prop="owner_phone" label="手机号" width="130" />
        <el-table-column prop="remark" label="备注" min-width="150" show-overflow-tooltip />
        <el-table-column prop="create_time" label="报名时间" width="170" />
        <el-table-column label="操作" width="80" align="center" fixed="right">
          <template #default="{ row: srow }">
            <el-popconfirm title="确定取消该报名吗？" @confirm="cancelSignup(srow)">
              <template #reference>
                <el-button type="danger" size="small" link>取消</el-button>
              </template>
            </el-popconfirm>
          </template>
        </el-table-column>
      </el-table>
      <template #footer>
        <el-button @click="signupVisible = false">关闭</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { Search, Plus, Picture } from '@element-plus/icons-vue'
import { apiGet, apiPost } from '@/utils/request'

const list = ref<any[]>([])
const total = ref(0)
const loading = ref(false)
const dialogVisible = ref(false)
const signupVisible = ref(false)
const submitting = ref(false)
const signupLoading = ref(false)
const formRef = ref<any>(null)
const formTitle = ref('新建活动')
const isEdit = ref(false)
const communities = ref<any[]>([])
const signups = ref<any[]>([])
let currentActivityId = 0

const query = reactive({ keyword: '', community_id: '', status: '', page: 1, limit: 15 })
const form = reactive<any>({ id: 0, community_id: '', title: '', location: '', content: '', cover_image: '', start_time: '', end_time: '', max_participants: 0, status: 2 })
const rules = {
  title: [{ required: true, message: '请输入活动标题', trigger: 'blur' }],
  community_id: [{ required: true, message: '请选择小区', trigger: 'change' }],
  location: [{ required: true, message: '请输入活动地点', trigger: 'blur' }],
}

const activityStatusLabel = (s: number) => ({ 1: '草稿', 2: '报名中', 3: '进行中', 4: '已结束', 5: '已取消' } as any)[s] || '未知'
const activityStatusType = (s: number) => ({ 1: 'info', 2: 'primary', 3: 'success', 4: 'warning', 5: 'danger' } as any)[s] || 'info'

onMounted(() => { loadCommunities(); loadData() })

async function loadCommunities() {
  const res = await apiGet<any[]>('/admin/community/list', { limit: 100 })
  communities.value = res.data || []
}
async function loadData() {
  loading.value = true
  try {
    const res = await apiGet<{ list: any[]; total: number }>('/admin/activity/list', { ...query, community_id: query.community_id || undefined, status: query.status || undefined })
    list.value = (res.data as any)?.list || (res.data as any) || []
    total.value = (res.data as any)?.total || res.count || 0
  } finally { loading.value = false }
}
function resetQuery() { query.keyword = ''; query.community_id = ''; query.status = ''; query.page = 1; loadData() }

function openForm(row?: any) {
  isEdit.value = !!row
  formTitle.value = row ? '编辑活动' : '新建活动'
  if (row) {
    apiGet<any>('/admin/activity/detail', { id: row.id }).then(res => {
      const d = res.data
      Object.assign(form, { id: d.id, community_id: d.community_id, title: d.title, location: d.location, content: d.content || '', cover_image: d.cover_image || '', start_time: d.start_time, end_time: d.end_time, max_participants: d.max_participants || 0, status: d.status || 2 })
      dialogVisible.value = true
    })
    return
  }
  Object.assign(form, { id: 0, community_id: '', title: '', location: '', content: '', cover_image: '', start_time: '', end_time: '', max_participants: 0, status: 2 })
  dialogVisible.value = true
}
async function submitForm() {
  const valid = await formRef.value?.validate().catch(() => false)
  if (!valid) return
  submitting.value = true
  try {
    if (isEdit.value) await apiPost('/admin/activity/edit', form)
    else await apiPost('/admin/activity/add', form)
    ElMessage.success(isEdit.value ? '修改成功' : '添加成功')
    dialogVisible.value = false; loadData()
  } finally { submitting.value = false }
}

async function handleStatus(row: any, action: string) {
  const labels: any = { publish: '确定发布该活动吗？', start: '确定开始该活动吗？', complete: '确定结束该活动吗？', cancel: '确定取消该活动吗？' }
  // Simple confirm for safety
  if (!confirm(labels[action] || '确定执行此操作吗？')) return
  await apiPost(`/admin/activity/${action}`, { id: row.id })
  ElMessage.success('操作成功')
  loadData()
}
async function viewSignups(row: any) {
  currentActivityId = row.id
  signupVisible.value = true
  signupLoading.value = true
  try {
    const res = await apiGet<any>('/admin/activity/signups', { activity_id: row.id, limit: 200 })
    signups.value = (res.data as any)?.list || (res.data as any) || []
  } finally { signupLoading.value = false }
}
async function cancelSignup(row: any) {
  await apiPost('/admin/activity/cancelSignup', { id: row.id })
  ElMessage.success('已取消报名')
  viewSignups({ id: currentActivityId })
}
async function handleDelete(row: any) {
  await apiPost('/admin/activity/delete', { id: row.id })
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
.time-cell { line-height: 1.6; font-size: 12px; color: #666; }
.time-arrow { color: #999; font-size: 11px; text-align: center; }
.text-muted { color: #999; }
.full { color: #F56C6C; font-weight: 700; }
.cover-upload { display: flex; align-items: center; gap: 12px; width: 100%; }
.cover-preview { width: 80px; height: 60px; border-radius: 4px; overflow: hidden; border: 1px solid #e2e8f0; }
.cover-preview img { width: 100%; height: 100%; object-fit: cover; }
</style>
