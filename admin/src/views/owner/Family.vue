<template>
  <div class="page-container">
    <div class="search-bar">
      <el-form :model="query" inline>
        <el-form-item><el-select v-model="query.owner_id" placeholder="选择业主" clearable filterable style="width:200px;"><el-option v-for="o in owners" :key="o.id" :label="o.realname + ' - ' + o.phone" :value="o.id" /></el-select></el-form-item>
        <el-form-item>
          <el-button type="primary" @click="loadData">搜索</el-button>
          <el-button @click="resetQuery">重置</el-button>
        </el-form-item>
      </el-form>
    </div>
    <el-card shadow="never" class="table-card">
      <div class="table-toolbar"><el-button type="primary" @click="openForm()">添加家庭成员</el-button></div>
      <el-table :data="list" v-loading="loading" stripe border>
        <el-table-column type="index" label="#" width="50" />
        <el-table-column prop="id" label="ID" width="60" />
        <el-table-column prop="realname" label="姓名" width="100" />
        <el-table-column prop="owner_name" label="所属业主" width="100" />
        <el-table-column prop="relation" label="关系" width="80" />
        <el-table-column prop="gender" label="性别" width="60"><template #default="{row}">{{ row.gender===1?'男':row.gender===2?'女':'-' }}</template></el-table-column>
        <el-table-column prop="phone" label="手机" width="130" />
        <el-table-column prop="id_card" label="身份证" width="180" />
        <el-table-column prop="room_number" label="关联房间" width="120" />
        <el-table-column label="微信" width="90"><template #default="{row}"><el-tag v-if="row.wx_bound" type="success" size="small">已绑定</el-tag><span v-else class="text-gray">未绑定</span></template></el-table-column>
        <el-table-column prop="remark" label="备注" min-width="120" show-overflow-tooltip />
        <el-table-column prop="create_time" label="创建时间" width="170" />
        <el-table-column label="操作" width="270" fixed="right">
          <template #default="{row}">
            <el-button size="small" @click="openForm(row)">编辑</el-button>
            <el-button size="small" type="danger" @click="handleDelete(row)">删除</el-button>
            <el-button v-if="row.wx_bound" size="small" type="warning" @click="handleUnbindWechat(row)">解绑微信</el-button>
          </template>
        </el-table-column>
      </el-table>
      <div class="pagination">
        <el-pagination v-model:current-page="query.page" v-model:page-size="query.limit" :total="total" :page-sizes="[15,30,50,100]" layout="total,sizes,prev,pager,next" @change="loadData" />
      </div>
    </el-card>

    <el-dialog v-model="dialogVisible" :title="formTitle" width="500px" destroy-on-close>
      <el-form :model="form" :rules="rules" ref="formRef" label-width="100px">
        <el-form-item label="所属业主" prop="owner_id">
          <el-select v-model="form.owner_id" placeholder="选择业主" filterable style="width:100%;" @change="onOwnerChange"><el-option v-for="o in owners" :key="o.id" :label="o.realname + ' - ' + o.phone" :value="o.id" /></el-select>
        </el-form-item>
        <el-form-item label="关联房间" prop="room_id"><el-select v-model="form.room_id" placeholder="选择房间" clearable filterable style="width:100%;"><el-option v-for="r in rooms" :key="r.id" :label="r.building_name + ' ' + r.room_number" :value="r.id" /></el-select></el-form-item>
        <el-form-item label="姓名" prop="realname"><el-input v-model="form.realname" placeholder="姓名" /></el-form-item>
        <el-form-item label="关系" prop="relation"><el-select v-model="form.relation" placeholder="选择关系" style="width:100%;"><el-option label="配偶" value="配偶" /><el-option label="子女" value="子女" /><el-option label="父母" value="父母" /><el-option label="兄弟姐妹" value="兄弟姐妹" /><el-option label="其他" value="其他" /></el-select></el-form-item>
        <el-form-item label="性别"><el-radio-group v-model="form.gender"><el-radio :label="1">男</el-radio><el-radio :label="2">女</el-radio></el-radio-group></el-form-item>
        <el-form-item label="手机号"><el-input v-model="form.phone" placeholder="手机号" maxlength="11" /></el-form-item>
        <el-form-item label="身份证"><el-input v-model="form.id_card" placeholder="身份证号码" maxlength="18" /></el-form-item>
        <el-form-item label="备注"><el-input v-model="form.remark" placeholder="备注" type="textarea" /></el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" @click="submitForm" :loading="submitting">确定</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { apiGet, apiPost } from '@/utils/request'

