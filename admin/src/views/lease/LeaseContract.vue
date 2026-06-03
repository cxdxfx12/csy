<template>
  <div class="lease-contract-page">
    <!-- 头部 -->
    <div class="page-hero">
      <div class="hero-content">
        <div class="hero-left">
          <div class="hero-icon"><el-icon :size="28"><Document /></el-icon></div>
          <div class="hero-text">
            <h1>租赁合同管理</h1>
            <p>全生命周期合同管理，从签约到续约到退租一键追踪</p>
          </div>
        </div>
        <div class="hero-actions">
          <el-button type="primary" size="large" @click="openForm()" class="btn-create">
            <el-icon><Plus /></el-icon><span>新建合同</span>
          </el-button>
        </div>
      </div>
    </div>

    <!-- 统计卡片 -->
    <div class="stats-row">
      <div class="stat-card">
        <div class="stat-icon" style="background:linear-gradient(135deg,#667eea,#764ba2)"><el-icon :size="22"><Document /></el-icon></div>
        <div class="stat-info"><span class="stat-num">{{ stats.total }}</span><span class="stat-lbl">合同总数</span></div>
      </div>
      <div class="stat-card">
        <div class="stat-icon" style="background:linear-gradient(135deg,#43e97b,#38f9d7)"><el-icon :size="22"><CircleCheck /></el-icon></div>
        <div class="stat-info"><span class="stat-num">{{ stats.active }}</span><span class="stat-lbl">履行中</span></div>
      </div>
      <div class="stat-card">
        <div class="stat-icon" style="background:linear-gradient(135deg,#f59e0b,#fbbf24)"><el-icon :size="22"><WarningFilled /></el-icon></div>
        <div class="stat-info"><span class="stat-num">{{ stats.expiring }}</span><span class="stat-lbl">即将到期</span></div>
      </div>
      <div class="stat-card">
        <div class="stat-icon" style="background:linear-gradient(135deg,#f093fb,#f5576c)"><el-icon :size="22"><Close /></el-icon></div>
        <div class="stat-info"><span class="stat-num">{{ stats.terminated }}</span><span class="stat-lbl">已终止</span></div>
      </div>
    </div>

    <!-- 筛选栏 -->
    <div class="filter-bar">
      <div class="filter-left">
        <el-input v-model="query.keyword" placeholder="搜索合同编号或备注..." clearable class="search-inp" @keyup.enter="handleSearch" @clear="handleSearch"><template #prefix><el-icon><Search /></el-icon></template></el-input>
        <el-select v-model="query.status" placeholder="合同状态" clearable class="filter-sel" @change="handleSearch">
          <el-option label="履行中" value="履行中"/><el-option label="即将到期" value="即将到期"/><el-option label="已到期" value="已到期"/><el-option label="已终止" value="已终止"/>
        </el-select>
        <el-select v-model="query.is_renewal" placeholder="是否续约" clearable class="filter-sel" @change="handleSearch">
          <el-option label="新签" :value="0"/><el-option label="续约" :value="1"/>
        </el-select>
      </div>
      <div class="filter-right">
        <el-button @click="resetQuery" text><el-icon><Refresh /></el-icon>重置</el-button>
      </div>
    </div>

    <!-- 合同表格 -->
    <el-card shadow="never" class="table-card">
      <el-table :data="list" v-loading="loading" stripe class="modern-table">
        <el-table-column type="index" label="#" width="55"/>
        <el-table-column prop="contract_no" label="合同编号" width="160"><template #default="{row}"><span class="contract-no">{{ row.contract_no||'-' }}</span></template></el-table-column>
        <el-table-column prop="property_id" label="房源ID" width="85" align="center"/>
        <el-table-column prop="tenant_id" label="租客ID" width="85" align="center"/>
        <el-table-column prop="tenant_type" label="租客类型" width="100" align="center"><template #default="{row}"><el-tag size="small" effect="plain">{{ row.tenant_type==='1'||row.tenant_type===1?'企业':'个人' }}</el-tag></template></el-table-column>
        <el-table-column prop="monthly_rent" label="月租(元)" width="110" align="right"><template #default="{row}"><span class="money">{{ row.monthly_rent||0 }}</span></template></el-table-column>
        <el-table-column prop="deposit_amount" label="押金(元)" width="110" align="right"><template #default="{row}"><span class="money">{{ row.deposit_amount||0 }}</span></template></el-table-column>
        <el-table-column prop="lease_start" label="租期开始" width="115"/>
        <el-table-column prop="lease_end" label="租期结束" width="115"/>
        <el-table-column prop="lease_months" label="租期(月)" width="85" align="center">
          <template #default="{row}"><el-tag size="small" round effect="plain" type="info">{{ row.lease_months||0 }}个月</el-tag></template>
        </el-table-column>
        <el-table-column prop="rent_cycle" label="缴费周期" width="95" align="center"><template #default="{row}">{{ cycleLabel(row.rent_cycle) }}</template></el-table-column>
        <el-table-column prop="status" label="状态" width="100" align="center"><template #default="{row}">
          <span class="status-dot" :class="contractDot(row.status)"></span>{{ row.status||'未知' }}
        </template></el-table-column>
        <el-table-column prop="sign_time" label="签约时间" width="115"/>
        <el-table-column label="操作" width="140" fixed="right"><template #default="{row}">
          <el-button size="small" plain type="primary" @click="openForm(row)">编辑</el-button>
          <el-popconfirm title="确定删除？" @confirm="handleDelete(row)"><template #reference><el-button size="small" plain type="danger">删除</el-button></template></el-popconfirm>
        </template></el-table-column>
      </el-table>
      <div v-if="list.length===0&&!loading" class="empty-state-inline">
        <el-icon :size="48"><DocumentRemove /></el-icon><p>暂无合同记录</p>
      </div>
      <div class="pagination-inner" v-if="total>query.limit">
        <el-pagination v-model:current-page="query.page" v-model:page-size="query.limit" :total="total" :page-sizes="[15,30,50,100]" layout="total, sizes, prev, pager, next, jumper" background/>
      </div>
    </el-card>

    <!-- 编辑弹窗 -->
    <el-dialog v-model="dialogVisible" :title="editId?'编辑租赁合同':'新建租赁合同'" width="780px" destroy-on-close :close-on-click-modal="false" class="contract-dialog">
      <el-form :model="form" ref="formRef" label-width="100px">
        <div class="form-section">
          <div class="section-title"><el-icon><InfoFilled /></el-icon>合同基本信息</div>
          <el-row :gutter="16">
            <el-col :span="12"><el-form-item label="合同编号" required><el-input v-model="form.contract_no" placeholder="自动生成或手动输入"/></el-form-item></el-col>
            <el-col :span="12"><el-form-item label="租客类型"><el-select v-model="form.tenant_type" style="width:100%"><el-option label="个人" value="0"/><el-option label="企业" value="1"/></el-select></el-form-item></el-col>
          </el-row>
          <el-row :gutter="16">
            <el-col :span="8"><el-form-item label="房源ID"><el-input-number v-model="form.property_id" :min="0" controls-position="right" style="width:100%"/></el-form-item></el-col>
            <el-col :span="8"><el-form-item label="租客ID"><el-input-number v-model="form.tenant_id" :min="0" controls-position="right" style="width:100%"/></el-form-item></el-col>
            <el-col :span="8"><el-form-item label="所属小区"><el-input-number v-model="form.community_id" :min="0" controls-position="right" style="width:100%"/></el-form-item></el-col>
          </el-row>
          <el-row :gutter="16">
            <el-col :span="12"><el-form-item label="合同状态"><el-select v-model="form.status" style="width:100%"><el-option label="履行中" value="履行中"/><el-option label="即将到期" value="即将到期"/><el-option label="已到期" value="已到期"/><el-option label="已终止" value="已终止"/></el-select></el-form-item></el-col>
            <el-col :span="12"><el-form-item label="签约人"><el-input v-model="form.signer" placeholder="签约经办人"/></el-form-item></el-col>
          </el-row>
        </div>
        <div class="form-section">
          <div class="section-title"><el-icon><Money /></el-icon>租金与费用</div>
          <el-row :gutter="16">
            <el-col :span="8"><el-form-item label="月租(元)"><el-input v-model="form.monthly_rent" placeholder="0"/></el-form-item></el-col>
            <el-col :span="8"><el-form-item label="押金(元)"><el-input v-model="form.deposit_amount" placeholder="0"/></el-form-item></el-col>
            <el-col :span="8"><el-form-item label="押金月数"><el-input-number v-model="form.deposit_months" :min="0" :max="12" controls-position="right" style="width:100%"/></el-form-item></el-col>
          </el-row>
          <el-row :gutter="16">
            <el-col :span="8"><el-form-item label="物业费(元)"><el-input v-model="form.property_tax" placeholder="0"/></el-form-item></el-col>
            <el-col :span="8"><el-form-item label="缴费日"><el-input-number v-model="form.rent_day" :min="1" :max="28" controls-position="right" style="width:100%"/></el-form-item></el-col>
            <el-col :span="8"><el-form-item label="缴费周期"><el-select v-model="form.rent_cycle" style="width:100%"><el-option label="月付" value="1"/><el-option label="季付" value="3"/><el-option label="半年付" value="6"/><el-option label="年付" value="12"/></el-select></el-form-item></el-col>
          </el-row>
          <el-row :gutter="16">
            <el-col :span="12"><el-form-item label="物业费承担"><el-select v-model="form.property_tax_bearer" style="width:100%"><el-option label="租客承担" :value="1"/><el-option label="房东承担" :value="0"/></el-select></el-form-item></el-col>
          </el-row>
        </div>
        <div class="form-section">
          <div class="section-title"><el-icon><Calendar /></el-icon>租期与签约</div>
          <el-row :gutter="16">
            <el-col :span="8"><el-form-item label="租期开始"><el-input v-model="form.lease_start" placeholder="如：2025-01-01"/></el-form-item></el-col>
            <el-col :span="8"><el-form-item label="租期结束"><el-input v-model="form.lease_end" placeholder="如：2026-01-01"/></el-form-item></el-col>
            <el-col :span="8"><el-form-item label="租期月数"><el-input-number v-model="form.lease_months" :min="1" :max="240" controls-position="right" style="width:100%"/></el-form-item></el-col>
          </el-row>
          <el-row :gutter="16">
            <el-col :span="8"><el-form-item label="签约时间"><el-input v-model="form.sign_time" placeholder="如：2025-01-01"/></el-form-item></el-col>
            <el-col :span="8"><el-form-item label="是否续约"><el-switch v-model="form.is_renewal" :active-value="1" :inactive-value="0" active-text="是" inactive-text="否"/></el-form-item></el-col>
            <el-col :span="8"><el-form-item label="上份合同ID"><el-input-number v-model="form.prev_contract_id" :min="0" controls-position="right" style="width:100%"/></el-form-item></el-col>
          </el-row>
        </div>
        <div class="form-section" style="border:none">
          <div class="section-title"><el-icon><EditPen /></el-icon>退租与附件</div>
          <el-row :gutter="16">
            <el-col :span="12"><el-form-item label="退租时间"><el-input v-model="form.terminate_time" placeholder="实际退租日期"/></el-form-item></el-col>
            <el-col :span="12"><el-form-item label="退租原因"><el-input v-model="form.terminate_reason" placeholder="退租原因说明"/></el-form-item></el-col>
          </el-row>
          <el-row :gutter="16">
            <el-col :span="12"><el-form-item label="附件URL"><el-input v-model="form.files" placeholder="合同文件URL"/></el-form-item></el-col>
            <el-col :span="12"><el-form-item label="备注"><el-input v-model="form.remark" placeholder="备注信息"/></el-form-item></el-col>
          </el-row>
        </div>
      </el-form>
      <template #footer><div class="dialog-footer"><el-button @click="dialogVisible=false">取消</el-button><el-button type="primary" @click="handleSubmit" :loading="submitting">{{editId?'保存修改':'创建合同'}}</el-button></div></template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { apiGet, apiPost } from '@/utils/request'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Document, Plus, CircleCheck, WarningFilled, Close, Search, Refresh, Money, Calendar, InfoFilled, EditPen, DocumentRemove } from '@element-plus/icons-vue'

