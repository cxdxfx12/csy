<template>
  <div class="push-page">
    <!-- 页面头部 -->
    <div class="page-hero">
      <div class="hero-left">
        <div class="hero-icon"><el-icon :size="28"><Iphone /></el-icon></div>
        <div class="hero-text">
          <h2>推送设备管理</h2>
          <p>管理业主端APP、小程序等消息推送终端，保障通知精准触达</p>
        </div>
      </div>
      <div class="hero-stats">
        <div class="hero-stat">
          <span class="hs-num" style="color:#667eea">{{ stats.total }}</span>
          <span class="hs-label">注册设备</span>
        </div>
        <div class="hs-divider" />
        <div class="hero-stat">
          <span class="hs-num" style="color:#48bb78">{{ stats.online }}</span>
          <span class="hs-label">在线设备</span>
        </div>
        <div class="hs-divider" />
        <div class="hero-stat">
          <span class="hs-num" style="color:#fc8181">{{ stats.offline }}</span>
          <span class="hs-label">离线设备</span>
        </div>
      </div>
    </div>

    <!-- 平台分布卡片 -->
    <div class="platform-cards">
      <div class="plat-card" v-for="p in platforms" :key="p.key" :style="{ borderTopColor: p.color }">
        <div class="plat-icon" :style="{ background: p.bg, color: p.color }">
          <el-icon :size="22"><component :is="p.icon" /></el-icon>
        </div>
        <div class="plat-info">
          <span class="plat-count">{{ p.count }}</span>
          <span class="plat-name">{{ p.label }}</span>
        </div>
        <el-progress :percentage="stats.total ? Math.round(p.count / stats.total * 100) : 0" :color="p.color" :stroke-width="4" :show-text="false" />
      </div>
    </div>

    <!-- 搜索栏 -->
    <div class="search-bar">
      <el-form :model="query" inline>
        <el-form-item>
          <el-input v-model="query.keyword" placeholder="搜索设备Token / 用户ID..." clearable style="width:280px" prefix-icon="Search" @keyup.enter="loadData" />
        </el-form-item>
        <el-form-item>
          <el-select v-model="query.platform" placeholder="设备平台" clearable style="width:140px">
            <el-option label="iOS" value="ios" />
            <el-option label="Android" value="android" />
            <el-option label="Web" value="web" />
            <el-option label="小程序" value="miniapp" />
          </el-select>
        </el-form-item>
        <el-form-item>
          <el-select v-model="query.status" placeholder="在线状态" clearable style="width:120px">
            <el-option label="在线" :value="1" />
            <el-option label="离线" :value="0" />
          </el-select>
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="loadData"><el-icon><Search /></el-icon> 搜索</el-button>
          <el-button @click="resetQuery">重置</el-button>
        </el-form-item>
      </el-form>
    </div>

    <!-- 设备列表 -->
    <el-card shadow="never" class="table-card">
      <template #header>
        <div class="card-header-bar">
          <span><el-icon><List /></el-icon> 设备列表</span>
          <el-button type="primary" size="small" @click="openForm()"><el-icon><Plus /></el-icon> 注册设备</el-button>
        </div>
      </template>
      <el-table :data="list" v-loading="loading" stripe class="modern-table" row-key="id">
        <el-table-column type="index" label="#" width="55" />
        <el-table-column label="设备信息" min-width="220">
          <template #default="{ row }">
            <div class="device-info-cell">
              <div class="device-platform-badge" :class="'plat-' + row.platform">
                {{ platformLabel(row.platform) }}
              </div>
              <div class="device-token" :title="row.device_token">{{ row.device_token || '-' }}</div>
            </div>
          </template>
        </el-table-column>
        <el-table-column label="用户" width="130" align="center">
          <template #default="{ row }">
            <el-tag effect="plain" size="small">{{ row.user_type || '业主' }} #{{ row.user_id }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="app_version" label="版本" width="100" align="center">
          <template #default="{ row }">
            <span class="version-tag">{{ row.app_version || '-' }}</span>
          </template>
        </el-table-column>
        <el-table-column label="在线状态" width="100" align="center">
          <template #default="{ row }">
            <span class="status-dot" :class="row.status == 1 ? 'online' : 'offline'" />
            <span>{{ row.status == 1 ? '在线' : '离线' }}</span>
          </template>
        </el-table-column>
        <el-table-column label="最后活跃" width="170" sortable prop="last_active">
          <template #default="{ row }">
            <span class="time-text">{{ row.last_active || '-' }}</span>
          </template>
        </el-table-column>
        <el-table-column label="注册时间" width="170" sortable prop="create_time">
          <template #default="{ row }">
            <span class="time-text">{{ row.create_time || '-' }}</span>
          </template>
        </el-table-column>
        <el-table-column label="操作" width="140" align="center" fixed="right">
          <template #default="{ row }">
            <el-button size="small" type="primary" link @click="openForm(row)"><el-icon><Edit /></el-icon></el-button>
            <el-popconfirm title="确定注销此设备？" @confirm="handleDelete(row)">
              <template #reference>
                <el-button size="small" type="danger" link><el-icon><Delete /></el-icon></el-button>
              </template>
            </el-popconfirm>
          </template>
        </el-table-column>
      </el-table>
      <div class="pagination-wrap">
        <el-pagination v-model:current-page="query.page" v-model:page-size="query.limit" :total="total" :page-sizes="[15,30,50,100]" layout="total,sizes,prev,pager,next" @update:current-page="loadData" @update:page-size="loadData" />
      </div>
    </el-card>

    <!-- 设备注册弹窗 -->
    <el-dialog v-model="dialogVisible" :title="editId ? '编辑设备信息' : '注册推送设备'" width="560px" destroy-on-close :close-on-click-modal="false">
      <el-form :model="form" ref="formRef" label-width="100px" label-position="top" class="device-form">
        <el-row :gutter="16">
          <el-col :span="12">
            <el-form-item label="用户类型">
              <el-select v-model="form.user_type" style="width:100%">
                <el-option label="业主" value="owner" />
                <el-option label="员工" value="staff" />
                <el-option label="管理员" value="admin" />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="用户ID">
              <el-input-number v-model="form.user_id" :min="0" style="width:100%" />
            </el-form-item>
          </el-col>
        </el-row>
        <el-row :gutter="16">
          <el-col :span="12">
            <el-form-item label="设备平台">
              <el-select v-model="form.platform" style="width:100%">
                <el-option label="iOS" value="ios" />
                <el-option label="Android" value="android" />
                <el-option label="Web" value="web" />
                <el-option label="小程序" value="miniapp" />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="在线状态">
              <el-switch v-model="form.status" :active-value="1" :inactive-value="0" active-text="在线" inactive-text="离线" />
            </el-form-item>
          </el-col>
        </el-row>
        <el-form-item label="设备Token">
          <el-input v-model="form.device_token" type="textarea" :rows="2" placeholder="推送服务商分配的设备唯一标识Token" />
        </el-form-item>
        <el-form-item label="APP版本">
          <el-input v-model="form.app_version" placeholder="如：1.2.3" />
        </el-form-item>
        <el-form-item label="最后活跃时间">
          <el-date-picker v-model="form.last_active" type="datetime" placeholder="选择最后活跃时间" value-format="YYYY-MM-DD HH:mm:ss" style="width:100%" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" @click="handleSubmit" :loading="submitting">{{ editId ? '保存修改' : '注册设备' }}</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { apiGet, apiPost } from '@/utils/request'
