<template>
  <div class="lease-termination-page">
    <!-- 头部 -->
    <div class="page-hero">
      <div class="hero-content">
        <div class="hero-left">
          <div class="hero-icon"><el-icon :size="28"><Close /></el-icon></div>
          <div class="hero-text">
            <h1>退租记录</h1>
            <p>管理所有合同退租流程，包括验房、费用结算、押金退还等</p>
          </div>
        </div>
        <div class="hero-actions">
          <el-button type="primary" size="large" @click="openForm()" class="btn-create">
            <el-icon><Plus /></el-icon><span>登记退租</span>
          </el-button>
        </div>
      </div>
    </div>

    <!-- 统计卡片 -->
    <div class="stats-row">
      <div class="stat-card">
        <div class="stat-icon" style="background:linear-gradient(135deg,#667eea,#764ba2)"><el-icon :size="22"><List /></el-icon></div>
        <div class="stat-info"><span class="stat-num">{{ stats.total }}</span><span class="stat-lbl">退租总数</span></div>
      </div>
      <div class="stat-card">
        <div class="stat-icon" style="background:linear-gradient(135deg,#43e97b,#38f9d7)"><el-icon :size="22"><CircleCheck /></el-icon></div>
        <div class="stat-info"><span class="stat-num">{{ stats.settled }}</span><span class="stat-lbl">已结算</span></div>
      </div>
      <div class="stat-card">
        <div class="stat-icon" style="background:linear-gradient(135deg,#f59e0b,#fbbf24)"><el-icon :size="22"><WarningFilled /></el-icon></div>
        <div class="stat-info"><span class="stat-num">{{ stats.pending }}</span><span class="stat-lbl">待结算</span></div>
      </div>
      <div class="stat-card">
        <div class="stat-icon" style="background:linear-gradient(135deg,#f093fb,#f5576c)"><el-icon :size="22"><Money /></el-icon></div>
        <div class="stat-info"><span class="stat-num">&yen;{{ fmtMoney(stats.totalRefund) }}</span><span class="stat-lbl">累计退还押金</span></div>
      </div>
    </div>

    <!-- 筛选栏 -->
    <div class="filter-bar">
      <div class="filter-left">
        <el-input v-model="query.keyword" placeholder="搜索退租原因或备注..." clearable class="search-inp" @keyup.enter="handleSearch" @clear="handleSearch"><template #prefix><el-icon><Search /></el-icon></template></el-input>
        <el-select v-model="query.termination_type" placeholder="退租类型" clearable class="filter-sel" @change="handleSearch">
          <el-option label="到期退租" value="到期退租"/><el-option label="提前退租" value="提前退租"/><el-option label="违约退租" value="违约退租"/><el-option label="协商退租" value="协商退租"/>
        </el-select>
        <el-select v-model="query.community_id" placeholder="所属小区" clearable class="filter-sel" @change="handleSearch">
          <el-option v-for="c in communities" :key="c.id" :label="c.name" :value="c.id"/>
        </el-select>
      </div>
      <div class="filter-right">
        <el-button @click="resetQuery" text><el-icon><Refresh /></el-icon>重置</el-button>
      </div>
    </div>

    <!-- 退租记录表格 -->
    <el-card shadow="never" class="table-card">
      <el-table :data="list" v-loading="loading" stripe class="modern-table">
        <el-table-column type="index" label="#" width="55"/>
        <el-table-column prop="contract_id" label="合同ID" width="85" align="center"><template #default="{row}"><el-tag size="small" effect="plain" type="info">{{ row.contract_id }}</el-tag></template></el-table-column>
        <el-table-column prop="termination_type" label="退租类型" width="110"><template #default="{row}"><el-tag size="small" effect="dark" :type="termTypeTag(row.termination_type)" round>{{ row.termination_type||'未知' }}</el-tag></template></el-table-column>
        <el-table-column prop="apply_time" label="申请时间" width="115"/>
        <el-table-column prop="actual_terminate_time" label="实际退租" width="115"/>
        <el-table-column prop="reason" label="退租原因" min-width="160"><template #default="{row}"><span class="reason-text">{{ row.reason||'-' }}</span></template></el-table-column>
        <el-table-column prop="deposit_refund" label="押金退还" width="110" align="right"><template #default="{row}"><span class="refund-amount">&yen;{{ row.deposit_refund||0 }}</span></template></el-table-column>
        <el-table-column prop="deposit_deduct" label="押金扣除" width="110" align="right"><template #default="{row}"><span :class="(row.deposit_deduct||0)>0?'deduct-amount':''">&yen;{{ row.deposit_deduct||0 }}</span></template></el-table-column>
        <el-table-column prop="settlement_amount" label="结算金额" width="115" align="right"><template #default="{row}"><span class="settle-amount">&yen;{{ row.settlement_amount||0 }}</span></template></el-table-column>
        <el-table-column prop="settlement_time" label="结算时间" width="115"/>
        <el-table-column label="操作" width="160" fixed="right"><template #default="{row}">
          <el-button size="small" plain type="primary" @click="openForm(row)">编辑</el-button>
          <el-popconfirm title="确定删除？" @confirm="handleDelete(row)"><template #reference><el-button size="small" plain type="danger">删除</el-button></template></el-popconfirm>
        </template></el-table-column>
      </el-table>
      <div v-if="list.length===0&&!loading" class="empty-state-inline">
        <el-icon :size="48"><DocumentRemove /></el-icon><p>暂无退租记录</p>
      </div>
      <div class="pagination-inner" v-if="total>query.limit">
        <el-pagination v-model:current-page="query.page" v-model:page-size="query.limit" :total="total" :page-sizes="[15,30,50,100]" layout="total, sizes, prev, pager, next, jumper" background/>
      </div>
    </el-card>

    <!-- 编辑弹窗 -->
    <el-dialog v-model="dialogVisible" :title="editId?'编辑退租记录':'登记退租'" width="780px" destroy-on-close :close-on-click-modal="false" class="termination-dialog">
      <el-form :model="form" ref="formRef" label-width="100px">
        <div class="form-section">
          <div class="section-title"><el-icon><InfoFilled /></el-icon>退租基本信息</div>
          <el-row :gutter="16">
            <el-col :span="12"><el-form-item label="关联合同" required><el-select v-model="form.contract_id" filterable clearable placeholder="选择合同" style="width:100%" @change="onContractChange"><el-option v-for="c in contracts" :key="c.id" :label="c.contract_no+' (¥'+c.monthly_rent+'/月)'" :value="c.id"/></el-select></el-form-item></el-col>
            <el-col :span="12"><el-form-item label="所属小区"><el-select v-model="form.community_id" style="width:100%" clearable @change="onFormCommunityChange"><el-option v-for="c in communities" :key="c.id" :label="c.name" :value="c.id"/></el-select></el-form-item></el-col>
          </el-row>
          <el-row :gutter="16">
            <el-col :span="12"><el-form-item label="退租类型"><el-select v-model="form.termination_type" style="width:100%"><el-option label="到期退租" value="到期退租"/><el-option label="提前退租" value="提前退租"/><el-option label="违约退租" value="违约退租"/><el-option label="协商退租" value="协商退租"/></el-select></el-form-item></el-col>
          </el-row>
          <el-row :gutter="16">
            <el-col :span="12"><el-form-item label="申请时间"><el-input v-model="form.apply_time" placeholder="如：2025-06-01"/></el-form-item></el-col>
            <el-col :span="12"><el-form-item label="实际退租"><el-input v-model="form.actual_terminate_time" placeholder="实际退租日期"/></el-form-item></el-col>
          </el-row>
          <el-form-item label="退租原因"><el-input v-model="form.reason" type="textarea" :rows="2" placeholder="请说明退租原因"/></el-form-item>
        </div>
        <div class="form-section">
          <div class="section-title"><el-icon><Money /></el-icon>押金处理</div>
          <el-row :gutter="16">
            <el-col :span="12"><el-form-item label="押金退还(元)"><el-input v-model="form.deposit_refund" placeholder="0"/></el-form-item></el-col>
            <el-col :span="12"><el-form-item label="押金扣除(元)"><el-input v-model="form.deposit_deduct" placeholder="0"/></el-form-item></el-col>
          </el-row>
          <el-form-item label="扣除明细"><el-input v-model="form.deduct_detail" placeholder="说明押金扣除的具体项目和金额"/></el-form-item>
        </div>
        <div class="form-section">
          <div class="section-title"><el-icon><House /></el-icon>验房情况</div>
          <el-row :gutter="16">
            <el-col :span="12"><el-form-item label="验房结果"><el-input v-model="form.property_check_result" placeholder="如：完好/有损坏"/></el-form-item></el-col>
            <el-col :span="12"><el-form-item label="损坏情况"><el-input v-model="form.property_damage" placeholder="描述具体损坏位置和程度"/></el-form-item></el-col>
          </el-row>
          <el-row :gutter="16">
            <el-col :span="12"><el-form-item label="损坏赔偿(元)"><el-input v-model="form.damage_compensation" placeholder="0"/></el-form-item></el-col>
            <el-col :span="12"><el-form-item label="未结费用(元)"><el-input v-model="form.unpaid_bills" placeholder="0"/></el-form-item></el-col>
          </el-row>
        </div>
        <div class="form-section">
          <div class="section-title"><el-icon><ScaleToOriginal /></el-icon>抄表读数</div>
          <el-row :gutter="16">
            <el-col :span="8"><el-form-item label="水表读数"><el-input v-model="form.water_reading" placeholder="0"/></el-form-item></el-col>
            <el-col :span="8"><el-form-item label="电表读数"><el-input v-model="form.electric_reading" placeholder="0"/></el-form-item></el-col>
            <el-col :span="8"><el-form-item label="气表读数"><el-input v-model="form.gas_reading" placeholder="0"/></el-form-item></el-col>
          </el-row>
        </div>
        <div class="form-section" style="border:none">
          <div class="section-title"><el-icon><EditPen /></el-icon>结算与附件</div>
          <el-row :gutter="16">
            <el-col :span="12"><el-form-item label="结算金额(元)"><el-input v-model="form.settlement_amount" placeholder="最终结算金额"/></el-form-item></el-col>
            <el-col :span="12"><el-form-item label="结算时间"><el-input v-model="form.settlement_time" placeholder="如：2025-06-15"/></el-form-item></el-col>
          </el-row>
          <el-row :gutter="16">
            <el-col :span="8"><el-form-item label="操作员ID"><el-input-number v-model="form.operator_id" :min="0" controls-position="right" style="width:100%"/></el-form-item></el-col>
            <el-col :span="8"><el-form-item label="附件URL"><el-input v-model="form.files" placeholder="扣款凭证等"/></el-form-item></el-col>
            <el-col :span="8"><el-form-item label="备注"><el-input v-model="form.remark" placeholder="备注信息"/></el-form-item></el-col>
          </el-row>
        </div>
      </el-form>
      <template #footer><div class="dialog-footer"><el-button @click="dialogVisible=false">取消</el-button><el-button type="primary" @click="handleSubmit" :loading="submitting">{{editId?'保存修改':'确认登记'}}</el-button></div></template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { apiGet, apiPost } from '@/utils/request'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Close, Plus, List, CircleCheck, WarningFilled, Money, Search, Refresh, InfoFilled, House, ScaleToOriginal, EditPen, DocumentRemove } from '@element-plus/icons-vue'

