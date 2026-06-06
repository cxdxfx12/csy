<template>
  <div class="page-container">
    <!-- 统计卡片 -->
    <div class="stats-row">
      <div class="stat-card" v-for="s in statsCards" :key="s.key" :style="{borderTopColor:s.color}">
        <div class="stat-num">{{ s.value }}</div>
        <div class="stat-label">{{ s.label }}</div>
      </div>
    </div>

    <!-- 搜索 -->
    <div class="search-bar">
      <el-form :model="query" inline>
        <el-form-item><el-input v-model="query.keyword" placeholder="编号/房号/业主/公司" clearable style="width:220px;" /></el-form-item>
        <el-form-item><el-select v-model="query.community_id" placeholder="小区" clearable style="width:160px;"><el-option v-for="c in communities" :key="c.id" :label="c.name" :value="c.id" /></el-select></el-form-item>
        <el-form-item><el-select v-model="query.status" placeholder="状态" clearable style="width:130px;"><el-option v-for="(v,k) in statusMap" :key="k" :label="v" :value="Number(k)" /></el-select></el-form-item>
        <el-form-item>
          <el-button type="primary" @click="loadData">搜索</el-button>
          <el-button @click="resetQuery">重置</el-button>
        </el-form-item>
      </el-form>
    </div>

    <!-- 表格 -->
    <el-card shadow="never" class="table-card">
      <div class="table-toolbar">
        <el-button type="primary" @click="openForm()">新增申请</el-button>
        <el-button @click="loadStats">刷新统计</el-button>
      </div>
      <el-table :data="list" v-loading="loading" stripe border>
        <el-table-column type="index" label="#" width="50" />
        <el-table-column prop="apply_no" label="申请编号" width="160" />
        <el-table-column prop="community_name" label="小区" width="130" />
        <el-table-column prop="room_number" label="房间号" width="110" />
        <el-table-column prop="owner_name" label="业主" width="100" />
        <el-table-column prop="company_name" label="装修公司" width="140" show-overflow-tooltip />
        <el-table-column prop="leader_name" label="负责人" width="90" />
        <el-table-column prop="status_name" label="状态" width="90">
          <template #default="{row}"><el-tag :type="statusTagType[row.status]||'info'" size="small">{{ row.status_name }}</el-tag></template>
        </el-table-column>
        <el-table-column prop="total_fee" label="费用合计" width="100">
          <template #default="{row}">¥{{ Number(row.total_fee||0).toFixed(2) }}</template>
        </el-table-column>
        <el-table-column prop="start_date" label="开工日期" width="110" />
        <el-table-column prop="end_date" label="竣工日期" width="110" />
        <el-table-column label="操作" width="280" fixed="right">
          <template #default="{row}">
            <el-button size="small" @click="openDetail(row)">详情</el-button>
            <el-button v-if="row.status===0" size="small" type="success" @click="auditApply(row,'pass')">通过</el-button>
            <el-button v-if="row.status===0" size="small" type="danger" @click="auditApply(row,'reject')">驳回</el-button>
            <el-button v-if="row.status===1" size="small" type="warning" @click="chargeApply(row)">缴费</el-button>
            <el-button v-if="row.status===2" size="small" type="primary" @click="requestAccept(row)">提请验收</el-button>
            <el-button v-if="row.status===3" size="small" type="success" @click="acceptApply(row)">验收</el-button>
            <el-button v-if="row.status===4&&Number(row.deposit_amount||0)>0&&!(Number(row.refund_amount||0)>0)" size="small" type="warning" @click="refundApply(row)">退押金</el-button>
            <el-button v-if="row.status<4&&row.status!==6" size="small" type="danger" @click="cancelApply(row)">取消</el-button>
          </template>
        </el-table-column>
      </el-table>
      <div class="pagination">
        <el-pagination v-model:current-page="query.page" v-model:page-size="query.limit" :total="total" :page-sizes="[15,30,50,100]" layout="total,sizes,prev,pager,next" @current-change="loadData" @size-change="loadData" />
      </div>
    </el-card>

    <!-- 新增/编辑弹窗 -->
    <el-dialog v-model="dialogVisible" :title="formTitle" width="800px" destroy-on-close>
      <el-form :model="form" :rules="rules" ref="formRef" label-width="110px">
        <el-row :gutter="16">
          <el-col :span="12"><el-form-item label="所属小区" prop="community_id"><el-select v-model="form.community_id" placeholder="选择小区" style="width:100%;" :disabled="!!form.id" @change="onCommunityChange"><el-option v-for="c in communities" :key="c.id" :label="c.name" :value="c.id" /></el-select></el-form-item></el-col>
          <el-col :span="12"><el-form-item label="房间" prop="room_id"><el-select v-model="form.room_id" placeholder="选择房间" filterable style="width:100%;" @change="onRoomChange"><el-option v-for="r in rooms" :key="r.id" :label="r.room_number + ' (' + (r.building_name||'') + ')' + (r.owner_name ? ' - ' + r.owner_name : '')" :value="r.id" /></el-select></el-form-item></el-col>
        </el-row>
        <el-row :gutter="16">
          <el-col :span="12"><el-form-item label="业主" prop="owner_id"><el-select v-model="form.owner_id" placeholder="选择业主" filterable style="width:100%;" :disabled="roomHasOwner"><el-option v-for="o in owners" :key="o.id" :label="o.realname + ' - ' + o.phone" :value="o.id" /></el-select></el-form-item></el-col>
          <el-col :span="12"><el-form-item label="装修公司"><el-input v-model="form.company_name" placeholder="装修公司名称" /></el-form-item></el-col>
        </el-row>
        <el-row :gutter="16">
          <el-col :span="12"><el-form-item label="负责人"><el-input v-model="form.leader_name" placeholder="施工负责人" /></el-form-item></el-col>
          <el-col :span="12"><el-form-item label="负责人电话"><el-input v-model="form.leader_phone" placeholder="手机号" /></el-form-item></el-col>
        </el-row>
        <el-row :gutter="16">
          <el-col :span="12"><el-form-item label="计划开工" prop="start_date"><el-date-picker v-model="form.start_date" type="date" placeholder="开工日期" style="width:100%;" value-format="YYYY-MM-DD" /></el-form-item></el-col>
          <el-col :span="12"><el-form-item label="计划竣工" prop="end_date"><el-date-picker v-model="form.end_date" type="date" placeholder="竣工日期" style="width:100%;" value-format="YYYY-MM-DD" /></el-form-item></el-col>
        </el-row>
        <el-form-item label="装修内容"><el-input v-model="form.content" type="textarea" :rows="2" placeholder="简要描述装修内容" /></el-form-item>
        <el-divider content-position="left">费用明细</el-divider>
        <el-row :gutter="16">
          <el-col :span="12"><el-form-item label="押金（元）"><el-input v-model="form.deposit_amount" placeholder="3000" /></el-form-item></el-col>
          <el-col :span="12"><el-form-item label="管理费（元）"><el-input v-model="form.manage_fee" placeholder="0" /></el-form-item></el-col>
        </el-row>
        <el-row :gutter="16">
          <el-col :span="12"><el-form-item label="垃圾费（元）"><el-input v-model="form.trash_fee" placeholder="0" /></el-form-item></el-col>
          <el-col :span="12"><el-form-item label="其他费用（元）"><el-input v-model="form.other_fee" placeholder="0" /></el-form-item></el-col>
        </el-row>
      </el-form>
      <template #footer>
        <el-button @click="dialogVisible=false">取消</el-button>
        <el-button type="primary" @click="submitForm" :loading="submitting">确定</el-button>
      </template>
    </el-dialog>

    <!-- 详情弹窗 -->
    <el-dialog v-model="detailVisible" title="装修详情" width="800px" destroy-on-close>
      <template v-if="detail">
        <el-descriptions :column="2" border>
          <el-descriptions-item label="申请编号">{{ detail.apply_no }}</el-descriptions-item>
          <el-descriptions-item label="状态"><el-tag :type="statusTagType[detail.status]||'info'">{{ detail.status_name }}</el-tag></el-descriptions-item>
          <el-descriptions-item label="小区">{{ detail.community_name }}</el-descriptions-item>
          <el-descriptions-item label="房间">{{ detail.room_number }} ({{ detail.building_name }})</el-descriptions-item>
          <el-descriptions-item label="业主">{{ detail.owner_name }}</el-descriptions-item>
          <el-descriptions-item label="业主电话">{{ detail.owner_phone }}</el-descriptions-item>
          <el-descriptions-item label="装修公司">{{ detail.company_name }}</el-descriptions-item>
          <el-descriptions-item label="负责人">{{ detail.leader_name }} {{ detail.leader_phone }}</el-descriptions-item>
          <el-descriptions-item label="开工日期">{{ detail.start_date }}</el-descriptions-item>
          <el-descriptions-item label="竣工日期">{{ detail.end_date }}</el-descriptions-item>
          <el-descriptions-item label="装修内容" :span="2">{{ detail.content }}</el-descriptions-item>
          <el-descriptions-item label="押金">¥{{ Number(detail.deposit_amount||0).toFixed(2) }}</el-descriptions-item>
          <el-descriptions-item label="管理费">¥{{ Number(detail.manage_fee||0).toFixed(2) }}</el-descriptions-item>
          <el-descriptions-item label="垃圾费">¥{{ Number(detail.trash_fee||0).toFixed(2) }}</el-descriptions-item>
          <el-descriptions-item label="其他">¥{{ Number(detail.other_fee||0).toFixed(2) }}</el-descriptions-item>
          <el-descriptions-item label="费用合计">¥{{ Number(detail.total_fee||0).toFixed(2) }}</el-descriptions-item>
          <el-descriptions-item label="已缴">¥{{ Number(detail.paid_amount||0).toFixed(2) }}</el-descriptions-item>
          <el-descriptions-item label="已退押金">¥{{ Number(detail.refund_amount||0).toFixed(2) }}</el-descriptions-item>
          <el-descriptions-item v-if="detail.audit_remark" label="审核意见">{{ detail.audit_remark }}</el-descriptions-item>
          <el-descriptions-item v-if="detail.accept_result" label="验收结论">{{ detail.accept_result }}</el-descriptions-item>
        </el-descriptions>

        <el-divider content-position="left">施工人员 ({{ detail.workers?.length||0 }}人)</el-divider>
        <el-table :data="detail.workers||[]" size="small" border>
          <el-table-column prop="name" label="姓名" width="80" />
          <el-table-column prop="job_type" label="工种" width="90" />
          <el-table-column prop="phone" label="电话" width="130" />
          <el-table-column prop="id_card" label="身份证" width="180" />
          <el-table-column prop="card_no" label="出入证号" width="120" />
          <el-table-column prop="card_expire_date" label="有效期至" width="110" />
        </el-table>

        <el-divider content-position="left">巡查记录</el-divider>
        <el-table :data="detail.inspects||[]" size="small" border>
          <el-table-column prop="inspect_time" label="时间" width="170" />
          <el-table-column prop="inspector_name" label="巡查人" width="90" />
          <el-table-column prop="result" label="结果" width="80">
            <template #default="{row}"><el-tag :type="row.result? 'danger':'success'" size="small">{{ row.result?'异常':'正常' }}</el-tag></template>
          </el-table-column>
          <el-table-column prop="content" label="内容" show-overflow-tooltip />
        </el-table>

        <el-divider content-position="left">违规记录</el-divider>
        <el-table :data="detail.violations||[]" size="small" border>
          <el-table-column prop="violation_type" label="类型" width="100" />
          <el-table-column prop="description" label="描述" show-overflow-tooltip />
          <el-table-column prop="penalty_amount" label="罚金" width="90">
            <template #default="{row}">¥{{ Number(row.penalty_amount||0).toFixed(2) }}</template>
          </el-table-column>
          <el-table-column prop="status" label="状态" width="90">
            <template #default="{row}"><el-tag :type="row.status===1?'success':row.status===2?'danger':'warning'" size="small">{{ ['待整改','已整改','已扣款'][row.status]||'未知' }}</el-tag></template>
          </el-table-column>
        </el-table>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, watch } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { apiGet, apiPost } from '@/utils/request'

