<template>
  <div class="dashboard">
    <h2 style="margin-bottom:20px;font-size:22px;font-weight:700;color:#1a202c;">📊 数据概览</h2>
    <div class="stat-grid">
      <el-card v-for="(item, idx) in stats" :key="idx" shadow="hover" class="stat-card">
        <div class="stat-body">
          <div class="stat-icon" :style="{ background: item.bg }">{{ item.icon }}</div>
          <div class="stat-info">
            <div class="stat-label">{{ item.label }}</div>
            <div class="stat-value">{{ item.value }}</div>
          </div>
        </div>
      </el-card>
    </div>
    <div class="chart-layout">
      <el-card shadow="hover" class="chart-card"><div ref="incomeChartRef" style="height:340px;"></div></el-card>
      <el-card shadow="hover" class="chart-card"><div ref="repairChartRef" style="height:340px;"></div></el-card>
      <el-card shadow="hover" class="chart-card"><div ref="pieChartRef" style="height:340px;"></div></el-card>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import { apiGet } from '@/utils/request'
import * as echarts from 'echarts'

const incomeChartRef = ref()
const repairChartRef = ref()
const pieChartRef = ref()
let incomeChart: any = null
let repairChart: any = null
let pieChart: any = null

const stats = ref([
  { icon: '🏘️', label: '管理小区', value: 0, bg: 'linear-gradient(135deg,#ebf8ff,#bee3f8)' },
  { icon: '👤', label: '业主总数', value: 0, bg: 'linear-gradient(135deg,#f0fff4,#c6f6d5)' },
  { icon: '💰', label: '本月收费', value: '¥0.00', bg: 'linear-gradient(135deg,#fffbeb,#fef3c7)' },
  { icon: '📋', label: '待缴费总额', value: '¥0.00', bg: 'linear-gradient(135deg,#ffebee,#ef9a9a)' },
  { icon: '🔧', label: '待处理工单', value: 0, bg: 'linear-gradient(135deg,#fff5f5,#fed7d7)' },
])

async function loadData() {
  try {
    const res = await apiGet<any>('/admin/dashboard/statistics')
    if (res.code === 0) stats.value = res.data
  } catch {}
}

async function loadIncomeChart() {
  try {
    const res = await apiGet<any>('/admin/dashboard/incomeChart', { year: new Date().getFullYear() })
    if (res.code !== 0 || !res.data) return
    const { months, data } = res.data
    if (incomeChart) {
      incomeChart.setOption({
        xAxis: { type: 'category', data: months },
        series: [{ data }],
      })
    }
  } catch {}
}

async function loadRepairChart() {
  try {
    const res = await apiGet<any>('/admin/dashboard/repairChart')
    if (res.code !== 0 || !res.data) return
    const { days, counts } = res.data
    if (repairChart) {
      repairChart.setOption({
        xAxis: { type: 'category', data: days },
        series: [{ data: counts }],
      })
    }
  } catch {}
}

async function loadPieChart() {
  try {
    const res = await apiGet<any>('/admin/dashboard/pieChart')
    if (res.code !== 0 || !res.data) return
    if (pieChart) {
      pieChart.setOption({ series: [{ data: res.data }] })
    }
  } catch {}
}

function initCharts() {
  if (incomeChartRef.value) {
    incomeChart = echarts.init(incomeChartRef.value)
    incomeChart.setOption({
      title: { text: '收入趋势 (' + new Date().getFullYear() + ')', textStyle: { fontSize: 15, fontWeight: 600 } },
      tooltip: { trigger: 'axis' },
      grid: { left: 60, right: 20, bottom: 30, top: 40 },
      xAxis: { type: 'category', data: [], axisLine: { lineStyle: { color: '#e2e8f0' } } },
      yAxis: { type: 'value', splitLine: { lineStyle: { color: '#f7f8fc' } } },
      series: [{ type: 'bar', data: [], itemStyle: { color: new echarts.graphic.LinearGradient(0,0,0,1,[{offset:0,color:'#3182ce'},{offset:1,color:'#2b6cb0'}]) }, barRadius: 6 }],
    })
    loadIncomeChart()
  }
  if (repairChartRef.value) {
    repairChart = echarts.init(repairChartRef.value)
    repairChart.setOption({
      title: { text: '近8日报修趋势', textStyle: { fontSize: 15, fontWeight: 600 } },
      tooltip: { trigger: 'axis' },
      grid: { left: 40, right: 20, bottom: 30, top: 40 },
      xAxis: { type: 'category', data: [], axisLine: { lineStyle: { color: '#e2e8f0' } } },
      yAxis: { type: 'value', splitLine: { lineStyle: { color: '#f7f8fc' } } },
      series: [{ type: 'bar', data: [], itemStyle: { color: new echarts.graphic.LinearGradient(0,0,0,1,[{offset:0,color:'#e53e3e'},{offset:1,color:'#c53030'}]) }, barRadius: 6 }],
    })
    loadRepairChart()
  }
  if (pieChartRef.value) {
    pieChart = echarts.init(pieChartRef.value)
    const colors = ['#3182ce', '#38a169', '#d69e2e', '#e53e3e', '#805ad5', '#00b5d8', '#dd6b20']
    pieChart.setOption({
      title: { text: '本月收费 & 待缴费', textStyle: { fontSize: 15, fontWeight: 600 } },
      tooltip: { trigger: 'item', formatter: '{b}: ¥{c} ({d}%)' },
      legend: { orient: 'vertical', left: 'left', top: 50 },
      color: colors,
      series: [{
        type: 'pie',
        radius: ['40%', '70%'],
        center: ['55%', '55%'],
        avoidLabelOverlap: false,
        itemStyle: { borderRadius: 6, borderColor: '#fff', borderWidth: 2 },
        label: { show: true, formatter: '{b}\n¥{c}' },
        emphasis: { label: { fontSize: 16, fontWeight: 'bold' } },
        data: [],
      }],
    })
    loadPieChart()
  }
}

onMounted(() => { loadData(); setTimeout(initCharts, 100) })
onUnmounted(() => { incomeChart?.dispose(); repairChart?.dispose(); pieChart?.dispose() })
</script>

<style scoped>
.dashboard { animation: fadeIn 0.3s ease; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
.stat-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 16px; margin-bottom: 16px; }
@media (max-width: 1400px) { .stat-grid { grid-template-columns: repeat(3, 1fr); } }
@media (max-width: 900px) { .stat-grid { grid-template-columns: repeat(2, 1fr); } }
.stat-card { border-radius: 12px; border: 1px solid #e2e8f0; }
.stat-body { display: flex; align-items: center; gap: 16px; }
.stat-icon { width: 52px; height: 52px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 24px; flex-shrink: 0; }
.stat-info { flex: 1; min-width: 0; }
.stat-label { font-size: 13px; color: #a0aec0; margin-bottom: 4px; }
.stat-value { font-size: 24px; font-weight: 700; color: #1a202c; }

.chart-layout { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; }
.chart-card { border-radius: 12px; border: 1px solid #e2e8f0; }
@media (max-width: 1100px) { .chart-layout { grid-template-columns: 1fr; } }
</style>