const list = ref<any[]>([])
const total = ref(0)
const loading = ref(false)
const submitting = ref(false)
const dialogVisible = ref(false)
const editId = ref(0)
const formRef = ref()

const query = reactive({ page:1, limit:15, keyword:'', status:'', is_renewal:undefined as any })
const form = reactive<any>({
  community_id:0, contract_no:'', property_id:0, tenant_id:0, tenant_type:'0',
  monthly_rent:'', deposit_amount:'', deposit_months:1, lease_start:'', lease_end:'',
  lease_months:12, rent_day:1, rent_cycle:'1', property_tax:'', property_tax_bearer:1,
  is_renewal:0, prev_contract_id:0, status:'履行中', sign_time:'', signer:'',
  terminate_time:'', terminate_reason:'', files:'', remark:''
})

const stats = computed(()=>{
  const data = list.value||[]
  const today = new Date().toISOString().slice(0,10)
  return {
    total: total.value,
    active: data.filter((d:any)=>d.status==='履行中').length,
    expiring: data.filter((d:any)=>d.status==='履行中'&&d.lease_end&&d.lease_end<=today&&d.lease_end>=today).length,
    terminated: data.filter((d:any)=>d.status==='已终止'||d.status==='已到期').length
  }
})

function cycleLabel(v:any){const m:Record<string,string>={'1':'月付','3':'季付','6':'半年付','12':'年付'};return m[v]||v||'-'}
function contractDot(s:string){const m:Record<string,string>={'履行中':'dot-active','即将到期':'dot-expiring','已到期':'dot-ended','已终止':'dot-terminated'};return m[s]||''}

