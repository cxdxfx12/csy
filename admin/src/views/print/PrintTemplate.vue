<template>
  <div class="print-template-page">
    <!-- 页面头部 -->
    <div class="page-hero">
      <div class="hero-content">
        <div class="hero-left">
          <div class="hero-icon">
            <el-icon :size="28"><Notebook /></el-icon>
          </div>
          <div class="hero-text">
            <h1>打印模板管理中心</h1>
            <p>统一管理各类单据模板，支持收据、通知、合同等多种格式自定义</p>
          </div>
        </div>
        <div class="hero-actions">
          <el-button type="primary" size="large" @click="openForm()" class="btn-create">
            <el-icon><Plus /></el-icon>
            <span>新建模板</span>
          </el-button>
        </div>
      </div>
    </div>

    <!-- 统计卡片 -->
    <div class="stats-row">
      <div class="stat-card">
        <div class="stat-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
          <el-icon :size="22"><Tickets /></el-icon>
        </div>
        <div class="stat-info">
          <span class="stat-number">{{ stats.total }}</span>
          <span class="stat-label">模板总数</span>
        </div>
        <div class="stat-spark"></div>
      </div>
      <div class="stat-card">
        <div class="stat-icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
          <el-icon :size="22"><Document /></el-icon>
        </div>
        <div class="stat-info">
          <span class="stat-number">{{ stats.receiptType }}</span>
          <span class="stat-label">收据类模板</span>
        </div>
        <div class="stat-spark"></div>
      </div>
      <div class="stat-card">
        <div class="stat-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
          <el-icon :size="22"><Bell /></el-icon>
        </div>
        <div class="stat-info">
          <span class="stat-number">{{ stats.noticeType }}</span>
          <span class="stat-label">通知类模板</span>
        </div>
        <div class="stat-spark"></div>
      </div>
      <div class="stat-card">
        <div class="stat-icon" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
          <el-icon :size="22"><CircleCheck /></el-icon>
        </div>
        <div class="stat-info">
          <span class="stat-number">{{ stats.activeCount }}</span>
          <span class="stat-label">启用中</span>
        </div>
        <div class="stat-spark"></div>
      </div>
    </div>

    <!-- 筛选与操作栏 -->
    <div class="filter-bar">
      <div class="filter-left">
        <el-input v-model="query.keyword" placeholder="搜索模板名称、编码或内容..." clearable class="search-input" @keyup.enter="loadData" @clear="loadData">
          <template #prefix>
            <el-icon><Search /></el-icon>
          </template>
        </el-input>
        <el-select v-model="query.type" placeholder="模板类型" clearable class="filter-select" @change="loadData">
          <el-option label="收据" value="receipt" />
          <el-option label="通知" value="notice" />
          <el-option label="合同" value="contract" />
          <el-option label="表单" value="form" />
          <el-option label="其他" value="other" />
        </el-select>
        <el-select v-model="query.status" placeholder="启用状态" clearable class="filter-select" @change="loadData">
          <el-option label="已启用" :value="1" />
          <el-option label="已禁用" :value="0" />
        </el-select>
      </div>
      <div class="filter-right">
        <el-radio-group v-model="viewMode" size="small" class="view-toggle">
          <el-radio-button value="grid"><el-icon><Grid /></el-icon></el-radio-button>
          <el-radio-button value="list"><el-icon><List /></el-icon></el-radio-button>
        </el-radio-group>
      </div>
    </div>

    <!-- 网格视图 -->
    <div v-if="viewMode === 'grid'" class="template-grid" v-loading="loading">
      <div v-if="list.length === 0 && !loading" class="empty-state">
        <div class="empty-illustration">
          <el-icon :size="64"><FolderOpened /></el-icon>
        </div>
        <h3>暂无打印模板</h3>
        <p>点击「新建模板」创建您的第一个打印模板</p>
        <el-button type="primary" @click="openForm()">立即创建</el-button>
      </div>
      <div v-for="item in list" :key="item.id" class="template-card" @click="openForm(item)">
        <div class="card-ribbon" :class="item.is_default == 1 ? 'ribbon-default' : ''">
          <span v-if="item.is_default == 1">默认</span>
        </div>
        <div class="card-preview">
          <div class="preview-paper" :class="'orientation-' + (item.page_orientation || 'portrait')">
            <div class="preview-header">{{ item.name || '未命名模板' }}</div>
            <div class="preview-lines">
              <span class="preview-line w-80"></span>
              <span class="preview-line w-60"></span>
              <span class="preview-line w-90"></span>
              <span class="preview-line w-50"></span>
              <span class="preview-line w-70"></span>
            </div>
            <div class="preview-footer"></div>
          </div>
        </div>
        <div class="card-body">
          <div class="card-header-row">
            <h3 class="card-title">{{ item.name }}</h3>
            <el-tag :type="item.is_default == 1 ? 'warning' : 'info'" size="small" effect="plain">
              {{ item.is_default == 1 ? '默认' : '普通' }}
            </el-tag>
          </div>
          <div class="card-meta">
            <span class="meta-item">
              <el-icon :size="14"><Key /></el-icon>
              {{ item.code }}
            </span>
            <span class="meta-item">
              <el-icon :size="14"><PriceTag /></el-icon>
              {{ item.type || '通用' }}
            </span>
          </div>
          <div class="card-specs">
            <span class="spec-chip">{{ item.page_size || 'A4' }}</span>
            <span class="spec-chip">{{ item.page_orientation === 'landscape' ? '横向' : '纵向' }}</span>
            <span class="spec-chip" v-if="item.margin_top">上{{ item.margin_top }}mm</span>
          </div>
          <div class="card-actions" @click.stop>
            <el-button size="small" plain @click="openForm(item)">
              <el-icon><Edit /></el-icon>编辑
            </el-button>
            <el-button size="small" plain type="warning" @click="setDefault(item)" v-if="item.is_default != 1">
              <el-icon><Star /></el-icon>设为默认
            </el-button>
            <el-popconfirm title="确定要删除此模板吗？" @confirm="handleDelete(item)">
              <template #reference>
                <el-button size="small" plain type="danger">
                  <el-icon><Delete /></el-icon>
                </el-button>
              </template>
            </el-popconfirm>
          </div>
        </div>
      </div>
    </div>

    <!-- 列表视图 -->
    <el-card v-else shadow="never" class="table-card">
      <el-table :data="list" v-loading="loading" stripe class="modern-table">
        <el-table-column type="index" label="#" width="55" />
        <el-table-column prop="name" label="模板名称" min-width="180">
          <template #default="{row}">
            <div class="table-name-cell">
              <span class="name-text">{{ row.name }}</span>
              <el-tag v-if="row.is_default == 1" type="warning" size="small" effect="dark">默认</el-tag>
            </div>
          </template>
        </el-table-column>
        <el-table-column prop="code" label="模板编码" width="140" />
        <el-table-column prop="type" label="类型" width="100">
          <template #default="{row}">
            <el-tag size="small" effect="plain" :type="typeTagType(row.type)">{{ row.type || '通用' }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="page_size" label="纸张规格" width="100" />
        <el-table-column prop="page_orientation" label="方向" width="80">
          <template #default="{row}">{{ row.page_orientation === 'landscape' ? '横向' : '纵向' }}</template>
        </el-table-column>
        <el-table-column prop="create_time" label="创建时间" width="170" />
        <el-table-column label="操作" width="220" fixed="right">
          <template #default="{row}">
            <el-button size="small" plain type="primary" @click="openForm(row)">编辑</el-button>
            <el-button size="small" plain type="warning" @click="setDefault(row)" v-if="row.is_default != 1">默认</el-button>
            <el-popconfirm title="确定删除？" @confirm="handleDelete(row)">
              <template #reference>
                <el-button size="small" plain type="danger">删除</el-button>
              </template>
            </el-popconfirm>
          </template>
        </el-table-column>
      </el-table>
    </el-card>

    <!-- 分页 -->
    <div class="pagination-wrap" v-if="total > query.limit">
      <el-pagination
        v-model:current-page="query.page"
        v-model:page-size="query.limit"
        :total="total"
        :page-sizes="[12, 24, 48, 96]"
        layout="total, sizes, prev, pager, next, jumper"
        background
      />
    </div>

    <!-- 编辑弹窗 -->
    <el-dialog
      v-model="dialogVisible"
      :title="editId ? '编辑打印模板' : '新建打印模板'"
      width="720px"
      destroy-on-close
      class="template-dialog"
      :close-on-click-modal="false"
    >
      <el-form :model="form" ref="formRef" label-width="100px" class="template-form">
        <div class="form-section">
          <div class="section-title">
            <el-icon><InfoFilled /></el-icon>
            基本信息
          </div>
          <el-row :gutter="16">
            <el-col :span="12">
              <el-form-item label="模板名称" required>
                <el-input v-model="form.name" placeholder="如：标准缴费收据" />
              </el-form-item>
            </el-col>
            <el-col :span="12">
              <el-form-item label="模板编码" required>
                <el-input v-model="form.code" placeholder="如：receipt_standard" />
              </el-form-item>
            </el-col>
          </el-row>
          <el-row :gutter="16">
            <el-col :span="12">
              <el-form-item label="模板类型">
                <el-select v-model="form.type" placeholder="选择类型" style="width:100%">
                  <el-option label="收据" value="receipt" />
                  <el-option label="通知" value="notice" />
                  <el-option label="合同" value="contract" />
                  <el-option label="表单" value="form" />
                  <el-option label="其他" value="other" />
                </el-select>
              </el-form-item>
            </el-col>
            <el-col :span="12">
              <el-form-item label="启用状态">
                <el-switch v-model="form.status" :active-value="1" :inactive-value="0" active-text="启用" inactive-text="禁用" />
              </el-form-item>
            </el-col>
          </el-row>
        </div>

        <div class="form-section">
          <div class="section-title">
            <el-icon><Printer /></el-icon>
            页面设置
          </div>
          <el-row :gutter="16">
            <el-col :span="8">
              <el-form-item label="纸张规格">
                <el-select v-model="form.page_size" style="width:100%">
                  <el-option label="A4" value="A4" />
                  <el-option label="A5" value="A5" />
                  <el-option label="B5" value="B5" />
                  <el-option label="Letter" value="Letter" />
                  <el-option label="自定义" value="custom" />
                </el-select>
              </el-form-item>
            </el-col>
            <el-col :span="8">
              <el-form-item label="纸张方向">
                <el-radio-group v-model="form.page_orientation">
                  <el-radio value="portrait">纵向</el-radio>
                  <el-radio value="landscape">横向</el-radio>
                </el-radio-group>
              </el-form-item>
            </el-col>
            <el-col :span="8">
              <el-form-item label="设为默认">
                <el-switch v-model="form.is_default" :active-value="1" :inactive-value="0" />
              </el-form-item>
            </el-col>
          </el-row>
          <el-row :gutter="16">
            <el-col :span="6">
              <el-form-item label="上边距">
                <el-input-number v-model="form.margin_top" :min="0" :max="50" controls-position="right" style="width:100%" />
              </el-form-item>
            </el-col>
            <el-col :span="6">
              <el-form-item label="下边距">
                <el-input-number v-model="form.margin_bottom" :min="0" :max="50" controls-position="right" style="width:100%" />
              </el-form-item>
            </el-col>
            <el-col :span="6">
              <el-form-item label="左边距">
                <el-input-number v-model="form.margin_left" :min="0" :max="50" controls-position="right" style="width:100%" />
              </el-form-item>
            </el-col>
            <el-col :span="6">
              <el-form-item label="右边距">
                <el-input-number v-model="form.margin_right" :min="0" :max="50" controls-position="right" style="width:100%" />
              </el-form-item>
            </el-col>
          </el-row>
        </div>

        <div class="form-section">
          <div class="section-title">
            <el-icon><EditPen /></el-icon>
            模板内容
          </div>
          <el-form-item label="模板内容" label-width="100px">
            <el-input
              v-model="form.content"
              type="textarea"
              :rows="6"
              placeholder="支持 HTML 格式，可使用变量占位符如：{{owner_name}}、{{room_number}}、{{bill_no}} 等"
            />
          </el-form-item>
          <div class="variable-hints">
            <span class="hint-label">可用变量：</span>
            <el-tag size="small" v-for="v in templateVars" :key="v" class="var-tag" @click="insertVar(v)">{{ v }}</el-tag>
          </div>
        </div>
      </el-form>
      <template #footer>
        <div class="dialog-footer">
          <el-button @click="dialogVisible = false">取消</el-button>
          <el-button type="primary" @click="handleSubmit" :loading="submitting">
            {{ editId ? '保存修改' : '创建模板' }}
          </el-button>
        </div>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { apiGet, apiPost } from '@/utils/request'
import { ElMessage } from 'element-plus'
import {
  Notebook, Plus, Tickets, Document, Bell, CircleCheck, Search,
  Grid, List, FolderOpened, Key, PriceTag, Edit, Star, Delete, Printer,
  InfoFilled, EditPen
} from '@element-plus/icons-vue'

const list = ref<any[]>([])
const total = ref(0)
const loading = ref(false)
const submitting = ref(false)
const dialogVisible = ref(false)
const editId = ref(0)
const formRef = ref()
const viewMode = ref<'grid' | 'list'>('grid')

const query = reactive({ page: 1, limit: 12, keyword: '', type: '', status: undefined as any })
const form = reactive<any>({
  name: '', code: '', type: 'receipt', content: '',
  page_size: 'A4', page_orientation: 'portrait',
  margin_top: 10, margin_bottom: 10, margin_left: 15, margin_right: 15,
  is_default: 0, status: 1
})

const templateVars = [
  '{{owner_name}}', '{{room_number}}', '{{community_name}}',
  '{{bill_no}}', '{{total_amount}}', '{{paid_amount}}',
  '{{bill_period}}', '{{due_date}}', '{{charge_item_name}}',
  '{{current_date}}', '{{phone}}'
]

const stats = computed(() => {
  const data = list.value || []
  return {
    total: total.value,
    receiptType: data.filter((d: any) => d.type === 'receipt').length,
    noticeType: data.filter((d: any) => d.type === 'notice').length,
    activeCount: data.filter((d: any) => d.status == 1).length,
  }
})

function typeTagType(type: string) {
  const map: Record<string, string> = { receipt: 'success', notice: 'warning', contract: 'danger', form: 'info' }
  return map[type] || ''
}

function insertVar(v: string) {
  form.content = (form.content || '') + ' ' + v
}

async function loadData() {
  loading.value = true
  try {
    const params: any = { page: query.page, limit: query.limit }
    if (query.keyword) params.keyword = query.keyword
    if (query.type) params.type = query.type
    if (query.status !== undefined && query.status !== '') params.status = query.status
    const res = await apiGet('/admin/print/printTemplateList', { params })
    if (res && res.code === 0) { list.value = res.data.list || []; total.value = res.data.total || 0 }
  } catch (_) { list.value = []; total.value = 0 }
  finally { loading.value = false }
}

function openForm(row?: any) {
  if (row) {
    editId.value = row.id
    Object.keys(form).forEach(k => {
      if (row[k] !== undefined) (form as any)[k] = row[k]
    })
  } else {
    editId.value = 0
    Object.assign(form, {
      name: '', code: '', type: 'receipt', content: '',
      page_size: 'A4', page_orientation: 'portrait',
      margin_top: 10, margin_bottom: 10, margin_left: 15, margin_right: 15,
      is_default: 0, status: 1
    })
  }
  dialogVisible.value = true
}

async function handleSubmit() {
  submitting.value = true
  try {
    const url = editId.value ? '/admin/print/printTemplateEdit' : '/admin/print/printTemplateAdd'
    const payload = { ...form, id: editId.value || undefined }
    const res = await apiPost(url, payload)
    if (res && res.code === 0) {
      ElMessage.success(editId.value ? '模板已更新' : '模板已创建')
      dialogVisible.value = false
      loadData()
    }
  } catch (_) {}
  finally { submitting.value = false }
}

async function setDefault(row: any) {
  try {
    const res = await apiPost('/admin/print/printTemplateEdit', { id: row.id, is_default: 1 })
    if (res && res.code === 0) { ElMessage.success('已设为默认模板'); loadData() }
  } catch (_) {}
}

async function handleDelete(row: any) {
  try {
    const res = await apiPost('/admin/print/printTemplateDelete', { id: row.id })
    if (res && res.code === 0) { ElMessage.success('模板已删除'); loadData() }
  } catch (_) {}
}

onMounted(() => loadData())

watch([() => query.page, () => query.limit], () => {
  loadData()
})
</script>

<style scoped>
.print-template-page {
  min-height: calc(100vh - 100px);
  padding: 0;
}

/* 页面头部 */
.page-hero {
  background: linear-gradient(135deg, #1a365d 0%, #2563eb 50%, #3b82f6 100%);
  border-radius: 16px;
  padding: 28px 32px;
  margin-bottom: 24px;
  position: relative;
  overflow: hidden;
}
.page-hero::after {
  content: '';
  position: absolute;
  right: -60px;
  top: -60px;
  width: 200px;
  height: 200px;
  border-radius: 50%;
  background: rgba(255,255,255,0.05);
}
.page-hero::before {
  content: '';
  position: absolute;
  right: 80px;
  bottom: -40px;
  width: 120px;
  height: 120px;
  border-radius: 50%;
  background: rgba(255,255,255,0.03);
}
.hero-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
  position: relative;
  z-index: 1;
}
.hero-left {
  display: flex;
  align-items: center;
  gap: 20px;
}
.hero-icon {
  width: 56px;
  height: 56px;
  border-radius: 14px;
  background: rgba(255,255,255,0.15);
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  backdrop-filter: blur(10px);
}
.hero-text h1 {
  margin: 0;
  color: #fff;
  font-size: 22px;
  font-weight: 700;
  letter-spacing: 0.5px;
}
.hero-text p {
  margin: 6px 0 0;
  color: rgba(255,255,255,0.75);
  font-size: 13px;
}
.btn-create {
  height: 42px;
  padding: 0 24px;
  font-weight: 600;
  font-size: 14px;
  border-radius: 10px;
  background: #fff;
  color: #2563eb;
  border: none;
  box-shadow: 0 4px 15px rgba(0,0,0,0.15);
}
.btn-create:hover {
  background: #f0f5ff;
  color: #1d4ed8;
  transform: translateY(-1px);
  box-shadow: 0 6px 20px rgba(0,0,0,0.2);
}

/* 统计卡片行 */
.stats-row {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
  margin-bottom: 24px;
}
.stat-card {
  background: #fff;
  border-radius: 14px;
  padding: 22px 24px;
  display: flex;
  align-items: center;
  gap: 18px;
  border: 1px solid #e8ecf1;
  position: relative;
  overflow: hidden;
  transition: all 0.3s ease;
  cursor: default;
}
.stat-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(0,0,0,0.08);
  border-color: #c8d6e5;
}
.stat-spark {
  position: absolute;
  right: 0;
  top: 0;
  width: 60px;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(59,130,246,0.03));
}
.stat-icon {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  flex-shrink: 0;
}
.stat-number {
  font-size: 28px;
  font-weight: 800;
  color: #1a202c;
  line-height: 1;
  display: block;
}
.stat-label {
  font-size: 12px;
  color: #718096;
  margin-top: 4px;
  display: block;
}

