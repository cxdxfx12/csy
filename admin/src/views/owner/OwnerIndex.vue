<template>
  <div class="page-container">
    <div class="search-bar">
      <el-form :model="query" inline>
        <el-form-item><el-input v-model="query.keyword" placeholder="姓名/手机/身份证" clearable style="width:200px;" /></el-form-item>
        <el-form-item><el-select v-model="query.community_id" placeholder="小区" clearable style="width:160px;"><el-option v-for="c in communities" :key="c.id" :label="c.name" :value="c.id" /></el-select></el-form-item>
        <el-form-item>
          <el-button type="primary" @click="loadData">搜索</el-button>
          <el-button @click="resetQuery">重置</el-button>
        </el-form-item>
      </el-form>
    </div>
    <el-card shadow="never" class="table-card">
      <div class="table-toolbar"><el-button type="primary" @click="openForm()">添加业主</el-button></div>
      <el-table :data="list" v-loading="loading" stripe border>
        <el-table-column type="index" label="#" width="50" />
        <el-table-column prop="id" label="ID" width="60" />
        <el-table-column prop="realname" label="姓名" width="120" />
        <el-table-column prop="phone" label="手机" width="130" />
        <el-table-column prop="id_card" label="身份证" width="180" />
        <el-table-column prop="gender" label="性别" width="70"><template #default="{row}">{{ row.gender===1?'男':row.gender===2?'女':'-' }}</template></el-table-column>
        <el-table-column prop="community_name" label="小区" width="140" />
        <el-table-column prop="room_count" label="房产数" width="80" />
        <el-table-column prop="create_time" label="注册时间" width="170" />
        <el-table-column label="操作" width="240" fixed="right">
          <template #default="{row}">
            <el-button size="small" @click="openForm(row)">编辑</el-button>
            <el-button size="small" type="primary" @click="showDetail(row)">详情</el-button>
            <el-button size="small" type="danger" @click="handleDelete(row)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>
      <div class="pagination">
        <el-pagination v-model:current-page="query.page" v-model:page-size="query.limit" :total="total" :page-sizes="[15,30,50,100]" layout="total,sizes,prev,pager,next" @change="loadData" />
      </div>
    </el-card>

    <el-dialog v-model="dialogVisible" :title="formTitle" width="580px" destroy-on-close>
      <el-form :model="form" :rules="rules" ref="formRef" label-width="100px">
        <el-form-item label="所属小区" prop="community_id"><el-select v-model="form.community_id" placeholder="选择小区" style="width:100%;" @change="onCommunityChange"><el-option v-for="c in communities" :key="c.id" :label="c.name" :value="c.id" /></el-select></el-form-item>
        <el-form-item label="姓名" prop="realname"><el-input v-model="form.realname" placeholder="真实姓名" /></el-form-item>
        <el-form-item label="手机号" prop="phone"><el-input v-model="form.phone" placeholder="手机号" maxlength="11" /></el-form-item>
        <el-form-item label="身份证"><el-input v-model="form.id_card" placeholder="身份证号码" maxlength="18" /></el-form-item>
        <el-form-item label="性别">
          <el-radio-group v-model="form.gender"><el-radio :label="1">男</el-radio><el-radio :label="2">女</el-radio></el-radio-group>
        </el-form-item>
        <el-form-item label="绑定房间"><el-select v-model="form.room_id" placeholder="选择房间" clearable style="width:100%;"><el-option v-for="r in rooms" :key="r.id" :label="r.building_name + ' ' + r.room_number + (r.owner_name ? ' (' + r.owner_name + ')' : '')" :value="r.id" :disabled="!!r.owner_name && r.owner_id !== form.id" /></el-select></el-form-item>
        <el-form-item label="密码" v-if="!form.id"><el-input v-model="form.password" placeholder="默认手机后6位" type="password" /></el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" @click="submitForm" :loading="submitting">确定</el-button>
      </template>
    </el-dialog>

    <el-dialog v-model="detailVisible" title="业主详情" width="700px" destroy-on-close>
      <el-descriptions :column="2" border v-if="detail">
        <el-descriptions-item label="姓名">{{ detail.realname }}</el-descriptions-item>
        <el-descriptions-item label="手机号">{{ detail.phone }}</el-descriptions-item>
        <el-descriptions-item label="身份证">{{ detail.id_card }}</el-descriptions-item>
        <el-descriptions-item label="性别">{{ detail.gender===1?'男':detail.gender===2?'女':'-' }}</el-descriptions-item>
        <el-descriptions-item label="注册时间">{{ detail.register_time }}</el-descriptions-item>
        <el-descriptions-item label="状态"><el-tag>{{ detail.status===1?'正常':'禁用' }}</el-tag></el-descriptions-item>
      </el-descriptions>
      <h4 style="margin:16px 0 8px;">房产信息</h4>
      <el-table :data="detail?.rooms||[]" size="small" border>
        <el-table-column prop="building_name" label="楼栋" width="120" />
        <el-table-column prop="room_number" label="房间号" width="120" />
        <el-table-column prop="area" label="面积" width="80" />
        <el-table-column prop="relation" label="关系" width="100" />
      </el-table>
      <h4 style="margin:16px 0 8px;">最近账单</h4>
      <el-table :data="detail?.bills||[]" size="small" border>
        <el-table-column prop="bill_no" label="账单号" width="160" />
        <el-table-column prop="charge_item_name" label="收费项目" width="120" />
        <el-table-column prop="total_amount" label="金额" width="100" />
        <el-table-column prop="status" label="状态" width="90"><template #default="{row}"><el-tag :type="row.status===3?'success':row.status===2?'warning':'info'">{{ row.status===3?'已缴':row.status===2?'部分缴纳':'待缴' }}</el-tag></template></el-table-column>
      </el-table>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { apiGet, apiPost } from '@/utils/request'

