<template>
  <div class="page">
    <header>
      <button class="back" @click="$router.back()">←</button>
      <h1>工单管理</h1>
      <button class="btn-sm" @click="showForm=!showForm">{{showForm?'取消':'新建'}}</button>
    </header>
    <!-- Tabs -->
    <div class="tabs">
      <button :class="{active:tab===''}" @click="tab='';loadList()">全部</button>
      <button :class="{active:tab==='1'}" @click="tab='1';loadList()">待处理</button>
      <button :class="{active:tab==='2'}" @click="tab='2';loadList()">已派单</button>
      <button :class="{active:tab==='3'}" @click="tab='3';loadList()">处理中</button>
      <button :class="{active:tab==='4'}" @click="tab='4';loadList()">待验收</button>
      <button :class="{active:tab==='5'}" @click="tab='5';loadList()">已完成</button>
    </div>
    <!-- Create Form -->
    <div v-if="showForm" class="form-card">
      <h3>新建工单</h3>
      <input v-model="form.title" placeholder="工单标题" />
      <textarea v-model="form.content" placeholder="工单内容" rows="3"></textarea>
      <button class="btn-primary" @click="createOrder" :disabled="loading">
        {{ loading ? '提交中...' : '提交工单' }}
      </button>
    </div>
    <!-- List -->
    <div v-if="!list.length" class="empty">暂无工单</div>
    <div v-else class="list">
      <div v-for="item in list" :key="item.id" class="item">
        <div class="item-hd">
          <span class="title">{{ item.title }}</span>
          <span class="tag" :style="{color:stColor(item.status)}">{{ stText(item.status) }}</span>
        </div>
        <div class="item-body">{{ item.content }}</div>
        <div class="item-info" v-if="item.community_name">
          <span>🏘️ {{ item.community_name }}</span>
          <span>📋 {{ item.order_no }}</span>
        </div>
        <div class="item-ft">
          <span>{{ item.create_time }}</span>
          <button v-if="item.status!==6 && item.status!==5" class="btn-close" @click="closeOrder(item.id)">关闭</button>
        </div>
      </div>
    </div>
  </div>
</template>
<script setup>
import { ref, onMounted, reactive } from 'vue'
import { createApi } from '@/shared/api.js'
import { showToast, statusLabels, statusColors } from '@/shared/utils.js'
const api = createApi('/api/staff', 'staff_token')
const list = ref([])
const showForm = ref(false)
const loading = ref(false)
const tab = ref('')
const form = reactive({ title: '', content: '' })
const stText = s => statusLabels[s] || s
const stColor = s => statusColors[s] || '#999'

onMounted(loadList)
async function loadList() {
  const url = tab.value ? '/order/list?status=' + tab.value : '/order/list'
  const res = await api(url)
  if (res.code === 0) list.value = Array.isArray(res.data) ? res.data : (res.data?.list || [])
}
async function createOrder() {
  if (!form.title) return showToast('请输入标题')
  loading.value = true
  const res = await api('/order/create', { method: 'POST', body: JSON.stringify(form) })
  loading.value = false
  showToast(res.code === 0 ? '创建成功' : (res.msg || '失败'))
  if (res.code === 0) { form.title = form.content = ''; showForm.value = false; loadList() }
}
async function closeOrder(id) {
  if (!confirm('确认关闭此工单？')) return
  const res = await api('/order/close', { method: 'POST', body: JSON.stringify({ id }) })
  showToast(res.code === 0 ? '已关闭' : (res.msg || '失败'))
  if (res.code === 0) loadList()
}
</script>
<style scoped>
.page{padding:16px}
header{display:flex;align-items:center;gap:12px;margin-bottom:16px}
header h1{font-size:18px;flex:1}
.back{background:none;border:none;font-size:20px;cursor:pointer;padding:4px 8px}
.btn-sm{background:none;border:1px solid #2563eb;color:#2563eb;padding:6px 14px;border-radius:6px;font-size:13px;cursor:pointer}
.tabs{display:flex;gap:6px;margin-bottom:14px;flex-wrap:wrap}
.tabs button{padding:6px 12px;border:1px solid #e5e7eb;border-radius:6px;background:#fff;font-size:12px;cursor:pointer;white-space:nowrap}
.tabs button.active{background:#2563eb;color:#fff;border-color:#2563eb}
.form-card{background:#fff;border-radius:12px;padding:20px;margin-bottom:16px;box-shadow:0 1px 3px rgba(0,0,0,.06)}
.form-card h3{font-size:16px;margin-bottom:14px}
input,textarea{width:100%;border:1px solid #e5e7eb;border-radius:8px;padding:10px 14px;font-size:14px;margin-bottom:12px;outline:none;font-family:inherit}
input:focus,textarea:focus{border-color:#2563eb}
.btn-primary{width:100%;height:44px;background:#2563eb;color:#fff;border:none;border-radius:8px;font-size:15px;cursor:pointer}
.btn-primary:disabled{opacity:.6}
.empty{text-align:center;padding:40px;color:#9ca3af}
.list{display:flex;flex-direction:column;gap:10px}
.item{background:#fff;border-radius:12px;padding:16px;box-shadow:0 1px 3px rgba(0,0,0,.06)}
.item-hd{display:flex;justify-content:space-between;margin-bottom:8px}
.title{font-size:15px;font-weight:600}
.tag{font-size:12px;font-weight:600}
.item-body{font-size:13px;color:#6b7280;margin-bottom:8px;line-height:1.5}
.item-info{display:flex;gap:12px;font-size:12px;color:#6b7280;margin-bottom:8px}
.item-ft{display:flex;justify-content:space-between;align-items:center;font-size:12px;color:#9ca3af}
.btn-close{background:#fee2e2;color:#ef4444;border:none;padding:4px 12px;border-radius:4px;font-size:12px;cursor:pointer}
</style>
