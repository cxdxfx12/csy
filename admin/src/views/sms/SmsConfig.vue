<template>
  <div class="sms-page">
    <!-- 页面头部 -->
    <div class="page-header">
      <div class="header-left">
        <div class="header-icon"><el-icon :size="26"><Message /></el-icon></div>
        <div class="header-info">
          <h2>短信服务商配置</h2>
          <p>为各小区配置第三方短信接口，支持阿里云/腾讯云等多平台</p>
        </div>
      </div>
      <div class="header-stats">
        <div class="stat-item">
          <span class="stat-num">{{ stats.configured }}</span>
          <span class="stat-label">已配置</span>
        </div>
        <div class="stat-divider" />
        <div class="stat-item">
          <span class="stat-num warn">{{ stats.unconfigured }}</span>
          <span class="stat-label">待配置</span>
        </div>
      </div>
    </div>

    <!-- 搜索栏 -->
    <div class="search-card">
      <el-form :model="query" inline>
        <el-form-item><el-input v-model="query.keyword" placeholder="搜索小区名称/编码" clearable style="width:280px" prefix-icon="Search" @keyup.enter="loadData" /></el-form-item>
        <el-form-item>
          <el-select v-model="query.status" placeholder="配置状态" clearable style="width:150px">
            <el-option label="已配置" :value="1" />
            <el-option label="未配置" :value="0" />
          </el-select>
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="loadData"><el-icon><Search /></el-icon> 搜索</el-button>
          <el-button @click="resetQuery">重置</el-button>
        </el-form-item>
      </el-form>
    </div>

    <!-- 小区列表 -->
    <el-card shadow="never" class="table-card">
      <el-table :data="list" v-loading="loading" stripe border highlight-current-row class="modern-table">
        <el-table-column type="index" label="#" width="55" />
        <el-table-column prop="name" label="小区名称" min-width="180">
          <template #default="{ row }">
            <div class="community-cell">
              <el-icon class="community-icon"><HomeFilled /></el-icon>
              <div>
                <div class="community-name">{{ row.name }}</div>
                <div class="community-code">{{ row.code }}</div>
              </div>
            </div>
          </template>
        </el-table-column>
        <el-table-column prop="address" label="地址" min-width="220" show-overflow-tooltip>
          <template #default="{ row }">
            <span style="color:#718096">{{ row.address || '未填写' }}</span>
          </template>
        </el-table-column>
        <el-table-column label="短信服务商" width="140" align="center">
          <template #default="{ row }">
            <el-tag v-if="row.sms_status === 1" effect="plain" type="success" round>
              <el-icon style="margin-right:4px"><CircleCheck /></el-icon> 已接入
            </el-tag>
            <el-tag v-else effect="plain" type="info" round>
              <el-icon style="margin-right:4px"><WarningFilled /></el-icon> 未接入
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="配置KEY" width="200" align="center">
          <template #default="{ row }">
            <span v-if="row.sms_key_mask" class="key-text">{{ row.sms_key_mask }}</span>
            <span v-else style="color:#cbd5e0">-</span>
          </template>
        </el-table-column>
        <el-table-column label="操作" width="200" align="center" fixed="right">
          <template #default="{ row }">
            <el-button size="small" type="primary" link @click="openConfig(row)">
              <el-icon><Edit /></el-icon> 配置
            </el-button>
            <el-button v-if="row.sms_status === 1" size="small" type="success" link @click="testConfig(row)">
              <el-icon><Connection /></el-icon> 测试
            </el-button>
          </template>
        </el-table-column>
      </el-table>
      <div class="pagination-wrap">
        <el-pagination v-model:current-page="query.page" v-model:page-size="query.limit" :total="total" :page-sizes="[15,30,50]" layout="total,sizes,prev,pager,next" @current-change="loadData" @size-change="loadData" />
      </div>
    </el-card>

    <!-- 配置抽屉 -->
    <el-drawer v-model="drawerVisible" size="560px" :close-on-click-modal="false" destroy-on-close>
      <template #header>
        <div class="drawer-header">
          <el-icon :size="22" color="#409EFF"><Setting /></el-icon>
          <span>短信接口配置 · {{ currentCommunity.name || '' }}</span>
        </div>
      </template>

      <el-alert type="info" :closable="false" show-icon style="margin-bottom:20px">
        <template #title>配置说明</template>
        <template #default>
          <p style="margin:4px 0">请前往短信服务商平台获取 API Key / Secret。</p>
          <p style="margin:0;color:#718096;font-size:13px">支持：阿里云短信、腾讯云短信、华为云短信等主流平台。</p>
        </template>
      </el-alert>

      <el-form :model="form" label-position="top" class="config-form">
        <el-form-item label="短信平台">
          <el-select v-model="form.provider" placeholder="请选择短信服务商" style="width:100%">
            <el-option label="阿里云短信 - Alibaba Cloud SMS" value="aliyun">
              <div class="provider-option"><span>阿里云短信</span><span style="color:#a0aec0;font-size:12px">Alibaba Cloud SMS</span></div>
            </el-option>
            <el-option label="腾讯云短信 - Tencent Cloud SMS" value="tencent">
              <div class="provider-option"><span>腾讯云短信</span><span style="color:#a0aec0;font-size:12px">Tencent Cloud SMS</span></div>
            </el-option>
            <el-option label="华为云短信 - Huawei Cloud SMS" value="huawei">
              <div class="provider-option"><span>华为云短信</span><span style="color:#a0aec0;font-size:12px">Huawei Cloud SMS</span></div>
            </el-option>
            <el-option label="其他/自定义" value="custom">
              <div class="provider-option"><span>其他平台</span><span style="color:#a0aec0;font-size:12px">自定义接入</span></div>
            </el-option>
          </el-select>
        </el-form-item>

        <el-form-item label="AccessKey ID / 应用ID">
          <el-input v-model="form.sms_key" type="textarea" :rows="3" placeholder="请输入服务商提供的 AccessKey ID 或 AppID" />
        </el-form-item>

        <el-form-item label="AccessKey Secret / 密钥">
          <el-input v-model="form.sms_secret" type="password" show-password :rows="3" placeholder="请输入 AccessKey Secret" />
        </el-form-item>

        <el-form-item label="签名名称">
          <el-input v-model="form.sign_name" placeholder="短信签名，如：大圣物业" />
        </el-form-item>

        <div class="status-badge">
          <el-tag v-if="isDirty" type="warning" size="large" effect="light">● 待保存</el-tag>
          <el-tag v-else-if="form.sms_key && form.sms_key.includes('****')" type="success" size="large" effect="light">● 已配置</el-tag>
          <el-tag v-else type="info" size="large" effect="light">● 未配置</el-tag>
        </div>
      </el-form>

      <div class="drawer-footer">
        <el-button @click="handleTest" :disabled="!form.sms_key" :loading="testing">
          <el-icon><Connection /></el-icon> 测试连通性
        </el-button>
        <div style="flex:1" />
        <el-button @click="drawerVisible = false">取消</el-button>
        <el-button type="primary" @click="handleSave" :loading="saving">
          <el-icon><Check /></el-icon> 保存配置
        </el-button>
      </div>
    </el-drawer>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { apiGet, apiPost } from '@/utils/request'