const list = ref<any[]>([])
const total = ref(0)
const loading = ref(false)
const submitting = ref(false)
const dialogVisible = ref(false)
const editId = ref(0)
const formRef = ref()

const query = reactive({ page:1, limit:15, keyword:'', termination_type:'', community_id:undefined as any })
const communities = ref<any[]>([])
const contracts = ref<any[]>([])
const form = reactive<any>({
  community_id:0, contract_id:0, termination_type:'到期退租', apply_time:'', actual_terminate_time:'', reason:'',
  deposit_refund:'', deposit_deduct:'0', deduct_detail:'', property_check_result:'',
  property_damage:'', damage_compensation:'0', water_reading:'', electric_reading:'',
  gas_reading:'', unpaid_bills:'0', settlement_amount:'', settlement_time:'',
  operator_id:0, files:'', remark:''
})

const stats = computed(()=>{
  const data = list.value||[]
  return {
    total: total.value,
    settled: data.filter((d:any)=>d.settlement_amount>0).length,
    pending: data.filter((d:any)=>!d.settlement_amount||d.settlement_amount==='0').length,
    totalRefund: data.reduce((s:number,d:any)=>s+(Number(d.deposit_refund)||0),0)
  }
})

function fmtMoney(v:number){return v>=10000?(v/10000).toFixed(1)+'万':v.toLocaleString()}
function termTypeTag(s:string){
  const m:Record<string,string>={'到期退租':'success','提前退租':'warning','违约退租':'danger','协商退租':'info'}
  return m[s]||''
}

