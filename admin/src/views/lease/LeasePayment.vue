<template>
  <div class="lease-payment-page">
    <!-- 头部 -->
    <div class="page-hero">
      <div class="hero-content">
        <div class="hero-left">
          <div class="hero-icon"><el-icon :size="28"><Money /></el-icon></div>
          <div class="hero-text">
            <h1>租金支付记录</h1>
            <p>实时追踪每一笔租金流水，掌握收入动态与逾期状况</p>
          </div>
        </div>
        <div class="hero-actions">
          <el-button size="large" plain class="btn-export">
            <el-icon><Download /></el-icon><span>导出报表</span>
          </el-button>
        </div>
      </div>
    </div>

    <!-- 收入统计看板 -->
    <div class="kpi-grid">
      <div class="kpi-card kpi-total">
        <div class="kpi-left"><div class="kpi-icon" style="background:linear-gradient(135deg,#667eea,#764ba2)"><el-icon :size="20"><TrendCharts /></el-icon></div></div>
        <div class="kpi-body"><span class="kpi-value">&yen;{{ fmtMoney(stats.totalAmount) }}</span><span class="kpi-label">累计收款</span></div>
        <div class="kpi-bar"></div>
      </div>
      <div class="kpi-card kpi-month">
        <div class="kpi-left"><div class="kpi-icon" style="background:linear-gradient(135deg,#43e97b,#38f9d7)"><el-icon :size="20"><DataAnalysis /></el-icon></div></div>
        <div class="kpi-body"><span class="kpi-value">&yen;{{ fmtMoney(stats.monthAmount) }}</span><span class="kpi-label">本月收款</span></div>
        <div class="kpi-bar"></div>
      </div>
      <div class="kpi-card kpi-pending">
        <div class="kpi-left"><div class="kpi-icon" style="background:linear-gradient(135deg,#f59e0b,#fbbf24)"><el-icon :size="20"><WarningFilled /></el-icon></div></div>
        <div class="kpi-body"><span class="kpi-value">&yen;{{ fmtMoney(stats.pendingAmount) }}</span><span class="kpi-label">待收金额</span></div>
        <div class="kpi-bar"></div>
      </div>
      <div class="kpi-card kpi-overdue">
        <div class="kpi-left"><div class="kpi-icon" style="background:linear-gradient(135deg,#f093fb,#f5576c)"><el-icon :size="20"><Clock /></el-icon></div></div>
        <div class="kpi-body"><span class="kpi-value">{{ stats.overdueCount }}</span><span class="kpi-label">逾期笔数</span></div>
        <div class="kpi-bar"></div>
      </div>
    </div>

    <!-- 筛选栏 -->
    <div class="filter-bar">
      <div class="filter-left">
        <el-input v-model="query.keyword" placeholder="搜索支付编号或备注..." clearable class="search-inp" @keyup.enter="handleSearch" @clear="handleSearch"><template #prefix><el-icon><Search /></el-icon></template></el-input>
        <el-select v-model="query.payment_type" placeholder="支付类型" clearable class="filter-sel" @change="handleSearch">
          <el-option label="正常收款" value="正常收款"/><el-option label="逾期补缴" value="逾期补缴"/><el-option label="预收款" value="预收款"/><el-option label="退款" value="退款"/>
        </el-select>
        <el-select v-model="query.pay_method" placeholder="支付方式" clearable class="filter-sel" @change="handleSearch">
          <el-option label="微信支付" value="微信支付"/><el-option label="支付宝" value="支付宝"/><el-option label="银行转账" value="银行转账"/><el-option label="现金" value="现金"/><el-option label="POS刷卡" value="POS刷卡"/>
        </el-select>
        <el-select v-model="query.status" placeholder="支付状态" clearable class="filter-sel" @change="handleSearch">
          <el-option label="已支付" value="已支付"/><el-option label="待支付" value="待支付"/><el-option label="已退款" value="已退款"/><el-option label="部分支付" value="部分支付"/>
        </el-select>
        <el-select v-model="query.community_id" placeholder="所属小区" clearable class="filter-sel" @change="handleSearch">
          <el-option v-for="c in communities" :key="c.id" :label="c.name" :value="c.id"/>
        </el-select>
      </div>
      <div class="filter-right">
        <el-button @click="resetQuery" text><el-icon><Refresh /></el-icon>重置</el-button>
      </div>
    </div>

    <!-- 支付记录表格 -->
    <el-card shadow="never" class="table-card">
      <el-table :data="list" v-loading="loading" stripe class="modern-table" @row-click="showDetail">
        <el-table-column type="index" label="#" width="55"/>
        <el-table-column prop="payment_no" label="支付编号" width="150"><template #default="{row}"><span class="pay-no">{{ row.payment_no||'-' }}</span></template></el-table-column>
        <el-table-column prop="contract_id" label="合同ID" width="85" align="center"/>
        <el-table-column prop="period_name" label="账期" width="140"><template #default="{row}"><span class="period">{{ row.period_name||'-' }}</span></template></el-table-column>
        <el-table-column prop="period_start" label="账期开始" width="115"/>
        <el-table-column prop="period_end" label="账期结束" width="115"/>
        <el-table-column prop="rent_amount" label="租金(元)" width="110" align="right"><template #default="{row}"><span class="money">{{ row.rent_amount||0 }}</span></template></el-table-column>
        <el-table-column prop="property_tax_amount" label="物业费(元)" width="110" align="right"><template #default="{row}">{{ row.property_tax_amount||0 }}</template></el-table-column>
        <el-table-column prop="other_amount" label="其他(元)" width="100" align="right"/>
        <el-table-column prop="total_amount" label="应收金额" width="110" align="right"><template #default="{row}"><span class="money-lg">{{ row.total_amount||0 }}</span></template></el-table-column>
        <el-table-column prop="paid_amount" label="实付金额" width="120" align="right"><template #default="{row}"><span class="money-paid">&yen;{{ row.paid_amount||0 }}</span></template></el-table-column>
        <el-table-column prop="late_fee" label="逾期费(元)" width="105" align="right"><template #default="{row}"><span :class="(row.late_fee||0)>0?'late-fee':''">{{ row.late_fee||0 }}</span></template></el-table-column>
        <el-table-column prop="pay_method" label="支付方式" width="100" align="center"><template #default="{row}">{{ row.pay_method||'-' }}</template></el-table-column>
        <el-table-column prop="status" label="状态" width="90" align="center"><template #default="{row}"><el-tag :type="paymentStatusTag(row.status)" size="small" effect="dark" round>{{ row.status||'未知' }}</el-tag></template></el-table-column>
        <el-table-column prop="pay_time" label="支付时间" width="170" sortable><template #default="{row}"><div class="time-cell"><el-icon :size="14"><Clock /></el-icon><span>{{ row.pay_time||'-' }}</span></div></template></el-table-column>
        <el-table-column label="详情" width="70" fixed="right" align="center"><template #default="{row}"><el-button link type="primary" size="small" @click.stop="showDetail(row)"><el-icon><View /></el-icon></el-button></template></el-table-column>
      </el-table>
      <div v-if="list.length===0&&!loading" class="empty-state-inline">
        <el-icon :size="48"><DocumentRemove /></el-icon><p>暂无支付记录</p>
      </div>
      <div class="pagination-inner" v-if="total>query.limit">
        <el-pagination v-model:current-page="query.page" v-model:page-size="query.limit" :total="total" :page-sizes="[15,30,50,100]" layout="total, sizes, prev, pager, next, jumper" background/>
      </div>
    </el-card>

    <!-- 详情弹窗 -->
    <el-dialog v-model="detailVisible" title="支付记录详情" width="520px" destroy-on-close class="detail-dialog">
      <div class="detail-body" v-if="detailRow">
        <div class="detail-item"><span class="dl">支付编号</span><span class="dv mono">{{ detailRow.payment_no||'-' }}</span></div>
        <div class="detail-item"><span class="dl">合同ID</span><span class="dv">{{ detailRow.contract_id||'-' }}</span></div>
        <div class="detail-item"><span class="dl">账期名称</span><span class="dv highlight">{{ detailRow.period_name||'-' }}</span></div>
        <div class="detail-item"><span class="dl">账期范围</span><span class="dv">{{ detailRow.period_start||'-' }} ~ {{ detailRow.period_end||'-' }}</span></div>
        <div class="detail-split">费用明细</div>
        <div class="detail-item"><span class="dl">租金</span><span class="dv money-text">&yen;{{ detailRow.rent_amount||0 }}</span></div>
        <div class="detail-item"><span class="dl">物业费</span><span class="dv">&yen;{{ detailRow.property_tax_amount||0 }}</span></div>
        <div class="detail-item"><span class="dl">其他费用</span><span class="dv">&yen;{{ detailRow.other_amount||0 }}</span></div>
        <div class="detail-item"><span class="dl">逾期费</span><span class="dv late-text">&yen;{{ detailRow.late_fee||0 }}</span></div>
        <div class="detail-item total-row"><span class="dl">应收合计</span><span class="dv total-val">&yen;{{ detailRow.total_amount||0 }}</span></div>
        <div class="detail-item total-row"><span class="dl">实付金额</span><span class="dv paid-val">&yen;{{ detailRow.paid_amount||0 }}</span></div>
        <div class="detail-split">支付信息</div>
        <div class="detail-item"><span class="dl">支付类型</span><span class="dv">{{ detailRow.payment_type||'-' }}</span></div>
        <div class="detail-item"><span class="dl">支付方式</span><span class="dv">{{ detailRow.pay_method||'-' }}</span></div>
        <div class="detail-item"><span class="dl">交易流水号</span><span class="dv mono">{{ detailRow.trade_no||'-' }}</span></div>
        <div class="detail-item"><span class="dl">支付时间</span><span class="dv">{{ detailRow.pay_time||'-' }}</span></div>
        <div class="detail-item"><span class="dl">操作员ID</span><span class="dv">{{ detailRow.operator_id||'-' }}</span></div>
        <div class="detail-item" v-if="detailRow.remark"><span class="dl">备注</span><span class="dv">{{ detailRow.remark }}</span></div>
      </div>
      <template #footer><el-button @click="detailVisible=false">关闭</el-button></template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { apiGet } from '@/utils/request'
