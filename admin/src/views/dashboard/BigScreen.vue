<template>
  <div class="big-screen">
    <div class="bg-grid"></div>
    <div class="bg-particles">
      <span v-for="i in 40" :key="i" class="particle" :style="particleStyle()"></span>
    </div>

    <div class="screen-header">
      <div class="header-left">
        <div class="title-line"></div>
        <h1 class="screen-title">智慧物业数据大屏</h1>
        <div class="title-line" style="margin-left:16px;"></div>
      </div>
      <div class="header-right">
        <span class="time-text">{{ currentTime }}</span>
        <span class="refresh-tag">
          <span class="refresh-dot" :class="{ spinning: refreshing }"></span>
          {{ refreshCountdown }}s
        </span>
      </div>
    </div>

    <div class="kpi-row">
      <div class="kpi-card" v-for="item in kpiItems" :key="item.key" :style="{ borderTopColor: item.color }">
        <div class="kpi-icon" :style="{ color: item.color }">{{ item.icon }}</div>
        <div class="kpi-body">
          <div class="kpi-value">{{ item.prefix || '' }}{{ item.value }}{{ item.suffix || '' }}</div>
          <div class="kpi-label">{{ item.label }}</div>
        </div>
      </div>
    </div>

    <div class="chart-row">
      <div class="panel chart-panel">
        <div class="panel-hd"><span class="panel-title">年度收入趋势</span><span class="panel-sub">元</span></div>
        <div ref="incomeChartRef" class="chart-box"></div>
      </div>
      <div class="panel chart-panel">
        <div class="panel-hd"><span class="panel-title">报修状态分布</span><span class="panel-sub">全部</span></div>
        <div ref="repairPieRef" class="chart-box"></div>
      </div>
      <div class="panel chart-panel">
        <div class="panel-hd"><span class="panel-title">收费项目占比</span><span class="panel-sub">本月</span></div>
        <div ref="chargePieRef" class="chart-box"></div>
      </div>
    </div>

    <div class="list-row">
      <div class="panel list-panel">
        <div class="panel-hd"><span class="panel-title">最新缴费记录</span></div>
        <div class="list-body">
          <div class="list-item" v-for="(item,i) in data?.latest_payments||[]" :key="'p'+i">
            <span class="li-idx">{{ i+1 }}</span>
            <span class="li-name">{{ item.owner_name }}</span>
            <span class="li-tag">{{ item.charge_item_name }}</span>
            <span class="li-val">¥{{ fmt(item.amount) }}</span>
          </div>
          <div class="list-empty" v-if="!data?.latest_payments?.length">暂无数据</div>
        </div>
      </div>
      <div class="panel list-panel">
        <div class="panel-hd"><span class="panel-title">最新报修工单</span></div>
        <div class="list-body">
          <div class="list-item" v-for="(item,i) in data?.latest_repairs||[]" :key="'r'+i">
            <span class="li-idx">{{ i+1 }}</span>
            <span class="li-name">{{ item.owner_name }}</span>
            <span class="li-tag">{{ item.repair_type }}</span>
            <span class="li-status" :class="'rs'+item.status">{{ repairStatus(item.status) }}</span>
          </div>
          <div class="list-empty" v-if="!data?.latest_repairs?.length">暂无数据</div>
        </div>
      </div>
      <div class="panel list-panel">
        <div class="panel-hd"><span class="panel-title">最新投诉建议</span></div>
        <div class="list-body">
          <div class="list-item" v-for="(item,i) in data?.latest_complaints||[]" :key="'c'+i">
            <span class="li-idx">{{ i+1 }}</span>
            <span class="li-name">{{ item.owner_name }}</span>
            <span class="li-tag">{{ item.complaint_type }}</span>
            <span class="li-status" :class="'cs'+item.status">{{ compStatus(item.status) }}</span>
          </div>
          <div class="list-empty" v-if="!data?.latest_complaints?.length">暂无数据</div>
        </div>
      </div>
    </div>

    <div class="screen-footer">
      <span>大圣物业 · 数据大屏</span>
      <span class="ft-sep">|</span>
      <span>数据每60秒自动刷新</span>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue'
import { apiGet } from '@/utils/request'
import * as echarts from 'echarts'

const data = ref<any>(null)
const refreshing = ref(false)
const refreshCountdown = ref(60)
let countdownTimer: any = null

