<template>
  <div class="dashboard">
    <!-- Top Bar -->
    <header>
      <div class="header-left">
        <h1>🏘️ {{ communityName || '小区经理工作台' }}<span v-if="communityName" class="title-divider">|</span><span v-if="communityName" class="title-sub">小区经理工作台</span></h1>
      </div>
      <div class="header-center" v-if="communities.length > 1">
        <select v-model.number="selectedCommunityId" @change="switchCommunity" class="community-select">
          <option v-for="c in communities" :key="c.id" :value="c.id">
            {{ c.name }}（{{ c.code }}）
          </option>
        </select>
      </div>
      <div class="header-right">
        <span class="user-info">{{ managerName }}</span>
        <button class="btn-logout" @click="logout">退出</button>
      </div>
    </header>

    <!-- Tab Nav -->
    <nav class="tab-nav">
      <button v-for="t in tabs" :key="t.key" :class="{ active: activeTab === t.key }" @click="activeTab = t.key; loadTabData()">
        {{ t.icon }} {{ t.label }}
      </button>
    </nav>

    <div v-if="loading" class="loading">数据加载中...</div>
    <div v-else-if="error" class="error">{{ error }}</div>

    <!-- Dashboard -->
    <template v-else-if="activeTab === 'dashboard'">
      <div class="stats-row">
        <div class="stat-card" v-for="s in statCards" :key="s.label">
          <div class="stat-val">{{ s.value }}</div>
          <div class="stat-label">{{ s.label }}</div>
        </div>
      </div>

      <div class="grid2">
        <div class="panel" v-if="income.months?.length">
          <h3>📈 收入趋势</h3>
          <div class="bar-chart">
            <div class="bar" v-for="(m,i) in income.months" :key="i" :style="{height:(income.values[i]/maxIncome*100)+'%'}">
              <span class="bar-val">¥{{ income.values[i] }}</span>
              <span class="bar-label">{{ m }}</span>
            </div>
          </div>
        </div>

        <div class="panel" v-if="repair">
          <h3>🔧 报修统计</h3>
          <div class="stat-grid">
            <div class="mini-stat"><strong>{{ repair.pending }}</strong><small>待处理</small></div>
            <div class="mini-stat"><strong>{{ repair.processing }}</strong><small>处理中</small></div>
            <div class="mini-stat"><strong>{{ repair.finished }}</strong><small>已完成</small></div>
            <div class="mini-stat"><strong>{{ repair.total }}</strong><small>总工单</small></div>
          </div>
        </div>

        <div class="panel" v-if="owner">
          <h3>👥 业主统计</h3>
          <div class="big-num">{{ owner.total }}<small>业主总数</small></div>
          <div class="big-num secondary">{{ owner.newThisMonth }}<small>本月新增</small></div>
          <div class="big-num tertiary">{{ owner.wxBound }}<small>微信绑定</small></div>
        </div>

        <div class="panel" v-if="todos.length">
          <h3>⏳ 待处理事项</h3>
          <div class="todo-list">
            <div v-for="t in todos" :key="t.title" class="todo-item">
              <span class="todo-icon">{{ t.icon }}</span>
              <span class="todo-title">{{ t.title }}</span>
              <span class="todo-count">{{ t.count }}条</span>
            </div>
          </div>
        </div>

        <div class="panel" v-if="chargeRate">
          <h3>💰 收费率</h3>
          <div class="charge-wrap">
            <div class="charge-big">{{ chargeRate.rate }}%</div>
            <div class="charge-bar-bg"><div class="charge-bar" :style="{width:chargeRate.rate+'%'}"></div></div>
            <div class="charge-detail">已收 ¥{{ chargeRate.paid || 0 }} / 应收 ¥{{ chargeRate.total || 0 }}</div>
          </div>
        </div>
      </div>
    </template>

    <!-- Vote Tab -->
    <template v-else-if="activeTab === 'vote'">
      <div class="list-toolbar">
        <button class="btn-search" @click="openVoteForm()">+ 新增投票</button>
        <select v-model="voteStatusFilter" @change="loadVoteList" style="height:40px;background:#1e293b;border:1px solid #334155;border-radius:8px;padding:0 12px;color:#e2e8f0;font-size:14px;outline:none;">
          <option value="0">全部状态</option>
          <option value="1">草稿</option>
          <option value="2">进行中</option>
          <option value="3">已结束</option>
        </select>
      </div>
      <div class="list-table-wrap">
        <table v-if="voteList.length" class="list-table">
          <thead><tr><th>ID</th><th>标题</th><th>类型</th><th>状态</th><th>选项</th><th>参与人数</th><th>时间</th><th style="width:240px;">操作</th></tr></thead>
          <tbody>
            <tr v-for="v in voteList" :key="v.id">
              <td>{{ v.id }}</td>
              <td class="text-ellipsis" style="max-width:180px;">{{ v.title }}</td>
              <td><span class="tag">{{ v.type === 2 ? '多选' : '单选' }}</span></td>
              <td><span :class="'tag tag-'+v.status">{{ voteStatusMap[v.status] }}</span></td>
              <td>{{ v.option_count }}</td>
              <td>{{ v.total_votes }}</td>
              <td style="font-size:12px;">{{ v.start_time?.substring(0,10) || '-' }} ~ {{ v.end_time?.substring(0,10) || '-' }}</td>
              <td class="action-btns">
                <button v-if="v.status==1" class="btn-mini btn-green" @click="publishVote(v.id)">发布</button>
                <button v-if="v.status==2" class="btn-mini btn-red" @click="closeVote(v.id)">结束</button>
                <button v-if="v.status>=2" class="btn-mini btn-blue" @click="viewVoteResult(v.id)">结果</button>
                <button v-if="v.status!=2" class="btn-mini" @click="openVoteForm(v)">编辑</button>
                <button class="btn-mini btn-danger" @click="deleteVote(v.id)">删除</button>
              </td>
            </tr>
          </tbody>
        </table>
        <div v-else class="empty">暂无投票数据</div>
      </div>
      <!-- Vote Form Modal -->
      <div class="modal-overlay" v-if="voteFormVisible" @click.self="voteFormVisible=false">
        <div class="modal-box">
          <h3>{{ voteFormTitle }}</h3>
          <div class="form-group"><label>标题</label><input v-model="voteForm.title" class="input" placeholder="投票标题" /></div>
          <div class="form-group"><label>类型</label>
            <select v-model.number="voteForm.type" style="width:100%;height:40px;background:#0f172a;border:1px solid #334155;border-radius:8px;padding:0 12px;color:#e2e8f0;font-size:14px;outline:none;">
              <option :value="1">单选</option>
              <option :value="2">多选</option>
            </select>
          </div>
          <div class="form-group"><label>说明</label><textarea v-model="voteForm.content" class="input" rows="2" placeholder="投票说明(选填)"></textarea></div>
          <div class="form-row"><div class="form-group"><label>开始时间</label><input v-model="voteForm.start_time" type="datetime-local" class="input" /></div><div class="form-group"><label>结束时间</label><input v-model="voteForm.end_time" type="datetime-local" class="input" /></div></div>
          <div class="form-group"><label>选项列表（至少2个）</label></div>
          <div v-for="(opt,idx) in voteForm.options" :key="idx" class="option-row">
            <input v-model="voteForm.options[idx]" class="input" :placeholder="'选项'+(idx+1)" />
            <button v-if="voteForm.options.length>2" class="btn-mini btn-danger" type="button" @click="voteForm.options.splice(idx,1)">×</button>
          </div>
          <button class="btn-mini" type="button" style="margin-top:8px" @click="addOption">+ 添加选项</button>
          <div class="modal-actions"><button class="btn-cancel" @click="voteFormVisible=false">取消</button><button class="btn-search" @click="submitVote" :disabled="voteSubmitting">{{ voteSubmitting?'提交中...':'保存' }}</button></div>
        </div>
      </div>
      <!-- Vote Result Modal -->
      <div class="modal-overlay" v-if="voteResultVisible" @click.self="voteResultVisible=false">
        <div class="modal-box">
          <h3>📊 {{ voteResult.title }}</h3>
          <div style="margin-bottom:12px;color:#94a3b8;">总投票人数: {{ voteResult.total_votes || 0 }}</div>
          <div v-for="opt in (voteResult.options||[])" :key="opt.id" class="result-bar">
            <div class="result-label"><span>{{ opt.title }}</span><span>{{ opt.percent }}% ({{ opt.count }}票)</span></div>
            <div class="result-bg"><div class="result-fill" :style="{width:opt.percent+'%'}"></div></div>
          </div>
          <div class="modal-actions"><button class="btn-cancel" @click="voteResultVisible=false">关闭</button></div>
        </div>
      </div>
    </template>

    <!-- Activity Tab -->
    <template v-else-if="activeTab === 'activity'">
      <div class="list-toolbar">
        <button class="btn-search" @click="openActivityForm()">+ 新增活动</button>
        <select v-model="activityStatusFilter" @change="loadActivityList" style="height:40px;background:#1e293b;border:1px solid #334155;border-radius:8px;padding:0 12px;color:#e2e8f0;font-size:14px;outline:none;">
          <option value="0">全部状态</option>
          <option value="1">草稿</option>
          <option value="2">报名中</option>
          <option value="3">进行中</option>
          <option value="4">已结束</option>
          <option value="5">已取消</option>
        </select>
      </div>
      <div class="list-table-wrap">
        <table v-if="activityList.length" class="list-table">
          <thead><tr><th>ID</th><th>标题</th><th>地点</th><th>报名情况</th><th>状态</th><th>活动时间</th><th style="width:280px;">操作</th></tr></thead>
          <tbody>
            <tr v-for="a in activityList" :key="a.id">
              <td>{{ a.id }}</td>
              <td class="text-ellipsis" style="max-width:160px;">{{ a.title }}</td>
              <td>{{ a.location || '-' }}</td>
              <td><span class="tag tag-1" v-if="a.pending_signup>0">待审{{ a.pending_signup }}</span> <span class="tag tag-2">已通过{{ a.approved_signup||a.signup_count }}</span> / {{ a.max_participants||'不限' }}</td>
              <td><span :class="'tag tag-'+a.status">{{ activityStatusMap[a.status] }}</span></td>
              <td style="font-size:12px;">{{ a.start_time?.substring(0,10) || '-' }} ~ {{ a.end_time?.substring(0,10) || '-' }}</td>
              <td class="action-btns">
                <button v-if="a.status==1" class="btn-mini btn-green" @click="publishActivity(a.id)">发布</button>
                <button v-if="a.status==1||a.status==2" class="btn-mini btn-blue" @click="startActivity(a.id)">开始</button>
                <button v-if="a.status==2||a.status==3" class="btn-mini btn-red" @click="completeActivity(a.id)">结束</button>
                <button v-if="a.status!=4&&a.status!=5" class="btn-mini btn-warn" @click="cancelActivity(a.id)">取消</button>
                <button class="btn-mini btn-blue" @click="openSignupList(a)">报名管理</button>
                <button v-if="a.status!=3" class="btn-mini" @click="openActivityForm(a)">编辑</button>
                <button class="btn-mini btn-danger" @click="deleteActivity(a.id)">删除</button>
              </td>
            </tr>
          </tbody>
        </table>
        <div v-else class="empty">暂无活动数据</div>
      </div>
      <!-- Activity Form Modal -->
      <div class="modal-overlay" v-if="activityFormVisible" @click.self="activityFormVisible=false">
        <div class="modal-box">
          <h3>{{ activityFormTitle }}</h3>
          <div class="form-group"><label>标题</label><input v-model="activityForm.title" class="input" placeholder="活动标题" /></div>
          <div class="form-group"><label>地点</label><input v-model="activityForm.location" class="input" placeholder="活动地点" /></div>
          <div class="form-group"><label>人数上限</label><input v-model.number="activityForm.max_participants" type="number" class="input" placeholder="0表示不限" /></div>
          <div class="form-row"><div class="form-group"><label>开始时间</label><input v-model="activityForm.start_time" type="datetime-local" class="input" /></div><div class="form-group"><label>结束时间</label><input v-model="activityForm.end_time" type="datetime-local" class="input" /></div></div>
          <div class="form-group"><label>详情</label><textarea v-model="activityForm.content" class="input" rows="3" placeholder="活动详情..."></textarea></div>
          <div class="modal-actions"><button class="btn-cancel" @click="activityFormVisible=false">取消</button><button class="btn-search" @click="submitActivity" :disabled="activitySubmitting">{{ activitySubmitting?'提交中...':'保存' }}</button></div>
        </div>
      </div>
      <!-- Signup List Modal -->
      <div class="modal-overlay" v-if="signupListVisible" @click.self="signupListVisible=false">
        <div class="modal-box" style="max-width:700px;">
          <h3>📋 报名管理 - {{ signupActivityTitle }}</h3>
          <div class="list-toolbar" style="margin-bottom:12px;">
            <select v-model="signupStatusFilter" @change="loadSignupList" style="height:36px;background:#1e293b;border:1px solid #334155;border-radius:8px;padding:0 10px;color:#e2e8f0;font-size:13px;outline:none;">
              <option :value="-1">全部</option><option :value="0">待审核</option><option :value="1">已通过</option><option :value="2">已拒绝</option>
            </select>
          </div>
          <div class="list-table-wrap">
            <table v-if="signupList.length" class="list-table">
              <thead><tr><th>姓名</th><th>手机号</th><th>备注</th><th>状态</th><th>报名时间</th><th>操作</th></tr></thead>
              <tbody>
                <tr v-for="s in signupList" :key="s.id">
                  <td>{{ s.owner_name || s.name }}</td>
                  <td>{{ s.owner_phone || s.phone }}</td>
                  <td style="font-size:12px;">{{ s.remark || '-' }}</td>
                  <td><span :class="'tag tag-'+(s.status==0?1:s.status==1?2:5)">{{ signupStatusMap[s.status] }}</span></td>
                  <td style="font-size:12px;">{{ s.create_time?.substring(0,16) }}</td>
                  <td class="action-btns">
                    <button v-if="s.status==0" class="btn-mini btn-green" @click="approveSignup(s.id)">通过</button>
                    <button v-if="s.status==0" class="btn-mini btn-warn" @click="rejectSignup(s.id)">拒绝</button>
                    <button class="btn-mini btn-danger" @click="cancelSignup(s.id)">移除</button>
                  </td>
                </tr>
              </tbody>
            </table>
            <div v-else class="empty">暂无报名记录</div>
          </div>
          <div class="modal-actions"><button class="btn-cancel" @click="signupListVisible=false">关闭</button></div>
        </div>
      </div>
    </template>

    <!-- Lists -->
    <template v-else>
      <div class="list-toolbar" v-if="activeTab==='owner'">
        <input v-model="listKeyword" placeholder="搜索姓名/手机号..." @keyup.enter="loadTabData" />
        <button class="btn-search" @click="loadTabData">搜索</button>
      </div>
      <div class="list-toolbar" v-else>
        <select v-model="listStatus" @change="loadTabData">
          <option value="0">全部</option>
          <option v-if="activeTab==='bill'" value="1">未缴</option>
          <option v-if="activeTab==='bill'" value="2">部分缴纳</option>
          <option v-if="activeTab==='bill'" value="3">已缴清</option>
          <option v-if="activeTab==='repair'" value="1">待派修</option>
          <option v-if="activeTab==='repair'" value="2">待接单</option>
          <option v-if="activeTab==='repair'" value="3">处理中</option>
          <option v-if="activeTab==='repair'" value="4">已完成</option>
          <option v-if="activeTab==='complaint'" value="1">待处理</option>
          <option v-if="activeTab==='complaint'" value="2">处理中</option>
          <option v-if="activeTab==='complaint'" value="3">已解决</option>
        </select>
      </div>

      <div class="list-table-wrap">
        <table v-if="listData.length" class="list-table">
          <thead>
            <tr>
              <template v-if="activeTab==='owner'">
                <th>姓名</th><th>手机</th><th>类型</th><th>房间</th><th>注册时间</th>
              </template>
              <template v-else-if="activeTab==='bill'">
                <th>业主</th><th>房间</th><th>项目</th><th>金额</th><th>已付</th><th>状态</th><th>日期</th>
              </template>
              <template v-else-if="activeTab==='repair'">
                <th>业主</th><th>房间</th><th>问题描述</th><th>类型</th><th>状态</th><th>报修时间</th>
              </template>
              <template v-else-if="activeTab==='complaint'">
                <th>业主</th><th>内容</th><th>类型</th><th>状态</th><th>投诉时间</th>
              </template>
            </tr>
          </thead>
          <tbody>
            <template v-if="activeTab==='owner'">
              <tr v-for="row in listData" :key="row.id">
                <td>{{ row.realname }}</td><td>{{ row.phone }}</td>
                <td><span class="tag">{{ row.type==1?'业主':row.type==2?'家属':'租户' }}</span></td>
                <td>{{ row.rooms || '-' }}</td><td>{{ row.create_time }}</td>
              </tr>
            </template>
            <template v-else-if="activeTab==='bill'">
              <tr v-for="row in listData" :key="row.id">
                <td>{{ row.owner_name }}</td><td>{{ row.room_number }}</td>
                <td>{{ row.charge_item_name }}</td><td>¥{{ row.total_amount }}</td><td>¥{{ row.paid_amount }}</td>
                <td><span :class="'tag tag-'+row.status">{{ row.status==1?'未缴':row.status==2?'部分缴纳':'已缴清' }}</span></td>
                <td>{{ row.create_time?.substring(0,10) }}</td>
              </tr>
            </template>
            <template v-else-if="activeTab==='repair'">
              <tr v-for="row in listData" :key="row.id">
                <td>{{ row.owner_name }}</td><td>{{ row.room_number }}</td>
                <td class="text-ellipsis">{{ row.description }}</td>
                <td>{{ row.repair_type }}</td>
                <td><span :class="'tag tag-'+row.status">{{ statusMap.repair[row.status]||row.status }}</span></td>
                <td>{{ row.create_time?.substring(0,10) }}</td>
              </tr>
            </template>
            <template v-else-if="activeTab==='complaint'">
              <tr v-for="row in listData" :key="row.id">
                <td>{{ row.owner_name }}</td>
                <td class="text-ellipsis">{{ row.content }}</td>
                <td>{{ row.complaint_type }}</td>
                <td><span :class="'tag tag-'+row.status">{{ statusMap.complaint[row.status]||row.status }}</span></td>
                <td>{{ row.create_time?.substring(0,10) }}</td>
              </tr>
            </template>
          </tbody>
        </table>
        <div v-else class="empty">暂无数据</div>
      </div>
      <div class="pagination" v-if="listTotal > limit">
        <button :disabled="page <= 1" @click="page--; loadTabData()">上一页</button>
        <span>第 {{ page }} / {{ Math.ceil(listTotal/limit) }} 页 (共{{ listTotal }}条)</span>
        <button :disabled="page >= Math.ceil(listTotal/limit)" @click="page++; loadTabData()">下一页</button>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { createApi, createAuth } from '@/shared/api.js'