const list = ref<any[]>([])
const total = ref(0)
const loading = ref(false)
const dialogVisible = ref(false)
const detailVisible = ref(false)
const submitting = ref(false)
const formRef = ref<any>(null)
const formTitle = ref('新增装修申请')
const communities = ref<any[]>([])
const rooms = ref<any[]>([])
const owners = ref<any[]>([])
const detail = ref<any>(null)

const statusMap: Record<number,string> = {0:'待审核',1:'待缴费',2:'施工中',3:'待验收',4:'已完成',5:'已驳回',6:'已取消'}
const statusTagType: Record<number,string> = {0:'warning',1:'info',2:'primary',3:'danger',4:'success',5:'danger',6:'info'}

const statsCards = reactive([
  { key:'pending_audit', label:'待审核', value:0, color:'#e6a23c' },
  { key:'pending_pay', label:'待缴费', value:0, color:'#909399' },
  { key:'in_progress', label:'施工中', value:0, color:'#409eff' },
  { key:'pending_accept', label:'待验收', value:0, color:'#f56c6c' },
  { key:'completed', label:'已完成', value:0, color:'#67c23a' },
  { key:'pending_violation', label:'待整改违规', value:0, color:'#e040fb' },
])

const query = reactive({ keyword:'', community_id:undefined as any, status:undefined as any, page:1, limit:15 })
const form = reactive<any>({ id:0, community_id:'', room_id:'', owner_id:'', company_name:'', leader_name:'', leader_phone:'', start_date:'', end_date:'', content:'', deposit_amount:3000, manage_fee:0, trash_fee:0, other_fee:0 })
const rules = { community_id:[{required:true,message:'请选择小区',trigger:'change'}], room_id:[{required:true,message:'请选择房间',trigger:'change'}], owner_id:[{required:true,message:'请选择业主',trigger:'change'}], start_date:[{required:true,message:'请选择开工日期',trigger:'change'}], end_date:[{required:true,message:'请选择竣工日期',trigger:'change'}] }

