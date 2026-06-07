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
            <el-option v-for="(name,key) in brandMap" :key="key" :label="name" :value="key" />
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
          <el-button type="success" @click="openAdd">添加配置</el-button>
        </el-form-item>
      </el-form>
    </div>

    <el-card shadow="never" class="table-card">
      <el-table :data="list" v-loading="loading" stripe border>
        <el-table-column type="index" label="#" width="50" />
        <el-table-column prop="id" label="ID" width="60" />
        <el-table-column prop="community_name" label="小区" width="120" />
        <el-table-column prop="entrance_name" label="出入口" width="140" />
        <el-table-column label="品牌" width="110">
          <template #default="{row}"><el-tag effect="plain" type="primary">{{ brandMap[row.brand] || row.brand }}</el-tag></template>
        </el-table-column>
        <el-table-column label="方向" width="70">
          <template #default="{row}"><el-tag :type="row.direction==='in'?'success':'warning'" size="small">{{ row.direction==='in'?'入口':'出口' }}</el-tag></template>
        </el-table-column>
        <el-table-column prop="api_url" label="接口地址" min-width="200" show-overflow-tooltip />
        <el-table-column prop="device_sn" label="设备编号" width="120" />
        <el-table-column label="状态" width="70">
          <template #default="{row}"><el-switch :model-value="row.enabled===1" @change="toggleEnable(row)" /></template>
        </el-table-column>
        <el-table-column label="操作" width="280" fixed="right">
          <template #default="{row}">
            <el-button size="small" @click="openEdit(row)">编辑</el-button>
            <el-button size="small" type="success" @click="testConn(row)">测试</el-button>
            <el-button size="small" type="warning" @click="remoteOpen(row)">开闸</el-button>
            <el-button size="small" type="primary" @click="syncWhite(row)">同步白名单</el-button>
            <el-button size="small" type="danger" @click="handleDelete(row)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>
      <div class="pagination">
        <el-pagination v-model:current-page="query.page" v-model:page-size="query.limit" :total="total" :page-sizes="[15,30,50]" layout="total,sizes,prev,pager,next" @current-change="loadData" @size-change="loadData" />
      </div>
    </el-card>

    <!-- 添加/编辑弹窗 -->
    <el-dialog v-model="formVisible" :title="formTitle" width="560px" destroy-on-close>
      <el-form :model="form" label-width="100px">
        <el-form-item label="小区" required>
          <el-select v-model="form.community_id" placeholder="请选择小区" style="width:100%" :disabled="isEdit">
            <el-option v-for="c in communities" :key="c.id" :label="c.name" :value="c.id" />
          </el-select>
        </el-form-item>
        <el-form-item label="出入口名称" required>
          <el-input v-model="form.entrance_name" placeholder="如：南门入口、北门出口" />
        </el-form-item>
        <el-form-item label="道闸品牌" required>
          <el-select v-model="form.brand" placeholder="请选择品牌" style="width:100%">
            <el-option v-for="(name,key) in brandMap" :key="key" :label="name" :value="key" />
          </el-select>
        </el-form-item>
        <el-form-item label="方向" required>
          <el-radio-group v-model="form.direction">
            <el-radio value="in">入口 (in)</el-radio>
            <el-radio value="out">出口 (out)</el-radio>
          </el-radio-group>
        </el-form-item>
        <el-divider content-position="left">接口参数</el-divider>
        <el-form-item label="接口地址">
          <el-input v-model="form.api_url" placeholder="如 http://192.168.1.100:8080" />
          <div class="form-tip">不同品牌接口地址格式不同，请参照各品牌文档填写</div>
        </el-form-item>
        <el-form-item label="接口密钥">
          <el-input v-model="form.api_token" placeholder="Token/AppSecret" show-password />
        </el-form-item>
        <el-form-item label="用户名" v-if="form.brand==='hikvision'">
          <el-input v-model="form.api_username" placeholder="海康设备默认 admin" />
        </el-form-item>
        <el-form-item label="设备编号">
          <el-input v-model="form.device_sn" placeholder="道闸设备序列号" />
        </el-form-item>
        <el-form-item label="通道号">
          <el-input-number v-model="form.channel_no" :min="1" :max="16" />
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
const isEdit = ref(false)
const editId = ref(0)
const communities = ref<any[]>([])
const brandMap = ref<Record<string,string>>({})