import { Search, Check, WarningFilled, CircleCheck, Edit, Connection, Setting, Message, HomeFilled } from '@element-plus/icons-vue'

const list = ref<any[]>([])
const total = ref(0)
const loading = ref(false)
const saving = ref(false)
const testing = ref(false)
const drawerVisible = ref(false)
const currentCommunity = ref<{ id: number; name: string; code: string }>({ id: 0, name: '', code: '' })

const query = reactive({ keyword: '', status: '' as any, page: 1, limit: 15 })
const form = reactive<Record<string, any>>({ community_id: 0, sms_key: '', sms_secret: '', sign_name: '', provider: 'aliyun' })

const stats = reactive({ configured: 0, unconfigured: 0 })
const isDirty = computed(() => form.sms_key && !form.sms_key.includes('****'))

function resetQuery() { query.keyword = ''; query.status = ''; query.page = 1; loadData() }

async function loadData() {
  loading.value = true
  try {
    const r = await apiGet('/admin/sms/list', {
      page: query.page,
      limit: query.limit,
      keyword: query.keyword,
      status: query.status,
    })
    list.value = r.data?.list || r.data || []
    total.value = r.count || r.data?.total || list.value.length
    stats.configured = list.value.filter((c: any) => c.sms_status === 1).length
    stats.unconfigured = total.value - stats.configured
  } catch { list.value = []; total.value = 0 }
  finally { loading.value = false }
}

