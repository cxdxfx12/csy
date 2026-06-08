<template>
  <div class="iot-page">
    <div class="page-header">
      <div class="header-left">
        <h2 class="page-title"><el-icon :size="24"><Cpu /></el-icon> IoT 设备管理</h2>
        <p class="page-desc">数字孪生大屏设备 · {{ stats.total }}台在线/{{ stats.offline }}台离线</p>
      </div>
    </div>

    <!-- 统计卡片 -->
    <el-row :gutter="16" class="stats-row">
      <el-col :span="6"><el-card shadow="hover" class="stat-card stat-total"><div class="stat-inner">
        <div class="stat-icon"><el-icon :size="32"><Monitor /></el-icon></div>
        <div class="stat-info"><div class="stat-value">{{ stats.total }}</div><div class="stat-label">设备总数</div></div>
      </div></el-card></el-col>
      <el-col :span="6"><el-card shadow="hover" class="stat-card stat-online"><div class="stat-inner">
        <div class="stat-icon"><el-icon :size="32"><CircleCheckFilled /></el-icon></div>
        <div class="stat-info"><div class="stat-value">{{ stats.online }}</div><div class="stat-label">在线设备</div></div>
      </div></el-card></el-col>
      <el-col :span="6"><el-card shadow="hover" class="stat-card stat-offline"><div class="stat-inner">
        <div class="stat-icon"><el-icon :size="32"><WarningFilled /></el-icon></div>
        <div class="stat-info"><div class="stat-value">{{ stats.offline }}</div><div class="stat-label">离线/告警</div></div>
      </div></el-card></el-col>
      <el-col :span="6"><el-card shadow="hover" class="stat-card stat-type"><div class="stat-inner">
        <div class="stat-icon"><el-icon :size="32"><Grid /></el-icon></div>
        <div class="stat-info"><div class="stat-value">{{ stats.types }}</div><div class="stat-label">设备类型</div></div>
      </div></el-card></el-col>
    </el-row>

    <el-tabs v-model="activeTab" type="border-card" class="main-tabs">
      <!-- ===== 设备列表 ===== -->
      <el-tab-pane label="设备列表" name="devices">
        <el-card shadow="never" class="filter-card">
          <el-form :model="query" inline>
            <el-form-item label="所属小区">
              <el-select v-model="query.community_id" placeholder="全部小区" clearable style="width:160px" @change="loadDevices">
                <el-option v-for="c in communities" :key="c.id" :label="c.name" :value="c.id" />
              </el-select>
            </el-form-item>
            <el-form-item label="设备类型">
              <el-select v-model="query.type_id" placeholder="全部类型" clearable style="width:150px" @change="loadDevices">
                <el-option v-for="t in deviceTypes" :key="t.id" :label="t.name" :value="t.id" />
              </el-select>
            </el-form-item>
            <el-form-item label="状态">
              <el-select v-model="query.status" placeholder="全部" clearable style="width:100px" @change="loadDevices">
                <el-option label="在线" :value="1" /><el-option label="离线" :value="0" />
              </el-select>
            </el-form-item>
            <el-form-item>
              <el-input v-model="query.keyword" placeholder="设备名称/编号/位置" clearable style="width:220px" @keyup.enter="loadDevices" @clear="loadDevices">
                <template #prefix><el-icon><Search /></el-icon></template>
              </el-input>
            </el-form-item>
            <el-form-item>
              <el-button type="primary" @click="loadDevices"><el-icon><Search /></el-icon> 搜索</el-button>
              <el-button @click="resetQuery">重置</el-button>
            </el-form-item>
          </el-form>
        </el-card>
        <el-card shadow="never" class="table-card">
          <div style="margin-bottom:12px">
            <el-button type="primary" @click="openDeviceForm()"><el-icon><Plus /></el-icon> 添加设备</el-button>
          </div>
          <el-table :data="devices" v-loading="loading" stripe>
            <el-table-column type="index" label="序号" width="55" />
            <el-table-column prop="code" label="设备编号" width="130" />
            <el-table-column prop="name" label="设备名称" min-width="140" />
            <el-table-column prop="community_name" label="所属小区" width="110" />
            <el-table-column prop="type_name" label="设备类型" width="110">
              <template #default="{row}"><el-tag size="small" :type="typeTag(row.category)">{{ row.type_name }}</el-tag></template>
            </el-table-column>
            <el-table-column prop="protocol_name" label="通信协议" width="100" />
            <el-table-column label="3D坐标" width="130">
              <template #default="{row}">({{row.x}}, {{row.y}}, {{row.z}})</template>
            </el-table-column>
            <el-table-column prop="install_location" label="安装位置" min-width="120" show-overflow-tooltip />
            <el-table-column prop="building" label="楼栋" width="70" />
            <el-table-column prop="floor" label="楼层" width="70" />
            <el-table-column label="电量" width="75">
              <template #default="{row}">
                <el-progress :percentage="row.battery_level||0" :status="(row.battery_level||0)<20?'exception':''" :stroke-width="6" style="width:50px" />
              </template>
            </el-table-column>
            <el-table-column prop="firmware_ver" label="固件版本" width="90" />
            <el-table-column label="状态" width="75" align="center">
              <template #default="{row}"><el-tag :type="row.status==1?'success':'danger'" effect="dark" size="small">{{row.status==1?'在线':'离线'}}</el-tag></template>
            </el-table-column>
            <el-table-column prop="last_online" label="最后在线" width="160" />
            <el-table-column label="操作" width="140" fixed="right" align="center">
              <template #default="{row}">
                <el-button type="primary" plain size="small" @click="openDeviceForm(row)">编辑</el-button>
                <el-popconfirm title="确定删除？" @confirm="delDevice(row)"><template #reference><el-button type="danger" plain size="small">删除</el-button></template></el-popconfirm>
              </template>
            </el-table-column>
          </el-table>
          <div class="pagination-wrap"><el-pagination v-model:current-page="query.page" v-model:page-size="query.limit" :total="total" :page-sizes="[15,30,50]" layout="total,sizes,prev,pager,next,jumper" @change="loadDevices" /></div>
        </el-card>
      </el-tab-pane>

      <!-- ===== 设备类型 ===== -->
      <el-tab-pane label="设备类型" name="types">
        <el-card shadow="never" class="table-card">
          <div style="margin-bottom:12px"><el-button type="primary" @click="openTypeForm()"><el-icon><Plus /></el-icon> 添加类型</el-button></div>
          <el-table :data="types" stripe>
            <el-table-column prop="sort" label="排序" width="60" />
            <el-table-column prop="code" label="编码" width="120" />
            <el-table-column prop="name" label="名称" width="150" />
            <el-table-column prop="category" label="分类" width="100">
              <template #default="{row}"><el-tag size="small" :type="typeTag(row.category)">{{row.category}}</el-tag></template>
            </el-table-column>
            <el-table-column prop="unit" label="单位" width="70" />
            <el-table-column prop="y_height" label="Y轴高度" width="80" />
            <el-table-column prop="icon" label="图标编码" width="100" />
            <el-table-column label="状态" width="75"><template #default="{row}"><el-tag :type="row.status==1?'success':'info'" size="small">{{row.status==1?'启用':'禁用'}}</el-tag></template></el-table-column>
            <el-table-column prop="remark" label="备注" min-width="120" show-overflow-tooltip />
            <el-table-column label="操作" width="140" align="center">
              <template #default="{row}"><el-button type="primary" plain size="small" @click="openTypeForm(row)">编辑</el-button><el-popconfirm title="确定删除？" @confirm="delType(row)"><template #reference><el-button type="danger" plain size="small">删除</el-button></template></el-popconfirm></template>
            </el-table-column>
          </el-table>
        </el-card>
      </el-tab-pane>

      <!-- ===== 协议管理 ===== -->
      <el-tab-pane label="协议管理" name="protocols">
        <el-card shadow="never" class="table-card">
          <div style="margin-bottom:12px"><el-button type="primary" @click="openProtoForm()"><el-icon><Plus /></el-icon> 添加协议</el-button></div>
          <el-table :data="protocols" stripe>
            <el-table-column prop="sort" label="排序" width="60" />
            <el-table-column prop="code" label="编码" width="120" />
            <el-table-column prop="name" label="名称" width="160" />
            <el-table-column prop="version" label="版本" width="100" />
            <el-table-column prop="frequency" label="频段" width="120" />
            <el-table-column prop="range_m" label="传输距离" width="90" />
            <el-table-column prop="data_rate" label="速率" width="100" />
            <el-table-column label="状态" width="75"><template #default="{row}"><el-tag :type="row.status==1?'success':'info'" size="small">{{row.status==1?'启用':'禁用'}}</el-tag></template></el-table-column>
            <el-table-column prop="remark" label="备注" min-width="120" show-overflow-tooltip />
            <el-table-column label="操作" width="140" align="center">
              <template #default="{row}"><el-button type="primary" plain size="small" @click="openProtoForm(row)">编辑</el-button><el-popconfirm title="确定删除？" @confirm="delProto(row)"><template #reference><el-button type="danger" plain size="small">删除</el-button></template></el-popconfirm></template>
            </el-table-column>
          </el-table>
        </el-card>
      </el-tab-pane>

      <!-- ===== 实时数据 ===== -->
      <el-tab-pane label="实时数据" name="data">
        <el-card shadow="never" class="filter-card">
          <el-form :model="dataQuery" inline>
            <el-form-item label="设备筛选">
              <el-select v-model="dataQuery.device_id" filterable placeholder="全部设备" clearable style="width:220px" @change="loadData">
                <el-option v-for="d in allDevices" :key="d.id" :label="d.code+' '+d.name" :value="d.id" />
              </el-select>
            </el-form-item>
            <el-form-item label="时间">
              <el-date-picker v-model="dataQuery.date_range" type="daterange" range-separator="至" start-placeholder="开始" end-placeholder="结束" value-format="YYYY-MM-DD" style="width:250px" @change="loadData" />
            </el-form-item>
            <el-form-item><el-button type="primary" @click="loadData">查询</el-button></el-form-item>
          </el-form>
        </el-card>
        <el-card shadow="never" class="table-card">
          <el-table :data="dataList" v-loading="dataLoading" stripe>
            <el-table-column type="index" label="序号" width="55" />
            <el-table-column prop="device_code" label="设备编号" width="130" />
            <el-table-column prop="device_name" label="设备" min-width="130" />
            <el-table-column prop="type_name" label="类型" width="100" />
            <el-table-column prop="raw_value" label="数值" width="100" align="right" />
            <el-table-column prop="unit" label="单位" width="60" />
            <el-table-column label="在线" width="70" align="center"><template #default="{row}"><el-tag :type="row.is_online?'success':'danger'" size="small">{{row.is_online?'在线':'离线'}}</el-tag></template></el-table-column>
            <el-table-column prop="device_status" label="设备状态" width="90">
              <template #default="{row}"><el-tag size="small" :type="row.device_status=='normal'?'success':row.device_status=='alarm'?'danger':'warning'">{{row.device_status||'normal'}}</el-tag></template>
            </el-table-column>
            <el-table-column prop="alarm_msg" label="告警信息" min-width="150" show-overflow-tooltip />
            <el-table-column prop="data_time" label="数据时间" width="160" />
          </el-table>
          <div class="pagination-wrap"><el-pagination v-model:current-page="dataQuery.page" v-model:page-size="dataQuery.limit" :total="dataTotal" :page-sizes="[15,30,50]" layout="total,sizes,prev,pager,next,jumper" @change="loadData" /></div>
        </el-card>
      </el-tab-pane>
    </el-tabs>

    <!-- 设备编辑弹窗 -->
    <el-dialog v-model="devDialog" :title="devEditId?'编辑设备':'添加设备'" width="720px" destroy-on-close top="5vh">
      <el-form :model="devForm" ref="devFormRef" :rules="devRules" label-width="95px">
        <el-row :gutter="16">
          <el-col :span="12"><el-form-item label="所属小区" prop="community_id"><el-select v-model="devForm.community_id" placeholder="选择小区" style="width:100%"><el-option v-for="c in communities" :key="c.id" :label="c.name" :value="c.id" /></el-select></el-form-item></el-col>
          <el-col :span="12"><el-form-item label="设备类型" prop="device_type_id"><el-select v-model="devForm.device_type_id" placeholder="选择类型" style="width:100%"><el-option v-for="t in deviceTypes" :key="t.id" :label="t.name" :value="t.id" /></el-select></el-form-item></el-col>
          <el-col :span="12"><el-form-item label="设备编号" prop="code"><el-input v-model="devForm.code" placeholder="如 TEMP-01-001" /></el-form-item></el-col>
          <el-col :span="12"><el-form-item label="设备名称" prop="name"><el-input v-model="devForm.name" placeholder="如 1号楼烟感01" /></el-form-item></el-col>
          <el-col :span="12"><el-form-item label="通信协议" prop="protocol_id"><el-select v-model="devForm.protocol_id" placeholder="选择协议" style="width:100%"><el-option v-for="p in protocols" :key="p.id" :label="p.code+' - '+p.name" :value="p.id" /></el-select></el-form-item></el-col>
          <el-col :span="12"><el-form-item label="安装位置" prop="install_location"><el-input v-model="devForm.install_location" placeholder="如 1号楼1层东走廊" /></el-form-item></el-col>
          <el-col :span="8"><el-form-item label="楼栋"><el-input v-model="devForm.building" placeholder="如 A栋" /></el-form-item></el-col>
          <el-col :span="8"><el-form-item label="楼层"><el-input v-model="devForm.floor" placeholder="如 1F" /></el-form-item></el-col>
          <el-col :span="8"><el-form-item label="固件版本"><el-input v-model="devForm.firmware_ver" placeholder="如 v1.2.3" /></el-form-item></el-col>
          <el-col :span="8"><el-form-item label="X坐标"><el-input-number v-model="devForm.x" :precision="2" style="width:100%" /></el-form-item></el-col>
          <el-col :span="8"><el-form-item label="Y坐标"><el-input-number v-model="devForm.y" :precision="2" style="width:100%" /></el-form-item></el-col>
          <el-col :span="8"><el-form-item label="Z坐标"><el-input-number v-model="devForm.z" :precision="2" style="width:100%" /></el-form-item></el-col>
          <el-col :span="8"><el-form-item label="电量"><el-input-number v-model="devForm.battery_level" :min="0" :max="100" style="width:100%" /></el-form-item></el-col>
          <el-col :span="8"><el-form-item label="状态"><el-radio-group v-model="devForm.status"><el-radio :value="1">在线</el-radio><el-radio :value="0">离线</el-radio></el-radio-group></el-form-item></el-col>
          <el-col :span="8"><el-form-item label="安装日期"><el-date-picker v-model="devForm.install_date" type="date" value-format="YYYY-MM-DD" style="width:100%" /></el-form-item></el-col>
        </el-row>
      </el-form>
      <template #footer><el-button @click="devDialog=false">取消</el-button><el-button type="primary" :loading="submitting" @click="submitDevice">确定</el-button></template>
    </el-dialog>

    <!-- 类型编辑弹窗 -->
    <el-dialog v-model="typeDialog" :title="typeEditId?'编辑类型':'添加类型'" width="500px" destroy-on-close>
      <el-form :model="typeForm" ref="typeFormRef" :rules="typeRules" label-width="90px">
        <el-form-item label="名称" prop="name"><el-input v-model="typeForm.name" /></el-form-item>
        <el-form-item label="编码" prop="code"><el-input v-model="typeForm.code" /></el-form-item>
        <el-form-item label="分类"><el-input v-model="typeForm.category" placeholder="如 安全/能耗/环境" /></el-form-item>
        <el-form-item label="单位"><el-input v-model="typeForm.unit" placeholder="如 ℃/ppm/kWh" /></el-form-item>
        <el-form-item label="图标编码"><el-input v-model="typeForm.icon" placeholder="3D场景图标名" /></el-form-item>
        <el-form-item label="排序"><el-input-number v-model="typeForm.sort" :min="0" :max="999" /></el-form-item>
        <el-form-item label="Y轴高度"><el-input-number v-model="typeForm.y_height" :precision="2" /></el-form-item>
        <el-form-item label="备注"><el-input v-model="typeForm.remark" type="textarea" :rows="2" /></el-form-item>
        <el-form-item label="状态"><el-radio-group v-model="typeForm.status"><el-radio :value="1">启用</el-radio><el-radio :value="0">禁用</el-radio></el-radio-group></el-form-item>
      </el-form>
      <template #footer><el-button @click="typeDialog=false">取消</el-button><el-button type="primary" :loading="submitting" @click="submitType">确定</el-button></template>
    </el-dialog>

    <!-- 协议编辑弹窗 -->
    <el-dialog v-model="protoDialog" :title="protoEditId?'编辑协议':'添加协议'" width="500px" destroy-on-close>
      <el-form :model="protoForm" ref="protoFormRef" :rules="protoRules" label-width="90px">
        <el-form-item label="名称" prop="name"><el-input v-model="protoForm.name" /></el-form-item>
        <el-form-item label="编码" prop="code"><el-input v-model="protoForm.code" /></el-form-item>
        <el-form-item label="版本"><el-input v-model="protoForm.version" placeholder="如 3.1.1" /></el-form-item>
        <el-form-item label="频段"><el-input v-model="protoForm.frequency" placeholder="如 2.4GHz" /></el-form-item>
        <el-form-item label="传输距离"><el-input v-model="protoForm.range_m" placeholder="如 100m" /></el-form-item>
        <el-form-item label="速率"><el-input v-model="protoForm.data_rate" placeholder="如 250kbps" /></el-form-item>
        <el-form-item label="排序"><el-input-number v-model="protoForm.sort" :min="0" :max="999" /></el-form-item>
        <el-form-item label="备注"><el-input v-model="protoForm.remark" type="textarea" :rows="2" /></el-form-item>
        <el-form-item label="状态"><el-radio-group v-model="protoForm.status"><el-radio :value="1">启用</el-radio><el-radio :value="0">禁用</el-radio></el-radio-group></el-form-item>
      </el-form>
      <template #footer><el-button @click="protoDialog=false">取消</el-button><el-button type="primary" :loading="submitting" @click="submitProto">确定</el-button></template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { apiGet, apiPost } from '@/utils/request'
