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
        <el-row :gutter="16" style="margin-bottom:16px" class="stats-tab-charts">
          <el-col :span="12">
            <el-card shadow="hover"><div ref="trendChart" class="chart-type" style="height:320px"></div></el-card>
          </el-col>
          <el-col :span="12">
            <el-card shadow="hover"><div ref="statusChart" class="chart-status" style="height:320px"></div></el-card>
          </el-col>
        </el-row>

        <!-- 报修类型明细表 -->
        <el-row :gutter="16" style="margin-bottom:16px">
          <el-col :span="12">
            <el-card shadow="never">
              <template #header><span style="font-weight:600">📋 报修类型分布（共 {{ stats.repair_total||0 }} 单）</span></template>
              <el-table :data="stats.by_type||[]" stripe size="small" show-summary :summary-method="typeSummary">
                <el-table-column prop="type_name" label="报修类型" min-width="120" />
                <el-table-column prop="count" label="单数" width="80" align="center">
                  <template #default="{row}"><el-tag size="small">{{ row.count }}</el-tag></template>
                </el-table-column>
                <el-table-column label="占比" width="90" align="center">
                  <template #default="{row}">{{ ((row.count/(stats.repair_total||1))*100).toFixed(1) }}%</template>
                </el-table-column>
              </el-table>
            </el-card>
          </el-col>
          <el-col :span="12">
            <el-card shadow="never">
              <template #header><span style="font-weight:600">📊 报修单状态分布</span></template>
              <el-table :data="stats.status_list||[]" stripe size="small">
                <el-table-column prop="status_name" label="状态" min-width="100" />
                <el-table-column prop="count" label="数量" width="80" align="center">
                  <template #default="{row}"><el-tag :type="statusTagType(row.status)" size="small">{{ row.count }}</el-tag></template>
                </el-table-column>
              </el-table>
            </el-card>
          </el-col>
        </el-row>

        <!-- AI 识别类型统计 -->
        <el-card shadow="never">
          <template #header><span style="font-weight:600">🤖 AI 识别报修类型（来自对话记录）</span></template>
          <el-table :data="stats.ai_types||[]" stripe size="small">
            <el-table-column prop="type" label="AI 分类" min-width="120">
              <template #default="{row}"><el-tag type="warning">{{ row.type }}</el-tag></template>
            </el-table-column>
            <el-table-column prop="count" label="生成单数" width="100" align="center">
              <template #default="{row}"><strong>{{ row.count }}</strong></template>
            </el-table-column>
          </el-table>
        </el-card>
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
const trendChart = ref(), typeChart = ref(), statusChart = ref()

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
    setTimeout(() => {
      // 左侧：报修类型分布 - 饼图
      const el1 = trendChart.value || document.querySelector('.chart-type')
      if (el1 && el1.offsetWidth > 0) {
        const c1 = echarts.init(el1)
        const typeData = (stats.by_type||[]).map((t:any)=>({ name: t.type_name, value: t.count }))
        c1.setOption({
          title: { text: '报修类型分布', subtext: `共 ${stats.repair_total||0} 单`, left: 'center', textStyle: { fontSize: 15, fontWeight:'bold' }, subtextStyle:{fontSize:12,color:'#999'} },
          tooltip: { trigger: 'item', formatter: '{b}: {c}单 ({d}%)' },
          legend: { orient: 'vertical', left: 'left', top: 'middle', type: 'scroll' },
          color: ['#5470C6','#91CC75','#FAC858','#EE6666','#73C0DE','#3BA272','#FC8452','#9A60B4','#EA7CCC'],
          series: [{
            type: 'pie', radius: ['40%', '68%'], center: ['60%', '55%'],
            data: typeData,
            label: { formatter: '{b}\n{d}%', fontSize: 11 },
            emphasis: { itemStyle: { shadowBlur: 10, shadowOffsetX: 0, shadowColor: 'rgba(0,0,0,0.2)' } },
            animationType: 'scale',
          }],
        })
        c1.resize()
      }
      // 右侧：状态分布 - 饼图
      const el2 = statusChart.value || document.querySelector('.chart-status')
      if (el2 && el2.offsetWidth > 0) {
        const c2 = echarts.init(el2)
        const statusData = (stats.status_list||[]).map((s:any)=>({ name: s.status_name, value: s.count }))
        const statusColorMap: Record<string,string> = {'待接单':'#909399','已接单':'#E6A23C','维修中':'#409EFF','已完成':'#67C23A','已评价':'#67C23A','已取消':'#F56C6C','已关闭':'#909399'}
        c2.setOption({
          title: { text: '报修单状态分布', left: 'center', textStyle: { fontSize: 15, fontWeight:'bold' } },
          tooltip: { trigger: 'item', formatter: '{b}: {c}单 ({d}%)' },
          legend: { orient: 'vertical', left: 'left', top: 'middle', type: 'scroll' },
          color: statusData.map((d:any)=>statusColorMap[d.name]||'#5470C6'),
          series: [{
            type: 'pie', radius: ['40%', '68%'], center: ['60%', '55%'],
            data: statusData,
            label: { formatter: '{b}\n{d}%', fontSize: 11 },
            emphasis: { itemStyle: { shadowBlur: 10, shadowOffsetX: 0, shadowColor: 'rgba(0,0,0,0.2)' } },
            animationType: 'scale',
          }],
        })
        c2.resize()
      }
    }, 300)
  })
}

function typeSummary(param: any) {
  const total = param.data.reduce((s:number, r:any) => s + r.count, 0)
  return ['合计', total, '100%']
}
function statusTagType(status: number): string {
  const m: any = {'0':'info','1':'warning','2':'primary','3':'success','4':'success','5':'danger','-1':'info'}
  return (m[String(status)] as string) || 'info'
}

watch(() => activeTab.value, (v) => { if (v==='history') loadHistory(); if (v==='stats') loadStats() })

onMounted(() => { loadConfig(); /* loadStats deferred to tab switch */ })
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