function handleSearch(){query.page=1;loadData()}
function resetQuery(){query.keyword='';query.termination_type='';query.community_id=undefined;query.page=1;loadData()}

async function loadData(){
  loading.value=true
  try{
    const p:any={page:query.page,limit:query.limit}
    if(query.keyword)p.keyword=query.keyword
    if(query.termination_type)p.termination_type=query.termination_type
    if(query.community_id)p.community_id=query.community_id
    const res:any=await apiGet('/admin/lease/leaseTerminationList',p)
    if(res&&(res.code===0||res.code===undefined)){list.value=res.data||[];total.value=res.count||0}
  }catch(_){list.value=[];total.value=0}
  finally{loading.value=false}
}

async function loadContracts(community_id?:any){
  try{
    const p:any={limit:999}
    if(community_id)p.community_id=community_id
    const r:any=await apiGet('/admin/lease/leaseContractList',p)
    contracts.value=r.data||[]
  }catch(_){contracts.value=[]}
}
function onFormCommunityChange(val:any){
  loadContracts(val)
}
function onContractChange(contractId:any){
  const c=contracts.value.find((x:any)=>x.id===contractId)
  if(c){
    form.community_id=c.community_id||0
    if(c.deposit_amount)form.deposit_refund=c.deposit_amount
  }
}

