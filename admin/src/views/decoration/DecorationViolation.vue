<template>
  <div class="page-container">
    <div class="search-bar">
      <el-form :model="query" inline>
        <el-form-item><el-select v-model="query.community_id" placeholder="小区" clearable style="width:160px;"><el-option v-for="c in communities" :key="c.id" :label="c.name" :value="c.id" /></el-select></el-form-item>
        <el-form-item><el-select v-model="query.apply_id" placeholder="装修申请" clearable filterable style="width:200px;"><el-option v-for="a in applyOptions" :key="a.id" :label="a.apply_no + ' - ' + a.room_number" :value="a.id" /></el-select></el-form-item>
        <el-form-item><el-select v-model="query.status" placeholder="状态" clearable style="width:120px;"><el-option label="待整改" :value="0" /><el-option label="已整改" :value="1" /><el-option label="已扣款" :value="2" /></el-select></el-form-item>
        <el-form-item><el-select v-model="query.violation_type" placeholder="违规类型" clearable style="width:140px;"><el-option v-for="t in violationTypes" :key="t" :label="t" :value="t" /></el-select></el-form-item>
        <el-form-item>
          <el-button type="primary" @click="loadData">搜索</el-button>
          <el-button @click="resetQuery">重置</el-button>
        </el-form-item>
      </el-form>
    </div>

    <el-card shadow="never" class="table-card">
      <div class="table-toolbar">
        <el-button type="primary" @click="openAddForm()">登记违规</el-button>
      </div>
      <el-table :data="list" v-loading="loading" stripe border>
        <el-table-column type="index" label="#" width="50" />
        <el-table-column prop="apply_no" label="装修申请" width="160" />
        <el-table-column prop="room_number" label="房间" width="110" />
        <el-table-column prop="community_name" label="小区" width="130" />
        <el-table-column prop="violation_type" label="违规类型" width="130">
          <template #default="{row}"><el-tag type="danger" size="small">{{ row.violation_type }}</el-tag></template>
        </el-table-column>
        <el-table-column prop="description" label="描述" show-overflow-tooltip min-width="180" />
        <el-table-column prop="penalty_amount" label="罚金" width="100">
          <template #default="{row}">¥{{ Number(row.penalty_amount||0).toFixed(2) }}</template>
        </el-table-column>
        <el-table-column prop="rectify_deadline" label="整改截止" width="110" />
        <el-table-column prop="status_name" label="状态" width="90">
          <template #default="{row}"><el-tag :type="row.status===1?'success':row.status===2?'danger':'warning'" size="small">{{ row.status_name }}</el-tag></template>
        </el-table-column>
        <el-table-column prop="create_time" label="登记时间" width="170" />
        <el-table-column label="操作" width="160" fixed="right">
          <template #default="{row}">
            <el-button v-if="row.status===0" size="small" type="success" @click="rectifyViolation(row)">整改处理</el-button>
            <el-popconfirm v-if="row.status!==2" title="确定删除？" @confirm="handleDelete(row)">
              <template #reference><el-button size="small" type="danger">删除</el-button></template>
            </el-popconfirm>
          </template>
        </el-table-column>
      </el-table>
      <div class="pagination">
        <el-pagination v-model:current-page="query.page" v-model:page-size="query.limit" :total="total" :page-sizes="[15,30,50]" layout="total,sizes,prev,pager,next" @current-change="loadData" @size-change="loadData" />
      </div>
    </el-card>

    <!-- 登记违规弹窗 -->
    <el-dialog v-model="dialogVisible" title="登记违规" width="520px" destroy-on-close>
      <el-form :model="form" :rules="rules" ref="formRef" label-width="100px">
        <el-form-item label="装修申请" prop="apply_id">
          <el-select v-model="form.apply_id" placeholder="选择装修申请" filterable style="width:100%;">
            <el-option v-for="a in inProgressApplies" :key="a.id" :label="a.apply_no + ' - ' + a.room_number" :value="a.id" />
          </el-select>
        </el-form-item>
        <el-form-item label="违规类型" prop="violation_type">
          <el-select v-model="form.violation_type" style="width:100%;">
            <el-option v-for="t in violationTypes" :key="t" :label="t" :value="t" />
          </el-select>
        </el-form-item>
        <el-form-item label="违规描述" prop="description"><el-input v-model="form.description" type="textarea" :rows="3" placeholder="详细描述违规情况" /></el-form-item>
        <el-form-item label="整改截止"><el-date-picker v-model="form.rectify_deadline" type="date" placeholder="整改截止日期" style="width:100%;" value-format="YYYY-MM-DD" /></el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="dialogVisible=false">取消</el-button>
        <el-button type="primary" @click="submitForm" :loading="submitting">登记</el-button>
      </template>
    </el-dialog>

    <!-- 整改处理弹窗 -->
    <el-dialog v-model="rectifyVisible" title="整改处理" width="480px" destroy-on-close>
      <el-form :model="rectifyForm" label-width="100px">
        <el-form-item label="整改结果"><el-input v-model="rectifyForm.rectify_result" type="textarea" :rows="2" placeholder="整改结果说明" /></el-form-item>
        <el-form-item label="是否扣款"><el-switch v-model="rectifyForm.do_penalty" /></el-form-item>
        <el-form-item v-if="rectifyForm.do_penalty" label="罚金金额"><el-input-number v-model="rectifyForm.penalty_amount" :min="0" :precision="2" style="width:200px;" /></el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="rectifyVisible=false">取消</el-button>
        <el-button type="primary" @click="doRectify" :loading="submitting">确认</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { apiGet, apiPost } from '@/utils/request'

