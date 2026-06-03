<template>
  <MobileLayout title="在线报修" showBack>
    <div class="pd">
      <button class="or-add" @click="showForm = true">➕ 发起报修</button>
      <div v-if="showForm" class="or-form">
        <input v-model="form.content" placeholder="描述问题" class="or-input" />
        <select v-model="form.type" class="or-input">
          <option value="">选择类型</option>
          <option value="1">水电</option>
          <option value="2">门窗</option>
          <option value="3">家电</option>
        </select>
        <button class="or-btn" @click="submitRepair">提交报修</button>
      </div>
      <div class="sh-section-title" style="margin-top:16px;">📋 我的报修</div>
      <div v-for="o in orders" class="so-card">
        <div class="so-header">
          <span>{{ o.type_name || '报修' }}</span>
          <span class="so-status">{{ o.status_name || repairStatusLabel(o.status) }}</span>
        </div>
        <div class="so-body">{{ o.content }}</div>
        <div class="so-footer"><span>{{ o.create_time }}</span></div>
      </div>
      <div v-if="!orders.length" style="text-align:center;color:#a0aec0;padding:40px;">暂无报修</div>
    </div>
  </MobileLayout>
</template>
<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import MobileLayout from '../MobileLayout.vue'

const orders = ref<any[]>([])
const showForm = ref(false)
const form = ref({ content: '', type: '' })

function repairStatusLabel(status: number) {
  if (status === 5) return '已完成'
  return '处理中'
}

onMounted(async () => {
  try {
    const r = await fetch('/api/repair/list', {
      headers: { Authorization: 'Bearer ' + localStorage.getItem('owner_token') },
    })
    const d = await r.json()
    orders.value = d.data?.list || d.data || []
  } catch (e) {}
})

async function submitRepair() {
  if (!form.value.content) {
    ElMessage.warning('请描述问题')
    return
  }
  try {
    const r = await fetch('/api/repair/add', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Authorization: 'Bearer ' + localStorage.getItem('owner_token'),
      },
      body: JSON.stringify(form.value),
    })
    const d = await r.json()
    if (d.code === 0) {
      ElMessage.success('报修成功')
      showForm.value = false
      form.value = { content: '', type: '' }
      location.reload()
    } else {
      ElMessage.error(d.msg)
    }
  } catch (e) {
    ElMessage.error('提交失败')
  }
}
</script>
<style scoped>
.pd { padding: 4px 0; }
.or-add { width: 100%; height: 44px; background: linear-gradient(135deg, #3182ce, #2b6cb0); color: #fff; border: none; border-radius: 10px; font-size: 15px; cursor: pointer; margin-bottom: 12px; }
.or-form { background: #fff; border-radius: 12px; padding: 16px; margin-bottom: 16px; border: 1px solid #e2e8f0; }
.or-input { width: 100%; height: 40px; border: 1px solid #e2e8f0; border-radius: 8px; padding: 0 12px; margin-bottom: 10px; font-size: 14px; box-sizing: border-box; }
.or-btn { width: 100%; height: 40px; background: #38a169; color: #fff; border: none; border-radius: 8px; font-size: 14px; cursor: pointer; }
.sh-section-title { font-size: 16px; font-weight: 600; color: #1a202c; margin-bottom: 12px; }
.so-card { background: #fff; border-radius: 12px; padding: 14px 16px; margin-bottom: 10px; border: 1px solid #e2e8f0; }
.so-header { display: flex; justify-content: space-between; margin-bottom: 6px; }
.so-header span:first-child { font-weight: 600; color: #1a202c; font-size: 14px; }
.so-status { font-size: 12px; padding: 2px 10px; border-radius: 10px; background: #ebf8ff; color: #2b6cb0; }
.so-body { font-size: 13px; color: #4a5568; margin-bottom: 6px; }
.so-footer { font-size: 12px; color: #a0aec0; }
</style>
