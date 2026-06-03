<template>
  <div class="wechat-config-page">
    <!-- Hero 区域 -->
    <div class="hero-section">
      <div class="hero-content">
        <div class="hero-left">
          <div class="hero-icon-wrap">
            <el-icon :size="28"><svg viewBox="0 0 24 24" width="28" height="28" fill="currentColor"><path d="M8.691 2.188C3.891 2.188 0 5.476 0 9.53c0 2.212 1.17 4.203 3.002 5.55a.59.59 0 0 1 .213.665l-.39 1.48c-.019.07-.048.141-.048.213 0 .163.13.295.29.295a.326.326 0 0 0 .167-.054l1.903-1.114a.864.864 0 0 1 .717-.098 10.16 10.16 0 0 0 2.837.403c.276 0 .543-.027.811-.05-.857-2.578.157-4.972 1.932-6.446 1.703-1.415 3.882-1.98 5.853-1.838-.576-3.583-4.196-6.348-8.596-6.348zM5.785 5.991c.642 0 1.162.529 1.162 1.18a1.17 1.17 0 0 1-1.162 1.178A1.17 1.17 0 0 1 4.623 7.17c0-.651.52-1.18 1.162-1.18zm5.813 0c.642 0 1.162.529 1.162 1.18a1.17 1.17 0 0 1-1.162 1.178 1.17 1.17 0 0 1-1.162-1.178c0-.651.52-1.18 1.162-1.18zm3.804 2.29c-2.226.02-4.5.705-6.044 2.758-1.595 2.122-1.478 4.791.307 7.013 1.785 2.222 4.628 3.237 7.451 2.912a.884.884 0 0 1 .717.114l2.062 1.206a.334.334 0 0 0 .17.057c.158 0 .288-.131.288-.291 0-.071-.027-.143-.046-.212l-.424-1.609a.59.59 0 0 1 .213-.665c1.832-1.344 3.002-3.328 3.002-5.542C24 10.495 20.337 8.27 15.402 8.28zm-2.656 2.506a.93.93 0 0 1 .926.934c0 .516-.415.935-.926.935a.932.932 0 0 1-.926-.935c0-.517.414-.934.926-.934zm4.648 0a.93.93 0 0 1 .927.934c0 .516-.415.935-.927.935a.932.932 0 0 1-.926-.935c0-.517.414-.934.926-.934z"/></svg></el-icon>
          </div>
          <div>
            <h1 class="hero-title">公众号配置</h1>
            <p class="hero-desc">管理各小区微信公众号接入参数与模板消息</p>
          </div>
        </div>
        <div class="hero-stats">
          <div class="stat-item">
            <span class="stat-value">{{ list.length }}</span>
            <span class="stat-label">小区总数</span>
          </div>
          <div class="stat-divider" />
          <div class="stat-item">
            <span class="stat-value stat-success">{{ bindCount }}</span>
            <span class="stat-label">已绑定</span>
          </div>
          <div class="stat-divider" />
          <div class="stat-item">
            <span class="stat-value stat-warning">{{ unbindCount }}</span>
            <span class="stat-label">未绑定</span>
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
          <el-icon class="search-icon"><Search /></el-icon>
          <el-input
            v-model="query.keyword"
            placeholder="搜索小区名称或编码..."
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

      <!-- 表格卡片 -->
      <div class="table-card">
        <div class="card-header">
          <span class="card-title">
            <span class="dot dot-primary"></span>
            小区列表
          </span>
          <span class="card-count">共 {{ total }} 个小区</span>
        </div>
        <el-table :data="list" v-loading="loading" class="data-table" row-class-name="table-row">
          <el-table-column type="index" label="#" width="56" align="center" />
          <el-table-column label="小区信息" min-width="220" prop="name">
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
          <el-table-column prop="address" label="地址" min-width="220" show-overflow-tooltip>
            <template #default="{ row }">
              <span class="text-muted">{{ row.address || '-' }}</span>
            </template>
          </el-table-column>
          <el-table-column label="公众号绑定" width="220" align="center">
            <template #default="{ row }">
              <div v-if="row.wx_status === 1" class="bind-tag">
                <span class="bind-dot"></span>
                <span class="bind-text">{{ row.wx_app_id }}</span>
              </div>
              <div v-else class="unbind-tag">
                <span class="unbind-dot"></span>
                <span class="unbind-text">未绑定</span>
              </div>
            </template>
          </el-table-column>
          <el-table-column label="接入状态" width="120" align="center">
            <template #default="{ row }">
              <el-tag v-if="row.wx_status === 1" type="success" effect="light" round size="small">
                <el-icon style="margin-right: 4px;"><CircleCheckFilled /></el-icon>
                已接入
              </el-tag>
              <el-tag v-else type="info" effect="light" round size="small">
                <el-icon style="margin-right: 4px;"><WarningFilled /></el-icon>
                未接入
              </el-tag>
            </template>
          </el-table-column>
          <el-table-column label="操作" width="180" align="center" fixed="right">
            <template #default="{ row }">
              <el-button size="small" type="primary" link @click="openConfig(row)">
                <el-icon><Edit /></el-icon>
                配置
              </el-button>
              <el-button v-if="row.wx_status === 1" size="small" type="success" link @click="testConfig(row)">
                <el-icon><Connection /></el-icon>
                测试
              </el-button>
            </template>
          </el-table-column>
        </el-table>

        <!-- 空状态 -->
        <div v-if="!loading && list.length === 0" class="empty-state">
          <el-empty description="暂无小区数据" :image-size="120">
            <template #image>
              <el-icon :size="80" color="#cbd5e0"><Folder /></el-icon>
            </template>
          </el-empty>
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
            <span class="drawer-icon"><el-icon :size="22"><Setting /></el-icon></span>
            <div>
              <h3 class="drawer-title">公众号配置</h3>
              <p class="drawer-subtitle">{{ currentCommunity.name }} · {{ currentCommunity.code }}</p>
            </div>
          </div>
        </div>
      </template>

      <div class="drawer-body">
        <!-- 操作指引 -->
        <div class="guide-card">
          <div class="guide-icon-wrap">
            <el-icon :size="20"><InfoFilled /></el-icon>
          </div>
          <div class="guide-text">
            请前往 <a href="https://mp.weixin.qq.com" target="_blank" rel="noopener">微信公众平台</a> → 设置与开发 → 基本配置 获取以下参数
          </div>
        </div>

        <!-- Section 1: 基础参数 -->
        <div class="section-block">
          <div class="section-header">
            <span class="section-dot"></span>
            <span class="section-title">基础参数</span>
          </div>

          <el-form :model="form" label-position="top" class="section-form">
            <el-row :gutter="16">
              <el-col :span="12">
                <el-form-item>
                  <template #label>
                    <span class="form-label">
                      <el-icon class="label-icon"><Key /></el-icon> AppID
                    </span>
                  </template>
                  <el-input v-model="form.app_id" placeholder="wx 开头，如 wx1234567890abcdef" clearable>
                    <template #prefix><el-icon><Key /></el-icon></template>
                  </el-input>
                </el-form-item>
              </el-col>
              <el-col :span="12">
                <el-form-item>
                  <template #label>
                    <span class="form-label">
                      <el-icon class="label-icon"><Ticket /></el-icon> 原始ID
                    </span>
                  </template>
                  <el-input v-model="form.original_id" placeholder="gh_ 开头" clearable>
                    <template #prefix><el-icon><Ticket /></el-icon></template>
                  </el-input>
                </el-form-item>
              </el-col>
            </el-row>

            <el-form-item>
              <template #label>
                <span class="form-label">
                  <el-icon class="label-icon"><Lock /></el-icon> AppSecret（开发者密码）
                </span>
              </template>
              <el-input v-model="form.app_secret" placeholder="32 位字符串，留空则不修改" clearable show-password>
                <template #prefix><el-icon><Lock /></el-icon></template>
              </el-input>
              <div class="form-hint">若已设置，留空不修改原有密钥</div>
            </el-form-item>
          </el-form>
        </div>

        <!-- Section 2: 安全配置 -->
        <div class="section-block">
          <div class="section-header">
            <span class="section-dot"></span>
            <span class="section-title">安全配置</span>
          </div>

          <el-form :model="form" label-position="top" class="section-form">
            <el-row :gutter="16">
              <el-col :span="12">
                <el-form-item>
                  <template #label>
                    <span class="form-label">
                      <el-icon class="label-icon"><Link /></el-icon> 消息校验Token
                    </span>
                  </template>
                  <el-input v-model="form.token" placeholder="3-32 位字符" clearable show-password>
                    <template #prefix><el-icon><Link /></el-icon></template>
                  </el-input>
                  <div class="form-hint">服务器配置中的 Token，用于验证消息来源</div>
                </el-form-item>
              </el-col>
              <el-col :span="12">
                <el-form-item>
                  <template #label>
                    <span class="form-label">
                      <el-icon class="label-icon"><Key /></el-icon> 消息加密密钥
                    </span>
                  </template>
                  <el-input v-model="form.encoding_aes_key" placeholder="43 位 EncodingAESKey" clearable show-password>
                    <template #prefix><el-icon><Key /></el-icon></template>
                  </el-input>
                  <div class="form-hint">安全模式下使用</div>
                </el-form-item>
              </el-col>
            </el-row>
          </el-form>
        </div>

        <!-- Section 3: 模板消息 -->
        <div class="section-block">
          <div class="section-header">
            <span class="section-dot"></span>
            <span class="section-title">模板消息</span>
          </div>

          <div class="guide-card-sm">
            在公众号后台 <b>功能 → 模板消息</b> 中获取模板 ID。留空可在绑定 AppID 后补填。
          </div>

          <el-form :model="form" label-position="top" class="section-form">
            <el-form-item>
              <template #label>
                <span class="form-label">
                  <el-icon class="label-icon"><CircleCheckFilled /></el-icon> 缴费成功通知模板 ID
                </span>
              </template>
              <el-input v-model="form.template_pay_success" placeholder="模板消息 ID" clearable>
                <template #prefix><el-icon><Tickets /></el-icon></template>
              </el-input>
              <div class="form-hint">缴费金额、缴费时间、账单编号</div>
            </el-form-item>
            <el-form-item>
              <template #label>
                <span class="form-label">
                  <el-icon class="label-icon"><WarningFilled /></el-icon> 催缴通知模板 ID
                </span>
              </template>
              <el-input v-model="form.template_arrears" placeholder="模板消息 ID" clearable>
                <template #prefix><el-icon><Tickets /></el-icon></template>
              </el-input>
              <div class="form-hint">欠费金额、账单周期、缴费截止日期</div>
            </el-form-item>
            <el-form-item>
              <template #label>
                <span class="form-label">
                  <el-icon class="label-icon"><Wallet /></el-icon> 关联微信支付商户号
                </span>
              </template>
              <el-input v-model="form.mch_id" placeholder="微信支付商户号（可选）" clearable>
                <template #prefix><el-icon><Wallet /></el-icon></template>
              </el-input>
              <div class="form-hint">用于模板消息跳转缴费页面，可在支付配置中统一设置</div>
            </el-form-item>
          </el-form>
        </div>
      </div>

      <!-- 底部操作 -->
      <template #footer>
        <div class="drawer-footer">
          <el-button @click="handleTest" :disabled="!form.app_id" :loading="testing" :icon="Connection">
            测试接口
          </el-button>
          <div class="footer-spacer" />
          <el-button @click="drawerVisible = false">取消</el-button>
          <el-button type="primary" @click="handleSave" :loading="saving" :icon="Check">
            保存配置
          </el-button>
        </div>
      </template>
    </el-drawer>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import {
  Search, RefreshRight, Setting, Edit, Connection, OfficeBuilding,
  CircleCheckFilled, WarningFilled, Key, Ticket, Lock, Link, Wallet,
  Tickets, Check, InfoFilled, Folder,
} from '@element-plus/icons-vue'
import { apiGet, apiPost } from '@/utils/request'