const list = ref<any[]>([])
const total = ref(0)
const loading = ref(false)
const dialogVisible = ref(false)
const rectifyVisible = ref(false)
const submitting = ref(false)
const formRef = ref<any>(null)
const communities = ref<any[]>([])
const applyOptions = ref<any[]>([])
const inProgressApplies = ref<any[]>([])

const violationTypes = ['破坏承重结构','擅自改动燃气管道','封堵消防通道','超时施工噪音','损坏公共区域','违规堆放垃圾','私改水电线路','改变外立面','夜间施工','其他违规']

const query = reactive({ community_id:undefined as any, apply_id:undefined as any, status:undefined as any, violation_type:'', page:1, limit:15 })
const form = reactive({ apply_id:'', violation_type:'破坏承重结构', description:'', rectify_deadline:'' })
const rectifyForm = reactive({ id:0, rectify_result:'', do_penalty:false, penalty_amount:0 })
const rules = { apply_id:[{required:true,message:'请选择装修申请',trigger:'change'}], violation_type:[{required:true,message:'请选择违规类型',trigger:'change'}], description:[{required:true,message:'请输入违规描述',trigger:'blur'}] }

function resetQuery() { query.community_id=undefined; query.apply_id=undefined; query.status=undefined; query.violation_type=''; query.page=1; loadData() }

async function loadData() {
  loading.value = true
  try {
    const r = await apiGet('/admin/decoration/violationList', {...query})
    list.value = r.data?.list || r.data || []
    total.value = r.count || 0
  } catch { list.value=[]; total.value=0 }
  finally { loading.value=false }
}

async function openAddForm() {
  try {
    const r = await apiGet('/admin/decoration/applyList', {status:2, limit:999})
    inProgressApplies.value = r.data?.list || r.data || []
  } catch {}
  form.apply_id=''; form.violation_type='破坏承重结构'; form.description=''; form.rectify_deadline=''
  dialogVisible.value = true
}

async function submitForm() {
  const valid = await formRef.value?.validate().catch(()=>false)
  if (!valid) return
  submitting.value = true
  try {
    await apiPost('/admin/decoration/violationAdd', {...form})
    ElMessage.success('违规已登记'); dialogVisible.value = false; loadData()
  } finally { submitting.value=false }
}

function rectifyViolation(row: any) {
  rectifyForm.id = row.id; rectifyForm.rectify_result=''; rectifyForm.do_penalty=false; rectifyForm.penalty_amount=0
  rectifyVisible.value = true
}

async function doRectify() {
  submitting.value = true
  try {
    await apiPost('/admin/decoration/violationRectify', {...rectifyForm})
    ElMessage.success('处理完成'); rectifyVisible.value = false; loadData()
  } finally { submitting.value=false }
}

async function handleDelete(row: any) {
  await apiPost('/admin/decoration/violationDelete', {id:row.id})
  ElMessage.success('删除成功'); loadData()
}

onMounted(async () => {
  try { const r = await apiGet('/admin/community/listAll'); communities.value = r.data?.list||r.data||[] } catch {}
  try { const r = await apiGet('/admin/decoration/applyList', {limit:999}); applyOptions.value = r.data?.list||r.data||[] } catch {}
  loadData()
})
</script>

<style scoped>
.search-bar { background:#fff; border-radius:8px; padding:16px 20px; margin-bottom:16px; border:1px solid #e2e8f0; }
.table-card { border-radius:8px; border:1px solid #e2e8f0; }
.table-toolbar { margin-bottom:16px; }
.pagination { margin-top:16px; display:flex; justify-content:flex-end; }
</style>
