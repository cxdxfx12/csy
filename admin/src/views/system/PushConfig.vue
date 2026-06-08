<template>
  <div class="push-config-page">
    <!-- Hero -->
    <div class="hero-section">
      <div class="hero-content">
        <div class="hero-left">
          <div class="hero-icon-wrap">
            <el-icon :size="28"><Bell /></el-icon>
          </div>
          <div>
            <h1 class="hero-title">推送配置</h1>
            <p class="hero-desc">管理多渠道推送开关与短信服务商接入</p>
          </div>
        </div>
        <div class="hero-stats">
          <div class="stat-item">
            <span class="stat-value">{{ list.length }}</span>
            <span class="stat-label">小区总数</span>
          </div>
          <div class="stat-divider" />
          <div class="stat-item">
            <span class="stat-value stat-success">{{ smsBoundCount }}</span>
            <span class="stat-label">已配短信</span>
          </div>
          <div class="stat-divider" />
          <div class="stat-item">
            <span class="stat-value stat-primary">{{ sseOnCount }}</span>
            <span class="stat-label">SSE已开启</span>
          </div>
        </div>
      </div>
      <svg class="hero-wave" viewBox="0 0 1440 60" preserveAspectRatio="none">
        <path d="M0,40 C360,0 1080,0 1440,40 L1440,60 L0,60 Z" fill="#f8fafc"/>
      </svg>
    </div>

    <!-- 搜索栏 -->
    <div class="content-wrap">
      <div class="search-bar">
        <div class="search-left">
          <el-input
            v-model="query.keyword"
            placeholder="搜索小区名称..."
            clearable
            class="search-input"
            @keyup.enter="handleSearch"
          >
            <template #prefix><el-icon><Search /></el-icon></template>
          </el-input>
        </div>
        <div class="search-right">
          <el-button :icon="RefreshRight" @click="resetQuery" round>重置</el-button>
          <el-button type="primary" :icon="Search" @click="handleSearch" round>搜索</el-button>
        </div>
      </div>

      <!-- 表格 -->
      <div class="table-card">
        <div class="card-header">
          <span class="card-title">
            <span class="dot dot-primary"></span>
            小区推送配置
          </span>
          <span class="card-count">共 {{ total }} 个小区</span>
        </div>
        <el-table :data="list" v-loading="loading" class="data-table" row-class-name="table-row">
          <el-table-column type="index" label="#" width="56" align="center" />
          <el-table-column label="小区" min-width="200" prop="name">
            <template #default="{ row }">
              <div class="cell-community">
                <div class="community-avatar">
                  <el-icon :size="20"><OfficeBuilding /></el-icon>
                </div>
                <div class="community-info">
                  <span class="community-name">{{ row.name }}</span>
                  <span class="community-code">{{ row.code }}</span>
                </div>
              </div>
            </template>
          </el-table-column>

          <el-table-column label="SSE实时" width="90" align="center">
            <template #default="{ row }">
              <el-switch v-model="row.sse" :active-value="1" :inactive-value="0"
                inline-prompt active-text="开" inactive-text="关"
                @change="quickToggle(row, 'sse_enable', $event)" />
            </template>
          </el-table-column>
          <el-table-column label="微信" width="90" align="center">
            <template #default="{ row }">
              <el-switch v-model="row.wechat" :active-value="1" :inactive-value="0"
                inline-prompt active-text="开" inactive-text="关"
                @change="quickToggle(row, 'wechat_enable', $event)" />
            </template>
          </el-table-column>
          <el-table-column label="短信" width="90" align="center">
            <template #default="{ row }">
              <el-switch v-model="row.sms" :active-value="1" :inactive-value="0"
                inline-prompt active-text="开" inactive-text="关"
                @change="quickToggle(row, 'sms_enable', $event)" />
            </template>
          </el-table-column>
          <el-table-column label="新报修" width="90" align="center">
            <template #default="{ row }">
              <el-switch v-model="row.repair_new" :active-value="1" :inactive-value="0"
                inline-prompt active-text="开" inactive-text="关" size="small"
                @change="quickToggle(row, 'repair_new_enable', $event)" />
            </template>
          </el-table-column>
          <el-table-column label="派单" width="80" align="center">
            <template #default="{ row }">
              <el-switch v-model="row.repair_assign" :active-value="1" :inactive-value="0"
                inline-prompt active-text="开" inactive-text="关" size="small"
                @change="quickToggle(row, 'repair_assign_enable', $event)" />
            </template>
          </el-table-column>
          <el-table-column label="短信商" width="100" align="center">
            <template #default="{ row }">
              <el-tag v-if="row.sms_status === 1" type="success" effect="light" size="small">
                {{ row.sms_provider === 'tencent' ? '腾讯云' : '阿里云' }}
              </el-tag>
              <span v-else class="text-muted">未配置</span>
            </template>
          </el-table-column>
          <el-table-column label="操作" width="140" align="center" fixed="right">
            <template #default="{ row }">
              <el-button size="small" type="primary" link @click="openConfig(row)">
                <el-icon><Setting /></el-icon> 配置
              </el-button>
            </template>
          </el-table-column>
        </el-table>

        <div v-if="!loading && list.length === 0" class="empty-state">
          <el-empty description="暂无小区数据" :image-size="120" />
        </div>

        <div class="pagination-wrap">
          <el-pagination
            v-model:current-page="query.page"
            v-model:page-size="query.limit"
            :total="total"
            :page-sizes="[12, 24, 36]"
            layout="total, sizes, prev, pager, next, jumper"
            background
          />
        </div>
      </div>
    </div>

    <!-- 配置抽屉 -->
    <el-drawer
      v-model="drawerVisible"
      :title="null"
      size="640px"
      :close-on-click-modal="false"
      destroy-on-close
      class="config-drawer"
    >
      <template #header>
        <div class="drawer-header">
          <div class="drawer-title-row">
            <span class="drawer-icon"><el-icon :size="22"><Bell /></el-icon></span>
            <div>
              <h3 class="drawer-title">推送配置</h3>
              <p class="drawer-subtitle">{{ currentCommunity.name }} · {{ currentCommunity.code }}</p>
            </div>
          </div>
        </div>
      </template>

      <div class="drawer-body">
        <!-- Section 1: 推送渠道开关 -->
        <div class="section-block">
          <div class="section-header">
            <span class="section-dot"></span>
            <span class="section-title">推送渠道</span>
          </div>
          <el-form :model="form" label-position="top" class="section-form">
            <el-row :gutter="20">
              <el-col :span="12">
                <el-form-item label="SSE 实时推送">
                  <el-switch v-model="form.sse_enable" :active-value="1" :inactive-value="0"
                    active-text="开启" inactive-text="关闭" />
                  <div class="form-hint">Web端即时通知，无需额外配置</div>
                </el-form-item>
              </el-col>
              <el-col :span="12">
                <el-form-item label="微信模板消息">
                  <el-switch v-model="form.wechat_enable" :active-value="1" :inactive-value="0"
                    active-text="开启" inactive-text="关闭" />
                  <div class="form-hint">需在公众号配置中设置模板消息ID</div>
                </el-form-item>
              </el-col>
            </el-row>
            <el-row :gutter="20">
              <el-col :span="12">
                <el-form-item label="短信通知">
                  <el-switch v-model="form.sms_enable" :active-value="1" :inactive-value="0"
                    active-text="开启" inactive-text="关闭" />
                  <div class="form-hint">需在下方面板配置短信服务商</div>
                </el-form-item>
              </el-col>
              <el-col :span="12">
                <el-form-item label="App Push">
                  <el-switch v-model="form.app_push_enable" :active-value="1" :inactive-value="0"
                    active-text="开启" inactive-text="关闭" />
                  <div class="form-hint">预留扩展，待后续接入</div>
                </el-form-item>
              </el-col>
            </el-row>
          </el-form>
        </div>

        <!-- Section 2: 事件类型开关 -->
        <div class="section-block">
          <div class="section-header">
            <span class="section-dot"></span>
            <span class="section-title">事件类型</span>
          </div>
          <el-form :model="form" label-position="top" class="section-form">
            <el-row :gutter="20">
              <el-col :span="8">
                <el-form-item label="新报修通知">
                  <el-switch v-model="form.repair_new_enable" :active-value="1" :inactive-value="0"
                    active-text="开启" inactive-text="关闭" size="small" />
                </el-form-item>
              </el-col>
              <el-col :span="8">
                <el-form-item label="派单通知">
                  <el-switch v-model="form.repair_assign_enable" :active-value="1" :inactive-value="0"
                    active-text="开启" inactive-text="关闭" size="small" />
                </el-form-item>
              </el-col>
              <el-col :span="8">
                <el-form-item label="催缴通知">
                  <el-switch v-model="form.dunning_enable" :active-value="1" :inactive-value="0"
                    active-text="开启" inactive-text="关闭" size="small" />
                </el-form-item>
              </el-col>
            </el-row>
          </el-form>
        </div>

        <!-- Section 3: 短信服务商配置 -->
        <div class="section-block">
          <div class="section-header">
            <span class="section-dot"></span>
            <span class="section-title">短信服务商</span>
          </div>
          <div class="guide-card-sm">
            支持 <b>阿里云短信</b> 和 <b>腾讯云短信</b>。请前往对应控制台获取 AccessKey 和模板CODE。
          </div>

          <el-form :model="smsForm" label-position="top" class="section-form">
            <el-form-item>
              <template #label>
                <span class="form-label">服务商选择</span>
              </template>
              <el-radio-group v-model="smsForm.provider">
                <el-radio-button value="aliyun">阿里云短信</el-radio-button>
                <el-radio-button value="tencent">腾讯云短信</el-radio-button>
              </el-radio-group>
            </el-form-item>

            <el-row :gutter="16">
              <el-col :span="12">
                <el-form-item>
                  <template #label>
                    <span class="form-label">{{ smsForm.provider === 'tencent' ? 'SecretId' : 'AccessKey ID' }}</span>
                  </template>
                  <el-input v-model="smsForm.access_key_id"
                    :placeholder="smsForm.provider === 'tencent' ? '腾讯云 SecretId' : '阿里云 AccessKey ID'"
                    clearable show-password />
                </el-form-item>
              </el-col>
              <el-col :span="12">
                <el-form-item>
                  <template #label>
                    <span class="form-label">{{ smsForm.provider === 'tencent' ? 'SecretKey' : 'AccessKey Secret' }}</span>
                  </template>
                  <el-input v-model="smsForm.access_key_secret"
                    :placeholder="smsForm.provider === 'tencent' ? '腾讯云 SecretKey' : '阿里云 AccessKey Secret'"
                    clearable show-password />
                  <div class="form-hint">留空则不修改已有密钥</div>
                </el-form-item>
              </el-col>
            </el-row>

            <el-row :gutter="16">
              <el-col :span="8">
                <el-form-item>
                  <template #label>
                    <span class="form-label">短信签名</span>
                  </template>
                  <el-input v-model="smsForm.sign_name" placeholder="如：大圣物业" clearable />
                </el-form-item>
              </el-col>
              <el-col :span="8">
                <el-form-item>
                  <template #label>
                    <span class="form-label">报修通知模板CODE</span>
                  </template>
                  <el-input v-model="smsForm.repair_template" placeholder="SMS_xxx" clearable />
                </el-form-item>
              </el-col>
              <el-col :span="8">
                <el-form-item>
                  <template #label>
                    <span class="form-label">催缴通知模板CODE</span>
                  </template>
                  <el-input v-model="smsForm.dunning_template" placeholder="SMS_xxx" clearable />
                </el-form-item>
              </el-col>
            </el-row>
          </el-form>
        </div>
      </div>

      <template #footer>
        <div class="drawer-footer">
          <el-button @click="handleTestSms" :disabled="!smsForm.sign_name" :loading="testing" :icon="Connection">
            测试短信接口
          </el-button>
          <el-button @click="handleSaveSms" :loading="savingSms" :icon="Check">
            保存短信配置
          </el-button>
          <div class="footer-spacer" />
          <el-button @click="drawerVisible = false">取消</el-button>
          <el-button type="primary" @click="handleSavePush" :loading="saving" :icon="Check">
            保存推送配置
          </el-button>
        </div>
      </template>
    </el-drawer>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { ElMessage } from 'element-plus'