import { Iphone, Search, Plus, Edit, Delete, List, Monitor, Cellphone, Platform } from '@element-plus/icons-vue'

const list = ref<any[]>([])
const total = ref(0)
const loading = ref(false)
const dialogVisible = ref(false)
const editId = ref(0)
const submitting = ref(false)
const formRef = ref()

const query = reactive({ page: 1, limit: 15, keyword: '', platform: '', status: '' as any })
const form = reactive<any>({ user_type: 'owner', user_id: 0, platform: 'android', device_token: '', app_version: '', status: 1, last_active: '' })

const stats = reactive({ total: 0, online: 0, offline: 0 })

const platforms = computed(() => [
  { key: 'ios', label: 'iOS', icon: 'Iphone', color: '#6366f1', bg: '#eef0ff', count: list.value.filter(d => d.platform === 'ios').length },
  { key: 'android', label: 'Android', icon: 'Cellphone', color: '#48bb78', bg: '#e6fffa', count: list.value.filter(d => d.platform === 'android').length },
  { key: 'web', label: 'Web', icon: 'Monitor', color: '#ed8936', bg: '#fffaf0', count: list.value.filter(d => d.platform === 'web').length },
  { key: 'miniapp', label: '小程序', icon: 'Platform', color: '#38bdf8', bg: '#ecfeff', count: list.value.filter(d => d.platform === 'miniapp').length },
])

function platformLabel(v: string) {
  const m: Record<string, string> = { ios: 'iOS', android: 'Android', web: 'Web', miniapp: '小程序' }
  return m[v] || v || '未知'
}

async function loadData() {
  loading.value = true
  try {
    const res = await apiGet('/admin/system/pushDeviceList', {
      page: query.page, limit: query.limit,
      keyword: query.keyword, platform: query.platform, status: query.status,
    })
    if (res.code === 0) {
      list.value = res.data?.list || res.data || []
      total.value = res.data?.total || res.count || 0
    } else { list.value = []; total.value = 0 }
    stats.total = total.value
    stats.online = (list.value || []).filter((d: any) => d.status == 1).length
    stats.offline = (list.value || []).filter((d: any) => d.status == 0).length
  } catch { list.value = []; total.value = 0 }
  finally { loading.value = false }
}

