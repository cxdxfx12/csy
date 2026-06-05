<template>
  <div class="premium-page">
    <!-- 费率概览 -->
    <div class="summary-strip">
      <div class="summary-item">
        <span class="s-label">生效费率</span>
        <span class="s-val s-green">{{ ruleCount.active }}</span>
      </div>
      <div class="summary-div"></div>
      <div class="summary-item">
        <span class="s-label">车辆类型覆盖</span>
        <span class="s-val s-blue">{{ ruleCount.types }}</span>
      </div>
      <div class="summary-div"></div>
      <div class="summary-item">
        <span class="s-label">免费时段</span>
        <span class="s-val s-purple">{{ ruleCount.freeMinutes }}</span>
      </div>
      <div class="summary-div"></div>
      <div class="summary-item">
        <span class="s-label">日封顶规则</span>
        <span class="s-val s-orange">{{ ruleCount.dailyCaps }}</span>
      </div>
      <div class="summary-action">
        <el-button type="primary" @click="openForm()" :icon="Plus" round>新增费率</el-button>
      </div>
    </div>

    <!-- 搜索 -->
    <div class="filter-row">
      <el-input v-model="query.keyword" placeholder="搜索费率名称/车辆类型" clearable prefix-icon="Search" class="f-inp" @keyup.enter="loadData" />
      <el-select v-model="query.community_id" placeholder="所属小区" clearable class="f-sel" @change="loadData">
        <el-option v-for="c in communities" :key="c.id" :label="c.name" :value="c.id"/>
      </el-select>
      <el-button type="primary" @click="loadData" :icon="Search">搜索</el-button>
      <el-button @click="resetQuery" :icon="RefreshRight">重置</el-button>
    </div>

    <!-- 费率卡片网格 -->
    <div class="rate-grid">
      <div v-for="rule in list" :key="rule.id" class="rate-card">
        <div class="rc-top">
          <div class="rc-icon-wrap">
            <el-icon v-if="rule.vehicle_type && rule.vehicle_type.includes('月租')"><Timer /></el-icon>
            <el-icon v-else-if="rule.vehicle_type && rule.vehicle_type.includes('临')"><Van /></el-icon>
            <el-icon v-else><Cpu /></el-icon>
          </div>
          <div class="rc-info">
            <div class="rc-name">{{ rule.name || '未命名' }}</div>
            <div class="rc-type">{{ rule.vehicle_type || '通用' }}</div>
          </div>
          <div class="rc-badge">{{ rule.charge_type == 1 ? '按时' : rule.charge_type == 2 ? '按次' : rule.charge_type == 3 ? '月租' : '其他' }}</div>
        </div>

        <div class="rc-prices">
          <div class="price-block">
            <div class="price-val">¥{{ rule.unit_price || 0 }}</div>
            <div class="price-unit">/{{ rule.unit_duration || 60 }}分钟</div>
          </div>
          <div class="price-div"></div>
          <div class="price-block">
            <div class="price-val">¥{{ rule.daily_max || '—' }}</div>
            <div class="price-unit">日封顶</div>
          </div>
          <div v-if="rule.monthly_price" class="price-div"></div>
          <div v-if="rule.monthly_price" class="price-block">
            <div class="price-val">¥{{ rule.monthly_price }}</div>
            <div class="price-unit">月租价</div>
          </div>
        </div>

        <div class="rc-meta">
          <span><el-icon><Clock /></el-icon> 免费 {{ rule.free_minutes || 0 }} 分钟</span>
          <span v-if="rule.night_start && rule.night_end"><el-icon><Moon /></el-icon> {{ rule.night_start }}-{{ rule.night_end }}</span>
        </div>

        <div class="rc-actions">
          <el-button size="small" @click="openForm(rule)" :icon="Edit" round>编辑</el-button>
          <el-popconfirm title="确认删除此费率规则？" @confirm="handleDelete(rule)">
            <template #reference>
              <el-button size="small" type="danger" :icon="Delete" round plain>删除</el-button>
            </template>
          </el-popconfirm>
        </div>
      </div>
    </div>

    <!-- 空状态 -->
    <div v-if="!loading && list.length === 0" class="empty-state">
      <el-icon :size="64" color="#cbd5e1"><Coin /></el-icon>
      <p>暂未设置停车费率规则</p>
      <el-button type="primary" @click="openForm()" :icon="Plus" round>创建第一条费率</el-button>
    </div>

    <!-- 分页 -->
    <div class="pagi-area" v-if="total > 0">
      <el-pagination v-model:current-page="query.page" v-model:page-size="query.limit" :total="total"
        :page-sizes="[12, 24, 48]" layout="total, sizes, prev, pager, next" background
        @current-change="loadData" @size-change="loadData" />
    </div>

    <!-- 弹窗 -->
    <el-dialog v-model="dialogVisible" :title="editId ? '编辑费率规则' : '新增费率规则'" width="680px" destroy-on-close top="8vh">
      <el-form :model="form" ref="formRef" label-width="110px" label-position="right" class="rate-form">
        <el-row :gutter="16">
          <el-col :span="12">
            <el-form-item label="规则名称"><el-input v-model="form.name" placeholder="如：临时车-白天" /></el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="所属小区"><el-select v-model="form.community_id" placeholder="选择小区" style="width:100%" clearable><el-option v-for="c in communities" :key="c.id" :label="c.name" :value="c.id"/></el-select></el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="车辆类型"><el-select v-model="form.vehicle_type" placeholder="选择类型" style="width:100%">
              <el-option label="临时车" value="临时车" />
              <el-option label="月租车" value="月租车" />
              <el-option label="业主车" value="业主车" />
              <el-option label="访客车" value="访客车" />
            </el-select></el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="计费方式"><el-select v-model="form.charge_type" placeholder="选择方式" style="width:100%">
              <el-option label="按时计费" :value="1" />
              <el-option label="按次计费" :value="2" />
              <el-option label="月租" :value="3" />
            </el-select></el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="免费分钟"><el-input-number v-model="form.free_minutes" :min="0" :step="5" style="width:100%" /></el-form-item>
          </el-col>
          <el-col :span="8">
            <el-form-item label="单价(元)"><el-input v-model="form.unit_price" placeholder="0.00" /></el-form-item>
          </el-col>
          <el-col :span="8">
            <el-form-item label="计费单位(分钟)"><el-input-number v-model="form.unit_duration" :min="1" style="width:100%" /></el-form-item>
          </el-col>
          <el-col :span="8">
            <el-form-item label="日封顶(元)"><el-input v-model="form.daily_max" placeholder="0.00" /></el-form-item>
          </el-col>
          <el-col :span="12" v-if="form.charge_type == 3">
            <el-form-item label="月租价(元)"><el-input v-model="form.monthly_price" placeholder="0.00" /></el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="夜间免费"><el-input-number v-model="form.night_free" :min="0" style="width:100%" /></el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="夜间时段"><el-input v-model="form.night_start" placeholder="开始 如22:00" style="width:48%" /> <span style="margin:0 4px">—</span> <el-input v-model="form.night_end" placeholder="结束 如08:00" style="width:48%" /></el-form-item>
          </el-col>
        </el-row>
      </el-form>
      <template #footer>
        <el-button @click="dialogVisible = false" round>取消</el-button>
        <el-button type="primary" @click="handleSubmit" round :loading="submitting">保存规则</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, computed } from 'vue'
