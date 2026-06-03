<template>
  <div class="page">
    <header><button class="back" @click="$router.back()">←</button><h1>移动收费</h1></header>
    <div v-if="loading" class="loading">加载中...</div>
    <div v-else-if="!list.length" class="empty">暂无可收费项目</div>
    <div v-else class="list">
      <div v-for="item in list" :key="item.id" class="item">
        <div class="item-title">{{ item.owner_name || '--' }}</div>
        <div class="item-info">
          <span>🏘️ {{ item.community_name }}</span>
          <span>🏠 {{ item.room_no }}</span>
        </div>
        <div class="item-row">
          <span class="amount">¥{{ item.amount || item.total || 0 }}</span>
          <span class="tag" :style="{color:item.status==='paid'?'#10b981':'#ef4444'}">{{ item.status==='paid'?'已缴':'未缴' }}</span>
        </div>
        <button v-if="item.status !== 'paid'" class="btn-pay" @click="collect(item)">现场收款</button>
      </div>
    </div>
  </div>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import { createApi } from '@/shared/api.js'
import { showToast } from '@/shared/utils.js'
const api = createApi('/api/staff', 'staff_token')
const list = ref([])
const loading = ref(false)

onMounted(async () => {
  loading.value = true
  const res = await api('/charge/unpaidList')
  if (res.code === 0) list.value = Array.isArray(res.data) ? res.data : (res.data?.list || [])
  loading.value = false
})

async function collect(item) {
  if (!confirm(`确认收取 ¥${item.amount || item.total} 费用？`)) return
  const res = await api('/charge/collect', { method: 'POST', body: JSON.stringify({ id: item.id, amount: item.amount || item.total }) })
  showToast(res.code === 0 ? '收款成功' : (res.msg || '失败'))
  if (res.code === 0) { item.status = 'paid' }
}
</script>
<style scoped>
.page{padding:16px}
header{display:flex;align-items:center;gap:12px;margin-bottom:16px}
header h1{font-size:18px}
.back{background:none;border:none;font-size:20px;cursor:pointer;padding:4px 8px}
.loading,.empty{text-align:center;padding:40px;color:#9ca3af}
.list{display:flex;flex-direction:column;gap:12px}
.item{background:#fff;border-radius:12px;padding:16px;box-shadow:0 1px 3px rgba(0,0,0,.06)}
.item-title{font-size:15px;font-weight:600;margin-bottom:6px}
.item-info{display:flex;gap:12px;font-size:12px;color:#6b7280;margin-bottom:8px}
.item-row{display:flex;justify-content:space-between;align-items:center;margin-bottom:10px}
.amount{font-size:20px;font-weight:700;color:#ef4444}
.tag{font-size:12px;font-weight:600}
.btn-pay{width:100%;height:40px;background:#10b981;color:#fff;border:none;border-radius:8px;font-size:14px;font-weight:600;cursor:pointer}
</style>
