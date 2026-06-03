<template>
  <div class="equip-page">
    <!-- 页面标题与数据概览 -->
    <div class="page-header">
      <div class="header-left">
        <h2 class="page-title">
          <el-icon :size="24"><Cpu /></el-icon>
          硬件设备管理
        </h2>
        <p class="page-desc">智能网关 · 传感器 · 控制器 · 摄像头等IoT设备统一管理</p>
      </div>
      <el-button type="primary" size="large" @click="openForm()">
        <el-icon><Plus /></el-icon> 添加设备
      </el-button>
    </div>

    <!-- 统计卡片 -->
    <el-row :gutter="16" class="stats-row">
      <el-col :span="6">
        <el-card shadow="hover" class="stat-card stat-total">
          <div class="stat-inner">
            <div class="stat-icon"><el-icon :size="32"><Monitor /></el-icon></div>
            <div class="stat-info">
              <div class="stat-value">{{ stats.total }}</div>
              <div class="stat-label">设备总数</div>
            </div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card shadow="hover" class="stat-card stat-online">
          <div class="stat-inner">
            <div class="stat-icon"><el-icon :size="32"><CircleCheckFilled /></el-icon></div>
            <div class="stat-info">
              <div class="stat-value">{{ stats.online }}</div>
              <div class="stat-label">在线设备</div>
            </div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card shadow="hover" class="stat-card stat-offline">
          <div class="stat-inner">
            <div class="stat-icon"><el-icon :size="32"><WarningFilled /></el-icon></div>
            <div class="stat-info">
              <div class="stat-value">{{ stats.offline }}</div>
              <div class="stat-label">离线设备</div>
            </div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card shadow="hover" class="stat-card stat-type">
          <div class="stat-inner">
            <div class="stat-icon"><el-icon :size="32"><Grid /></el-icon></div>
            <div class="stat-info">
              <div class="stat-value">{{ stats.types }}</div>
              <div class="stat-label">设备类型</div>
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
        <el-form-item label="设备类型">
          <el-select v-model="query.device_type" placeholder="全部类型" clearable style="width:140px" @change="loadData">
            <el-option label="智能网关" value="智能网关" />
            <el-option label="门禁控制器" value="门禁控制器" />
            <el-option label="监控摄像头" value="监控摄像头" />
            <el-option label="环境传感器" value="环境传感器" />
            <el-option label="消防设备" value="消防设备" />
            <el-option label="道闸设备" value="道闸设备" />
            <el-option label="充电桩" value="充电桩" />
            <el-option label="水表采集器" value="水表采集器" />
            <el-option label="电表采集器" value="电表采集器" />
          </el-select>
        </el-form-item>
        <el-form-item label="运行状态">
          <el-select v-model="query.status" placeholder="全部状态" clearable style="width:130px" @change="loadData">
            <el-option label="运行中" :value="1" />
            <el-option label="已离线" :value="0" />
          </el-select>
        </el-form-item>
        <el-form-item>
          <el-input v-model="query.keyword" placeholder="搜索设备名称/编号/位置" clearable style="width:240px" @keyup.enter="loadData" @clear="loadData">
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
        <el-table-column prop="device_code" label="设备编号" min-width="140" sortable="custom" />
        <el-table-column prop="device_name" label="设备名称" min-width="150" />
        <el-table-column prop="community_name" label="所属小区" width="130" />
        <el-table-column prop="device_type" label="设备类型" width="120">
          <template #default="{ row }">
            <el-tag :type="deviceTypeTag(row.device_type)" effect="light" size="small">
              {{ row.device_type }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="manufacturer" label="厂家品牌" width="120" />
        <el-table-column prop="model" label="型号" width="100" />
        <el-table-column prop="location" label="安装位置" min-width="140" show-overflow-tooltip />
        <el-table-column prop="ip_address" label="IP地址" width="140" />
        <el-table-column label="状态" width="90" align="center">
          <template #default="{ row }">
            <el-tag :type="row.status == 1 ? 'success' : 'danger'" effect="dark" size="small">
              {{ row.status == 1 ? '在线' : '离线' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="last_heartbeat" label="最后心跳" width="160" />
        <el-table-column label="操作" width="160" fixed="right">
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
    <el-dialog v-model="dialogVisible" :title="editId ? '编辑设备' : '添加设备'" width="700px" destroy-on-close top="5vh">
      <el-form :model="form" ref="formRef" :rules="rules" label-width="90px" label-position="right">
        <el-row :gutter="16">
          <el-col :span="12">
            <el-form-item label="所属小区" prop="community_id">
              <el-select v-model="form.community_id" placeholder="选择小区" style="width:100%">
                <el-option v-for="c in communities" :key="c.id" :label="c.name" :value="c.id" />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="设备类型" prop="device_type">
              <el-select v-model="form.device_type" placeholder="选择设备类型" style="width:100%">
                <el-option v-for="t in deviceTypes" :key="t" :label="t" :value="t" />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="设备编号" prop="device_code">
              <el-input v-model="form.device_code" placeholder="如 GW-01-001" maxlength="100" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="设备名称" prop="device_name">
              <el-input v-model="form.device_name" placeholder="如 1号楼门禁控制器" maxlength="100" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="厂家品牌" prop="manufacturer">
              <el-input v-model="form.manufacturer" placeholder="如 海康威视" maxlength="100" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="设备型号" prop="model">
              <el-input v-model="form.model" placeholder="如 DS-K1T671M" maxlength="100" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="通信协议" prop="protocol">
              <el-input v-model="form.protocol" placeholder="如 MQTT / Modbus / RTSP" maxlength="50" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="IP地址" prop="ip_address">
              <el-input v-model="form.ip_address" placeholder="如 192.168.1.100" maxlength="50" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="端口号" prop="port">
              <el-input-number v-model="form.port" :min="0" :max="65535" style="width:100%" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="序列号" prop="serial_no">
              <el-input v-model="form.serial_no" placeholder="设备出厂序列号" maxlength="100" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="安装位置" prop="location">
              <el-input v-model="form.location" placeholder="如 1号楼1层大厅" maxlength="255" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="运行状态" prop="status">
              <el-radio-group v-model="form.status">
                <el-radio :value="1">在线</el-radio>
                <el-radio :value="0">离线</el-radio>
              </el-radio-group>
            </el-form-item>
          </el-col>
          <el-col :span="24">
            <el-form-item label="备注说明" prop="remark">
              <el-input v-model="form.remark" type="textarea" :rows="2" placeholder="设备备注信息" />
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
import { Cpu, Plus, Monitor, CircleCheckFilled, WarningFilled, Grid, Search } from '@element-plus/icons-vue'

const list = ref<any[]>([])
const total = ref(0)
const loading = ref(false)
const submitting = ref(false)
const dialogVisible = ref(false)
const editId = ref(0)
const formRef = ref()
const communities = ref<any[]>([])

const query = reactive({ page: 1, limit: 15, keyword: '', community_id: 0, device_type: '', status: '' })
const form = reactive<any>({ community_id: null, device_type: '', device_code: '', device_name: '', manufacturer: '', model: '', protocol: '', ip_address: '', port: 0, serial_no: '', location: '', status: 1, remark: '' })

const deviceTypes = ['智能网关', '门禁控制器', '监控摄像头', '环境传感器', '消防设备', '道闸设备', '充电桩', '水表采集器', '电表采集器']

const rules = {
  device_code: [{ required: true, message: '请输入设备编号', trigger: 'blur' }],
  device_name: [{ required: true, message: '请输入设备名称', trigger: 'blur' }],
  community_id: [{ required: true, message: '请选择所属小区', trigger: 'change' }],
  device_type: [{ required: true, message: '请选择设备类型', trigger: 'change' }],
}

const stats = computed(() => {
  const data = list.value || []
  return {
    total: total.value,
    online: data.filter((d: any) => d.status == 1).length,
    offline: data.filter((d: any) => d.status == 0).length,
    types: new Set(data.map((d: any) => d.device_type).filter(Boolean)).size,
  }
})

function deviceTypeTag(type: string) {
  const map: Record<string, string> = { '智能网关': '', '门禁控制器': 'warning', '监控摄像头': 'info', '环境传感器': 'success', '消防设备': 'danger', '道闸设备': '', '充电桩': 'success', '水表采集器': 'info', '电表采集器': 'warning' }
  return map[type] || ''
}

async function loadCommunities() {
  try { const res = await apiGet('/admin/community/listAll'); if (res && res.code === 0) communities.value = res.data || [] } catch (_) {}
}

async function loadData() {
  loading.value = true
  try {
    const params: any = { page: query.page, limit: query.limit }
    if (query.keyword) params.keyword = query.keyword
    if (query.community_id) params.community_id = query.community_id
    if (query.device_type) params.device_type = query.device_type
    if (query.status !== '') params.status = query.status
    const res = await apiGet('/admin/equipment/deviceList', { params })
    if (res && res.code === 0) { list.value = res.data.list || []; total.value = res.data.total || 0 }
  } catch (_) { list.value = []; total.value = 0 } finally { loading.value = false }
}

function resetQuery() {
  query.keyword = ''; query.community_id = 0; query.device_type = ''; query.status = ''
  query.page = 1; loadData()
}

function openForm(row?: any) {
  if (row) {
    editId.value = row.id
    Object.assign(form, { community_id: row.community_id, device_type: row.device_type, device_code: row.device_code, device_name: row.device_name, manufacturer: row.manufacturer, model: row.model, protocol: row.protocol, ip_address: row.ip_address, port: row.port || 0, serial_no: row.serial_no, location: row.location, status: row.status ?? 1, remark: row.remark || '' })
  } else {
    editId.value = 0
    Object.assign(form, { community_id: null, device_type: '', device_code: '', device_name: '', manufacturer: '', model: '', protocol: '', ip_address: '', port: 0, serial_no: '', location: '', status: 1, remark: '' })
  }
  dialogVisible.value = true
}

async function handleSubmit() {
  const valid = await formRef.value.validate().catch(() => false)
  if (!valid) return
  submitting.value = true
  try {
    const url = editId.value ? '/admin/equipment/deviceEdit' : '/admin/equipment/deviceAdd'
    const res = await apiPost(url, { ...form, id: editId.value || undefined })
    if (res.code === 0) { ElMessage.success(res.msg); dialogVisible.value = false; loadData() }
  } finally { submitting.value = false }
}

async function handleDelete(row: any) {
  await ElMessageBox.confirm(`确定删除设备「${row.device_name}」吗？`, '删除确认', { type: 'warning', confirmButtonText: '确定删除', cancelButtonText: '取消' })
  const res = await apiPost('/admin/equipment/deviceDelete', { id: row.id })
  if (res.code === 0) { ElMessage.success('删除成功'); loadData() }
}

onMounted(() => { loadCommunities(); loadData() })
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
.stat-online .stat-icon { background: linear-gradient(135deg, #43e97b, #38f9d7); }
.stat-offline .stat-icon { background: linear-gradient(135deg, #f093fb, #f5576c); }
.stat-type .stat-icon { background: linear-gradient(135deg, #4facfe, #00f2fe); }
.stat-value { font-size: 28px; font-weight: 700; color: #303133; line-height: 1.1; }
.stat-label { font-size: 13px; color: #909399; margin-top: 2px; }
.filter-card { margin-bottom: 16px; border-radius: 10px; }
.filter-card :deep(.el-card__body) { padding: 16px 16px 4px; }
.table-card { border-radius: 10px; }
.table-card :deep(.el-card__body) { padding: 16px; }
.pagination-wrap { margin-top: 16px; display: flex; justify-content: flex-end; }
:deep(.el-table th) { background: #fafbfc; color: #303133; font-weight: 600; }
</style>
