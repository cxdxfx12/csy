<template>
  <div class="ai-page">
    <div class="page-header">
      <div class="header-left">
        <h2 class="page-title"><el-icon :size="24"><ChatDotRound /></el-icon> AI 助手管理</h2>
        <p class="page-desc">智能报修助手配置 · 今日对话{{ stats.today_chats }}次</p>
      </div>
    </div>

    <el-row :gutter="16" class="stats-row">
      <el-col :span="6"><el-card shadow="hover" class="stat-card stat-total"><div class="stat-inner">
        <div class="stat-icon"><el-icon :size="32"><ChatLineSquare /></el-icon></div>
        <div class="stat-info"><div class="stat-value">{{ stats.total_chats }}</div><div class="stat-label">累计对话</div></div>
      </div></el-card></el-col>
      <el-col :span="6"><el-card shadow="hover" class="stat-card stat-online"><div class="stat-inner">
        <div class="stat-icon"><el-icon :size="32"><DataAnalysis /></el-icon></div>
        <div class="stat-info"><div class="stat-value">{{ stats.today_chats }}</div><div class="stat-label">今日对话</div></div>
      </div></el-card></el-col>
      <el-col :span="6"><el-card shadow="hover" class="stat-card stat-offline"><div class="stat-inner">
        <div class="stat-icon"><el-icon :size="32"><TrendCharts /></el-icon></div>
        <div class="stat-info"><div class="stat-value">{{ stats.week_chats }}</div><div class="stat-label">近7天</div></div>
      </div></el-card></el-col>
      <el-col :span="6"><el-card shadow="hover" class="stat-card stat-type"><div class="stat-inner">
        <div class="stat-icon"><el-icon :size="32"><PieChart /></el-icon></div>
        <div class="stat-info"><div class="stat-value">{{ stats.by_type?.length||0 }}</div><div class="stat-label">报修类型</div></div>
      </div></el-card></el-col>
    </el-row>

    <el-tabs v-model="activeTab" type="border-card" class="main-tabs">
      <!-- ===== AI 配置 ===== -->
      <el-tab-pane label="AI 配置" name="config">
        <el-card shadow="never" class="config-card">
          <el-form :model="cfgForm" label-width="110px" label-position="right">
            <el-form-item label="问候语">
              <el-input v-model="cfgForm.greeting" type="textarea" :rows="2" placeholder="首次对话欢迎语" />
            </el-form-item>
            <el-form-item label="操作提示">
              <el-input v-model="cfgForm.welcome_tips" placeholder="如：直接描述您的问题，我会自动生成报修单" />
            </el-form-item>

            <el-divider content-position="left">报修类型关键词配置</el-divider>
            <div v-for="(keywords, typeName) in cfgForm.type_keywords" :key="typeName" class="kw-group">
              <div class="kw-header">
                <span class="kw-type-name">{{ typeName }}</span>
                <el-button text type="primary" size="small" @click="addKeyword(typeName)"><el-icon><Plus /></el-icon> 添加</el-button>
                <el-button v-if="typeName!=='其他'" text type="danger" size="small" @click="removeType(typeName)">删除类型</el-button>
              </div>
              <div class="kw-tags">
                <el-tag v-for="(kw, i) in keywords" :key="i" closable size="large" class="kw-tag" @close="removeKeyword(typeName, i)">
                  {{ kw }}
                </el-tag>
              </div>
            </div>
            <el-button type="primary" plain size="small" style="margin-top:12px" @click="addType()"><el-icon><Plus /></el-icon> 添加报修类型</el-button>

            <el-divider content-position="left">紧急程度关键词</el-divider>
            <div class="kw-tags">
              <el-tag v-for="(kw, i) in cfgForm.urgent_keywords" :key="i" closable size="large" class="kw-tag kw-urgent" @close="removeUrgent(i)">
                {{ kw }}
              </el-tag>
              <el-button size="small" @click="addUrgent()"><el-icon><Plus /></el-icon></el-button>
            </div>

            <div style="margin-top:24px">
              <el-button type="primary" :loading="cfgSaving" @click="saveConfig">保存配置</el-button>
            </div>
          </el-form>
        </el-card>
      </el-tab-pane>

      <!-- ===== 对话记录 ===== -->
      <el-tab-pane label="对话记录" name="history">
        <el-card shadow="never" class="filter-card">
          <el-form :model="hQuery" inline>
            <el-form-item label="关键词"><el-input v-model="hQuery.keyword" placeholder="搜索对话内容" clearable style="width:200px" @keyup.enter="loadHistory" @clear="loadHistory"><template #prefix><el-icon><Search /></el-icon></template></el-input></el-form-item>
            <el-form-item label="时间"><el-date-picker v-model="hQuery.date_range" type="daterange" range-separator="至" start-placeholder="开始" end-placeholder="结束" value-format="YYYY-MM-DD" style="width:250px" @change="loadHistory" /></el-form-item>
            <el-form-item><el-button type="primary" @click="loadHistory">查询</el-button></el-form-item>
          </el-form>
        </el-card>
        <el-card shadow="never" class="table-card">
          <el-table :data="histories" v-loading="hLoading" stripe>
            <el-table-column type="index" label="序号" width="55" />
            <el-table-column prop="owner_name" label="业主" width="100" />
            <el-table-column prop="owner_phone" label="手机号" width="120" />
            <el-table-column prop="message" label="用户消息" min-width="180" show-overflow-tooltip />
            <el-table-column prop="reply" label="AI回复" min-width="200" show-overflow-tooltip />
            <el-table-column label="操作" width="80" align="center">
              <template #default="{row}"><el-tag size="small" :type="(row.action||'')==='submit'?'success':((row.action||'')==='confirm'?'warning':'info')">{{row.action||'chat'}}</el-tag></template>
            </el-table-column>
            <el-table-column prop="create_time" label="时间" width="160" />
          </el-table>
          <div class="pagination-wrap"><el-pagination v-model:current-page="hQuery.page" v-model:page-size="hQuery.limit" :total="hTotal" :page-sizes="[15,30,50]" layout="total,sizes,prev,pager,next,jumper" @change="loadHistory" /></div>
        </el-card>
      </el-tab-pane>

      <!-- ===== 数据统计 ===== -->
      <el-tab-pane label="数据统计" name="stats">
        <el-row :gutter="16" style="margin-bottom:16px">
          <el-col :span="12">
            <el-card shadow="hover"><div ref="trendChart" style="height:300px"></div></el-card>
          </el-col>
          <el-col :span="12">
            <el-card shadow="hover"><div ref="typeChart" style="height:300px"></div></el-card>
          </el-col>
        </el-row>
      </el-tab-pane>
    </el-tabs>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, nextTick, watch } from 'vue'