function resetQuery() { query.keyword=''; query.community_id=undefined; query.status=undefined; query.page=1; loadData() }

async function loadData() {
  loading.value = true
  try {
    const r = await apiGet('/admin/decoration/applyList', {...query})
    list.value = r.data?.list || r.data || []
    total.value = r.count || 0
  } catch { list.value=[]; total.value=0 }
  finally { loading.value=false }
}

async function loadStats() {
  try {
    const r = await apiGet('/admin/decoration/statistics', {community_id: query.community_id||undefined})
    const d = r.data
    statsCards.forEach(s => { s.value = d[s.key] ?? 0 })
  } catch {}
}

function openForm(row?: any) {
  formTitle.value = row ? '编辑装修申请' : '新增装修申请'
  const base = {id:0, community_id:'', room_id:'', owner_id:'', company_name:'', leader_name:'', leader_phone:'', start_date:'', end_date:'', content:''}
  if (row) {
    Object.assign(form, base, {
      ...row,
      // 确保 el-input-number 用的是数字类型
      deposit_amount: Number(row.deposit_amount ?? 3000),
      manage_fee: Number(row.manage_fee ?? 0),
      trash_fee: Number(row.trash_fee ?? 0),
      other_fee: Number(row.other_fee ?? 0),
    })
  } else {
    Object.assign(form, base, {deposit_amount:3000, manage_fee:0, trash_fee:0, other_fee:0})
  }
  dialogVisible.value = true
  // 如果是编辑已有记录，加载对应小区的房间和业主
  if (row?.community_id) loadRoomsAndOwners(row.community_id)
}

