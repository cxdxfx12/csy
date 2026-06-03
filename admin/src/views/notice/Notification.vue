<template>
  <div class="premium-page">
    <!-- 推送概览 -->
    <div class="hero-banner">
      <div class="hb-bg"></div>
      <div class="hb-content">
        <div class="hb-left">
          <h2 class="hb-title">消息推送中心</h2>
          <p class="hb-desc">向业主、租客及住户精准推送通知、公告、催缴等各类消息</p>
          <div class="hb-tags">
            <div class="hbt" v-for="t in pushStats.recent" :key="t.id">
              <span class="hbt-dot" :style="{ background: pushColor(t.type) }"></span>
              {{ t.title }}
              <span class="hbt-time">{{ timeAgo(t.create_time) }}</span>
            </div>
          </div>
        </div>
        <div class="hb-right">
          <div class="hb-stat-circle">
            <svg viewBox="0 0 140 140">
              <circle cx="70" cy="70" r="58" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="10" />
              <circle cx="70" cy="70" r="58" fill="none" stroke="rgba(255,255,255,0.5)" stroke-width="10"
                :stroke-dasharray="pushStats.successRate * 3.64 + ' 364'" stroke-linecap="round" transform="rotate(-90 70 70)" />
            </svg>
            <div class="hb-sc-inner">
              <span class="hb-sc-num">{{ pushStats.successRate }}%</span>
              <span class="hb-sc-label">送达率</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- KPI 卡片 -->
    <div class="kpi-row">
      <div class="kpi kpi-a">
        <div class="kpi-num">{{ pushStats.total }}</div>
        <div class="kpi-title">推送总数</div>
        <el-icon class="kpi-icon"><Promotion /></el-icon>
      </div>
      <div class="kpi kpi-b">
        <div class="kpi-num">{{ pushStats.today }}</div>
        <div class="kpi-title">今日推送</div>
        <el-icon class="kpi-icon"><Operation /></el-icon>
      </div>
      <div class="kpi kpi-c">
        <div class="kpi-num">{{ pushStats.types }}</div>
        <div class="kpi-title">推送类型</div>
        <el-icon class="kpi-icon"><Grid /></el-icon>
      </div>
      <div class="kpi kpi-d">
        <div class="kpi-num">{{ pushStats.communities }}</div>
        <div class="kpi-title">覆盖小区</div>
        <el-icon class="kpi-icon"><Location /></el-icon>
      </div>
    </div>

    <!-- 搜索 + 新建 -->
    <div class="action-bar">
      <div class="ab-left">
        <el-input v-model="query.keyword" placeholder="搜索推送标题/内容" clearable prefix-icon="Search" class="ab-input" @keyup.enter="loadData" />
        <el-select v-model="query.community_id" placeholder="全部小区" clearable class="ab-sel" @change="loadData">
          <el-option v-for="c in communities" :key="c.id" :label="c.name" :value="c.id" />
        </el-select>
        <el-button type="primary" @click="loadData" :icon="Search">查询</el-button>
        <el-button @click="resetQuery" :icon="RefreshRight">重置</el-button>
      </div>
      <el-button type="primary" @click="openForm()" :icon="Plus" round size="large">新建推送</el-button>
    </div>

    <!-- 推送卡片列表 -->
    <div class="push-grid">
      <div v-for="item in list" :key="item.id" class="push-card" :class="'pc-'+item.status">
        <div class="pc-header">
          <div class="pc-status-dot" :style="{ background: pushColor(item.type || '') }"></div>
          <span class="pc-title">{{ item.title || '无标题' }}</span>
          <el-tag :type="statusTag(item.status)" size="small" effect="plain" round>{{ statusLabel(item.status) }}</el-tag>
        </div>
        <div class="pc-body">
          <p class="pc-content">{{ item.content || '—' }}</p>
        </div>
        <div class="pc-meta">
          <span><el-icon><User /></el-icon> {{ item.target || '全部用户' }}</span>
          <span><el-icon><Calendar /></el-icon> {{ item.create_time || '—' }}</span>
        </div>
        <div class="pc-footer">
          <div class="pc-stats">
            <span class="pcs-sent">已发送 {{ item.sent_count || 0 }}</span>
            <span class="pcs-read">已读 {{ item.read_count || 0 }}</span>
          </div>
          <div class="pc-actions">
            <el-button size="small" @click="openForm(item)" :icon="Edit" round>编辑</el-button>
            <el-popconfirm title="确定删除？" @confirm="handleDelete(item)">
              <template #reference>
                <el-button size="small" type="danger" :icon="Delete" round plain>删除</el-button>
              </template>
            </el-popconfirm>
          </div>
        </div>
      </div>
    </div>

    <div v-if="!loading && list.length === 0" class="empty-box">
      <el-icon :size="64" color="#cbd5e1"><Promotion /></el-icon>
      <p>暂无线推送记录</p>
      <el-button type="primary" @click="openForm()" :icon="Plus" round>创建第一条推送</el-button>
    </div>

    <div class="pagi-box" v-if="total > 0">
      <el-pagination v-model:current-page="query.page" v-model:page-size="query.limit" :total="total"
        :page-sizes="[12, 24, 48]" layout="total, sizes, prev, pager, next, jumper" background
        @current-change="loadData" @size-change="loadData" />
    </div>

    <!-- 编辑弹窗 -->
    <el-dialog v-model="dialogVisible" :title="editId ? '编辑推送' : '新建推送'" width="640px" destroy-on-close top="8vh">
      <el-form :model="form" label-width="90px" label-position="right">
        <el-form-item label="推送标题">
          <el-input v-model="form.title" placeholder="请输入推送标题" maxlength="100" show-word-limit />
        </el-form-item>
        <el-form-item label="推送内容">
          <el-input v-model="form.content" type="textarea" :rows="5" placeholder="请输入推送内容" maxlength="1000" show-word-limit />
        </el-form-item>
        <el-row :gutter="16">
          <el-col :span="12">
            <el-form-item label="目标人群">
              <el-input v-model="form.target" placeholder="如：全部业主" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="推送类型">
              <el-select v-model="form.type" style="width:100%">
                <el-option label="通知公告" value="notice" />
                <el-option label="紧急通知" value="urgent" />
                <el-option label="账单提醒" value="bill" />
                <el-option label="活动推送" value="activity" />
                <el-option label="系统消息" value="system" />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="优先级">
              <el-radio-group v-model="form.priority">
                <el-radio-button :value="1">普通</el-radio-button>
                <el-radio-button :value="2">重要</el-radio-button>
                <el-radio-button :value="3">紧急</el-radio-button>
              </el-radio-group>
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="状态">
              <el-switch v-model="form.status" :active-value="1" :inactive-value="0" active-text="启用" inactive-text="停用" />
            </el-form-item>
          </el-col>
        </el-row>
      </el-form>
      <template #footer>
        <el-button @click="dialogVisible = false" round>取消</el-button>
        <el-button type="primary" @click="handleSubmit" :loading="submitting" round>{{ editId ? '更新推送' : '立即推送' }}</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, computed } from 'vue'
