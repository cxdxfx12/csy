<template>
  <div class="lease-tenant-page">
    <!-- 头部 -->
    <div class="page-hero">
      <div class="hero-content">
        <div class="hero-left">
          <div class="hero-icon"><el-icon :size="28"><UserFilled /></el-icon></div>
          <div class="hero-text">
            <h1>租客信息管理</h1>
            <p>统一管理所有租客档案资料，关联合同、支付与退租全流程</p>
          </div>
        </div>
        <div class="hero-actions">
          <el-button type="primary" size="large" @click="openForm()" class="btn-create">
            <el-icon><Plus /></el-icon><span>添加租客</span>
          </el-button>
        </div>
      </div>
    </div>

    <!-- 统计卡片 -->
    <div class="stats-row">
      <div class="stat-card">
        <div class="stat-icon" style="background:linear-gradient(135deg,#f59e0b,#fbbf24)"><el-icon :size="22"><User /></el-icon></div>
        <div class="stat-info"><span class="stat-num">{{ stats.total }}</span><span class="stat-lbl">租客总数</span></div>
      </div>
      <div class="stat-card">
        <div class="stat-icon" style="background:linear-gradient(135deg,#43e97b,#38f9d7)"><el-icon :size="22"><CircleCheck /></el-icon></div>
        <div class="stat-info"><span class="stat-num">{{ stats.active }}</span><span class="stat-lbl">在租</span></div>
      </div>
      <div class="stat-card">
        <div class="stat-icon" style="background:linear-gradient(135deg,#f093fb,#f5576c)"><el-icon :size="22"><Close /></el-icon></div>
        <div class="stat-info"><span class="stat-num">{{ stats.inactive }}</span><span class="stat-lbl">已退租</span></div>
      </div>
      <div class="stat-card">
        <div class="stat-icon" style="background:linear-gradient(135deg,#667eea,#764ba2)"><el-icon :size="22"><List /></el-icon></div>
        <div class="stat-info"><span class="stat-num">{{ stats.company }}</span><span class="stat-lbl">企业租客</span></div>
      </div>
    </div>

    <!-- 筛选栏 -->
    <div class="filter-bar">
      <div class="filter-left">
        <el-input v-model="query.keyword" placeholder="搜索姓名、手机号或身份证..." clearable class="search-inp" @keyup.enter="handleSearch" @clear="handleSearch"><template #prefix><el-icon><Search /></el-icon></template></el-input>
        <el-select v-model="query.gender" placeholder="性别" clearable class="filter-sel" @change="handleSearch">
          <el-option label="男" value="男"/><el-option label="女" value="女"/>
        </el-select>
        <el-select v-model="query.status" placeholder="租赁状态" clearable class="filter-sel" @change="handleSearch">
          <el-option label="在租" value="在租|1"/><el-option label="已退租" value="已退租|0"/>
        </el-select>
        <el-select v-model="query.community_id" placeholder="所属小区" clearable class="filter-sel" @change="handleSearch">
          <el-option v-for="c in communities" :key="c.id" :label="c.name" :value="c.id"/>
        </el-select>
      </div>
      <div class="filter-right">
        <el-button @click="resetQuery" text><el-icon><Refresh /></el-icon>重置</el-button>
      </div>
    </div>

    <!-- 租客卡片 -->
    <div class="tenant-grid" v-loading="loading">
      <div v-if="list.length===0&&!loading" class="empty-state">
        <el-icon :size="64"><FolderOpened /></el-icon>
        <h3>暂无租客信息</h3><p>点击「添加租客」录入第一位租客</p>
        <el-button type="primary" @click="openForm()">立即添加</el-button>
      </div>
      <div v-for="item in list" :key="item.id" class="tenant-card" @click="openForm(item)">
        <div class="tc-header">
          <div class="tc-avatar" :class="item.gender==='女'?'female':''">
            <el-icon :size="24"><UserFilled /></el-icon>
          </div>
          <div class="tc-basic">
            <h3>{{ item.name||'未命名' }}</h3>
            <div class="tc-contact">
              <span><el-icon :size="12"><Phone /></el-icon>{{ item.mobile||'未填写' }}</span>
              <span v-if="item.email"><el-icon :size="12"><Message /></el-icon>{{ item.email }}</span>
            </div>
          </div>
          <div class="tc-status">
            <el-tag :type="item.status==='在租'||item.status==='1'?'success':'info'" size="small" effect="dark" round>
              {{ item.status==='在租'||item.status==='1'?'在租':'已退租' }}
            </el-tag>
          </div>
        </div>
        <div class="tc-body">
          <div class="tc-row">
            <span class="tcl">性别</span><span class="tcv">{{ item.gender||'-' }}</span>
            <span class="tcl">身份证</span><span class="tcv">{{ maskIdCard(item.id_card) }}</span>
          </div>
          <div class="tc-row" v-if="item.company_name">
            <span class="tcl">工作单位</span><span class="tcv">{{ item.company_name }}</span>
          </div>
          <div class="tc-row">
            <span class="tcl">紧急联系人</span><span class="tcv">{{ item.emergency_contact||'-' }}</span>
            <span class="tcl">紧急电话</span><span class="tcv">{{ item.emergency_phone||'-' }}</span>
          </div>
          <div class="tc-row" v-if="item.wechat">
            <span class="tcl">微信</span><span class="tcv">{{ item.wechat }}</span>
          </div>
        </div>
        <div class="tc-actions" @click.stop>
          <el-button size="small" plain @click="openForm(item)"><el-icon><Edit /></el-icon>编辑</el-button>
          <el-popconfirm title="确定删除该租客？" @confirm="handleDelete(item)"><template #reference><el-button size="small" plain type="danger"><el-icon><Delete /></el-icon></el-button></template></el-popconfirm>
        </div>
      </div>
    </div>

    <!-- 分页 -->
    <div class="pagination-wrap" v-if="total>query.limit">
      <el-pagination v-model:current-page="query.page" v-model:page-size="query.limit" :total="total" :page-sizes="[12,24,48,96]" layout="total, sizes, prev, pager, next, jumper" background/>
    </div>

    <!-- 编辑弹窗 -->
    <el-dialog v-model="dialogVisible" :title="editId?'编辑租客信息':'添加租客'" width="700px" destroy-on-close :close-on-click-modal="false" class="tenant-dialog">
      <el-form :model="form" ref="formRef" label-width="100px">
        <div class="form-section">
          <div class="section-title"><el-icon><User /></el-icon>基本信息</div>
          <el-row :gutter="16">
            <el-col :span="12"><el-form-item label="姓名" required><el-input v-model="form.name" placeholder="请输入姓名"/></el-form-item></el-col>
            <el-col :span="12"><el-form-item label="性别"><el-radio-group v-model="form.gender"><el-radio value="男">男</el-radio><el-radio value="女">女</el-radio></el-radio-group></el-form-item></el-col>
          </el-row>
          <el-row :gutter="16">
            <el-col :span="12"><el-form-item label="身份证号"><el-input v-model="form.id_card" placeholder="18位身份证号"/></el-form-item></el-col>
            <el-col :span="12"><el-form-item label="手机号" required><el-input v-model="form.mobile" placeholder="请输入手机号"/></el-form-item></el-col>
          </el-row>
          <el-row :gutter="16">
            <el-col :span="12"><el-form-item label="微信"><el-input v-model="form.wechat" placeholder="微信号"/></el-form-item></el-col>
            <el-col :span="12"><el-form-item label="邮箱"><el-input v-model="form.email" placeholder="电子邮箱"/></el-form-item></el-col>
          </el-row>
        </div>
        <div class="form-section">
          <div class="section-title"><el-icon><OfficeBuilding /></el-icon>工作信息</div>
          <el-row :gutter="16">
            <el-col :span="12"><el-form-item label="公司名称"><el-input v-model="form.company_name" placeholder="工作单位"/></el-form-item></el-col>
            <el-col :span="12"><el-form-item label="公司地址"><el-input v-model="form.company_address" placeholder="公司地址"/></el-form-item></el-col>
          </el-row>
          <el-row :gutter="16">
            <el-col :span="12"><el-form-item label="联系人"><el-input v-model="form.contact_person" placeholder="公司联系人"/></el-form-item></el-col>
            <el-col :span="12"><el-form-item label="联系电话"><el-input v-model="form.contact_phone" placeholder="联系人电话"/></el-form-item></el-col>
          </el-row>
        </div>
        <div class="form-section">
          <div class="section-title"><el-icon><Warning /></el-icon>紧急联系</div>
          <el-row :gutter="16">
            <el-col :span="12"><el-form-item label="紧急联系人"><el-input v-model="form.emergency_contact" placeholder="紧急联系人姓名"/></el-form-item></el-col>
            <el-col :span="12"><el-form-item label="紧急电话"><el-input v-model="form.emergency_phone" placeholder="紧急联系电话"/></el-form-item></el-col>
          </el-row>
        </div>
        <div class="form-section" style="border:none">
          <el-row :gutter="16">
            <el-col :span="12"><el-form-item label="租赁状态"><el-select v-model="form.status" style="width:100%"><el-option label="在租" value="在租"/><el-option label="已退租" value="已退租"/></el-select></el-form-item></el-col>
            <el-col :span="12"><el-form-item label="所属小区"><el-select v-model="form.community_id" style="width:100%" clearable><el-option v-for="c in communities" :key="c.id" :label="c.name" :value="c.id"/></el-select></el-form-item></el-col>
          </el-row>
          <el-form-item label="备注"><el-input v-model="form.remark" placeholder="备注信息"/></el-form-item>
        </div>
      </el-form>
      <template #footer><div class="dialog-footer"><el-button @click="dialogVisible=false">取消</el-button><el-button type="primary" @click="handleSubmit" :loading="submitting">{{editId?'保存修改':'确认添加'}}</el-button></div></template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { apiGet, apiPost } from '@/utils/request'