function handleSearch(){query.page=1;loadData()}
function resetQuery(){query.keyword='';query.status='';query.is_renewal=undefined;query.page=1;loadData()}

async function loadData(){
  loading.value=true
  try{
    const p:any={page:query.page,limit:query.limit}
    if(query.keyword)p.keyword=query.keyword
    if(query.status)p.status=query.status
    if(query.is_renewal!==undefined&&query.is_renewal!=='')p.is_renewal=query.is_renewal
    const res:any=await apiGet('/admin/lease/leaseContractList',p)
    if(res&&(res.code===0||res.code===undefined)){list.value=res.data?.list||[];total.value=res.data?.total||0}
  }catch(_){list.value=[];total.value=0}
  finally{loading.value=false}
}

function openForm(row?:any){
  if(row){editId.value=row.id;Object.keys(form).forEach(k=>{if(row[k]!==undefined)(form as any)[k]=row[k]})}
  else{editId.value=0;Object.assign(form,{community_id:0,contract_no:'',property_id:0,tenant_id:0,tenant_type:'0',monthly_rent:'',deposit_amount:'',deposit_months:1,lease_start:'',lease_end:'',lease_months:12,rent_day:1,rent_cycle:'1',property_tax:'',property_tax_bearer:1,is_renewal:0,prev_contract_id:0,status:'履行中',sign_time:'',signer:'',terminate_time:'',terminate_reason:'',files:'',remark:''})}
  dialogVisible.value=true
}

