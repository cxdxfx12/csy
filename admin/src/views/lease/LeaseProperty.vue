<template>
  <div class="lease-property-page">
    <!-- 头部 -->
    <div class="page-hero">
      <div class="hero-content">
        <div class="hero-left">
          <div class="hero-icon"><el-icon :size="28"><House /></el-icon></div>
          <div class="hero-text">
            <h1>可租赁房源</h1>
            <p>管理所有可出租物业资产，实时监控房源状态与定价策略</p>
          </div>
        </div>
        <div class="hero-actions">
          <el-button type="primary" size="large" @click="openForm()" class="btn-create">
            <el-icon><Plus /></el-icon><span>录入新房源</span>
          </el-button>
        </div>
      </div>
    </div>

    <!-- 统计卡片 -->
    <div class="stats-row">
      <div class="stat-card">
        <div class="stat-icon" style="background:linear-gradient(135deg,#667eea,#764ba2)"><el-icon :size="22"><OfficeBuilding /></el-icon></div>
        <div class="stat-info"><span class="stat-num">{{ stats.total }}</span><span class="stat-lbl">房源总数</span></div>
      </div>
      <div class="stat-card">
        <div class="stat-icon" style="background:linear-gradient(135deg,#43e97b,#38f9d7)"><el-icon :size="22"><CircleCheck /></el-icon></div>
        <div class="stat-info"><span class="stat-num">{{ stats.available }}</span><span class="stat-lbl">可租</span></div>
      </div>
      <div class="stat-card">
        <div class="stat-icon" style="background:linear-gradient(135deg,#f093fb,#f5576c)"><el-icon :size="22"><Lock /></el-icon></div>
        <div class="stat-info"><span class="stat-num">{{ stats.rented }}</span><span class="stat-lbl">已租出</span></div>
      </div>
      <div class="stat-card">
        <div class="stat-icon" style="background:linear-gradient(135deg,#f59e0b,#fbbf24)"><el-icon :size="22"><Money /></el-icon></div>
        <div class="stat-info"><span class="stat-num">&yen;{{ stats.avgRent }}</span><span class="stat-lbl">平均月租</span></div>
      </div>
    </div>

    <!-- 筛选栏 -->
    <div class="filter-bar">
      <div class="filter-left">
        <el-input v-model="query.keyword" placeholder="搜索房源名称、描述或标签..." clearable class="search-inp" @keyup.enter="handleSearch" @clear="handleSearch"><template #prefix><el-icon><Search /></el-icon></template></el-input>
        <el-select v-model="query.property_type" placeholder="房源类型" clearable class="filter-sel" @change="handleSearch">
          <el-option label="住宅" value="住宅"/><el-option label="公寓" value="公寓"/><el-option label="商铺" value="商铺"/><el-option label="办公室" value="办公室"/><el-option label="仓库" value="仓库"/><el-option label="车位" value="车位"/>
        </el-select>
        <el-select v-model="query.status" placeholder="租赁状态" clearable class="filter-sel" @change="handleSearch">
          <el-option label="可租" value="可租"/><el-option label="已租" value="已租"/><el-option label="预留" value="预留"/><el-option label="装修中" value="装修中"/><el-option label="已下架" value="已下架"/>
        </el-select>
        <el-select v-model="query.decoration" placeholder="装修情况" clearable class="filter-sel" @change="handleSearch">
          <el-option label="精装" value="精装"/><el-option label="简装" value="简装"/><el-option label="毛坯" value="毛坯"/><el-option label="豪装" value="豪装"/>
        </el-select>
        <el-select v-model="query.community_id" placeholder="所属小区" clearable class="filter-sel" @change="handleSearch">
          <el-option v-for="c in communities" :key="c.id" :label="c.name" :value="c.id"/>
        </el-select>
      </div>
      <div class="filter-right">
        <el-radio-group v-model="viewMode" size="small" class="view-toggle">
          <el-radio-button value="card"><el-icon><Grid /></el-icon></el-radio-button>
          <el-radio-button value="table"><el-icon><List /></el-icon></el-radio-button>
        </el-radio-group>
      </div>
    </div>

    <!-- 卡片视图 -->
    <div v-if="viewMode==='card'" class="property-grid" v-loading="loading">
      <div v-if="list.length===0&&!loading" class="empty-state">
        <el-icon :size="64"><FolderOpened /></el-icon>
        <h3>暂无可租赁房源</h3><p>点击「录入新房源」添加物业资产</p>
        <el-button type="primary" @click="openForm()">立即添加</el-button>
      </div>
      <div v-for="item in list" :key="item.id" class="property-card" @click="openForm(item)">
        <div class="card-img">
          <div class="img-placeholder" :class="'type-'+((item.status||'')==='已租'?'rented':'available')">
            <el-icon :size="32"><House /></el-icon>
          </div>
          <span class="card-badge" :class="statusBadgeClass(item.status)">{{ item.status||'未知' }}</span>
          <span class="card-rent">&yen;{{ item.monthly_rent||0 }}/月</span>
        </div>
        <div class="card-body">
          <div class="cb-top">
            <h3>{{ item.property_name||'未命名房源' }}</h3>
            <el-tag size="small" effect="plain" type="info">{{ item.property_type||'未分类' }}</el-tag>
          </div>
          <div class="cb-specs">
            <span><el-icon :size="14"><Location /></el-icon>{{ item.floor||0 }}层</span>
            <span><el-icon :size="14"><FullScreen /></el-icon>{{ item.area_built||0 }}㎡</span>
            <span><el-icon :size="14"><Brush /></el-icon>{{ item.decoration||'未知' }}</span>
            <span><el-icon :size="14"><Compass /></el-icon>{{ item.direction||'未知' }}</span>
          </div>
          <div class="cb-tags" v-if="item.feature_tags">
            <el-tag v-for="t in tagSplit(item.feature_tags)" :key="t" size="small" round class="feat-tag">{{ t }}</el-tag>
          </div>
          <div class="cb-actions" @click.stop>
            <el-button size="small" plain @click="openForm(item)"><el-icon><Edit /></el-icon>编辑</el-button>
            <el-popconfirm title="确定删除此房源？" @confirm="handleDelete(item)"><template #reference><el-button size="small" plain type="danger"><el-icon><Delete /></el-icon></el-button></template></el-popconfirm>
          </div>
        </div>
      </div>
    </div>

    <!-- 表格视图 -->
    <el-card v-else shadow="never" class="table-card">
      <el-table :data="list" v-loading="loading" stripe class="modern-table">
        <el-table-column type="index" label="#" width="55"/>
        <el-table-column prop="property_name" label="房源名称" min-width="160"><template #default="{row}"><span class="t-name">{{ row.property_name||'未命名' }}</span></template></el-table-column>
        <el-table-column prop="property_type" label="类型" width="90"><template #default="{row}"><el-tag size="small" effect="plain">{{ row.property_type||'未分类' }}</el-tag></template></el-table-column>
        <el-table-column prop="status" label="状态" width="95"><template #default="{row}"><span class="status-dot" :class="statusDotClass(row.status)"></span>{{ row.status||'未知' }}</template></el-table-column>
        <el-table-column prop="floor" label="楼层" width="75" align="center"/>
        <el-table-column prop="area_built" label="面积(㎡)" width="95" align="center"/>
        <el-table-column prop="decoration" label="装修" width="75" align="center"/>
        <el-table-column prop="direction" label="朝向" width="75" align="center"/>
        <el-table-column prop="monthly_rent" label="月租(元)" width="110" align="right"><template #default="{row}"><span class="rent-val">&yen;{{ row.monthly_rent||0 }}</span></template></el-table-column>
        <el-table-column prop="deposit_months" label="押金(月)" width="90" align="center"/>
        <el-table-column prop="min_lease_months" label="最短租期" width="90" align="center"><template #default="{row}">{{ row.min_lease_months||0 }}个月</template></el-table-column>
        <el-table-column prop="publish_time" label="发布时间" width="120"/>
        <el-table-column label="操作" width="140" fixed="right"><template #default="{row}">
          <el-button size="small" plain type="primary" @click="openForm(row)">编辑</el-button>
          <el-popconfirm title="确定删除？" @confirm="handleDelete(row)"><template #reference><el-button size="small" plain type="danger">删除</el-button></template></el-popconfirm>
        </template></el-table-column>
      </el-table>
    </el-card>

    <!-- 分页 -->
    <div class="pagination-wrap" v-if="total>query.limit">
      <el-pagination v-model:current-page="query.page" v-model:page-size="query.limit" :total="total" :page-sizes="[12,24,48,96]" layout="total, sizes, prev, pager, next, jumper" background/>
    </div>

    <!-- 编辑弹窗 -->
    <el-dialog v-model="dialogVisible" :title="editId?'编辑房源信息':'录入新房源'" width="760px" destroy-on-close :close-on-click-modal="false" class="property-dialog">
      <el-form :model="form" ref="formRef" label-width="90px">
        <!-- 房间数据联动选取器（住宅/公寓时可用） -->
        <div class="room-picker-bar" v-if="isRoomDataAvailable">
          <div class="room-picker-header">
            <span class="room-picker-icon"><el-icon><Connection /></el-icon></span>
            <span>房间管理数据联动</span>
            <el-tag size="small" type="success" effect="plain">自动填充</el-tag>
          </div>
          <div class="room-picker-body">
            <el-select v-model="selectedRoomId" filterable clearable :loading="vacantRoomsLoading"
                       placeholder="选择房间管理中的空置房产，自动填充下方信息..."
                       @change="onRoomSelect" @clear="clearRoomAutoFill"
                       :no-data-text="vacantRoomsLoaded ? '暂无符合条件的空置房间' : '选择小区和类型后自动加载'"
                       style="width:100%">
              <template #prefix>
                <el-icon><Search /></el-icon>
              </template>
              <el-option v-for="r in vacantRooms" :key="r.id" :label="labelRoom(r)" :value="r.id">
                <div class="room-option">
                  <div class="room-option-main">
                    <span class="room-option-name">{{ r.building_name }} {{ r.room_number }}</span>
                    <span class="room-option-area">{{ r.area||0 }}㎡ {{ r.layout||'' }}</span>
                  </div>
                  <div class="room-option-sub">
                    <span>{{ r.community_name }}</span>
                    <span>{{ r.floor }}层 · {{ orientLabel(r.orientation) }}</span>
                    <span>{{ decorateLabel(r.decorate_status) }}</span>
                  </div>
                </div>
              </el-option>
            </el-select>
            <div class="room-picker-hint" v-if="!selectedRoomId">
              <el-icon><InfoFilled /></el-icon> 选取房间后将自动填充：房源名称、小区、楼层、面积、朝向、装修
            </div>
            <div class="room-picker-hint success" v-else>
              <el-icon><CircleCheck /></el-icon> 已关联房间：{{ form.building_name }} {{ form.room_number }}，信息已自动填充，也可手动修改
            </div>
          </div>
        </div>

        <div class="form-section">
          <div class="section-title"><el-icon><InfoFilled /></el-icon>基本信息</div>
          <el-row :gutter="16">
            <el-col :span="12"><el-form-item label="房源名称" required><el-input v-model="form.property_name" :placeholder="isRoomDataAvailable ? '选择上方房间自动生成，或手动输入...' : '如：阳光花园3栋201'"/></el-form-item></el-col>
            <el-col :span="12"><el-form-item label="所属小区"><el-select v-model="form.community_id" style="width:100%" clearable @change="onCommunityChange"><el-option v-for="c in communities" :key="c.id" :label="c.name" :value="c.id"/></el-select></el-form-item></el-col>
          </el-row>
          <el-row :gutter="16">
            <el-col :span="12"><el-form-item label="房源类型"><el-select v-model="form.property_type" style="width:100%" @change="onTypeChange"><el-option label="住宅" value="住宅"/><el-option label="公寓" value="公寓"/><el-option label="商铺" value="商铺"/><el-option label="办公室" value="办公室"/><el-option label="仓库" value="仓库"/><el-option label="车位" value="车位"/></el-select></el-form-item></el-col>
            <el-col :span="6"><el-form-item label="楼层"><el-input-number v-model="form.floor" :min="0" controls-position="right" style="width:100%"/></el-form-item></el-col>
            <el-col :span="6"><el-form-item label="建筑面积(㎡)"><el-input v-model="form.area_built" placeholder="如：86.5"/></el-form-item></el-col>
            <el-col :span="6"><el-form-item label="实用面积(㎡)"><el-input v-model="form.area_used" placeholder="如：72.0"/></el-form-item></el-col>
          </el-row>
          <el-row :gutter="16">
            <el-col :span="8"><el-form-item label="装修情况"><el-select v-model="form.decoration" style="width:100%"><el-option label="精装" value="精装"/><el-option label="简装" value="简装"/><el-option label="毛坯" value="毛坯"/><el-option label="豪装" value="豪装"/></el-select></el-form-item></el-col>
            <el-col :span="8"><el-form-item label="朝向"><el-select v-model="form.direction" style="width:100%"><el-option label="东" value="东"/><el-option label="南" value="南"/><el-option label="西" value="西"/><el-option label="北" value="北"/><el-option label="东南" value="东南"/><el-option label="西南" value="西南"/><el-option label="东北" value="东北"/><el-option label="西北" value="西北"/><el-option label="南北" value="南北"/></el-select></el-form-item></el-col>
            <el-col :span="8"><el-form-item label="租赁状态"><el-select v-model="form.status" style="width:100%"><el-option label="可租" value="可租"/><el-option label="已租" value="已租"/><el-option label="预留" value="预留"/><el-option label="装修中" value="装修中"/><el-option label="已下架" value="已下架"/></el-select></el-form-item></el-col>
          </el-row>
          <el-form-item label="特色标签"><el-input v-model="form.feature_tags" placeholder="多个标签用逗号分隔，如：近地铁,拎包入住,采光好"/></el-form-item>
        </div>
        <div class="form-section">
          <div class="section-title"><el-icon><Money /></el-icon>租金设置</div>
          <el-row :gutter="16">
            <el-col :span="8"><el-form-item label="月租金(元)"><el-input v-model="form.monthly_rent" placeholder="如：3500"/></el-form-item></el-col>
            <el-col :span="8"><el-form-item label="押金月数"><el-input-number v-model="form.deposit_months" :min="0" :max="12" controls-position="right" style="width:100%"/></el-form-item></el-col>
            <el-col :span="8"><el-form-item label="最短租期(月)"><el-input-number v-model="form.min_lease_months" :min="1" :max="120" controls-position="right" style="width:100%"/></el-form-item></el-col>
          </el-row>
          <el-row :gutter="16">
            <el-col :span="8"><el-form-item label="物业费(元/月)"><el-input v-model="form.property_tax" placeholder="如：200"/></el-form-item></el-col>
            <el-col :span="8"><el-form-item label="发布时间"><el-input v-model="form.publish_time" placeholder="如：2025-01-01"/></el-form-item></el-col>
          </el-row>
        </div>
        <div class="form-section">
          <div class="section-title"><el-icon><EditPen /></el-icon>其他信息</div>
          <el-row :gutter="16">
            <el-col :span="12"><el-form-item label="封面图片URL"><el-input v-model="form.cover_image" placeholder="房源封面图URL"/></el-form-item></el-col>
            <el-col :span="12"><el-form-item label="更多图片URL"><el-input v-model="form.images" placeholder="多个URL用逗号分隔"/></el-form-item></el-col>
          </el-row>
          <el-form-item label="房源描述"><el-input v-model="form.description" type="textarea" :rows="3" placeholder="描述房源优势、周边配套等信息"/></el-form-item>
          <el-form-item label="备注"><el-input v-model="form.remark" placeholder="内部备注信息"/></el-form-item>
        </div>
      </el-form>
      <template #footer><div class="dialog-footer"><el-button @click="dialogVisible=false">取消</el-button><el-button type="primary" @click="handleSubmit" :loading="submitting">{{editId?'保存修改':'确认录入'}}</el-button></div></template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { apiGet, apiPost } from '@/utils/request'
