<template>
  <div class="vendor-page">
    <!-- 页面头部 -->
    <div class="page-hero">
      <div class="hero-left">
        <div class="hero-icon"><el-icon :size="28"><Connection /></el-icon></div>
        <div class="hero-text">
          <h2>服务商管理中心</h2>
          <p>统一管理物业各类外包服务供应商，涵盖维保、保洁、安保、绿化等多领域</p>
        </div>
      </div>
      <div class="hero-summary">
        <div class="hs-item">
          <span class="hs-num">{{ stats.total }}</span>
          <span class="hs-label">合作服务商</span>
        </div>
        <div class="hs-divider" />
        <div class="hs-item">
          <span class="hs-num" style="color:#48bb78">{{ stats.active }}</span>
          <span class="hs-label">合同有效</span>
        </div>
        <div class="hs-divider" />
        <div class="hs-item">
          <span class="hs-num" style="color:#e53e3e">{{ stats.expiring }}</span>
          <span class="hs-label">即将到期</span>
        </div>
      </div>
    </div>

    <!-- 服务类别概览 -->
    <div class="vendor-type-grid">
      <div class="vt-card" v-for="vt in vendorTypes" :key="vt.key"
        :class="{ active: query.vendor_type === vt.key }"
        @click="query.vendor_type = query.vendor_type === vt.key ? '' : vt.key; loadData()">
        <div class="vt-icon" :style="{ background: vt.bg, color: vt.color }">
          <el-icon :size="20"><component :is="vt.icon" /></el-icon>
        </div>
        <span class="vt-label">{{ vt.label }}</span>
        <span class="vt-count">{{ vtCount(vt.key) }}</span>
      </div>
    </div>

    <!-- 搜索栏 -->
    <div class="search-bar">
      <el-form :model="query" inline>
        <el-form-item>
          <el-input v-model="query.keyword" placeholder="公司名称 / 联系人 / 电话..." clearable style="width:300px" prefix-icon="Search" @keyup.enter="loadData" />
        </el-form-item>
        <el-form-item>
          <el-select v-model="query.community_id" placeholder="所属小区" clearable filterable style="width:180px">
            <el-option v-for="c in communities" :key="c.id" :label="c.name" :value="c.id" />
          </el-select>
        </el-form-item>
        <el-form-item>
          <el-select v-model="query.status" placeholder="合同状态" clearable style="width:130px">
            <el-option label="合作中" :value="1" />
            <el-option label="已到期" :value="0" />
            <el-option label="已终止" :value="2" />
          </el-select>
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="loadData"><el-icon><Search /></el-icon> 搜索</el-button>
          <el-button @click="resetQuery">重置</el-button>
        </el-form-item>
      </el-form>
    </div>

    <!-- 服务商列表 -->
    <el-card shadow="never" class="table-card">
      <template #header>
        <div class="card-header-bar">
          <span><el-icon><List /></el-icon> 服务商列表</span>
          <el-button type="primary" size="small" @click="openForm()"><el-icon><Plus /></el-icon> 新增服务商</el-button>
        </div>
      </template>
      <el-table :data="list" v-loading="loading" stripe class="modern-table" row-key="id">
        <el-table-column type="index" label="#" width="55" />
        <el-table-column label="服务商信息" min-width="240">
          <template #default="{ row }">
            <div class="vendor-info-cell">
              <el-avatar :size="36" :style="{ background: vtColor(row.vendor_type) }">
                {{ (row.company_name || 'S').charAt(0) }}
              </el-avatar>
              <div class="vendor-detail">
                <div class="vendor-name">{{ row.company_name || '未命名' }}</div>
                <div class="vendor-contact">{{ row.contact_person || '-' }} · {{ row.mobile || row.phone || '-' }}</div>
              </div>
            </div>
          </template>
        </el-table-column>
        <el-table-column label="服务类别" width="120" align="center">
          <template #default="{ row }">
            <el-tag :type="vtTagType(row.vendor_type)" effect="light" round size="small">{{ vtLabel(row.vendor_type) }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="service_scope" label="服务范围" min-width="180" show-overflow-tooltip>
          <template #default="{ row }">
            <span style="font-size:13px;color:#4a5568">{{ row.service_scope || '-' }}</span>
          </template>
        </el-table-column>
        <el-table-column label="联系信息" width="170">
          <template #default="{ row }">
            <div class="contact-links">
              <span v-if="row.mobile" class="contact-item"><el-icon><Phone /></el-icon> {{ row.mobile }}</span>
              <span v-if="row.email" class="contact-item"><el-icon><Message /></el-icon> {{ row.email }}</span>
            </div>
          </template>
        </el-table-column>
        <el-table-column label="合同期限" width="200">
          <template #default="{ row }">
            <div class="contract-cell">
              <div class="cc-date"><span class="cc-label">起</span> {{ row.contract_start || '-' }}</div>
              <div class="cc-date"><span class="cc-label">止</span> {{ row.contract_end || '-' }}</div>
              <el-progress :percentage="contractProgress(row)" :color="contractProgressColor(row)" :stroke-width="3" :show-text="false" style="margin-top:4px" />
              <span class="cc-days" v-if="daysRemaining(row) !== null"
                :style="{ color: daysRemaining(row)! < 30 ? '#e53e3e' : daysRemaining(row)! < 90 ? '#ed8936' : '#48bb78' }">
                {{ daysRemaining(row) }} 天后到期
              </span>
            </div>
          </template>
        </el-table-column>
        <el-table-column label="状态" width="90" align="center">
          <template #default="{ row }">
            <span class="status-dot" :class="statusClass(row)" />
            {{ statusLabel(row) }}
          </template>
        </el-table-column>
        <el-table-column label="操作" width="120" align="center" fixed="right">
          <template #default="{ row }">
            <el-button size="small" type="primary" link @click="openForm(row)"><el-icon><Edit /></el-icon></el-button>
            <el-popconfirm title="确定删除此服务商？" @confirm="handleDelete(row)">
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

    <!-- 服务商弹窗 -->
    <el-dialog v-model="dialogVisible" :title="editId ? '编辑服务商信息' : '新增服务商'" width="700px" destroy-on-close :close-on-click-modal="false">
      <el-form :model="form" ref="formRef" label-width="110px" label-position="top" class="vendor-form">
        <el-row :gutter="16">
          <el-col :span="16">
            <el-form-item label="公司名称" required>
              <el-input v-model="form.company_name" placeholder="请输入公司名称" />
            </el-form-item>
          </el-col>
          <el-col :span="8">
            <el-form-item label="服务类别" required>
              <el-select v-model="form.vendor_type" style="width:100%">
                <el-option label="电梯维保" value="elevator" />
                <el-option label="保洁服务" value="cleaning" />
                <el-option label="安保服务" value="security" />
                <el-option label="绿化养护" value="landscaping" />
                <el-option label="消防维保" value="fire" />
                <el-option label="水电维修" value="plumbing" />
                <el-option label="暖通空调" value="hvac" />
                <el-option label="弱电智能" value="lowvoltage" />
                <el-option label="消杀服务" value="pest" />
                <el-option label="垃圾清运" value="waste" />
                <el-option label="停车系统" value="parking" />
                <el-option label="其他服务" value="other" />
              </el-select>
            </el-form-item>
          </el-col>
        </el-row>
        <el-row :gutter="16">
          <el-col :span="8">
            <el-form-item label="所属小区">
              <el-select v-model="form.community_id" placeholder="全部小区" clearable filterable style="width:100%">
                <el-option v-for="c in communities" :key="c.id" :label="c.name" :value="c.id" />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="8">
            <el-form-item label="联系人">
              <el-input v-model="form.contact_person" placeholder="姓名" />
            </el-form-item>
          </el-col>
          <el-col :span="8">
            <el-form-item label="手机号码">
              <el-input v-model="form.mobile" placeholder="11位手机号" />
            </el-form-item>
          </el-col>
        </el-row>
        <el-row :gutter="16">
          <el-col :span="8">
            <el-form-item label="固定电话">
              <el-input v-model="form.phone" placeholder="座机号码" />
            </el-form-item>
          </el-col>
          <el-col :span="8">
            <el-form-item label="邮箱">
              <el-input v-model="form.email" placeholder="Email地址" />
            </el-form-item>
          </el-col>
          <el-col :span="8">
            <el-form-item label="微信">
              <el-input v-model="form.wechat" placeholder="微信号" />
            </el-form-item>
          </el-col>
        </el-row>
        <el-form-item label="联系地址">
          <el-input v-model="form.address" placeholder="详细地址" />
        </el-form-item>
        <el-form-item label="服务范围">
          <el-input v-model="form.service_scope" type="textarea" :rows="2" placeholder="描述该服务商的具体服务内容和范围" />
        </el-form-item>
        <el-row :gutter="16">
          <el-col :span="12">
            <el-form-item label="合同开始">
              <el-date-picker v-model="form.contract_start" type="date" placeholder="选择日期" value-format="YYYY-MM-DD" style="width:100%" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="合同截止">
              <el-date-picker v-model="form.contract_end" type="date" placeholder="选择日期" value-format="YYYY-MM-DD" style="width:100%" />
            </el-form-item>
          </el-col>
        </el-row>
        <el-row :gutter="16">
          <el-col :span="12">
            <el-form-item label="备注">
              <el-input v-model="form.remark" placeholder="备注信息" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="合作状态">
              <el-radio-group v-model="form.status">
                <el-radio :value="1">合作中</el-radio>
                <el-radio :value="0">已到期</el-radio>
                <el-radio :value="2">已终止</el-radio>
              </el-radio-group>
            </el-form-item>
          </el-col>
        </el-row>
      </el-form>
      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" @click="handleSubmit" :loading="submitting">{{ editId ? '保存修改' : '确认新增' }}</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { apiGet, apiPost } from '@/utils/request'
import { Connection, Search, Plus, Edit, Delete, List, Phone, Message,
  Service, Brush, Lock, Sunny, WarnTriangleFilled, Wrench, WindPower, Monitor, MagicStick, DeleteFilled, Van, OfficeBuilding } from '@element-plus/icons-vue'

const list = ref<any[]>([])
const total = ref(0)
const loading = ref(false)
const dialogVisible = ref(false)
const editId = ref(0)
const submitting = ref(false)
const formRef = ref()
const communities = ref<any[]>([])

const query = reactive({ page: 1, limit: 15, keyword: '', vendor_type: '', community_id: '' as any, status: '' as any })
const form = reactive<any>({ community_id: null, vendor_type: 'cleaning', company_name: '', contact_person: '', mobile: '', phone: '', email: '', wechat: '', address: '', service_scope: '', contract_start: '', contract_end: '', remark: '', status: 1 })

const stats = reactive({ total: 0, active: 0, expiring: 0 })

const vendorTypes = [
  { key: 'elevator', label: '电梯维保', icon: 'Service', color: '#6366f1', bg: '#eef0ff' },
  { key: 'cleaning', label: '保洁服务', icon: 'Brush', color: '#48bb78', bg: '#e6fffa' },
  { key: 'security', label: '安保服务', icon: 'Lock', color: '#1e40af', bg: '#eff6ff' },
  { key: 'landscaping', label: '绿化养护', icon: 'Sunny', color: '#16a34a', bg: '#f0fdf4' },
  { key: 'fire', label: '消防维保', icon: 'WarnTriangleFilled', color: '#e53e3e', bg: '#fff5f5' },
  { key: 'plumbing', label: '水电维修', icon: 'Wrench', color: '#0ea5e9', bg: '#f0f9ff' },
  { key: 'hvac', label: '暖通空调', icon: 'WindPower', color: '#8b5cf6', bg: '#f5f3ff' },
  { key: 'lowvoltage', label: '弱电智能', icon: 'Monitor', color: '#db2777', bg: '#fdf2f8' },
  { key: 'pest', label: '消杀服务', icon: 'MagicStick', color: '#d97706', bg: '#fffbeb' },
  { key: 'waste', label: '垃圾清运', icon: 'DeleteFilled', color: '#78716c', bg: '#fafaf9' },
  { key: 'parking', label: '停车系统', icon: 'Van', color: '#0891b2', bg: '#ecfeff' },
  { key: 'other', label: '其他', icon: 'OfficeBuilding', color: '#a0aec0', bg: '#f7fafc' },
]

function vtLabel(v: string) {
  const t = vendorTypes.find(t => t.key === v)
  return t?.label || v || '未知'
}
function vtTagType(v: string) {
  const m: Record<string, string> = { elevator: '', cleaning: 'success', security: '', landscaping: 'success', fire: 'danger', plumbing: '', hvac: '', lowvoltage: 'danger', pest: 'warning', waste: 'info', parking: '', other: 'info' }
  return m[v] || 'info'
}
function vtColor(v: string) {
  const t = vendorTypes.find(t => t.key === v)
  return t?.color || '#a0aec0'
}
function vtCount(key: string) { return list.value.filter((v: any) => v.vendor_type === key).length }

function contractProgress(row: any) {
  if (!row.contract_start || !row.contract_end) return 0
  const start = new Date(row.contract_start).getTime()
  const end = new Date(row.contract_end).getTime()
  const now = Date.now()
  if (now < start) return 0
  if (now > end) return 100
  return Math.round((now - start) / (end - start) * 100)
}
function contractProgressColor(row: any) {
  const days = daysRemaining(row)
  if (days === null) return '#cbd5e0'
  if (days < 30) return '#e53e3e'
  if (days < 90) return '#ed8936'
  return '#48bb78'
}
function daysRemaining(row: any) {
  if (!row.contract_end) return null
  const end = new Date(row.contract_end).getTime()
  const now = Date.now()
  return Math.ceil((end - now) / (1000 * 60 * 60 * 24))
}

function statusLabel(row: any) {
  const days = daysRemaining(row)
  if (row.status == 2) return '已终止'
  if (row.status == 0 || (days !== null && days < 0)) return '已到期'
  return '合作中'
}
function statusClass(row: any) {
  const label = statusLabel(row)
  if (label === '已终止') return 'terminated'
  if (label === '已到期') return 'expired'
  return 'active'
}

async function loadData() {
  loading.value = true
  try {
    const res = await apiGet('/admin/system/serviceVendorList', {
      page: query.page, limit: query.limit,
      keyword: query.keyword, vendor_type: query.vendor_type,
      community_id: query.community_id, status: query.status,
    })
    if (res.code === 0) { list.value = res.data?.list || res.data || []; total.value = res.data?.total || res.count || 0 }
    else { list.value = []; total.value = 0 }
    stats.total = total.value
    stats.active = (list.value || []).filter((v: any) => v.status == 1 && daysRemaining(v) !== null && daysRemaining(v)! > 0).length
    stats.expiring = (list.value || []).filter((v: any) => daysRemaining(v) !== null && daysRemaining(v)! >= 0 && daysRemaining(v)! < 30).length
  } catch { list.value = []; total.value = 0 }
  finally { loading.value = false }
}

async function loadCommunities() {
  try { const r = await apiGet('/admin/community/list', { limit: 999 }); communities.value = r.data?.list || r.data || [] } catch {}
}

function resetQuery() { query.keyword = ''; query.vendor_type = ''; query.community_id = ''; query.status = ''; query.page = 1; loadData() }

function openForm(row?: any) {
  if (row) {
    editId.value = row.id
    Object.assign(form, {
      community_id: row.community_id || null, vendor_type: row.vendor_type || 'cleaning',
      company_name: row.company_name || '', contact_person: row.contact_person || '',
      mobile: row.mobile || '', phone: row.phone || '', email: row.email || '', wechat: row.wechat || '',
      address: row.address || '', service_scope: row.service_scope || '',
      contract_start: row.contract_start || '', contract_end: row.contract_end || '',
      remark: row.remark || '', status: row.status ?? 1,
    })
  } else {
    editId.value = 0
    Object.assign(form, { community_id: null, vendor_type: 'cleaning', company_name: '', contact_person: '', mobile: '', phone: '', email: '', wechat: '', address: '', service_scope: '', contract_start: '', contract_end: '', remark: '', status: 1 })
  }
  dialogVisible.value = true
}

async function handleSubmit() {
  if (!form.company_name) return ElMessage.warning('请输入公司名称')
  if (!form.vendor_type) return ElMessage.warning('请选择服务类别')
  submitting.value = true
  try {
    const url = editId.value ? '/admin/system/serviceVendorEdit' : '/admin/system/serviceVendorAdd'
    const res = await apiPost(url, { ...form, id: editId.value || undefined })
    if (res.code === 0) { ElMessage.success(res.msg || '保存成功'); dialogVisible.value = false; loadData() }
  } finally { submitting.value = false }
}

async function handleDelete(row: any) {
  const res = await apiPost('/admin/system/serviceVendorDelete', { id: row.id })
  if (res.code === 0) { ElMessage.success('已删除服务商'); loadData() }
}

onMounted(() => { loadData(); loadCommunities() })
</script>

<style scoped>
.vendor-page { max-width: 1400px; margin: 0 auto; }
.page-hero {
  display: flex; align-items: center; justify-content: space-between;
  background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
  border-radius: 14px; padding: 28px 32px; margin-bottom: 20px; color: #fff; flex-wrap: wrap; gap: 20px;
}
.hero-left { display: flex; align-items: center; gap: 16px; }
.hero-icon { width: 56px; height: 56px; background: rgba(255,255,255,.2); border-radius: 14px; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(4px); }
.hero-text h2 { margin: 0 0 4px; font-size: 22px; font-weight: 700; }
.hero-text p { margin: 0; font-size: 13px; opacity: .85; }
.hero-summary { display: flex; align-items: center; gap: 28px; }
.hs-item { text-align: center; }
.hs-num { font-size: 32px; font-weight: 800; display: block; line-height: 1.1; }
.hs-label { font-size: 12px; opacity: .7; margin-top: 2px; display: block; }
.hs-divider { width: 1px; height: 40px; background: rgba(255,255,255,.25); }

.vendor-type-grid { display: grid; grid-template-columns: repeat(6, 1fr); gap: 10px; margin-bottom: 20px; }
.vt-card {
  background: #fff; border-radius: 10px; padding: 14px 10px; border: 2px solid #e2e8f0;
  display: flex; flex-direction: column; align-items: center; gap: 8px; cursor: pointer;
  transition: all .2s; user-select: none;
}
.vt-card:hover { border-color: #667eea; box-shadow: 0 4px 16px rgba(102,126,234,.15); transform: translateY(-2px); }
.vt-card.active { border-color: #667eea; background: #f8faff; box-shadow: 0 0 0 2px rgba(102,126,234,.2); }
.vt-icon { width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; }
.vt-label { font-size: 12px; font-weight: 600; color: #4a5568; }
.vt-count { font-size: 11px; color: #a0aec0; }

.search-bar { background: #fff; border-radius: 10px; padding: 16px 20px; margin-bottom: 16px; border: 1px solid #e2e8f0; }
.table-card { border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden; }
.card-header-bar { display: flex; align-items: center; justify-content: space-between; font-weight: 600; }
.pagination-wrap { padding: 16px 0 0; display: flex; justify-content: flex-end; }

.vendor-info-cell { display: flex; align-items: center; gap: 12px; }
.vendor-detail { display: flex; flex-direction: column; gap: 2px; }
.vendor-name { font-weight: 600; color: #2d3748; font-size: 14px; }
.vendor-contact { font-size: 12px; color: #a0aec0; }

.contact-links { display: flex; flex-direction: column; gap: 3px; }
.contact-item { display: flex; align-items: center; gap: 4px; font-size: 12px; color: #4a5568; }

.contract-cell { display: flex; flex-direction: column; gap: 2px; }
.cc-date { font-size: 12px; color: #4a5568; }
.cc-label { display: inline-block; width: 18px; font-weight: 600; color: #718096; }
.cc-days { font-size: 11px; font-weight: 600; }

.status-dot { display: inline-block; width: 7px; height: 7px; border-radius: 50%; margin-right: 6px; }
.status-dot.active { background: #48bb78; box-shadow: 0 0 6px rgba(72,187,120,.5); }
.status-dot.expired { background: #e53e3e; box-shadow: 0 0 6px rgba(229,62,62,.5); }
.status-dot.terminated { background: #cbd5e0; }

.vendor-form { padding-right: 10px; max-height: 65vh; overflow-y: auto; }
.modern-table :deep(th) { background: #f8fafc !important; font-weight: 600; color: #2d3748; font-size: 13px; }
.modern-table :deep(td) { font-size: 13px; }
</style>
