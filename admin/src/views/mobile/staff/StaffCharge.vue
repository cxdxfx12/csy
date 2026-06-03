<template>
  <MobileLayout title="移动收费" showBack>
    <div class="pd">
      <div class="sh-stats">
        <div class="shs-item"><div class="shs-num">{{ stats.count }}</div><div>欠费户数</div></div>
        <div class="shs-item"><div class="shs-num">¥{{ stats.amount }}</div><div>欠费总额</div></div>
      </div>
      <div class="sh-section-title">📋 欠费列表</div>
      <div v-for="b in bills" class="so-card">
        <div class="so-header">
          <span>{{ b.room_name || b.room }}</span>
          <span class="so-status" style="background:#fff5f5;color:#e53e3e;">欠费</span>
        </div>
        <div class="so-body">项目: {{ b.item_name || b.name }} | 金额: ¥{{ b.amount }}</div>
        <div class="so-footer">
          <span>{{ b.owner_name }}</span>
          <span>{{ b.period }}</span>
        </div>
        <button class="sc-btn" @click="collect(b)">💵 收款</button>
      </div>
      <div v-if="!bills.length" style="text-align:center;color:#a0aec0;padding:40px;">暂无欠费</div>
    </div>
  </MobileLayout>
</template>
<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import MobileLayout from '../MobileLayout.vue'

const bills = ref<any[]>([])
const stats = ref({ count: 0, amount: 0 })

async function load() {
  try {
    const r = await fetch('/api/staff/charge/list', {
      headers: { Authorization: 'Bearer ' + localStorage.getItem('staff_token') },
    })
    const d = await r.json()
    if (d.code === 0) {
      bills.value = d.data?.list || []
      stats.value = { count: d.data?.count || 0, amount: d.data?.amount || 0 }
    }
  } catch (e) {}
}

function collect(b: any) {
  ElMessage.info('收款功能: ' + b.id + ' 金额¥' + b.amount)
}

onMounted(load)
</script>
<style scoped>
.pd { padding: 4px 0; }
.sc-btn { width: 100%; height: 40px; background: linear-gradient(135deg, #38a169, #2f855a); color: #fff; border: none; border-radius: 8px; font-size: 14px; margin-top: 8px; cursor: pointer; }
.sh-stats { display: flex; gap: 10px; margin-bottom: 16px; }
.shs-item { flex: 1; background: #fff; border-radius: 12px; padding: 16px; text-align: center; border: 1px solid #e2e8f0; font-size: 13px; color: #a0aec0; }
.shs-num { font-size: 24px; font-weight: 700; color: #2b6cb0; margin-bottom: 4px; }
.sh-section-title { font-size: 16px; font-weight: 600; color: #1a202c; margin-bottom: 12px; }
.so-card { background: #fff; border-radius: 12px; padding: 14px 16px; margin-bottom: 10px; border: 1px solid #e2e8f0; }
.so-header { display: flex; justify-content: space-between; margin-bottom: 6px; }
.so-header span:first-child { font-weight: 600; color: #1a202c; }
.so-status { font-size: 12px; padding: 2px 10px; border-radius: 10px; }
.so-body { font-size: 13px; color: #4a5568; margin-bottom: 6px; }
.so-footer { display: flex; justify-content: space-between; font-size: 12px; color: #a0aec0; }
</style>