import { ElMessage, ElMessageBox } from 'element-plus'
import { House, Plus, OfficeBuilding, CircleCheck, Lock, Money, Search, Grid, List, FolderOpened, Location, FullScreen, Brush, Compass, Edit, Delete, InfoFilled, EditPen, Connection } from '@element-plus/icons-vue'

const list = ref<any[]>([])
const total = ref(0)
const loading = ref(false)
const submitting = ref(false)
const dialogVisible = ref(false)
const editId = ref(0)
const viewMode = ref<'card'|'table'>('card')
const formRef = ref()

// 房间数据联动
const vacantRooms = ref<any[]>([])
const vacantRoomsLoading = ref(false)
const vacantRoomsLoaded = ref(false)
const selectedRoomId = ref<number | null>(null)

const query = reactive({ page:1, limit:12, keyword:'', property_type:'', status:'', decoration:'', community_id:undefined as any })
const communities = ref<any[]>([])
const form = reactive<any>({
  community_id:0, building_id:0, room_id:0, building_name:'', room_number:'', property_name:'', property_type:'住宅',
  floor:1, area_built:'', area_used:'', decoration:'精装', direction:'南', feature_tags:'',
  monthly_rent:'', deposit_months:2, min_lease_months:12, property_tax:'',
  status:'可租', publish_time:'', cover_image:'', images:'', description:'', remark:''
})

