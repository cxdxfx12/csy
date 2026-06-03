<template>
  <div class="premium-page">
    <!-- 消息概览 -->
    <div class="stat-bar">
      <div class="sb-card sb-total">
        <div class="sb-icon"><el-icon><ChatLineSquare /></el-icon></div>
        <div><div class="sb-num">{{ stats.total }}</div><div class="sb-label">消息总数</div></div>
      </div>
      <div class="sb-card sb-read">
        <div class="sb-icon"><el-icon><Check /></el-icon></div>
        <div><div class="sb-num">{{ stats.read }}</div><div class="sb-label">已读</div></div>
      </div>
      <div class="sb-card sb-unread">
        <div class="sb-icon"><el-icon><Message /></el-icon></div>
        <div><div class="sb-num">{{ stats.unread }}</div><div class="sb-label">未读</div></div>
      </div>
      <div class="sb-card sb-rate">
        <div class="sb-icon"><el-icon><DataAnalysis /></el-icon></div>
        <div><div class="sb-num">{{ stats.readRate }}%</div><div class="sb-label">已读率</div></div>
      </div>
    </div>

    <!-- 类型统计 + 筛选 -->
    <div class="content-row">
      <!-- 消息类型环形图 -->
      <div class="type-panel">
        <div class="type-title">消息类型分布</div>
        <div class="type-rings">
          <div class="type-ring-item" v-for="t in typeStats" :key="t.type" @click="query.type = t.type === query.type ? '' : t.type; loadData()">
            <div class="tri-ring">
              <svg viewBox="0 0 60 60" class="tri-svg">
                <circle cx="30" cy="30" r="24" fill="none" stroke="#f1f5f9" stroke-width="8" />
                <circle cx="30" cy="30" r="24" fill="none" :stroke="t.color" stroke-width="8"
                  :stroke-dasharray="t.pct * 1.5 + ' ' + (150 - t.pct * 1.5)"
                  stroke-linecap="round" transform="rotate(-90 30 30)" />
              </svg>
              <span class="tri-label">{{ t.pct }}%</span>
            </div>
            <div class="tri-name" :style="{ color: t.color }">{{ t.label }}</div>
            <div class="tri-count">{{ t.count }} 条</div>
          </div>
        </div>
      </div>

      <!-- 筛选 -->
      <div class="filter-box">
        <div class="fb-header">
          <span class="fb-title">消息查询</span>
          <el-button type="primary" size="small" :icon="Promotion" @click="openSendDialog" round>发送新消息</el-button>
        </div>
        <div class="fb-fields">
          <el-input v-model="query.keyword" placeholder="搜索标题/内容" clearable prefix-icon="Search" @keyup.enter="loadData" />
          <el-select v-model="query.type" placeholder="消息类型" clearable @change="loadData">
            <el-option label="通知公告" value="notice" />
            <el-option label="报修通知" value="repair" />
            <el-option label="账单提醒" value="bill" />
            <el-option label="访客通知" value="visit" />
            <el-option label="其他" value="other" />
          </el-select>
          <el-select v-model="query.receiver_type" placeholder="接收方" clearable @change="loadData">
            <el-option label="业主" value="owner" />
            <el-option label="家庭成员" value="family" />
            <el-option label="租客" value="tenant" />
            <el-option label="所有用户" value="all" />
          </el-select>
          <el-button type="primary" @click="loadData" :icon="Search">查询</el-button>
          <el-button @click="resetQuery" :icon="RefreshRight">重置</el-button>
        </div>
      </div>
    </div>

    <!-- 消息列表 -->
    <el-card shadow="never" class="msg-panel">
      <template #header>
        <div class="panel-head">
          <span class="panel-title"><span class="dot"></span>消息记录</span>
          <span class="panel-total">共 {{ total }} 条</span>
        </div>
      </template>

      <div class="msg-list" v-loading="loading">
        <div v-for="msg in list" :key="msg.id" class="msg-item" :class="{ 'msg-unread': msg.is_read == 0 }">
          <div class="msg-left">
            <div class="msg-avatar" :style="{ background: typeColor(msg.type) }">
              <el-icon v-if="msg.type === 'notice'"><Bell /></el-icon>
              <el-icon v-else-if="msg.type === 'repair'"><Tools /></el-icon>
              <el-icon v-else-if="msg.type === 'bill'"><Money /></el-icon>
              <el-icon v-else-if="msg.type === 'visit'"><UserFilled /></el-icon>
              <el-icon v-else><InfoFilled /></el-icon>
            </div>
          </div>
          <div class="msg-body">
            <div class="msg-head">
              <span class="msg-title">{{ msg.title || '无标题' }}</span>
              <span class="msg-badge" v-if="msg.is_read == 0">未读</span>
              <el-tag :type="msgTypeTag(msg.type)" size="small" effect="plain" round>{{ msgTypeLabel(msg.type) }}</el-tag>
            </div>
            <div class="msg-content">{{ msg.content || '—' }}</div>
            <div class="msg-meta">
              <span><el-icon><User /></el-icon> {{ receiverLabel(msg.receiver_type) }} | 接收ID: {{ msg.receiver_id }}</span>
              <span class="msg-time">{{ msg.create_time }}</span>
            </div>
          </div>
          <div class="msg-right">
            <el-popconfirm title="确定删除该消息？" @confirm="handleDelete(msg)">
              <template #reference>
                <el-button size="small" type="danger" :icon="Delete" circle />
              </template>
            </el-popconfirm>
          </div>
        </div>

        <div v-if="!loading && list.length === 0" class="empty-msg">
          <el-icon :size="56" color="#cbd5e1"><ChatLineSquare /></el-icon>
          <p>暂无消息记录</p>
        </div>
      </div>

      <div class="pagi-wrap" v-if="total > 0">
        <el-pagination v-model:current-page="query.page" v-model:page-size="query.limit" :total="total"
          :page-sizes="[10, 20, 30]" layout="total, sizes, prev, pager, next, jumper" background
          @current-change="loadData" @size-change="loadData" />
      </div>
    </el-card>

    <!-- 发送消息弹窗 -->
    <el-dialog v-model="sendVisible" title="发送新消息" width="560px" destroy-on-close top="10vh">
      <el-form :model="sendForm" label-width="80px">
        <el-form-item label="消息类型">
          <el-select v-model="sendForm.type" style="width:100%">
            <el-option label="通知公告" value="notice" />
            <el-option label="报修通知" value="repair" />
            <el-option label="账单提醒" value="bill" />
            <el-option label="访客通知" value="visit" />
            <el-option label="其他" value="other" />
          </el-select>
        </el-form-item>
        <el-form-item label="接收方">
          <el-select v-model="sendForm.receiver_type" style="width:45%">
            <el-option label="业主" value="owner" />
            <el-option label="家庭成员" value="family" />
            <el-option label="租客" value="tenant" />
            <el-option label="所有用户" value="all" />
          </el-select>
          <el-input v-model="sendForm.receiver_id" placeholder="接收人ID" style="width:52%;margin-left:3%" />
        </el-form-item>
        <el-form-item label="消息标题">
          <el-input v-model="sendForm.title" placeholder="请输入标题" maxlength="100" show-word-limit />
        </el-form-item>
        <el-form-item label="消息内容">
          <el-input v-model="sendForm.content" type="textarea" :rows="4" placeholder="请输入消息内容" maxlength="500" show-word-limit />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="sendVisible = false" round>取消</el-button>
        <el-button type="primary" @click="handleSend" :loading="sending" round>发送消息</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, computed } from 'vue'
