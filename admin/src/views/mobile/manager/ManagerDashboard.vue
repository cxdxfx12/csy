<template>
  <MobileLayout title="领导驾驶舱">
    <div class="md">
      <div class="md-date">{{ now }}</div>
      <div class="md-grid">
        <div v-for="k in kpis" class="md-card">
          <div class="md-icon">{{ k.icon }}</div>
          <div class="md-val">{{ k.val }}</div>
          <div class="md-lbl">{{ k.lbl }}</div>
        </div>
      </div>
      <div class="md-chart-title">📈 收入趋势</div>
      <div ref="chartRef" style="height:200px;background:#fff;border-radius:12px;padding:8px;"></div>
    </div>
  </MobileLayout>
</template>
<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import MobileLayout from '../MobileLayout.vue'

const now = ref('')
const chartRef = ref()
let chart: any = null

interface KpiItem { icon: string; val: string; lbl: string }

const kpis = ref<KpiItem[]>([
  { icon: '🏘️', val: '-', lbl: '小区' },
  { icon: '👤', val: '-', lbl: '业主' },
  { icon: '💰', val: '-', lbl: '本月收费' },
  { icon: '🔧', val: '-', lbl: '待处理' },
])

onMounted(async () => {
  now.value = new Date().toLocaleDateString('zh-CN', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  })
  try {
    const r = await fetch('/api/manager/dashboard', {
      headers: { Authorization: 'Bearer ' + localStorage.getItem('manager_token') },
    })
    const d = await r.json()
    if (d.code === 0) {
      const t = d.data
      kpis.value = [
        { icon: '🏘️', val: t.community_count || '-', lbl: '小区' },
        { icon: '👤', val: t.owner_count || '-', lbl: '业主' },
        { icon: '💰', val: '¥' + (t.monthly_income || '0'), lbl: '本月收费' },
        { icon: '🔧', val: t.pending_repair || '0', lbl: '待处理' },
      ]
    }
  } catch (e) {}

  import('echarts').then((e) => {
    chart = e.init(chartRef.value)
    chart.setOption({
      tooltip: { trigger: 'axis' },
      grid: { left: 30, right: 10, bottom: 20, top: 10 },
      xAxis: {
        type: 'category',
        data: ['1月', '2月', '3月', '4月', '5月', '6月'],
        axisLabel: { fontSize: 10 },
      },
      yAxis: {
        type: 'value',
        splitLine: { lineStyle: { color: '#f0f0f0' } },
      },
      series: [
        {
          type: 'bar',
          data: [12, 18, 15, 22, 20, 28],
          itemStyle: {
            color: new e.graphic.LinearGradient(0, 0, 0, 1, [
              { offset: 0, color: '#3182ce' },
              { offset: 1, color: '#2b6cb0' },
            ]),
          },
          barRadius: 4,
        },
      ],
    })
  })
})

onUnmounted(() => {
  chart?.dispose()
})
</script>
<style scoped>
.md { padding: 0 0 20px; }
.md-date { font-size: 14px; color: #a0aec0; margin-bottom: 16px; text-align: center; }
.md-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 20px; }
.md-card { background: #fff; border-radius: 12px; padding: 16px; text-align: center; border: 1px solid #e2e8f0; }
.md-icon { font-size: 28px; margin-bottom: 6px; }
.md-val { font-size: 22px; font-weight: 700; color: #1a202c; margin-bottom: 2px; }
.md-lbl { font-size: 12px; color: #a0aec0; }
.md-chart-title { font-size: 15px; font-weight: 600; color: #1a202c; margin-bottom: 10px; }
</style>
