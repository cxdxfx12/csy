<template>
  <div class="page">
    <header>
      <button class="back" @click="$router.back()">←</button>
      <h1>投诉建议</h1>
      <button class="btn-sm" @click="showForm=!showForm">{{ showForm?'取消':'投诉' }}</button>
    </header>
    <div v-if="showForm" class="form-card">
      <h3>提交投诉</h3>
      <input v-model="form.title" placeholder="投诉标题" />
      <textarea v-model="form.content" placeholder="请详细描述您的问题或建议..." rows="4"></textarea>
      <input v-model="form.contact" placeholder="联系电话（选填）" />
      <button class="btn-primary" @click="submitComplaint" :disabled="loading">
        {{ loading ? '提交中...' : '提交投诉' }}
      </button>
    </div>
    <div v-if="!list.length" class="empty">暂无记录</div>
    <div v-else class="list">
      <div v-for="item in list" :key="item.id" class="item">
        <div class="item-hd">
          <span class="title">{{ item.title }}</span>
          <span class="tag" :style="{color:stColor(item.status)}">{{ stText(item.status) }}</span>
        </div>
        <div class="item-body">{{ item.content }}</div>
        <div class="item-ft">{{ item.create_time }}</div>
      </div>
    </div>
  </div>
</template>
<script setup>
import { ref, onMounted, reactive } from 'vue'
import { createApi } from '@/shared/api.js'
import { showToast, statusLabels, statusColors } from '@/shared/utils.js'
const api = createApi('/api/api', 'owner_token')
const form = reactive({ title: '', content: '', contact: '' })
const list = ref([])
const showForm = ref(false)
const loading = ref(false)
const stText = s => statusLabels[s] || s
const stColor = s => statusColors[s] || '#999'

onMounted(async () => {
  const res = await api('/complaint/list')
  if (res.code === 0) list.value = Array.isArray(res.data) ? res.data : (res.data?.list || [])
})

async function submitComplaint() {
  if (!form.title || !form.content) return showToast('请填写投诉内容')
  loading.value = true
  const res = await api('/complaint/add', { method: 'POST', body: JSON.stringify(form) })
  loading.value = false
  showToast(res.code === 0 ? '提交成功' : (res.msg || '失败'))
  if (res.code === 0) { showForm.value = false; form.title = form.content = form.contact = ''; onMounted(async () => { const r = await api('/complaint/list'); if (r.code === 0) list.value = Array.isArray(r.data) ? r.data : (r.data?.list || []) }) }
}
</script>
<style scoped>
.page{padding:16px}
header{display:flex;align-items:center;gap:12px;margin-bottom:16px}
header h1{font-size:18px;flex:1}
.back{background:none;border:none;font-size:20px;cursor:pointer;padding:4px 8px}
.btn-sm{background:none;border:1px solid #ef4444;color:#ef4444;padding:6px 14px;border-radius:6px;font-size:13px;cursor:pointer}
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
.item-ft{font-size:12px;color:#9ca3af}
</style>
