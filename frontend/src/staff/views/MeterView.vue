<template>
  <div class="page">
    <header><button class="back" @click="$router.back()">←</button><h1>抄表录入</h1></header>
    <div v-if="rooms.length" class="list">
      <div v-for="room in rooms" :key="room.id" class="item">
        <div class="item-title">{{ room.community_name }} - {{ room.room_no }}</div>
        <div class="item-info">
          <span>业主: {{ room.owner_name || '--' }}</span>
          <span>上次读数: {{ room.last_reading || '--' }}</span>
        </div>
        <div class="input-row">
          <input v-model="readings[room.id]" type="number" placeholder="本次读数" />
          <button class="btn-act" @click="submitReading(room.id)" :disabled="!readings[room.id]">提交</button>
        </div>
      </div>
    </div>
    <div v-else class="empty">暂无需抄表的房间</div>

    <div class="section" style="margin-top:20px">
      <h3>抄表历史</h3>
      <div v-if="!history.length" class="empty">暂无记录</div>
      <div v-for="h in history" :key="h.id" class="h-item">
        <div class="item-title">{{ h.community_name }} - {{ h.room_no }}</div>
        <div class="item-info">
          <span>读数: {{ h.reading }}</span>
          <span>时间: {{ h.create_time }}</span>
        </div>
      </div>
    </div>
  </div>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import { createApi } from '@/shared/api.js'
import { showToast } from '@/shared/utils.js'
const api = createApi('/api/staff', 'staff_token')
const rooms = ref([])
const readings = ref({})
const history = ref([])

onMounted(async () => {
  const [r1, r2] = await Promise.all([
    api('/meter/list'), api('/meter/history')
  ])
  if (r1.code === 0) rooms.value = Array.isArray(r1.data) ? r1.data : (r1.data?.list || [])
  if (r2.code === 0) history.value = Array.isArray(r2.data) ? r2.data : (r2.data?.list || [])
})

async function submitReading(roomId) {
  const val = readings.value[roomId]
  if (!val) return
  const res = await api('/meter/read', { method: 'POST', body: JSON.stringify({ room_id: roomId, reading: Number(val) }) })
  showToast(res.code === 0 ? '录入成功' : (res.msg || '失败'))
  if (res.code === 0) { readings.value[roomId] = ''; loadHistory() }
}

async function loadHistory() {
  const res = await api('/meter/history')
  if (res.code === 0) history.value = Array.isArray(res.data) ? res.data : (res.data?.list || [])
}
</script>
<style scoped>
.page{padding:16px}
header{display:flex;align-items:center;gap:12px;margin-bottom:16px}
header h1{font-size:18px}
.back{background:none;border:none;font-size:20px;cursor:pointer;padding:4px 8px}
.list{display:flex;flex-direction:column;gap:12px}
.item{background:#fff;border-radius:12px;padding:16px;box-shadow:0 1px 3px rgba(0,0,0,.06)}
.item-title{font-size:15px;font-weight:600;margin-bottom:6px}
.item-info{display:flex;gap:12px;font-size:12px;color:#6b7280;margin-bottom:10px}
.input-row{display:flex;gap:8px}
.input-row input{flex:1;height:40px;border:1px solid #e5e7eb;border-radius:8px;padding:0 12px;font-size:14px;outline:none}
.input-row input:focus{border-color:#2563eb}
.btn-act{padding:8px 16px;background:#2563eb;color:#fff;border:none;border-radius:8px;font-size:13px;cursor:pointer;white-space:nowrap}
.btn-act:disabled{opacity:.5}
.empty{text-align:center;padding:40px;color:#9ca3af}
.section h3{font-size:16px;margin-bottom:12px}
.h-item{background:#fff;border-radius:10px;padding:12px;margin-bottom:8px;box-shadow:0 1px 2px rgba(0,0,0,.04)}
</style>