const list = ref<any[]>([])
const total = ref(0)
const loading = ref(false)
const saving = ref(false)
const testing = ref(false)
const drawerVisible = ref(false)
const currentCommunity = ref<{ id: number; name: string; code: string }>({ id: 0, name: '', code: '' })

const query = reactive({ keyword: '', page: 1, limit: 12 })

const bindCount = computed(() => list.value.filter((r: any) => r.wx_status === 1).length)
const unbindCount = computed(() => list.value.filter((r: any) => r.wx_status !== 1).length)

const form = reactive<Record<string, any>>({
  community_id: 0,
  app_id: '', app_secret: '', token: '', encoding_aes_key: '', original_id: '',
  mch_id: '', template_pay_success: '', template_arrears: '',
})

function resetQuery() { query.keyword = ''; query.page = 1; loadData() }
function handleSearch() { query.page = 1; loadData() }

watch([() => query.page, () => query.limit], () => loadData())

async function loadData() {
  loading.value = true
  try {
    const r = await apiGet('/admin/wechat/configList', { ...query })
    list.value = r.data?.list || r.data || []
    total.value = r.count || r.data?.total || list.value.length
  } catch { list.value = []; total.value = 0 }
  finally { loading.value = false }
}

function resetForm() {
  Object.assign(form, {
    community_id: 0, app_id: '', app_secret: '', token: '', encoding_aes_key: '',
    original_id: '', mch_id: '', template_pay_success: '', template_arrears: '',
  })
}

