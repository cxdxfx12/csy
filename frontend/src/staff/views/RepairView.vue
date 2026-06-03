<template>
  <div class="page">
    <header><button class="back" @click="$router.back()">←</button><h1>报修处理</h1></header>
    <div class="tabs">
      <button :class="{active:tab==='pending'}" @click="tab='pending';loadList()">待接单</button>
      <button :class="{active:tab==='accepted'}" @click="tab='accepted';loadList()">处理中</button>
      <button :class="{active:tab==='finished'}" @click="tab='finished';loadList()">已完成</button>
    </div>
    <div v-if="loading" class="loading">加载中...</div>
    <div v-else-if="!list.length" class="empty">暂无工单</div>
    <div v-else class="list">
      <div v-for="item in list" :key="item.id" class="item">
        <div class="item-hd">
          <span class="tag" :style="{color:stColor(item.status)}">{{ stText(item.status) }}</span>
          <span class="time">{{ item.create_time }}</span>
        </div>
        <div class="item-title">{{ item.title || item.content || '报修工单' }}</div>
        <div class="item-info">
          <span>🏘️ {{ item.community_name || '--' }}</span>
          <span>🏠 {{ item.room_no || '--' }}</span>
          <span>👤 {{ item.contact || '--' }}</span>
        </div>
        <div class="item-actions">
          <button v-if="tab==='pending'" class="btn-act btn-a" @click="acceptRepair(item.id)">接单</button>
          <button v-if="tab==='accepted'" class="btn-act btn-b" @click="finishRepair(item.id)">完工</button>
          <button class="btn-act btn-c" @click="showDetail(item)">详情</button>
        </div>
      </div>
    </div>
    <!-- Detail Modal -->
    <div class="modal" v-if="detail" @click.self="detail=null">
      <div class="modal-content">
        <h3>工单详情</h3>
        <div class="detail-info">
          <p><label>标题</label> {{ detail.title || '--' }}</p>
          <p><label>描述</label> {{ detail.content || detail.description || '--' }}</p>
          <p><label>小区</label> {{ detail.community_name || '--' }}</p>
          <p><label>房号</label> {{ detail.room_no || '--' }}</p>
          <p><label>联系人</label> {{ detail.contact || '--' }}</p>
          <p><label>电话</label> {{ detail.phone || '--' }}</p>
          <p><label>状态</label> {{ stText(detail.status) }}</p>
          <p><label>报修时间</label> {{ detail.create_time || '--' }}</p>
        </div>
        <button class="btn-primary" @click="detail=null">关闭</button>
      </div>
    </div>
  </div>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import { createApi } from '@/shared/api.js'
import { showToast, statusLabels, statusColors } from '@/shared/utils.js'
const api = createApi('/api/staff', 'staff_token')
const tab = ref('pending')
const list = ref([])
const loading = ref(false)
const detail = ref(null)

const stText = s => statusLabels[s] || s
const stColor = s => statusColors[s] || '#999'

onMounted(loadList)
async function loadList() {
  loading.value = true
  const res = await api('/repair/list?status=' + tab.value)
  if (res.code === 0) list.value = Array.isArray(res.data) ? res.data : (res.data?.list || [])
  loading.value = false
}
async function acceptRepair(id) {
  const res = await api('/repair/accept', { method: 'POST', body: JSON.stringify({ id }) })
  showToast(res.code === 0 ? '接单成功' : res.msg)
  if (res.code === 0) loadList()
}
async function finishRepair(id) {
  const res = await api('/repair/finish', { method: 'POST', body: JSON.stringify({ id }) })
  showToast(res.code === 0 ? '完工成功' : res.msg)
  if (res.code === 0) loadList()
}
async function showDetail(item) {
  const res = await api('/repair/detail?id=' + item.id)
  detail.value = res.code === 0 ? res.data : item
}
</script>
<style scoped>
.page{padding:16px}
header{display:flex;align-items:center;gap:12px;margin-bottom:16px}
header h1{font-size:18px}
.back{background:none;border:none;font-size:20px;cursor:pointer;padding:4px 8px}
.tabs{display:flex;gap:8px;margin-bottom:16px}
.tabs button{flex:1;padding:10px;border:1px solid #e5e7eb;border-radius:8px;background:#fff;font-size:13px;cursor:pointer}
.tabs button.active{background:#2563eb;color:#fff;border-color:#2563eb}
.loading,.empty{text-align:center;padding:40px 0;color:#9ca3af}
.list{display:flex;flex-direction:column;gap:12px}
.item{background:#fff;border-radius:12px;padding:16px;box-shadow:0 1px 3px rgba(0,0,0,.06)}
.item-hd{display:flex;justify-content:space-between;margin-bottom:8px}
.tag{font-size:12px;font-weight:600}
.time{font-size:12px;color:#9ca3af}
.item-title{font-size:15px;font-weight:600;margin-bottom:8px}
.item-info{display:flex;gap:12px;font-size:12px;color:#6b7280;margin-bottom:12px}
.item-actions{display:flex;gap:8px}
.btn-act{padding:8px 16px;border:none;border-radius:6px;font-size:13px;cursor:pointer}
.btn-a{background:#2563eb;color:#fff}.btn-b{background:#10b981;color:#fff}.btn-c{background:#f3f4f6;color:#374151}
.modal{position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,.4);display:flex;align-items:center;justify-content:center;z-index:200;padding:20px}
.modal-content{background:#fff;border-radius:16px;padding:24px;width:100%;max-width:400px;max-height:80vh;overflow-y:auto}
.modal-content h3{font-size:18px;margin-bottom:16px}
.detail-info p{margin:8px 0;font-size:14px}
.detail-info label{color:#6b7280;margin-right:8px;min-width:60px;display:inline-block}
.btn-primary{width:100%;height:44px;background:#2563eb;color:#fff;border:none;border-radius:8px;font-size:15px;margin-top:16px;cursor:pointer}
</style>
