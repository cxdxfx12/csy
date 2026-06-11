<template>
  <div class="equip-page">
    <!-- 页面标题 -->
    <div class="page-header">
      <div class="header-left">
        <h2 class="page-title">
          <el-icon :size="24"><CircleCheck /></el-icon>
          电梯巡检管理
        </h2>
        <p class="page-desc">定期巡检 · 安全检查 · 整改追踪 · 合规档案</p>
      </div>
      <el-button type="primary" size="large" @click="openForm()">
        <el-icon><Plus /></el-icon> 登记巡检
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
              <div class="stat-label">巡检总数</div>
            </div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card shadow="hover" class="stat-card stat-month">
          <div class="stat-inner">
            <div class="stat-icon"><el-icon :size="32"><Calendar /></el-icon></div>
            <div class="stat-info">
              <div class="stat-value">{{ stats.thisMonth }}</div>
              <div class="stat-label">本月巡检</div>
            </div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card shadow="hover" class="stat-card stat-pass">
          <div class="stat-inner">
            <div class="stat-icon"><el-icon :size="32"><CircleCheckFilled /></el-icon></div>
            <div class="stat-info">
              <div class="stat-value">{{ stats.passed }}</div>
              <div class="stat-label">合格</div>
            </div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card shadow="hover" class="stat-card stat-fail">
          <div class="stat-inner">
            <div class="stat-icon"><el-icon :size="32"><WarningFilled /></el-icon></div>
            <div class="stat-info">
              <div class="stat-value">{{ stats.failed }}</div>
              <div class="stat-label">需整改</div>
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
        <el-form-item label="巡检类型">
          <el-select v-model="query.inspection_type" placeholder="全部类型" clearable style="width:130px" @change="loadData">
            <el-option label="日常巡检" :value="1" />
            <el-option label="月度检查" :value="2" />
            <el-option label="季度检查" :value="3" />
            <el-option label="年度检验" :value="4" />
            <el-option label="专项检查" :value="5" />
          </el-select>
        </el-form-item>
        <el-form-item label="检查结果">
          <el-select v-model="query.result" placeholder="全部结果" clearable style="width:120px" @change="loadData">
            <el-option label="合格" :value="1" />
            <el-option label="需整改" :value="0" />
          </el-select>
        </el-form-item>
        <el-form-item>
          <el-input v-model="query.keyword" placeholder="搜索检查人/单位/电梯编号" clearable style="width:250px" @keyup.enter="loadData" @clear="loadData">
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
        <el-table-column prop="inspection_date" label="巡检日期" width="120" sortable="custom" />
        <el-table-column prop="elevator_no" label="电梯编号" width="120">
          <template #default="{ row }">
            <span class="elevator-no">{{ row.elevator_no || '-' }}</span>
          </template>
        </el-table-column>
        <el-table-column prop="community_name" label="所属小区" width="110" />
        <el-table-column label="巡检类型" width="110">
          <template #default="{ row }">
            <el-tag effect="plain" size="small" :type="inspectionTypeTag(row.inspection_type)">
              {{ inspectionTypeText(row.inspection_type) }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="inspector" label="检查人" width="100" />
        <el-table-column prop="inspection_company" label="检查单位" min-width="150" show-overflow-tooltip />
        <el-table-column label="检查结果" width="100" align="center">
          <template #default="{ row }">
            <el-tag :type="row.result == 1 ? 'success' : 'danger'" effect="dark" size="small">
              {{ row.result == 1 ? '合格' : '需整改' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="rectify_items" label="整改项" min-width="160" show-overflow-tooltip>
          <template #default="{ row }">
            <span :class="{ 'rectify-text': row.result != 1 }">{{ row.rectify_items || '-' }}</span>
          </template>
        </el-table-column>
        <el-table-column prop="certificate_file" label="证书/附件" width="130" show-overflow-tooltip>
          <template #default="{ row }">
            <el-link v-if="row.certificate_file" type="primary" :underline="false">
              <el-icon><Document /></el-icon> {{ row.certificate_file }}
            </el-link>
            <span v-else class="no-file">-</span>
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
    <el-dialog v-model="dialogVisible" :title="editId ? '编辑巡检记录' : '登记巡检'" width="650px" destroy-on-close top="5vh">
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
            <el-form-item label="巡检类型" prop="inspection_type">
              <el-select v-model="form.inspection_type" placeholder="选择巡检类型" style="width:100%">
                <el-option label="日常巡检" :value="1" />
                <el-option label="月度检查" :value="2" />
                <el-option label="季度检查" :value="3" />
                <el-option label="年度检验" :value="4" />
                <el-option label="专项检查" :value="5" />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="巡检日期" prop="inspection_date">
              <el-date-picker v-model="form.inspection_date" type="date" placeholder="选择巡检日期" style="width:100%" value-format="YYYY-MM-DD" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="检查结果" prop="result">
              <el-radio-group v-model="form.result">
                <el-radio :value="1">合格</el-radio>
                <el-radio :value="0">需整改</el-radio>
              </el-radio-group>
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="检查人" prop="inspector">
              <el-input v-model="form.inspector" placeholder="检查人员姓名" maxlength="50" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="检查单位" prop="inspection_company">
              <el-input v-model="form.inspection_company" placeholder="检验机构全称" maxlength="200" />
            </el-form-item>
          </el-col>
          <el-col :span="24">
            <el-form-item label="整改项">
              <el-input v-model="form.rectify_items" type="textarea" :rows="2" placeholder="需整改的具体项目，合格时可不填..." />
            </el-form-item>
          </el-col>
          <el-col :span="24">
            <el-form-item label="证书/附件">
              <el-input v-model="form.certificate_file" placeholder="检验合格证编号或附件文件名" maxlength="255" />
            </el-form-item>
          </el-col>
          <el-col :span="24">
            <el-form-item label="备注">
              <el-input v-model="form.remark" type="textarea" :rows="2" placeholder="其他备注信息..." />
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
import { CircleCheck, Plus, List, Calendar, CircleCheckFilled, WarningFilled, Search, Document } from '@element-plus/icons-vue'

const list = ref<any[]>([])
const total = ref(0)
const loading = ref(false)
const submitting = ref(false)
const dialogVisible = ref(false)
const editId = ref(0)
const formRef = ref()
const communities = ref<any[]>([])
const elevators = ref<any[]>([])

const query = reactive({ page: 1, limit: 15, keyword: '', community_id: 0, elevator_id: 0, inspection_type: '', result: '' })
const form = reactive<any>({ community_id: null, elevator_id: null, inspection_type: 1, inspection_date: '', inspector: '', inspection_company: '', result: 1, rectify_items: '', certificate_file: '', remark: '' })

const rules = {
  community_id: [{ required: true, message: '请选择所属小区', trigger: 'change' }],
  elevator_id: [{ required: true, message: '请选择关联电梯', trigger: 'change' }],
  inspection_type: [{ required: true, message: '请选择巡检类型', trigger: 'change' }],
  inspection_date: [{ required: true, message: '请选择巡检日期', trigger: 'change' }],
  inspector: [{ required: true, message: '请输入检查人', trigger: 'blur' }],
  inspection_company: [{ required: true, message: '请输入检查单位', trigger: 'blur' }],
}

const filteredElevators = computed(() => {
  const data = elevators.value || []
  if (!form.community_id) return data
  return data.filter((e: any) => e.community_id == form.community_id)
})

const stats = computed(() => {
  const data = list.value || []
  const now = new Date()
  const monthStart = now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0') + '-01'
  return {
    total: total.value,
    thisMonth: data.filter((d: any) => d.inspection_date && d.inspection_date >= monthStart).length,
    passed: data.filter((d: any) => d.result == 1).length,
    failed: data.filter((d: any) => d.result == 0).length,
  }
})

function inspectionTypeText(type: number) {
  const map: Record<number, string> = { 1: '日常巡检', 2: '月度检查', 3: '季度检查', 4: '年度检验', 5: '专项检查' }
  return map[type] || '未知'
}

function inspectionTypeTag(type: number) {
  const map: Record<number, string> = { 1: 'info', 2: '', 3: 'warning', 4: 'danger', 5: 'success' }
  return map[type] || ''
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
    if (query.inspection_type) params.inspection_type = query.inspection_type
    if (query.result !== '') params.result = query.result
    const res = await apiGet('/admin/equipment/elevatorInspectionList', { params })
    if (res && res.code === 0) { list.value = (res.data as any)?.list || res.data || []; total.value = (res.data as any)?.total || res.count || 0 }
  } catch (_) { list.value = []; total.value = 0 } finally { loading.value = false }
}

function resetQuery() {
  query.keyword = ''; query.community_id = 0; query.elevator_id = 0; query.inspection_type = ''; query.result = ''
  query.page = 1; loadData()
}

function onFormCommunityChange() {
  form.elevator_id = null
}

function openForm(row?: any) {
  if (row) {
    editId.value = row.id
    const keys = ['community_id','elevator_id','inspection_type','inspection_date','inspector','inspection_company','result','rectify_items','certificate_file','remark']
    keys.forEach(k => { (form as any)[k] = row[k] ?? (k === 'result' ? 1 : (k === 'inspection_type' ? 1 : '')) })
  } else {
    editId.value = 0
    Object.assign(form, { community_id: null, elevator_id: null, inspection_type: 1, inspection_date: '', inspector: '', inspection_company: '', result: 1, rectify_items: '', certificate_file: '', remark: '' })
  }
  dialogVisible.value = true
}

async function handleSubmit() {
  try { await formRef.value?.validate() } catch { return }
  submitting.value = true
  try {
    const url = editId.value ? '/admin/equipment/elevatorInspectionEdit' : '/admin/equipment/elevatorInspectionAdd'
    const res = await apiPost(url, { ...form, id: editId.value || undefined })
    if (res.code === 0) { ElMessage.success(res.msg); dialogVisible.value = false; loadData() }
  } finally { submitting.value = false }
}

async function handleDelete(row: any) {
  await ElMessageBox.confirm('确定删除该巡检记录吗？', '删除确认', { type: 'warning' })
  const res = await apiPost('/admin/equipment/elevatorInspectionDelete', { id: row.id })
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
.stat-month .stat-icon { background: linear-gradient(135deg, #43e97b, #38f9d7); }
.stat-pass .stat-icon { background: linear-gradient(135deg, #4facfe, #00f2fe); }
.stat-fail .stat-icon { background: linear-gradient(135deg, #fa709a, #fee140); }
.stat-value { font-size: 28px; font-weight: 700; color: #303133; line-height: 1.1; }
.stat-label { font-size: 13px; color: #909399; margin-top: 2px; }
.filter-card { margin-bottom: 16px; border-radius: 10px; }
.filter-card :deep(.el-card__body) { padding: 16px 16px 4px; }
.table-card { border-radius: 10px; }
.table-card :deep(.el-card__body) { padding: 16px; }
.pagination-wrap { margin-top: 16px; display: flex; justify-content: flex-end; }
:deep(.el-table th) { background: #fafbfc; color: #303133; font-weight: 600; }
.elevator-no { font-weight: 600; color: #409eff; font-family: 'Courier New', monospace; }
.rectify-text { color: #e73b3b; font-weight: 500; }
.no-file { color: #c0c4cc; }
</style>