import { ElMessage } from 'element-plus'

const activeTab = ref('devices')
const loading = ref(false), dataLoading = ref(false), submitting = ref(false)
const total = ref(0), dataTotal = ref(0)
const devices = ref<any[]>([]), types = ref<any[]>([]), protocols = ref<any[]>([]), dataList = ref<any[]>([])
const deviceTypes = ref<any[]>([]), communities = ref<any[]>([]), allDevices = ref<any[]>([])
const devDialog = ref(false), typeDialog = ref(false), protoDialog = ref(false)
const devEditId = ref(0), typeEditId = ref(0), protoEditId = ref(0)
const devFormRef = ref(), typeFormRef = ref(), protoFormRef = ref()

const stats = reactive({ total: 0, online: 0, offline: 0, types: 0 })
const query = reactive({ page: 1, limit: 15, keyword: '', community_id: '', type_id: '', status: '' })
const dataQuery = reactive({ page: 1, limit: 15, device_id: '', date_range: [] as string[] })

const devForm = reactive<any>({ community_id: null, device_type_id: null, protocol_id: null, code: '', name: '', install_location: '', building: '', floor: '', x: 0, y: 0, z: 0, battery_level: 100, firmware_ver: '', install_date: '', status: 1 })
const typeForm = reactive<any>({ name: '', code: '', category: '', unit: '', icon: '', y_height: 0, sort: 99, remark: '', status: 1 })
const protoForm = reactive<any>({ name: '', code: '', version: '', frequency: '', range_m: '', data_rate: '', sort: 99, remark: '', status: 1 })