// 是否显示房间数据联动（住宅/公寓）
const isRoomDataAvailable = computed(() => {
  return ['住宅','公寓'].includes(form.property_type)
})

function orientLabel(o: string) { const m: Record<string,string>={东:'东',南:'南',西:'西',北:'北',东南:'东南',西南:'西南',东北:'东北',西北:'西北',南北:'南北'}; return m[o] || o }
function decorateLabel(d: any) { const m: Record<number,string> = {0:'毛坯',1:'简装',2:'精装',3:'豪装'}; return m[Number(d)] || '' }
function labelRoom(r: any) { return `${r.building_name||''} ${r.room_number||''} (${r.area||0}㎡) - ${r.community_name||''}` }

const stats = computed(()=>{
  const data = list.value||[]
  const rents = data.filter((d:any)=>d.monthly_rent>0).map((d:any)=>Number(d.monthly_rent)||0)
  return {
    total: total.value,
    available: data.filter((d:any)=>d.status==='可租').length,
    rented: data.filter((d:any)=>d.status==='已租').length,
    avgRent: rents.length?Math.round(rents.reduce((a:number,b:number)=>a+b,0)/rents.length):0
  }
})

function tagSplit(s:string){return s?s.split(/[,，]/).filter(Boolean).slice(0,5):[]}
function statusBadgeClass(s:string){
  const m:Record<string,string>={'可租':'badge-ok','已租':'badge-rented','预留':'badge-reserve','装修中':'badge-reno','已下架':'badge-off'}
  return m[s]||''
}
function statusDotClass(s:string){
  const m:Record<string,string>={'可租':'dot-ok','已租':'dot-rented','预留':'dot-reserve','装修中':'dot-reno','已下架':'dot-off'}
  return m[s]||''
}

