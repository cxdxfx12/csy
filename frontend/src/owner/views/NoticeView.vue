<template>
  <div class="page">
    <header><button class="back" @click="$router.back()">←</button><h1>社区公告</h1></header>
    <div v-if="!list.length" class="empty">暂无公告</div>
    <div v-else class="list">
      <div v-for="item in list" :key="item.id" class="item" @click="showDetail(item)">
        <div class="title">{{ item.title }}</div>
        <div class="preview">{{ (item.content||'').substring(0, 80) }}...</div>
        <div class="time">{{ item.create_time }}</div>
      </div>
    </div>
    <div class="modal" v-if="detail" @click.self="detail=null">
      <div class="modal-content">
        <h3>{{ detail.title }}</h3>
        <div class="detail-body">{{ detail.content }}</div>
        <div class="detail-time">{{ detail.create_time }}</div>
        <button class="btn-primary" @click="detail=null">关闭</button>
      </div>
    </div>
  </div>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import { createApi } from '@/shared/api.js'
const api = createApi('/api/api', 'owner_token')
const list = ref([])
const detail = ref(null)

onMounted(async () => {
  const res = await api('/notice/list')
  if (res.code === 0) list.value = Array.isArray(res.data) ? res.data : (res.data?.list || [])
})
async function showDetail(item) {
  const res = await api('/notice/detail?id=' + item.id)
  detail.value = res.code === 0 ? res.data : item
}
</script>
<style scoped>
.page{padding:16px}
header{display:flex;align-items:center;gap:12px;margin-bottom:16px}
header h1{font-size:18px}
.back{background:none;border:none;font-size:20px;cursor:pointer;padding:4px 8px}
.empty{text-align:center;padding:40px;color:#9ca3af}
.list{display:flex;flex-direction:column;gap:10px}
.item{background:#fff;border-radius:12px;padding:16px;box-shadow:0 1px 3px rgba(0,0,0,.06);cursor:pointer;transition:transform .15s}
.item:active{transform:scale(.98)}
.title{font-size:15px;font-weight:600;margin-bottom:8px}
.preview{font-size:13px;color:#6b7280;margin-bottom:8px;line-height:1.5}
.time{font-size:12px;color:#9ca3af}
.modal{position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,.4);display:flex;align-items:center;justify-content:center;z-index:200;padding:20px}
.modal-content{background:#fff;border-radius:16px;padding:24px;width:100%;max-width:400px;max-height:80vh;overflow-y:auto}
.modal-content h3{font-size:18px;margin-bottom:14px}
.detail-body{font-size:14px;line-height:1.7;color:#374151;margin-bottom:14px;white-space:pre-wrap}
.detail-time{font-size:12px;color:#9ca3af;text-align:right}
.btn-primary{width:100%;height:44px;background:#2563eb;color:#fff;border:none;border-radius:8px;font-size:15px;margin-top:16px;cursor:pointer}
</style>