/* 筛选栏 */
.filter-bar {
  background: #fff;
  border-radius: 14px;
  padding: 16px 20px;
  margin-bottom: 20px;
  border: 1px solid #e8ecf1;
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 16px;
}
.filter-left {
  display: flex;
  gap: 12px;
  flex: 1;
}
.search-input {
  width: 280px;
}
.search-input :deep(.el-input__wrapper) {
  border-radius: 10px;
}
.filter-select {
  width: 150px;
}
.filter-select :deep(.el-input__wrapper) {
  border-radius: 10px;
}
.view-toggle {
  flex-shrink: 0;
}

/* 模板网格 */
.template-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 20px;
  min-height: 200px;
}
.template-card {
  background: #fff;
  border-radius: 16px;
  border: 1px solid #e8ecf1;
  overflow: hidden;
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
}
.template-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 16px 40px rgba(0,0,0,0.1);
  border-color: #93c5fd;
}
.card-ribbon {
  position: absolute;
  top: 12px;
  right: -28px;
  width: 100px;
  text-align: center;
  font-size: 10px;
  font-weight: 700;
  padding: 3px 0;
  transform: rotate(45deg);
  background: #e2e8f0;
  color: #718096;
  z-index: 2;
}
.card-ribbon.ribbon-default {
  background: linear-gradient(135deg, #f59e0b, #fbbf24);
  color: #7c2d12;
}
.card-preview {
  padding: 20px;
  background: linear-gradient(180deg, #f8fafc 0%, #e8ecf1 100%);
  display: flex;
  justify-content: center;
}
.preview-paper {
  width: 120px;
  height: 160px;
  background: #fff;
  border-radius: 4px;
  padding: 12px 10px;
  box-shadow: 0 2px 12px rgba(0,0,0,0.08);
  display: flex;
  flex-direction: column;
  gap: 6px;
  transition: all 0.3s ease;
}
.template-card:hover .preview-paper {
  box-shadow: 0 4px 20px rgba(37,99,235,0.12);
}
.preview-paper.orientation-landscape {
  width: 160px;
  height: 110px;
}
.preview-header {
  font-size: 8px;
  font-weight: 700;
  color: #1a365d;
  text-align: center;
  padding-bottom: 4px;
  border-bottom: 1px solid #e2e8f0;
}
.preview-lines {
  display: flex;
  flex-direction: column;
  gap: 3px;
  flex: 1;
  padding: 4px 0;
}
.preview-line {
  height: 3px;
  background: #e2e8f0;
  border-radius: 2px;
}
.preview-line.w-80 { width: 80%; }
.preview-line.w-60 { width: 60%; }
.preview-line.w-90 { width: 90%; }
.preview-line.w-50 { width: 50%; }
.preview-line.w-70 { width: 70%; }
.preview-footer {
  height: 6px;
  background: #2563eb;
  border-radius: 1px;
  opacity: 0.3;
}
.card-body {
  padding: 16px 18px 18px;
}
.card-header-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 10px;
}
.card-title {
  margin: 0;
  font-size: 15px;
  font-weight: 600;
  color: #1a202c;
}
.card-meta {
  display: flex;
  gap: 16px;
  margin-bottom: 10px;
  font-size: 12px;
  color: #718096;
}
.meta-item {
  display: flex;
  align-items: center;
  gap: 4px;
}
.card-specs {
  display: flex;
  gap: 6px;
  margin-bottom: 14px;
  flex-wrap: wrap;
}
.spec-chip {
  font-size: 11px;
  padding: 2px 8px;
  background: #f0f5ff;
  color: #2563eb;
  border-radius: 6px;
  font-weight: 500;
}
.card-actions {
  display: flex;
  gap: 8px;
  padding-top: 12px;
  border-top: 1px solid #f0f2f5;
}

/* 列表表格 */
.table-card {
  border-radius: 14px;
  border: 1px solid #e8ecf1;
  overflow: hidden;
}
.modern-table :deep(.el-table__header th) {
  background: #f8fafc;
  font-weight: 600;
  color: #475569;
}
.table-name-cell {
  display: flex;
  align-items: center;
  gap: 8px;
}
.name-text {
  font-weight: 500;
}

/* 空状态 */
.empty-state {
  grid-column: 1 / -1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 80px 20px;
  background: #fff;
  border-radius: 16px;
  border: 2px dashed #e2e8f0;
}
.empty-illustration {
  color: #cbd5e1;
  margin-bottom: 16px;
}
.empty-state h3 {
  color: #475569;
  margin: 0 0 8px;
  font-size: 18px;
}
.empty-state p {
  color: #94a3b8;
  margin: 0 0 20px;
  font-size: 14px;
}

/* 分页 */
.pagination-wrap {
  display: flex;
  justify-content: center;
  margin-top: 24px;
}

/* 弹窗 */
.template-dialog :deep(.el-dialog__header) {
  border-bottom: 1px solid #f0f2f5;
  padding: 20px 24px;
}
.template-dialog :deep(.el-dialog__body) {
  padding: 24px;
}
.form-section {
  margin-bottom: 24px;
  padding-bottom: 20px;
  border-bottom: 1px solid #f0f2f5;
}
.form-section:last-child {
  border-bottom: none;
  margin-bottom: 0;
  padding-bottom: 0;
}
.section-title {
  font-size: 14px;
  font-weight: 700;
  color: #1a365d;
  margin-bottom: 16px;
  display: flex;
  align-items: center;
  gap: 8px;
}
.section-title .el-icon {
  color: #2563eb;
}
.variable-hints {
  margin-top: 8px;
  display: flex;
  align-items: center;
  gap: 6px;
  flex-wrap: wrap;
}
.hint-label {
  font-size: 12px;
  color: #94a3b8;
  margin-right: 4px;
}
.var-tag {
  cursor: pointer;
  transition: all 0.2s;
}
.var-tag:hover {
  transform: scale(1.05);
  background: #2563eb;
  color: #fff;
  border-color: #2563eb;
}
.dialog-footer {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
}

/* 响应式 */
@media (max-width: 1400px) {
  .template-grid { grid-template-columns: repeat(2, 1fr); }
  .stats-row { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 900px) {
  .template-grid { grid-template-columns: 1fr; }
  .stats-row { grid-template-columns: 1fr 1fr; }
  .filter-bar { flex-direction: column; }
  .filter-left { flex-wrap: wrap; }
}
</style>