function handleSearch(){query.page=1;loadData()}

async function loadData(){
  loading.value=true
  try{
    const p:any={page:query.page,limit:query.limit}
    if(query.keyword)p.keyword=query.keyword
    if(query.property_type)p.property_type=query.property_type
    if(query.status)p.status=query.status
    if(query.decoration)p.decoration=query.decoration
    if(query.community_id)p.community_id=query.community_id
    const res:any=await apiGet('/admin/lease/leasePropertyList',p)
    if(res&&(res.code===0||res.code===undefined)){list.value=res.data||[];total.value=res.count||0}
  }catch(_){list.value=[];total.value=0}
  finally{loading.value=false}
}

function openForm(row?: any) {
  if (row) {
    editId.value = row.id
    Object.keys(form).forEach(k => { if (row[k] !== undefined) (form as any)[k] = row[k] })
    selectedRoomId.value = row.room_id > 0 ? row.room_id : null
  } else {
    editId.value = 0
    Object.assign(form, {
      community_id:0, building_id:0, room_id:0, building_name:'', room_number:'',
      property_name:'', property_type:'住宅', floor:1, area_built:'', area_used:'',
      decoration:'精装', direction:'南', feature_tags:'', monthly_rent:'',
      deposit_months:2, min_lease_months:12, property_tax:'', status:'可租',
      publish_time:'', cover_image:'', images:'', description:'', remark:''
    })
    selectedRoomId.value = null
    vacantRooms.value = []
    vacantRoomsLoaded.value = false
  }
  dialogVisible.value = true
  if (isRoomDataAvailable.value) loadVacantRooms()
}