const devRules = { code: [{ required: true, message: '必填', trigger: 'blur' }], name: [{ required: true, message: '必填', trigger: 'blur' }], community_id: [{ required: true, message: '必选', trigger: 'change' }], device_type_id: [{ required: true, message: '必选', trigger: 'change' }] }
const typeRules = { name: [{ required: true, message: '必填', trigger: 'blur' }], code: [{ required: true, message: '必填', trigger: 'blur' }] }
const protoRules = { name: [{ required: true, message: '必填', trigger: 'blur' }], code: [{ required: true, message: '必填', trigger: 'blur' }] }

function typeTag(cat: string) { const m: Record<string,string> = { 安全:'danger', 能耗:'warning', 环境:'success', 安防:'danger', 消防:'danger', 能源:'warning', 传感器:'info', 控制器:'', 停车:'warning' }; return (m[cat]||'info') as any }

async function loadCommunities() { try { const r = await apiGet('/admin/iot/deviceList'); if (r?.code===0) {} } catch(_){} try { const r = await apiGet('/admin/community/listAll'); if (r?.code===0) communities.value = r.data||[] } catch(_){} }

async function loadStats() { try { const r = await apiGet('/admin/iot/stats'); if (r?.code===0) Object.assign(stats, r.data) } catch(_){} }

