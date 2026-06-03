<template>
  <div class="page-container">
    <div class="search-bar">
      <el-form :model="query" inline>
        <el-form-item><el-input v-model="query.keyword" placeholder="名称/编码" clearable style="width:200px;" /></el-form-item>
        <el-form-item><el-select v-model="query.community_id" placeholder="选择小区" clearable style="width:180px;"><el-option v-for="c in communities" :key="c.id" :label="c.name" :value="c.id" /></el-select></el-form-item>
        <el-form-item>
          <el-button type="primary" @click="loadData">搜索</el-button>
          <el-button @click="resetQuery">重置</el-button>
        </el-form-item>
      </el-form>
    </div>
    <el-card shadow="never" class="table-card">
      <div class="table-toolbar"><el-button type="primary" @click="openForm()">添加楼栋</el-button></div>
      <el-table :data="list" v-loading="loading" stripe border>
        <el-table-column type="index" label="#" width="50" />
        <el-table-column prop="id" label="ID" width="60" />
        <el-table-column prop="name" label="名称" width="150" />
        <el-table-column prop="community_name" label="所属小区" width="150" />
        <el-table-column prop="floors" label="层数" width="100">
          <template #default="{row}">
            {{ row.floors }}<template v-if="row.has_commercial"> <el-tag size="small" type="warning">底{{ row.commercial_floors }}</el-tag></template>
          </template>
        </el-table-column>
        <el-table-column prop="units" label="单元数" width="70" />
        <el-table-column prop="total_rooms" label="房间数" width="80" />
        <el-table-column prop="sort" label="排序" width="70" />
        <el-table-column label="操作" width="200" fixed="right">
          <template #default="{row}">
            <el-button size="small" @click="openForm(row)">编辑</el-button>
            <el-button size="small" type="danger" @click="handleDelete(row)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>
      <div class="pagination">
        <el-pagination v-model:current-page="query.page" v-model:page-size="query.limit" :total="total" :page-sizes="[15,30,50,100]" layout="total,sizes,prev,pager,next" @current-change="loadData" @size-change="loadData" />
      </div>
    </el-card>

    <el-dialog v-model="dialogVisible" :title="formTitle" width="720px" destroy-on-close>
      <el-form :model="form" :rules="rules" ref="formRef" label-width="100px">
        <el-form-item label="所属小区" prop="community_id"><el-select v-model="form.community_id" placeholder="选择小区" style="width:100%;"><el-option v-for="c in communities" :key="c.id" :label="c.name" :value="c.id" /></el-select></el-form-item>
        <el-form-item label="名称" prop="name"><el-input v-model="form.name" placeholder="楼栋名称" /></el-form-item>
        <el-row :gutter="16">
          <el-col :span="8"><el-form-item label="层数"><el-input-number v-model="form.floors" :min="1" style="width:100%;" /></el-form-item></el-col>
          <el-col :span="8"><el-form-item label="单元数"><el-input-number v-model="form.units" :min="1" style="width:100%;" /></el-form-item></el-col>
          <el-col :span="8"><el-form-item label="排序"><el-input-number v-model="form.sort" :min="0" style="width:100%;" /></el-form-item></el-col>
        </el-row>
        <el-row :gutter="16">
          <el-col :span="8">
            <el-form-item label="底商"><el-switch v-model="form.has_commercial" :disabled="!isAdd" active-text="有" inactive-text="无" /></el-form-item>
          </el-col>
          <el-col :span="8">
            <el-form-item label="底商层数">
              <el-input-number v-model="form.commercial_floors" :min="1" :max="form.floors - 1" :disabled="!form.has_commercial || !isAdd" style="width:100%;" />
            </el-form-item>
          </el-col>
          <el-col :span="8" v-if="isAdd && form.has_commercial">
            <span style="line-height:36px;color:#909399;font-size:13px;">
              {{ form.floors - form.commercial_floors }}层住宅 + {{ form.commercial_floors }}层底商
            </span>
          </el-col>
        </el-row>

        <template v-if="isAdd">
          <el-divider content-position="left">单元户型配置</el-divider>
          <el-alert type="info" :closable="false" style="margin-bottom:16px;">每个单元内各楼层户型相同，配置后将自动生成所有房间并填入面积。</el-alert>

          <div v-for="(unit, idx) in form.units_config" :key="idx" class="unit-card">
            <div class="unit-header">
              <strong>单元 {{ idx + 1 }}</strong>
              <el-checkbox v-if="idx > 0" v-model="unit.sameAsFirst" size="small" style="margin-left:12px;">与单元1户型相同</el-checkbox>
            </div>
            <div v-if="!unit.sameAsFirst || idx === 0" class="unit-body">
              <div class="config-inline">
                <label>每层户数</label>
                <el-input-number v-model="unit.rooms_per_floor" :min="1" size="small" style="width:100px;" @change="() => syncUnitAreas(idx)" />
              </div>
              <div class="config-inline area-config">
                <label>户型面积</label>
                <div class="area-inputs">
                  <template v-for="(a, ai) in unit.areas" :key="ai">
                    <span class="area-tag">户{{ ai + 1 }}</span>
                    <el-input-number v-model="unit.areas[ai]" :min="0" :precision="2" size="small" style="width:105px;" />
                    <span style="margin:0 4px 0 2px;color:#909399;">㎡</span>
                  </template>
                  <el-button size="small" type="primary" link @click="addUnitArea(idx)">+ 添加户型</el-button>
                </div>
              </div>
              <div class="unit-summary" v-if="unit.rooms_per_floor > 0">
                → <template v-if="form.has_commercial && form.commercial_floors > 0">{{ form.floors - form.commercial_floors }} 层住宅 × {{ unit.rooms_per_floor }} 户 = {{ (form.floors - form.commercial_floors) * unit.rooms_per_floor }} 个房间<el-tag size="small" type="warning" style="margin-left:6px;">底商{{ form.commercial_floors }}层跳过</el-tag></template>
                <template v-else>{{ form.floors }} 层 × {{ unit.rooms_per_floor }} 户 = {{ form.floors * unit.rooms_per_floor }} 个房间</template>
              </div>
            </div>
            <div v-else class="unit-body muted-text">
              (自动与单元1保持一致：{{ form.units_config[0]?.rooms_per_floor || 0 }}户/层，{{ (form.units_config[0]?.areas || []).join('、') || '—' }}㎡)
            </div>
          </div>

          <div class="total-rooms-bar" v-if="totalRoomsPreview > 0">
            <el-tag type="success" size="large">共计 {{ totalRoomsPreview }} 个房间将被自动生成</el-tag>
          </div>
        </template>
      </el-form>
      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" @click="submitForm" :loading="submitting">确定</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, watch, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { apiGet, apiPost } from '@/utils/request'

