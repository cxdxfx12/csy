<template>
  <div class="page">
    <header>
      <button class="back" @click="$router.back()">←</button>
      <h1>个人中心</h1>
    </header>

    <!-- 个人资料 -->
    <div class="user-card" v-if="profile">
      <span class="avatar">👷</span>
      <div>
        <strong>{{ profile.realname || profile.username }}</strong>
        <small>{{ profile.phone || '--' }}</small>
        <span class="community-tag" v-if="profile.community_name">{{ profile.community_name }}</span>
      </div>
      <button class="edit-btn" @click="showEditProfile=true">编辑</button>
    </div>

    <!-- 快捷菜单 -->
    <div class="menu-grid">
      <div class="menu-item" :class="{active:activeTab==='attendance'}" @click="activeTab='attendance'">
        <span>📅</span><label>考勤记录</label>
      </div>
      <div class="menu-item" :class="{active:activeTab==='schedule'}" @click="activeTab='schedule'">
        <span>📋</span><label>排班记录</label>
      </div>
      <div class="menu-item" :class="{active:activeTab==='salary'}" @click="activeTab='salary'">
        <span>💰</span><label>工资记录</label>
      </div>
    </div>

    <!-- 考勤 -->
    <div v-if="activeTab==='attendance'" class="section">
      <div class="section-hd">
        <span>{{ currentMonth }} 考勤</span>
        <button class="month-btn" @click="prevMonth">←</button>
        <button class="month-btn" @click="nextMonth">→</button>
      </div>
      <div v-if="attLoading" class="loading">加载中...</div>
      <div v-else-if="!attList.length" class="empty">暂无记录</div>
      <div v-else class="list">
        <div v-for="item in attList" :key="item.id" class="list-item">
          <div>
            <strong>{{ item.attendance_date }}</strong>
            <small>{{ item.sign_in_time || '--' }} ~ {{ item.sign_out_time || '--' }}</small>
          </div>
          <span class="tag" :style="{color:attColor(item.type)}">{{ attText(item.type) }}</span>
        </div>
      </div>
    </div>

    <!-- 排班 -->
    <div v-if="activeTab==='schedule'" class="section">
      <div class="section-hd">
        <span>{{ currentMonth }} 排班</span>
        <button class="month-btn" @click="prevMonth">←</button>
        <button class="month-btn" @click="nextMonth">→</button>
      </div>
      <div v-if="schLoading" class="loading">加载中...</div>
      <div v-else-if="!schList.length" class="empty">暂无记录</div>
      <div v-else class="list">
        <div v-for="item in schList" :key="item.id" class="list-item">
          <div>
            <strong>{{ item.schedule_date }}</strong>
            <small>{{ item.start_time || '--' }} ~ {{ item.end_time || '--' }}</small>
            <small v-if="item.work_area">· {{ item.work_area }}</small>
          </div>
          <span class="tag" :style="{color:item.shift==='早班'?'#10b981':item.shift==='中班'?'#f59e0b':'#3b82f6'}">{{ item.shift }}</span>
        </div>
      </div>
    </div>

    <!-- 工资 -->
    <div v-if="activeTab==='salary'" class="section">
      <div v-if="salLoading" class="loading">加载中...</div>
      <div v-else-if="!salList.length" class="empty">暂无记录</div>
      <div v-else class="list">
        <div v-for="item in salList" :key="item.id" class="salary-card">
          <div class="sal-hd">
            <strong>{{ item.salary_month }}</strong>
            <span :class="item.status===1?'paid':'unpaid'">{{ item.status===1?'已发放':'未发放' }}</span>
          </div>
          <div class="sal-grid">
            <div><small>基本工资</small><b>¥{{ item.base_salary||0 }}</b></div>
            <div><small>奖金</small><b>¥{{ item.bonus||0 }}</b></div>
            <div><small>加班费</small><b>¥{{ item.overtime_pay||0 }}</b></div>
            <div><small>补贴</small><b>¥{{ item.subsidy||0 }}</b></div>
            <div><small>扣款</small><b>¥{{ item.deduction||0 }}</b></div>
            <div><small>社保</small><b>¥{{ item.social_insurance||0 }}</b></div>
          </div>
          <div class="sal-total">
            <small>实发工资</small>
            <b>¥{{ item.net_salary||0 }}</b>
          </div>
        </div>
      </div>
    </div>

    <!-- 编辑资料弹窗 -->
    <div class="modal" v-if="showEditProfile" @click.self="showEditProfile=false">
      <div class="modal-box">
        <h3>编辑资料</h3>
        <div class="form">
          <label>昵称</label>
          <input v-model="editForm.nickname" placeholder="昵称" />
          <label>邮箱</label>
          <input v-model="editForm.email" placeholder="邮箱" />
          <label>手机号</label>
          <input v-model="editForm.phone" placeholder="手机号" />
          <button class="btn-primary" @click="saveProfile" :disabled="saving">{{ saving?'保存中...':'保存' }}</button>
          <button class="btn-cancel" @click="showEditProfile=false">取消</button>
        </div>
      </div>
    </div>
  </div>
