<template>
  <div class="page-container">
    <!-- 搜索栏 -->
    <div class="search-bar">
      <el-form :model="query" inline>
        <el-form-item><el-input v-model="query.keyword" placeholder="搜索小区名称/编码" clearable style="width:220px;" @keyup.enter="loadData" /></el-form-item>
        <el-form-item>
          <el-button type="primary" @click="loadData">搜索</el-button>
          <el-button @click="resetQuery">重置</el-button>
        </el-form-item>
      </el-form>
    </div>

    <!-- 小区列表 -->
    <el-card shadow="never" class="table-card">
      <el-table :data="list" v-loading="loading" stripe border highlight-current-row>
        <el-table-column type="index" label="#" width="55" />
        <el-table-column prop="name" label="小区名称" min-width="160">
          <template #default="{row}"><el-tag effect="plain">{{ row.name }}</el-tag> <span style="color:#a0aec0;font-size:12px;">{{ row.code }}</span></template>
        </el-table-column>
        <el-table-column prop="address" label="地址" min-width="200" show-overflow-tooltip />
        <el-table-column prop="sms_key_mask" label="已配置KEY" width="220" align="center">
          <template #default="{row}">
            <el-tag v-if="row.sms_status===1" type="success" effect="plain">{{ row.sms_key_mask }}</el-tag>
            <el-tag v-else type="info" effect="plain">未配置</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="sms_status" label="状态" width="100" align="center">
          <template #default="{row}">
            <el-switch :model-value="row.sms_status===1" disabled size="small" />
          </template>
        </el-table-column>
        <el-table-column label="操作" width="160" align="center" fixed="right">
          <template #default="{row}">
            <el-button size="small" type="primary" @click="openConfig(row)">配置</el-button>
            <el-button v-if="row.sms_status===1" size="small" type="success" @click="testConfig(row)">测试</el-button>
          </template>
        </el-table-column>
      </el-table>
      <div class="pagination">
        <el-pagination v-model:current-page="query.page" v-model:page-size="query.limit" :total="total" :page-sizes="[15,30,50]" layout="total,sizes,prev,pager,next" @change="loadData" />
      </div>
    </el-card>

    <!-- 配置抽屉 -->
    <el-drawer v-model="drawerVisible" :title="'短信配置 - ' + currentCommunity.name" size="520px" :close-on-click-modal="false" destroy-on-close>
      <el-alert title="短信接口配置说明" type="info" :closable="false" show-icon style="margin-bottom:16px;">
        <template #default>
          请前往短信服务商平台（如<b>阿里云短信</b>、<b>腾讯云短信</b>等）获取 API Key / SecretKey，粘贴到下方。该KEY将用于本小区的短信发送功能。
        </template>
      </el-alert>

      <el-form :model="form" label-width="120px" label-position="top">
        <el-form-item label="短信接口 KEY">
          <el-input
            v-model="form.sms_key"
            type="textarea"
            :rows="6"
            placeholder="请输入短信服务商提供的 API Key 或 AccessKey Secret&#10;&#10;示例格式：&#10;阿里云：AccessKey ID + AccessKey Secret&#10;腾讯云：SDK AppID + App Key&#10;或其他短信平台的认证密钥"
            clearable
          />
          <div class="tip">KEY将以加密方式存储。若已设置，留空则不修改原有密钥</div>
        </el-form-item>
        <el-form-item label="配置状态">
          <el-tag v-if="form.sms_key && !form.sms_key.includes('****')" type="warning" effect="plain">待保存</el-tag>
          <el-tag v-else-if="form.sms_key && form.sms_key.includes('****')" type="success" effect="plain">已配置（脱敏显示）</el-tag>
          <el-tag v-else type="info" effect="plain">未配置</el-tag>
        </el-form-item>
      </el-form>

      <!-- 底部操作 -->
      <div class="drawer-footer">
        <el-button @click="handleTest" :disabled="!form.sms_key" :loading="testing">
          测试接口连通性
        </el-button>
        <div style="flex:1;" />
        <el-button @click="drawerVisible = false">取消</el-button>
        <el-button type="primary" @click="handleSave" :loading="saving">保存配置</el-button>
      </div>
    </el-drawer>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { apiGet, apiPost } from '@/utils/request'

const list = ref<any[]>([])
const total = ref(0)
const loading = ref(false)
const saving = ref(false)
const testing = ref(false)
const drawerVisible = ref(false)
const currentCommunity = ref<{id:number;name:string;code:string}>({id:0,name:'',code:''})

const query = reactive({ keyword: '', page: 1, limit: 15 })
const form = reactive<Record<string, any>>({
  community_id: 0,
  sms_key: '',
})

function resetQuery() { query.keyword = ''; query.page = 1; loadData() }

async function loadData() {
  loading.value = true
  try {
    const r = await apiGet('/admin/sms/list', { ...query })
    list.value = r.data?.list || r.data || []
    total.value = r.count || r.data?.total || list.value.length
  } catch { list.value = []; total.value = 0 }
  finally { loading.value = false }
}

function resetForm() {
  form.community_id = 0
  form.sms_key = ''
}

async function openConfig(row: any) {
  resetForm()
  currentCommunity.value = { id: row.id, name: row.name, code: row.code }
  try {
    const r = await apiGet('/admin/sms/detail', { community_id: row.id })
    form.community_id = row.id
    form.sms_key = r.data?.sms_key || ''
  } catch {}
  drawerVisible.value = true
}

async function handleSave() {
  if (!form.community_id) return ElMessage.warning('请先选择小区')
  if (!form.sms_key) return ElMessage.warning('请输入短信接口KEY')
  if (form.sms_key.includes('****')) {
    ElMessage.info('KEY未修改，无需保存')
    return
  }

  saving.value = true
  try {
    await apiPost('/admin/sms/save', { ...form })
    ElMessage.success('保存成功')
    drawerVisible.value = false
    loadData()
  } finally { saving.value = false }
}

async function handleTest() {
  if (!form.community_id) return ElMessage.warning('请先保存配置')
  testing.value = true
  try {
    const r = await apiGet('/admin/sms/test', { community_id: form.community_id })
    ElMessage.success({ message: r.msg, duration: 3000 })
  } finally { testing.value = false }
}

function testConfig(row: any) {
  ElMessageBox.confirm(`确定测试「${row.name}」的短信接口配置吗？`, '测试短信配置', {
    confirmButtonText: '开始测试',
    type: 'info',
  }).then(async () => {
    try {
      const r = await apiGet('/admin/sms/test', { community_id: row.id })
      ElMessage.success({ message: r.msg, duration: 3000 })
    } catch {}
  }).catch(() => {})
}

onMounted(loadData)
</script>

<style scoped>
.page-container { padding: 0; }
.search-bar { background: #fff; border-radius: 8px; padding: 16px 20px; margin-bottom: 16px; border: 1px solid #e2e8f0; }
.table-card { border-radius: 8px; border: 1px solid #e2e8f0; }
.pagination { margin-top: 16px; display: flex; justify-content: flex-end; }
.drawer-footer { display: flex; align-items: center; gap: 10px; padding: 16px 0 0; border-top: 1px solid #e2e8f0; margin-top: 20px; }
.tip { font-size: 12px; color: #a0aec0; margin-top: 4px; line-height: 1.4; }
</style>
