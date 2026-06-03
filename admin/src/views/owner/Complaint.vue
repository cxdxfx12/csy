<template>
  <div class="page-container">
    <div class="search-bar">
      <el-form :model="query" inline>
        <el-form-item label="关键字">
          <el-input v-model="query.keyword" placeholder="标题/内容搜索" clearable @keyup.enter="loadData" />
        </el-form-item>
        <el-form-item label="小区">
          <el-select v-model="query.community_id" placeholder="全部小区" clearable style="width:180px">
            <el-option v-for="c in communities" :key="c.id" :label="c.name" :value="c.id" />
          </el-select>
        </el-form-item>
        <el-form-item label="状态">
          <el-select v-model="query.status" placeholder="全部状态" clearable style="width:120px">
            <el-option label="待处理" :value="1" />
            <el-option label="处理中" :value="2" />
            <el-option label="已处理" :value="3" />
            <el-option label="已关闭" :value="4" />
            <el-option label="已评价" :value="5" />
          </el-select>
        </el-form-item>
        <el-form-item label="类型">
          <el-select v-model="query.type" placeholder="全部类型" clearable style="width:110px">
            <el-option label="投诉" :value="1" />
            <el-option label="建议" :value="2" />
            <el-option label="表扬" :value="3" />
            <el-option label="咨询" :value="4" />
          </el-select>
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="loadData"><el-icon><Search /></el-icon>搜索</el-button>
          <el-button @click="resetQuery">重置</el-button>
        </el-form-item>
      </el-form>
    </div>

    <!-- 统计卡片 -->
    <el-row :gutter="16" class="stat-row">
      <el-col :span="4" v-for="s in stats" :key="s.label">
        <div class="stat-card" :style="{ borderLeftColor: s.color }" @click="query.status = s.status || ''; query.type = ''; loadData()">
          <div class="stat-num" :style="{ color: s.color }">{{ s.count }}</div>
          <div class="stat-label">{{ s.label }}</div>
        </div>
      </el-col>
    </el-row>

    <el-card shadow="never" class="table-card">
      <el-table :data="list" v-loading="loading" stripe border>
        <el-table-column prop="id" label="ID" width="70" align="center" />
        <el-table-column prop="title" label="标题" min-width="180" show-overflow-tooltip />
        <el-table-column prop="community_name" label="小区" width="120" />
        <el-table-column label="类型" width="90" align="center">
          <template #default="{ row }">
            <el-tag :type="complaintTypeTag(row.type)" size="small" effect="dark">
              {{ complaintTypeLabel(row.type) }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="owner_name" label="业主" width="100" />
        <el-table-column prop="room_number" label="房间" width="100" show-overflow-tooltip />
        <el-table-column label="状态" width="90" align="center">
          <template #default="{ row }">
            <el-tag :type="complaintStatusType(row.status)" size="small" effect="dark">
              {{ complaintStatusLabel(row.status) }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="handler_name" label="处理人" width="100">
          <template #default="{ row }">{{ row.handler_name || '-' }}</template>
        </el-table-column>
        <el-table-column label="提交时间" width="170">
          <template #default="{ row }">{{ row.create_time?.slice(0, 16) || '-' }}</template>
        </el-table-column>
        <el-table-column label="评分" width="80" align="center">
          <template #default="{ row }">
            <el-rate v-if="row.rating > 0" :model-value="row.rating" disabled size="small" />
            <span v-else class="text-muted">-</span>
          </template>
        </el-table-column>
        <el-table-column label="操作" width="180" fixed="right" align="center">
          <template #default="{ row }">
            <el-button type="primary" size="small" link @click="showDetail(row)">详情</el-button>
            <el-button v-if="row.status < 3" type="success" size="small" link @click="openHandle(row)">处理</el-button>
            <el-popconfirm title="确定删除该记录吗？" @confirm="handleDelete(row)">
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

    <!-- 详情弹窗 -->
    <el-dialog v-model="detailVisible" title="投诉建议详情" width="650px" destroy-on-close>
      <template v-if="detail">
        <el-descriptions :column="2" border>
          <el-descriptions-item label="标题" :span="2">{{ detail.title || '-' }}</el-descriptions-item>
          <el-descriptions-item label="类型">
            <el-tag :type="complaintTypeTag(detail.type)" size="small" effect="dark">{{ complaintTypeLabel(detail.type) }}</el-tag>
          </el-descriptions-item>
          <el-descriptions-item label="状态">
            <el-tag :type="complaintStatusType(detail.status)" size="small" effect="dark">{{ complaintStatusLabel(detail.status) }}</el-tag>
          </el-descriptions-item>
          <el-descriptions-item label="提交业主">{{ detail.owner_name || detail.complaint_name || '-' }}</el-descriptions-item>
          <el-descriptions-item label="手机号">{{ detail.owner_phone || detail.complaint_phone || '-' }}</el-descriptions-item>
          <el-descriptions-item label="所属房间">{{ detail.room_number || '-' }}</el-descriptions-item>
          <el-descriptions-item label="提交时间">{{ detail.create_time?.slice(0, 16) || '-' }}</el-descriptions-item>
          <el-descriptions-item label="处理人">{{ detail.handler_name || '-' }}</el-descriptions-item>
          <el-descriptions-item label="处理时间">{{ detail.handle_time?.slice(0, 16) || '-' }}</el-descriptions-item>
          <el-descriptions-item label="内容" :span="2">{{ detail.content || '-' }}</el-descriptions-item>
          <el-descriptions-item label="处理结果" :span="2">
            <span :class="{ 'text-muted': !detail.handle_content }">{{ detail.handle_content || '暂无处理结果' }}</span>
          </el-descriptions-item>
        </el-descriptions>
      </template>
      <template #footer>
        <el-button @click="detailVisible = false">关闭</el-button>
      </template>
    </el-dialog>

    <!-- 处理弹窗 -->
    <el-dialog v-model="handleVisible" title="处理投诉建议" width="550px" destroy-on-close>
      <el-form :model="handleForm" :rules="handleRules" ref="handleFormRef" label-width="90px">
        <el-form-item label="当前状态">
          <el-tag :type="complaintStatusType(handleForm.currentStatus)" size="default" effect="dark">
            {{ complaintStatusLabel(handleForm.currentStatus) }}
          </el-tag>
        </el-form-item>
        <el-form-item label="处理结果" prop="handle_content">
          <el-input v-model="handleForm.handle_content" type="textarea" :rows="5" placeholder="请输入处理结果或回复内容..." />
        </el-form-item>
        <el-form-item label="处理状态">
          <el-radio-group v-model="handleForm.handler_status">
            <el-radio :value="2">处理中</el-radio>
            <el-radio :value="3">已处理</el-radio>
            <el-radio :value="4">关闭</el-radio>
          </el-radio-group>
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="handleVisible = false">取消</el-button>
        <el-button type="primary" @click="submitHandle" :loading="submitting">提交处理</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { Search } from '@element-plus/icons-vue'
import { apiGet, apiPost } from '@/utils/request'

const list = ref<any[]>([])
const total = ref(0)
const loading = ref(false)
const detailVisible = ref(false)
const handleVisible = ref(false)
const submitting = ref(false)
const handleFormRef = ref<any>(null)
const communities = ref<any[]>([])
const detail = ref<any>(null)

const query = reactive({ keyword: '', community_id: '', status: '', type: '', page: 1, limit: 15 })
const handleForm = reactive({ id: 0, handle_content: '', handler_status: 2, currentStatus: 1 })
const handleRules = { handle_content: [{ required: true, message: '请输入处理结果', trigger: 'blur' }] }

const stats = ref([
  { label: '待处理', status: 1, count: 0, color: '#E6A23C' },
  { label: '处理中', status: 2, count: 0, color: '#409EFF' },
  { label: '已处理', status: 3, count: 0, color: '#67C23A' },
  { label: '已关闭', status: 4, count: 0, color: '#909399' },
  { label: '已评价', status: 5, count: 0, color: '#8B5CF6' },
  { label: '总计', status: '', count: 0, color: '#1a1a1a' },
])

const complaintStatusLabel = (s: number) => ({ 1: '待处理', 2: '处理中', 3: '已处理', 4: '已关闭', 5: '已评价' } as any)[s] || '未知'
const complaintStatusType = (s: number) => ({ 1: 'warning', 2: 'primary', 3: 'success', 4: 'info', 5: '' } as any)[s] || 'info'
const complaintTypeLabel = (t: number) => ({ 1: '投诉', 2: '建议', 3: '表扬', 4: '咨询' } as any)[t] || '未知'
const complaintTypeTag = (t: number) => ({ 1: 'danger', 2: 'success', 3: 'warning', 4: 'primary' } as any)[t] || 'info'

onMounted(() => { loadCommunities(); loadData() })

async function loadCommunities() {
  const res = await apiGet<any[]>('/admin/community/list', { limit: 100 })
  communities.value = res.data || []
}
async function loadData() {
  loading.value = true
  try {
    const res = await apiGet<any>('/admin/complaint/list', {
      ...query,
      community_id: query.community_id || undefined,
      status: query.status || undefined,
      type: query.type || undefined,
    })
    list.value = (res.data as any)?.list || (res.data as any) || []
    total.value = (res.data as any)?.total || res.count || 0
    loadStats()
  } finally { loading.value = false }
}
async function loadStats() {
  try {
    const [p1, p2, p3, p4, p5] = await Promise.all([
      apiGet<any>('/admin/complaint/list', { status: 1, limit: 1 }),
      apiGet<any>('/admin/complaint/list', { status: 2, limit: 1 }),
      apiGet<any>('/admin/complaint/list', { status: 3, limit: 1 }),
      apiGet<any>('/admin/complaint/list', { status: 4, limit: 1 }),
      apiGet<any>('/admin/complaint/list', { status: 5, limit: 1 }),
    ])
    stats.value[0].count = (p1.data as any)?.total || p1.count || 0
    stats.value[1].count = (p2.data as any)?.total || p2.count || 0
    stats.value[2].count = (p3.data as any)?.total || p3.count || 0
    stats.value[3].count = (p4.data as any)?.total || p4.count || 0
    stats.value[4].count = (p5.data as any)?.total || p5.count || 0
    stats.value[5].count = stats.value[0].count + stats.value[1].count + stats.value[2].count + stats.value[3].count + stats.value[4].count
  } catch { /* silent */ }
}
function resetQuery() { query.keyword = ''; query.community_id = ''; query.status = ''; query.type = ''; query.page = 1; loadData() }

async function showDetail(row: any) {
  const res = await apiGet<any>('/admin/complaint/detail', { id: row.id })
  detail.value = res.data
  detailVisible.value = true
}
function openHandle(row: any) {
  handleForm.id = row.id
  handleForm.handle_content = row.handle_content || ''
  handleForm.handler_status = row.status < 3 ? 2 : row.status
  handleForm.currentStatus = row.status
  handleVisible.value = true
}
async function submitHandle() {
  const valid = await handleFormRef.value?.validate().catch(() => false)
  if (!valid) return
  submitting.value = true
  try {
    await apiPost('/admin/complaint/handle', {
      id: handleForm.id,
      handle_content: handleForm.handle_content,
      status: handleForm.handler_status,
    })
    ElMessage.success('处理成功')
    handleVisible.value = false; loadData()
  } finally { submitting.value = false }
}
async function handleDelete(row: any) {
  await apiPost('/admin/complaint/delete', { id: row.id })
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
.text-muted { color: #999; }
.stat-row { margin-bottom: 16px; }
.stat-card { background: #fff; border-radius: 8px; padding: 14px 16px; border: 1px solid #e2e8f0; border-left: 4px solid #409EFF; cursor: pointer; transition: all .2s; }
.stat-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,.08); }
.stat-num { font-size: 26px; font-weight: 700; line-height: 1.2; }
.stat-label { font-size: 12px; color: #666; margin-top: 2px; }
</style>
