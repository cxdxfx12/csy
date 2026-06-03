<template>
  <div class="page">
    <header>
      <button class="back" @click="$router.back()">←</button>
      <h1>抄表录入</h1>
    </header>

    <!-- 表类型切换 -->
    <div class="tabs">
      <button
        v-for="t in meterTypes"
        :key="t.value"
        :class="['tab', { active: curType === t.value }]"
        @click="switchType(t.value)"
      >{{ t.label }}</button>
    </div>

    <!-- 房间列表（按楼栋分组） -->
    <div v-if="groupedRooms.length" class="room-list">
      <div v-for="group in groupedRooms" :key="group.building" class="building-group">
        <div class="building-title">🏢 {{ group.building }}</div>
        <div v-for="r in group.rooms" :key="r.id" class="room-card">
          <div class="card-top">
            <div class="room-info">
              <span class="room-no">{{ r.room_number }}室</span>
              <span v-if="r.unit" class="unit">{{ r.unit }}单元</span>
              <span v-if="r.owner_name" class="owner">业主：{{ r.owner_name }}</span>
            </div>
            <div class="last-reading">
              <span class="label">上次读数</span>
              <span :class="['value', { none: !+r.last_reading }]">
                {{ +r.last_reading ? r.last_reading : '--' }}
              </span>
              <span v-if="r.last_date" class="date">{{ r.last_date }}</span>
            </div>
          </div>
          <div class="input-row">
            <input
              v-model="readings[r.id]"
              type="number"
              step="0.01"
              placeholder="输入本次读数"
              @keyup.enter="submitReading(r.id)"
            />
            <button
              class="btn-submit"
              :disabled="!readings[r.id] || submitting[r.id]"
              @click="submitReading(r.id)"
            >
              {{ submitting[r.id] ? '提交中' : '提交' }}
            </button>
          </div>
        </div>
      </div>
    </div>
    <div v-else class="empty">暂无需抄表的房间</div>

    <!-- 抄表历史 -->
    <div class="section">
      <h3>{{ curTypeLabel }}抄表记录</h3>
      <div v-if="!history.length" class="empty">暂无记录</div>
      <div v-for="h in history" :key="h.id" class="h-item">
        <div class="h-top">
          <span class="h-room">{{ h.building_name }}-{{ h.room_number }}</span>
          <span class="h-reading">{{ h.current_reading }}</span>
        </div>
        <div class="h-bottom">
          <span>用量：{{ h.usage_amount }}</span>
          <span>{{ h.reading_date }}</span>
          <span class="h-by">{{ h.reading_by }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onActivated } from 'vue'
import { createApi } from '@/shared/api.js'
import { showToast } from '@/shared/utils.js'

const api = createApi('/api/staff', 'staff_token')

const meterTypes = [
  { value: 1, label: '💧 水表' },
  { value: 2, label: '⚡ 电表' },
  { value: 3, label: '🔥 燃气表' },
]

const curType   = ref(1)
const rooms     = ref([])
const readings  = ref({})
const submitting = ref({})
const history   = ref([])

const curTypeLabel = computed(() => meterTypes.find(t => t.value === curType.value)?.label || '')

// 按楼栋分组
const groupedRooms = computed(() => {
  const map = {}
  for (const r of rooms.value) {
    const b = r.building_name || '其他'
    if (!map[b]) map[b] = []
    map[b].push(r)
  }
  return Object.entries(map).map(([building, rooms]) => ({ building, rooms }))
})

async function loadRooms() {
  const res = await api(`/meter/list?type=${curType.value}`)
  if (res.code === 0) {
    rooms.value = Array.isArray(res.data) ? res.data : (res.data?.list || [])
  }
}

async function loadHistory() {
  const res = await api(`/meter/history?type=${curType.value}`)
  if (res.code === 0) {
    history.value = Array.isArray(res.data) ? res.data : (res.data?.list || [])
  }
}

function switchType(type) {
  curType.value = type
  readings.value = {}
  loadRooms()
  loadHistory()
}