async function submitForm() {
  try {
    await formRef.value?.validate()
  } catch {
    ElMessage.warning('请完善必填信息（小区、房间、业主、日期）')
    return
  }
  submitting.value = true
  try {
    const url = form.id ? '/admin/decoration/applyEdit' : '/admin/decoration/applyAdd'
    const data = {
      ...form,
      deposit_amount: Number(form.deposit_amount) || 0,
      manage_fee: Number(form.manage_fee) || 0,
      trash_fee: Number(form.trash_fee) || 0,
      other_fee: Number(form.other_fee) || 0,
    }
    await apiPost(url, data)
    ElMessage.success(form.id ? '修改成功' : '添加成功')
    dialogVisible.value = false
    loadData(); loadStats()
  } catch {
    // 重载数据 — 如接口返回异常但数据实际已写入，列表会刷新
    loadData(); loadStats()
  } finally { submitting.value = false }
}

async function openDetail(row: any) {
  try {
    const r = await apiGet('/admin/decoration/applyDetail', {id:row.id})
    detail.value = r.data
    detailVisible.value = true
  } catch {}
}

async function auditApply(row: any, action: string) {
  try {
    if (action==='reject') {
      const {value:remark} = await ElMessageBox.prompt('请输入驳回原因', '驳回申请', {type:'warning',confirmButtonText:'确定驳回'})
      if (!remark) return
      await apiPost('/admin/decoration/applyAudit', {id:row.id, action:'reject', remark})
    } else {
      await ElMessageBox.confirm(`确定通过装修申请 "${row.apply_no}" 吗？`, '审核通过', {type:'success',confirmButtonText:'确定通过'})
      await apiPost('/admin/decoration/applyAudit', {id:row.id, action:'pass'})
    }
    ElMessage.success('操作成功'); loadData(); loadStats()
  } catch {}
}