const router = useRouter()
// rawApi 是原始 API；下面包装成自动带 X-Community-Id 请求头的 api
const rawApi = createApi('/api/manager', 'manager_token')
const auth = createAuth('manager_token')

function api(path, options = {}) {
  const opts = { ...options, headers: { ...options.headers } }
  if (selectedCommunityId.value) {
    opts.headers['X-Community-Id'] = String(selectedCommunityId.value)
  }
  return rawApi(path, opts)
}

const loading = ref(true)
const error = ref('')
const activeTab = ref('dashboard')
const communityName = ref('')
const managerName = ref('')

const tabs = [
  { key: 'dashboard', label: '驾驶舱', icon: '📊' },
  { key: 'owner', label: '业主', icon: '👥' },
  { key: 'bill', label: '账单', icon: '💰' },
  { key: 'repair', label: '报修', icon: '🔧' },
  { key: 'complaint', label: '投诉', icon: '📢' },
  { key: 'vote', label: '投票', icon: '🗳️' },
  { key: 'activity', label: '活动', icon: '🎉' },
]

const statusMap = {
  repair: { 1:'待派修', 2:'待接单', 3:'处理中', 4:'已完成', 5:'已关闭' },
  complaint: { 1:'待处理', 2:'处理中', 3:'已解决', 4:'已关闭' }
}