import { apiGet, apiPost } from '@/utils/request'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Plus, Search, RefreshRight, Timer, Van, Cpu, Clock, Moon, Edit, Delete, Coin } from '@element-plus/icons-vue'

const list = ref<any[]>([])
const total = ref(0)
const loading = ref(false)
const dialogVisible = ref(false)
const editId = ref(0)
const submitting = ref(false)
const formRef = ref()
const query = reactive({ page: 1, limit: 12, keyword: '', community_id: undefined as any })
const communities = ref<any[]>([])
const form = reactive<any>({ charge_type: 1, free_minutes: 0, unit_duration: 60 })

const ruleCount = computed(() => {
  const arr = list.value || []
  return {
    active: arr.length,
    types: new Set(arr.map(r => r.vehicle_type).filter(Boolean)).size,
    freeMinutes: new Set(arr.map(r => r.free_minutes).filter(v => v > 0)).size,
    dailyCaps: arr.filter(r => parseFloat(r.daily_max) > 0).length,
  }
})

async function loadData() {
  loading.value = true
  try {
    const res = await apiGet('/admin/parking/parkingFeeRuleList', { page: query.page, limit: query.limit, keyword: query.keyword, community_id: query.community_id || undefined })
    if (res.code === 0) { list.value = res.data || []; total.value = res.count || 0 }
  } finally { loading.value = false }
}

