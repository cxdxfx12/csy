<template>
  <MobileLayout title="工单管理" showBack>
    <div class="pd">
      <div class="s-filter">
        <span
          v-for="s in statusList"
          :key="s.v"
          class="sf-item"
          :class="{ active: status === s.v }"
          @click="changeFilter(s.v)"
        >{{ s.l }}</span>
      </div>
      <div v-for="o in orders" class="so-card" @click="showDetail(o)">
        <div class="so-header">
          <span>{{ o.type_name || '报修' }}</span>
          <span class="so-status">{{ o.status_name }}</span>
        </div>
        <div class="so-body">{{ o.content || o.desc }}</div>
        <div class="so-footer">
          <span>{{ o.owner_name || o.contact }}</span>
          <span>{{ o.create_time }}</span>
        </div>
      </div>
      <div v-if="!orders.length" style="text-align:center;color:#a0aec0;padding:40px;">暂无工单</div>
    </div>
  </MobileLayout>
</template>
<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import MobileLayout from '../MobileLayout.vue'

const orders = ref<any[]>([])
const status = ref('')
const statusList = [
  { v: '', l: '全部' },
  { v: '1', l: '待派单' },
  { v: '3', l: '处理中' },
  { v: '5', l: '已完成' },
]

function changeFilter(v: string) {
  status.value = v
  load()
}

async function load() {
  try {
    const r = await fetch('/api/staff/repair/list?status=' + status.value, {
      headers: { Authorization: 'Bearer ' + localStorage.getItem('staff_token') },
    })
    const d = await r.json()
    orders.value = d.data?.list || d.data || []
  } catch (e) {}
}

function showDetail(o: any) {
  ElMessage.info('工单详情: ' + o.id)
}

onMounted(load)
</script>
<style scoped>
.pd { padding: 4px 0; }
.s-filter { display: flex; gap: 6px; margin-bottom: 16px; flex-wrap: wrap; }
.sf-item { padding: 6px 16px; border-radius: 20px; font-size: 13px; background: #fff; border: 1px solid #e2e8f0; color: #4a5568; cursor: pointer; }
.sf-item.active { background: #2b6cb0; color: #fff; border-color: #2b6cb0; }
.so-card { background: #fff; border-radius: 12px; padding: 14px 16px; margin-bottom: 10px; border: 1px solid #e2e8f0; cursor: pointer; }
.so-header { display: flex; justify-content: space-between; margin-bottom: 6px; }
.so-header span:first-child { font-weight: 600; color: #1a202c; font-size: 14px; }
.so-status { font-size: 12px; padding: 2px 8px; border-radius: 10px; background: #ebf8ff; color: #2b6cb0; }
.so-body { font-size: 13px; color: #4a5568; margin-bottom: 8px; line-height: 1.5; }
.so-footer { display: flex; justify-content: space-between; font-size: 12px; color: #a0aec0; }
</style>