import {
  Search, RefreshRight, Setting, Bell, OfficeBuilding,
  Check, Connection,
} from '@element-plus/icons-vue'
import { apiGet, apiPost } from '@/utils/request'

const list = ref<any[]>([])
const total = ref(0)
const loading = ref(false)
const saving = ref(false)
const savingSms = ref(false)
const testing = ref(false)
const drawerVisible = ref(false)
const currentCommunity = ref<{ id: number; name: string; code: string }>({ id: 0, name: '', code: '' })

const query = reactive({ keyword: '', page: 1, limit: 12 })

const smsBoundCount = computed(() => list.value.filter((r: any) => r.sms_status === 1).length)
const sseOnCount = computed(() => list.value.filter((r: any) => r.sse === 1).length)

const form = reactive<Record<string, any>>({
  community_id: 0, sse_enable: 1, wechat_enable: 1, sms_enable: 0,
  app_push_enable: 0, repair_new_enable: 1, repair_assign_enable: 1, dunning_enable: 1,
})

const smsForm = reactive<Record<string, any>>({
  community_id: 0, provider: 'aliyun',
  access_key_id: '', access_key_secret: '',
  sign_name: '', repair_template: '', dunning_template: '',
})

function resetQuery() { query.keyword = ''; query.page = 1; loadData() }
function handleSearch() { query.page = 1; loadData() }