import { apiGet, apiPost } from '@/utils/request'
import { ElMessage } from 'element-plus'
import { ChatLineSquare, Check, Message, DataAnalysis, Search, RefreshRight, Promotion, Bell, Tools, Money, UserFilled, InfoFilled, User, Delete } from '@element-plus/icons-vue'

const list = ref<any[]>([])
const total = ref(0)
const loading = ref(false)
const sendVisible = ref(false)
const sending = ref(false)
const query = reactive({ page: 1, limit: 15, keyword: '', type: '', receiver_type: '' })
const sendForm = reactive({ type: 'notice', receiver_type: 'owner', receiver_id: '', title: '', content: '' })

const stats = computed(() => {
  const arr = list.value || []
  const totalCount = total.value
  const readCount = arr.filter(m => m.is_read == 1).length
  const unread = totalCount - readCount
  return { total: totalCount, read: readCount, unread, readRate: totalCount ? Math.round(readCount / totalCount * 100) : 0 }
})

const typeStats = computed(() => {
  const arr = list.value || []
  const types = ['notice', 'repair', 'bill', 'visit', 'other']
  const colors: Record<string, string> = { notice: '#6366f1', repair: '#f59e0b', bill: '#22c55e', visit: '#3b82f6', other: '#94a3b8' }
  const labels: Record<string, string> = { notice: '通知公告', repair: '报修', bill: '账单', visit: '访客', other: '其他' }
  const counts: Record<string, number> = {}
  arr.forEach(m => { const t = m.type || 'other'; counts[t] = (counts[t] || 0) + 1 })
  const totalCount = Object.values(counts).reduce((s, c) => s + c, 0) || 1
  return types.map(t => ({
    type: t, label: labels[t], color: colors[t],
    count: counts[t] || 0,
    pct: Math.round(((counts[t] || 0) / totalCount) * 100)
  }))
})

