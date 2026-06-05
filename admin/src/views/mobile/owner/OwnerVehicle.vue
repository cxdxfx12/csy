<template>
  <MobileLayout title="车辆管理" showBack>
    <div class="pd">
      <button class="or-add" @click="showForm = true">➕ 添加车辆</button>
      <div v-if="showForm" class="or-form">
        <input v-model="form.plate_number" placeholder="车牌号" class="or-input" />
        <input v-model="form.brand" placeholder="品牌" class="or-input" />
        <input v-model="form.color" placeholder="颜色" class="or-input" />
        <button class="or-btn" style="background:#3182ce;" @click="submitVehicle">保存</button>
      </div>
      <div class="sh-section-title" style="margin-top:16px;">🚗 我的车辆</div>
      <div v-for="v in vehicles" class="so-card">
        <div class="so-header">
          <span>🚗 {{ v.plate_number }}</span>
          <span style="font-size:12px;color:#a0aec0;">{{ v.brand }}</span>
        </div>
        <div class="so-body">颜色: {{ v.color || '-' }} | 车位: {{ v.space_code || '未绑定' }}</div>
      </div>
      <div v-if="!vehicles.length" style="text-align:center;color:#a0aec0;padding:40px;">暂无车辆</div>
    </div>
  </MobileLayout>
</template>
<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import MobileLayout from '../MobileLayout.vue'

const vehicles = ref<any[]>([])
const showForm = ref(false)
const form = ref({ plate_number: '', brand: '', color: '' })

onMounted(async () => {
  try {
    const r = await fetch('/api/vehicle/list', {
      headers: { Authorization: 'Bearer ' + localStorage.getItem('owner_token') },
    })
    const d = await r.json()
    vehicles.value = d.data?.list || d.data || []
  } catch (e) {}
})

async function submitVehicle() {
  if (!form.value.plate_number) {
    ElMessage.warning('请输入车牌号')
    return
  }
  try {
    const r = await fetch('/api/vehicle/add', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Authorization: 'Bearer ' + localStorage.getItem('owner_token'),
      },
      body: JSON.stringify(form.value),
    })
    const d = await r.json()
    if (d.code === 0) {
      ElMessage.success('添加成功')
      showForm.value = false
      location.reload()
    } else {
      ElMessage.error(d.msg)
    }
  } catch (e) {
    ElMessage.error('提交失败')
  }
}
</script>