const currentTime = ref('')
let clockTimer: any = null
function updateClock() {
  const d = new Date()
  currentTime.value = d.getFullYear() + '-' +
    String(d.getMonth()+1).padStart(2,'0') + '-' +
    String(d.getDate()).padStart(2,'0') + ' ' +
    String(d.getHours()).padStart(2,'0') + ':' +
    String(d.getMinutes()).padStart(2,'0') + ':' +
    String(d.getSeconds()).padStart(2,'0')
}

const kpiItems = computed(() => {
  const s = data.value?.stats || {}
  const f = data.value?.finance || {}
  const occRate = s.room_count > 0 ? Math.round((s.occupied_count / s.room_count) * 100) : 0
  return [
    { key:'community', icon:'🏘️', label:'管理小区', value: s.community_count||0, color:'#3b82f6', suffix:' 个' },
    { key:'building', icon:'🏗️', label:'楼栋数量', value: s.building_count||0, color:'#6366f1', suffix:' 栋' },
    { key:'room', icon:'🏠', label:'房间总数', value: s.room_count||0, color:'#06b6d4', suffix:' 间' },
    { key:'occupy', icon:'📊', label:'入住率', value: occRate, color:'#10b981', suffix:'%' },
    { key:'owner', icon:'👥', label:'业主总数', value: s.owner_count||0, color:'#f59e0b', suffix:' 人' },
    { key:'staff', icon:'👷', label:'在岗员工', value: s.staff_count||0, color:'#8b5cf6', suffix:' 人' },
    { key:'income', icon:'💰', label:'本月收入', value: f.month_income||0, color:'#f0c060', prefix:'¥', suffix:'' },
    { key:'rate', icon:'📈', label:'年收费率', value: f.collection_rate||0, color:'#22d3ee', suffix:'%' },
  ]
})

const incomeChartRef = ref()
const repairPieRef = ref()
const chargePieRef = ref()
let incomeChart: any = null, repairPieChart: any = null, chargePieChart: any = null

async function loadData() {
  refreshing.value = true
  try {
    const res = await apiGet<any>('/admin/dashboard/bigscreen')
    if (res.code === 0) { data.value = res.data; await nextTick(); renderCharts() }
  } catch {}
  refreshing.value = false
}

function renderCharts() {
  if (!data.value) return
  if (incomeChartRef.value) {
    if (!incomeChart) incomeChart = echarts.init(incomeChartRef.value)
    incomeChart.setOption({
      tooltip: { trigger:'axis', backgroundColor:'rgba(8,14,40,0.9)', borderColor:'#1e4b6e', textStyle:{color:'#c8d6e5',fontSize:11} },
      grid: { left:48, right:14, top:12, bottom:24 },
      xAxis: { type:'category', data:data.value.income_trend.months||[], axisLine:{lineStyle:{color:'#2a3a5c'}}, axisLabel:{color:'#7b93b0',fontSize:10}, axisTick:{show:false} },
      yAxis: { type:'value', splitLine:{lineStyle:{color:'rgba(42,58,92,0.35)',type:'dashed'}}, axisLabel:{color:'#7b93b0',fontSize:10,formatter:(v:number)=>v>=10000?(v/10000).toFixed(1)+'w':v} },
      series: [{ type:'bar', data:data.value.income_trend.data||[], barWidth:18, itemStyle:{ borderRadius:[4,4,0,0], color:new echarts.graphic.LinearGradient(0,0,0,1,[{offset:0,color:'#06b6d4'},{offset:1,color:'#0e5a8a'}]) }, emphasis:{itemStyle:{color:'#22d3ee'}} }],
    }, true)
  }
  if (repairPieRef.value) {
    if (!repairPieChart) repairPieChart = echarts.init(repairPieRef.value)
    repairPieChart.setOption({
      tooltip: { trigger:'item', backgroundColor:'rgba(8,14,40,0.9)', borderColor:'#1e4b6e', textStyle:{color:'#c8d6e5',fontSize:11} },
      legend: { bottom:0, textStyle:{color:'#7b93b0',fontSize:10}, itemWidth:8, itemHeight:8 },
      color: ['#f59e0b','#3b82f6','#06b6d4','#10b981'],
      series: [{ type:'pie', radius:['52%','78%'], center:['50%','45%'], itemStyle:{borderColor:'#0a0e27',borderWidth:3,borderRadius:2}, label:{show:true,position:'outside',color:'#a0b8d0',fontSize:10,formatter:'{b}\n{d}%'}, data:data.value.repair_status_pie||[] }],
    }, true)
  }
  if (chargePieRef.value) {
    if (!chargePieChart) chargePieChart = echarts.init(chargePieRef.value)
    chargePieChart.setOption({
      tooltip: { trigger:'item', backgroundColor:'rgba(8,14,40,0.9)', borderColor:'#1e4b6e', textStyle:{color:'#c8d6e5',fontSize:11}, formatter:'{b}: {c}' },
      legend: { bottom:0, textStyle:{color:'#7b93b0',fontSize:10}, itemWidth:8, itemHeight:8 },
      color: ['#06b6d4','#10b981','#f59e0b','#6366f1','#ec4899','#f97316','#84cc16'],
      series: [{ type:'pie', radius:['50%','75%'], center:['50%','45%'], roseType:'area', itemStyle:{borderColor:'#0a0e27',borderWidth:2,borderRadius:2}, label:{show:true,position:'outside',color:'#a0b8d0',fontSize:10,formatter:'{b}'}, data:data.value.charge_pie||[] }],
    }, true)
  }
}

