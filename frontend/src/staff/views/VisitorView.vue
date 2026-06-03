<template>
  <div class="page">
    <header><button class="back" @click="$router.back()">←</button><h1>访客登记</h1></header>
    <div class="form-card">
      <h3>新增访客</h3>
      <input v-model="form.name" placeholder="访客姓名" />
      <input v-model="form.phone" placeholder="手机号码" />
      <input v-model="form.reason" placeholder="来访事由" />
      <input v-model="form.owner_room" placeholder="被访房号" />
      <button class="btn-primary" @click="addVisitor" :disabled="loading">
        {{ loading ? '提交中...' : '登记访客' }}
      </button>
    </div>
    <div class="section" style="margin-top:20px">
      <h3>访客记录</h3>
      <div v-if="!visitors.length" class="empty">暂无记录</div>
      <div v-for="v in visitors" :key="v.id" class="v-item">
        <div class="v-name">{{ v.name }} <span class="tag">{{ v.status || '已登记' }}</span></div>
        <div class="v-info">📱 {{ v.phone }} | 🏠 {{ v.owner_room || '--' }} | 📝 {{ v.reason || '--' }}</div>
        <div class="v-time">{{ v.create_time }}</div>
      </div>
    </div>
  </div>
</template>
<script setup>
import { ref, onMounted, reactive } from 'vue'
import { createApi } from '@/shared/api.js'
import { showToast } from '@/shared/utils.js'
const api = createApi('/api/staff', 'staff_token')
const visitors = ref([])
const loading = ref(false)
const form = reactive({ name: '', phone: '', reason: '', owner_room: '' })

onMounted(loadVisitors)
async function loadVisitors() {
  const res = await api('/visitor/list')
  if (res.code === 0) visitors.value = Array.isArray(res.data) ? res.data : (res.data?.list || [])
}
async function addVisitor() {
  if (!form.name || !form.phone) return showToast('请填写完整信息')
  loading.value = true
  const res = await api('/visitor/add', { method: 'POST', body: JSON.stringify(form) })
  loading.value = false
  showToast(res.code === 0 ? '登记成功' : (res.msg || '失败'))
  if (res.code === 0) { form.name = form.phone = form.reason = form.owner_room = ''; loadVisitors() }
}
</script>
<style scoped>
.page{padding:16px}
header{display:flex;align-items:center;gap:12px;margin-bottom:16px}
header h1{font-size:18px}
.back{background:none;border:none;font-size:20px;cursor:pointer;padding:4px 8px}
.form-card{background:#fff;border-radius:12px;padding:20px;box-shadow:0 1px 3px rgba(0,0,0,.06)}
.form-card h3{font-size:16px;margin-bottom:14px}
input{width:100%;height:44px;border:1px solid #e5e7eb;border-radius:8px;padding:0 14px;font-size:14px;margin-bottom:12px;outline:none}
input:focus{border-color:#2563eb}
.btn-primary{width:100%;height:44px;background:#2563eb;color:#fff;border:none;border-radius:8px;font-size:15px;cursor:pointer}
.btn-primary:disabled{opacity:.6}
.section h3{font-size:16px;margin-bottom:12px}
.empty{text-align:center;padding:40px;color:#9ca3af}
.v-item{background:#fff;border-radius:10px;padding:12px 14px;margin-bottom:8px;box-shadow:0 1px 2px rgba(0,0,0,.04)}
.v-name{font-size:15px;font-weight:600;margin-bottom:6px}
.v-name .tag{font-size:11px;background:#ecfdf5;color:#10b981;padding:2px 8px;border-radius:4px;margin-left:6px}
.v-info{font-size:12px;color:#6b7280;margin-bottom:4px}
.v-time{font-size:11px;color:#9ca3af}
</style>
