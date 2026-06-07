<template>
  <div class="equip-page">
    <!-- 页面标题与数据概览 -->
    <div class="page-header">
      <div class="header-left">
        <h2 class="page-title">
          <el-icon :size="24"><Cpu /></el-icon>
          硬件设备管理
        </h2>
        <p class="page-desc">智能网关 · 门禁控制器 · 监控摄像头 · 道闸设备等IoT设备统一管理与协议控制</p>
      </div>
      <el-button type="primary" size="large" @click="openForm()">
        <el-icon><Plus /></el-icon> 添加设备
      </el-button>
    </div>

    <!-- 统计卡片 -->
    <el-row :gutter="16" class="stats-row">
      <el-col :span="6">
        <el-card shadow="hover" class="stat-card stat-total"><div class="stat-inner">
          <div class="stat-icon"><el-icon :size="32"><Monitor /></el-icon></div>
          <div class="stat-info"><div class="stat-value">{{ stats.total }}</div><div class="stat-label">设备总数</div></div>
        </div></el-card>
      </el-col>
      <el-col :span="6">
        <el-card shadow="hover" class="stat-card stat-online"><div class="stat-inner">
          <div class="stat-icon"><el-icon :size="32"><CircleCheckFilled /></el-icon></div>
          <div class="stat-info"><div class="stat-value">{{ stats.online }}</div><div class="stat-label">在线设备</div></div>
        </div></el-card>
      </el-col>
      <el-col :span="6">
        <el-card shadow="hover" class="stat-card stat-offline"><div class="stat-inner">
          <div class="stat-icon"><el-icon :size="32"><WarningFilled /></el-icon></div>
          <div class="stat-info"><div class="stat-value">{{ stats.offline }}</div><div class="stat-label">离线设备</div></div>
        </div></el-card>
      </el-col>
      <el-col :span="6">
        <el-card shadow="hover" class="stat-card stat-type"><div class="stat-inner">
          <div class="stat-icon"><el-icon :size="32"><Grid /></el-icon></div>
          <div class="stat-info"><div class="stat-value">{{ stats.types }}</div><div class="stat-label">设备类型</div></div>
        </div></el-card>
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
            <el-option v-for="t in deviceTypes" :key="t" :label="t" :value="t" />
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
          <el-button type="success" @click="batchTestConnection" :loading="testingAll" :disabled="list.length===0"><el-icon><Connection /> </el-icon> 全部测试连接</el-button>
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
            <el-tag :type="deviceTypeTag(row.device_type)" effect="light" size="small">{{ row.device_type }}</el-tag>
            <template v-if="row.ref_type">
              <el-tag type="info" effect="plain" size="small" style="margin-left:4px">{{ refLabel(row.ref_type) }}#{{ row.ref_id }}</el-tag>
            </template>
          </template>
        </el-table-column>
        <el-table-column prop="manufacturer" label="品牌/型号" width="130">
          <template #default="{ row }">
            {{ row.manufacturer || '-' }}
            <template v-if="row.model"><br/><span class="text-muted">{{ row.model }}</span></template>
          </template>
        </el-table-column>
        <el-table-column prop="protocol" label="协议" width="90" />
        <el-table-column label="网络" width="150">
          <template #default="{ row }">
            <span v-if="row.ip_address">{{ row.ip_address }}{{ row.port ? ':'+row.port : '' }}</span>
            <span v-else class="text-muted">-</span>
          </template>
        </el-table-column>
        <el-table-column prop="location" label="安装位置" min-width="120" show-overflow-tooltip />
        <el-table-column label="状态" width="90" align="center">
          <template #default="{ row }">
            <el-tag :type="row.status == 1 ? 'success' : 'danger'" effect="dark" size="small">
              {{ row.status == 1 ? '在线' : '离线' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="last_heartbeat" label="最后心跳" width="160" />
        <el-table-column label="操作" width="200" fixed="right" align="center">
          <template #default="{ row }">
            <div class="action-btns">
              <el-dropdown trigger="click" size="small" @command="(cmd) => handleRemoteAction(cmd, row)">
                <el-button type="success" plain size="small" :loading="actionLoading[`${row.id}`]">
                  操作<el-icon class="el-icon--right"><ArrowDown /></el-icon>
                </el-button>
                <template #dropdown>
                  <el-dropdown-menu>
                    <el-dropdown-item command="test_connection">
                      <el-icon><Connection /></el-icon> 测试连接
                    </el-dropdown-item>
                    <el-dropdown-item command="get_status">
                      <el-icon><Monitor /></el-icon> 状态查询
                    </el-dropdown-item>
                    <el-divider v-if="(actionsCache[row.device_type] || []).length > 0" style="margin:4px 0"/>
                    <template v-for="act in (actionsCache[row.device_type] || [])" :key="act.key">
                      <el-dropdown-item :command="'remote:'+act.key"
                        :class="{ 'is-danger': act.danger }">
                        <el-icon><component :is="act.icon || 'Setting'" /></el-icon>
                        {{ act.name }}
                        <el-tag v-if="act.need_param" size="small" type="warning" style="margin-left:4px">需参数</el-tag>
                      </el-dropdown-item>
                    </template>
                  </el-dropdown-menu>
                </template>
              </el-dropdown>
              <el-button type="primary" plain size="small" @click="openForm(row)">编辑</el-button>
              <el-popconfirm title="确定删除该设备？" @confirm="handleDelete(row)" confirm-button-text="确定" cancel-button-text="取消">
                <template #reference>
                  <el-button type="danger" plain size="small">删除</el-button>
                </template>
              </el-popconfirm>
            </div>
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
    <el-dialog v-model="dialogVisible" :title="editId ? '编辑设备' : '添加设备'" width="720px" destroy-on-close top="5vh">
      <el-form :model="form" ref="formRef" :rules="rules" label-width="95px" label-position="right">
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
              <el-select v-model="form.device_type" placeholder="选择设备类型" style="width:100%" @change="onTypeChange">
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
              <el-select v-model="form.protocol" placeholder="选择协议" filterable allow-create clearable style="width:100%">
                <el-option v-for="p in currentProtocols" :key="p" :label="p.toUpperCase()" :value="p" />
              </el-select>
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
            <el-form-item label="认证密钥" prop="auth_key">
              <el-input v-model="form.auth_key" placeholder="Token/API Key (选填)" maxlength="100" show-password />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="序列号" prop="serial_no">
              <el-input v-model="form.serial_no" placeholder="设备出厂序列号" maxlength="100" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="关联业务" prop="ref_type">
              <el-cascader
                v-model="refCascade"
                :options="businessRefOptions"
                placeholder="可选：关联道闸/门禁等"
                clearable
                style="width:100%"
                :props="{ value:'value', label:'label', children:'children' }"
                @change="onRefChange"
              />
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

    <!-- 操作结果弹窗 -->
    <el-dialog v-model="resultDialogVisible" title="操作结果" width="500px">
      <el-descriptions :column="2" border>
        <el-descriptions-item label="操作">{{ resultInfo.action }}</el-descriptions-item>
        <el-descriptions-item label="耗时">{{ resultInfo.latency_ms ?? '-' }} ms</el-descriptions-item>
        <el-descriptions-item label="状态">
          <el-tag :type="resultInfo.success ? 'success' : 'danger'">{{ resultInfo.success ? '成功' : '失败' }}</el-tag>
        </el-descriptions-item>
        <el-descriptions-item label="消息" :span="2">{{ resultInfo.message }}</el-descriptions-item>
        <el-descriptions-item v-for="(v,k) in (resultInfo.data||{})" :key="k" :label="k" :span="1">{{ v }}</el-descriptions-item>
      </el-descriptions>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import { apiGet, apiPost } from '@/utils/request'
import { ElMessage, ElMessageBox } from 'element-plus'
import {
  Cpu, Plus, Monitor, CircleCheckFilled, WarningFilled, Grid, Search,
  Connection, Operation, ArrowDown, ArrowUp, Bottom, Back, Right, Top,
  ZoomIn, ZoomOut, Camera, RefreshRight, Delete as IconDelete,
  Unlock, Lock, UserFilled, Document, DataLine, Aim, Setting, Clock,
  Warning, View, VideoPlay, VideoPause, Odometer, Open, Close,
  SwitchButton, Mute, PhoneFilled, Message as MsgIcon, List
} from '@element-plus/icons-vue'

const list = ref<any[]>([])
const total = ref(0)
const loading = ref(false)
const submitting = ref(false)
const testingAll = ref(false)
const dialogVisible = ref(false)
const editId = ref(0)
const formRef = ref()
const communities = ref<any[]>([])
const actionLoading = ref<Record<string, boolean>>({})

// 远程操作结果
const resultDialogVisible = ref(false)
const resultInfo = ref<any>({})

// 关联业务级联
const refCascade = ref<(string|number)[]>([])

const query = reactive({ page: 1, limit: 15, keyword: '', community_id: 0, device_type: '', status: '' })
const form = reactive<any>({
  community_id: null, device_type: '', device_code: '', device_name: '', manufacturer: '',
  model: '', protocol: '', ip_address: '', port: 0, serial_no: '', auth_key: '',
  location: '', status: 1, remark: '',
  ref_type: '', ref_id: 0,
})

const deviceTypes = ['智能网关', '门禁控制器', '监控摄像头', '环境传感器', '消防设备', '道闸设备', '充电桩', '水表采集器', '电表采集器']

// 协议映射（根据设备类型动态显示）
const protocolMap: Record<string, string[]> = {
  '智能网关':     ['mqtt', 'http', 'tcp', 'coap'],
  '门禁控制器':   ['http', 'tcp', 'modbus', 'websocket'],
  '监控摄像头':   ['rtsp', 'onvif', 'sdk', 'gb28181'],
  '环境传感器':   ['mqtt', 'modbus', 'zigbee', 'lorawan'],
  '消防设备':     ['modbus', 'snmp', 'mqtt', 'http'],
  '道闸设备':     ['http', 'tcp', 'modbus', 'sdk'],
  '充电桩':       ['ocpp', 'modbus', 'http', 'tcp'],
  '水表采集器':   ['modbus', 'nb-iot', 'lora', 'mqtt'],
  '电表采集器':   ['modbus', 'dlms', 'mqtt', 'http'],
}

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

// 当前设备类型对应的协议列表
const currentProtocols = computed(() => {
  if (!form.device_type) return []
  // 也支持用户手动输入的协议名（显示在选项中供参考）
  const protocols = [...(protocolMap[form.device_type] || [])]
  if (protocols.length && !protocols.includes('http')) protocols.push('http')
  return protocols
})

// 业务关联选项（级联）
const businessRefOptions = computed(() => {
  return [
    {
      value: 'gate',
      label: '道闸设备',
      children: [
        { value: 'auto', label: '自动匹配(SN)' },
        { value: 'manual', label: '手动指定...' }
      ]
    },
    {
      value: 'access',
      label: '门禁设备',
      children: [
        { value: 'auto', label: '自动匹配(SN)' },
        { value: 'manual', label: '手动指定...' }
      ]
    },
    {
      value: 'camera',
      label: '监控通道',
      children: [
        { value: 'manual', label: '手动指定...' }
      ]
    }
  ]
})

function deviceTypeTag(type: string) {
  const map: Record<string, string> = {
    '智能网关':'','门禁控制器':'warning','监控摄像头':'info','环境传感器':'success',
    '消防设备':'danger','道闸设备':'','充电桩':'success','水表采集器':'info','电表采集器':'warning'
  }
  return map[type] || ''
}

function refLabel(t: string): string {
  const m: Record<string,string> = { gate:'道闸', access:'门禁', camera:'监控', elevator:'电梯' }
  return m[t] || t
}

// 获取某设备的可用远程操作按钮列表（同步缓存，onMounted 预加载）
const actionsCache = reactive<Record<string, any[]>>({})
async function loadActionsCache() {
  try {
    const res = await apiGet('/admin/equipment/deviceActions')
    if (res?.code === 0) {
      for (const [key, info] of Object.entries(res.data)) {
        if ((info as any)?.actions) actionsCache[key] = (info as any).actions
      }
    }
  } catch(_) {}
}
async function getActions(row: any): Promise<any[]> {
  if (!row.device_type) return []
  if (actionsCache[row.device_type]) return actionsCache[row.device_type]
  // 按需加载单个类型
  try {
    const res = await apiGet('/admin/equipment/deviceActions', { params: { type: row.device_type } })
    if (res?.code === 0 && res.data?.actions) {
      actionsCache[row.device_type] = res.data.actions
      return res.data.actions
    }
  } catch(_) {}
  return []
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
    Object.assign(form, {
      community_id: row.community_id, device_type: row.device_type, device_code: row.device_code,
      device_name: row.device_name, manufacturer: row.manufacturer, model: row.model,
      protocol: row.protocol, ip_address: row.ip_address, port: row.port || 0,
      serial_no: row.serial_no, location: row.location, status: row.status ?? 1,
      remark: row.remark || '', auth_key: row.auth_key || '',
      ref_type: row.ref_type || '', ref_id: row.ref_id || 0,
    })
    // 设置级联值
    if (row.ref_type && row.ref_id) {
      refCascade.value = [row.ref_type, 'manual']
    } else {
      refCascade.value = []
    }
  } else {
    editId.value = 0
    Object.assign(form, { community_id: null, device_type: '', device_code: '', device_name: '',
      manufacturer: '', model: '', protocol: '', ip_address: '', port: 0, serial_no: '',
      auth_key: '', location: '', status: 1, remark: '', ref_type: '', ref_id: 0 })
    refCascade.value = []
  }
  dialogVisible.value = true
}

function onTypeChange(val: string) {
  // 切换类型时自动推荐协议
  const protos = protocolMap[val]
  if (protos?.length === 1) form.protocol = protos[0]
  else if (protos?.length > 1 && !protos.includes(form.protocol)) form.protocol = ''
}

function onRefChange(val: (string|number)[]) {
  if (val && val.length >= 1) {
    form.ref_type = val[0] as string
    form.ref_id = val[1] === 'auto' ? -1 : (val[1] === 'manual' ? 0 : Number(val[1]))
  } else {
    form.ref_type = ''
    form.ref_id = 0
  }
}

async function handleSubmit() {
  const valid = await formRef.value.validate().catch(() => false)
  if (!valid) return
  submitting.value = true
  try {
    const url = editId.value ? '/admin/equipment/deviceEdit' : '/admin/equipment/deviceAdd'
    const submitData = { ...form, id: editId.value || undefined }
    delete submitData.ref_type  // 用 refCascade 提交更清晰
    if (refCascade.value.length >= 1) submitData.ref_type = String(refCascade.value[0])
    if (refCascade.value.length >= 2 && refCascade.value[1] !== 'auto') submitData.ref_id = Number(refCascade.value[1])
    const res = await apiPost(url, submitData)
    if (res.code === 0) { ElMessage.success(res.msg); dialogVisible.value = false; loadData() }
  } finally { submitting.value = false }
}

async function handleDelete(row: any) {
  const res = await apiPost('/admin/equipment/deviceDelete', { id: row.id })
  if (res.code === 0) { ElMessage.success('删除成功'); loadData() }
}

// ---- 协议操作 ----

async function handleRemoteAction(cmd: string, row: any) {
  const [cmdType, ...rest] = cmd.split(':')
  const actionKey = rest.join(':')

  actionLoading.value[`${row.id}`] = true
  try {
    let res: any
    switch (cmdType) {
      case 'test_connection':
        res = await apiPost('/admin/equipment/deviceTestConnection', { id: row.id })
        break
      case 'get_status':
        res = await apiGet('/admin/equipment/deviceGetStatus', { params: { id: row.id } })
        break
      case 'remote':
        // 需要参数的操作弹出输入框
        if (await needsParam(actionKey, row)) {
          const paramVal = await promptForAction(actionKey, row)
          if (paramVal === null) break
          res = await apiPost('/admin/equipment/deviceRemoteAction', { id: row.id, action: actionKey, params: paramVal })
        } else {
          res = await apiPost('/admin/equipment/deviceRemoteAction', { id: row.id, action: actionKey, params: {} })
        }
        break
      default:
        return
    }

    resultInfo.value = res?.data || { success: !!res?.code, message: res?.msg || '未知结果' }
    resultInfo.value.action = getActionName(cmdType, actionKey)

    if (res?.code !== 0 && cmdType !== 'test_connection') {
      ElMessage.warning(res?.msg || '操作返回异常')
    }

    // 测试成功时刷新状态
    if (cmdType === 'test_connection' && resultInfo.value.success) {
      row.last_heartbeat = new Date().toLocaleString()
      row.status = 1
    }

    resultDialogVisible.value = true
  } catch(e: any) {
    ElMessage.error(e?.message || '操作失败')
  } finally {
    actionLoading.value[`${row.id}`] = false
  }
}

async function batchTestConnection() {
  testingAll.value = true
  let ok = 0, fail = 0
  for (const row of list.value) {
    actionLoading.value[`${row.id}`] = true
    try {
      const res = await apiPost('/admin/equipment/deviceTestConnection', { id: row.id })
      if (res?.data?.success) { ok++; row.status = 1; row.last_heartbeat = new Date().toLocaleString(); }
      else fail++
    } catch(_) { fail++ }
    actionLoading.value[`${row.id}`] = false
  }
  testingAll.value = false
  ElMessage.success(`批量测试完成: ${ok} 在线, ${fail} 异常`)
}

async function needsParam(actionKey: string, row: any): Promise<boolean> {
  const acts = await getActions(row)
  return acts.some(a => a.key === actionKey && a.need_param)
}

async function promptForAction(actionKey: string, row: any): Promise<any | null> {
  const acts = await getActions(row)
  const act = acts.find(a => a.key === actionKey)
  if (!act) return {}

  if (act.param_label && !act.param_form) {
    const { value } = await ElMessageBox.prompt(`请输入「${act.name}」参数:`, '操作参数', {
      confirmButtonText: '执行', cancelButtonText: '取消', inputValue: '',
      inputPlaceholder: `请输入 ${act.param_label}`,
    }).catch(() => ({ value: null }))
    if (value === null) return null
    return { value }
  }

  if (act.param_options) {
    const options = Object.entries(act.param_options).map(([v,l]) => ({ value: v, label: l }))
    const idx = await ElMessageBox.prompt(
      `请选择「${act.name}」模式:`, '操作参数',
      {
        confirmButtonText: '执行', cancelButtonText: '取消',
        inputType: undefined,
        inputValidator(v) { return !v ? '必填' : true },
      }
    ).catch(() => ({ value: null }))
    // 对于 select 类型用自定义方式
    const mode = act.param_options[Object.keys(act.param_options)[0]] || 'normal'
    return { mode }
  }

  if (act.param_form) {
    // 多字段表单 - 这里简化为提示输入第一个字段
    const firstField = Object.keys(act.param_form)[0]
    const { value } = await ElMessageBox.prompt(`${act.name}: ${act.param_form[firstField]}`, '参数', {
      confirmButtonText: '确认', cancelButtonText: '取消',
    }).catch(() => ({ value: null }))
    if (value === null) return null
    return { [firstField]: parseFloat(value) || value }
  }

  return {}
}

function getActionName(cmdType: string, key: string): string {
  const names: Record<string, string> = {
    test_connection: '测试连接', get_status: '状态查询',
    open: '远程开门', lock: '锁定', unlock: '解锁', sync_whitelist: '同步白名单',
    reboot: '重启设备', get_log: '获取日志',
    ptz_up: '云台上移', ptz_down: '云台下移', ptz_left: '云台左移', ptz_right: '云台右移',
    zoom_in: '放大', zoom_out: '缩小', snapshot: '抓拍', get_stream_url: '获取流地址',
    read_data: '读取数据', calibrate: '校准', set_threshold: '设置阈值', get_history: '历史数据',
    reset_alarm: '报警复位', test_device: '自检', silence: '消音',
      open_in: '开闸(入场)', open_out: '开闸(出场)', stop: '停止', reset: '复位',
    start: '开始充电', stop_charging: '停止充电', unlock_charger: '解锁枪头',
    get_meter_value: '读计量', set_price: '设电价',
    read_meter: '读用量', open_valve: '开水阀', close_valve: '关水阀',
    breaker_on: '合闸', breaker_off: '跳闸',
    call_floor: '召唤楼层', open_door: '开门', close_door: '关门', set_mode: '切换模式', emergency_stop: '急停',
    call: '呼叫', send_message: '发送消息',
    capture: '抓拍', sync_faces: '同步人脸库',
  }
  return names[key] || names[cmdType] || `${cmdType}:${key}`
}

onMounted(() => { loadActionsCache(); loadCommunities(); loadData() })
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
::deep(.el-table th) { background: #fafbfc; color: #303133; font-weight: 600; }
.text-muted { color: #c0c4cc; font-size: 12px; }
.is-danger { color: var(--el-color-danger)!important; }
.action-btns { display: flex; align-items: center; justify-content: center; gap: 6px; flex-wrap: nowrap; }
</style>
