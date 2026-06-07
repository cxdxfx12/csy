<template>
  <div class="page-container">
    <div class="search-bar">
      <el-form :model="query" inline>
        <el-form-item label="小区">
          <el-select v-model="query.community_id" placeholder="全部小区" clearable style="width:180px">
            <el-option v-for="c in communities" :key="c.id" :label="c.name" :value="c.id" />
          </el-select>
        </el-form-item>
        <el-form-item label="品牌">
          <el-select v-model="query.brand" placeholder="全部品牌" clearable style="width:140px">
            <el-option v-for="(v,k) in brandMap" :key="k" :label="v.name" :value="k" />
          </el-select>
        </el-form-item>
        <el-form-item label="状态">
          <el-select v-model="query.enabled" placeholder="全部" clearable style="width:100px">
            <el-option label="启用" :value="1" /><el-option label="禁用" :value="0" />
          </el-select>
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="loadData">搜索</el-button>
          <el-button @click="resetQuery">重置</el-button>
          <el-button type="success" @click="openAdd">添加录像机</el-button>
          <el-button type="warning" @click="patrolAll" :loading="patrolLoading">一键巡检</el-button>
        </el-form-item>
      </el-form>
    </div>

    <!-- 统计卡片 -->
    <el-row :gutter="16" class="stats-row">
      <el-col :span="6"><el-card shadow="hover" class="stat-card"><div class="stat-value" style="color:#2563eb">{{ stats.nvrTotal }}</div><div class="stat-label">录像机总数</div></el-card></el-col>
      <el-col :span="6"><el-card shadow="hover" class="stat-card"><div class="stat-value" style="color:#16a34a">{{ stats.cameraOnline }}</div><div class="stat-label">在线摄像头</div></el-card></el-col>
      <el-col :span="6"><el-card shadow="hover" class="stat-card"><div class="stat-value" style="color:#dc2626">{{ stats.cameraOffline }}</div><div class="stat-label">离线摄像头</div></el-card></el-col>
      <el-col :span="6"><el-card shadow="hover" class="stat-card"><div class="stat-value" style="color:#f59e0b">{{ stats.eventPending }}</div><div class="stat-label">未处理告警</div></el-card></el-col>
    </el-row>

    <!-- 录像机列表 -->
    <el-card shadow="never" class="table-card">
      <template #header><span class="card-title">📹 录像机列表</span></template>
      <el-table :data="list" v-loading="loading" stripe border>
        <el-table-column type="index" label="#" width="50" />
        <el-table-column prop="id" label="ID" width="60" />
        <el-table-column prop="community_name" label="小区" width="120" />
        <el-table-column prop="nvr_name" label="录像机" min-width="150" show-overflow-tooltip />
        <el-table-column label="品牌" width="120">
          <template #default="{row}"><el-tag effect="plain" type="primary">{{ brandMap[row.brand]?.name || row.brand }}</el-tag></template>
        </el-table-column>
        <el-table-column prop="model" label="型号" width="140" />
        <el-table-column prop="api_url" label="接口地址" width="200" show-overflow-tooltip />
        <el-table-column label="通道数" width="80" align="center">
          <template #default="{row}">{{ row.channel_count || 0 }}</template>
        </el-table-column>
        <el-table-column label="摄像头" width="130" align="center">
          <template #default="{row}">
            <span :style="{color: (row.device_total - row.device_online) > 0 ? '#dc2626' : '#16a34a'}">
              {{ row.device_online }}/{{ row.device_total }}
            </span>
          </template>
        </el-table-column>
        <el-table-column label="告警" width="70" align="center">
          <template #default="{row}">
            <el-badge :value="row.alarm_count || 0" :hidden="!row.alarm_count" type="danger" />
          </template>
        </el-table-column>
        <el-table-column label="状态" width="70">
          <template #default="{row}"><el-switch :model-value="row.enabled===1" @change="toggleEnable(row)" /></template>
        </el-table-column>
        <el-table-column label="操作" width="420" fixed="right">
          <template #default="{row}">
            <el-button size="small" @click="openEdit(row)">编辑</el-button>
            <el-button size="small" type="success" @click="testConn(row)">测试连接</el-button>
            <el-button size="small" type="primary" @click="showCameras(row)">摄像头</el-button>
            <el-button size="small" type="info" @click="checkHdd(row)">硬盘</el-button>
            <el-button size="small" type="warning" @click="showAlarms(row)">告警</el-button>
            <el-button size="small" type="danger" @click="handleDelete(row)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>
      <div class="pagination">
        <el-pagination v-model:current-page="query.page" v-model:page-size="query.limit" :total="total" :page-sizes="[15,30,50]" layout="total,sizes,prev,pager,next" @current-change="loadData" @size-change="loadData" />
      </div>
    </el-card>

    <!-- 添加/编辑弹窗 -->
    <el-dialog v-model="formVisible" :title="formTitle" width="600px" destroy-on-close>
      <el-form :model="form" label-width="100px">
        <el-form-item label="小区" required>
          <el-select v-model="form.community_id" placeholder="请选择小区" style="width:100%" :disabled="isEdit">
            <el-option v-for="c in communities" :key="c.id" :label="c.name" :value="c.id" />
          </el-select>
        </el-form-item>
        <el-form-item label="录像机名称" required>
          <el-input v-model="form.nvr_name" placeholder="如：南门监控室NVR、北门录像机" />
        </el-form-item>
        <el-form-item label="品牌" required>
          <el-select v-model="form.brand" placeholder="请选择品牌" style="width:100%" @change="onBrandChange">
            <el-option v-for="(v,k) in brandMap" :key="k" :label="v.name + ' (' + v.protocol + ')'" :value="k" />
          </el-select>
        </el-form-item>
        <el-form-item label="型号">
          <el-select v-model="form.model" placeholder="请选择型号(可选)" style="width:100%" clearable filterable>
            <el-option v-for="m in currentModels" :key="m.model" :label="m.model + ' - ' + m.description" :value="m.model" />
          </el-select>
        </el-form-item>
        <el-divider content-position="left">接口参数</el-divider>
        <el-form-item label="IP地址">
          <el-input v-model="form.api_url" placeholder="如 192.168.1.200">
            <template #prepend>http://</template>
          </el-input>
        </el-form-item>
        <el-form-item label="端口">
          <el-input-number v-model="form.api_port" :min="1" :max="65535" />
          <span class="form-tip" style="margin-left:8px">{{ brandMap[form.brand]?.protocol || '' }} 默认端口: {{ brandMap[form.brand]?.port || 80 }}</span>
        </el-form-item>
        <el-form-item label="用户名">
          <el-input v-model="form.api_username" placeholder="默认 admin" />
        </el-form-item>
        <el-form-item label="密码">
          <el-input v-model="form.api_password" placeholder="NVR登录密码" show-password />
        </el-form-item>
        <el-form-item label="通道数">
          <el-input-number v-model="form.channel_count" :min="1" :max="256" :step="4" />
          <span class="form-tip" style="margin-left:8px">根据NVR实际接入路数设置</span>
        </el-form-item>
        <el-form-item label="备注">
          <el-input v-model="form.remark" type="textarea" rows="2" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="formVisible=false">取消</el-button>
        <el-button type="primary" @click="submitForm" :loading="submitting">确定</el-button>
      </template>
    </el-dialog>

    <!-- 摄像头状态弹窗 -->
    <el-dialog v-model="cameraVisible" title="摄像头状态" width="700px" destroy-on-close>
      <div v-loading="cameraLoading">
        <el-alert v-if="selectedNvr" :title="selectedNvr.nvr_name + ' - 摄像头实时状态'" type="info" :closable="false" style="margin-bottom:12px" />
        <el-table :data="cameraList" stripe border>
          <el-table-column label="通道" width="70"><template #default="{row}">CH{{ row.channel_no }}</template></el-table-column>
          <el-table-column prop="camera_name" label="名称" width="120" />
          <el-table-column label="在线" width="80" align="center">
            <template #default="{row}"><el-tag :type="row.online ? 'success' : 'danger'" size="small">{{ row.online ? '在线' : '离线' }}</el-tag></template>
          </el-table-column>
          <el-table-column label="录像状态" width="100">
            <template #default="{row}"><el-tag :type="row.record_status==='recording' ? 'success' : 'info'" size="small">{{ row.record_status==='recording' ? '录制中' : row.record_status==='stopped' ? '已停止' : '未知' }}</el-tag></template>
          </el-table-column>
          <el-table-column prop="resolution" label="分辨率" width="100" />
          <el-table-column prop="error_info" label="异常信息" min-width="150" show-overflow-tooltip>
            <template #default="{row}"><span :style="{color:row.error_info?'#dc2626':''}">{{ row.error_info || '-' }}</span></template>
          </el-table-column>
        </el-table>
        <div style="margin-top:12px;text-align:right">
          <el-button type="primary" @click="refreshCameras" :loading="cameraLoading">刷新状态</el-button>
        </div>
      </div>
    </el-dialog>

    <!-- 硬盘状态弹窗 -->
    <el-dialog v-model="hddVisible" title="硬盘状态" width="550px" destroy-on-close>
      <div v-loading="hddLoading">
        <el-alert v-if="selectedNvr" :title="selectedNvr.nvr_name + ' - 硬盘健康状态'" type="info" :closable="false" style="margin-bottom:12px" />
        <el-table :data="hddList" stripe border>
          <el-table-column label="硬盘" width="80"><template #default="{row}">硬盘{{ row.hdd_no }}</template></el-table-column>
          <el-table-column label="容量" width="100">
            <template #default="{row}">{{ row.capacity_gb > 0 ? row.capacity_gb + ' GB' : '-' }}</template>
          </el-table-column>
          <el-table-column label="剩余" width="100">
            <template #default="{row}">{{ row.free_gb > 0 ? row.free_gb + ' GB' : '-' }}</template>
          </el-table-column>
          <el-table-column label="状态" width="90">
            <template #default="{row}">
              <el-tag :type="row.status==='ok' ? 'success' : row.status==='unknown' ? 'info' : 'danger'" size="small">
                {{ row.status==='ok'?'正常':row.status==='full'?'已满':'异常' }}
              </el-tag>
            </template>
          </el-table-column>
          <el-table-column label="健康度" width="100">
            <template #default="{row}">
              <el-progress :percentage="row.health_percent" :color="row.health_percent > 70 ? '#16a34a' : '#dc2626'" :stroke-width="16" />
            </template>
          </el-table-column>
        </el-table>
        <div style="margin-top:12px;text-align:right">
          <el-button type="primary" @click="refreshHdd" :loading="hddLoading">刷新</el-button>
        </div>
      </div>
    </el-dialog>

    <!-- 告警事件弹窗 -->
    <el-dialog v-model="alarmVisible" title="告警事件" width="800px" destroy-on-close @opened="loadEvents">
      <el-form :model="eventQuery" inline style="margin-bottom:12px">
        <el-form-item label="类型">
          <el-select v-model="eventQuery.event_type" placeholder="全部" clearable style="width:140px" @change="loadEvents">
            <el-option label="摄像头离线" value="camera_offline" />
            <el-option label="摄像头恢复" value="camera_online" />
            <el-option label="硬盘故障" value="hdd_error" />
            <el-option label="硬盘已满" value="hdd_full" />
            <el-option label="录像异常" value="record_error" />
          </el-select>
        </el-form-item>
        <el-form-item label="状态">
          <el-select v-model="eventQuery.handled" placeholder="全部" clearable style="width:100px" @change="loadEvents">
            <el-option label="未处理" :value="0" /><el-option label="已处理" :value="1" />
          </el-select>
        </el-form-item>
      </el-form>
      <el-table :data="eventList" stripe border max-height="400">
        <el-table-column prop="id" label="ID" width="60" />
        <el-table-column label="类型" width="110">
          <template #default="{row}">
            <el-tag :type="eventTypeTag(row.event_type)" size="small">{{ eventTypeName(row.event_type) }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="event_desc" label="描述" min-width="200" show-overflow-tooltip />
        <el-table-column prop="nvr_name" label="录像机" width="120" />
        <el-table-column prop="camera_name" label="摄像头" width="100" />
        <el-table-column prop="create_time" label="时间" width="160" />
        <el-table-column label="状态" width="90">
          <template #default="{row}"><el-tag :type="row.handled?'success':'warning'" size="small">{{ row.handled?'已处理':'未处理' }}</el-tag></template>
        </el-table-column>
        <el-table-column label="操作" width="80">
          <template #default="{row}">
            <el-button v-if="!row.handled" size="small" type="primary" @click="handleEvent(row)">处理</el-button>
          </template>
        </el-table-column>
      </el-table>
      <div class="pagination">
        <el-pagination v-model:current-page="eventQuery.page" v-model:page-size="eventQuery.limit" :total="eventTotal" :page-sizes="[10,20,50]" layout="total,sizes,prev,pager,next" small @current-change="loadEvents" @size-change="loadEvents" />
      </div>
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
const formVisible = ref(false)
const submitting = ref(false)
const patrolLoading = ref(false)
const isEdit = ref(false)
const editId = ref(0)
const communities = ref<any[]>([])
const brandMap = ref<Record<string,any>>({})
const currentModels = ref<any[]>([])

// 统计
const stats = reactive({ nvrTotal:0, cameraOnline:0, cameraOffline:0, eventPending:0 })

// 摄像头
const cameraVisible = ref(false)
const cameraLoading = ref(false)
const cameraList = ref<any[]>([])
const selectedNvr = ref<any>(null)

// 硬盘
const hddVisible = ref(false)
const hddLoading = ref(false)
const hddList = ref<any[]>([])

// 告警
const alarmVisible = ref(false)
const eventList = ref<any[]>([])
const eventTotal = ref(0)
const eventQuery = reactive({ config_id: '', event_type: '', handled: '', page: 1, limit: 15 })

const form = reactive({
  community_id: undefined as any, nvr_name: '', brand: 'hikvision', model: '',
  api_url: '', api_port: 80, api_username: 'admin', api_password: '',
  channel_count: 4, remark: ''
})
const query = reactive({ community_id: undefined as any, brand: '', enabled: '', page: 1, limit: 15 })

const formTitle = ref('添加录像机')

function resetQuery() { query.community_id = undefined; query.brand = ''; query.enabled = ''; query.page = 1; loadData() }

async function loadData() {
  loading.value = true
  try {
    const r = await apiGet('/admin/monitoring/surveillanceConfigList', { ...query })
    list.value = r.data?.list || r.data || []
    total.value = r.count || r.data?.total || list.value.length
  } catch { list.value = []; total.value = 0 }
  finally { loading.value = false }
}

async function loadStats() {
  try {
    const r = await apiGet('/admin/monitoring/surveillanceTodayStats')
    const d = r.data || {}
    stats.nvrTotal = d.nvr_total || 0
    stats.cameraOnline = d.camera_online || 0
    stats.cameraOffline = d.camera_offline || 0
    stats.eventPending = d.event_pending || 0
  } catch {}
}

function openAdd() {
  isEdit.value = false; editId.value = 0
  Object.assign(form, { community_id:undefined, nvr_name:'', brand:'hikvision', model:'', api_url:'', api_port:80, api_username:'admin', api_password:'', channel_count:4, remark:'' })
  formTitle.value = '添加录像机'; formVisible.value = true
}

function openEdit(row: any) {
  isEdit.value = true; editId.value = row.id
  Object.assign(form, { community_id:row.community_id, nvr_name:row.nvr_name, brand:row.brand, model:row.model||'', api_url:row.api_url||'', api_port:row.api_port||80, api_username:row.api_username||'admin', api_password:row.api_password||'', channel_count:row.channel_count||4, remark:row.remark||'' })
  formTitle.value = '编辑录像机'; formVisible.value = true
  onBrandChange()
}

async function submitForm() {
  if (!form.community_id) { ElMessage.warning('请选择小区'); return }
  if (!form.nvr_name) { ElMessage.warning('请输入录像机名称'); return }
  submitting.value = true
  try {
    const url = isEdit.value ? '/admin/monitoring/surveillanceConfigEdit' : '/admin/monitoring/surveillanceConfigAdd'
    const payload: any = { ...form }
    if (isEdit.value) payload.id = editId.value
    await apiPost(url, payload)
    ElMessage.success(isEdit.value ? '更新成功' : '添加成功')
    formVisible.value = false; loadData(); loadStats()
  } finally { submitting.value = false }
}

async function toggleEnable(row: any) {
  try { await apiPost('/admin/monitoring/surveillanceConfigEdit', { id: row.id, enabled: row.enabled === 1 ? 0 : 1 }); loadData() } catch {}
}

async function onBrandChange() {
  try {
    const r = await apiGet('/admin/monitoring/surveillanceModels', { brand: form.brand })
    currentModels.value = r.data || []
  } catch { currentModels.value = [] }
}

async function testConn(row: any) {
  try {
    const r = await apiPost('/admin/monitoring/surveillanceTestConn', { id: row.id })
    ElMessage.success({ message: '连接成功', type: 'success' })
  } catch (e: any) { ElMessage.error('连接失败') }
}

async function showCameras(row: any) {
  selectedNvr.value = row
  cameraVisible.value = true
  await refreshCameras()
}

async function refreshCameras() {
  if (!selectedNvr.value) return
  cameraLoading.value = true
  try {
    const r = await apiGet('/admin/monitoring/surveillanceCameraStatus', { id: selectedNvr.value.id })
    cameraList.value = r.data || []
  } catch { cameraList.value = [] }
  finally { cameraLoading.value = false }
}

async function checkHdd(row: any) {
  selectedNvr.value = row
  hddVisible.value = true
  await refreshHdd()
}

async function refreshHdd() {
  if (!selectedNvr.value) return
  hddLoading.value = true
  try {
    const r = await apiGet('/admin/monitoring/surveillanceHddStatus', { id: selectedNvr.value.id })
    hddList.value = r.data || []
  } catch { hddList.value = [] }
  finally { hddLoading.value = false }
}

async function showAlarms(row: any) {
  selectedNvr.value = row
  eventQuery.config_id = row.id
  eventQuery.event_type = ''
  eventQuery.handled = ''
  eventQuery.page = 1
  alarmVisible.value = true
}

async function loadEvents() {
  try {
    const r = await apiGet('/admin/monitoring/surveillanceEventList', { ...eventQuery })
    eventList.value = r.data?.list || r.data || []
    eventTotal.value = r.count || r.data?.total || eventList.value.length
  } catch { eventList.value = []; eventTotal.value = 0 }
}

async function handleEvent(row: any) {
  try {
    await apiPost('/admin/monitoring/surveillanceHandleEvent', { id: row.id, remark: '已确认处理' })
    ElMessage.success('已标记为处理'); loadEvents(); loadStats()
  } catch {}
}

async function patrolAll() {
  patrolLoading.value = true
  try {
    const r = await apiPost('/admin/monitoring/surveillancePatrolAll')
    const results = r.data || []
    const ok = results.filter((x:any) => x.success).length
    ElMessage.success(`巡检完成: ${ok}/${results.length} 台正常`)
    loadData(); loadStats()
  } catch { ElMessage.error('巡检失败') }
  finally { patrolLoading.value = false }
}

async function handleDelete(row: any) {
  try {
    await ElMessageBox.confirm(`确定删除「${row.nvr_name}」的配置及关联摄像头吗？`, '提示', { type: 'warning' })
    await apiPost('/admin/monitoring/surveillanceConfigDelete', { id: row.id })
    ElMessage.success('删除成功'); loadData(); loadStats()
  } catch {}
}

function eventTypeName(type: string) {
  const map: Record<string,string> = { camera_offline:'摄像头离线', camera_online:'摄像头恢复', hdd_error:'硬盘故障', hdd_full:'硬盘已满', record_error:'录像异常', nvr_offline:'录像机离线' }
  return map[type] || type
}
function eventTypeTag(type: string) {
  const map: Record<string,string> = { camera_offline:'danger', camera_online:'success', hdd_error:'danger', hdd_full:'warning', record_error:'warning', nvr_offline:'danger' }
  return map[type] || 'info'
}

onMounted(async () => {
  try {
    const [cr, br] = await Promise.all([
      apiGet('/admin/community/list', { limit: 999 }),
      apiGet('/admin/monitoring/surveillanceBrands'),
    ])
    communities.value = cr.data?.list || cr.data || []
    brandMap.value = br.data || br || {}
  } catch {}
  loadData(); loadStats()
})
</script>

<style scoped>
.search-bar { background:#fff;border-radius:8px;padding:16px 20px;margin-bottom:16px;border:1px solid #e2e8f0; }
.table-card { border-radius:8px;border:1px solid #e2e8f0; }
.card-title { font-weight:600;font-size:15px; }
.pagination { margin-top:16px;display:flex;justify-content:flex-end; }
.form-tip { color:#999;font-size:12px; }
.stats-row { margin-bottom:16px; }
.stat-card { text-align:center;cursor:pointer;border-radius:8px; }
.stat-card .stat-value { font-size:32px;font-weight:700; }
.stat-card .stat-label { color:#888;font-size:13px;margin-top:4px; }
</style>
