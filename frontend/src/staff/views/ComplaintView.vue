<template>
  <div class="page">
    <header>
      <button class="back" @click="$router.back()">←</button>
      <h1>投诉处理</h1>
    </header>
    <div class="tabs">
      <button :class="{active:tab===1}" @click="tab=1;loadList()">待处理<span v-if="counts[1]">({{ counts[1] }})</span></button>
      <button :class="{active:tab===2}" @click="tab=2;loadList()">处理中<span v-if="counts[2]">({{ counts[2] }})</span></button>
      <button :class="{active:tab===3}" @click="tab=3;loadList()">已处理</button>
    </div>
    <div v-if="loading" class="loading">加载中...</div>
    <div v-else-if="!list.length" class="empty">暂无投诉</div>
    <div v-else class="list">
      <div v-for="item in list" :key="item.id" class="item">
        <div class="item-hd">
          <span class="tag" :style="{color:stColor(item.status)}">{{ stText(item.status) }}</span>
          <span class="time">{{ item.create_time }}</span>
        </div>
        <div class="item-title">{{ item.title || '投诉建议' }}</div>
        <div class="item-body">{{ item.content }}</div>
        <div class="item-info" v-if="item.owner_name">
          <span>👤 {{ item.owner_name }}</span>
          <span v-if="item.room_number">🏠 {{ item.room_number }}</span>
        </div>
        <div class="item-actions">
          <button v-if="tab===1" class="btn-act btn-a" @click="showHandle(item)">处理</button>
          <button v-if="tab===2" class="btn-act btn-b" @click="showHandle(item)">完成</button>
          <button class="btn-act btn-c" @click="showDetail(item)">详情</button>
        </div>
      </div>
    </div>

    <!-- Detail Modal -->
    <div class="modal" v-if="detail" @click.self="detail=null">
      <div class="modal-box">
        <h3>投诉详情</h3>
        <div class="detail-content">
          <p><label>标题：</label>{{ detail.title }}</p>
          <p><label>内容：</label>{{ detail.content }}</p>
          <p><label>业主：</label>{{ detail.owner_name || '--' }}</p>
          <p><label>房间：</label>{{ detail.room_number || '--' }} {{ detail.building_name || '' }}</p>
          <p><label>电话：</label>{{ detail.complaint_phone || detail.owner_phone || '--' }}</p>
          <p><label>类型：</label>{{ typeMap[detail.type] || '其他' }}</p>
          <p><label>时间：</label>{{ detail.create_time }}</p>
          <div v-if="detail.handle_content">
            <p><label>处理内容：</label>{{ detail.handle_content }}</p>
            <p><label>处理人：</label>{{ detail.handler_name }} · {{ detail.handle_time }}</p>
          </div>
        </div>
        <button class="btn-primary" @click="detail=null">关闭</button>
      </div>
    </div>

    <!-- Handle Modal -->
    <div class="modal" v-if="curItem" @click.self="curItem=null">
      <div class="modal-box">
        <h3>{{ tab===2 ? '完成处理' : '处理投诉' }}</h3>
        <p class="handle-tip">标题：{{ curItem.title || '投诉建议' }}</p>
        <textarea v-model="handleContent" placeholder="请输入处理内容..." rows="4"></textarea>
        <button class="btn-primary" @click="submitHandle" :disabled="handling">
          {{ handling ? '提交中...' : (tab===2 ? '确认为已处理' : '提交处理') }}
        </button>
        <button class="btn-cancel" @click="curItem=null">取消</button>
      </div>
    </div>
  </div>
</template>
<script setup>
import { ref, reactive, onMounted } from 'vue'
import { createApi } from '@/shared/api.js'
import { showToast } from '@/shared/utils.js'
const api = createApi('/api/staff', 'staff_token')

const tab = ref(1)
const list = ref([])
const loading = ref(false)
const detail = ref(null)
const curItem = ref(null)
const handleContent = ref('')
const handling = ref(false)
const counts = reactive({ 1: 0, 2: 0, 3: 0 })

const typeMap = { 1: '投诉', 2: '建议', 3: '表扬', 4: '咨询' }
const statusLabels = { 1: '待处理', 2: '处理中', 3: '已处理', 4: '已关闭', 5: '已评价' }
const statusColors = { 1: '#f59e0b', 2: '#3b82f6', 3: '#10b981', 4: '#6b7280', 5: '#8b5cf6' }
const stText = s => statusLabels[s] || s
const stColor = s => statusColors[s] || '#999'