async function loadDevices() {
  loading.value = true
  try {
    const p: any = { page: query.page, limit: query.limit }
    if (query.keyword) p.keyword = query.keyword
    if (query.community_id) p.community_id = query.community_id
    if (query.type_id) p.type_id = query.type_id
    if (query.status !== '' && query.status !== null) p.status = query.status
    const r = await apiGet('/admin/iot/deviceList', p)
    if (r?.code===0) { devices.value = r.data.list||[]; total.value = r.data.total||0 }
  } finally { loading.value = false }
}

function resetQuery() { query.keyword=''; query.community_id=''; query.type_id=''; query.status=''; query.page=1; loadDevices() }

async function loadTypes() { try { const r = await apiGet('/admin/iot/typeAll'); if (r?.code===0) { types.value = r.data||[]; deviceTypes.value = r.data||[] } } catch(_){} }
async function loadProtocols() { try { const r = await apiGet('/admin/iot/protocolAll'); if (r?.code===0) protocols.value = r.data||[] } catch(_){} }
async function loadAllDevices() { try { const r = await apiGet('/admin/iot/deviceList', { page:1, limit:9999 }); if (r?.code===0) allDevices.value = r.data.list||[] } catch(_){} }

async function loadData() {
  dataLoading.value = true
  try {
    const p: any = { page: dataQuery.page, limit: dataQuery.limit }
    if (dataQuery.device_id) p.device_id = dataQuery.device_id
    if (dataQuery.date_range?.length===2) { p.date_from = dataQuery.date_range[0]; p.date_to = dataQuery.date_range[1] }
    const r = await apiGet('/admin/iot/dataList', p)
    if (r?.code===0) { dataList.value = r.data.list||[]; dataTotal.value = r.data.total||0 }
  } finally { dataLoading.value = false }
}