</template>
<script setup>
import { ref, reactive, onMounted } from 'vue'
import { createApi } from '@/shared/api.js'
import { showToast } from '@/shared/utils.js'
const api = createApi('/api/staff', 'staff_token')

const profile = ref(null)
const activeTab = ref('attendance')
const currentMonth = ref(new Date().toISOString().substring(0, 7))

const attList = ref([])
const schList = ref([])
const salList = ref([])
const attLoading = ref(false)
const schLoading = ref(false)
const salLoading = ref(false)

const showEditProfile = ref(false)
const saving = ref(false)
const editForm = reactive({ nickname: '', email: '', phone: '' })

const attTypeMap = { 1: '出勤', 2: '迟到', 3: '早退', 4: '请假', 5: '旷工' }
const attTypeColor = { 1: '#10b981', 2: '#f59e0b', 3: '#f59e0b', 4: '#3b82f6', 5: '#ef4444' }
const attText = t => attTypeMap[t] || '未知'
const attColor = t => attTypeColor[t] || '#999'

function prevMonth() {
  const d = new Date(currentMonth.value + '-01')
  d.setMonth(d.getMonth() - 1)
  currentMonth.value = d.toISOString().substring(0, 7)
  loadTabData()
}
function nextMonth() {
  const d = new Date(currentMonth.value + '-01')
  d.setMonth(d.getMonth() + 1)
  currentMonth.value = d.toISOString().substring(0, 7)
  loadTabData()
}

onMounted(async () => {
  const res = await api('/profile/info')
  if (res.code === 0) profile.value = res.data
  loadTabData()
})

async function loadTabData() {
  if (activeTab.value === 'attendance') {
    attLoading.value = true
    try {
      const res = await api(`/attendance/lists?month=${currentMonth.value}`)
      if (res.code === 0) attList.value = res.data.list || []
    } catch { attList.value = [] } finally { attLoading.value = false }
  } else if (activeTab.value === 'schedule') {
    schLoading.value = true
    try {
      const res = await api(`/schedule/lists?month=${currentMonth.value}`)
      if (res.code === 0) schList.value = res.data.list || []
    } catch { schList.value = [] } finally { schLoading.value = false }
  } else if (activeTab.value === 'salary') {
    salLoading.value = true
    try {
      const res = await api('/salary/lists')
      if (res.code === 0) salList.value = res.data.list || []
    } catch { salList.value = [] } finally { salLoading.value = false }
  }
}

// 监听tab切换
import { watch } from 'vue'
watch(activeTab, () => loadTabData())