function particleStyle() {
  return {
    left: Math.random()*100+'%', top: Math.random()*100+'%',
    animationDelay: Math.random()*6+'s', animationDuration: (4+Math.random()*6)+'s',
    width: (1+Math.random()*2)+'px', height: (1+Math.random()*2)+'px',
  }
}
function fmt(v: number) { return Number(v||0).toLocaleString('zh-CN',{minimumFractionDigits:2,maximumFractionDigits:2}) }
function repairStatus(s: number) { const m:Record<number,string>={1:'待派修',2:'处理中',3:'待验收',4:'已完成'}; return m[s]||'未知' }
function compStatus(s: number) { const m:Record<number,string>={1:'待处理',2:'处理中',3:'已完成',4:'已关闭'}; return m[s]||'未知' }

function startRefreshLoop() {
  countdownTimer = setInterval(() => {
    refreshCountdown.value--
    if (refreshCountdown.value <= 0) { refreshCountdown.value = 60; loadData() }
  }, 1000)
}
function handleResize() { incomeChart?.resize(); repairPieChart?.resize(); chargePieChart?.resize() }

onMounted(() => { updateClock(); clockTimer=setInterval(updateClock,1000); loadData(); startRefreshLoop(); window.addEventListener('resize',handleResize) })
onUnmounted(() => { clearInterval(clockTimer); clearInterval(countdownTimer); window.removeEventListener('resize',handleResize); incomeChart?.dispose(); repairPieChart?.dispose(); chargePieChart?.dispose() })
</script>