async function loadVacantRooms() {
  if (!isRoomDataAvailable.value) return
  vacantRoomsLoading.value = true
  try {
    const p: any = { property_type: form.property_type }
    if (form.community_id) p.community_id = form.community_id
    const res: any = await apiGet('/admin/lease/vacantRooms', p)
    if (res && (res.code === 0 || res.code === undefined)) {
      vacantRooms.value = res.data || []
    }
    vacantRoomsLoaded.value = true
  } catch (_) { vacantRooms.value = [] }
  finally { vacantRoomsLoading.value = false }
}

function onRoomSelect(roomId: number) {
  const room = vacantRooms.value.find((r: any) => r.id === roomId)
  if (!room) return
  // 自动填充表单
  form.community_id = room.community_id || 0
  form.building_id   = room.building_id || 0
  form.room_id       = room.id
  form.building_name = room.building_name || ''
  form.room_number   = room.room_number || ''
  form.property_name = `${room.community_name||''} ${room.building_name||''} ${room.room_number||''}`.trim()
  form.floor         = Number(room.floor) || 1
  form.area_built    = room.area || ''
  form.area_used     = room.usable_area || ''
  form.direction     = room.orientation || '南'
  // 装修状态映射
  const decoMap: Record<number,string> = {0:'毛坯',1:'简装',2:'精装',3:'豪装'}
  form.decoration    = decoMap[Number(room.decorate_status)] || '精装'
}

