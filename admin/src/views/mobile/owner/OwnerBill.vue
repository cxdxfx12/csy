<template>
  <MobileLayout title="费用查询" showBack>
    <div class="pd">
      <div class="sh-stats">
        <div class="shs-item">
          <div class="shs-num">{{ unpaid }}</div>
          <div>未缴账单</div>
        </div>
        <div class="shs-item">
          <div class="shs-num" style="color:#e53e3e;">¥{{ totalUnpaid }}</div>
          <div>欠费金额</div>
        </div>
      </div>
      <div class="sh-section-title">📋 历史账单</div>
      <div v-for="b in bills" class="so-card">
        <div class="so-header">
          <span>{{ b.item_name || b.name }}</span>
          <span class="so-status" :style="statusStyle(b)">{{ statusName(b) }}</span>
        </div>
        <div class="so-body">金额: ¥{{ b.amount }} | 期间: {{ b.period || getPeriod(b) }}</div>
      </div>
      <div v-if="!bills.length" style="text-align:center;color:#a0aec0;padding:40px;">暂无账单</div>
    </div>
  </MobileLayout>
</template>
<script setup lang="ts">
import { ref, onMounted } from 'vue'
import MobileLayout from '../MobileLayout.vue'

const bills = ref<any[]>([])
const unpaid = ref(0)
const totalUnpaid = ref('0')

function getPeriod(b: any) {
  return b.create_time?.substring(0, 7)
}

function statusName(b: any) {
  if (b.status_name) return b.status_name
  if (b.status === 3) return '已缴清'
  if (b.status === 1) return '未缴'
  return '部分缴纳'
}

function statusStyle(b: any) {
  if (b.status === 3) return { background: '#f0fff4', color: '#38a169' }
  if (b.status === 1) return { background: '#fff5f5', color: '#e53e3e' }
  return { background: '#fffbeb', color: '#d69e2e' }
}

onMounted(async () => {
  try {
    const r = await fetch('/api/bill/list', {
      headers: { Authorization: 'Bearer ' + localStorage.getItem('owner_token') },
    })
    const d = await r.json()
    if (d.code === 0) {
      bills.value = d.data?.list || []
      const unpaidBills = bills.value.filter((b: any) => b.status === 1 || b.status === 2)
      unpaid.value = unpaidBills.length
      totalUnpaid.value = unpaidBills
        .reduce((s: number, b: any) => s + parseFloat(b.amount || '0'), 0)
        .toFixed(2)
    }
  } catch (e) {}
})
</script>
<style scoped>
.pd { padding: 4px 0; }
.sh-stats { display: flex; gap: 10px; margin-bottom: 16px; }
.shs-item { flex: 1; background: #fff; border-radius: 12px; padding: 16px; text-align: center; border: 1px solid #e2e8f0; font-size: 13px; color: #a0aec0; }
.shs-num { font-size: 24px; font-weight: 700; color: #2b6cb0; margin-bottom: 4px; }
.sh-section-title { font-size: 16px; font-weight: 600; color: #1a202c; margin-bottom: 12px; }
.so-card { background: #fff; border-radius: 12px; padding: 14px 16px; margin-bottom: 10px; border: 1px solid #e2e8f0; }
.so-header { display: flex; justify-content: space-between; margin-bottom: 6px; }
.so-header span:first-child { font-weight: 600; color: #1a202c; font-size: 14px; }
.so-status { font-size: 12px; padding: 2px 10px; border-radius: 10px; }
.so-body { font-size: 13px; color: #4a5568; }
</style>
