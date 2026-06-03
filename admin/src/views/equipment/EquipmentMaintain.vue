<template>
  <div class="page-container">
    <div class="search-bar">
      <el-form :model="query" inline>
        <el-form-item><el-input v-model="query.keyword" placeholder="设备名称" clearable style="width:200px;" /></el-form-item>
        <el-form-item><el-select v-model="query.equipment_id" placeholder="设备" clearable style="width:180px;"><el-option v-for="e in equipments" :key="e.id" :label="e.name" :value="e.id" /></el-select></el-form-item>
        <el-form-item>
          <el-button type="primary" @click="loadData">搜索</el-button>
          <el-button @click="resetQuery">重置</el-button>
        </el-form-item>
      </el-form>
    </div>
    <el-card shadow="never" class="table-card">
      <div class="table-toolbar"><el-button type="primary" @click="openForm()">添加维保记录</el-button></div>
      <el-table :data="list" v-loading="loading" stripe border>
        <el-table-column type="index" label="#" width="50" />
        <el-table-column prop="id" label="ID" width="60" />
        <el-table-column prop="equipment_name" label="设备名称" width="150" />
        <el-table-column prop="equipment_code" label="设备编码" width="120" />
        <el-table-column prop="community_name" label="小区" width="120" />
        <el-table-column prop="maintain_date" label="维保日期" width="110" />
        <el-table-column prop="type" label="维保类型" width="100"><template #default="{row}">{{ typeMap[row.type]||row.type }}</template></el-table-column>
        <el-table-column prop="cost" label="费用" width="100"><template #default="{row}">¥{{ row.cost||0 }}</template></el-table-column>
        <el-table-column prop="maintainer" label="维保人员" width="120" />
        <el-table-column prop="next_maintain_date" label="下次维保" width="110" />
        <el-table-column prop="create_time" label="创建时间" width="170" />
        <el-table-column label="操作" width="200" fixed="right">
          <template #default="{row}">
            <el-button size="small" type="danger" @click="handleDelete(row)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>
      <div class="pagination">
        <el-pagination v-model:current-page="query.page" v-model:page-size="query.limit" :total="total" :page-sizes="[15,30,50,100]" layout="total,sizes,prev,pager,next" @change="loadData" />
      </div>
    </el-card>

    <el-dialog v-model="dialogVisible" title="添加维保记录" width="560px" destroy-on-close>
      <el-form :model="form" :rules="rules" ref="formRef" label-width="100px">
        <el-form-item label="设备" prop="equipment_id"><el-select v-model="form.equipment_id" placeholder="选择设备" style="width:100%;"><el-option v-for="e in equipments" :key="e.id" :label="e.name" :value="e.id" /></el-select></el-form-item>
        <el-form-item label="维保日期" prop="maintain_date"><el-date-picker v-model="form.maintain_date" type="date" placeholder="选择日期" style="width:100%;" value-format="YYYY-MM-DD" /></el-form-item>
        <el-form-item label="维保类型">
          <el-select v-model="form.type" style="width:100%;"><el-option label="日常巡检" :value="1" /><el-option label="定期维保" :value="2" /><el-option label="故障维修" :value="3" /><el-option label="紧急抢修" :value="4" /></el-select>
        </el-form-item>
        <el-form-item label="维保内容"><el-input v-model="form.content" type="textarea" rows="3" placeholder="维保具体内容" /></el-form-item>
        <el-form-item label="维保人员"><el-input v-model="form.maintainer" placeholder="维保人员姓名" /></el-form-item>
        <el-form-item label="维保公司"><el-input v-model="form.maintain_company" placeholder="维保公司名称" /></el-form-item>
        <el-form-item label="费用"><el-input-number v-model="form.cost" :min="0" :precision="2" style="width:100%;" /></el-form-item>
        <el-form-item label="下次维保"><el-date-picker v-model="form.next_maintain_date" type="date" placeholder="选择日期" style="width:100%;" value-format="YYYY-MM-DD" /></el-form-item>
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
const equipments = ref<any[]>([])

const typeMap: Record<number, string> = { 1: '日常巡检', 2: '定期维保', 3: '故障维修', 4: '紧急抢修' }

const query = reactive({ keyword: '', equipment_id: undefined as any, page: 1, limit: 15 })
const form = reactive<any>({ equipment_id: '', maintain_date: '', type: 1, content: '', maintainer: '', maintain_company: '', cost: 0, next_maintain_date: '' })
const rules = { equipment_id: [{ required: true, message: '请选择设备', trigger: 'change' }], maintain_date: [{ required: true, message: '请选择维保日期', trigger: 'change' }] }

function resetQuery() { query.keyword = ''; query.equipment_id = undefined; query.page = 1; loadData() }

async function loadData() {
  loading.value = true
  try {
    const r = await apiGet('/admin/equipment/maintainList', { ...query })
    list.value = r.data?.list || r.data || []
    total.value = r.count || r.data?.total || list.value.length
  } catch { list.value = []; total.value = 0 }
  finally { loading.value = false }
}

function openForm() {
  Object.assign(form, { equipment_id: '', maintain_date: '', type: 1, content: '', maintainer: '', maintain_company: '', cost: 0, next_maintain_date: '' })
  dialogVisible.value = true
}

async function submitForm() {
  const valid = await formRef.value?.validate().catch(() => false)
  if (!valid) return
  submitting.value = true
  try {
    await apiPost('/admin/equipment/maintainAdd', { ...form })
    ElMessage.success('添加成功')
    dialogVisible.value = false
    loadData()
  } finally { submitting.value = false }
}

async function handleDelete(row: any) {
  try {
    await ElMessageBox.confirm('确定删除该维保记录吗？', '提示', { type: 'warning' })
    await apiPost('/admin/equipment/maintainDelete', { id: row.id })
    ElMessage.success('删除成功')
    loadData()
  } catch {}
}

onMounted(async () => {
  try {
    const re = await apiGet('/admin/equipment/list', { limit: 999 })
    equipments.value = re.data?.list || re.data || []
  } catch {}
  loadData()
})
</script>

<style scoped>
.search-bar { background:#fff;border-radius:8px;padding:16px 20px;margin-bottom:16px;border:1px solid #e2e8f0; }
.table-card { border-radius:8px;border:1px solid #e2e8f0; }
.table-toolbar { margin-bottom:16px; }
.pagination { margin-top:16px;display:flex;justify-content:flex-end; }
</style>