function clearRoomAutoFill() {
  selectedRoomId.value = null
  form.building_id   = 0
  form.room_id       = 0
  form.building_name = ''
  form.room_number   = ''
  // 不清除其他字段，保留用户可能的修改
}

function onTypeChange() {
  selectedRoomId.value = null
  form.building_id = 0
  form.room_id = 0
  form.building_name = ''
  form.room_number = ''
  if (isRoomDataAvailable.value) {
    loadVacantRooms()
  } else {
    vacantRooms.value = []
    vacantRoomsLoaded.value = false
  }
}

function onCommunityChange() {
  if (isRoomDataAvailable.value) loadVacantRooms()
}

async function handleSubmit(){
  submitting.value=true
  try{
    const url=editId.value?'/admin/lease/leasePropertyEdit':'/admin/lease/leasePropertyAdd'
    const payload: any = { ...form }
    delete payload.building_name
    delete payload.room_number
    if (editId.value) payload.id = editId.value
    const res:any=await apiPost(url,payload)
    if(res&&(res.code===0||res.code===undefined)){ElMessage.success(editId.value?'房源已更新':'房源已录入');dialogVisible.value=false;loadData()}
  }catch(_){}
  finally{submitting.value=false}
}

async function handleDelete(row:any){
  try{await ElMessageBox.confirm('确定删除该房源？','提示',{type:'warning'});const res:any=await apiPost('/admin/lease/leasePropertyDelete',{id:row.id});if(res&&(res.code===0||res.code===undefined)){ElMessage.success('删除成功');loadData()}}catch(_){}
}

onMounted(async ()=>{
  try{const r:any=await apiGet('/admin/community/list',{limit:999});communities.value=r.data?.list||r.data||[]}catch(_){}
  loadData()
})
watch([()=>query.page,()=>query.limit],()=>loadData())
</script>

<style scoped>
.lease-property-page{min-height:calc(100vh - 100px)}
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
.filter-left{display:flex;gap:12px;flex:1;flex-wrap:wrap}
.search-inp{width:260px}.search-inp :deep(.el-input__wrapper){border-radius:10px}
.filter-sel{width:140px}.filter-sel :deep(.el-input__wrapper){border-radius:10px}