const list = ref<any[]>([])
const total = ref(0)
const loading = ref(false)
const dialogVisible = ref(false)
const submitting = ref(false)
const formRef = ref<any>(null)
const formTitle = ref('添加楼栋')
const communities = ref<any[]>([])

const defaultUnitConfig = () => ({ rooms_per_floor: 2, areas: [0, 0], sameAsFirst: false })

const query = reactive({ keyword: '', community_id: undefined as any, page: 1, limit: 15 })
const form = reactive<any>({
  id: 0, community_id: '', name: '', floors: 1, units: 1, sort: 0,
  has_commercial: false, commercial_floors: 1,
  units_config: [defaultUnitConfig()]
})
const rules = {
  name: [{ required: true, message: '请输入名称', trigger: 'blur' }],
  community_id: [{ required: true, message: '请选择小区', trigger: 'change' }]
}

const isAdd = computed(() => form.id === 0)
const totalRoomsPreview = computed(() => {
  if (!isAdd.value || !form.units_config) return 0
  const effectiveFloors = form.has_commercial ? Math.max(0, (form.floors || 0) - (form.commercial_floors || 0)) : (form.floors || 0)
  let total = 0
  for (const u of form.units_config) {
    total += effectiveFloors * (u.rooms_per_floor || 0)
  }
  return total
})

// Sync units_config count with units number
watch(() => form.units, (n) => {
  if (!isAdd.value || !form.units_config) return
  while (form.units_config.length < n) {
    const first = form.units_config[0]
    form.units_config.push({
      rooms_per_floor: first?.rooms_per_floor || 2,
      areas: [...(first?.areas || [0, 0])],
      sameAsFirst: form.units_config.length > 0
    })
  }
  while (form.units_config.length > n) form.units_config.pop()
})

// Clamp commercial_floors when floors drops below it
watch(() => form.floors, (n) => {
  if (n <= 1 && form.has_commercial) {
    form.has_commercial = false
    form.commercial_floors = 0
  } else if (form.has_commercial && form.commercial_floors >= n) {
    form.commercial_floors = n - 1
  }
})