onMounted(async () => {
  await loadCounts()
  loadList()
})

async function loadCounts() {
  try {
    for (const s of [1, 2, 3]) {
      const res = await api(`/complaint/list?status=${s}&limit=1`)
      if (res.code === 0) counts[s] = res.data.total || 0
    }
  } catch {}
}

async function loadList() {
  loading.value = true
  try {
    const res = await api(`/complaint/list?status=${tab.value}`)
    if (res.code === 0) list.value = res.data.list || []
  } catch { list.value = [] } finally { loading.value = false }
}

async function showDetail(item) {
  try {
    const res = await api(`/complaint/detail?id=${item.id}`)
    if (res.code === 0) detail.value = res.data
    else showToast('获取详情失败')
  } catch { showToast('获取详情失败') }
}

function showHandle(item) {
  curItem.value = item
  handleContent.value = ''
}

async function submitHandle() {
  if (!handleContent.value.trim()) return showToast('请填写处理内容')
  handling.value = true
  try {
    const status = tab.value === 2 ? 3 : 2 // 从处理中点"完成"→status=3；从待处理点"处理"→status=2
    const res = await api('/complaint/handle', {
      method: 'POST',
      body: JSON.stringify({ id: curItem.value.id, handle_content: handleContent.value, status })
    })
    if (res.code === 0) {
      showToast('处理成功')
      curItem.value = null
      await loadCounts()
      loadList()
    } else {
      showToast(res.msg || '处理失败')
    }
  } catch { showToast('处理失败') } finally { handling.value = false }
}
</script>
<style scoped>
.page{padding:16px}
header{display:flex;align-items:center;gap:12px;margin-bottom:14px}
header h1{font-size:18px;flex:1}
.back{background:none;border:none;font-size:20px;cursor:pointer;padding:4px 8px}
.tabs{display:flex;gap:8px;margin-bottom:16px}
.tabs button{flex:1;padding:10px 0;border:1px solid #e5e7eb;background:#fff;border-radius:8px;font-size:14px;cursor:pointer;transition:all .2s}
.tabs button.active{background:#2563eb;color:#fff;border-color:#2563eb}
.tabs button span{font-size:12px}
.loading,.empty{text-align:center;padding:40px;color:#9ca3af}
.list{display:flex;flex-direction:column;gap:12px}
.item{background:#fff;border-radius:12px;padding:16px;box-shadow:0 1px 3px rgba(0,0,0,.06)}
.item-hd{display:flex;justify-content:space-between;margin-bottom:8px}
.tag{font-size:12px;font-weight:600}
.time{font-size:12px;color:#9ca3af}
.item-title{font-size:15px;font-weight:600;margin-bottom:6px}
.item-body{font-size:13px;color:#6b7280;margin-bottom:8px;line-height:1.4}
.item-info{display:flex;gap:12px;font-size:12px;color:#9ca3af;margin-bottom:10px}
.item-actions{display:flex;gap:8px}
.btn-act{padding:6px 14px;border:none;border-radius:6px;font-size:13px;cursor:pointer}
.btn-a{background:#e0f2fe;color:#0369a1}
.btn-b{background:#dcfce7;color:#166534}
.btn-c{background:#f3f4f6;color:#6b7280}
.modal{position:fixed;inset:0;background:rgba(0,0,0,.4);display:flex;align-items:center;justify-content:center;z-index:100;padding:20px}
.modal-box{background:#fff;border-radius:16px;padding:24px;width:100%;max-width:400px;max-height:80vh;overflow-y:auto}
.modal-box h3{font-size:17px;margin-bottom:14px}
.detail-content p{margin-bottom:8px;font-size:14px}
.detail-content label{color:#6b7280;margin-right:4px}
.handle-tip{font-size:14px;color:#4b5563;margin-bottom:10px}
textarea{width:100%;border:1px solid #e5e7eb;border-radius:8px;padding:10px 14px;font-size:14px;margin-bottom:12px;outline:none;font-family:inherit;resize:vertical}
textarea:focus{border-color:#2563eb}
.btn-primary{width:100%;height:44px;background:#2563eb;color:#fff;border:none;border-radius:8px;font-size:15px;cursor:pointer;margin-bottom:8px}
.btn-primary:disabled{opacity:.6}
.btn-cancel{width:100%;height:40px;background:#fff;color:#6b7280;border:1px solid #e5e7eb;border-radius:8px;font-size:14px;cursor:pointer}
</style>