import { apiGet, apiPost } from '@/utils/request'
import { ElMessage, ElMessageBox } from 'element-plus'
import * as echarts from 'echarts'

const activeTab = ref('config')
const hLoading = ref(false), cfgSaving = ref(false)
const histories = ref<any[]>([])
const hTotal = ref(0)
const trendChart = ref(), typeChart = ref()

const stats = reactive<any>({ total_chats: 0, today_chats: 0, week_chats: 0, trend: [], by_type: [] })
const hQuery = reactive({ page: 1, limit: 15, keyword: '', date_range: [] as string[] })
const cfgForm = reactive<any>({ greeting: '', welcome_tips: '', type_keywords: {} as Record<string,string[]>, urgent_keywords: [] as string[] })

async function loadConfig() {
  try {
    const r = await apiGet('/admin/aiAssistant/config')
    if (r?.code===0) {
      cfgForm.greeting = r.data.greeting || ''
      cfgForm.welcome_tips = r.data.welcome_tips || ''
      cfgForm.type_keywords = r.data.type_keywords || {}
      cfgForm.urgent_keywords = r.data.urgent_keywords || []
    }
  } catch(_) {}
}

async function saveConfig() {
  cfgSaving.value = true
  try {
    const r = await apiPost('/admin/aiAssistant/config', {
      type_keywords: JSON.stringify(cfgForm.type_keywords),
      urgent_keywords: JSON.stringify(cfgForm.urgent_keywords),
      greeting: cfgForm.greeting,
      welcome_tips: cfgForm.welcome_tips,
    })
    if (r?.code===0) ElMessage.success('配置已保存')
  } finally { cfgSaving.value = false }
}

function removeKeyword(typeName: string, i: number) { cfgForm.type_keywords[typeName].splice(i, 1) }
function addKeyword(typeName: string) {
  ElMessageBox.prompt('请输入新关键词', '添加关键词').then(({ value }) => {
    if (value?.trim()) cfgForm.type_keywords[typeName].push(value.trim())
  }).catch(()=>{})
}
function removeType(typeName: string) { ElMessageBox.confirm(`确定删除"${typeName}"类型？`, '提示').then(() => { delete cfgForm.type_keywords[typeName] }).catch(()=>{}) }
function addType() {
  ElMessageBox.prompt('请输入新报修类型名称（如：网络）', '添加类型').then(({ value }) => {
    if (value?.trim() && !cfgForm.type_keywords[value.trim()]) cfgForm.type_keywords[value.trim()] = []
  }).catch(()=>{})
}
function removeUrgent(i: number) { cfgForm.urgent_keywords.splice(i, 1) }
function addUrgent() {
  ElMessageBox.prompt('请输入紧急关键词', '添加').then(({ value }) => {
    if (value?.trim()) cfgForm.urgent_keywords.push(value.trim())
  }).catch(()=>{})
}