import { ElMessage, ElMessageBox } from 'element-plus'
import { UserFilled, Plus, User, CircleCheck, Close, List, Search, Refresh, Phone, Message, Edit, Delete, OfficeBuilding, Warning, FolderOpened } from '@element-plus/icons-vue'

const list = ref<any[]>([])
const total = ref(0)
const loading = ref(false)
const submitting = ref(false)
const dialogVisible = ref(false)
const editId = ref(0)
const formRef = ref()

const query = reactive({ page:1, limit:12, keyword:'', gender:'', status:'', community_id:undefined as any })
const communities = ref<any[]>([])
const form = reactive<any>({
  community_id:0, name:'', gender:'男', id_card:'', mobile:'', wechat:'', email:'',
  company_name:'', company_address:'', contact_person:'', contact_phone:'',
  emergency_contact:'', emergency_phone:'', remark:'', status:'在租'
})

const stats = computed(()=>{
  const data = list.value||[]
  return {
    total: total.value,
    active: data.filter((d:any)=>d.status==='在租'||d.status==='1').length,
    inactive: data.filter((d:any)=>d.status==='已退租'||d.status==='0').length,
    company: data.filter((d:any)=>d.company_name&&d.company_name.trim()).length
  }
})

function maskIdCard(id:string){if(!id||id.length<8)return id||'-';return id.slice(0,3)+'****'+id.slice(-4)}
function handleSearch(){query.page=1;loadData()}
function resetQuery(){query.keyword='';query.gender='';query.status='';query.community_id=undefined;query.page=1;loadData()}