import { Money, Download, TrendCharts, DataAnalysis, WarningFilled, Clock, Search, Refresh, View, DocumentRemove } from '@element-plus/icons-vue'

const list = ref<any[]>([])
const total = ref(0)
const loading = ref(false)
const detailVisible = ref(false)
const detailRow = ref<any>(null)

const query = reactive({ page:1, limit:15, keyword:'', payment_type:'', pay_method:'', status:'', community_id:undefined as any })
const communities = ref<any[]>([])

const stats = computed(()=>{
  const data = list.value||[]
  const now = new Date(); const monthPrefix = now.getFullYear()+'-'+String(now.getMonth()+1).padStart(2,'0')
  const monthData = data.filter((d:any)=>d.pay_time&&d.pay_time.startsWith(monthPrefix))
  const pending = data.filter((d:any)=>d.status==='待支付'||d.status==='未支付')
  const overdue = data.filter((d:any)=>d.status==='逾期'||(d.late_fee||0)>0)
  return {
    totalAmount: data.reduce((s:number,d:any)=>s+(Number(d.paid_amount)||0),0),
    monthAmount: monthData.reduce((s:number,d:any)=>s+(Number(d.paid_amount)||0),0),
    pendingAmount: data.filter((d:any)=>d.status==='待支付'||d.status==='未支付').reduce((s:number,d:any)=>s+(Number(d.total_amount)||0)-(Number(d.paid_amount)||0),0),
    overdueCount: overdue.length
  }
})