async function handleSubmit(){
  submitting.value=true
  try{
    const url=editId.value?'/admin/lease/leaseContractEdit':'/admin/lease/leaseContractAdd'
    const payload={...form,id:editId.value||undefined}
    const res:any=await apiPost(url,payload)
    if(res&&(res.code===0||res.code===undefined)){ElMessage.success(editId.value?'合同已更新':'合同已创建');dialogVisible.value=false;loadData()}
  }catch(_){}
  finally{submitting.value=false}
}

async function handleDelete(row:any){
  try{await ElMessageBox.confirm('确定删除该合同？','提示',{type:'warning'});const res:any=await apiPost('/admin/lease/leaseContractDelete',{id:row.id});if(res&&(res.code===0||res.code===undefined)){ElMessage.success('删除成功');loadData()}}catch(_){}
}

onMounted(()=>loadData())
watch([()=>query.page,()=>query.limit],()=>loadData())
</script>

<style scoped>
.lease-contract-page{min-height:calc(100vh - 100px)}
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
.filter-left{display:flex;gap:12px;flex:1}.search-inp{width:240px}.search-inp :deep(.el-input__wrapper){border-radius:10px}.filter-sel{width:140px}.filter-sel :deep(.el-input__wrapper){border-radius:10px}

.table-card{border-radius:14px;border:1px solid #e8ecf1;overflow:hidden}.table-card :deep(.el-card__body){padding:0}
.modern-table :deep(.el-table__header th){background:#f8fafc;font-weight:600;color:#475569;font-size:13px}
.contract-no{font-weight:600;color:#2563eb;font-family:monospace;font-size:13px}
.money{color:#dc2626;font-weight:600}
.status-dot{display:inline-block;width:7px;height:7px;border-radius:50%;margin-right:6px}
.dot-active{background:#22c55e}.dot-expiring{background:#f59e0b}.dot-ended{background:#ef4444}.dot-terminated{background:#94a3b8}

.empty-state-inline{display:flex;flex-direction:column;align-items:center;justify-content:center;padding:60px 20px;color:#cbd5e1}.empty-state-inline p{color:#94a3b8;font-size:14px;margin-top:12px}
.pagination-inner{display:flex;justify-content:flex-end;padding:16px}

.contract-dialog :deep(.el-dialog__header){border-bottom:1px solid #f0f2f5;padding:20px 24px}
.contract-dialog :deep(.el-dialog__body){padding:24px}
.form-section{margin-bottom:24px;padding-bottom:20px;border-bottom:1px solid #f0f2f5}
.form-section:last-child{border-bottom:none;margin-bottom:0;padding-bottom:0}
.section-title{font-size:14px;font-weight:700;color:#1a365d;margin-bottom:16px;display:flex;align-items:center;gap:8px}
.section-title .el-icon{color:#2563eb}
.dialog-footer{display:flex;justify-content:flex-end;gap:12px}

@media(max-width:1200px){.stats-row{grid-template-columns:repeat(2,1fr)}}
@media(max-width:768px){.filter-bar{flex-direction:column}.filter-left{flex-direction:column;width:100%}.search-inp,.filter-sel{width:100%!important}}
</style>