watch([() => query.page, () => query.limit], () => loadData())

async function loadData() {
  loading.value = true
  try {
    const r = await apiGet('/admin/system/pushConfigList', { ...query })
    list.value = r.data?.list || r.data || []
    total.value = r.count || r.data?.total || list.value.length
  } catch { list.value = []; total.value = 0 }
  finally { loading.value = false }
}

async function quickToggle(row: any, field: string, val: number) {
  try {
    await apiPost('/admin/system/pushConfigSavePush', { community_id: row.id, [field]: val })
    ElMessage.success('已更新')
  } catch {
    // 回滚
    await loadData()
  }
}

function resetPushForm() {
  Object.assign(form, {
    community_id: 0, sse_enable: 1, wechat_enable: 1, sms_enable: 0,
    app_push_enable: 0, repair_new_enable: 1, repair_assign_enable: 1, dunning_enable: 1,
  })
}

function resetSmsForm() {
  Object.assign(smsForm, {
    community_id: 0, provider: 'aliyun',
    access_key_id: '', access_key_secret: '',
    sign_name: '', repair_template: '', dunning_template: '',
  })
}

async function openConfig(row: any) {
  resetPushForm()
  resetSmsForm()
  currentCommunity.value = { id: row.id, name: row.name, code: row.code }
  try {
    const r = await apiGet('/admin/system/pushConfigDetail', { community_id: row.id })
    const push = r.data?.push || null
    const sms = r.data?.sms || null

    form.community_id = row.id
    if (push) {
      form.sse_enable = push.sse_enable ?? 1
      form.wechat_enable = push.wechat_enable ?? 1
      form.sms_enable = push.sms_enable ?? 0
      form.app_push_enable = push.app_push_enable ?? 0
      form.repair_new_enable = push.repair_new_enable ?? 1
      form.repair_assign_enable = push.repair_assign_enable ?? 1
      form.dunning_enable = push.dunning_enable ?? 1
    }

    smsForm.community_id = row.id
    if (sms) {
      smsForm.provider = sms.provider || 'aliyun'
      smsForm.access_key_id = sms.access_key_id || ''
      smsForm.access_key_secret = sms.access_key_secret || ''
      smsForm.sign_name = sms.sign_name || ''
      smsForm.repair_template = sms.repair_template || ''
      smsForm.dunning_template = sms.dunning_template || ''
    }
  } catch { /* 新配置 */ }
  drawerVisible.value = true
}

