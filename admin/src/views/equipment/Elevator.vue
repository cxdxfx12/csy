<template>
  <div class="equip-page">
    <!-- 页面标题 -->
    <div class="page-header">
      <div class="header-left">
        <h2 class="page-title">
          <el-icon :size="24"><Connection /></el-icon>
          电梯资产管理
        </h2>
        <p class="page-desc">电梯台账 · 年检追踪 · 维保合同 · 运行状态一目了然</p>
      </div>
      <el-button type="primary" size="large" @click="openForm()">
        <el-icon><Plus /></el-icon> 登记电梯
      </el-button>
    </div>

    <!-- 统计卡片 -->
    <el-row :gutter="16" class="stats-row">
      <el-col :span="6">
        <el-card shadow="hover" class="stat-card stat-total">
          <div class="stat-inner">
            <div class="stat-icon"><el-icon :size="32"><Odometer /></el-icon></div>
            <div class="stat-info">
              <div class="stat-value">{{ stats.total }}</div>
              <div class="stat-label">电梯总数</div>
            </div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card shadow="hover" class="stat-card stat-running">
          <div class="stat-inner">
            <div class="stat-icon"><el-icon :size="32"><CircleCheckFilled /></el-icon></div>
            <div class="stat-info">
              <div class="stat-value">{{ stats.running }}</div>
              <div class="stat-label">正常运行</div>
            </div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card shadow="hover" class="stat-card stat-expiring">
          <div class="stat-inner">
            <div class="stat-icon"><el-icon :size="32"><WarningFilled /></el-icon></div>
            <div class="stat-info">
              <div class="stat-value">{{ stats.expiring }}</div>
              <div class="stat-label">年检即将到期</div>
            </div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card shadow="hover" class="stat-card stat-stopped">
          <div class="stat-inner">
            <div class="stat-icon"><el-icon :size="32"><RemoveFilled /></el-icon></div>
            <div class="stat-info">
              <div class="stat-value">{{ stats.stopped }}</div>
              <div class="stat-label">停用/维修</div>
            </div>
          </div>
        </el-card>
      </el-col>
    </el-row>

    <!-- 搜索筛选 -->
    <el-card shadow="never" class="filter-card">
      <el-form :model="query" inline>
        <el-form-item label="所属小区">
          <el-select v-model="query.community_id" placeholder="全部小区" clearable style="width:150px" @change="onCommunityChange">
            <el-option v-for="c in communities" :key="c.id" :label="c.name" :value="c.id" />
          </el-select>
        </el-form-item>
        <el-form-item label="所属楼栋">
          <el-select v-model="query.building_id" placeholder="全部楼栋" clearable style="width:150px" @change="loadData">
            <el-option v-for="b in filteredBuildings" :key="b.id" :label="b.name" :value="b.id" />
          </el-select>
        </el-form-item>
        <el-form-item label="运行状态">
          <el-select v-model="query.status" placeholder="全部状态" clearable style="width:130px" @change="loadData">
            <el-option label="正常运行" :value="1" />
            <el-option label="已停用" :value="0" />
          </el-select>
        </el-form-item>
        <el-form-item>
          <el-input v-model="query.keyword" placeholder="搜索电梯编号/品牌/维保公司" clearable style="width:260px" @keyup.enter="loadData" @clear="loadData">
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
        <el-table-column prop="elevator_no" label="电梯编号" width="130" sortable="custom">
          <template #default="{ row }">
            <span class="elevator-no">{{ row.elevator_no }}</span>
          </template>
        </el-table-column>
        <el-table-column prop="community_name" label="所属小区" width="110" />
        <el-table-column prop="building_name" label="所属楼栋" width="100" />
        <el-table-column label="品牌型号" min-width="170">
          <template #default="{ row }">
            <div class="brand-cell">
              <span class="brand">{{ row.brand }}</span>
              <span class="model">{{ row.model }}</span>
            </div>
          </template>
        </el-table-column>
        <el-table-column prop="elevator_type" label="类型" width="100">
          <template #default="{ row }">
            <el-tag effect="plain" size="small" :type="row.elevator_type === '客梯' ? '' : 'warning'">{{ row.elevator_type }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="floor_count" label="楼层" width="70" align="center" />
        <el-table-column label="载重(kg)" width="90" align="center">
          <template #default="{ row }"><span>{{ row.capacity }}</span></template>
        </el-table-column>
        <el-table-column prop="maintain_company" label="维保公司" width="130" show-overflow-tooltip />
        <el-table-column prop="next_inspection_date" label="下次年检" width="110" sortable="custom">
          <template #default="{ row }">
            <el-tag :type="nextInspectionTag(row.next_inspection_date)" effect="light" size="small">
              {{ row.next_inspection_date || '-' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="状态" width="90" align="center">
          <template #default="{ row }">
            <el-tag :type="row.status == 1 ? 'success' : 'info'" effect="dark" size="small">
              {{ row.status == 1 ? '运行中' : '停用' }}
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
    <el-dialog v-model="dialogVisible" :title="editId ? '编辑电梯信息' : '登记电梯'" width="750px" destroy-on-close top="3vh">
      <el-form :model="form" ref="formRef" :rules="rules" label-width="100px">
        <el-row :gutter="16">
          <el-col :span="12">
            <el-form-item label="所属小区" prop="community_id">
              <el-select v-model="form.community_id" placeholder="选择小区" style="width:100%" @change="onFormCommunityChange">
                <el-option v-for="c in communities" :key="c.id" :label="c.name" :value="c.id" />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="所属楼栋" prop="building_id">
              <el-select v-model="form.building_id" placeholder="选择楼栋" style="width:100%">
                <el-option v-for="b in formBuildings" :key="b.id" :label="b.name" :value="b.id" />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="8">
            <el-form-item label="电梯编号" prop="elevator_no">
              <el-input v-model="form.elevator_no" placeholder="如 DT-A-01" maxlength="50" />
            </el-form-item>
          </el-col>
          <el-col :span="8">
            <el-form-item label="电梯类型" prop="elevator_type">
              <el-select v-model="form.elevator_type" placeholder="选择类型" style="width:100%">
                <el-option label="客梯" value="客梯" />
                <el-option label="货梯" value="货梯" />
                <el-option label="客货两用" value="客货两用" />
                <el-option label="观光梯" value="观光梯" />
                <el-option label="自动扶梯" value="自动扶梯" />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="8">
            <el-form-item label="品牌" prop="brand">
              <el-input v-model="form.brand" placeholder="如 OTIS / 三菱" maxlength="100" />
            </el-form-item>
          </el-col>
          <el-col :span="8">
            <el-form-item label="型号" prop="model">
              <el-input v-model="form.model" placeholder="如 Gen2-Comfort" maxlength="100" />
            </el-form-item>
          </el-col>
          <el-col :span="8">
            <el-form-item label="序列号" prop="serial_no">
              <el-input v-model="form.serial_no" placeholder="出厂序列号" maxlength="100" />
            </el-form-item>
          </el-col>
          <el-col :span="8">
            <el-form-item label="楼层数" prop="floor_count">
              <el-input-number v-model="form.floor_count" :min="1" :max="200" style="width:100%" />
            </el-form-item>
          </el-col>
          <el-col :span="8">
            <el-form-item label="运行速度">
              <el-input v-model="form.speed" placeholder="如 2.5 m/s" maxlength="20" />
            </el-form-item>
          </el-col>
          <el-col :span="8">
            <el-form-item label="额定载重(kg)">
              <el-input-number v-model="form.capacity" :min="0" :max="50000" style="width:100%" />
            </el-form-item>
          </el-col>
          <el-col :span="8">
            <el-form-item label="安装日期">
              <el-date-picker v-model="form.install_date" type="date" placeholder="选择安装日期" style="width:100%" value-format="YYYY-MM-DD" />
            </el-form-item>
          </el-col>
          <el-col :span="8">
            <el-form-item label="上次年检" prop="last_inspection_date">
              <el-date-picker v-model="form.last_inspection_date" type="date" placeholder="上次年检日期" style="width:100%" value-format="YYYY-MM-DD" />
            </el-form-item>
          </el-col>
          <el-col :span="8">
            <el-form-item label="下次年检" prop="next_inspection_date">
              <el-date-picker v-model="form.next_inspection_date" type="date" placeholder="下次年检日期" style="width:100%" value-format="YYYY-MM-DD" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="维保公司" prop="maintain_company">
              <el-input v-model="form.maintain_company" placeholder="维保公司全称" maxlength="200" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="维保电话" prop="maintain_phone">
              <el-input v-model="form.maintain_phone" placeholder="紧急联系电话" maxlength="20" />
            </el-form-item>
          </el-col>
          <el-col :span="8">
            <el-form-item label="合同开始">
              <el-date-picker v-model="form.contract_start" type="date" placeholder="维保合同开始" style="width:100%" value-format="YYYY-MM-DD" />
            </el-form-item>
          </el-col>
          <el-col :span="8">
            <el-form-item label="合同结束">
              <el-date-picker v-model="form.contract_end" type="date" placeholder="维保合同结束" style="width:100%" value-format="YYYY-MM-DD" />
            </el-form-item>
          </el-col>
          <el-col :span="8">
            <el-form-item label="状态" prop="status">
              <el-radio-group v-model="form.status">
                <el-radio :value="1">运行中</el-radio>
                <el-radio :value="0">已停用</el-radio>
              </el-radio-group>
            </el-form-item>
          </el-col>
          <el-col :span="24">
            <el-form-item label="备注">
              <el-input v-model="form.remark" type="textarea" :rows="2" placeholder="备注信息" />
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
import { Connection, Plus, Odometer, CircleCheckFilled, WarningFilled, RemoveFilled, Search } from '@element-plus/icons-vue'

const list = ref<any[]>([])
const total = ref(0)
const loading = ref(false)
const submitting = ref(false)
const dialogVisible = ref(false)
const editId = ref(0)
const formRef = ref()
const communities = ref<any[]>([])
const allBuildings = ref<any[]>([])

const query = reactive({ page: 1, limit: 15, keyword: '', community_id: 0, building_id: 0, status: '' })
const form = reactive<any>({ community_id: null, building_id: null, elevator_no: '', elevator_type: '', brand: '', model: '', serial_no: '', floor_count: 1, speed: '', capacity: 1000, install_date: '', last_inspection_date: '', next_inspection_date: '', maintain_company: '', maintain_phone: '', contract_start: '', contract_end: '', status: 1, remark: '' })

const rules = {
  community_id: [{ required: true, message: '请选择所属小区', trigger: 'change' }],
  building_id: [{ required: true, message: '请选择所属楼栋', trigger: 'change' }],
  elevator_no: [{ required: true, message: '请输入电梯编号', trigger: 'blur' }],
  elevator_type: [{ required: true, message: '请选择电梯类型', trigger: 'change' }],
  brand: [{ required: true, message: '请输入品牌', trigger: 'blur' }],
  model: [{ required: true, message: '请输入型号', trigger: 'blur' }],
  serial_no: [{ required: true, message: '请输入序列号', trigger: 'blur' }],
  floor_count: [{ required: true, message: '请输入楼层数', trigger: 'blur' }],
  maintain_company: [{ required: true, message: '请输入维保公司', trigger: 'blur' }],
  maintain_phone: [{ required: true, message: '请输入维保电话', trigger: 'blur' }],
  last_inspection_date: [{ required: true, message: '请选择上次年检日期', trigger: 'change' }],
  next_inspection_date: [{ required: true, message: '请选择下次年检日期', trigger: 'change' }],
}

const filteredBuildings = computed(() => {
  const data = allBuildings.value || []
  if (!query.community_id) return data
  return data.filter((b: any) => b.community_id == query.community_id)
})

const formBuildings = computed(() => {
  const data = allBuildings.value || []
  if (!form.community_id) return data
  return data.filter((b: any) => b.community_id == form.community_id)
})

const stats = computed(() => {
  const data = list.value || []
  const now = new Date()
  const monthLater = new Date(now.getTime() + 30 * 86400000).toISOString().slice(0, 10)
  return {
    total: total.value,
    running: data.filter((d: any) => d.status == 1).length,
    expiring: data.filter((d: any) => d.next_inspection_date && d.next_inspection_date <= monthLater && d.status == 1).length,
    stopped: data.filter((d: any) => d.status == 0).length,
  }
})

function nextInspectionTag(date: string) {
  if (!date) return 'info'
  const d = new Date(date)
  const now = new Date()
  const diff = (d.getTime() - now.getTime()) / 86400000
  if (diff < 0) return 'danger'
  if (diff < 30) return 'warning'
  return 'success'
}

function onCommunityChange() { query.building_id = 0; loadData() }
function onFormCommunityChange() { form.building_id = null }

async function loadCommunities() {
  try { const res = await apiGet('/admin/community/listAll'); if (res && res.code === 0) communities.value = res.data || [] } catch (_) {}
}

async function loadBuildings() {
  try { const res = await apiGet('/admin/building/listAll'); if (res && (res.code === 0 || !res.code)) allBuildings.value = res.data || [] } catch (_) {}
}

async function loadData() {
  loading.value = true
  try {
    const params: any = { page: query.page, limit: query.limit }
    if (query.keyword) params.keyword = query.keyword
    if (query.community_id) params.community_id = query.community_id
    if (query.building_id) params.building_id = query.building_id
    if (query.status !== '') params.status = query.status
    const res = await apiGet('/admin/equipment/elevatorList', { params })
    if (res && res.code === 0) { list.value = (res.data as any)?.list || res.data || []; total.value = (res.data as any)?.total || res.count || 0 }
  } catch (_) { list.value = []; total.value = 0 } finally { loading.value = false }
}

function resetQuery() {
  query.keyword = ''; query.community_id = 0; query.building_id = 0; query.status = ''
  query.page = 1; loadData()
}

function openForm(row?: any) {
  if (row) {
    editId.value = row.id
    const keys = ['community_id','building_id','elevator_no','elevator_type','brand','model','serial_no','floor_count','speed','capacity','install_date','last_inspection_date','next_inspection_date','maintain_company','maintain_phone','contract_start','contract_end','status','remark']
    keys.forEach(k => { (form as any)[k] = row[k] ?? '' })
  } else {
    editId.value = 0
    Object.assign(form, { community_id: null, building_id: null, elevator_no: '', elevator_type: '', brand: '', model: '', serial_no: '', floor_count: 1, speed: '', capacity: 1000, install_date: '', last_inspection_date: '', next_inspection_date: '', maintain_company: '', maintain_phone: '', contract_start: '', contract_end: '', status: 1, remark: '' })
  }
  dialogVisible.value = true
}

async function handleSubmit() {
  try { await formRef.value?.validate() } catch { return }
  submitting.value = true
  try {
    const url = editId.value ? '/admin/equipment/elevatorEdit' : '/admin/equipment/elevatorAdd'
    const res = await apiPost(url, { ...form, id: editId.value || undefined })
    if (res.code === 0) { ElMessage.success(res.msg); dialogVisible.value = false; loadData() }
  } finally { submitting.value = false }
}

async function handleDelete(row: any) {
  await ElMessageBox.confirm(`确定删除电梯「${row.elevator_no}」吗？`, '删除确认', { type: 'warning' })
  const res = await apiPost('/admin/equipment/elevatorDelete', { id: row.id })
  if (res.code === 0) { ElMessage.success('删除成功'); loadData() }
}

onMounted(() => { loadCommunities(); loadBuildings(); loadData() })
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
.stat-running .stat-icon { background: linear-gradient(135deg, #43e97b, #38f9d7); }
.stat-expiring .stat-icon { background: linear-gradient(135deg, #fa709a, #fee140); }
.stat-stopped .stat-icon { background: linear-gradient(135deg, #a8a8a8, #787878); }
.stat-value { font-size: 28px; font-weight: 700; color: #303133; line-height: 1.1; }
.stat-label { font-size: 13px; color: #909399; margin-top: 2px; }
.filter-card { margin-bottom: 16px; border-radius: 10px; }
.filter-card :deep(.el-card__body) { padding: 16px 16px 4px; }
.table-card { border-radius: 10px; }
.table-card :deep(.el-card__body) { padding: 16px; }
.pagination-wrap { margin-top: 16px; display: flex; justify-content: flex-end; }
:deep(.el-table th) { background: #fafbfc; color: #303133; font-weight: 600; }
.elevator-no { font-weight: 600; color: #409eff; font-family: 'Courier New', monospace; font-size: 14px; }
.brand-cell { display: flex; flex-direction: column; }
.brand-cell .brand { font-weight: 500; color: #303133; }
.brand-cell .model { font-size: 12px; color: #909399; }
</style>