async function openConfig(row: any) {
  resetForm()
  currentCommunity.value = { id: row.id, name: row.name, code: row.code }
  try {
    const r = await apiGet('/admin/wechat/configDetail', { community_id: row.id })
    const cfg = r.data?.config || null
    form.community_id = row.id
    if (cfg) {
      form.app_id = cfg.app_id || ''
      form.app_secret = cfg.app_secret || ''
      form.token = cfg.token || ''
      form.encoding_aes_key = cfg.encoding_aes_key || ''
      form.original_id = cfg.original_id || ''
      form.mch_id = cfg.mch_id || ''
      form.template_pay_success = cfg.template_pay_success || ''
      form.template_arrears = cfg.template_arrears || ''
    }
  } catch { /* 新配置 */ }
  drawerVisible.value = true
}

async function handleSave() {
  if (!form.community_id) return ElMessage.warning('请先选择小区')
  if (!form.app_id) return ElMessage.warning('请填写公众号 AppID')
  saving.value = true
  try {
    await apiPost('/admin/wechat/configSave', { ...form })
    ElMessage.success('保存成功')
    drawerVisible.value = false
    loadData()
  } finally { saving.value = false }
}

async function handleTest() {
  if (!form.community_id) return ElMessage.warning('请先保存配置')
  testing.value = true
  try {
    const r = await apiGet('/admin/wechat/configTest', { community_id: form.community_id })
    ElMessage.success({ message: r.msg || '接口测试成功', duration: 3000 })
  } finally { testing.value = false }
}