function resetQuery() { query.keyword = ''; query.community_id = undefined; query.page = 1; loadData() }

function openForm(row?: any) {
  if (row) { editId.value = row.id; Object.assign(form, row) }
  else { editId.value = 0; Object.keys(form).forEach(k => delete form[k]); form.charge_type = 1; form.free_minutes = 0; form.unit_duration = 60 }
  dialogVisible.value = true
}

async function handleSubmit() {
  submitting.value = true
  try {
    const url = editId.value ? '/admin/parking/parkingFeeRuleEdit' : '/admin/parking/parkingFeeRuleAdd'
    const res = await apiPost(url, { ...form, id: editId.value || undefined })
    if (res.code === 0) { ElMessage.success(res.msg); dialogVisible.value = false; loadData() }
  } finally { submitting.value = false }
}

async function handleDelete(row: any) {
  const res = await apiPost('/admin/parking/parkingFeeRuleDelete', { id: row.id })
  if (res.code === 0) { ElMessage.success('删除成功'); loadData() }
}

onMounted(async () => {
  try { const r: any = await apiGet('/admin/community/list', { limit: 999 }); communities.value = r.data?.list || r.data || [] } catch (_) { }
  loadData()
})
</script>

<style scoped>
.premium-page { animation: fadeIn 0.4s ease; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }

.summary-strip { display: flex; align-items: center; gap: 20px; background: #fff; border-radius: 14px; padding: 16px 24px; margin-bottom: 16px; border: 1px solid #f1f5f9; }
.summary-item { display: flex; flex-direction: column; gap: 2px; }
.s-label { font-size: 11px; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px; }
.s-val { font-size: 22px; font-weight: 800; }
.s-green { color: #22c55e; } .s-blue { color: #3b82f6; } .s-purple { color: #8b5cf6; } .s-orange { color: #f59e0b; }
.summary-div { width: 1px; height: 32px; background: #e2e8f0; }
.summary-action { margin-left: auto; }

.filter-row { display: flex; gap: 10px; margin-bottom: 18px; }
.f-inp { width: 280px; }

.rate-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 18px; }
.rate-card { background: #fff; border-radius: 14px; padding: 20px; border: 1px solid #f1f5f9; transition: all 0.3s; position: relative; overflow: hidden; }
.rate-card:hover { box-shadow: 0 12px 36px rgba(0,0,0,0.08); transform: translateY(-3px); }
.rate-card::after { content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 3px; background: linear-gradient(90deg, #6366f1, #8b5cf6, #ec4899); opacity: 0; transition: opacity 0.3s; }
.rate-card:hover::after { opacity: 1; }
.rc-top { display: flex; align-items: center; gap: 12px; margin-bottom: 16px; }
.rc-icon-wrap { width: 42px; height: 42px; border-radius: 12px; background: linear-gradient(135deg, #6366f1, #8b5cf6); display: flex; align-items: center; justify-content: center; color: #fff; font-size: 18px; flex-shrink: 0; }
.rc-info { flex: 1; overflow: hidden; }
.rc-name { font-size: 15px; font-weight: 700; color: #1e293b; line-height: 1.2; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.rc-type { font-size: 12px; color: #94a3b8; }
.rc-badge { font-size: 11px; padding: 3px 10px; border-radius: 12px; background: #eef2ff; color: #6366f1; font-weight: 600; white-space: nowrap; }
.rc-prices { display: flex; align-items: center; gap: 12px; margin-bottom: 12px; }
.price-block { flex: 1; text-align: center; }
.price-val { font-size: 20px; font-weight: 800; color: #1e293b; }
.price-unit { font-size: 11px; color: #94a3b8; }
.price-div { width: 1px; height: 24px; background: #e2e8f0; }
.rc-meta { display: flex; gap: 14px; font-size: 12px; color: #64748b; margin-bottom: 14px; flex-wrap: wrap; }
.rc-meta span { display: flex; align-items: center; gap: 4px; }
.rc-actions { display: flex; gap: 8px; }

.empty-state { text-align: center; padding: 80px 20px; color: #94a3b8; }
.empty-state p { margin: 16px 0; font-size: 14px; }

.pagi-area { display: flex; justify-content: center; margin-top: 8px; }

.rate-form :deep(.el-form-item) { margin-bottom: 18px; }

@media (max-width: 1200px) { .rate-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 768px) { .rate-grid { grid-template-columns: 1fr; } .summary-strip { flex-wrap: wrap; } }
</style>
