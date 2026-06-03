<template>
  <div class="page-container">
    <div class="search-bar">
      <el-form :model="query" inline>
        <el-form-item label="关键字">
          <el-input v-model="query.keyword" placeholder="投票标题" clearable @keyup.enter="loadData" />
        </el-form-item>
        <el-form-item label="小区">
          <el-select v-model="query.community_id" placeholder="全部小区" clearable style="width:180px">
            <el-option v-for="c in communities" :key="c.id" :label="c.name" :value="c.id" />
          </el-select>
        </el-form-item>
        <el-form-item label="状态">
          <el-select v-model="query.status" placeholder="全部状态" clearable style="width:130px">
            <el-option label="草稿" :value="1" />
            <el-option label="进行中" :value="2" />
            <el-option label="已结束" :value="3" />
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
        <el-button type="primary" @click="openForm()"><el-icon><Plus /></el-icon>新建投票</el-button>
      </div>
      <el-table :data="list" v-loading="loading" stripe border>
        <el-table-column prop="id" label="ID" width="70" align="center" />
        <el-table-column prop="title" label="投票标题" min-width="200" show-overflow-tooltip />
        <el-table-column prop="community_name" label="所属小区" width="140" />
        <el-table-column label="类型" width="90" align="center">
          <template #default="{ row }">
            <el-tag :type="row.type == 1 ? 'primary' : 'success'" size="small" effect="dark">
              {{ row.type == 1 ? '单选' : '多选' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="状态" width="100" align="center">
          <template #default="{ row }">
            <el-tag :type="statusType(row.status)" size="small" effect="dark">
              {{ statusLabel(row.status) }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="投票人数" width="100" align="center">
          <template #default="{ row }">
            <el-badge :value="row.total_votes" type="primary" />
          </template>
        </el-table-column>
        <el-table-column label="有效期" width="220">
          <template #default="{ row }">
            <div class="time-range">
              <span class="time-label">起</span>{{ row.start_time?.slice(0, 16) || '-' }}
              <br/>
              <span class="time-label">止</span>{{ row.end_time?.slice(0, 16) || '-' }}
            </div>
          </template>
        </el-table-column>
        <el-table-column label="操作" width="280" fixed="right" align="center">
          <template #default="{ row }">
            <el-button v-if="row.status === 1" type="success" size="small" link @click="handlePublish(row)">发布</el-button>
            <el-button v-if="row.status === 2" type="warning" size="small" link @click="handleClose(row)">结束</el-button>
            <el-button type="primary" size="small" link @click="viewResult(row)">结果</el-button>
            <el-button v-if="row.status !== 2" type="primary" size="small" link @click="openForm(row)">编辑</el-button>
            <el-popconfirm title="确定删除该投票吗？" @confirm="handleDelete(row)">
              <template #reference>
                <el-button type="danger" size="small" link>删除</el-button>
              </template>
            </el-popconfirm>
          </template>
        </el-table-column>
      </el-table>
      <div class="pagination">
        <el-pagination v-model:current-page="query.page" v-model:page-size="query.limit" :page-sizes="[10,15,20,50]"
          layout="total,sizes,prev,pager,next,jumper" :total="total" @change="loadData" />
      </div>
    </el-card>

    <!-- 新增/编辑弹窗 -->
    <el-dialog v-model="dialogVisible" :title="formTitle" width="650px" destroy-on-close>
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
            <el-form-item label="投票类型" prop="type">
              <el-radio-group v-model="form.type">
                <el-radio :value="1">单选</el-radio>
                <el-radio :value="2">多选</el-radio>
              </el-radio-group>
            </el-form-item>
          </el-col>
        </el-row>
        <el-form-item label="投票标题" prop="title">
          <el-input v-model="form.title" placeholder="请输入投票标题" />
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
        <el-form-item label="投票说明" prop="content">
          <el-input v-model="form.content" type="textarea" :rows="3" placeholder="可选，投票补充说明" />
        </el-form-item>
        <el-form-item label="投票选项">
          <div class="option-list">
            <div v-for="(opt, idx) in form.options" :key="idx" class="option-row">
              <span class="option-letter">{{ String.fromCharCode(65 + idx) }}.</span>
              <el-input v-model="form.options[idx]" placeholder="选项内容" style="flex:1" />
              <el-button type="danger" :icon="Delete" circle size="small" @click="removeOption(idx)" :disabled="form.options.length <= 2" />
            </div>
          </div>
          <el-button type="primary" dashed size="small" @click="addOption" style="margin-top:8px">
            <el-icon><Plus /></el-icon>添加选项
          </el-button>
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" @click="submitForm" :loading="submitting">确定</el-button>
      </template>
    </el-dialog>

    <!-- 投票结果弹窗 -->
    <el-dialog v-model="resultVisible" title="投票结果" width="700px" destroy-on-close>
      <template v-if="result">
        <div class="result-header">
          <h3>{{ result.title }}</h3>
          <div class="result-meta">
            <el-tag :type="statusType(result.status)" effect="dark">{{ statusLabel(result.status) }}</el-tag>
            <span>小区：{{ result.community_name }}</span>
            <span>类型：{{ result.type == 1 ? '单选' : '多选' }}</span>
            <span>总投票数：<b>{{ result.total_votes }}</b></span>
          </div>
        </div>
        <div class="result-chart">
          <div v-for="(opt, idx) in result.options" :key="idx" class="result-bar">
            <div class="result-bar-label">
              <span class="bar-tag">{{ String.fromCharCode(65 + idx) }}</span>
              <span class="bar-title">{{ opt.title }}</span>
              <span class="bar-count">{{ opt.count }}票 ({{ opt.percent }}%)</span>
            </div>
            <el-progress :percentage="opt.percent" :color="barColor(idx)" :stroke-width="24" striped striped-flow />
          </div>
        </div>
        <div v-if="result.recent_votes?.length" class="recent-votes">
          <h4>最近投票记录</h4>
          <el-table :data="result.recent_votes" size="small" stripe border>
            <el-table-column prop="owner_name" label="业主" width="100" />
            <el-table-column prop="option_title" label="选项" min-width="150" />
            <el-table-column prop="create_time" label="投票时间" width="170" />
          </el-table>
        </div>
      </template>
      <template #footer>
        <el-button @click="resultVisible = false">关闭</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { Search, Plus, Delete } from '@element-plus/icons-vue'
import { apiGet, apiPost } from '@/utils/request'

const list = ref<any[]>([])
const total = ref(0)
const loading = ref(false)
const dialogVisible = ref(false)
const resultVisible = ref(false)
const submitting = ref(false)
const formRef = ref<any>(null)
const formTitle = ref('新建投票')
const isEdit = ref(false)
const communities = ref<any[]>([])
const result = ref<any>(null)

const query = reactive({ keyword: '', community_id: '', status: '', page: 1, limit: 15 })
const form = reactive<any>({ id: 0, community_id: '', title: '', type: 1, content: '', start_time: '', end_time: '', options: ['', ''] })
const rules = { title: [{ required: true, message: '请输入投票标题', trigger: 'blur' }], community_id: [{ required: true, message: '请选择小区', trigger: 'change' }] }

const statusLabel = (s: number) => ({ 1: '草稿', 2: '进行中', 3: '已结束' } as any)[s] || '未知'
const statusType = (s: number) => ({ 1: 'info', 2: 'success', 3: 'warning' } as any)[s] || 'info'
const barColor = (i: number) => ['#409EFF', '#67C23A', '#E6A23C', '#F56C6C', '#909399', '#8B5CF6', '#EC4899', '#14B8A6'][i] || '#409EFF'

onMounted(() => { loadCommunities(); loadData() })

async function loadCommunities() {
  const res = await apiGet<any[]>('/admin/community/list', { limit: 100 })
  communities.value = res.data || []
}
async function loadData() {
  loading.value = true
  try {
    const res = await apiGet<{ list: any[]; total: number }>('/admin/vote/list', { ...query, community_id: query.community_id || undefined, status: query.status || undefined })
    list.value = (res.data as any)?.list || (res.data as any) || []
    total.value = (res.data as any)?.total || res.count || 0
  } finally { loading.value = false }
}
function resetQuery() { query.keyword = ''; query.community_id = ''; query.status = ''; query.page = 1; loadData() }

function openForm(row?: any) {
  isEdit.value = !!row
  formTitle.value = row ? '编辑投票' : '新建投票'
  if (row) {
    apiGet<any>('/admin/vote/detail', { id: row.id }).then(res => {
      const d = res.data
      form.id = d.id; form.community_id = d.community_id; form.title = d.title
      form.type = d.type; form.content = d.content || ''
      form.start_time = d.start_time; form.end_time = d.end_time
      form.options = (d.options && d.options.length) ? d.options.map((o: any) => o.title) : ['', '']
      dialogVisible.value = true
    })
    return
  }
  Object.assign(form, { id: 0, community_id: '', title: '', type: 1, content: '', start_time: '', end_time: '', options: ['', ''] })
  dialogVisible.value = true
}
function addOption() { if (form.options.length < 10) form.options.push('') }
function removeOption(idx: number) { if (form.options.length > 2) form.options.splice(idx, 1) }

async function submitForm() {
  const valid = await formRef.value?.validate().catch(() => false)
  if (!valid) return
  submitting.value = true
  try {
    const validOptions = form.options.filter((o: string) => o.trim())
    if (validOptions.length < 2) { ElMessage.warning('至少需要2个有效选项'); return }
    const payload = { ...form, options: validOptions }
    if (isEdit.value) await apiPost('/admin/vote/edit', payload)
    else await apiPost('/admin/vote/add', payload)
    ElMessage.success(isEdit.value ? '修改成功' : '添加成功')
    dialogVisible.value = false; loadData()
  } finally { submitting.value = false }
}

async function handlePublish(row: any) {
  await apiPost('/admin/vote/publish', { id: row.id })
  ElMessage.success('投票已发布')
  loadData()
}
async function handleClose(row: any) {
  await apiPost('/admin/vote/close', { id: row.id })
  ElMessage.success('投票已结束')
  loadData()
}
async function viewResult(row: any) {
  const res = await apiGet<any>('/admin/vote/result', { id: row.id })
  result.value = res.data
  resultVisible.value = true
}
async function handleDelete(row: any) {
  await apiPost('/admin/vote/delete', { id: row.id })
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
.time-range { line-height: 1.8; font-size: 12px; color: #666; }
.time-label { display: inline-block; width: 18px; color: #999; font-size: 11px; }
.option-list { display: flex; flex-direction: column; gap: 8px; width: 100%; }
.option-row { display: flex; align-items: center; gap: 8px; }
.option-letter { width: 24px; font-weight: 700; color: #409EFF; text-align: center; flex-shrink: 0; }
.result-header { margin-bottom: 20px; padding-bottom: 16px; border-bottom: 1px solid #e2e8f0; }
.result-header h3 { margin: 0 0 12px 0; font-size: 18px; }
.result-meta { display: flex; gap: 16px; align-items: center; font-size: 13px; color: #666; }
.result-chart { display: flex; flex-direction: column; gap: 14px; }
.result-bar-label { display: flex; align-items: center; gap: 8px; margin-bottom: 4px; font-size: 13px; }
.bar-tag { background: #409EFF; color: #fff; width: 22px; height: 22px; display: flex; align-items: center; justify-content: center; border-radius: 50%; font-size: 11px; font-weight: 700; flex-shrink: 0; }
.bar-title { flex: 1; font-weight: 500; }
.bar-count { color: #909399; font-size: 12px; white-space: nowrap; }
.recent-votes { margin-top: 20px; }
.recent-votes h4 { margin: 0 0 10px 0; font-size: 14px; color: #333; }
</style>
