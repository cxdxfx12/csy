<template>
  <div class="page">
    <header><button class="back" @click="$router.back()">←</button><h1>安保巡更</h1></header>
    <div v-if="!routes.length" class="empty">暂无巡更路线</div>
    <div v-else class="list">
      <div v-for="route in routes" :key="route.id" class="item">
        <div class="item-title">🛡️ {{ route.name || route.title || '巡更路线' }}</div>
        <div class="item-info">{{ route.description || route.points?.length + '个打卡点' || '' }}</div>
        <button class="btn-chk" @click="checkIn(route.id)">📸 打卡签到</button>
      </div>
    </div>
    <div class="section" style="margin-top:24px">
      <h3>巡更记录</h3>
      <div v-if="!history.length" class="empty">暂无记录</div>
      <div v-for="h in history" :key="h.id" class="h-item">
        <span>📍 {{ h.route_name || h.location || '--' }}</span>
        <span class="time">{{ h.create_time }}</span>
      </div>
    </div>
  </div>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import { createApi } from '@/shared/api.js'
import { showToast } from '@/shared/utils.js'
const api = createApi('/api/staff', 'staff_token')
const routes = ref([])
const history = ref([])

onMounted(async () => {
  const [r1, r2] = await Promise.all([api('/patrol/routes'), api('/patrol/history')])
  if (r1.code === 0) routes.value = Array.isArray(r1.data) ? r1.data : (r1.data?.list || [])
  if (r2.code === 0) history.value = Array.isArray(r2.data) ? r2.data : (r2.data?.list || [])
})
async function checkIn(routeId) {
  const res = await api('/patrol/check', { method: 'POST', body: JSON.stringify({ route_id: routeId }) })
  showToast(res.code === 0 ? '打卡成功' : (res.msg || '打卡失败'))
  if (res.code === 0) {
    const r2 = await api('/patrol/history')
    if (r2.code === 0) history.value = Array.isArray(r2.data) ? r2.data : (r2.data?.list || [])
  }
}
</script>
<style scoped>
.page{padding:16px}
header{display:flex;align-items:center;gap:12px;margin-bottom:16px}
header h1{font-size:18px}
.back{background:none;border:none;font-size:20px;cursor:pointer;padding:4px 8px}
.empty{text-align:center;padding:40px;color:#9ca3af}
.list{display:flex;flex-direction:column;gap:12px}
.item{background:#fff;border-radius:12px;padding:16px;box-shadow:0 1px 3px rgba(0,0,0,.06)}
.item-title{font-size:15px;font-weight:600;margin-bottom:6px}
.item-info{font-size:13px;color:#6b7280;margin-bottom:10px}
.btn-chk{width:100%;height:40px;background:#f59e0b;color:#fff;border:none;border-radius:8px;font-size:14px;cursor:pointer}
.section h3{font-size:16px;margin-bottom:12px}
.h-item{display:flex;justify-content:space-between;background:#fff;border-radius:8px;padding:10px 14px;margin-bottom:6px;font-size:14px}
.h-item .time{color:#9ca3af;font-size:12px}
</style>