function testConfig(row: any) {
  ElMessageBox.confirm(`确定测试「${row.name}」的公众号配置吗？`, '测试公众号接口', {
    confirmButtonText: '开始测试',
    type: 'info',
    confirmButtonClass: 'el-button--primary',
  }).then(async () => {
    try {
      const r = await apiGet('/admin/wechat/configTest', { community_id: row.id })
      ElMessage.success({ message: r.msg || '接口测试成功', duration: 3000 })
    } catch { /* handled */ }
  }).catch(() => { /* cancelled */ })
}

onMounted(loadData)
</script>

<style scoped>
/* ========== Hero ========== */
.wechat-config-page { min-height: 100%; }
.hero-section {
  position: relative;
  background: linear-gradient(135deg, #07c160 0%, #06ad56 40%, #05944a 100%);
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
  width: 56px; height: 56px;
  border-radius: 16px;
  background: rgba(255,255,255,0.2);
  backdrop-filter: blur(8px);
  display: flex; align-items: center; justify-content: center;
}
.hero-title { font-size: 22px; font-weight: 700; margin: 0; line-height: 1.2; }
.hero-desc { font-size: 13px; opacity: 0.85; margin: 4px 0 0; }
.hero-stats { display: flex; align-items: center; gap: 24px; }
.stat-item { text-align: center; }
.stat-value { display: block; font-size: 26px; font-weight: 700; }
.stat-value.stat-success { color: #d4ffda; }
.stat-value.stat-warning { color: #ffe1a0; }
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
.search-icon { color: #a0aec0; margin-right: 8px; }
.search-input { flex: 1; }
.search-input :deep(.el-input__wrapper) {
  box-shadow: none !important; border-radius: 8px; background: #f7f8fa;
}
.search-input :deep(.el-input__wrapper):hover,
.search-input :deep(.el-input__wrapper.is-focus) {
  box-shadow: 0 0 0 1px #07c160 inset !important; background: #fff;
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
.dot-primary { background: #07c160; box-shadow: 0 0 6px rgba(7,193,96,0.4); }
.card-count { font-size: 12px; color: #a0aec0; }

/* ========== Table ========== */
.data-table { width: 100%; }
.data-table :deep(.table-row) { transition: background 0.2s; }
.data-table :deep(.table-row:hover) { background: #f6fffa !important; }
.data-table :deep(.el-table__header th) {
  background: #fafbfc !important; font-weight: 600; font-size: 12px; color: #718096;
  text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid #e2e8f0;
}

.cell-community { display: flex; align-items: center; gap: 12px; }
.community-avatar {
  width: 40px; height: 40px; border-radius: 10px;
  background: linear-gradient(135deg, #e8f5e9, #c8e6c9);
  color: #07c160; display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}
.community-info { display: flex; flex-direction: column; }
.community-name { font-weight: 600; font-size: 14px; }
.community-code { font-size: 11px; color: #a0aec0; }

.text-muted { color: #a0aec0; font-size: 13px; }

/* Bind status */
.bind-tag { display: inline-flex; align-items: center; gap: 6px; }
.bind-dot { width: 8px; height: 8px; border-radius: 50%; background: #07c160; box-shadow: 0 0 6px rgba(7,193,96,0.4); }
.bind-text { font-family: 'Courier New', monospace; font-size: 12px; color: #07c160; font-weight: 500; }
.unbind-tag { display: inline-flex; align-items: center; gap: 6px; }
.unbind-dot { width: 8px; height: 8px; border-radius: 50%; background: #cbd5e0; }
.unbind-text { font-size: 12px; color: #a0aec0; }

/* Empty */
.empty-state { padding: 60px 0; }

/* Pagination */
.pagination-wrap { padding: 12px 20px 16px; display: flex; justify-content: flex-end; border-top: 1px solid #f0f2f5; }

/* ========== Drawer ========== */
.config-drawer :deep(.el-drawer__header) { margin-bottom: 0; padding: 20px 24px; border-bottom: 1px solid #f0f2f5; }
.config-drawer :deep(.el-drawer__body) { padding: 0; }
.config-drawer :deep(.el-drawer__footer) { padding: 16px 24px; border-top: 1px solid #f0f2f5; }

.drawer-header { display: flex; align-items: center; }
.drawer-title-row { display: flex; align-items: center; gap: 14px; }
.drawer-icon {
  width: 44px; height: 44px; border-radius: 12px;
  background: linear-gradient(135deg, #e8f5e9, #c8e6c9);
  color: #07c160; display: flex; align-items: center; justify-content: center;
}
.drawer-title { font-size: 18px; font-weight: 700; margin: 0; line-height: 1.3; }
.drawer-subtitle { font-size: 12px; color: #a0aec0; margin: 2px 0 0; }

.drawer-body { padding: 20px 24px; }

/* Guide card */
.guide-card {
  display: flex; align-items: flex-start; gap: 10px;
  padding: 14px 16px;
  background: linear-gradient(135deg, #f0fdf4, #ecfdf5);
  border-radius: 10px; border: 1px solid #bbf7d0;
  margin-bottom: 20px; font-size: 13px; color: #166534;
}
.guide-card a { color: #059669; font-weight: 600; text-decoration: none; }
.guide-card a:hover { text-decoration: underline; }
.guide-icon-wrap { color: #22c55e; margin-top: 1px; flex-shrink: 0; }

.guide-card-sm {
  padding: 10px 14px; background: #f8fafc; border-radius: 8px;
  border: 1px solid #e2e8f0; font-size: 12px; color: #64748b; margin-bottom: 16px;
}

/* Section */
.section-block {
  margin-bottom: 16px;
  padding: 0 0 8px;
}
.section-header {
  display: flex; align-items: center; gap: 10px;
  margin-bottom: 14px; padding-bottom: 10px;
  border-bottom: 1px solid #f0f2f5;
}
.section-dot {
  width: 4px; height: 18px; border-radius: 2px;
  background: linear-gradient(180deg, #07c160, #06ad56);
}
.section-title { font-size: 14px; font-weight: 700; color: #1a202c; }
.section-form { margin-top: 4px; }

/* Form */
.form-label { display: inline-flex; align-items: center; gap: 6px; font-weight: 600; font-size: 13px; color: #4a5568; }
.label-icon { font-size: 15px; color: #07c160; }
.form-hint { font-size: 11px; color: #a0aec0; margin-top: 3px; line-height: 1.3; }

/* Drawer Footer */
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