import { apiGet, apiPost } from '@/utils/request'
import { ElMessage } from 'element-plus'
import { Promotion, Operation, Grid, Location, Search, RefreshRight, Plus, Edit, Delete, User, Calendar } from '@element-plus/icons-vue'

const list = ref<any[]>([])
const total = ref(0)
const loading = ref(false)
const dialogVisible = ref(false)
const editId = ref(0)
const submitting = ref(false)
const communities = ref<any[]>([])
const query = reactive({ page: 1, limit: 12, keyword: '', community_id: '' })
const form = reactive<any>({ title: '', content: '', target: '', type: 'notice', priority: 1, status: 1 })

const pushStats = computed(() => {
  const arr = list.value || []
  const totalCount = total.value
  const today = new Date().toISOString().slice(0, 10)
  const todayCount = arr.filter(n => (n.create_time || '').startsWith(today)).length
  const typeSet = new Set(arr.map(n => n.type).filter(Boolean))
  const commSet = new Set(arr.map(n => n.community_id).filter(Boolean))
  const sentTotal = arr.reduce((s, n) => s + (parseInt(n.sent_count) || 0), 0)
  const readTotal = arr.reduce((s, n) => s + (parseInt(n.read_count) || 0), 0)
  const recent = arr.slice(0, 5)
  return {
    total: totalCount, today: todayCount, types: typeSet.size,
    communities: commSet.size,
    successRate: sentTotal ? Math.round(readTotal / sentTotal * 100) : 100,
    recent
  }
})