async function loadData(){
  loading.value=true
  try{
    const p:any={page:query.page,limit:query.limit}
    if(query.keyword)p.keyword=query.keyword
    if(query.gender)p.gender=query.gender
    if(query.status)p.status=query.status
    if(query.community_id)p.community_id=query.community_id
    const res:any=await apiGet('/admin/lease/leaseTenantList',p)
    if(res&&(res.code===0||res.code===undefined)){list.value=res.data||[];total.value=res.count||0}
  }catch(_){list.value=[];total.value=0}
  finally{loading.value=false}
}

function openForm(row?:any){
  if(row){editId.value=row.id;Object.keys(form).forEach(k=>{if(row[k]!==undefined)(form as any)[k]=row[k]})}
  else{editId.value=0;Object.assign(form,{community_id:0,name:'',gender:'男',id_card:'',mobile:'',wechat:'',email:'',company_name:'',company_address:'',contact_person:'',contact_phone:'',emergency_contact:'',emergency_phone:'',remark:'',status:'在租'})}
  dialogVisible.value=true
}

async function handleSubmit(){
  submitting.value=true
  try{
    const url=editId.value?'/admin/lease/leaseTenantEdit':'/admin/lease/leaseTenantAdd'
    const payload={...form,id:editId.value||undefined}
    const res:any=await apiPost(url,payload)
    if(res&&(res.code===0||res.code===undefined)){ElMessage.success(editId.value?'租客信息已更新':'租客已添加');dialogVisible.value=false;loadData()}
  }catch(_){}
  finally{submitting.value=false}
}