function fmtMoney(v:number){return v>=10000?(v/10000).toFixed(1)+'万':v.toLocaleString()}
function paymentStatusTag(s:string){
  const m:Record<string,string>={'已支付':'success','待支付':'warning','已退款':'info','逾期':'danger','部分支付':''}
  return m[s]||'info'
}

function handleSearch(){query.page=1;loadData()}
function resetQuery(){query.keyword='';query.payment_type='';query.pay_method='';query.status='';query.community_id=undefined;query.page=1;loadData()}

async function loadData(){
  loading.value=true
  try{
    const p:any={page:query.page,limit:query.limit}
    if(query.keyword)p.keyword=query.keyword
    if(query.payment_type)p.payment_type=query.payment_type
    if(query.pay_method)p.pay_method=query.pay_method
    if(query.status)p.status=query.status
    if(query.community_id)p.community_id=query.community_id
    const res:any=await apiGet('/admin/lease/leasePaymentList',p)
    if(res&&(res.code===0||res.code===undefined)){list.value=res.data||[];total.value=res.count||0}
  }catch(_){list.value=[];total.value=0}
  finally{loading.value=false}
}

function showDetail(row:any){detailRow.value=row;detailVisible.value=true}

onMounted(async ()=>{
  try{const r:any=await apiGet('/admin/community/list',{limit:999});communities.value=r.data?.list||r.data||[]}catch(_){}
  loadData()
})
watch([()=>query.page,()=>query.limit],()=>loadData())
</script>