const list = ref<any[]>([])
const total = ref(0)
const loading = ref(false)
const dialogVisible = ref(false)
const detailVisible = ref(false)
const submitting = ref(false)
const formRef = ref<any>(null)
const formTitle = ref('添加业主')
const communities = ref<any[]>([])
const rooms = ref<any[]>([])
const detail = ref<any>(null)

const query = reactive({ keyword: '', community_id: undefined as any, page: 1, limit: 15 })
const form = reactive<any>({ id: 0, community_id: '', realname: '', phone: '', id_card: '', gender: 1, room_id: undefined, password: '' })
const rules = { realname: [{ required: true, message: '请输入姓名', trigger: 'blur' }], phone: [{ required: true, message: '请输入手机号', trigger: 'blur' }], community_id: [{ required: true, message: '请选择小区', trigger: 'change' }] }

function resetQuery() { query.keyword = ''; query.community_id = undefined; query.page = 1; loadData() }

async function loadData() {
  loading.value = true
  try {
    const r = await apiGet('/admin/owner/list', { ...query })
    list.value = r.data?.list || r.data || []
    total.value = r.count || r.data?.total || list.value.length
  } catch { list.value = []; total.value = 0 }
  finally { loading.value = false }
}

function openForm(row?: any) {
  formTitle.value = row ? '编辑业主' : '添加业主'
  Object.assign(form, row || { id: 0, community_id: '', realname: '', phone: '', id_card: '', gender: 1, room_id: undefined, password: '' })
  if (row?.community_id) loadRooms(row.community_id)
  dialogVisible.value = true
}

async function onCommunityChange(val: any) {
  form.room_id = undefined
  await loadRooms(val)
}

async function loadRooms(communityId: any) {
  if (!communityId) { rooms.value = []; return }
  try { const r = await apiGet('/admin/room/select', { community_id: communityId }); rooms.value = r.data || [] } catch { rooms.value = [] }
}

async function submitForm() {
  const valid = await formRef.value?.validate().catch(() => false)
  if (!valid) return
  submitting.value = true
  try {
    const url = form.id ? '/admin/owner/edit' : '/admin/owner/add'
    // 添加时 密码为空则不传（后端自动用手机后6位）
    const payload = { ...form }
    if (!form.id && !payload.password) delete payload.password
    await apiPost(url, payload)
    ElMessage.success(form.id ? '修改成功' : '添加成功')
    dialogVisible.value = false
    loadData()
  } catch {
    // 错误信息已由拦截器展示
  } finally { submitting.value = false }
}

async function showDetail(row: any) {
  try {
    const r = await apiGet('/admin/owner/detail', { id: row.id })
    detail.value = r.data
    detailVisible.value = true
  } catch {}
}

async function handleDelete(row: any) {
  try {
    await ElMessageBox.confirm(`确定删除业主 "${row.realname}" 吗？`, '提示', { type: 'warning' })
    await apiPost('/admin/owner/delete', { id: row.id })
    ElMessage.success('删除成功')
    loadData()
  } catch {}
}

onMounted(async () => {
  try { const r = await apiGet('/admin/community/list', { limit: 999 }); communities.value = r.data?.list || r.data || [] } catch {}
  loadData()
})
</script>

<style scoped>
.search-bar { background:#fff;border-radius:8px;padding:16px 20px;margin-bottom:16px;border:1px solid #e2e8f0; }
.table-card { border-radius:8px;border:1px solid #e2e8f0; }
.table-toolbar { margin-bottom:16px; }
.pagination { margin-top:16px;display:flex;justify-content:flex-end; }
</style>
