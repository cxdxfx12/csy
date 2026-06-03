<template>
  <div class="sms-page">
    <!-- 页面头部 -->
    <div class="page-header">
      <div class="header-left">
        <div class="header-icon"><el-icon :size="26"><Tickets /></el-icon></div>
        <div class="header-info">
          <h2>短信模板管理</h2>
          <p>配置短信模板，支持验证码、通知、营销等多种场景</p>
        </div>
      </div>
      <div class="header-stats">
        <div class="stat-item">
          <span class="stat-num">{{ stats.total }}</span>
          <span class="stat-label">模板总数</span>
        </div>
        <div class="stat-divider" />
        <div class="stat-item">
          <span class="stat-num" style="color:#48bb78">{{ stats.active }}</span>
          <span class="stat-label">已启用</span>
        </div>
      </div>
    </div>

    <!-- 搜索栏 -->
    <div class="search-card">
      <el-form :model="query" inline>
        <el-form-item><el-input v-model="query.keyword" placeholder="模板名称/编码/内容" clearable style="width:260px" prefix-icon="Search" @keyup.enter="loadData" /></el-form-item>
        <el-form-item>
          <el-select v-model="query.type" placeholder="模板类型" clearable style="width:150px">
            <el-option label="验证码" value="code" />
            <el-option label="通知" value="notice" />
            <el-option label="营销" value="marketing" />
          </el-select>
        </el-form-item>
        <el-form-item>
          <el-select v-model="query.status" placeholder="状态" clearable style="width:120px">
            <el-option label="启用" :value="1" />
            <el-option label="停用" :value="0" />
          </el-select>
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="loadData"><el-icon><Search /></el-icon> 搜索</el-button>
          <el-button @click="resetQuery">重置</el-button>
        </el-form-item>
      </el-form>
    </div>

    <!-- 模板列表 -->
    <el-card shadow="never" class="table-card">
      <div class="table-toolbar">
        <el-button type="primary" @click="openForm()"><el-icon><Plus /></el-icon> 添加模板</el-button>
      </div>
      <el-table :data="list" v-loading="loading" stripe border highlight-current-row class="modern-table">
        <el-table-column type="index" label="#" width="55" />
        <el-table-column prop="id" label="ID" width="70" align="center" />
        <el-table-column prop="name" label="模板名称" min-width="150">
          <template #default="{ row }">
            <div>
              <div style="font-weight:600">{{ row.name }}</div>
              <div style="font-size:12px;color:#a0aec0">{{ row.code }}</div>
            </div>
          </template>
        </el-table-column>
        <el-table-column label="类型" width="100" align="center">
          <template #default="{ row }">
            <el-tag :type="typeTag(row.type)" size="small" round effect="light">
              {{ typeLabel(row.type) }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="template_id" label="第三方模板ID" width="170" show-overflow-tooltip>
          <template #default="{ row }">
            <span class="mono-text">{{ row.template_id || '-' }}</span>
          </template>
        </el-table-column>
        <el-table-column prop="content" label="模板内容" min-width="250" show-overflow-tooltip>
          <template #default="{ row }">
            <div class="content-preview">{{ row.content || '-' }}</div>
          </template>
        </el-table-column>
        <el-table-column prop="params" label="参数说明" width="170" show-overflow-tooltip>
          <template #default="{ row }">
            <span style="color:#718096;font-size:13px">{{ row.params || '-' }}</span>
          </template>
        </el-table-column>
        <el-table-column label="状态" width="90" align="center">
          <template #default="{ row }">
            <el-switch :model-value="row.status == 1" size="small" :loading="row._switching"
              @change="(val: boolean) => toggleStatus(row, val)" />
          </template>
        </el-table-column>
        <el-table-column label="操作" width="160" align="center" fixed="right">
          <template #default="{ row }">
            <el-button size="small" type="primary" link @click="openForm(row)"><el-icon><Edit /></el-icon> 编辑</el-button>
            <el-button size="small" type="danger" link @click="handleDelete(row)"><el-icon><Delete /></el-icon> 删除</el-button>
          </template>
        </el-table-column>
      </el-table>
      <div class="pagination-wrap">
        <el-pagination v-model:current-page="query.page" v-model:page-size="query.limit" :total="total" :page-sizes="[15,30,50]" layout="total,sizes,prev,pager,next" @update:current-page="loadData" @update:page-size="loadData" />
      </div>
    </el-card>

    <!-- 弹窗表单 -->
    <el-dialog v-model="dialogVisible" :title="editId ? '编辑短信模板' : '添加短信模板'" width="640px" destroy-on-close :close-on-click-modal="false">
      <el-form :model="form" ref="formRef" label-width="110px" label-position="top" class="template-form">
        <el-row :gutter="16">
          <el-col :span="12">
            <el-form-item label="模板名称" required>
              <el-input v-model="form.name" placeholder="如：业主通知模板" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="模板编码" required>
              <el-input v-model="form.code" placeholder="如：owner_notice" />
            </el-form-item>
          </el-col>
        </el-row>
        <el-row :gutter="16">
          <el-col :span="12">
            <el-form-item label="模板类型">
              <el-select v-model="form.type" style="width:100%">
                <el-option label="验证码" value="code" />
                <el-option label="通知消息" value="notice" />
                <el-option label="营销推广" value="marketing" />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="状态">
              <el-switch v-model="form.status" :active-value="1" :inactive-value="0" active-text="启用" inactive-text="停用" />
            </el-form-item>
          </el-col>
        </el-row>
        <el-form-item label="第三方模板ID">
          <el-input v-model="form.template_id" placeholder="短信服务商分配的模板ID" />
        </el-form-item>
        <el-form-item label="模板内容">
          <el-input v-model="form.content" type="textarea" :rows="3"
            placeholder="亲爱的{1}，您的{2}已到期，请及时续费。如有疑问请联系物业：{3}" />
        </el-form-item>
        <el-form-item label="参数说明">
          <el-input v-model="form.params" placeholder="如：{1}=业主姓名 {2}=服务项目 {3}=联系电话" />
        </el-form-item>
        <el-form-item label="所属小区">
          <el-select v-model="form.community_id" placeholder="全部小区" clearable style="width:100%">
            <el-option v-for="c in communities" :key="c.id" :label="c.name" :value="c.id" />
          </el-select>
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" @click="handleSubmit" :loading="submitting">确定</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { apiGet, apiPost } from '@/utils/request'
import { Search, Plus, Edit, Delete, Tickets } from '@element-plus/icons-vue'

const list = ref<any[]>([])
const total = ref(0)
const loading = ref(false)
const submitting = ref(false)
const dialogVisible = ref(false)
const editId = ref(0)
const communities = ref<any[]>([])
const form = reactive<any>({ name: '', code: '', type: 'notice', template_id: '', content: '', params: '', status: 1, community_id: null })

const query = reactive({ page: 1, limit: 15, keyword: '', type: '', status: '' as any })
const stats = reactive({ total: 0, active: 0 })

function typeTag(v: string) { const m: Record<string, string> = { code: 'warning', notice: '', marketing: 'danger' }; return m[v] || 'info' }
function typeLabel(v: string) { const m: Record<string, string> = { code: '验证码', notice: '通知', marketing: '营销' }; return m[v] || v || '-' }

async function loadData() {
  loading.value = true
  try {
    const res = await apiGet('/admin/sms/smsTemplateList', {
      page: query.page, limit: query.limit, keyword: query.keyword,
      type: query.type, status: query.status,
    })
    if (res.code === 0) { list.value = res.data?.list || res.data || []; total.value = res.data?.total || res.count || 0 }
    else { list.value = []; total.value = 0 }
    stats.total = total.value
    stats.active = (list.value || []).filter((t: any) => t.status == 1).length
  } catch { list.value = []; total.value = 0; stats.active = 0 }
  finally { loading.value = false }
}

function resetQuery() { query.keyword = ''; query.type = ''; query.status = ''; query.page = 1; loadData() }

async function loadCommunities() {
  try {
    const r = await apiGet('/admin/community/list', { limit: 999 })
    communities.value = r.data?.list || r.data || []
  } catch { communities.value = [] }
}

function openForm(row?: any) {
  if (row) {
    editId.value = row.id
    Object.assign(form, { name: row.name, code: row.code, type: row.type || 'notice', template_id: row.template_id, content: row.content, params: row.params, status: row.status ?? 1, community_id: row.community_id || null })
  } else {
    editId.value = 0
    Object.keys(form).forEach(k => { (form as any)[k] = k === 'status' ? 1 : k === 'type' ? 'notice' : k === 'community_id' ? null : '' })
  }
  dialogVisible.value = true
}

async function handleSubmit() {
  if (!form.name) return ElMessage.warning('请输入模板名称')
  submitting.value = true
  try {
    const url = editId.value ? '/admin/sms/smsTemplateEdit' : '/admin/sms/smsTemplateAdd'
    const res = await apiPost(url, { ...form, id: editId.value || undefined })
    if (res.code === 0) { ElMessage.success(res.msg || '保存成功'); dialogVisible.value = false; loadData() }
  } finally { submitting.value = false }
}

async function handleDelete(row: any) {
  await ElMessageBox.confirm(`确定删除模板「${row.name}」吗？`, '删除确认', { type: 'warning' })
  const res = await apiPost('/admin/sms/smsTemplateDelete', { id: row.id })
  if (res.code === 0) { ElMessage.success('删除成功'); loadData() }
}

async function toggleStatus(row: any, val: boolean) {
  row._switching = true
  try {
    await apiPost('/admin/sms/smsTemplateEdit', { id: row.id, status: val ? 1 : 0 })
    row.status = val ? 1 : 0
    ElMessage.success(val ? '已启用' : '已停用')
    loadData()
  } catch { /* revert happens visually via loadData */ }
  finally { row._switching = false }
}

onMounted(() => { loadData(); loadCommunities() })
</script>

<style scoped>
.sms-page { max-width: 1400px; margin: 0 auto; }
.page-header {
  display: flex; align-items: center; justify-content: space-between;
  background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
  border-radius: 12px; padding: 24px 28px; margin-bottom: 20px; color: #1a202c; flex-wrap: wrap; gap:16px;
}
.header-left { display: flex; align-items: center; gap: 16px; }
.header-icon { width: 48px; height: 48px; background: rgba(255,255,255,.5); border-radius: 12px; display: flex; align-items: center; justify-content: center; }
.header-info h2 { margin: 0 0 4px; font-size: 20px; font-weight: 700; }
.header-info p { margin: 0; font-size: 13px; opacity: .7; }
.header-stats { display: flex; align-items: center; gap: 24px; }
.stat-item { text-align: center; }
.stat-num { font-size: 28px; font-weight: 800; display: block; line-height: 1.2; }
.stat-label { font-size: 12px; opacity: .6; }
.stat-divider { width: 1px; height: 36px; background: rgba(0,0,0,.15); }
.search-card { background: #fff; border-radius: 10px; padding: 16px 20px; margin-bottom: 16px; border: 1px solid #e2e8f0; }
.table-card { border-radius: 10px; border: 1px solid #e2e8f0; overflow: hidden; }
.table-toolbar { margin-bottom: 12px; }
.pagination-wrap { padding: 16px 0 0; display: flex; justify-content: flex-end; }
.mono-text { font-family: 'SF Mono', 'Fira Code', monospace; background: #f7fafc; padding: 2px 8px; border-radius: 4px; font-size: 12px; }
.content-preview { max-width: 300px; white-space: pre-wrap; line-height: 1.5; color: #4a5568; font-size: 13px; }
.template-form { padding-right: 20px; }
.modern-table :deep(th) { background: #f8fafc !important; font-weight: 600; color: #2d3748; }
</style>