function resetQuery() { query.keyword = ''; query.platform = ''; query.status = ''; query.page = 1; loadData() }

function openForm(row?: any) {
  if (row) {
    editId.value = row.id
    Object.assign(form, {
      user_type: row.user_type || 'owner',
      user_id: row.user_id || 0,
      platform: row.platform || 'android',
      device_token: row.device_token || '',
      app_version: row.app_version || '',
      status: row.status ?? 1,
      last_active: row.last_active || '',
    })
  } else {
    editId.value = 0
    Object.assign(form, { user_type: 'owner', user_id: 0, platform: 'android', device_token: '', app_version: '', status: 1, last_active: '' })
  }
  dialogVisible.value = true
}

async function handleSubmit() {
  if (!form.device_token) return ElMessage.warning('请输入设备Token')
  submitting.value = true
  try {
    const url = editId.value ? '/admin/system/pushDeviceEdit' : '/admin/system/pushDeviceAdd'
    const res = await apiPost(url, { ...form, id: editId.value || undefined })
    if (res.code === 0) { ElMessage.success(res.msg || '保存成功'); dialogVisible.value = false; loadData() }
  } finally { submitting.value = false }
}

async function handleDelete(row: any) {
  const res = await apiPost('/admin/system/pushDeviceDelete', { id: row.id })
  if (res.code === 0) { ElMessage.success('已注销设备'); loadData() }
}

onMounted(() => loadData())
</script>

<style scoped>
.push-page { max-width: 1400px; margin: 0 auto; }
.page-hero {
  display: flex; align-items: center; justify-content: space-between;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 14px; padding: 28px 32px; margin-bottom: 20px; color: #fff; flex-wrap: wrap; gap: 20px;
}
.hero-left { display: flex; align-items: center; gap: 16px; }
.hero-icon { width: 56px; height: 56px; background: rgba(255,255,255,.2); border-radius: 14px; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(4px); }
.hero-text h2 { margin: 0 0 4px; font-size: 22px; font-weight: 700; }
.hero-text p { margin: 0; font-size: 13px; opacity: .8; }
.hero-stats { display: flex; align-items: center; gap: 28px; }
.hero-stat { text-align: center; }
.hs-num { font-size: 32px; font-weight: 800; display: block; line-height: 1.1; }
.hs-label { font-size: 12px; opacity: .7; margin-top: 2px; display: block; }
.hs-divider { width: 1px; height: 40px; background: rgba(255,255,255,.25); }

.platform-cards { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 20px; }
.plat-card {
  background: #fff; border-radius: 12px; padding: 20px;
  border: 1px solid #e2e8f0; border-top: 3px solid; display: flex; flex-direction: column; gap: 14px;
}
.plat-icon { width: 44px; height: 44px; border-radius: 10px; display: flex; align-items: center; justify-content: center; }
.plat-info { display: flex; flex-direction: column; }
.plat-count { font-size: 26px; font-weight: 800; color: #2d3748; }
.plat-name { font-size: 13px; color: #a0aec0; margin-top: 2px; }

.search-bar { background: #fff; border-radius: 10px; padding: 16px 20px; margin-bottom: 16px; border: 1px solid #e2e8f0; }
.table-card { border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden; }
.card-header-bar { display: flex; align-items: center; justify-content: space-between; font-weight: 600; }
.pagination-wrap { padding: 16px 0 0; display: flex; justify-content: flex-end; }

.device-info-cell { display: flex; align-items: center; gap: 10px; }
.device-platform-badge {
  font-size: 11px; font-weight: 600; padding: 2px 8px; border-radius: 4px; text-transform: uppercase; white-space: nowrap;
}
.plat-ios { background: #eef0ff; color: #6366f1; }
.plat-android { background: #e6fffa; color: #48bb78; }
.plat-web { background: #fffaf0; color: #ed8936; }
.plat-miniapp { background: #ecfeff; color: #38bdf8; }
.device-token { font-family: 'SF Mono','Fira Code',monospace; font-size: 12px; color: #4a5568; max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

.status-dot { display: inline-block; width: 7px; height: 7px; border-radius: 50%; margin-right: 6px; }
.status-dot.online { background: #48bb78; box-shadow: 0 0 6px rgba(72,187,120,.5); }
.status-dot.offline { background: #cbd5e0; }

.version-tag { font-family: monospace; background: #f7fafc; padding: 2px 10px; border-radius: 4px; font-size: 12px; color: #4a5568; }
.time-text { font-size: 13px; color: #718096; }

.device-form { padding-right: 10px; }
.modern-table :deep(th) { background: #f8fafc !important; font-weight: 600; color: #2d3748; font-size: 13px; }
.modern-table :deep(td) { font-size: 13px; }
</style>
