<template>
  <div class="page">
    <header>
      <button class="back" @click="$router.back()">←</button>
      <h1>报事报修</h1>
      <button class="btn-sm" @click="showForm=!showForm">{{ showForm?'取消':'报修' }}</button>
    </header>
    <!-- Create Form -->
    <div v-if="showForm" class="form-card">
      <h3>提交报修</h3>
      <input v-model="form.title" placeholder="报修标题" />
      <textarea v-model="form.content" placeholder="请详细描述问题..." rows="3"></textarea>
      <input v-model="form.contact" placeholder="联系人" />
      <input v-model="form.phone" placeholder="联系电话" />
      <input v-model="form.room_no" placeholder="房号" />
      <button class="btn-primary" @click="submitRepair" :disabled="loading">
        {{ loading ? '提交中...' : '提交报修' }}
      </button>
    </div>
    <!-- List -->
    <div v-if="!repairs.length" class="empty">暂无报修记录</div>
    <div v-else class="list">
      <div v-for="item in repairs" :key="item.id" class="item">
        <div class="item-hd">
          <span class="title">{{ item.title }}</span>
          <span class="tag" :style="{color:stColor(item.status)}">{{ stText(item.status) }}</span>
        </div>
        <div class="item-body">{{ item.content || item.description || '--' }}</div>
        <div class="item-ft">
          <span>{{ item.create_time }}</span>
          <button v-if="item.status==='finished'" class="btn-eval" @click="startEval(item)">评价</button>
        </div>
      </div>
    </div>
    <!-- Evaluate Modal -->
    <div class="modal" v-if="evalItem" @click.self="evalItem=null">
      <div class="modal-content">
        <h3>服务评价</h3>
        <div class="stars">
          <span v-for="i in 5" :key="i" class="star" :class="{on:rating>=i}" @click="rating=i">{{ rating>=i?'★':'☆' }}</span>
        </div>
        <textarea v-model="evalContent" placeholder="评价内容（选填）" rows="2"></textarea>
        <button class="btn-primary" @click="doEval">提交评价</button>
      </div>
    </div>
  </div>
</template>
<script setup>
import { ref, onMounted, reactive } from 'vue'
import { createApi } from '@/shared/api.js'
import { showToast, statusLabels, statusColors } from '@/shared/utils.js'
const api = createApi('/api/api', 'owner_token')
const form = reactive({ title: '', content: '', contact: '', phone: '', room_no: '' })
const showForm = ref(false)
const loading = ref(false)
const repairs = ref([])
const evalItem = ref(null)
const rating = ref(5)
const evalContent = ref('')
const stText = s => statusLabels[s] || s
const stColor = s => statusColors[s] || '#999'

onMounted(async () => {
  const res = await api('/repair/list')
  if (res.code === 0) repairs.value = Array.isArray(res.data) ? res.data : (res.data?.list || [])
})

async function submitRepair() {
  if (!form.title || !form.content) return showToast('请填写标题和内容')
  loading.value = true
  const res = await api('/repair/add', { method: 'POST', body: JSON.stringify(form) })
  loading.value = false
  showToast(res.code === 0 ? '提交成功' : (res.msg || '失败'))
  if (res.code === 0) { showForm.value = false; Object.keys(form).forEach(k => form[k] = ''); refreshList() }
}

async function refreshList() {
  const res = await api('/repair/list')
  if (res.code === 0) repairs.value = Array.isArray(res.data) ? res.data : (res.data?.list || [])
}

function startEval(item) { evalItem.value = item; rating.value = 5; evalContent.value = '' }
async function doEval() {
  const res = await api('/repair/evaluate', { method: 'POST', body: JSON.stringify({ id: evalItem.value.id, rating: rating.value, content: evalContent.value }) })
  showToast(res.code === 0 ? '评价成功' : (res.msg || '失败'))
  if (res.code === 0) { evalItem.value = null; refreshList() }
}
</script>
<style scoped>
.page{padding:16px}
header{display:flex;align-items:center;gap:12px;margin-bottom:16px}
header h1{font-size:18px;flex:1}
.back{background:none;border:none;font-size:20px;cursor:pointer;padding:4px 8px}
.btn-sm{background:none;border:1px solid #2563eb;color:#2563eb;padding:6px 14px;border-radius:6px;font-size:13px;cursor:pointer}
.form-card{background:#fff;border-radius:12px;padding:18px;margin-bottom:16px;box-shadow:0 1px 3px rgba(0,0,0,.06)}
.form-card h3{font-size:16px;margin-bottom:14px}
input,textarea{width:100%;border:1px solid #e5e7eb;border-radius:8px;padding:10px 14px;font-size:14px;margin-bottom:10px;outline:none;font-family:inherit}
input:focus,textarea:focus{border-color:#2563eb}
.btn-primary{width:100%;height:44px;background:#2563eb;color:#fff;border:none;border-radius:8px;font-size:15px;cursor:pointer}
.btn-primary:disabled{opacity:.6}
.empty{text-align:center;padding:40px;color:#9ca3af}
.list{display:flex;flex-direction:column;gap:12px}
.item{background:#fff;border-radius:12px;padding:16px;box-shadow:0 1px 3px rgba(0,0,0,.06)}
.item-hd{display:flex;justify-content:space-between;margin-bottom:8px}
.title{font-size:15px;font-weight:600}
.tag{font-size:12px;font-weight:600}
.item-body{font-size:13px;color:#6b7280;margin-bottom:10px;line-height:1.5}
.item-ft{display:flex;justify-content:space-between;align-items:center;font-size:12px;color:#9ca3af}
.btn-eval{background:#fef3c7;color:#d97706;border:none;padding:4px 14px;border-radius:4px;font-size:12px;cursor:pointer}
.modal{position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,.4);display:flex;align-items:center;justify-content:center;z-index:200;padding:20px}
.modal-content{background:#fff;border-radius:16px;padding:24px;width:100%;max-width:360px}
.modal-content h3{font-size:18px;margin-bottom:16px;text-align:center}
.stars{text-align:center;margin-bottom:14px}
.star{font-size:32px;color:#e5e7eb;cursor:pointer;transition:color .2s}
.star.on{color:#f59e0b}
</style>
