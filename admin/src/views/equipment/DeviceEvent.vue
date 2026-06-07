<template>
  <div class="equip-page">
    <!-- 页面标题 -->
    <div class="page-header">
      <div class="header-left">
        <h2 class="page-title">
          <el-icon :size="24"><Warning /></el-icon>
          设备事件监控
        </h2>
        <p class="page-desc">实时追踪设备告警、异常、离线等关键事件，保障设备稳定运行</p>
      </div>
      <el-button type="primary" size="large" @click="openForm()">
        <el-icon><Plus /></el-icon> 记录事件
      </el-button>
    </div>

    <!-- 统计卡片 -->
    <el-row :gutter="16" class="stats-row">
      <el-col :span="6">
        <el-card shadow="hover" class="stat-card stat-total">
          <div class="stat-inner">
            <div class="stat-icon"><el-icon :size="32"><List /></el-icon></div>
            <div class="stat-info">
              <div class="stat-value">{{ stats.total }}</div>
              <div class="stat-label">事件总数</div>
            </div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card shadow="hover" class="stat-card stat-today">
          <div class="stat-inner">
            <div class="stat-icon"><el-icon :size="32"><Clock /></el-icon></div>
            <div class="stat-info">
              <div class="stat-value">{{ stats.today }}</div>
              <div class="stat-label">今日事件</div>
            </div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card shadow="hover" class="stat-card stat-alarm">
          <div class="stat-inner">
            <div class="stat-icon"><el-icon :size="32"><BellFilled /></el-icon></div>
            <div class="stat-info">
              <div class="stat-value">{{ stats.warning }}</div>
              <div class="stat-label">告警事件</div>
            </div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card shadow="hover" class="stat-card stat-normal">
          <div class="stat-inner">
            <div class="stat-icon"><el-icon :size="32"><CircleCheck /></el-icon></div>
            <div class="stat-info">
              <div class="stat-value">{{ stats.normal }}</div>
              <div class="stat-label">正常事件</div>
            </div>
          </div>
        </el-card>
      </el-col>
    </el-row>

    <!-- 搜索筛选 -->
    <el-card shadow="never" class="filter-card">
      <el-form :model="query" inline>
        <el-form-item label="所属小区">
          <el-select v-model="query.community_id" placeholder="全部小区" clearable style="width:160px" @change="loadData">
            <el-option v-for="c in communities" :key="c.id" :label="c.name" :value="c.id" />
          </el-select>
        </el-form-item>
        <el-form-item label="关联设备">
          <el-select v-model="query.device_id" placeholder="全部设备" clearable filterable style="width:180px" @change="loadData">
            <el-option v-for="d in devices" :key="d.id" :label="d.device_name + ' (' + d.device_code + ')'" :value="d.id" />
          </el-select>
        </el-form-item>
        <el-form-item label="事件类型">
          <el-select v-model="query.event_type" placeholder="全部类型" clearable style="width:140px" @change="loadData">
            <el-option v-for="t in eventTypes" :key="t" :label="t" :value="t" />
          </el-select>
        </el-form-item>
        <el-form-item>
          <el-input v-model="query.keyword" placeholder="搜索事件内容/设备名称" clearable style="width:240px" @keyup.enter="loadData" @clear="loadData">
            <template #prefix><el-icon><Search /></el-icon></template>
          </el-input>
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="loadData"><el-icon><Search /></el-icon> 搜索</el-button>
          <el-button @click="resetQuery">重置</el-button>
        </el-form-item>
      </el-form>
    </el-card>

    <!-- 事件时间线表格 -->
    <el-card shadow="never" class="table-card">
      <el-table :data="list" v-loading="loading" stripe style="width:100%">
        <el-table-column type="index" label="序号" width="55" />
        <el-table-column prop="create_time" label="事件时间" width="170" sortable="custom">
          <template #default="{ row }">
            <span class="event-time">{{ row.create_time }}</span>
          </template>
        </el-table-column>
        <el-table-column label="事件类型" width="120">
          <template #default="{ row }">
            <el-tag :type="eventTypeColor(row.event_type)" effect="dark" size="small">
              {{ row.event_type }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="device_name" label="关联设备" min-width="160">
          <template #default="{ row }">
            <div class="device-cell">
              <span class="device-name">{{ row.device_name || '-' }}</span>
              <span class="device-code">{{ row.device_code || '' }}</span>
            </div>
          </template>
        </el-table-column>
        <el-table-column prop="device_type" label="设备类型" width="120">
          <template #default="{ row }">
            <el-tag effect="plain" size="small" v-if="row.device_type">{{ row.device_type }}</el-tag>
            <span v-else>-</span>
          </template>
        </el-table-column>
        <el-table-column prop="community_name" label="所属小区" width="120" />
        <el-table-column prop="content" label="事件描述" min-width="200" show-overflow-tooltip />
        <el-table-column label="操作" width="140" fixed="right">
          <template #default="{ row }">
            <el-button link type="primary" size="small" @click="openForm(row)">编辑</el-button>
            <el-button link type="danger" size="small" @click="handleDelete(row)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>
      <div class="pagination-wrap">
        <el-pagination
          v-model:current-page="query.page" v-model:page-size="query.limit"
          :total="total" :page-sizes="[15,30,50,100]"
          layout="total, sizes, prev, pager, next, jumper"
          @current-change="loadData" @size-change="loadData"
        />
      </div>
    </el-card>

    <!-- 编辑弹窗 -->
    <el-dialog v-model="dialogVisible" :title="editId ? '编辑事件' : '记录事件'" width="550px" destroy-on-close top="8vh">
      <el-form :model="form" ref="formRef" :rules="rules" label-width="80px">
        <el-form-item label="所属小区" prop="community_id">
          <el-select v-model="form.community_id" placeholder="选择小区" style="width:100%" @change="onFormCommunityChange">
            <el-option v-for="c in communities" :key="c.id" :label="c.name" :value="c.id" />
          </el-select>
        </el-form-item>
        <el-form-item label="关联设备" prop="device_id">
          <el-select v-model="form.device_id" placeholder="选择设备" filterable style="width:100%">
            <el-option v-for="d in filteredDevices" :key="d.id" :label="d.device_name + ' (' + d.device_code + ')'" :value="d.id" />
          </el-select>
        </el-form-item>
        <el-form-item label="事件类型" prop="event_type">
          <el-select v-model="form.event_type" placeholder="选择事件类型" style="width:100%">
            <el-option v-for="t in eventTypes" :key="t" :label="t" :value="t" />
          </el-select>
        </el-form-item>
        <el-form-item label="事件描述" prop="content">
          <el-input v-model="form.content" type="textarea" :rows="4" placeholder="请详细描述事件内容..." />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" :loading="submitting" @click="handleSubmit">确定</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import { apiGet, apiPost } from '@/utils/request'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Warning, Plus, List, Clock, BellFilled, CircleCheck, Search } from '@element-plus/icons-vue'

const list = ref<any[]>([])
const total = ref(0)
const loading = ref(false)
const submitting = ref(false)
const dialogVisible = ref(false)
const editId = ref(0)
const formRef = ref()
const communities = ref<any[]>([])
const devices = ref<any[]>([])

const query = reactive({ page: 1, limit: 15, keyword: '', community_id: 0, device_id: 0, event_type: '' })
const form = reactive<any>({ community_id: null, device_id: null, event_type: '', content: '' })

const eventTypes = ['设备离线', '设备上线', '状态异常', '阈值告警', '通信超时', '配置变更', '固件升级', '故障报警', '自动恢复', '人工干预']

const rules = {
  device_id: [{ required: true, message: '请选择关联设备', trigger: 'change' }],
  event_type: [{ required: true, message: '请选择事件类型', trigger: 'change' }],
  content: [{ required: true, message: '请输入事件描述', trigger: 'blur' }],
  community_id: [{ required: true, message: '请选择所属小区', trigger: 'change' }],
}

const filteredDevices = computed(() => {
  const data = devices.value || []
  if (!form.community_id) return data
  return data.filter((d: any) => d.community_id == form.community_id)
})

const stats = computed(() => {
  const data = list.value || []
  const today = new Date().toISOString().slice(0, 10)
  return {
    total: total.value,
    today: data.filter((d: any) => d.create_time && d.create_time.startsWith(today)).length,
    warning: data.filter((d: any) => d.event_type && (d.event_type.includes('告警') || d.event_type.includes('故障') || d.event_type.includes('异常'))).length,
    normal: data.filter((d: any) => d.event_type && (d.event_type.includes('上线') || d.event_type.includes('恢复') || d.event_type.includes('升级') || d.event_type.includes('变更'))).length,
  }
})

function eventTypeColor(type: string) {
  if (!type) return 'info'
  if (type.includes('告警') || type.includes('故障')) return 'danger'
  if (type.includes('异常') || type.includes('超时') || type.includes('离线')) return 'warning'
  if (type.includes('上线') || type.includes('恢复')) return 'success'
  if (type.includes('变更') || type.includes('升级')) return 'info'
  return ''
}

async function loadCommunities() {
  try { const res = await apiGet('/admin/community/listAll'); if (res && res.code === 0) communities.value = res.data || [] } catch (_) {}
}

async function loadDevices() {
  try { const res = await apiGet('/admin/equipment/deviceListAll'); if (res && res.code === 0) devices.value = res.data || [] } catch (_) {}
}

async function loadData() {
  loading.value = true
  try {
    const params: any = { page: query.page, limit: query.limit }
    if (query.keyword) params.keyword = query.keyword
    if (query.community_id) params.community_id = query.community_id
    if (query.device_id) params.device_id = query.device_id
    if (query.event_type) params.event_type = query.event_type
    const res = await apiGet('/admin/equipment/deviceEventList', params)
    if (res && res.code === 0) {
      list.value = res.data?.list || res.data || []
      total.value = res.count || res.data?.total || list.value.length
    }
  } catch (_) { list.value = []; total.value = 0 } finally { loading.value = false }
}

function resetQuery() {
  query.keyword = ''; query.community_id = 0; query.device_id = 0; query.event_type = ''
  query.page = 1; loadData()
}

function onFormCommunityChange() {
  form.device_id = null
}

function openForm(row?: any) {
  if (row) { editId.value = row.id; Object.assign(form, { community_id: row.community_id, device_id: row.device_id, event_type: row.event_type, content: row.content }) }
  else { editId.value = 0; Object.assign(form, { community_id: null, device_id: null, event_type: '', content: '' }) }
  dialogVisible.value = true
}

async function handleSubmit() {
  const valid = await formRef.value.validate().catch(() => false)
  if (!valid) return
  submitting.value = true
  try {
    const url = editId.value ? '/admin/equipment/deviceEventEdit' : '/admin/equipment/deviceEventAdd'
    const res = await apiPost(url, { ...form, id: editId.value || undefined })
    if (res.code === 0) { ElMessage.success(res.msg); dialogVisible.value = false; loadData() }
  } finally { submitting.value = false }
}

async function handleDelete(row: any) {
  await ElMessageBox.confirm('确定删除该事件记录吗？', '删除确认', { type: 'warning' })
  const res = await apiPost('/admin/equipment/deviceEventDelete', { id: row.id })
  if (res.code === 0) { ElMessage.success('删除成功'); loadData() }
}

onMounted(() => { loadCommunities(); loadDevices(); loadData() })
</script>

<style scoped>
.equip-page { padding: 16px 20px; background: #f5f7fa; min-height: 100%; }
.page-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px; }
.page-title { margin: 0; font-size: 20px; font-weight: 700; color: #1a1a2e; display: flex; align-items: center; gap: 8px; }
.page-desc { margin: 4px 0 0; font-size: 13px; color: #909399; }
.stats-row { margin-bottom: 16px; }
.stat-card { border-radius: 10px; transition: all .3s; }
.stat-card:hover { transform: translateY(-2px); }
.stat-inner { display: flex; align-items: center; gap: 16px; }
.stat-icon { width: 56px; height: 56px; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #fff; }
.stat-total .stat-icon { background: linear-gradient(135deg, #667eea, #764ba2); }
.stat-today .stat-icon { background: linear-gradient(135deg, #43e97b, #38f9d7); }
.stat-alarm .stat-icon { background: linear-gradient(135deg, #fa709a, #fee140); }
.stat-normal .stat-icon { background: linear-gradient(135deg, #4facfe, #00f2fe); }
.stat-value { font-size: 28px; font-weight: 700; color: #303133; line-height: 1.1; }
.stat-label { font-size: 13px; color: #909399; margin-top: 2px; }
.filter-card { margin-bottom: 16px; border-radius: 10px; }
.filter-card :deep(.el-card__body) { padding: 16px 16px 4px; }
.table-card { border-radius: 10px; }
.table-card :deep(.el-card__body) { padding: 16px; }
.pagination-wrap { margin-top: 16px; display: flex; justify-content: flex-end; }
:deep(.el-table th) { background: #fafbfc; color: #303133; font-weight: 600; }
.event-time { color: #409eff; font-family: 'Courier New', monospace; font-size: 13px; }
.device-cell { display: flex; flex-direction: column; }
.device-name { font-weight: 500; color: #303133; }
.device-code { font-size: 12px; color: #909399; }
</style>
