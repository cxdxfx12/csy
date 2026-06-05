<template>
  <div class="page">
    <header>
      <button class="back" @click="$router.back()">←</button>
      <h1>我的车辆</h1>
      <button class="btn-sm" @click="showForm=!showForm">{{ showForm ? '取消' : '添加' }}</button>
    </header>

    <div v-if="showForm" class="form-card">
      <h3>添加车辆</h3>
      <input v-model="form.plate_number" placeholder="车牌号 (必填)" />
      <input v-model="form.brand" placeholder="品牌 (如: 大众)" />
      <input v-model="form.model" placeholder="车型 (如: 帕萨特)" />
      <input v-model="form.color" placeholder="颜色 (如: 黑色)" />
      <div class="select-wrap">
        <select v-model="form.vehicle_type">
          <option :value="1">轿车</option>
          <option :value="2">SUV</option>
          <option :value="3">面包车</option>
          <option :value="4">货车</option>
          <option :value="5">新能源</option>
        </select>
      </div>
      <button class="btn-primary" @click="submitVehicle" :disabled="loading">
        {{ loading ? '提交中...' : '确认添加' }}
      </button>
    </div>

    <div v-if="!list.length" class="empty">暂无车辆信息</div>
    <div v-else class="list">
      <div v-for="item in list" :key="item.id" class="item">
        <div class="item-hd">
          <span class="title">{{ item.plate_number }}</span>
          <span class="tag" :style="{background:vehicleTypeColor(item.vehicle_type),color:'#fff'}">
            {{ vehicleTypeLabel(item.vehicle_type) }}
          </span>
        </div>
        <div class="item-body">
          <p>🏭 {{ item.brand || '--' }} {{ item.model || '' }}</p>
          <p>🎨 {{ item.color || '--' }}</p>
          <p>📅 登记时间: {{ formatDate(item.create_time) }}</p>
        </div>
      </div>
    </div>
  </div>
</template>
<script setup>
import { ref, onMounted, reactive } from 'vue'
import { createApi } from '@/shared/api.js'
import { showToast, formatDate } from '@/shared/utils.js'

const api = createApi('/api/api', 'owner_token')
const form = reactive({ plate_number: '', brand: '', model: '', color: '', vehicle_type: 1 })
const list = ref([])
const showForm = ref(false)
const loading = ref(false)

const typeLabels = { 1: '轿车', 2: 'SUV', 3: '面包车', 4: '货车', 5: '新能源' }
const typeColors = { 1: '#3b82f6', 2: '#8b5cf6', 3: '#f59e0b', 4: '#6b7280', 5: '#10b981' }

function vehicleTypeLabel(v) { return typeLabels[v] || '其他' }
function vehicleTypeColor(v) { return typeColors[v] || '#6b7280' }

onMounted(async () => {
  const res = await api('/vehicle/list')
  if (res.code === 0) list.value = Array.isArray(res.data) ? res.data : (res.data?.list || [])
})

async function submitVehicle() {
  if (!form.plate_number) return showToast('请输入车牌号')
  loading.value = true
  const res = await api('/vehicle/add', { method: 'POST', body: JSON.stringify(form) })
  loading.value = false
  showToast(res.code === 0 ? '添加成功' : (res.msg || '添加失败'))
  if (res.code === 0) {
    showForm.value = false
    Object.keys(form).forEach(k => {
      if (k === 'vehicle_type') form[k] = 1
      else form[k] = ''
    })
    const r = await api('/vehicle/list')
    if (r.code === 0) list.value = Array.isArray(r.data) ? r.data : (r.data?.list || [])
  }
}
</script>
<style scoped>
.page{padding:16px}
header{display:flex;align-items:center;gap:12px;margin-bottom:16px}
header h1{font-size:18px;flex:1}
.back{background:none;border:none;font-size:20px;cursor:pointer;padding:4px 8px}
.btn-sm{background:none;border:1px solid #2563eb;color:#2563eb;padding:6px 14px;border-radius:6px;font-size:13px;cursor:pointer}
.form-card{background:#fff;border-radius:12px;padding:18px;margin-bottom:16px;box-shadow:0 1px 3px rgba(0,0,0,.06)}
.form-card h3{font-size:16px;margin-bottom:14px}
input,.select-wrap select{width:100%;height:44px;border:1px solid #e5e7eb;border-radius:8px;padding:0 14px;font-size:14px;margin-bottom:11px;outline:none;background:#fff}
input:focus,.select-wrap select:focus{border-color:#2563eb}
.select-wrap{margin-bottom:11px}
.btn-primary{width:100%;height:44px;background:#2563eb;color:#fff;border:none;border-radius:8px;font-size:15px;cursor:pointer}
.btn-primary:disabled{opacity:.6}
.empty{text-align:center;padding:40px;color:#9ca3af}
.list{display:flex;flex-direction:column;gap:10px}
.item{background:#fff;border-radius:12px;padding:16px;box-shadow:0 1px 3px rgba(0,0,0,.06)}
.item-hd{display:flex;justify-content:space-between;margin-bottom:8px}
.title{font-size:15px;font-weight:600}
.tag{font-size:11px;padding:2px 10px;border-radius:12px}
.item-body p{margin:3px 0;font-size:13px;color:#6b7280}
</style>
