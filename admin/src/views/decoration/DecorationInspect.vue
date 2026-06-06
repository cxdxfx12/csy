<template>
  <div class="page-container">
    <div class="search-bar">
      <el-form :model="query" inline>
        <el-form-item><el-select v-model="query.community_id" placeholder="小区" clearable style="width:160px;"><el-option v-for="c in communities" :key="c.id" :label="c.name" :value="c.id" /></el-select></el-form-item>
        <el-form-item><el-select v-model="query.apply_id" placeholder="装修申请" clearable filterable style="width:200px;"><el-option v-for="a in applyOptions" :key="a.id" :label="a.apply_no + ' - ' + a.room_number" :value="a.id" /></el-select></el-form-item>
        <el-form-item><el-select v-model="query.result" placeholder="巡查结果" clearable style="width:120px;"><el-option label="正常" :value="0" /><el-option label="异常" :value="1" /></el-select></el-form-item>
        <el-form-item>
          <el-button type="primary" @click="loadData">搜索</el-button>
          <el-button @click="resetQuery">重置</el-button>
        </el-form-item>
      </el-form>
    </div>

    <el-card shadow="never" class="table-card">
      <div class="table-toolbar">
        <el-button type="primary" @click="openAddInspect()">新增巡查</el-button>
      </div>
      <el-table :data="list" v-loading="loading" stripe border>
        <el-table-column type="index" label="#" width="50" />
        <el-table-column prop="apply_no" label="装修申请" width="160" />
        <el-table-column prop="room_number" label="房间" width="110" />
        <el-table-column prop="community_name" label="小区" width="130" />
        <el-table-column prop="inspector_name" label="巡查人" width="90" />
        <el-table-column prop="inspect_time" label="巡查时间" width="170" />
        <el-table-column prop="result" label="结果" width="80">
          <template #default="{row}"><el-tag :type="row.result?'danger':'success'" size="small">{{ row.result?'异常':'正常' }}</el-tag></template>
        </el-table-column>
        <el-table-column prop="content" label="巡查内容" show-overflow-tooltip />
        <el-table-column label="操作" width="100" fixed="right">
          <template #default="{row}">
            <el-popconfirm title="确定删除？" @confirm="handleDelete(row)">
              <template #reference><el-button size="small" type="danger">删除</el-button></template>
            </el-popconfirm>
          </template>
        </el-table-column>
      </el-table>
      <div class="pagination">
        <el-pagination v-model:current-page="query.page" v-model:page-size="query.limit" :total="total" :page-sizes="[15,30,50]" layout="total,sizes,prev,pager,next" @current-change="loadData" @size-change="loadData" />
      </div>
    </el-card>

    <!-- 新增巡查弹窗 -->
    <el-dialog v-model="dialogVisible" title="新增巡查记录" width="500px" destroy-on-close>
      <el-form :model="form" :rules="rules" ref="formRef" label-width="100px">
        <el-form-item label="装修申请" prop="apply_id">
          <el-select v-model="form.apply_id" placeholder="选择装修申请" filterable style="width:100%;">
            <el-option v-for="a in inProgressApplies" :key="a.id" :label="a.apply_no + ' - ' + a.room_number + ' (' + (a.community_name||'') + ')'" :value="a.id" />
          </el-select>
        </el-form-item>
        <el-form-item label="巡查结果" prop="result">
          <el-radio-group v-model="form.result">
            <el-radio :value="0">正常</el-radio>
            <el-radio :value="1">异常</el-radio>
          </el-radio-group>
        </el-form-item>
        <el-form-item label="巡查内容"><el-input v-model="form.content" type="textarea" :rows="3" placeholder="巡查情况描述" /></el-form-item>
        <el-form-item v-if="form.result===1" label="快速创建违规">
          <el-switch v-model="form.auto_violation" active-text="同步创建违规记录" />
        </el-form-item>
        <el-form-item v-if="form.result===1&&form.auto_violation" label="违规类型">
          <el-select v-model="form.violation_type" style="width:100%;">
            <el-option label="破坏承重结构" value="破坏承重结构" />
            <el-option label="擅自改动燃气管道" value="擅自改动燃气管道" />
            <el-option label="封堵消防通道" value="封堵消防通道" />
            <el-option label="超时施工噪音" value="超时施工噪音" />
            <el-option label="损坏公共区域" value="损坏公共区域" />
            <el-option label="违规堆放垃圾" value="违规堆放垃圾" />
            <el-option label="私改水电线路" value="私改水电线路" />
            <el-option label="其他违规" value="其他违规" />
          </el-select>
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="dialogVisible=false">取消</el-button>
        <el-button type="primary" @click="submitForm" :loading="submitting">提交巡查</el-button>
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
const submitting = ref(false)
const formRef = ref<any>(null)
const communities = ref<any[]>([])
const applyOptions = ref<any[]>([])
const inProgressApplies = ref<any[]>([])

const query = reactive({ community_id:undefined as any, apply_id:undefined as any, result:undefined as any, page:1, limit:15 })
const form = reactive({ apply_id:'', result:0, content:'', auto_violation:false, violation_type:'其他违规' })
const rules = { apply_id:[{required:true,message:'请选择装修申请',trigger:'change'}] }

function resetQuery() { query.community_id=undefined; query.apply_id=undefined; query.result=undefined; query.page=1; loadData() }

async function loadData() {
  loading.value = true
  try {
    const r = await apiGet('/admin/decoration/inspectList', {...query})
    list.value = r.data?.list || r.data || []
    total.value = r.count || 0
  } catch { list.value=[]; total.value=0 }
  finally { loading.value=false }
}

async function openAddInspect() {
  // 获取施工中的申请
  try {
    const r = await apiGet('/admin/decoration/applyList', {status:2, limit:999})
    inProgressApplies.value = r.data?.list || r.data || []
  } catch {}
  form.apply_id=''; form.result=0; form.content=''; form.auto_violation=false; form.violation_type='其他违规'
  dialogVisible.value = true
}

async function submitForm() {
  const valid = await formRef.value?.validate().catch(()=>false)
  if (!valid) return
  submitting.value = true
  try {
    await apiPost('/admin/decoration/inspectAdd', {...form})
    ElMessage.success('巡查记录已保存')
    dialogVisible.value = false
    loadData()
  } finally { submitting.value=false }
}

async function handleDelete(row: any) {
  await apiPost('/admin/decoration/inspectDelete', {id:row.id})
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
