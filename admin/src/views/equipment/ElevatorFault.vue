<template>
  <div class="equip-page">
    <!-- 页面标题 -->
    <div class="page-header">
      <div class="header-left">
        <h2 class="page-title">
          <el-icon :size="24"><WarningFilled /></el-icon>
          电梯故障管理
        </h2>
        <p class="page-desc">故障报警 · 困人救援 · 维修跟踪 · 安全闭环</p>
      </div>
      <el-button type="primary" size="large" @click="openForm()">
        <el-icon><Plus /></el-icon> 记录故障
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
              <div class="stat-label">故障总数</div>
            </div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card shadow="hover" class="stat-card stat-unresolved">
          <div class="stat-inner">
            <div class="stat-icon"><el-icon :size="32"><WarningFilled /></el-icon></div>
            <div class="stat-info">
              <div class="stat-value">{{ stats.unresolved }}</div>
              <div class="stat-label">待处理</div>
            </div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card shadow="hover" class="stat-card stat-trapped">
          <div class="stat-inner">
            <div class="stat-icon"><el-icon :size="32"><BellFilled /></el-icon></div>
            <div class="stat-info">
              <div class="stat-value">{{ stats.trapped }}</div>
              <div class="stat-label">困人事件</div>
            </div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card shadow="hover" class="stat-card stat-resolved">
          <div class="stat-inner">
            <div class="stat-icon"><el-icon :size="32"><CircleCheck /></el-icon></div>
            <div class="stat-info">
              <div class="stat-value">{{ stats.resolved }}</div>
              <div class="stat-label">已处理</div>
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
        <el-form-item label="关联电梯">
          <el-select v-model="query.elevator_id" placeholder="全部电梯" clearable filterable style="width:180px" @change="loadData">
            <el-option v-for="e in elevators" :key="e.id" :label="e.elevator_no + ' (' + (e.brand||'') + ')'" :value="e.id" />
          </el-select>
        </el-form-item>
        <el-form-item label="故障类型">
          <el-select v-model="query.fault_type" placeholder="全部类型" clearable style="width:140px" @change="loadData">
            <el-option v-for="t in faultTypes" :key="t" :label="t" :value="t" />
          </el-select>
        </el-form-item>
        <el-form-item label="处理状态">
          <el-select v-model="query.status" placeholder="全部状态" clearable style="width:120px" @change="loadData">
            <el-option label="待处理" :value="0" />
            <el-option label="处理中" :value="1" />
            <el-option label="已完成" :value="2" />
          </el-select>
        </el-form-item>
        <el-form-item>
          <el-input v-model="query.keyword" placeholder="搜索描述/维修公司/电梯编号" clearable style="width:250px" @keyup.enter="loadData" @clear="loadData">
            <template #prefix><el-icon><Search /></el-icon></template>
          </el-input>
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="loadData"><el-icon><Search /></el-icon> 搜索</el-button>
          <el-button @click="resetQuery">重置</el-button>
        </el-form-item>
      </el-form>
    </el-card>

    <!-- 数据表格 -->
    <el-card shadow="never" class="table-card">
      <el-table :data="list" v-loading="loading" stripe style="width:100%">
        <el-table-column type="index" label="序号" width="55" />
        <el-table-column prop="fault_time" label="故障时间" width="170" sortable="custom">
          <template #default="{ row }">
            <span class="fault-time">{{ row.fault_time }}</span>
          </template>
        </el-table-column>
        <el-table-column prop="elevator_no" label="电梯编号" width="120">
          <template #default="{ row }">
            <span class="elevator-no">{{ row.elevator_no || '-' }}</span>
          </template>
        </el-table-column>
        <el-table-column prop="community_name" label="所属小区" width="110" />
        <el-table-column label="故障类型" width="110">
          <template #default="{ row }">
            <el-tag :type="faultTypeColor(row.fault_type)" effect="dark" size="small">
              {{ row.fault_type }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="description" label="故障描述" min-width="180" show-overflow-tooltip />
        <el-table-column label="是否困人" width="90" align="center">
          <template #default="{ row }">
            <el-tag :type="row.is_trapped == 1 ? 'danger' : 'info'" effect="light" size="small">
              {{ row.is_trapped == 1 ? '是' : '否' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="trapped_persons" label="困人数" width="75" align="center" />
        <el-table-column prop="rescue_time" label="救援到达" width="120" show-overflow-tooltip />
        <el-table-column label="维修单位" min-width="140" show-overflow-tooltip>
          <template #default="{ row }">
            <div class="repair-cell">
              <span>{{ row.repair_company || '-' }}</span>
              <span v-if="row.repair_person" class="repair-person">{{ row.repair_person }}</span>
            </div>
          </template>
        </el-table-column>
        <el-table-column label="维修费用" width="100" align="right">
          <template #default="{ row }">
            <span class="cost">{{ row.cost ? '¥' + Number(row.cost).toFixed(2) : '-' }}</span>
          </template>
        </el-table-column>
        <el-table-column label="状态" width="90" align="center">
          <template #default="{ row }">
            <el-tag :type="statusColor(row.status)" effect="dark" size="small">
              {{ statusText(row.status) }}
            </el-tag>
          </template>
        </el-table-column>
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
    <el-dialog v-model="dialogVisible" :title="editId ? '编辑故障记录' : '记录故障'" width="700px" destroy-on-close top="5vh">
      <el-form :model="form" ref="formRef" :rules="rules" label-width="90px">
        <el-row :gutter="16">
          <el-col :span="12">
            <el-form-item label="所属小区" prop="community_id">
              <el-select v-model="form.community_id" placeholder="选择小区" style="width:100%" @change="onFormCommunityChange">
                <el-option v-for="c in communities" :key="c.id" :label="c.name" :value="c.id" />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="关联电梯" prop="elevator_id">
              <el-select v-model="form.elevator_id" placeholder="选择电梯" filterable style="width:100%">
                <el-option v-for="e in filteredElevators" :key="e.id" :label="e.elevator_no + ' (' + (e.brand||'') + ' ' + (e.model||'') + ')'" :value="e.id" />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="故障类型" prop="fault_type">
              <el-select v-model="form.fault_type" placeholder="选择故障类型" style="width:100%">
                <el-option v-for="t in faultTypes" :key="t" :label="t" :value="t" />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="故障时间" prop="fault_time">
              <el-date-picker v-model="form.fault_time" type="datetime" placeholder="故障发生时间" style="width:100%" value-format="YYYY-MM-DD HH:mm:ss" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="处理状态" prop="status">
              <el-select v-model="form.status" placeholder="处理状态" style="width:100%">
                <el-option label="待处理" :value="0" />
                <el-option label="处理中" :value="1" />
                <el-option label="已完成" :value="2" />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="24">
            <el-form-item label="故障描述" prop="description">
              <el-input v-model="form.description" type="textarea" :rows="3" placeholder="请详细描述故障现象..." />
            </el-form-item>
          </el-col>
          <el-col :span="8">
            <el-form-item label="是否困人">
              <el-radio-group v-model="form.is_trapped">
                <el-radio :value="1">是</el-radio>
                <el-radio :value="0">否</el-radio>
              </el-radio-group>
            </el-form-item>
          </el-col>
          <el-col :span="8">
            <el-form-item label="困人数">
              <el-input-number v-model="form.trapped_persons" :min="0" :max="50" :disabled="form.is_trapped != 1" style="width:100%" />
            </el-form-item>
          </el-col>
          <el-col :span="8">
            <el-form-item label="救援到达">
              <el-date-picker v-model="form.rescue_time" type="datetime" placeholder="救援到达时间" style="width:100%" value-format="YYYY-MM-DD HH:mm:ss" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="维修公司">
              <el-input v-model="form.repair_company" placeholder="维修单位名称" maxlength="200" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="维修人员">
              <el-input v-model="form.repair_person" placeholder="维修负责人姓名" maxlength="50" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="修复时间">
              <el-date-picker v-model="form.repair_time" type="datetime" placeholder="修复完成时间" style="width:100%" value-format="YYYY-MM-DD HH:mm:ss" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="维修费用(元)">
              <el-input-number v-model="form.cost" :min="0" :precision="2" style="width:100%" />
            </el-form-item>
          </el-col>
          <el-col :span="24">
            <el-form-item label="处理结果">
              <el-input v-model="form.repair_result" type="textarea" :rows="2" placeholder="维修处理结果说明..." />
            </el-form-item>
          </el-col>
        </el-row>
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
import { WarningFilled, Plus, List, BellFilled, CircleCheck, Search } from '@element-plus/icons-vue'

const list = ref<any[]>([])
const total = ref(0)
const loading = ref(false)
const submitting = ref(false)
const dialogVisible = ref(false)
const editId = ref(0)
const formRef = ref()
const communities = ref<any[]>([])
const elevators = ref<any[]>([])

const query = reactive({ page: 1, limit: 15, keyword: '', community_id: 0, elevator_id: 0, fault_type: '', status: '' })
const form = reactive<any>({ community_id: null, elevator_id: null, fault_type: '', fault_time: '', description: '', is_trapped: 0, trapped_persons: 0, rescue_time: '', repair_company: '', repair_person: '', repair_time: '', repair_result: '', cost: 0, status: 0 })

const faultTypes = ['门锁故障', '控制系统故障', '驱动系统故障', '安全回路故障', '门机故障', '平层故障', '噪音异响', '急停故障', '通讯故障', '其他故障']

const rules = {
  community_id: [{ required: true, message: '请选择所属小区', trigger: 'change' }],
  elevator_id: [{ required: true, message: '请选择关联电梯', trigger: 'change' }],
  fault_type: [{ required: true, message: '请选择故障类型', trigger: 'change' }],
  fault_time: [{ required: true, message: '请选择故障时间', trigger: 'change' }],
  description: [{ required: true, message: '请输入故障描述', trigger: 'blur' }],
}

const filteredElevators = computed(() => {
  const data = elevators.value || []
  if (!form.community_id) return data
  return data.filter((e: any) => e.community_id == form.community_id)
})

const stats = computed(() => ({
  total: total.value,
  unresolved: (list.value || []).filter((d: any) => d.status == 0).length,
  trapped: (list.value || []).filter((d: any) => d.is_trapped == 1).length,
  resolved: (list.value || []).filter((d: any) => d.status == 2).length,
}))

function faultTypeColor(type: string) {
  if (!type) return 'info'
  if (type.includes('困人') || type.includes('安全回路')) return 'danger'
  if (type.includes('急停') || type.includes('控制') || type.includes('驱动')) return 'warning'
  return ''
}

function statusColor(status: number) {
  if (status == 0) return 'danger'
  if (status == 1) return 'warning'
  return 'success'
}

function statusText(status: number) {
  if (status == 0) return '待处理'
  if (status == 1) return '处理中'
  return '已完成'
}

async function loadCommunities() {
  try { const res = await apiGet('/admin/community/listAll'); if (res && res.code === 0) communities.value = res.data || [] } catch (_) {}
}

async function loadElevators() {
  try { const res = await apiGet('/admin/equipment/elevatorListAll'); if (res && res.code === 0) elevators.value = res.data || [] } catch (_) {}
}

async function loadData() {
  loading.value = true
  try {
    const params: any = { page: query.page, limit: query.limit }
    if (query.keyword) params.keyword = query.keyword
    if (query.community_id) params.community_id = query.community_id
    if (query.elevator_id) params.elevator_id = query.elevator_id
    if (query.fault_type) params.fault_type = query.fault_type
    if (query.status !== '') params.status = query.status
    const res = await apiGet('/admin/equipment/elevatorFaultList', { params })
    if (res && res.code === 0) { list.value = (res.data as any)?.list || res.data || []; total.value = (res.data as any)?.total || res.count || 0 }
  } catch (_) { list.value = []; total.value = 0 } finally { loading.value = false }
}

function resetQuery() {
  query.keyword = ''; query.community_id = 0; query.elevator_id = 0; query.fault_type = ''; query.status = ''
  query.page = 1; loadData()
}

function onFormCommunityChange() {
  form.elevator_id = null
}

function openForm(row?: any) {
  if (row) {
    editId.value = row.id
    const keys = ['community_id','elevator_id','fault_type','fault_time','description','is_trapped','trapped_persons','rescue_time','repair_company','repair_person','repair_time','repair_result','cost','status']
    keys.forEach(k => { (form as any)[k] = row[k] ?? (k === 'status' ? 0 : (k === 'is_trapped' ? 0 : (k === 'trapped_persons' ? 0 : (k === 'cost' ? 0 : '')))) })
  } else {
    editId.value = 0
    Object.assign(form, { community_id: null, elevator_id: null, fault_type: '', fault_time: '', description: '', is_trapped: 0, trapped_persons: 0, rescue_time: '', repair_company: '', repair_person: '', repair_time: '', repair_result: '', cost: 0, status: 0 })
  }
  dialogVisible.value = true
}

async function handleSubmit() {
  try { await formRef.value?.validate() } catch { return }
  submitting.value = true
  try {
    const url = editId.value ? '/admin/equipment/elevatorFaultEdit' : '/admin/equipment/elevatorFaultAdd'
    const res = await apiPost(url, { ...form, id: editId.value || undefined })
    if (res.code === 0) { ElMessage.success(res.msg); dialogVisible.value = false; loadData() }
  } finally { submitting.value = false }
}

async function handleDelete(row: any) {
  await ElMessageBox.confirm('确定删除该故障记录吗？', '删除确认', { type: 'warning' })
  const res = await apiPost('/admin/equipment/elevatorFaultDelete', { id: row.id })
  if (res.code === 0) { ElMessage.success('删除成功'); loadData() }
}

onMounted(() => { loadCommunities(); loadElevators(); loadData() })
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
.stat-unresolved .stat-icon { background: linear-gradient(135deg, #fa709a, #fee140); }
.stat-trapped .stat-icon { background: linear-gradient(135deg, #f093fb, #f5576c); }
.stat-resolved .stat-icon { background: linear-gradient(135deg, #43e97b, #38f9d7); }
.stat-value { font-size: 28px; font-weight: 700; color: #303133; line-height: 1.1; }
.stat-label { font-size: 13px; color: #909399; margin-top: 2px; }
.filter-card { margin-bottom: 16px; border-radius: 10px; }
.filter-card :deep(.el-card__body) { padding: 16px 16px 4px; }
.table-card { border-radius: 10px; }
.table-card :deep(.el-card__body) { padding: 16px; }
.pagination-wrap { margin-top: 16px; display: flex; justify-content: flex-end; }
:deep(.el-table th) { background: #fafbfc; color: #303133; font-weight: 600; }
.fault-time { font-family: 'Courier New', monospace; font-size: 13px; color: #e73b3b; }
.elevator-no { font-weight: 600; color: #409eff; font-family: 'Courier New', monospace; }
.repair-cell { display: flex; flex-direction: column; }
.repair-cell .repair-person { font-size: 12px; color: #909399; }
.cost { font-family: 'Courier New', monospace; font-weight: 500; }
</style>