<style scoped>
.big-screen { position:fixed; inset:0; background:#070b1a; color:#c8d6e5; font-family:'PingFang SC','Microsoft YaHei',sans-serif; overflow:hidden; z-index:200; display:flex; flex-direction:column; padding:0 24px 14px; }
.bg-grid { position:absolute; inset:0; background-image:linear-gradient(rgba(30,60,120,.06) 1px,transparent 1px),linear-gradient(90deg,rgba(30,60,120,.06) 1px,transparent 1px); background-size:50px 50px; pointer-events:none; }
.bg-particles { position:absolute; inset:0; pointer-events:none; overflow:hidden; }
.particle { position:absolute; background:rgba(6,182,212,.3); border-radius:50%; animation:floatUp 6s linear infinite; }
@keyframes floatUp { 0%{transform:translateY(0) translateX(0);opacity:0} 10%{opacity:1} 90%{opacity:1} 100%{transform:translateY(-100vh) translateX(20px);opacity:0} }

.screen-header { position:relative; z-index:2; display:flex; align-items:center; justify-content:space-between; height:60px; flex-shrink:0; border-bottom:1px solid rgba(30,60,120,.25); margin-bottom:6px; }
.header-left { display:flex; align-items:center; }
.title-line { width:32px; height:2px; background:linear-gradient(90deg,transparent,#06b6d4); border-radius:1px; }
.screen-title { font-size:22px; font-weight:700; letter-spacing:3px; background:linear-gradient(90deg,#06b6d4,#22d3ee,#f0c060); -webkit-background-clip:text; -webkit-text-fill-color:transparent; margin:0 16px; }
.header-right { display:flex; align-items:center; gap:18px; }
.time-text { font-size:15px; color:#7b93b0; font-variant-numeric:tabular-nums; letter-spacing:1px; }
.refresh-tag { font-size:12px; color:#4a6480; display:flex; align-items:center; gap:5px; }
.refresh-dot { width:6px; height:6px; border-radius:50%; background:#06b6d4; transition:opacity .3s; }
.refresh-dot.spinning { animation:pulse-dot .6s ease-in-out infinite; }
@keyframes pulse-dot { 0%,100%{opacity:.3} 50%{opacity:1} }

.kpi-row { position:relative; z-index:2; display:grid; grid-template-columns:repeat(8,1fr); gap:10px; flex-shrink:0; margin-bottom:8px; }
.kpi-card { background:rgba(12,20,45,.7); border:1px solid rgba(30,60,120,.2); border-top:2px solid #06b6d4; border-radius:6px; padding:10px 12px; display:flex; align-items:center; gap:10px; backdrop-filter:blur(8px); transition:all .3s; }
.kpi-card:hover { border-color:rgba(6,182,212,.5); transform:translateY(-2px); background:rgba(16,28,60,.8); }
.kpi-icon { font-size:22px; flex-shrink:0; width:36px; text-align:center; }
.kpi-body { min-width:0; }
.kpi-value { font-size:20px; font-weight:700; color:#e0f0ff; white-space:nowrap; line-height:1.2; }
.kpi-label { font-size:11px; color:#5a7a9a; margin-top:1px; }

.chart-row { position:relative; z-index:2; display:grid; grid-template-columns:repeat(3,1fr); gap:10px; flex:1; min-height:0; margin-bottom:8px; }
.list-row { position:relative; z-index:2; display:grid; grid-template-columns:repeat(3,1fr); gap:10px; flex:0 0 190px; }

.panel { background:rgba(12,20,45,.65); border:1px solid rgba(30,60,120,.2); border-radius:8px; backdrop-filter:blur(8px); display:flex; flex-direction:column; overflow:hidden; }
.panel-hd { display:flex; align-items:center; justify-content:space-between; padding:10px 14px 6px; border-bottom:1px solid rgba(30,60,120,.15); flex-shrink:0; }
.panel-title { font-size:13px; font-weight:600; color:#a0c8e0; }
.panel-sub { font-size:11px; color:#4a6480; }
.chart-box { flex:1; min-height:0; }

.list-body { flex:1; overflow-y:auto; padding:4px 8px; }
.list-body::-webkit-scrollbar { width:3px; }
.list-body::-webkit-scrollbar-thumb { background:rgba(30,60,120,.35); border-radius:2px; }
.list-item { display:flex; align-items:center; gap:8px; padding:7px 6px; border-radius:4px; transition:background .2s; }
.list-item:hover { background:rgba(30,60,120,.15); }
.li-idx { width:18px; height:18px; border-radius:50%; background:rgba(6,182,212,.15); color:#06b6d4; font-size:10px; font-weight:700; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.li-name { flex:1; font-size:12px; color:#c8d6e5; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.li-tag { font-size:10px; color:#5a7a9a; background:rgba(30,60,120,.2); padding:1px 6px; border-radius:3px; flex-shrink:0; max-width:80px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.li-val { font-size:12px; font-weight:600; color:#f0c060; flex-shrink:0; }
.li-status { font-size:10px; padding:1px 6px; border-radius:3px; flex-shrink:0; }
.rs1,.cs1 { background:rgba(245,158,11,.15); color:#f59e0b; }
.rs2,.cs2 { background:rgba(59,130,246,.15); color:#3b82f6; }
.rs3 { background:rgba(6,182,212,.15); color:#06b6d4; }
.rs4,.cs3 { background:rgba(16,185,129,.15); color:#10b981; }
.cs4 { background:rgba(100,120,140,.15); color:#647890; }
.list-empty { text-align:center; color:#4a6480; font-size:12px; padding:16px 0; }

.screen-footer { position:relative; z-index:2; display:flex; align-items:center; justify-content:center; gap:8px; padding-top:6px; font-size:11px; color:#3a5a7a; flex-shrink:0; }
.ft-sep { color:#1e3c5e; }
</style>
