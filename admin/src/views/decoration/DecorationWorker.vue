<template>
  <div class="page-container">
    <div class="search-bar">
      <el-form :model="query" inline>
        <el-form-item><el-input v-model="query.keyword" placeholder="姓名/电话/身份证/出入证号" clearable style="width:240px;" /></el-form-item>
        <el-form-item><el-select v-model="query.apply_id" placeholder="装修申请" clearable filterable style="width:200px;"><el-option v-for="a in applyOptions" :key="a.id" :label="a.apply_no + ' - ' + a.room_number" :value="a.id" /></el-select></el-form-item>
        <el-form-item>
          <el-button type="primary" @click="loadData">搜索</el-button>
          <el-button @click="resetQuery">重置</el-button>
        </el-form-item>
      </el-form>
    </div>

    <el-card shadow="never" class="table-card">
      <div class="table-toolbar"><el-button type="primary" @click="openForm()">添加人员</el-button></div>
      <el-table :data="list" v-loading="loading" stripe border>
        <el-table-column type="index" label="#" width="50" />
        <el-table-column prop="apply_no" label="装修申请" width="160" />
        <el-table-column prop="room_number" label="房间" width="110" />
        <el-table-column prop="name" label="姓名" width="90" />
        <el-table-column prop="job_type" label="工种" width="90" />
        <el-table-column prop="phone" label="电话" width="130" />
        <el-table-column prop="id_card" label="身份证号" width="180" />
        <el-table-column prop="card_no" label="出入证号" width="130" />
        <el-table-column prop="card_expire_date" label="有效期至" width="110">
          <template #default="{row}">
            <span v-if="row.card_expire_date" :style="{color: new Date(row.card_expire_date)<new Date()?'#f56c6c':''}">{{ row.card_expire_date }}</span>
            <el-tag v-else type="info" size="small">未发证</el-tag>
          </template>
        </el-table-column>
        <el-table-column label="操作" width="200" fixed="right">
          <template #default="{row}">
            <el-button size="small" @click="openForm(row)">编辑</el-button>
            <el-button v-if="!row.card_no" size="small" type="success" @click="issueCard(row)">发证</el-button>
            <el-popconfirm title="确定删除？" @confirm="handleDelete(row)"><template #reference><el-button size="small" type="danger">删除</el-button></template></el-popconfirm>
          </template>
        </el-table-column>
      </el-table>
      <div class="pagination">
        <el-pagination v-model:current-page="query.page" v-model:page-size="query.limit" :total="total" :page-sizes="[15,30,50]" layout="total,sizes,prev,pager,next" @current-change="loadData" @size-change="loadData" />
      </div>
    </el-card>

    <!-- 添加/编辑弹窗 -->
    <el-dialog v-model="dialogVisible" :title="formTitle" width="500px" destroy-on-close>
      <el-form :model="form" :rules="rules" ref="formRef" label-width="100px">
        <el-form-item label="装修申请" prop="apply_id">
          <el-select v-model="form.apply_id" placeholder="选择装修申请" filterable style="width:100%;">
            <el-option v-for="a in applyOptions" :key="a.id" :label="a.apply_no + ' - ' + a.room_number" :value="a.id" />
          </el-select>
        </el-form-item>
        <el-form-item label="姓名" prop="name"><el-input v-model="form.name" placeholder="施工人员姓名" /></el-form-item>
        <el-row :gutter="16">
          <el-col :span="12"><el-form-item label="电话"><el-input v-model="form.phone" placeholder="手机号" /></el-form-item></el-col>
          <el-col :span="12"><el-form-item label="工种"><el-select v-model="form.job_type" style="width:100%;"><el-option v-for="t in jobTypes" :key="t" :label="t" :value="t" /></el-select></el-form-item></el-col>
        </el-row>
        <el-form-item label="身份证号"><el-input v-model="form.id_card" placeholder="18位身份证号" maxlength="18" /></el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="dialogVisible=false">取消</el-button>
        <el-button type="primary" @click="submitForm" :loading="submitting">确定</el-button>
      </template>
    </el-dialog>

    <!-- 发证弹窗 -->
    <el-dialog v-model="cardVisible" title="发放出入证" width="420px" destroy-on-close>
      <el-form :model="cardForm" label-width="100px">
        <el-form-item label="人员">{{ cardForm.worker_name }}</el-form-item>
        <el-form-item label="出入证号"><el-input v-model="cardForm.card_no" placeholder="出入证编号" /></el-form-item>
        <el-form-item label="有效期至"><el-date-picker v-model="cardForm.card_expire_date" type="date" placeholder="有效期" style="width:100%;" value-format="YYYY-MM-DD" /></el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="cardVisible=false">取消</el-button>
        <el-button type="primary" @click="doIssueCard" :loading="submitting">确认发证</el-button>
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
const cardVisible = ref(false)
const submitting = ref(false)
const formRef = ref<any>(null)
const formTitle = ref('添加施工人员')
const applyOptions = ref<any[]>([])

const jobTypes = ['水电工','木工','瓦工','油漆工','杂工','安装工','监工','其他']

const query = reactive({ keyword:'', apply_id:undefined as any, page:1, limit:15 })
const form = reactive({ id:0, apply_id:'', name:'', phone:'', job_type:'水电工', id_card:'' })
const cardForm = reactive({ id:0, worker_name:'', card_no:'', card_expire_date:'' })
const rules = { apply_id:[{required:true,message:'请选择装修申请',trigger:'change'}], name:[{required:true,message:'请输入姓名',trigger:'blur'}] }

function resetQuery() { query.keyword=''; query.apply_id=undefined; query.page=1; loadData() }

async function loadData() {
  loading.value = true
  try {
    const r = await apiGet('/admin/decoration/workerList', {...query})
    list.value = r.data?.list || r.data || []
    total.value = r.count || 0
  } catch { list.value=[]; total.value=0 }
  finally { loading.value=false }
}

function openForm(row?: any) {
  formTitle.value = row ? '编辑施工人员' : '添加施工人员'
  Object.assign(form, row ? {...row} : {id:0, apply_id:'', name:'', phone:'', job_type:'水电工', id_card:''})
  dialogVisible.value = true
}

async function submitForm() {
  const valid = await formRef.value?.validate().catch(()=>false)
  if (!valid) return
  submitting.value = true
  try {
    const url = form.id ? '/admin/decoration/workerEdit' : '/admin/decoration/workerAdd'
    await apiPost(url, {...form})
    ElMessage.success(form.id?'修改成功':'添加成功'); dialogVisible.value = false; loadData()
  } finally { submitting.value=false }
}

function issueCard(row: any) {
  cardForm.id = row.id; cardForm.worker_name = row.name
  cardForm.card_no = 'DZ' + Date.now().toString().slice(-6)
  cardForm.card_expire_date = ''
  cardVisible.value = true
}

async function doIssueCard() {
  if (!cardForm.card_no) { ElMessage.warning('请输入出入证号'); return }
  submitting.value = true
  try {
    await apiPost('/admin/decoration/workerIssueCard', {...cardForm})
    ElMessage.success('发证成功'); cardVisible.value = false; loadData()
  } finally { submitting.value=false }
}

async function handleDelete(row: any) {
  await apiPost('/admin/decoration/workerDelete', {id:row.id})
  ElMessage.success('删除成功'); loadData()
}

onMounted(async () => {
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