async function loadStats() {
  try {
    const r = await apiGet('/admin/aiAssistant/stats')
    if (r?.code===0) { Object.assign(stats, r.data); renderCharts() }
  } catch(_) {}
}

async function loadHistory() {
  hLoading.value = true
  try {
    const p: any = { page: hQuery.page, limit: hQuery.limit }
    if (hQuery.keyword) p.keyword = hQuery.keyword
    if (hQuery.date_range?.length===2) { p.date_from = hQuery.date_range[0]; p.date_to = hQuery.date_range[1] }
    const r = await apiGet('/admin/aiAssistant/chatHistory', p)
    if (r?.code===0) { histories.value = r.data.list||[]; hTotal.value = r.data.total||0 }
  } finally { hLoading.value = false }
}

function renderCharts() {
  nextTick(() => {
    if (trendChart.value) {
      const c = echarts.init(trendChart.value)
      c.setOption({
        title: { text: '对话趋势（近7天）', left: 'center', textStyle: { fontSize: 14 } },
        tooltip: { trigger: 'axis' },
        xAxis: { type: 'category', data: (stats.trend||[]).map((t:any)=>t.date) },
        yAxis: { type: 'value', name: '对话数' },
        series: [{ data: (stats.trend||[]).map((t:any)=>t.cnt), type: 'line', smooth: true, areaStyle: { opacity: 0.3 }, itemStyle: { color: '#409EFF' } }],
      })
    }
    if (typeChart.value) {
      const c = echarts.init(typeChart.value)
      c.setOption({
        title: { text: '报修类型分布', left: 'center', textStyle: { fontSize: 14 } },
        tooltip: { trigger: 'item' },
        series: [{
          type: 'pie', radius: ['45%','70%'],
          data: (stats.by_type||[]).map((t:any)=>({ name: t.type, value: t.cnt })),
          label: { formatter: '{b}\n{c}单' },
        }],
      })
    }
  })
}

watch(() => activeTab.value, (v) => { if (v==='history') loadHistory(); if (v==='stats') loadStats() })

onMounted(() => { loadConfig(); loadStats() })
</script>

<style scoped>
.ai-page { padding: 16px 20px; background: #f5f7fa; min-height: 100%; }
.page-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px; }
.page-title { margin: 0; font-size: 20px; font-weight: 700; color: #1a1a2e; display: flex; align-items: center; gap: 8px; }
.page-desc { margin: 4px 0 0; font-size: 13px; color: #909399; }
.stats-row { margin-bottom: 16px; }
.stat-card { border-radius: 10px; transition: all .3s; }
.stat-card:hover { transform: translateY(-2px); }
.stat-inner { display: flex; align-items: center; gap: 16px; }
.stat-icon { width: 56px; height: 56px; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #fff; }
.stat-total .stat-icon { background: linear-gradient(135deg, #667eea, #764ba2); }
.stat-online .stat-icon { background: linear-gradient(135deg, #43e97b, #38f9d7); }
.stat-offline .stat-icon { background: linear-gradient(135deg, #f093fb, #f5576c); }
.stat-type .stat-icon { background: linear-gradient(135deg, #4facfe, #00f2fe); }
.stat-value { font-size: 28px; font-weight: 700; color: #303133; line-height: 1.1; }
.stat-label { font-size: 13px; color: #909399; margin-top: 2px; }
.main-tabs { border-radius: 10px; }
.filter-card { margin-bottom: 16px; border-radius: 10px; }
.config-card { border-radius: 10px; }
.table-card { border-radius: 10px; }
.pagination-wrap { margin-top: 16px; display: flex; justify-content: flex-end; }
:deep(.el-table th) { background: #fafbfc; color: #303133; font-weight: 600; }
.kw-group { margin-bottom: 12px; padding: 10px 12px; background: #f9fafb; border-radius: 8px; border: 1px solid #ebeef5; }
.kw-header { display: flex; align-items: center; gap: 8px; margin-bottom: 8px; }
.kw-type-name { font-weight: 600; font-size: 14px; color: #303133; min-width: 60px; }
.kw-tags { display: flex; flex-wrap: wrap; gap: 6px; align-items: center; }
.kw-tag { cursor: pointer; }
.kw-urgent { background: #fef0f0; border-color: #fde2e2; color: #f56c6c; }
</style>