// 小区选择
const communities = ref([])
const selectedCommunityId = ref(parseInt(localStorage.getItem('manager_cid')) || 0)

// Dashboard data
const stats = ref(null)
const income = ref({ months: [], values: [] })
const repair = ref(null)
const owner = ref(null)
const todos = ref([])
const chargeRate = ref(null)

const maxIncome = computed(() => Math.max(...income.value.values, 1))
const statCards = computed(() => {
  if (!stats.value) return []
  const s = stats.value
  return [
    { label: '房产总数', value: s.total_rooms || 0 },
    { label: '业主总数', value: s.total_owners || 0 },
    { label: '本月收入', value: '¥' + (s.month_income || 0) },
    { label: '待收金额', value: '¥' + (s.unpaid_amount || 0) },
    { label: '收费率', value: (s.charge_rate || 0) + '%' },
    { label: '待处理工单', value: s.pending_repairs || 0 },
  ]
})

// List data
const listData = ref([])
const listTotal = ref(0)
const listKeyword = ref('')
const listStatus = ref(0)
const page = ref(1)
const limit = 15

const apiMap = {
  owner: '/owner/list',
  bill: '/bill/list',
  repair: '/repair/list',
  complaint: '/complaint/list',
}

// ===== 投票相关 =====
const voteList = ref([])
const voteStatusFilter = ref(0)
const voteFormVisible = ref(false)
const voteFormTitle = ref('新增投票')
const voteSubmitting = ref(false)
const voteForm = reactive({ id: 0, title: '', type: 1, content: '', start_time: '', end_time: '', options: ['', ''] })
const voteResultVisible = ref(false)
const voteResult = ref({})
const voteStatusMap = { 1: '草稿', 2: '进行中', 3: '已结束' }