function typeColor(t: string) {
  const m: Record<string, string> = { notice: 'linear-gradient(135deg, #6366f1, #8b5cf6)', repair: 'linear-gradient(135deg, #f59e0b, #f97316)', bill: 'linear-gradient(135deg, #22c55e, #10b981)', visit: 'linear-gradient(135deg, #3b82f6, #06b6d4)', other: 'linear-gradient(135deg, #94a3b8, #64748b)' }
  return m[t] || m.other
}
function msgTypeLabel(t: string) { const m: Record<string, string> = { notice: '通知', repair: '报修', bill: '账单', visit: '访客', other: '其他' }; return m[t] || '其他' }
function msgTypeTag(t: string): string { const m: Record<string, string> = { notice: '', repair: 'warning', bill: 'success', visit: '', other: 'info' }; return m[t] || 'info' }
function receiverLabel(t: string) { const m: Record<string, string> = { owner: '业主', family: '家庭成员', tenant: '租客', all: '所有用户' }; return m[t] || t }

async function loadData() {
  loading.value = true
  try {
    const p: any = { page: query.page, limit: query.limit, keyword: query.keyword }
    if (query.type) p.type = query.type
    if (query.receiver_type) p.receiver_type = query.receiver_type
    const res = await apiGet('/admin/notice/messageList', p)
    if (res.code === 0) { list.value = res.data?.list || []; total.value = res.data?.total || 0 }
  } finally { loading.value = false }
}

function resetQuery() { query.keyword = ''; query.type = ''; query.receiver_type = ''; query.page = 1; loadData() }

function openSendDialog() { sendForm.type = 'notice'; sendForm.receiver_type = 'owner'; sendForm.receiver_id = ''; sendForm.title = ''; sendForm.content = ''; sendVisible.value = true }

async function handleSend() {
  if (!sendForm.title.trim()) { ElMessage.warning('请输入标题'); return }
  sending.value = true
  try {
    const res = await apiPost('/admin/notice/messageAdd', { ...sendForm, is_read: 0, status: 1 })
    if (res.code === 0) { ElMessage.success('发送成功'); sendVisible.value = false; loadData() }
  } finally { sending.value = false }
}

async function handleDelete(row: any) {
  const res = await apiPost('/admin/notice/messageDelete', { id: row.id })
  if (res.code === 0) { ElMessage.success('删除成功'); loadData() }
}

onMounted(loadData)
</script>

<style scoped>
.premium-page { animation: fadeIn 0.4s; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }

.stat-bar { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 18px; }
.sb-card { background: #fff; border-radius: 14px; padding: 18px 22px; display: flex; align-items: center; gap: 14px; border: 1px solid #f1f5f9; transition: all 0.2s; }
.sb-card:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,0.06); }
.sb-icon { width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0; }
.sb-total .sb-icon { background: #eef2ff; color: #6366f1; }
.sb-read .sb-icon { background: #f0fdf4; color: #22c55e; }
.sb-unread .sb-icon { background: #fef2f2; color: #ef4444; }
.sb-rate .sb-icon { background: #fef9c3; color: #eab308; }
.sb-num { font-size: 24px; font-weight: 800; color: #1e293b; }
.sb-label { font-size: 12px; color: #94a3b8; }

.content-row { display: flex; gap: 16px; margin-bottom: 18px; }
.type-panel { flex: 0.8; background: #fff; border-radius: 14px; padding: 20px; border: 1px solid #f1f5f9; }
.type-title { font-size: 13px; font-weight: 700; color: #475569; margin-bottom: 12px; }
.type-rings { display: flex; justify-content: space-around; gap: 6px; }
.type-ring-item { text-align: center; cursor: pointer; transition: transform 0.2s; }
.type-ring-item:hover { transform: scale(1.05); }
.tri-ring { position: relative; width: 60px; height: 60px; margin: 0 auto 6px; }
.tri-svg { width: 60px; height: 60px; }
.tri-label { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); font-size: 11px; font-weight: 700; color: #334155; }
.tri-name { font-size: 12px; font-weight: 600; }
.tri-count { font-size: 11px; color: #94a3b8; }

.filter-box { flex: 1.8; background: #fff; border-radius: 14px; padding: 18px; border: 1px solid #f1f5f9; }
.fb-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px; }
.fb-title { font-size: 13px; font-weight: 700; color: #475569; }
.fb-fields { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; }
.fb-fields .el-input { width: 200px; }
.fb-fields .el-select { width: 130px; }

.msg-panel { border-radius: 12px; border: 1px solid #f1f5f9; }
.msg-panel :deep(.el-card__header) { padding: 14px 20px; border-bottom: 1px solid #f1f5f9; }
.panel-head { display: flex; align-items: center; justify-content: space-between; }
.panel-title { font-size: 15px; font-weight: 700; color: #1e293b; display: flex; align-items: center; gap: 8px; }
.dot { width: 8px; height: 8px; border-radius: 50%; background: linear-gradient(135deg, #6366f1, #8b5cf6); }
.panel-total { font-size: 12px; color: #94a3b8; }

.msg-list { min-height: 200px; }
.msg-item { display: flex; align-items: flex-start; gap: 14px; padding: 16px 20px; border-bottom: 1px solid #f8fafc; transition: all 0.15s; position: relative; }
.msg-item:hover { background: #fafbff; }
.msg-item.msg-unread { background: #fafbff; }
.msg-item.msg-unread::before { content: ''; position: absolute; left: 0; top: 50%; transform: translateY(-50%); width: 4px; height: 70%; border-radius: 0 3px 3px 0; background: #6366f1; }
.msg-avatar { width: 40px; height: 40px; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 17px; flex-shrink: 0; }
.msg-body { flex: 1; min-width: 0; }
.msg-head { display: flex; align-items: center; gap: 8px; margin-bottom: 6px; flex-wrap: wrap; }
.msg-title { font-size: 14px; font-weight: 700; color: #1e293b; }
.msg-badge { font-size: 10px; padding: 2px 8px; border-radius: 10px; background: #eef2ff; color: #6366f1; font-weight: 700; }
.msg-content { font-size: 13px; color: #64748b; line-height: 1.5; margin-bottom: 6px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
.msg-meta { display: flex; align-items: center; gap: 16px; font-size: 12px; color: #94a3b8; }
.msg-time { margin-left: auto; }
.msg-right { flex-shrink: 0; padding-top: 4px; }

.empty-msg { text-align: center; padding: 60px 20px; color: #cbd5e1; }
.empty-msg p { margin-top: 12px; font-size: 14px; }

.pagi-wrap { margin-top: 16px; display: flex; justify-content: flex-end; }

@media (max-width: 1200px) { .content-row { flex-direction: column; } .stat-bar { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 768px) { .stat-bar { grid-template-columns: 1fr; } }
</style>