// 设备 CRUD
function openDeviceForm(row?: any) {
  if (row) { devEditId.value = row.id; Object.assign(devForm, row) }
  else { devEditId.value = 0; Object.assign(devForm, { community_id:null, device_type_id:null, protocol_id:null, code:'', name:'', install_location:'', building:'', floor:'', x:0, y:0, z:0, battery_level:100, firmware_ver:'', install_date:'', status:1 }) }
  devDialog.value = true
}
async function submitDevice() {
  const v = await devFormRef.value.validate().catch(()=>false); if (!v) return
  submitting.value = true
  try {
    const u = devEditId.value ? '/admin/iot/deviceEdit' : '/admin/iot/deviceAdd'
    const d = { ...devForm, id: devEditId.value||undefined }
    const r = await apiPost(u, d)
    if (r?.code===0) { ElMessage.success(r.msg); devDialog.value=false; loadDevices(); loadStats() }
  } finally { submitting.value = false }
}
async function delDevice(row: any) { const r = await apiPost('/admin/iot/deviceDelete', { id: row.id }); if (r?.code===0) { ElMessage.success('已删除'); loadDevices(); loadStats() } }

// 类型 CRUD
function openTypeForm(row?: any) {
  if (row) { typeEditId.value = row.id; Object.assign(typeForm, row) }
  else { typeEditId.value = 0; Object.assign(typeForm, { name:'', code:'', category:'', unit:'', icon:'', y_height:0, sort:99, remark:'', status:1 }) }
  typeDialog.value = true
}
async function submitType() {
  const v = await typeFormRef.value.validate().catch(()=>false); if (!v) return
  submitting.value = true
  try {
    const u = typeEditId.value ? '/admin/iot/typeEdit' : '/admin/iot/typeAdd'
    const r = await apiPost(u, { ...typeForm, id: typeEditId.value||undefined })
    if (r?.code===0) { ElMessage.success(r.msg); typeDialog.value=false; loadTypes() }
  } finally { submitting.value = false }
}
async function delType(row: any) { const r = await apiPost('/admin/iot/typeDelete', { id: row.id }); if (r?.code===0) { ElMessage.success('已删除'); loadTypes() } }

