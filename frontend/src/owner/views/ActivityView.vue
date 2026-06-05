<template>
  <div class="page">
    <header>
      <button class="back" @click="$router.back()">←</button>
      <h1>社区活动</h1>
      <button type="button" class="btn-sm" :class="{ active: tab === 'my' }" @click.prevent="tab = tab === 'all' ? 'my' : 'all'">
        {{ tab === 'all' ? '我的' : '全部' }}
      </button>
    </header>

    <!-- 全部活动 -->
    <div v-if="tab === 'all'">
      <div v-if="!list.length" class="empty">暂无社区活动</div>
      <div v-else class="list">
        <div v-for="item in list" :key="item.id" class="item" :class="{ expanded: item.id === detailId }" @click="toggleDetail(item)">
          <div class="item-hd">
            <span class="title">{{ item.title }}</span>
            <span class="tag" :style="{ background: statusColor(item.status), color: '#fff' }">
              {{ statusLabel(item.status) }}
            </span>
          </div>
          <div class="item-info">
            <p>📍 {{ item.location || '待定' }}</p>
            <p>🕐 {{ item.start_time ? item.start_time.slice(0, 16) : '待定' }}</p>
            <p>👥 {{ item.signup_count }}{{ item.max_participants > 0 ? `/${item.max_participants}` : '' }} 人报名
              <span v-if="item.has_signed && item.signup_status === 1" class="signed approved">（已通过 ✓）</span>
              <span v-else-if="item.has_signed && item.signup_status === 2" class="signed rejected">（已拒绝）</span>
              <span v-else-if="item.has_signed" class="signed pending">（待审核）</span>
            </p>
          </div>

          <!-- 展开详情 -->
          <div v-if="item.id === detailId" class="item-detail">
            <div class="content" v-if="item.content" v-html="item.content"></div>
            <p class="time-end" v-if="item.end_time">活动结束时间: {{ item.end_time.slice(0, 16) }}</p>

            <div class="actions">
              <button v-if="(item.status === 2 || item.status === 3) && !item.has_signed" class="btn-signup" @click.stop="doSignup(item)">
                立即报名
              </button>
              <button v-if="(item.status === 2 || item.status === 3) && item.has_signed" class="btn-cancel" @click.stop="doCancel(item)">
                取消报名
              </button>
              <span v-if="item.status === 1 || item.status === 4 || item.status === 5" class="status-text">{{ statusLabel(item.status) }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- 我的报名 -->
    <div v-if="tab === 'my'">
      <div v-if="!myList.length" class="empty">暂无报名记录</div>
      <div v-else class="list">
        <div v-for="s in myList" :key="s.id" class="item my-item">
          <div class="item-hd">
            <span class="title">{{ s.activity_title }}</span>
            <span class="tag" :style="{ background: statusColor(s.activity_status), color: '#fff' }">
              {{ statusLabel(s.activity_status) }}
            </span>
          </div>
          <div class="item-info">
            <p>📍 {{ s.location || '待定' }}</p>
            <p>📅 {{ s.create_time ? s.create_time.slice(0, 10) : '--' }} 报名</p>
            <p>
              <span :class="'signup-tag ' + signupTagClass(s.status)">{{ signupStatusLabel(s.status) }}</span>
            </p>
          </div>
          <button v-if="(s.activity_status === 2 || s.activity_status === 3) && (s.status !== 1)" class="btn-cancel-sm" @click="doCancelById(s.activity_id, s.id)">取消报名</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { createApi } from '@/shared/api.js'
import { showToast } from '@/shared/utils.js'

const api = createApi('/api/api', 'owner_token')
const list = ref([])
const myList = ref([])
const tab = ref('all')
const detailId = ref(0)

const statusMap = { 1: '草稿', 2: '报名中', 3: '进行中', 4: '已结束', 5: '已取消' }
const colorMap = { 1: '#9ca3af', 2: '#10b981', 3: '#3b82f6', 4: '#6b7280', 5: '#ef4444' }
const signupStatusMap = { 0: '待审核', 1: '已通过', 2: '已拒绝' }

function statusLabel(s) { return statusMap[s] || '未知' }
function statusColor(s) { return colorMap[s] || '#9ca3af' }
function signupStatusLabel(s) { return signupStatusMap[s] || '待审核' }
function signupTagClass(s) { return s === 1 ? 'approved' : s === 2 ? 'rejected' : 'pending' }

onMounted(async () => {
  const [a, m] = await Promise.all([api('/activity/list'), api('/activity/mySignups')])
  if (a.code === 0) list.value = Array.isArray(a.data) ? a.data : []
  if (m.code === 0) myList.value = Array.isArray(m.data) ? m.data : []
})

function toggleDetail(item) {
  detailId.value = detailId.value === item.id ? 0 : item.id
}

async function doSignup(item) {
  const res = await api('/activity/signup', {
    method: 'POST',
    body: JSON.stringify({ activity_id: item.id })
  })
  if (res.code === 0) {
    showToast('报名成功')
    item.has_signed = true
    item.signup_count = (item.signup_count || 0) + 1
    const m = await api('/activity/mySignups')
    if (m.code === 0) myList.value = Array.isArray(m.data) ? m.data : []
  } else {
    showToast(res.msg || '报名失败')
  }
}

async function doCancel(item) {
  const res = await api('/activity/cancelSignup', {
    method: 'POST',
    body: JSON.stringify({ activity_id: item.id })
  })
  if (res.code === 0) {
    showToast('已取消报名')
    item.has_signed = false
    item.signup_count = Math.max(0, (item.signup_count || 1) - 1)
    const m = await api('/activity/mySignups')
    if (m.code === 0) myList.value = Array.isArray(m.data) ? m.data : []
  } else {
    showToast(res.msg || '取消失败')
  }
}

async function doCancelById(activityId, signupId) {
  const res = await api('/activity/cancelSignup', {
    method: 'POST',
    body: JSON.stringify({ activity_id: activityId })
  })
  if (res.code === 0) {
    showToast('已取消报名')
    myList.value = myList.value.filter(s => s.id !== signupId)
    const a = await api('/activity/list')
    if (a.code === 0) list.value = Array.isArray(a.data) ? a.data : []
  } else {
    showToast(res.msg || '取消失败')
  }
}
</script>

<style scoped>
.page { padding: 16px; }
header { display: flex; align-items: center; gap: 12px; margin-bottom: 16px; }
header h1 { font-size: 18px; flex: 1; }
.back { background: none; border: none; font-size: 20px; cursor: pointer; padding: 4px 8px; }
.btn-sm { background: none; border: 1px solid #2563eb; color: #2563eb; padding: 6px 14px; border-radius: 6px; font-size: 13px; cursor: pointer; }
.btn-sm.active { background: #2563eb; color: #fff; }
.empty { text-align: center; padding: 40px; color: #9ca3af; }
.list { display: flex; flex-direction: column; gap: 10px; }
.item { background: #fff; border-radius: 12px; padding: 16px; box-shadow: 0 1px 3px rgba(0, 0, 0, .06); cursor: pointer; transition: all .2s; }
.item:active { transform: scale(.98); }
.item-hd { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px; }
.title { font-size: 15px; font-weight: 600; flex: 1; }
.tag { font-size: 11px; padding: 2px 10px; border-radius: 12px; white-space: nowrap; flex-shrink: 0; margin-left: 8px; }
.item-info p { margin: 4px 0; font-size: 13px; color: #6b7280; }
.signed { color: #10b981; font-weight: 500; }
.signed.approved { color: #10b981; }
.signed.pending { color: #f59e0b; }
.signed.rejected { color: #ef4444; }
.signup-tag { font-size: 12px; padding: 2px 8px; border-radius: 10px; font-weight: 500; }
.signup-tag.approved { background: #d1fae5; color: #065f46; }
.signup-tag.pending { background: #fef3c7; color: #92400e; }
.signup-tag.rejected { background: #fee2e2; color: #991b1b; }
.item-detail { margin-top: 12px; padding-top: 12px; border-top: 1px solid #f3f4f6; }
.item-detail .content { font-size: 14px; color: #4b5563; line-height: 1.6; }
.time-end { font-size: 12px; color: #9ca3af; margin-top: 8px; }
.actions { margin-top: 14px; display: flex; gap: 10px; }
.btn-signup { flex: 1; height: 40px; background: #2563eb; color: #fff; border: none; border-radius: 8px; font-size: 15px; cursor: pointer; }
.btn-signup:active { background: #1d4ed8; }
.btn-cancel { flex: 1; height: 40px; background: #fef2f2; color: #ef4444; border: 1px solid #fecaca; border-radius: 8px; font-size: 15px; cursor: pointer; }
.btn-cancel:active { background: #fee2e2; }
.btn-cancel-sm { margin-top: 8px; padding: 6px 16px; background: #fef2f2; color: #ef4444; border: 1px solid #fecaca; border-radius: 6px; font-size: 13px; cursor: pointer; }
.status-text { font-size: 14px; color: #6b7280; line-height: 40px; }
.my-item { cursor: default; }
</style>
