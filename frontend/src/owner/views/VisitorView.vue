<template>
  <div class="page">
    <header>
      <button class="back" @click="$router.back()">←</button>
      <h1>访客邀请</h1>
      <button class="btn-sm" @click="showForm=!showForm">{{ showForm?'取消':'邀请' }}</button>
    </header>
    <div v-if="showForm" class="form-card">
      <h3>邀请访客</h3>
      <input v-model="form.name" placeholder="访客姓名" />
      <input v-model="form.phone" placeholder="访客手机号" />
      <input v-model="form.reason" placeholder="来访事由" />
      <input v-model="form.visit_time" placeholder="预计到访时间" />
      <button class="btn-primary" @click="submitVisitor" :disabled="loading">
        {{ loading ? '提交中...' : '发送邀请' }}
      </button>
    </div>
    <div v-if="!list.length" class="empty">暂无访客记录</div>
    <div v-else class="list">
      <div v-for="item in list" :key="item.id" class="item">
        <div class="item-hd">
          <span class="title">{{ item.name }}</span>
          <span class="tag">{{ item.status || '已登记' }}</span>
        </div>
        <div class="item-body">
          <p>📱 {{ item.phone }}</p>
          <p>📝 {{ item.reason || '--' }}</p>
          <p>🕐 {{ item.visit_time || item.create_time || '--' }}</p>
        </div>
      </div>
    </div>
  </div>
</template>
<script setup>
import { ref, onMounted, reactive } from 'vue'
import { createApi } from '@/shared/api.js'
import { showToast } from '@/shared/utils.js'
const api = createApi('/api/api', 'owner_token')
const form = reactive({ name: '', phone: '', reason: '', visit_time: '' })
const list = ref([])
const showForm = ref(false)
const loading = ref(false)

onMounted(async () => {
  const res = await api('/visitor/list')
  if (res.code === 0) list.value = Array.isArray(res.data) ? res.data : (res.data?.list || [])
})

async function submitVisitor() {
  if (!form.name || !form.phone) return showToast('请填写访客信息')
  loading.value = true
  const res = await api('/visitor/add', { method: 'POST', body: JSON.stringify(form) })
  loading.value = false
  showToast(res.code === 0 ? '邀请成功' : (res.msg || '失败'))
  if (res.code === 0) {
    showForm.value = false
    Object.keys(form).forEach(k => form[k] = '')
    const r = await api('/visitor/list')
    if (r.code === 0) list.value = Array.isArray(r.data) ? r.data : (r.data?.list || [])
  }
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
input{width:100%;height:44px;border:1px solid #e5e7eb;border-radius:8px;padding:0 14px;font-size:14px;margin-bottom:11px;outline:none}
input:focus{border-color:#2563eb}
.btn-primary{width:100%;height:44px;background:#2563eb;color:#fff;border:none;border-radius:8px;font-size:15px;cursor:pointer}
.btn-primary:disabled{opacity:.6}
.empty{text-align:center;padding:40px;color:#9ca3af}
.list{display:flex;flex-direction:column;gap:10px}
.item{background:#fff;border-radius:12px;padding:16px;box-shadow:0 1px 3px rgba(0,0,0,.06)}
.item-hd{display:flex;justify-content:space-between;margin-bottom:8px}
.title{font-size:15px;font-weight:600}
.tag{font-size:11px;background:#ecfdf5;color:#10b981;padding:2px 10px;border-radius:12px}
.item-body p{margin:3px 0;font-size:13px;color:#6b7280}
</style>
