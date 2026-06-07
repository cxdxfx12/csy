<template>
  <div class="page-container">
    <!-- 统计卡片 -->
    <div class="stats-row">
      <div class="stat-item"><span class="stat-num">{{ stats.total_events || 0 }}</span><span class="stat-label">今日通行</span></div>
      <div class="stat-item in"><span class="stat-num">{{ stats.in_count || 0 }}</span><span class="stat-label">入场</span></div>
      <div class="stat-item out"><span class="stat-num">{{ stats.out_count || 0 }}</span><span class="stat-label">出场</span></div>
      <div class="stat-item"><span class="stat-num">{{ stats.device_online || 0 }}/{{ stats.device_total || 0 }}</span><span class="stat-label">设备在线</span></div>
    </div>

    <div class="search-bar">
      <el-form :model="query" inline>
        <el-form-item label="小区">
          <el-select v-model="query.community_id" placeholder="全部小区" clearable style="width:180px">
            <el-option v-for="c in communities" :key="c.id" :label="c.name" :value="c.id" />
          </el-select>
        </el-form-item>
        <el-form-item label="品牌">
          <el-select v-model="query.brand" placeholder="全部品牌" clearable style="width:140px">
            <el-option v-for="(name,key) in brandMap" :key="key" :label="name" :value="key" />
          </el-select>
        </el-form-item>
        <el-form-item label="状态">
          <el-select v-model="query.online" placeholder="全部" clearable style="width:100px">
            <el-option label="在线" :value="1" /><el-option label="离线" :value="0" />
          </el-select>
        </el-form-item>
        <el-form-item label="关键词">
          <el-input v-model="query.keyword" placeholder="名称/序列号" clearable style="width:180px" />
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="loadData">搜索</el-button>
          <el-button @click="resetQuery">重置</el-button>
          <el-button type="success" @click="openAdd">添加设备</el-button>
        </el-form-item>
      </el-form>
    </div>

    <el-card shadow="never" class="table-card">
      <el-table :data="list" v-loading="loading" stripe border @row-click="showEvents">
        <el-table-column type="index" label="#" width="50" />
        <el-table-column prop="id" label="ID" width="60" />
        <el-table-column label="状态" width="70">
          <template #default="{row}">
            <el-tag :type="row.online===1 ? 'success' : 'info'" size="small" effect="dark">
              {{ row.online === 1 ? '在线' : '离线' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="device_name" label="设备名称" width="160" />
        <el-table-column prop="community_name" label="小区" width="120" />
        <el-table-column prop="entrance_name" label="出入口" width="140" />
        <el-table-column label="品牌" width="100">
          <template #default="{row}"><el-tag effect="plain" size="small">{{ brandMap[row.brand] || row.brand }}</el-tag></template>
        </el-table-column>
        <el-table-column prop="device_sn" label="序列号" width="140" />
        <el-table-column prop="ip_address" label="IP地址" width="140" />
        <el-table-column prop="last_heartbeat" label="最后心跳" width="170" />
        <el-table-column label="操作" width="160" fixed="right">
          <template #default="{row}">
            <el-button size="small" @click.stop="openEdit(row)">编辑</el-button>
            <el-button size="small" type="danger" @click.stop="handleDelete(row)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>
      <div class="pagination">
        <el-pagination v-model:current-page="query.page" v-model:page-size="query.limit" :total="total" :page-sizes="[15,30,50]" layout="total,sizes,prev,pager,next" @current-change="loadData" @size-change="loadData" />
      </div>
    </el-card>

    <!-- 通行事件弹窗 -->
    <el-dialog v-model="eventVisible" title="通行事件（最近50条）" width="800px" destroy-on-close>
      <el-table :data="events" stripe border max-height="400">
        <el-table-column type="index" label="#" width="50" />
        <el-table-column prop="plate_number" label="车牌号" width="120" />
        <el-table-column label="方向" width="70">
          <template #default="{r}"><el-tag :type="r.direction==='in'?'success':'warning'" size="small">{{ r.direction==='in'?'入场':'出场' }}</el-tag></template>
        </el-table-column>
        <el-table-column label="动作" width="80">
          <template #default="{r}"><el-tag :type="r.action==='pass'?'success':r.action==='deny'?'danger':'info'" size="small">{{ r.action==='pass'?'放行':r.action==='deny'?'拒绝':r.action }}</el-tag></template>
        </el-table-column>
        <el-table-column prop="recognize_time" label="识别时间" width="170" />
      </el-table>
    </el-dialog>

    <!-- 添加/编辑弹窗 -->
    <el-dialog v-model="formVisible" :title="formTitle" width="500px" destroy-on-close>
      <el-form :model="form" label-width="100px">
        <el-form-item label="小区" required>
          <el-select v-model="form.community_id" style="width:100%">
            <el-option v-for="c in communities" :key="c.id" :label="c.name" :value="c.id" />
          </el-select>
        </el-form-item>
        <el-form-item label="出入口名称">
          <el-input v-model="form.entrance_name" placeholder="如：南门入口" />
        </el-form-item>
        <el-form-item label="设备名称" required>
          <el-input v-model="form.device_name" placeholder="如：1号道闸" />
        </el-form-item>
        <el-form-item label="品牌">
          <el-select v-model="form.brand" style="width:100%">
            <el-option v-for="(name,key) in brandMap" :key="key" :label="name" :value="key" />
          </el-select>
        </el-form-item>
        <el-form-item label="序列号">
          <el-input v-model="form.device_sn" placeholder="设备唯一编号" />
        </el-form-item>
        <el-form-item label="IP地址">
          <el-input v-model="form.ip_address" placeholder="192.168.1.100" />
        </el-form-item>
        <el-form-item label="端口">
          <el-input-number v-model="form.port" :min="1" :max="65535" />
        </el-form-item>
        <el-form-item label="备注"><el-input v-model="form.remark" type="textarea" rows="2" /></el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="formVisible=false">取消</el-button>
        <el-button type="primary" @click="submitForm" :loading="submitting">确定</el-button>
      </template>
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
const eventVisible = ref(false)
const submitting = ref(false)
const isEdit = ref(false)
const editId = ref(0)
const communities = ref<any[]>([])
const brandMap = ref<Record<string,string>>({})
const events = ref<any[]>([])
const evtDeviceId = ref(0)
const stats = ref<Record<string,number>>({})

const form = reactive({ community_id:undefined as any, entrance_name:'', device_name:'', brand:'generic', device_sn:'', ip_address:'', port:80, remark:'' })
const query = reactive({ community_id:undefined as any, brand:'', online:'', keyword:'', page:1, limit:15 })
const formTitle = ref('添加设备')

function resetQuery() { query.community_id=undefined; query.brand=''; query.online=''; query.keyword=''; query.page=1; loadData() }

async function loadData() {
  loading.value = true
  try {
    const r = await apiGet('/admin/parking/gateDeviceList', { ...query })
    list.value = r.data?.list || r.data || []
    total.value = r.count || r.data?.total || list.value.length
  } catch { list.value = []; total.value = 0 }
  finally { loading.value = false }
}

async function loadStats() {
  try {
    const r = await apiGet('/admin/parking/gateDeviceTodayStats', { community_id: query.community_id || 0 })
    stats.value = r.data || r || {}
  } catch {}
}

function openAdd() {
  isEdit.value = false; editId.value = 0
  Object.assign(form, { community_id:undefined, entrance_name:'', device_name:'', brand:'generic', device_sn:'', ip_address:'', port:80, remark:'' })
  formTitle.value = '添加设备'; formVisible.value = true
}

function openEdit(row: any) {
  isEdit.value = true; editId.value = row.id
  Object.assign(form, { community_id:row.community_id, entrance_name:row.entrance_name||'', device_name:row.device_name, brand:row.brand||'generic', device_sn:row.device_sn||'', ip_address:row.ip_address||'', port:row.port||80, remark:row.remark||'' })
  formTitle.value = '编辑设备'; formVisible.value = true
}

async function submitForm() {
  if (!form.community_id) { ElMessage.warning('请选择小区'); return }
  if (!form.device_name) { ElMessage.warning('请输入设备名称'); return }
  submitting.value = true
  try {
    const url = isEdit.value ? '/admin/parking/gateDeviceEdit' : '/admin/parking/gateDeviceAdd'
    const payload: any = { ...form }
    if (isEdit.value) payload.id = editId.value
    await apiPost(url, payload)
    ElMessage.success(isEdit.value ? '更新成功' : '添加成功')
    formVisible.value = false; loadData(); loadStats()
  } finally { submitting.value = false }
}

async function showEvents(row: any) {
  evtDeviceId.value = row.id
  try {
    const r = await apiGet('/admin/parking/gateDeviceEvents', { device_id: row.id, community_id: row.community_id })
    events.value = r.data || r || []
    eventVisible.value = true
  } catch {}
}

async function handleDelete(row: any) {
  try {
    await ElMessageBox.confirm(`确定删除设备「${row.device_name}」吗？`, '提示', { type: 'warning' })
    await apiPost('/admin/parking/gateDeviceDelete', { id: row.id })
    ElMessage.success('删除成功'); loadData(); loadStats()
  } catch {}
}

onMounted(async () => {
  try {
    const [cr, br] = await Promise.all([
      apiGet('/admin/community/list', { limit: 999 }),
      apiGet('/admin/parking/gateConfigBrands'),
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
.pagination { margin-top:16px;display:flex;justify-content:flex-end; }
.stats-row { display:flex;gap:16px;margin-bottom:16px; }
.stat-item { flex:1;background:#fff;border-radius:8px;padding:20px;text-align:center;border:1px solid #e2e8f0; }
.stat-item.in { border-left:3px solid #67c23a; }
.stat-item.out { border-left:3px solid #e6a23c; }
.stat-num { display:block;font-size:28px;font-weight:700;color:#1e3a5f; }
.stat-label { display:block;font-size:13px;color:#8b9eb0;margin-top:4px; }
</style>