async function chargeApply(row: any) {
  try {
    const {value:amount} = await ElMessageBox.prompt('请输入实际缴费金额', '确认缴费', {
      confirmButtonText:'确认缴费',
      inputValue: String(row.total_fee||0),
      inputType:'number',
    })
    if (!amount) return
    await apiPost('/admin/decoration/applyCharge', {id:row.id, paid_amount:Number(amount)})
    ElMessage.success('缴费确认成功'); loadData(); loadStats()
  } catch {}
}

async function requestAccept(row: any) {
  try {
    await ElMessageBox.confirm(`确定提请验收 "${row.apply_no}" 吗？`, '提请验收', {type:'warning'})
    await apiPost('/admin/decoration/applyRequestAccept', {id:row.id})
    ElMessage.success('已提交验收申请'); loadData(); loadStats()
  } catch {}
}

async function acceptApply(row: any) {
  try {
    const {value:result} = await ElMessageBox.prompt('请输入验收结论', '竣工验收', {
      confirmButtonText:'验收通过',
      inputType:'textarea',
    })
    await ElMessageBox.confirm(`验收结论: ${result||'(空)'}。确定验收通过吗？\n\n验收通过后可在列表操作栏手动退押金。`, '确认验收', {type:'success',confirmButtonText:'确认',cancelButtonText:'取消'})
    await apiPost('/admin/decoration/applyAccept', {id:row.id, accept_result: result||'', is_pass:1})
    ElMessage.success('验收通过，请及时办理押金退还'); loadData(); loadStats()
  } catch {}
}

