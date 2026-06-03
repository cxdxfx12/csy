<template>
  <div class="page-container">
    <div class="search-bar">
      <el-form :model="query" inline>
        <el-form-item><el-input v-model="query.keyword" placeholder="项目名称" clearable style="width:200px;" /></el-form-item>
        <el-form-item><el-select v-model="query.community_id" placeholder="小区" clearable style="width:160px;"><el-option v-for="c in communities" :key="c.id" :label="c.name" :value="c.id" /></el-select></el-form-item>
        <el-form-item>
          <el-button type="primary" @click="loadData">搜索</el-button>
          <el-button @click="resetQuery">重置</el-button>
        </el-form-item>
      </el-form>
    </div>
    <el-card shadow="never" class="table-card">
      <div class="table-toolbar"><el-button type="primary" @click="openForm()">添加收费项目</el-button></div>
      <el-table :data="list" v-loading="loading" stripe border>
        <el-table-column type="index" label="#" width="50" />
        <el-table-column prop="id" label="ID" width="60" />
        <el-table-column prop="name" label="项目名称" width="150" />
        <el-table-column prop="community_name" label="所属小区" width="140" />
        <el-table-column prop="type_name" label="类型" width="100"><template #default="{row}">{{ typeMap[row.type]||row.type }}</template></el-table-column>
        <el-table-column prop="unit_price" label="单价" width="100"><template #default="{row}">¥{{ row.unit_price }}</template></el-table-column>
        <el-table-column prop="unit" label="单位" width="80" />
        <el-table-column prop="billing_mode" label="计费方式" width="100"><template #default="{row}">{{ billingMap[row.billing_mode]||row.billing_mode }}</template></el-table-column>
        <el-table-column prop="cycle" label="周期" width="80"><template #default="{row}">{{ cycleMap[row.cycle]||row.cycle }}</template></el-table-column>
        <el-table-column prop="status" label="状态" width="80"><template #default="{row}"><el-tag :type="row.status===1?'success':'danger'">{{ row.status===1?'启用':'禁用' }}</el-tag></template></el-table-column>
        <el-table-column label="操作" width="200" fixed="right">
          <template #default="{row}">
            <el-button size="small" @click="openForm(row)">编辑</el-button>
            <el-button size="small" type="danger" @click="handleDelete(row)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>
      <div class="pagination">
        <el-pagination v-model:current-page="query.page" v-model:page-size="query.limit" :total="total" :page-sizes="[15,30,50,100]" layout="total,sizes,prev,pager,next" @change="loadData" />
      </div>
    </el-card>

    <el-dialog v-model="dialogVisible" :title="formTitle" width="560px" destroy-on-close>
      <el-form :model="form" :rules="rules" ref="formRef" label-width="100px">
        <el-form-item label="所属小区" prop="community_id"><el-select v-model="form.community_id" placeholder="选择小区" style="width:100%;"><el-option v-for="c in communities" :key="c.id" :label="c.name" :value="c.id" /></el-select></el-form-item>
        <el-form-item label="项目名称" prop="name"><el-input v-model="form.name" placeholder="如：物业费" /></el-form-item>
        <el-form-item label="类型" prop="type">
          <el-select v-model="form.type" placeholder="选择类型" style="width:100%;">
            <el-option label="物业费" :value="1" /><el-option label="水费" :value="2" /><el-option label="电费" :value="3" />
            <el-option label="燃气费" :value="4" /><el-option label="停车费" :value="5" /><el-option label="其他" :value="6" />
          </el-select>
        </el-form-item>
        <el-form-item label="计费方式" prop="billing_mode">
          <el-select v-model="form.billing_mode" placeholder="选择计费方式" style="width:100%;">
            <el-option label="按面积" :value="1" /><el-option label="按户" :value="2" /><el-option label="按用量" :value="3" />
            <el-option label="按人头" :value="4" /><el-option label="固定金额" :value="5" />
          </el-select>
        </el-form-item>
        <el-form-item label="单价" prop="unit_price"><el-input-number v-model="form.unit_price" :min="0" :precision="2" style="width:100%;" /></el-form-item>
        <el-form-item label="单位"><el-input v-model="form.unit" placeholder="如：元/㎡" /></el-form-item>
        <el-form-item label="收费周期" prop="cycle">
          <el-select v-model="form.cycle" placeholder="选择周期" style="width:100%;">
            <el-option label="月" :value="1" /><el-option label="季" :value="2" /><el-option label="半年" :value="3" /><el-option label="年" :value="4" />
          </el-select>
        </el-form-item>
        <el-form-item label="排序"><el-input-number v-model="form.sort" :min="0" style="width:100%;" /></el-form-item>
        <el-form-item label="状态">
          <el-radio-group v-model="form.status"><el-radio :label="1">启用</el-radio><el-radio :label="0">禁用</el-radio></el-radio-group>
        </el-form-item>
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
const total = ref(0)
const loading = ref(false)
const dialogVisible = ref(false)
const submitting = ref(false)
const formRef = ref<any>(null)
const formTitle = ref('添加收费项目')
const communities = ref<any[]>([])

const typeMap: Record<number, string> = { 1: '物业费', 2: '水费', 3: '电费', 4: '燃气费', 5: '停车费', 6: '其他' }
const billingMap: Record<number, string> = { 1: '按面积', 2: '按户', 3: '按用量', 4: '按人头', 5: '固定金额' }
const cycleMap: Record<number, string> = { 1: '月', 2: '季', 3: '半年', 4: '年' }

const query = reactive({ keyword: '', community_id: undefined as any, page: 1, limit: 15 })
const form = reactive<any>({ id: 0, community_id: '', name: '', type: 1, billing_mode: 1, unit_price: 0, unit: '', cycle: 1, sort: 0, status: 1 })
const rules = { name: [{ required: true, message: '请输入项目名称', trigger: 'blur' }], community_id: [{ required: true, message: '请选择小区', trigger: 'change' }], type: [{ required: true, message: '请选择类型', trigger: 'change' }], billing_mode: [{ required: true, message: '请选择计费方式', trigger: 'change' }], unit_price: [{ required: true, message: '请输入单价', trigger: 'blur' }] }

function resetQuery() { query.keyword = ''; query.community_id = undefined; query.page = 1; loadData() }

async function loadData() {
  loading.value = true
  try {
    const r = await apiGet('/admin/charge/itemList', { ...query })
    list.value = r.data?.list || r.data || []
    total.value = r.count || r.data?.total || list.value.length
  } catch { list.value = []; total.value = 0 }
  finally { loading.value = false }
}

function openForm(row?: any) {
  formTitle.value = row ? '编辑收费项目' : '添加收费项目'
  Object.assign(form, row || { id: 0, community_id: '', name: '', type: 1, billing_mode: 1, unit_price: 0, unit: '', cycle: 1, sort: 0, status: 1 })
  dialogVisible.value = true
}

async function submitForm() {
  const valid = await formRef.value?.validate().catch(() => false)
  if (!valid) return
  submitting.value = true
  try {
    const url = form.id ? '/admin/charge/itemEdit' : '/admin/charge/itemAdd'
    await apiPost(url, { ...form })
    ElMessage.success(form.id ? '修改成功' : '添加成功')
    dialogVisible.value = false
    loadData()
  } finally { submitting.value = false }
}

async function handleDelete(row: any) {
  try {
    await ElMessageBox.confirm(`确定删除收费项目 "${row.name}" 吗？`, '提示', { type: 'warning' })
    await apiPost('/admin/charge/itemDelete', { id: row.id })
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