<style scoped>
.lease-payment-page{min-height:calc(100vh - 100px)}
.page-hero{background:linear-gradient(135deg,#0f172a 0%,#1e3a5f 50%,#2563eb 100%);border-radius:16px;padding:28px 32px;margin-bottom:24px;position:relative;overflow:hidden}
.page-hero::after{content:'';position:absolute;right:40px;top:-30px;width:160px;height:160px;border-radius:50%;border:2px dashed rgba(255,255,255,.08)}
.hero-content{display:flex;justify-content:space-between;align-items:center;position:relative;z-index:1}
.hero-left{display:flex;align-items:center;gap:20px}
.hero-icon{width:56px;height:56px;border-radius:14px;background:rgba(255,255,255,.12);display:flex;align-items:center;justify-content:center;color:#fff;backdrop-filter:blur(10px)}
.hero-text h1{margin:0;color:#fff;font-size:22px;font-weight:700}
.hero-text p{margin:6px 0 0;color:rgba(255,255,255,.7);font-size:13px}
.btn-export{height:42px;padding:0 24px;font-weight:600;font-size:14px;border-radius:10px;color:#fff;border:1.5px solid rgba(255,255,255,.3);background:rgba(255,255,255,.08);backdrop-filter:blur(10px)}
.btn-export:hover{background:rgba(255,255,255,.18);border-color:rgba(255,255,255,.5);transform:translateY(-1px)}

.kpi-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px}
.kpi-card{background:#fff;border-radius:14px;padding:20px 24px;border:1px solid #e8ecf1;display:flex;align-items:center;gap:16px;transition:all .3s;position:relative;overflow:hidden}
.kpi-card .kpi-bar{position:absolute;top:0;left:0;width:4px;height:100%}
.kpi-total .kpi-bar{background:linear-gradient(180deg,#667eea,#764ba2)}
.kpi-month .kpi-bar{background:linear-gradient(180deg,#43e97b,#38f9d7)}
.kpi-pending .kpi-bar{background:linear-gradient(180deg,#f59e0b,#fbbf24)}
.kpi-overdue .kpi-bar{background:linear-gradient(180deg,#f093fb,#f5576c)}
.kpi-card:hover{transform:translateY(-2px);box-shadow:0 8px 25px rgba(0,0,0,.08);border-color:#c8d6e5}
.kpi-icon{width:44px;height:44px;border-radius:11px;display:flex;align-items:center;justify-content:center;color:#fff;flex-shrink:0}
.kpi-value{font-size:24px;font-weight:800;color:#1a202c;line-height:1;display:block}
.kpi-label{font-size:12px;color:#718096;margin-top:4px;display:block}

.filter-bar{background:#fff;border-radius:14px;padding:16px 20px;margin-bottom:20px;border:1px solid #e8ecf1;display:flex;justify-content:space-between;align-items:center;gap:16px}
.filter-left{display:flex;gap:12px;flex:1;flex-wrap:wrap}.search-inp{width:240px}.search-inp :deep(.el-input__wrapper){border-radius:10px}.filter-sel{width:150px}.filter-sel :deep(.el-input__wrapper){border-radius:10px}

.table-card{border-radius:14px;border:1px solid #e8ecf1;overflow:hidden}.table-card :deep(.el-card__body){padding:0}
.modern-table :deep(.el-table__header th){background:#f8fafc;font-weight:600;color:#475569;font-size:13px}
.modern-table :deep(.el-table__row){cursor:pointer;transition:background .2s}
.modern-table :deep(.el-table__row:hover){background:#f8faff}
.pay-no{font-weight:600;color:#2563eb;font-family:monospace;font-size:13px}
.period{font-weight:500;color:#1e3a5f}
.money{color:#dc2626;font-weight:600}.money-lg{color:#dc2626;font-weight:700;font-size:14px}
.money-paid{color:#16a34a;font-weight:700;font-size:14px}
.late-fee{color:#dc2626;font-weight:700}
.time-cell{display:flex;align-items:center;gap:6px;color:#64748b;font-size:13px}

.empty-state-inline{display:flex;flex-direction:column;align-items:center;justify-content:center;padding:60px 20px;color:#cbd5e1}.empty-state-inline p{color:#94a3b8;font-size:14px;margin-top:12px}
.pagination-inner{display:flex;justify-content:flex-end;padding:16px}

.detail-dialog :deep(.el-dialog__header){border-bottom:1px solid #f0f2f5;padding:20px 24px}
.detail-dialog :deep(.el-dialog__body){padding:24px}
.detail-body{display:flex;flex-direction:column;gap:10px}
.detail-item{display:flex;align-items:center;padding:10px 16px;background:#f8fafc;border-radius:10px;border:1px solid #f0f2f5}
.detail-item .dl{width:100px;font-size:13px;color:#64748b;font-weight:500;flex-shrink:0}
.detail-item .dv{font-size:14px;color:#1a202c;font-weight:500}.dv.mono{font-family:monospace;color:#2563eb}.dv.highlight{color:#2563eb;font-weight:600}
.dv.money-text{color:#dc2626;font-weight:600}.dv.late-text{color:#dc2626;font-weight:600}
.detail-split{font-size:12px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px;padding:6px 0;border-bottom:1px solid #f0f2f5;margin-bottom:2px}
.total-row{background:#fef3c7;border-color:#fde68a}.total-row .dl{color:#92400e}.total-val{font-size:18px;font-weight:800;color:#dc2626}
.paid-val{font-size:18px;font-weight:800;color:#16a34a}

@media(max-width:1200px){.kpi-grid{grid-template-columns:repeat(2,1fr)}}
@media(max-width:768px){.kpi-grid{grid-template-columns:1fr 1fr}.filter-bar{flex-direction:column}.filter-left{flex-direction:column}.search-inp,.filter-sel{width:100%!important}}
</style>