async function handleDelete(row:any){
  try{await ElMessageBox.confirm('确定删除该租客？','提示',{type:'warning'});const res:any=await apiPost('/admin/lease/leaseTenantDelete',{id:row.id});if(res&&(res.code===0||res.code===undefined)){ElMessage.success('删除成功');loadData()}}catch(_){}
}

onMounted(async ()=>{
  try{const r:any=await apiGet('/admin/community/list',{limit:999});communities.value=r.data?.list||r.data||[]}catch(_){}
  loadData()
})
watch([()=>query.page,()=>query.limit],()=>loadData())
</script>

<style scoped>
.lease-tenant-page{min-height:calc(100vh - 100px)}
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
.filter-left{display:flex;gap:12px;flex:1}.search-inp{width:280px}.search-inp :deep(.el-input__wrapper){border-radius:10px}.filter-sel{width:140px}.filter-sel :deep(.el-input__wrapper){border-radius:10px}

.tenant-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:20px;min-height:200px}
.tenant-card{background:#fff;border-radius:16px;border:1px solid #e8ecf1;overflow:hidden;cursor:pointer;transition:all .3s cubic-bezier(.4,0,.2,1)}
.tenant-card:hover{transform:translateY(-4px);box-shadow:0 16px 40px rgba(0,0,0,.1);border-color:#93c5fd}
.tc-header{display:flex;align-items:center;gap:14px;padding:18px 18px 14px;background:linear-gradient(135deg,#f8fafc,#eff6ff);border-bottom:1px solid #e8ecf1}
.tc-avatar{width:48px;height:48px;border-radius:12px;background:linear-gradient(135deg,#3b82f6,#2563eb);display:flex;align-items:center;justify-content:center;color:#fff;flex-shrink:0}
.tc-avatar.female{background:linear-gradient(135deg,#ec4899,#db2777)}
.tc-basic{flex:1;min-width:0}.tc-basic h3{margin:0 0 4px;font-size:15px;font-weight:600;color:#1a202c}
.tc-contact{display:flex;flex-wrap:wrap;gap:12px;font-size:12px;color:#64748b}.tc-contact span{display:flex;align-items:center;gap:3px}
.tc-status{flex-shrink:0}
.tc-body{padding:14px 18px}.tc-row{display:flex;gap:0;margin-bottom:8px;font-size:12px;flex-wrap:wrap}
.tc-row:last-child{margin-bottom:0}.tcl{color:#94a3b8;width:75px;flex-shrink:0}.tcv{color:#475569;font-weight:500;flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.tc-actions{display:flex;gap:8px;padding:12px 18px;border-top:1px solid #f0f2f5}

.empty-state{grid-column:1/-1;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:80px 20px;background:#fff;border-radius:16px;border:2px dashed #e2e8f0;color:#cbd5e1}
.empty-state h3{color:#475569;margin:12px 0 8px;font-size:18px}.empty-state p{color:#94a3b8;margin:0 0 20px}

.pagination-wrap{display:flex;justify-content:center;margin-top:24px}

.tenant-dialog :deep(.el-dialog__header){border-bottom:1px solid #f0f2f5;padding:20px 24px}
.tenant-dialog :deep(.el-dialog__body){padding:24px}
.form-section{margin-bottom:24px;padding-bottom:20px;border-bottom:1px solid #f0f2f5}
.form-section:last-child{border-bottom:none;margin-bottom:0;padding-bottom:0}
.section-title{font-size:14px;font-weight:700;color:#1a365d;margin-bottom:16px;display:flex;align-items:center;gap:8px}
.section-title .el-icon{color:#2563eb}
.dialog-footer{display:flex;justify-content:flex-end;gap:12px}

@media(max-width:1400px){.tenant-grid{grid-template-columns:repeat(2,1fr)}.stats-row{grid-template-columns:repeat(2,1fr)}}
@media(max-width:900px){.tenant-grid{grid-template-columns:1fr}.stats-row{grid-template-columns:1fr 1fr}.filter-bar{flex-direction:column}.filter-left{flex-wrap:wrap}.search-inp,.filter-sel{width:100%!important}}
</style>