// Propagate unit[0] changes to sameAsFirst units
watch(() => {
  const u0 = form.units_config?.[0]
  if (!u0) return ''
  return JSON.stringify({ rpf: u0.rooms_per_floor, areas: u0.areas })
}, () => {
  if (!form.units_config || form.units_config.length < 2) return
  const u0 = form.units_config[0]
  for (let i = 1; i < form.units_config.length; i++) {
    if (form.units_config[i].sameAsFirst) {
      form.units_config[i].rooms_per_floor = u0.rooms_per_floor
      form.units_config[i].areas = [...u0.areas]
    }
  }
})

function syncUnitAreas(idx: number) {
  if (!form.units_config?.[idx]) return
  const unit = form.units_config[idx]
  if (unit.sameAsFirst) return
  while (unit.areas.length < unit.rooms_per_floor) unit.areas.push(0)
  while (unit.areas.length > unit.rooms_per_floor) unit.areas.pop()
}

function addUnitArea(idx: number) {
  if (!form.units_config?.[idx]) return
  const unit = form.units_config[idx]
  unit.areas.push(0)
  unit.rooms_per_floor = unit.areas.length
}

function resetQuery() { query.keyword = ''; query.community_id = undefined; query.page = 1; loadData() }

async function loadData() {
  loading.value = true
  try {
    const r = await apiGet('/admin/building/list', { ...query })
    list.value = r.data?.list || r.data || []
    total.value = r.count || r.data?.total || list.value.length
  } catch { list.value = []; total.value = 0 }
  finally { loading.value = false }
}

function openForm(row?: any) {
  formTitle.value = row ? '编辑楼栋' : '添加楼栋'
  if (row) {
    Object.assign(form, {
      id: row.id, community_id: row.community_id || '', name: row.name || '',
      floors: row.floors || 1, units: row.units || 1, sort: row.sort || 0,
      has_commercial: !!(row.has_commercial), commercial_floors: row.commercial_floors || 0,
      units_config: []
    })
  } else {
    Object.assign(form, {
      id: 0, community_id: '', name: '', floors: 1, units: 1, sort: 0,
      has_commercial: false, commercial_floors: 1,
      units_config: [defaultUnitConfig()]
    })
  }
  dialogVisible.value = true
}

async function submitForm() {
  const valid = await formRef.value?.validate().catch(() => false)
  if (!valid) return
  submitting.value = true
  try {
    let payload: any
    if (form.id) {
      payload = { id: form.id, community_id: form.community_id, name: form.name, floors: form.floors, units: form.units, sort: form.sort, has_commercial: form.has_commercial ? 1 : 0, commercial_floors: form.commercial_floors }
    } else {
      payload = {
        community_id: form.community_id,
        name: form.name,
        floors: form.floors,
        units: form.units,
        has_commercial: form.has_commercial ? 1 : 0,
        commercial_floors: form.has_commercial ? form.commercial_floors : 0,
        units_config: form.units_config.map((u: any) => ({ rooms_per_floor: u.rooms_per_floor, areas: [...u.areas] })),
        sort: form.sort
      }
    }
    const url = form.id ? '/admin/building/edit' : '/admin/building/add'
    await apiPost(url, payload)
    ElMessage.success(form.id ? '修改成功' : '添加成功')
    dialogVisible.value = false
    loadData()
  } catch {
    // apiPost 已 toast
  } finally { submitting.value = false }
}

async function handleDelete(row: any) {
  try {
    await ElMessageBox.confirm(`确定删除楼栋 "${row.name}" 吗？`, '提示', { type: 'warning' })
    await apiPost('/admin/building/delete', { id: row.id })
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

.unit-card { border:1px solid #e4e7ed;border-radius:8px;padding:12px 16px;margin-bottom:12px;background:#fafbfc; }
.unit-header { display:flex;align-items:center;margin-bottom:8px; }
.unit-body { padding-left:4px; }
.unit-summary { margin-top:8px;color:#909399;font-size:13px; }
.muted-text { color:#909399;font-style:italic; }

.config-inline { display:flex;align-items:center;margin-bottom:8px;gap:10px; }
.config-inline label { width:70px;flex-shrink:0;text-align:right;color:#606266;font-size:13px; }
.area-config { align-items:flex-start; }
.area-inputs { display:flex;flex-wrap:wrap;align-items:center;gap:6px; }
.area-tag { font-size:12px;color:#909399;white-space:nowrap; }

.total-rooms-bar { margin-top:16px;text-align:center; }
</style>