function openForm(row?:any){
  if(row){editId.value=row.id;Object.keys(form).forEach(k=>{if(row[k]!==undefined)(form as any)[k]=row[k]})}
  else{editId.value=0;Object.assign(form,{community_id:0,contract_id:'',termination_type:'到期退租',apply_time:'',actual_terminate_time:'',reason:'',deposit_refund:'',deposit_deduct:'0',deduct_detail:'',property_check_result:'',property_damage:'',damage_compensation:'0',water_reading:'',electric_reading:'',gas_reading:'',unpaid_bills:'0',settlement_amount:'',settlement_time:'',operator_id:0,files:'',remark:''})}
  loadContracts(form.community_id||undefined)
  dialogVisible.value=true
}

async function handleSubmit(){
  submitting.value=true
  try{
    const url=editId.value?'/admin/lease/leaseTerminationEdit':'/admin/lease/leaseTerminationAdd'
    const payload={...form,id:editId.value||undefined}
    const res:any=await apiPost(url,payload)
    if(res&&(res.code===0||res.code===undefined)){ElMessage.success(editId.value?'退租记录已更新':'退租已登记');dialogVisible.value=false;loadData()}
  }catch(_){}
  finally{submitting.value=false}
}

async function handleDelete(row:any){
  try{await ElMessageBox.confirm('确定删除该退租记录？','提示',{type:'warning'});const res:any=await apiPost('/admin/lease/leaseTerminationDelete',{id:row.id});if(res&&(res.code===0||res.code===undefined)){ElMessage.success('删除成功');loadData()}}catch(_){}
}

onMounted(async ()=>{
  try{const r:any=await apiGet('/admin/community/list',{limit:999});communities.value=r.data?.list||r.data||[]}catch(_){}
  loadData()
})
watch([()=>query.page,()=>query.limit],()=>loadData())
</script>