const list = ref<any[]>([])
const loading = ref(false)
const total = ref(0)
const query = reactive<any>({ page: 1, limit: 15, owner_id: undefined })
const owners = ref<any[]>([])
const rooms = ref<any[]>([])
const dialogVisible = ref(false)
const formTitle = ref('添加家庭成员')
const submitting = ref(false)
const formRef = ref<any>(null)
const form = reactive<any>({
  id: 0, owner_id: undefined, room_id: undefined, realname: '', relation: '', gender: 1, phone: '', id_card: '', remark: '',
})

const rules = {
  owner_id: [{ required: true, message: '请选择业主', trigger: 'change' }],
  realname: [{ required: true, message: '请输入姓名', trigger: 'blur' }],
  relation: [{ required: true, message: '请选择关系', trigger: 'change' }],
}

async function loadOwners() {
  try { const r = await apiGet('/admin/owner/list', { limit: 999 }); owners.value = r.data?.list || r.data || [] } catch {}
}

async function loadRooms(ownerId?: number) {
  try {
    const r = await apiGet('/admin/room/select', ownerId ? { owner_id: ownerId } : undefined)
    rooms.value = r.data || []
  } catch {}
}

async function loadData() {
  loading.value = true
  try {
    const params: any = { page: query.page, limit: query.limit }
    if (query.owner_id) params.owner_id = query.owner_id
    const r = await apiGet('/admin/owner/familyList', params)
    list.value = r.data || []
    total.value = r.count || 0
  } catch { list.value = []; total.value = 0 }
  finally { loading.value = false }
}

function resetQuery() { query.owner_id = undefined; query.page = 1; loadData() }

function openForm(row?: any) {
  if (row) {
    formTitle.value = '编辑家庭成员'
    Object.assign(form, { ...row, community_id: undefined })
  } else {
    formTitle.value = '添加家庭成员'
    Object.assign(form, { id: 0, owner_id: undefined, room_id: undefined, realname: '', relation: '', gender: 1, phone: '', id_card: '', remark: '' })
  }
  dialogVisible.value = true
  if (form.owner_id) onOwnerChange(form.owner_id)
}

async function onOwnerChange(val: number) {
  rooms.value = []
  if (val) await loadRooms(val)
}

async function submitForm() {
  const valid = await formRef.value?.validate().catch(() => false)
  if (!valid) return
  submitting.value = true
  try {
    const url = form.id ? '/admin/owner/familyEdit' : '/admin/owner/familyAdd'
    await apiPost(url, { ...form })
    ElMessage.success(form.id ? '修改成功' : '添加成功')
    dialogVisible.value = false
    loadData()
  } catch {} finally { submitting.value = false }
}

async function handleDelete(row: any) {
  try {
    await ElMessageBox.confirm(`确定删除 "${row.realname}" 吗？`, '确认删除', { type: 'warning' })
    await apiPost('/admin/owner/familyDelete', { id: row.id })
    ElMessage.success('删除成功')
    loadData()
  } catch {}
}

async function handleUnbindWechat(row: any) {
  try {
    await ElMessageBox.confirm(`确定解绑 "${row.realname}" 的微信吗？解绑后将无法通过微信接收通知。`, '确认解绑', { type: 'warning' })
    await apiPost('/admin/owner/familyUnbindWechat', { id: row.id })
    ElMessage.success('微信已解绑')
    loadData()
  } catch {}
}

onMounted(() => { loadOwners(); loadData() })
</script>

<style scoped>
.page-container { padding: 4px 0; }
.search-bar { margin-bottom: 12px; }
.table-card { margin-bottom: 0; }
.table-toolbar { margin-bottom: 12px; }
.pagination { display: flex; justify-content: flex-end; margin-top: 12px; }
.text-gray { color: #999; }
</style>