function openVoteForm(item) {
  if (item) {
    voteFormTitle.value = '编辑投票'
    voteForm.id = item.id; voteForm.title = item.title; voteForm.type = item.type
    voteForm.content = item.content || ''
    voteForm.start_time = item.start_time?.substring(0,16) || ''
    voteForm.end_time = item.end_time?.substring(0,16) || ''
    // 加载选项
    api('/vote/detail?id=' + item.id).then(r => {
      if (r.code === 0) {
        voteForm.options = (r.data.options || []).map(o => o.title)
      }
    })
  } else {
    voteFormTitle.value = '新增投票'
    voteForm.id = 0; voteForm.title = ''; voteForm.type = 1; voteForm.content = ''
    voteForm.start_time = ''; voteForm.end_time = ''; voteForm.options = ['', '']
  }
  voteFormVisible.value = true
}
function addOption() { voteForm.options.push('') }
async function submitVote() {
  if (!voteForm.title) return alert('请输入标题')
  const validOptions = voteForm.options.filter(o => o.trim())
  if (validOptions.length < 2) return alert('至少需要2个有效选项')
  voteSubmitting.value = true
  const data = { title: voteForm.title, type: voteForm.type, content: voteForm.content, start_time: voteForm.start_time, end_time: voteForm.end_time, options: validOptions }
  if (voteForm.id) data.id = voteForm.id
  const url = voteForm.id ? '/vote/edit' : '/vote/add'
  try {
    const res = await api(url, { method: 'POST', body: JSON.stringify(data) })
    if (res.code === 0) { voteFormVisible.value = false; loadVoteList() }
    else alert(res.msg || '操作失败')
  } catch(e) { alert('操作失败: ' + e.message) }
  voteSubmitting.value = false
}
async function publishVote(id) {
  if (!confirm('确认发布该投票？')) return
  const res = await api('/vote/publish', { method: 'POST', body: JSON.stringify({ id }) })
  if (res.code === 0) loadVoteList(); else alert(res.msg)
}
async function closeVote(id) {
  if (!confirm('确认结束该投票？')) return
  const res = await api('/vote/close', { method: 'POST', body: JSON.stringify({ id }) })
  if (res.code === 0) loadVoteList(); else alert(res.msg)
}
async function viewVoteResult(id) {
  const res = await api('/vote/result?id=' + id)
  if (res.code === 0) { voteResult.value = res.data; voteResultVisible.value = true }
  else alert(res.msg)
}
async function deleteVote(id) {
  if (!confirm('确认删除该投票？')) return
  const res = await api('/vote/delete', { method: 'POST', body: JSON.stringify({ id }) })
  if (res.code === 0) loadVoteList(); else alert(res.msg)
}
async function loadVoteList() {
  const params = { page: 1, limit: 200 }
  if (voteStatusFilter.value) params.status = voteStatusFilter.value
  const query = Object.entries(params).map(([k,v]) => `${k}=${encodeURIComponent(v)}`).join('&')
  try {
    const res = await api('/vote/list?' + query)
    voteList.value = res.code === 0 ? (res.data?.list || res.data || []) : []
  } catch { voteList.value = [] }
}