async function cancelApply(row: any) {
  try {
    const {value:remark} = await ElMessageBox.prompt('请输入取消原因', '取消申请', {type:'warning'})
    await apiPost('/admin/decoration/applyCancel', {id:row.id, remark})
    ElMessage.success('已取消'); loadData(); loadStats()
  } catch {}
}

async function refundApply(row: any) {
  try {
    const deposit = Number(row.deposit_amount || 0)
    let msg = `<div style="text-align:left;line-height:2;">
      <p><b>申请编号：</b>${row.apply_no}</p>
      <p><b>房间：</b>${row.room_number || ''} ${row.building_name || ''}</p>
      <p><b>业主：</b>${row.owner_name || ''}</p>
      <p><b>押金金额：</b>¥${deposit.toFixed(2)}</p>
      <p><b style="font-size:16px;">退还金额：</b><span style="color:#67c23a;font-size:18px;font-weight:bold;">¥${deposit.toFixed(2)}</span></p>
      <p style="color:#909399;font-size:12px;">如有违规扣款，系统自动扣除后实际退还</p></div>`
    await ElMessageBox.confirm(msg, '退还装修押金', { type:'warning', confirmButtonText:'确认退款', dangerouslyUseHTMLString:true })
    await apiPost('/admin/decoration/applyRefund', {id:row.id})
    ElMessage.success(`押金已退还`); loadData(); loadStats()
  } catch {}
}

// 选房间后自动带出业主
const roomHasOwner = ref(false)
function onCommunityChange(val: any) {
  console.log('[DEBUG] onCommunityChange:', val, typeof val)
  form.room_id = ''
  form.owner_id = ''
  roomHasOwner.value = false
  loadRoomsAndOwners(val)
}
function onRoomChange(roomId: number|string) {
  if (!roomId) { form.owner_id = ''; roomHasOwner.value = false; return }
  const room = rooms.value.find((r:any) => r.id == roomId)
  if (room?.owner_id) {
    form.owner_id = room.owner_id
    roomHasOwner.value = true
  } else {
    form.owner_id = ''
    roomHasOwner.value = false
  }
}

// 选择小区后加载房间和业主
async function loadRoomsAndOwners(communityId: number|string) {
  console.log('[DEBUG] loadRoomsAndOwners called with:', communityId, typeof communityId)
  if (!communityId) { rooms.value = []; owners.value = []; roomHasOwner.value = false; return }
  try {
    const [r1, r2] = await Promise.all([
      apiGet('/admin/room/select', { community_id: communityId }),
      apiGet('/admin/owner/list', { community_id: communityId, limit: 9999 }),
    ])
    console.log('[DEBUG] rooms API response:', r1)
    rooms.value = r1.data || []
    owners.value = r2.data?.list || r2.data || []
  } catch {
    rooms.value = []
    owners.value = []
  }
}

// 监听小区变化，自动加载房间和业主
watch(() => form.community_id, (newVal) => {
  form.room_id = ''
  form.owner_id = ''
  roomHasOwner.value = false
  loadRoomsAndOwners(newVal)
})

onMounted(async () => {
  try { const r = await apiGet('/admin/community/listAll'); communities.value = r.data?.list||r.data||[] } catch {}
  loadData(); loadStats()
})
</script>

<style scoped>
.stats-row { display:flex; gap:14px; margin-bottom:16px; flex-wrap:wrap; }
.stat-card { flex:1; min-width:140px; background:#fff; border-radius:10px; padding:16px 18px; border:1px solid #e2e8f0; border-top:3px solid #409eff; }
.stat-num { font-size:28px; font-weight:700; color:#1e293b; }
.stat-label { font-size:12px; color:#94a3b8; margin-top:4px; }
.search-bar { background:#fff; border-radius:8px; padding:16px 20px; margin-bottom:16px; border:1px solid #e2e8f0; }
.table-card { border-radius:8px; border:1px solid #e2e8f0; }
.table-toolbar { margin-bottom:16px; }
.pagination { margin-top:16px; display:flex; justify-content:flex-end; }
</style>