.property-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:20px;min-height:200px}
.property-card{background:#fff;border-radius:16px;border:1px solid #e8ecf1;overflow:hidden;cursor:pointer;transition:all .3s cubic-bezier(.4,0,.2,1);position:relative}
.property-card:hover{transform:translateY(-4px);box-shadow:0 16px 40px rgba(0,0,0,.1);border-color:#93c5fd}
.card-img{position:relative;height:150px;overflow:hidden}
.img-placeholder{width:100%;height:100%;display:flex;align-items:center;justify-content:center}
.img-placeholder.type-available{background:linear-gradient(135deg,#e8f5e9,#c8e6c9);color:#388e3c}
.img-placeholder.type-rented{background:linear-gradient(135deg,#fce4ec,#f8bbd0);color:#c62828}
.card-badge{position:absolute;top:12px;left:12px;padding:4px 12px;border-radius:20px;font-size:11px;font-weight:700;backdrop-filter:blur(8px)}
.badge-ok{background:#22c55e;color:#fff}.badge-rented{background:#ef4444;color:#fff}.badge-reserve{background:#f59e0b;color:#fff}.badge-reno{background:#8b5cf6;color:#fff}.badge-off{background:#94a3b8;color:#fff}
.card-rent{position:absolute;bottom:12px;right:12px;padding:4px 14px;background:rgba(0,0,0,.7);color:#fbbf24;border-radius:20px;font-size:14px;font-weight:700;backdrop-filter:blur(8px)}
.card-body{padding:16px 18px 18px}.cb-top{display:flex;justify-content:space-between;align-items:center;margin-bottom:10px}.cb-top h3{margin:0;font-size:15px;font-weight:600;color:#1a202c}.cb-specs{display:flex;gap:14px;font-size:12px;color:#64748b;margin-bottom:10px;flex-wrap:wrap}.cb-specs span{display:flex;align-items:center;gap:3px}.cb-tags{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:12px}.feat-tag{background:#f0f5ff;color:#2563eb;border-color:#bfdbfe}.cb-actions{display:flex;gap:8px;padding-top:12px;border-top:1px solid #f0f2f5}

.table-card{border-radius:14px;border:1px solid #e8ecf1;overflow:hidden}.table-card :deep(.el-card__body){padding:0}
.modern-table :deep(.el-table__header th){background:#f8fafc;font-weight:600;color:#475569;font-size:13px}
.t-name{font-weight:500}.rent-val{color:#dc2626;font-weight:600}
.status-dot{display:inline-block;width:7px;height:7px;border-radius:50%;margin-right:6px}
.dot-ok{background:#22c55e}.dot-rented{background:#ef4444}.dot-reserve{background:#f59e0b}.dot-reno{background:#8b5cf6}.dot-off{background:#94a3b8}

.empty-state{grid-column:1/-1;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:80px 20px;background:#fff;border-radius:16px;border:2px dashed #e2e8f0;color:#cbd5e1}
.empty-state h3{color:#475569;margin:12px 0 8px;font-size:18px}.empty-state p{color:#94a3b8;margin:0 0 20px;font-size:14px}

.pagination-wrap{display:flex;justify-content:center;margin-top:24px}

.property-dialog :deep(.el-dialog__header){border-bottom:1px solid #f0f2f5;padding:20px 24px}
.property-dialog :deep(.el-dialog__body){padding:24px}
.room-picker-bar{background:linear-gradient(135deg,#f0f9ff 0%,#ecfdf5 100%);border:1px solid #bae6fd;border-radius:12px;padding:16px 20px;margin-bottom:20px}
.room-picker-header{display:flex;align-items:center;gap:8px;font-size:14px;font-weight:600;color:#0369a1;margin-bottom:12px}
.room-picker-icon{display:flex;align-items:center}
.room-picker-body{display:flex;flex-direction:column;gap:8px}
.room-picker-hint{display:flex;align-items:center;gap:6px;font-size:12px;color:#64748b}
.room-picker-hint.success{color:#16a34a}
.room-option{padding:4px 0}
.room-option-main{display:flex;justify-content:space-between;align-items:center;font-weight:500}
.room-option-name{color:#1a202c}
.room-option-area{color:#2563eb;font-size:12px}
.room-option-sub{display:flex;gap:10px;font-size:11px;color:#94a3b8;margin-top:3px}
.form-section{margin-bottom:24px;padding-bottom:20px;border-bottom:1px solid #f0f2f5}
.form-section:last-child{border-bottom:none;margin-bottom:0;padding-bottom:0}
.section-title{font-size:14px;font-weight:700;color:#1a365d;margin-bottom:16px;display:flex;align-items:center;gap:8px}
.section-title .el-icon{color:#2563eb}
.dialog-footer{display:flex;justify-content:flex-end;gap:12px}

@media(max-width:1400px){.property-grid{grid-template-columns:repeat(2,1fr)}.stats-row{grid-template-columns:repeat(2,1fr)}}
@media(max-width:900px){.property-grid{grid-template-columns:1fr}.stats-row{grid-template-columns:1fr 1fr}.filter-bar{flex-direction:column}.filter-left{flex-direction:column}.search-inp,.filter-sel{width:100%!important}}
</style>