// ===== 活动相关 =====
const activityList = ref([])
const activityStatusFilter = ref(0)
const activityFormVisible = ref(false)
const activityFormTitle = ref('新增活动')
const activitySubmitting = ref(false)
const activityForm = reactive({ id: 0, title: '', location: '', content: '', max_participants: 0, start_time: '', end_time: '' })
const activityStatusMap = { 1: '草稿', 2: '报名中', 3: '进行中', 4: '已结束', 5: '已取消' }
const signupListVisible = ref(false)
const signupActivityId = ref(0)
const signupActivityTitle = ref('')
const signupList = ref([])
const signupStatusFilter = ref(-1)
const signupStatusMap = { 0: '待审核', 1: '已通过', 2: '已拒绝' }

function openActivityForm(item) {
  if (item) {
    activityFormTitle.value = '编辑活动'; activityForm.id = item.id
    activityForm.title = item.title; activityForm.location = item.location || ''
    activityForm.content = item.content || ''; activityForm.max_participants = item.max_participants || 0
    activityForm.start_time = item.start_time?.substring(0,16) || ''
    activityForm.end_time = item.end_time?.substring(0,16) || ''
  } else {
    activityFormTitle.value = '新增活动'; activityForm.id = 0
    activityForm.title = ''; activityForm.location = ''; activityForm.content = ''
    activityForm.max_participants = 0; activityForm.start_time = ''; activityForm.end_time = ''
  }
  activityFormVisible.value = true
}
async function submitActivity() {
  if (!activityForm.title) return alert('请输入标题')
  activitySubmitting.value = true
  const data = { title: activityForm.title, location: activityForm.location, content: activityForm.content, max_participants: activityForm.max_participants, start_time: activityForm.start_time, end_time: activityForm.end_time }
  if (activityForm.id) data.id = activityForm.id
  const url = activityForm.id ? '/activity/edit' : '/activity/add'
  try {
    const res = await api(url, { method: 'POST', body: JSON.stringify(data) })
    if (res.code === 0) { activityFormVisible.value = false; loadActivityList() }
    else alert(res.msg || '操作失败')
  } catch(e) { alert('操作失败: ' + e.message) }
  activitySubmitting.value = false
}
async function publishActivity(id) {
  if (!confirm('确认发布该活动？')) return
  const res = await api('/activity/publish', { method: 'POST', body: JSON.stringify({ id }) })
  if (res.code === 0) loadActivityList(); else alert(res.msg)
}
async function startActivity(id) {
  if (!confirm('确认开始该活动？')) return
  const res = await api('/activity/start', { method: 'POST', body: JSON.stringify({ id }) })
  if (res.code === 0) loadActivityList(); else alert(res.msg)
}
async function completeActivity(id) {
  if (!confirm('确认结束该活动？')) return
  const res = await api('/activity/complete', { method: 'POST', body: JSON.stringify({ id }) })
  if (res.code === 0) loadActivityList(); else alert(res.msg)
}
async function cancelActivity(id) {
  if (!confirm('确认取消该活动？')) return
  const res = await api('/activity/cancel', { method: 'POST', body: JSON.stringify({ id }) })
  if (res.code === 0) loadActivityList(); else alert(res.msg)
}
async function deleteActivity(id) {
  if (!confirm('确认删除该活动？')) return
  const res = await api('/activity/delete', { method: 'POST', body: JSON.stringify({ id }) })
  if (res.code === 0) loadActivityList(); else alert(res.msg)
}
async function loadActivityList() {
  const params = { page: 1, limit: 200 }
  if (activityStatusFilter.value) params.status = activityStatusFilter.value
  const query = Object.entries(params).map(([k,v]) => `${k}=${encodeURIComponent(v)}`).join('&')
  try {
    // 确保 signup status 字段存在
    api('/activity/ensureSignupStatus').catch(()=>{})
    const res = await api('/activity/list?' + query)
    activityList.value = res.code === 0 ? (res.data?.list || res.data || []) : []
  } catch { activityList.value = [] }
}
function openSignupList(activity) {
  signupActivityId.value = activity.id
  signupActivityTitle.value = activity.title
  signupStatusFilter.value = -1
  signupListVisible.value = true
  loadSignupList()
}
async function loadSignupList() {
  const params = { activity_id: signupActivityId.value, page: 1, limit: 500 }
  if (signupStatusFilter.value >= 0) params.status = signupStatusFilter.value
  const query = Object.entries(params).map(([k,v]) => `${k}=${encodeURIComponent(v)}`).join('&')
  try {
    const res = await api('/activity/signups?' + query)
    signupList.value = res.code === 0 ? (res.data?.list || res.data || []) : []
  } catch { signupList.value = [] }
}
async function approveSignup(id) {
  const res = await api('/activity/approveSignup', { method: 'POST', body: JSON.stringify({ id }) })
  if (res.code === 0) { loadSignupList(); loadActivityList() } else alert(res.msg)
}
async function rejectSignup(id) {
  const res = await api('/activity/rejectSignup', { method: 'POST', body: JSON.stringify({ id }) })
  if (res.code === 0) { loadSignupList(); loadActivityList() } else alert(res.msg)
}
async function cancelSignup(id) {
  if (!confirm('确认移除该报名记录？')) return
  const res = await api('/activity/cancelSignup', { method: 'POST', body: JSON.stringify({ id }) })
  if (res.code === 0) { loadSignupList(); loadActivityList() } else alert(res.msg)
}