<style scoped>
.lease-termination-page{min-height:calc(100vh - 100px)}
.page-hero{background:linear-gradient(135deg,#1a365d 0%,#2563eb 50%,#3b82f6 100%);border-radius:16px;padding:28px 32px;margin-bottom:24px;position:relative;overflow:hidden}
.page-hero::after{content:'';position:absolute;right:-50px;top:-50px;width:180px;height:180px;border-radius:50%;background:rgba(255,255,255,.05)}
.hero-content{display:flex;justify-content:space-between;align-items:center;position:relative;z-index:1}
.hero-left{display:flex;align-items:center;gap:20px}
.hero-icon{width:56px;height:56px;border-radius:14px;background:rgba(255,255,255,.15);display:flex;align-items:center;justify-content:center;color:#fff;backdrop-filter:blur(10px)}
.hero-text h1{margin:0;color:#fff;font-size:22px;font-weight:700}
.hero-text p{margin:6px 0 0;color:rgba(255,255,255,.75);font-size:13px}
.btn-create{height:42px;padding:0 24px;font-weight:600;font-size:14px;border-radius:10px;background:#fff;color:#2563eb;border:none;box-shadow:0 4px 15px rgba(0,0,0,.15)}
.btn-create:hover{background:#f0f5ff;color:#1d4ed8;transform:translateY(-1px);box-shadow:0 6px 20px rgba(0,0,0,.2)}

.stats-row{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px}
.stat-card{background:#fff;border-radius:14px;padding:22px 24px;display:flex;align-items:center;gap:18px;border:1px solid #e8ecf1;transition:all .3s;cursor:default}
.stat-card:hover{transform:translateY(-2px);box-shadow:0 8px 25px rgba(0,0,0,.08);border-color:#c8d6e5}
.stat-icon{width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;color:#fff;flex-shrink:0}
.stat-num{font-size:26px;font-weight:800;color:#1a202c;line-height:1;display:block}
.stat-lbl{font-size:12px;color:#718096;margin-top:4px;display:block}

.filter-bar{background:#fff;border-radius:14px;padding:16px 20px;margin-bottom:20px;border:1px solid #e8ecf1;display:flex;justify-content:space-between;align-items:center;gap:16px}
.filter-left{display:flex;gap:12px;flex:1}.search-inp{width:280px}.search-inp :deep(.el-input__wrapper){border-radius:10px}.filter-sel{width:160px}.filter-sel :deep(.el-input__wrapper){border-radius:10px}

.table-card{border-radius:14px;border:1px solid #e8ecf1;overflow:hidden}.table-card :deep(.el-card__body){padding:0}
.modern-table :deep(.el-table__header th){background:#f8fafc;font-weight:600;color:#475569;font-size:13px}
.modern-table :deep(.el-table__row){transition:background .2s}
.modern-table :deep(.el-table__row:hover){background:#f8faff}
.reason-text{color:#475569;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.refund-amount{color:#16a34a;font-weight:600}.deduct-amount{color:#dc2626;font-weight:600}.settle-amount{color:#2563eb;font-weight:700}

.empty-state-inline{display:flex;flex-direction:column;align-items:center;justify-content:center;padding:60px 20px;color:#cbd5e1}.empty-state-inline p{color:#94a3b8;font-size:14px;margin-top:12px}
.pagination-inner{display:flex;justify-content:flex-end;padding:16px}

.termination-dialog :deep(.el-dialog__header){border-bottom:1px solid #f0f2f5;padding:20px 24px}
.termination-dialog :deep(.el-dialog__body){padding:24px}
.form-section{margin-bottom:24px;padding-bottom:20px;border-bottom:1px solid #f0f2f5}
.form-section:last-child{border-bottom:none;margin-bottom:0;padding-bottom:0}
.section-title{font-size:14px;font-weight:700;color:#1a365d;margin-bottom:16px;display:flex;align-items:center;gap:8px}
.section-title .el-icon{color:#2563eb}
.dialog-footer{display:flex;justify-content:flex-end;gap:12px}

@media(max-width:1200px){.stats-row{grid-template-columns:repeat(2,1fr)}}
@media(max-width:768px){.filter-bar{flex-direction:column}.filter-left{flex-direction:column;width:100%}.search-inp,.filter-sel{width:100%!important}}
</style>