async function handleSavePush() {
  if (!form.community_id) return ElMessage.warning('请先选择小区')
  saving.value = true
  try {
    await apiPost('/admin/system/pushConfigSavePush', { ...form })
    ElMessage.success('推送配置保存成功')
    drawerVisible.value = false
    loadData()
  } finally { saving.value = false }
}

async function handleSaveSms() {
  if (!smsForm.community_id) return ElMessage.warning('请先选择小区')
  savingSms.value = true
  try {
    await apiPost('/admin/system/pushConfigSaveSms', { ...smsForm })
    ElMessage.success('短信配置保存成功')
  } finally { savingSms.value = false }
}

async function handleTestSms() {
  if (!smsForm.community_id) return ElMessage.warning('请先保存短信配置')
  testing.value = true
  try {
    const r = await apiGet('/admin/system/pushConfigTestSms', { community_id: smsForm.community_id })
    ElMessage.success({ message: r.msg || '短信接口连通性测试通过', duration: 3000 })
  } finally { testing.value = false }
}

onMounted(loadData)
</script>

<style scoped>
/* ========== Hero ========== */
.push-config-page { min-height: 100%; }
.hero-section {
  position: relative;
  background: linear-gradient(135deg, #6366f1 0%, #4f46e5 40%, #4338ca 100%);
  padding: 32px 36px 50px;
  color: #fff;
  overflow: hidden;
}
.hero-content {
  display: flex; align-items: center; justify-content: space-between;
  flex-wrap: wrap; gap: 20px;
  position: relative; z-index: 1;
}
.hero-left { display: flex; align-items: center; gap: 16px; }
.hero-icon-wrap {
  width: 56px; height: 56px; border-radius: 16px;
  background: rgba(255,255,255,0.2); backdrop-filter: blur(8px);
  display: flex; align-items: center; justify-content: center;
}
.hero-title { font-size: 22px; font-weight: 700; margin: 0; line-height: 1.2; }
.hero-desc { font-size: 13px; opacity: 0.85; margin: 4px 0 0; }
.hero-stats { display: flex; align-items: center; gap: 24px; }
.stat-item { text-align: center; }
.stat-value { display: block; font-size: 26px; font-weight: 700; }
.stat-value.stat-success { color: #d4ffda; }
.stat-value.stat-primary { color: #c7d2fe; }
.stat-label { font-size: 12px; opacity: 0.8; }
.stat-divider { width: 1px; height: 36px; background: rgba(255,255,255,0.25); }
.hero-wave { position: absolute; bottom: -1px; left: 0; width: 100%; height: 40px; z-index: 0; }

/* ========== Content ========== */
.content-wrap { padding: 16px 24px; }

/* ========== Search ========== */
.search-bar {
  display: flex; align-items: center; justify-content: space-between; gap: 16px;
  background: #fff; border-radius: 12px; padding: 12px 20px;
  margin-bottom: 16px;
  border: 1px solid #e8ecf1;
  box-shadow: 0 1px 3px rgba(0,0,0,0.04);
}
.search-left { display: flex; align-items: center; flex: 1; max-width: 440px; }
.search-input { flex: 1; }
.search-input :deep(.el-input__wrapper) {
  box-shadow: none !important; border-radius: 8px; background: #f7f8fa;
}
.search-input :deep(.el-input__wrapper):hover,
.search-input :deep(.el-input__wrapper.is-focus) {
  box-shadow: 0 0 0 1px #6366f1 inset !important; background: #fff;
}
.search-right { display: flex; gap: 8px; }

/* ========== Table Card ========== */
.table-card {
  background: #fff; border-radius: 12px;
  border: 1px solid #e8ecf1;
  box-shadow: 0 1px 3px rgba(0,0,0,0.04);
  overflow: hidden;
}
.card-header {
  display: flex; align-items: center; justify-content: space-between;
  padding: 16px 20px; border-bottom: 1px solid #f0f2f5;
}
.card-title { font-size: 15px; font-weight: 600; display: flex; align-items: center; gap: 8px; }
.dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; }
.dot-primary { background: #6366f1; box-shadow: 0 0 6px rgba(99,102,241,0.4); }
.card-count { font-size: 12px; color: #a0aec0; }

.data-table { width: 100%; }
.data-table :deep(.table-row) { transition: background 0.2s; }
.data-table :deep(.table-row:hover) { background: #f5f3ff !important; }
.data-table :deep(.el-table__header th) {
  background: #fafbfc !important; font-weight: 600; font-size: 12px; color: #718096;
  text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid #e2e8f0;
}

.cell-community { display: flex; align-items: center; gap: 12px; }
.community-avatar {
  width: 40px; height: 40px; border-radius: 10px;
  background: linear-gradient(135deg, #eef2ff, #e0e7ff);
  color: #6366f1; display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}
.community-info { display: flex; flex-direction: column; }
.community-name { font-weight: 600; font-size: 14px; }
.community-code { font-size: 11px; color: #a0aec0; }
.text-muted { color: #a0aec0; font-size: 13px; }

.empty-state { padding: 60px 0; }
.pagination-wrap { padding: 12px 20px 16px; display: flex; justify-content: flex-end; border-top: 1px solid #f0f2f5; }

/* ========== Drawer ========== */
.config-drawer :deep(.el-drawer__header) { margin-bottom: 0; padding: 20px 24px; border-bottom: 1px solid #f0f2f5; }
.config-drawer :deep(.el-drawer__body) { padding: 0; }
.config-drawer :deep(.el-drawer__footer) { padding: 16px 24px; border-top: 1px solid #f0f2f5; }

.drawer-header { display: flex; align-items: center; }
.drawer-title-row { display: flex; align-items: center; gap: 14px; }
.drawer-icon {
  width: 44px; height: 44px; border-radius: 12px;
  background: linear-gradient(135deg, #eef2ff, #e0e7ff);
  color: #6366f1; display: flex; align-items: center; justify-content: center;
}
.drawer-title { font-size: 18px; font-weight: 700; margin: 0; line-height: 1.3; }
.drawer-subtitle { font-size: 12px; color: #a0aec0; margin: 2px 0 0; }
.drawer-body { padding: 20px 24px; }

/* Guide */
.guide-card-sm {
  padding: 10px 14px; background: #f8fafc; border-radius: 8px;
  border: 1px solid #e2e8f0; font-size: 12px; color: #64748b; margin-bottom: 16px;
}

/* Section */
.section-block { margin-bottom: 16px; padding: 0 0 8px; }
.section-header {
  display: flex; align-items: center; gap: 10px;
  margin-bottom: 14px; padding-bottom: 10px;
  border-bottom: 1px solid #f0f2f5;
}
.section-dot {
  width: 4px; height: 18px; border-radius: 2px;
  background: linear-gradient(180deg, #6366f1, #4f46e5);
}
.section-title { font-size: 14px; font-weight: 700; color: #1a202c; }
.section-form { margin-top: 4px; }

.form-label { font-weight: 600; font-size: 13px; color: #4a5568; }
.form-hint { font-size: 11px; color: #a0aec0; margin-top: 3px; line-height: 1.3; }

.drawer-footer { display: flex; align-items: center; gap: 10px; }
.footer-spacer { flex: 1; }

/* ========== Responsive ========== */
@media (max-width: 768px) {
  .hero-section { padding: 20px 16px 40px; }
  .hero-content { flex-direction: column; align-items: flex-start; }
  .hero-stats { gap: 16px; }
  .stat-value { font-size: 22px; }
  .content-wrap { padding: 12px; }
  .search-bar { flex-direction: column; align-items: stretch; }
  .search-left { max-width: 100%; }
}
</style>