function resetForm() {
  form.community_id = 0; form.sms_key = ''; form.sms_secret = ''; form.sign_name = ''; form.provider = 'aliyun'
}

async function openConfig(row: any) {
  resetForm()
  currentCommunity.value = { id: row.id, name: row.name, code: row.code }
  try {
    const r = await apiGet('/admin/sms/detail', { community_id: row.id })
    form.community_id = row.id
    form.sms_key = r.data?.sms_key || ''
    form.sms_secret = r.data?.sms_secret || ''
    form.sign_name = r.data?.sign_name || ''
    form.provider = r.data?.provider || 'aliyun'
  } catch { }
  drawerVisible.value = true
}

async function handleSave() {
  if (!form.community_id) return ElMessage.warning('请先选择小区')
  if (!form.sms_key) return ElMessage.warning('请输入 AccessKey ID')
  if (form.sms_key.includes('****')) { ElMessage.info('密钥未修改，无需保存'); return }
  saving.value = true
  try {
    await apiPost('/admin/sms/save', { ...form })
    ElMessage.success('保存成功')
    drawerVisible.value = false
    loadData()
  } finally { saving.value = false }
}

async function handleTest() {
  testing.value = true
  try {
    const r = await apiGet('/admin/sms/test', { community_id: form.community_id })
    ElMessage.success({ message: r.msg, duration: 3000 })
  } finally { testing.value = false }
}

function testConfig(row: any) {
  ElMessageBox.confirm(`确定测试「${row.name}」的短信接口连通性吗？`, '连通性测试', {
    confirmButtonText: '开始测试',
    type: 'info',
  }).then(async () => {
    try {
      const r = await apiGet('/admin/sms/test', { community_id: row.id })
      ElMessage.success({ message: r.msg, duration: 3000 })
    } catch { }
  }).catch(() => { })
}

onMounted(loadData)
</script>

<style scoped>
.sms-page { max-width: 1400px; margin: 0 auto; }
.page-header {
  display: flex; align-items: center; justify-content: space-between;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 12px; padding: 24px 28px; margin-bottom: 20px; color: #fff; flex-wrap: wrap; gap:16px;
}
.header-left { display: flex; align-items: center; gap: 16px; }
.header-icon { width: 48px; height: 48px; background: rgba(255,255,255,.2); border-radius: 12px; display: flex; align-items: center; justify-content: center; }
.header-info h2 { margin: 0 0 4px; font-size: 20px; font-weight: 700; }
.header-info p { margin: 0; font-size: 13px; opacity: .85; }
.header-stats { display: flex; align-items: center; gap: 24px; }
.stat-item { text-align: center; }
.stat-num { font-size: 28px; font-weight: 800; display: block; line-height: 1.2; }
.stat-num.warn { color: #fbbf24; }
.stat-label { font-size: 12px; opacity: .8; }
.stat-divider { width: 1px; height: 36px; background: rgba(255,255,255,.3); }
.search-card { background: #fff; border-radius: 10px; padding: 16px 20px; margin-bottom: 16px; border: 1px solid #e2e8f0; }
.table-card { border-radius: 10px; border: 1px solid #e2e8f0; overflow: hidden; }
.pagination-wrap { padding: 16px 0 0; display: flex; justify-content: flex-end; }
.community-cell { display: flex; align-items: center; gap: 10px; }
.community-icon { font-size: 20px; color: #667eea; flex-shrink: 0; }
.community-name { font-weight: 600; font-size: 14px; }
.community-code { font-size: 12px; color: #a0aec0; }
.key-text { font-family: monospace; background: #f7fafc; padding: 2px 8px; border-radius: 4px; font-size: 12px; color: #4a5568; }
.drawer-header { display: flex; align-items: center; gap: 8px; font-size: 16px; font-weight: 600; }
.config-form { margin-top: 8px; }
.status-badge { margin: 8px 0 16px; }
.provider-option { display: flex; justify-content: space-between; align-items: center; width: 100%; }
.drawer-footer { display: flex; align-items: center; gap: 10px; padding: 20px 0 0; border-top: 1px solid #e2e8f0; margin-top: 8px; }
.modern-table :deep(th) { background: #f8fafc !important; font-weight: 600; color: #2d3748; }
</style>
