<template>
  <div class="page">
    <header><button class="back" @click="$router.back()">←</button><h1>我的房产</h1></header>
    <div v-if="!rooms.length" class="empty">暂无房产信息</div>
    <div v-else class="list">
      <div v-for="room in rooms" :key="room.id" class="item">
        <div class="item-hd">
          <span class="title">🏢 {{ room.community_name }}</span>
        </div>
        <div class="item-body">
          <p>房号：{{ room.room_no }}</p>
          <p>面积：{{ room.area || '--' }}㎡</p>
          <p>楼层：{{ room.floor || '--' }}</p>
        </div>
        <button class="btn-detail" @click="showDetail(room)">查看详情</button>
      </div>
    </div>
    <!-- Detail Modal -->
    <div class="modal" v-if="detail" @click.self="detail=null">
      <div class="modal-content">
        <h3>房产详情</h3>
        <div class="detail-info">
          <p><label>小区</label> {{ detail.community_name }}</p>
          <p><label>房号</label> {{ detail.room_no }}</p>
          <p><label>面积</label> {{ detail.area || '--' }}㎡</p>
          <p><label>楼层</label> {{ detail.floor || '--' }}</p>
          <p><label>户型</label> {{ detail.layout || '--' }}</p>
          <p><label>入住日期</label> {{ detail.move_in_date || '--' }}</p>
        </div>
        <button class="btn-primary" @click="detail=null">关闭</button>
      </div>
    </div>
  </div>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import { createApi } from '@/shared/api.js'
const api = createApi('/api/api', 'owner_token')
const rooms = ref([])
const detail = ref(null)

onMounted(async () => {
  const res = await api('/room/list')
  if (res.code === 0) rooms.value = Array.isArray(res.data) ? res.data : (res.data?.list || [])
})
async function showDetail(room) {
  const res = await api('/room/detail?id=' + room.id)
  detail.value = res.code === 0 ? res.data : room
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
.item-hd{margin-bottom:10px}
.title{font-size:16px;font-weight:600}
.item-body p{margin:4px 0;font-size:13px;color:#6b7280}
.btn-detail{width:100%;height:38px;background:#f3f4f6;border:1px solid #e5e7eb;border-radius:8px;font-size:13px;color:#374151;cursor:pointer;margin-top:10px}
.modal{position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,.4);display:flex;align-items:center;justify-content:center;z-index:200;padding:20px}
.modal-content{background:#fff;border-radius:16px;padding:24px;width:100%;max-width:400px;max-height:80vh;overflow-y:auto}
.modal-content h3{font-size:18px;margin-bottom:16px}
.detail-info p{margin:8px 0;font-size:14px}
.detail-info label{color:#6b7280;margin-right:8px;min-width:60px;display:inline-block}
.btn-primary{width:100%;height:44px;background:#2563eb;color:#fff;border:none;border-radius:8px;font-size:15px;margin-top:16px;cursor:pointer}
</style>