function pushColor(t: string) {
  const m: Record<string, string> = { notice: '#6366f1', urgent: '#ef4444', bill: '#f59e0b', activity: '#22c55e', system: '#94a3b8' }
  return m[t] || '#6366f1'
}
function statusLabel(s: any) { const m: Record<number, string> = { 0: '停用', 1: '启用', 2: '已发送', 3: '已取消' }; return m[parseInt(s)] || '未知' }
function statusTag(s: any): string { const m: Record<number, string> = { 0: 'info', 1: 'success', 2: '', 3: 'danger' }; return m[parseInt(s)] || 'info' }
function timeAgo(d: string) {
  if (!d) return ''
  const now = Date.now(), t = new Date(d).getTime()
  const diff = Math.floor((now - t) / 1000)
  if (diff < 60) return '刚刚'
  if (diff < 3600) return Math.floor(diff / 60) + '分钟前'
  if (diff < 86400) return Math.floor(diff / 3600) + '小时前'
  return Math.floor(diff / 86400) + '天前'
}

async function loadData() {
  loading.value = true
  try {
    const p: any = { page: query.page, limit: query.limit, keyword: query.keyword }
    if (query.community_id) p.community_id = query.community_id
    const res = await apiGet('/admin/notice/notificationList', p)
    if (res.code === 0) { list.value = res.data?.list || []; total.value = res.data?.total || 0 }
  } finally { loading.value = false }
}

async function loadCommunities() {
  try {
    // Use a light API to get community list for filter
    const res = await apiGet('/admin/community/list', { page: 1, limit: 999 })
    if (res.code === 0) communities.value = res.data.list || []
  } catch (_) { /* ignore */ }
}

function resetQuery() { query.keyword = ''; query.community_id = ''; query.page = 1; loadData() }

function openForm(row?: any) {
  if (row) { editId.value = row.id; Object.assign(form, row) }
  else { editId.value = 0; form.title = ''; form.content = ''; form.target = ''; form.type = 'notice'; form.priority = 1; form.status = 1 }
  dialogVisible.value = true
}

async function handleSubmit() {
  if (!form.title.trim()) { ElMessage.warning('请输入标题'); return }
  submitting.value = true
  try {
    const url = editId.value ? '/admin/notice/notificationEdit' : '/admin/notice/notificationAdd'
    const res = await apiPost(url, { ...form, id: editId.value || undefined })
    if (res.code === 0) { ElMessage.success(res.msg); dialogVisible.value = false; loadData() }
  } finally { submitting.value = false }
}

async function handleDelete(row: any) {
  const res = await apiPost('/admin/notice/notificationDelete', { id: row.id })
  if (res.code === 0) { ElMessage.success('删除成功'); loadData() }
}

onMounted(() => { loadCommunities(); loadData() })
</script>

<style scoped>
.premium-page { animation: fadeIn 0.4s; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }

/* 英雄横幅 */
.hero-banner { border-radius: 16px; overflow: hidden; position: relative; margin-bottom: 18px; }
.hb-bg { position: absolute; inset: 0; background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 30%, #1e40af 60%, #6366f1 100%); }
.hb-bg::after { content: ''; position: absolute; inset: 0; background: url("data:image/svg+xml,%3Csvg width='60' height='60' xmlns='http://www.w3.org/2000/svg'%3E%3Cdefs%3E%3Cpattern id='g' width='60' height='60' patternUnits='userSpaceOnUse'%3E%3Cpath d='M 60 0 L 0 0 0 60' fill='none' stroke='rgba(255,255,255,0.03)' stroke-width='1'/%3E%3C/pattern%3E%3C/defs%3E%3Crect width='60' height='60' fill='url(%23g)'/%3E%3C/svg%3E"); }
.hb-content { position: relative; z-index: 2; display: flex; align-items: center; gap: 40px; padding: 28px 32px; color: #fff; }
.hb-left { flex: 1; }
.hb-title { font-size: 22px; font-weight: 800; margin: 0 0 6px; letter-spacing: 0.5px; }
.hb-desc { font-size: 13px; opacity: 0.7; margin: 0 0 12px; }
.hb-tags { display: flex; gap: 12px; flex-wrap: wrap; }
.hbt { font-size: 12px; display: flex; align-items: center; gap: 5px; padding: 4px 11px; border-radius: 12px; background: rgba(255,255,255,0.1); }
.hbt-dot { width: 6px; height: 6px; border-radius: 50%; }
.hbt-time { margin-left: 4px; opacity: 0.5; }
.hb-right { flex-shrink: 0; }
.hb-stat-circle { position: relative; width: 100px; height: 100px; }
.hb-sc-inner { position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; }
.hb-sc-num { font-size: 22px; font-weight: 800; }
.hb-sc-label { font-size: 10px; opacity: 0.6; }

/* KPI */
.kpi-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 18px; }
.kpi { background: #fff; border-radius: 14px; padding: 20px 22px; border: 1px solid #f1f5f9; position: relative; overflow: hidden; transition: all 0.2s; }
.kpi:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,0.06); }
.kpi-num { font-size: 28px; font-weight: 800; color: #1e293b; }
.kpi-title { font-size: 12px; color: #94a3b8; margin-top: 2px; }
.kpi-icon { position: absolute; top: 16px; right: 16px; font-size: 26px; opacity: 0.08; }
.kpi-a .kpi-icon { color: #6366f1; }
.kpi-b .kpi-icon { color: #22c55e; }
.kpi-c .kpi-icon { color: #f59e0b; }
.kpi-d .kpi-icon { color: #3b82f6; }

/* 操作栏 */
.action-bar { display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px; gap: 12px; flex-wrap: wrap; }
.ab-left { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
.ab-input { width: 220px; }
.ab-sel { width: 150px; }

/* 推送卡片 */
.push-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 18px; }
.push-card { background: #fff; border-radius: 14px; padding: 20px; border: 1px solid #f1f5f9; transition: all 0.25s; position: relative; overflow: hidden; }
.push-card:hover { box-shadow: 0 10px 32px rgba(0,0,0,0.07); transform: translateY(-3px); }
.push-card.pc-0 { opacity: 0.65; }
.pc-header { display: flex; align-items: center; gap: 8px; margin-bottom: 10px; }
.pc-status-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
.pc-title { font-size: 14px; font-weight: 700; color: #1e293b; flex: 1; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.pc-body { margin-bottom: 10px; }
.pc-content { font-size: 13px; color: #64748b; line-height: 1.5; margin: 0; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; }
.pc-meta { display: flex; gap: 14px; font-size: 12px; color: #94a3b8; margin-bottom: 10px; }
.pc-meta span { display: flex; align-items: center; gap: 4px; }
.pc-footer { display: flex; align-items: center; justify-content: space-between; padding-top: 10px; border-top: 1px solid #f8fafc; }
.pc-stats { display: flex; gap: 12px; font-size: 11px; }
.pcs-sent { color: #64748b; }
.pcs-read { color: #22c55e; font-weight: 600; }
.pc-actions { display: flex; gap: 6px; }

.empty-box { text-align: center; padding: 80px 20px; color: #cbd5e1; }
.empty-box p { margin: 16px 0; font-size: 14px; }

.pagi-box { display: flex; justify-content: center; }

@media (max-width: 1200px) { .push-grid { grid-template-columns: repeat(2, 1fr); } .hero-banner .hb-content { flex-direction: column; align-items: flex-start; } }
@media (max-width: 768px) { .push-grid { grid-template-columns: 1fr; } .kpi-row { grid-template-columns: repeat(2, 1fr); } }
</style>