async function saveProfile() {
  saving.value = true
  try {
    const res = await api('/profile/edit', {
      method: 'POST',
      body: JSON.stringify({
        nickname: editForm.nickname,
        email: editForm.email,
        phone: editForm.phone,
      })
    })
    if (res.code === 0) {
      showToast('保存成功')
      showEditProfile.value = false
      // 刷新资料
      const r = await api('/profile/info')
      if (r.code === 0) profile.value = r.data
    } else {
      showToast(res.msg || '保存失败')
    }
  } catch { showToast('保存失败') } finally { saving.value = false }
}
</script>
<style scoped>
.page{padding:16px}
header{display:flex;align-items:center;gap:12px;margin-bottom:16px}
header h1{font-size:18px;flex:1}
.back{background:none;border:none;font-size:20px;cursor:pointer;padding:4px 8px}
.user-card{background:#fff;border-radius:12px;padding:16px;display:flex;align-items:center;gap:12px;margin-bottom:20px;box-shadow:0 1px 3px rgba(0,0,0,.06);position:relative}
.avatar{font-size:36px}
.user-card strong{display:block;font-size:16px}
.user-card small{color:#9ca3af;font-size:13px;display:block}
.community-tag{display:inline-block;margin-top:4px;padding:2px 10px;background:#e0f2fe;color:#0369a1;font-size:12px;border-radius:10px;font-weight:500}
.edit-btn{position:absolute;right:16px;top:50%;transform:translateY(-50%);background:none;border:1px solid #e5e7eb;padding:6px 14px;border-radius:6px;font-size:13px;color:#2563eb;cursor:pointer}
.menu-grid{display:flex;gap:10px;margin-bottom:20px}
.menu-item{flex:1;background:#fff;border-radius:10px;padding:14px 8px;text-align:center;box-shadow:0 1px 3px rgba(0,0,0,.06);cursor:pointer;transition:all .2s}
.menu-item.active{background:#2563eb;color:#fff}
.menu-item span{font-size:28px;display:block;margin-bottom:6px}
.menu-item label{font-size:13px}
.menu-item.active label{color:#fff}
.section{margin-top:8px}
.section-hd{display:flex;align-items:center;gap:8px;margin-bottom:14px;font-size:15px;font-weight:600}
.month-btn{background:#f3f4f6;border:1px solid #e5e7eb;padding:4px 10px;border-radius:6px;font-size:13px;cursor:pointer}
.loading,.empty{text-align:center;padding:40px;color:#9ca3af}
.list{display:flex;flex-direction:column;gap:8px}
.list-item{background:#fff;border-radius:10px;padding:14px;display:flex;justify-content:space-between;align-items:center;box-shadow:0 1px 3px rgba(0,0,0,.06)}
.list-item strong{display:block;font-size:14px}
.list-item small{font-size:12px;color:#9ca3af}
.tag{font-size:13px;font-weight:600}
.salary-card{background:#fff;border-radius:12px;padding:16px;box-shadow:0 1px 3px rgba(0,0,0,.06);margin-bottom:10px}
.sal-hd{display:flex;justify-content:space-between;align-items:center;margin-bottom:10px}
.sal-hd strong{font-size:15px}
.paid{color:#10b981;font-size:13px;font-weight:600}
.unpaid{color:#f59e0b;font-size:13px;font-weight:600}
.sal-grid{display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px 12px;margin-bottom:10px;padding-bottom:10px;border-bottom:1px solid #f3f4f6}
.sal-grid small{display:block;font-size:12px;color:#9ca3af}
.sal-grid b{font-size:14px;color:#1f2937}
.sal-total{display:flex;justify-content:space-between;align-items:center}
.sal-total small{font-size:12px;color:#6b7280}
.sal-total b{font-size:18px;color:#2563eb}
.modal{position:fixed;inset:0;background:rgba(0,0,0,.4);display:flex;align-items:center;justify-content:center;z-index:100;padding:20px}
.modal-box{background:#fff;border-radius:16px;padding:24px;width:100%;max-width:400px}
.modal-box h3{font-size:17px;margin-bottom:16px}
.form{display:flex;flex-direction:column;gap:10px}
.form label{font-size:13px;color:#6b7280;margin-bottom:-6px}
.form input{width:100%;border:1px solid #e5e7eb;border-radius:8px;padding:10px 14px;font-size:14px;outline:none;margin-bottom:4px}
.form input:focus{border-color:#2563eb}
.btn-primary{width:100%;height:44px;background:#2563eb;color:#fff;border:none;border-radius:8px;font-size:15px;cursor:pointer;margin-top:4px}
.btn-primary:disabled{opacity:.6}
.btn-cancel{width:100%;height:40px;background:#fff;color:#6b7280;border:1px solid #e5e7eb;border-radius:8px;font-size:14px;cursor:pointer;margin-top:4px}
</style>