onMounted(async () => {
  await loadCommunities()
  await loadAll()
})

async function loadCommunities() {
  const res = await rawApi('/dashboard/communityList')
  if (res.code === 0 && Array.isArray(res.data)) {
    communities.value = res.data
    // 如果已经有选择则保留；否则用后端返回的第一个
    if (!selectedCommunityId.value && communities.value.length > 0) {
      selectedCommunityId.value = communities.value[0].id
      localStorage.setItem('manager_cid', String(selectedCommunityId.value))
    }
  }
}

function switchCommunity() {
  localStorage.setItem('manager_cid', String(selectedCommunityId.value))
  loadAll()
}

async function loadAll() {
  loading.value = true
  error.value = ''
  try {
    const [infoRes, statRes, incomeRes, repairRes, ownerRes, todoRes, chargeRes] = await Promise.all([
      api('/dashboard/communityInfo'),
      api('/dashboard/statistics'),
      api('/dashboard/incomeTrend'),
      api('/dashboard/repairStats'),
      api('/dashboard/ownerStats'),
      api('/dashboard/pendingTodo'),
      api('/dashboard/chargeRate'),
    ])
    if (infoRes.code === 0) {
      communityName.value = infoRes.data.name || ''
      managerName.value = infoRes.data.contact || ''
    }
    if (statRes.code === 0) stats.value = statRes.data
    if (incomeRes.code === 0) income.value = { months: incomeRes.data.months || [], values: incomeRes.data.income || [] }
    if (repairRes.code === 0) repair.value = repairRes.data
    if (ownerRes.code === 0) owner.value = ownerRes.data
    if (todoRes.code === 0) todos.value = Array.isArray(todoRes.data) ? todoRes.data : (todoRes.data?.list || [])
    if (chargeRes.code === 0) chargeRate.value = chargeRes.data
  } catch (e) {
    error.value = '数据加载失败：' + e.message
  }
  loading.value = false
}