// 协议 CRUD
function openProtoForm(row?: any) {
  if (row) { protoEditId.value = row.id; Object.assign(protoForm, row) }
  else { protoEditId.value = 0; Object.assign(protoForm, { name:'', code:'', version:'', frequency:'', range_m:'', data_rate:'', sort:99, remark:'', status:1 }) }
  protoDialog.value = true
}
async function submitProto() {
  const v = await protoFormRef.value.validate().catch(()=>false); if (!v) return
  submitting.value = true
  try {
    const u = protoEditId.value ? '/admin/iot/protocolEdit' : '/admin/iot/protocolAdd'
    const r = await apiPost(u, { ...protoForm, id: protoEditId.value||undefined })
    if (r?.code===0) { ElMessage.success(r.msg); protoDialog.value=false; loadProtocols() }
  } finally { submitting.value = false }
}
async function delProto(row: any) { const r = await apiPost('/admin/iot/protocolDelete', { id: row.id }); if (r?.code===0) { ElMessage.success('已删除'); loadProtocols() } }

onMounted(() => { loadCommunities(); loadStats(); loadDevices(); loadTypes(); loadProtocols(); loadAllDevices() })
</script>

<style scoped>
.iot-page { padding: 16px 20px; background: #f5f7fa; min-height: 100%; }
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
.main-tabs { border-radius: 10px; }
.filter-card { margin-bottom: 16px; border-radius: 10px; }
.table-card { border-radius: 10px; }
.pagination-wrap { margin-top: 16px; display: flex; justify-content: flex-end; }
:deep(.el-table th) { background: #fafbfc; color: #303133; font-weight: 600; }
</style>