const form = reactive({ community_id: undefined as any, entrance_name:'', brand:'generic', direction:'in', api_url:'', api_token:'', api_username:'', device_sn:'', channel_no:1, remark:'' })
const query = reactive({ community_id: undefined as any, brand:'', enabled:'', page:1, limit:15 })

const formTitle = ref('添加道闸配置')

function resetQuery() { query.community_id = undefined; query.brand = ''; query.enabled = ''; query.page = 1; loadData() }

async function loadData() {
  loading.value = true
  try {
    const r = await apiGet('/admin/parking/gateConfigList', { ...query })
    list.value = r.data?.list || r.data || []
    total.value = r.count || r.data?.total || list.value.length
  } catch { list.value = []; total.value = 0 }
  finally { loading.value = false }
}

function openAdd() {
  isEdit.value = false; editId.value = 0
  Object.assign(form, { community_id:undefined, entrance_name:'', brand:'generic', direction:'in', api_url:'', api_token:'', api_username:'', device_sn:'', channel_no:1, remark:'' })
  formTitle.value = '添加道闸配置'; formVisible.value = true
}

function openEdit(row: any) {
  isEdit.value = true; editId.value = row.id
  Object.assign(form, { community_id:row.community_id, entrance_name:row.entrance_name, brand:row.brand, direction:row.direction, api_url:row.api_url||'', api_token:row.api_token||'', api_username:row.api_username||'', device_sn:row.device_sn||'', channel_no:row.channel_no||1, remark:row.remark||'' })
  formTitle.value = '编辑道闸配置'; formVisible.value = true
}

async function submitForm() {
  if (!form.community_id) { ElMessage.warning('请选择小区'); return }
  if (!form.entrance_name) { ElMessage.warning('请输入出入口名称'); return }
  submitting.value = true
  try {
    const url = isEdit.value ? '/admin/parking/gateConfigEdit' : '/admin/parking/gateConfigAdd'
    const payload: any = { ...form }
    if (isEdit.value) payload.id = editId.value
    await apiPost(url, payload)
    ElMessage.success(isEdit.value ? '更新成功' : '添加成功')
    formVisible.value = false; loadData()
  } finally { submitting.value = false }
}

async function toggleEnable(row: any) {
  try { await apiPost('/admin/parking/gateConfigEdit', { id: row.id, enabled: row.enabled === 1 ? 0 : 1 }); loadData() } catch {}
}

async function testConn(row: any) {
  try {
    const r = await apiPost('/admin/parking/gateConfigTest', { id: row.id })
    ElMessage.success('连接成功: ' + JSON.stringify(r.data || r))
  } catch (e: any) { ElMessage.error('连接失败') }
}

async function remoteOpen(row: any) {
  try {
    await ElMessageBox.confirm(`确定远程打开「${row.entrance_name}」道闸吗？`, '远程开闸', { type: 'warning' })
    await apiPost('/admin/parking/gateConfigOpen', { id: row.id })
    ElMessage.success('已发送开闸指令')
  } catch {}
}

async function syncWhite(row: any) {
  try {
    const r = await apiPost('/admin/parking/gateConfigSyncWhitelist', { id: row.id })
    const count = r.data?.count || 0
    ElMessage.success(`同步成功，共推送 ${count} 个车牌到设备`)
  } catch {}
}

async function handleDelete(row: any) {
  try {
    await ElMessageBox.confirm(`确定删除「${row.entrance_name}」的配置吗？`, '提示', { type: 'warning' })
    await apiPost('/admin/parking/gateConfigDelete', { id: row.id })
    ElMessage.success('删除成功'); loadData()
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
  loadData()
})
</script>

<style scoped>
.search-bar { background:#fff;border-radius:8px;padding:16px 20px;margin-bottom:16px;border:1px solid #e2e8f0; }
.table-card { border-radius:8px;border:1px solid #e2e8f0; }
.pagination { margin-top:16px;display:flex;justify-content:flex-end; }
.form-tip { color:#999;font-size:12px;margin-top:4px; }
</style>