async function submitReading(roomId) {
  const val = readings.value[roomId]
  if (!val) return
  submitting.value[roomId] = true
  try {
    const res = await api('/meter/read', {
      method: 'POST',
      body: JSON.stringify({
        room_id: roomId,
        type: curType.value,
        current_reading: Number(val),
        reading_date: new Date().toISOString().slice(0, 10),
      }),
    })
    showToast(res.code === 0 ? '录入成功' : (res.msg || '失败'))
    if (res.code === 0) {
      readings.value[roomId] = ''
      loadRooms()
      loadHistory()
    }
  } finally {
    submitting.value[roomId] = false
  }
}

onMounted(() => { loadRooms(); loadHistory() })
// keep-alive 时刷新
onActivated(() => { loadRooms(); loadHistory() })
</script>

<style scoped>
.page { padding: 16px; padding-bottom: 40px; }
header { display: flex; align-items: center; gap: 12px; margin-bottom: 16px; }
header h1 { font-size: 18px; margin: 0; }
.back { background: none; border: none; font-size: 20px; cursor: pointer; padding: 4px 8px; }

/* 类型切换 */
.tabs { display: flex; gap: 8px; margin-bottom: 16px; }
.tab {
  flex: 1; height: 40px; border-radius: 10px; border: 1px solid #e5e7eb;
  background: #fff; font-size: 14px; cursor: pointer; transition: all .2s;
  display: flex; align-items: center; justify-content: center;
}
.tab.active { background: #2563eb; color: #fff; border-color: #2563eb; font-weight: 600; }

/* 房间列表 */
.building-group { margin-bottom: 14px; }
.building-title { font-size: 14px; font-weight: 600; color: #374151; margin-bottom: 8px; padding-left: 4px; }

.room-card {
  background: #fff; border-radius: 12px; padding: 14px; margin-bottom: 8px;
  box-shadow: 0 1px 3px rgba(0,0,0,.06);
}
.card-top { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px; }
.room-info { display: flex; flex-wrap: wrap; gap: 8px; }
.room-no { font-size: 15px; font-weight: 600; }
.unit { font-size: 12px; color: #6b7280; background: #f3f4f6; padding: 2px 6px; border-radius: 4px; }
.owner { font-size: 12px; color: #6b7280; }

.last-reading { text-align: right; }
.last-reading .label { font-size: 11px; color: #9ca3af; display: block; }
.last-reading .value { font-size: 16px; font-weight: 600; color: #1f2937; }
.last-reading .value.none { color: #d1d5db; font-weight: 400; font-size: 14px; }
.last-reading .date { font-size: 11px; color: #9ca3af; display: block; }

.input-row { display: flex; gap: 8px; }
.input-row input {
  flex: 1; height: 42px; border: 1px solid #d1d5db; border-radius: 8px;
  padding: 0 12px; font-size: 15px; outline: none; transition: border-color .2s;
}
.input-row input:focus { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,.1); }

.btn-submit {
  padding: 0 20px; background: #2563eb; color: #fff; border: none; border-radius: 8px;
  font-size: 14px; cursor: pointer; white-space: nowrap; min-width: 64px;
  transition: opacity .2s;
}
.btn-submit:disabled { opacity: .4; cursor: not-allowed; }

/* 历史 */
.section { margin-top: 24px; }
.section h3 { font-size: 15px; margin-bottom: 10px; }
.h-item { background: #fff; border-radius: 10px; padding: 12px; margin-bottom: 6px; box-shadow: 0 1px 2px rgba(0,0,0,.04); }
.h-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px; }
.h-room { font-size: 14px; font-weight: 600; }
.h-reading { font-size: 16px; font-weight: 600; color: #2563eb; }
.h-bottom { display: flex; gap: 12px; font-size: 12px; color: #6b7280; }
.h-by { margin-left: auto; color: #9ca3af; }

.empty { text-align: center; padding: 32px 0; color: #9ca3af; font-size: 14px; }
</style>