async function loadTabData() {
  if (activeTab.value === 'dashboard') return loadAll()
  if (activeTab.value === 'vote') return loadVoteList()
  if (activeTab.value === 'activity') return loadActivityList()
  loading.value = true
  try {
    const params = { page: page.value, limit }
    if (activeTab.value === 'owner' && listKeyword.value) params.keyword = listKeyword.value
    if (activeTab.value !== 'owner' && listStatus.value) params.status = listStatus.value
    const query = Object.entries(params).map(([k, v]) => `${k}=${encodeURIComponent(v)}`).join('&')
    const res = await api(`${apiMap[activeTab.value]}?${query}`)
    if (res.code === 0) {
      listData.value = res.data?.list || res.data || []
      listTotal.value = res.count || 0
    } else {
      listData.value = []
      listTotal.value = 0
    }
  } catch { listData.value = []; listTotal.value = 0 }
  loading.value = false
}

function logout() {
  auth.removeToken()
  router.replace('/login')
}
</script>

<style scoped>
.dashboard{padding:20px;max-width:1200px;margin:0 auto}
header{display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;flex-wrap:wrap;gap:10px}
header h1{font-size:22px;color:#f1f5f9;display:flex;align-items:center;gap:8px}
.title-divider{color:#64748b;font-weight:300}
.title-sub{font-size:16px;color:#94a3b8;font-weight:400}
.header-center{flex:1;display:flex;justify-content:center}
.community-select{height:38px;background:#1e293b;border:1px solid #334155;border-radius:8px;padding:0 14px;color:#e2e8f0;font-size:14px;cursor:pointer;outline:none;min-width:180px}
.community-select:focus{border-color:#10b981}
.community-select option{background:#1e293b;color:#e2e8f0}
.header-right{display:flex;align-items:center;gap:12px}
.user-info{color:#94a3b8;font-size:13px}
.btn-logout{background:rgba(255,255,255,.1);border:1px solid #334155;color:#94a3b8;padding:8px 18px;border-radius:8px;cursor:pointer;font-size:13px}
.btn-logout:hover{background:rgba(255,255,255,.15)}

.tab-nav{display:flex;gap:6px;margin-bottom:20px;overflow-x:auto}
.tab-nav button{background:#1e293b;border:1px solid #334155;color:#94a3b8;padding:10px 18px;border-radius:10px;cursor:pointer;font-size:14px;white-space:nowrap;transition:.2s}
.tab-nav button:hover{background:#334155;color:#e2e8f0}
.tab-nav button.active{background:#10b981;border-color:#10b981;color:#fff}

.loading,.error{text-align:center;padding:80px 0;color:#64748b;font-size:16px}
.error{color:#f87171}

.stats-row{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:14px;margin-bottom:24px}
.stat-card{background:#1e293b;border:1px solid #334155;border-radius:14px;padding:20px;text-align:center;border-top:3px solid #10b981}
.stat-val{font-size:28px;font-weight:700;color:#f1f5f9;margin-bottom:4px}
.stat-label{font-size:13px;color:#64748b}

.grid2{display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:16px}
.panel{background:#1e293b;border:1px solid #334155;border-radius:14px;padding:20px}
.panel h3{font-size:16px;color:#e2e8f0;margin-bottom:16px}

.bar-chart{display:flex;align-items:flex-end;justify-content:center;height:160px;gap:8px;padding-top:10px}
.bar{flex:1;max-width:50px;background:linear-gradient(180deg,#10b981,#34d399);border-radius:6px 6px 0 0;position:relative;min-height:4px;transition:height .5s}
.bar-val{position:absolute;top:-20px;left:50%;transform:translateX(-50%);font-size:11px;color:#94a3b8;white-space:nowrap}
.bar-label{position:absolute;bottom:-22px;left:50%;transform:translateX(-50%);font-size:10px;color:#64748b}

.stat-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.mini-stat{background:#0f172a;border-radius:10px;padding:14px;text-align:center}
.mini-stat strong{display:block;font-size:22px;color:#f1f5f9;margin-bottom:4px}
.mini-stat small{font-size:12px;color:#64748b}

.big-num{font-size:36px;font-weight:700;color:#f1f5f9;margin-bottom:16px}
.big-num small{display:block;font-size:13px;font-weight:400;color:#64748b;margin-top:2px}
.big-num.secondary{font-size:28px;color:#34d399}
.big-num.tertiary{font-size:28px;color:#818cf8}

.todo-list{display:flex;flex-direction:column;gap:8px}
.todo-item{display:flex;align-items:center;gap:10px;padding:10px;background:#0f172a;border-radius:8px}
.todo-icon{font-size:18px}
.todo-title{flex:1;font-size:14px;color:#e2e8f0}
.todo-count{background:#1e293b;color:#34d399;padding:3px 10px;border-radius:12px;font-size:12px;font-weight:600}

.charge-wrap{text-align:center}
.charge-big{font-size:42px;font-weight:700;color:#10b981;margin-bottom:10px}
.charge-bar-bg{height:10px;background:#0f172a;border-radius:5px;overflow:hidden;margin-bottom:8px}
.charge-bar{height:100%;background:linear-gradient(90deg,#10b981,#34d399);border-radius:5px;transition:width .5s}
.charge-detail{font-size:13px;color:#64748b}

/* List */
.list-toolbar{display:flex;gap:10px;margin-bottom:16px}
.list-toolbar input{flex:1;max-width:280px;height:40px;background:#1e293b;border:1px solid #334155;border-radius:8px;padding:0 12px;color:#e2e8f0;font-size:14px;outline:none}
.list-toolbar input:focus{border-color:#10b981}
.list-toolbar select{height:40px;background:#1e293b;border:1px solid #334155;border-radius:8px;padding:0 12px;color:#e2e8f0;font-size:14px;outline:none}
.btn-search{height:40px;padding:0 20px;background:#10b981;border:none;border-radius:8px;color:#fff;font-size:14px;cursor:pointer}

.list-table-wrap{overflow-x:auto}
.list-table{width:100%;border-collapse:collapse}
.list-table th{text-align:left;padding:12px 10px;background:#1e293b;color:#94a3b8;font-size:13px;font-weight:500;border-bottom:1px solid #334155;white-space:nowrap}
.list-table td{padding:10px;border-bottom:1px solid #1e293b;color:#e2e8f0;font-size:14px}
.list-table tbody tr:hover{background:#1e293b}
.text-ellipsis{max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.tag{display:inline-block;padding:2px 8px;border-radius:4px;font-size:12px;background:#334155;color:#94a3b8}
.tag-1{background:#f59e0b22;color:#f59e0b}
.tag-2{background:#3b82f622;color:#3b82f6}
.tag-3{background:#10b98122;color:#10b981}
.tag-4{background:#10b98122;color:#10b981}
.tag-5{background:#ef444422;color:#ef4444}

.empty{text-align:center;padding:60px 0;color:#64748b;font-size:15px}

.pagination{display:flex;align-items:center;justify-content:center;gap:12px;margin-top:16px}
.pagination button{height:36px;padding:0 16px;background:#1e293b;border:1px solid #334155;border-radius:8px;color:#e2e8f0;cursor:pointer;font-size:13px}
.pagination button:disabled{opacity:.4;cursor:default}
.pagination span{color:#64748b;font-size:13px}

/* Modal */
.modal-overlay{position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.6);display:flex;align-items:center;justify-content:center;z-index:1000}
.modal-box{background:#1e293b;border:1px solid #334155;border-radius:14px;padding:24px;width:90%;max-width:520px;max-height:85vh;overflow-y:auto}
.modal-box h3{font-size:17px;color:#e2e8f0;margin-bottom:18px}
.form-group{margin-bottom:14px}
.form-group label{display:block;font-size:13px;color:#94a3b8;margin-bottom:6px}
.form-row{display:flex;gap:12px}
.form-row .form-group{flex:1}
.input{width:100%;background:#0f172a;border:1px solid #334155;border-radius:8px;padding:9px 12px;color:#e2e8f0;font-size:14px;outline:none;box-sizing:border-box}
.input:focus{border-color:#10b981}
textarea.input{resize:vertical;min-height:60px}
.option-row{display:flex;gap:8px;align-items:center;margin-bottom:8px}
.option-row .input{flex:1}
.modal-actions{display:flex;justify-content:flex-end;gap:10px;margin-top:18px;padding-top:16px;border-top:1px solid #334155}
.btn-cancel{background:#334155;border:none;color:#94a3b8;padding:9px 20px;border-radius:8px;cursor:pointer;font-size:14px}
.btn-cancel:hover{background:#475569}

/* Action buttons in table */
.action-btns{display:flex;gap:4px;flex-wrap:wrap}
.btn-mini{background:#334155;border:none;color:#94a3b8;padding:3px 10px;border-radius:6px;cursor:pointer;font-size:12px;white-space:nowrap}
.btn-mini:hover{background:#475569;color:#e2e8f0}
.btn-mini.btn-green{background:#10b98122;color:#10b981}
.btn-mini.btn-green:hover{background:#10b98133}
.btn-mini.btn-blue{background:#3b82f622;color:#3b82f6}
.btn-mini.btn-blue:hover{background:#3b82f633}
.btn-mini.btn-red{background:#ef444422;color:#ef4444}
.btn-mini.btn-red:hover{background:#ef444433}
.btn-mini.btn-danger{background:#ef444422;color:#ef4444}
.btn-mini.btn-danger:hover{background:#ef444433}
.btn-mini.btn-warn{background:#f59e0b22;color:#f59e0b}
.btn-mini.btn-warn:hover{background:#f59e0b33}

/* Vote result */
.result-bar{margin-bottom:12px}
.result-label{display:flex;justify-content:space-between;font-size:13px;color:#e2e8f0;margin-bottom:4px}
.result-label span:last-child{color:#94a3b8}
.result-bg{height:8px;background:#0f172a;border-radius:4px;overflow:hidden}
.result-fill{height:100%;background:linear-gradient(90deg,#10b981,#34d399);border-radius:4px;transition:width .5s}
</style>
