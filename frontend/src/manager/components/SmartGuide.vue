<template>
  <div class="sg-root">
    <!-- 浮动按钮 -->
    <button class="sg-fab" :class="{ active: isOpen }" @click="togglePanel" title="大圣导航助手">
      <span class="sg-avatar">🐵</span>
      <span class="sg-tail"></span>
    </button>

    <!-- 面板 -->
    <Transition name="sg-slide">
      <div class="sg-panel" v-if="isOpen">
        <div class="sg-hd">
          <span class="sg-hd-icon">🐵</span>
          <span class="sg-hd-title">大圣导航助手</span>
          <button class="sg-hd-close" @click="isOpen = false">✕</button>
        </div>

        <!-- 搜索框 -->
        <div class="sg-search">
          <div class="sg-input-wrap">
            <span class="sg-input-icon">🔍</span>
            <input
              ref="searchRef"
              v-model="q"
              class="sg-input"
              placeholder="搜索业主、房号、工单、账单、车牌..."
              @input="onInput"
              @keydown.enter="doSearch"
            />
            <button v-if="q" class="sg-clear" @click="q='';results=[]">✕</button>
          </div>
          <div class="sg-tags">
            <span v-for="t in quickTags" :key="t" class="sg-tag" @click="quickSearch(t)">{{ t }}</span>
          </div>
        </div>

        <!-- 内容体 -->
        <div class="sg-body">
          <!-- 加载中 -->
          <div v-if="loading" class="sg-state">
            <span class="sg-spin">⏳</span>
            <span>搜索中...</span>
          </div>

          <!-- 搜索结果 -->
          <template v-else-if="q.trim()">
            <template v-if="results.length">
              <div class="sg-sec-label">
                <span>📊</span> 数据结果 ({{ results.length }})
              </div>
              <div class="sg-list">
                <div
                  v-for="r in results"
                  :key="r.type + '-' + r.id"
                  class="sg-item"
                  @click="openResult(r)"
                >
                  <span class="sg-badge" :class="'bd-' + r.type">{{ r.typeName }}</span>
                  <div class="sg-info">
                    <div class="sg-title">{{ r.title }}</div>
                    <div class="sg-sub" v-html="r.subtitle"></div>
                  </div>
                  <span class="sg-arr">›</span>
                </div>
              </div>
            </template>
            <div v-else class="sg-state">
              <span style="font-size:28px">🔍</span>
              <p>未找到匹配结果</p>
              <p class="sg-hint">试试搜索：业主姓名、房号、工单号、账单号、车牌、手机号...</p>
            </div>
          </template>

          <!-- 默认：提示输入搜索 -->
          <template v-else>
            <div class="sg-state">
              <span style="font-size:36px">🐵</span>
              <p style="color:var(--text-2);font-weight:500">你好，我是大圣</p>
              <p class="sg-hint">输入关键词搜索业主、房号、工单、账单、车牌…</p>
            </div>
          </template>
        </div>
      </div>
    </Transition>

    <!-- 遮罩 -->
    <Transition name="sg-fade">
      <div class="sg-overlay" v-if="isOpen" @click="isOpen = false"></div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, nextTick, onMounted, onUnmounted } from 'vue'

const isOpen = ref(false)
const q = ref('')
const loading = ref(false)
const results = ref([])
const searchRef = ref(null)

let debounceTimer = null

// 快捷搜索标签
const quickTags = ['业主', '房号', '工单', '账单', '车牌', '公告']

// 常见问题
const faqList = [
  { q: '怎么创建账单？', a: '进入收费管理 → 账单管理，点击"新增账单"按钮，选择小区、楼栋、房间后填写收费项目即可生成账单。' },
  { q: '如何添加业主？', a: '进入业主管理 Tab，可以查看所有业主信息。业主可以通过微信小程序自助注册，经理可在后台审核添加。' },
  { q: '报修工单怎么处理？', a: '切换到报修 Tab，可查看所有工单。经理可查看各状态工单数量，跟进处理进度。维修师傅会通过员工端接单处理。' },
  { q: '投诉建议如何处理？', a: '切换到投诉 Tab，查看所有投诉。按状态筛选待处理/处理中/已解决的投诉，及时跟进回复业主。' },
  { q: '怎样查看收费率？', a: '在驾驶舱页面可直接看到收费率图表。切换到账单 Tab 可查看详细账单列表，按未缴/部分缴纳/已缴清筛选。' },
  { q: '如何发布投票？', a: '切换到投票 Tab，点击"新增投票"，填写标题、选项、时间等信息，草稿状态下可编辑，确认无误后发布。' },
  { q: '怎么组织社区活动？', a: '切换到活动 Tab，点击"新增活动"，填写标题、地点、时间、人数上限等信息发布，业主可通过小程序报名参加。' },
  { q: '驾驶舱怎么看数据？', a: '登录后默认进入驾驶舱页面，可查看房产总数、业主总数、本月收入、待收金额、收费率、待处理工单等核心指标。' },
]

// ========== 搜索 ==========
function onInput() {
  if (debounceTimer) clearTimeout(debounceTimer)
  if (!q.value.trim()) {
    results.value = []
    loading.value = false
    return
  }
  loading.value = true
  debounceTimer = setTimeout(() => doSearch(), 300)
}

async function doSearch() {
  const kw = q.value.trim()
  if (!kw) { results.value = []; loading.value = false; return }

  try {
    const token = localStorage.getItem('manager_token')
    const res = await fetch(`/api/manager/search/global?keyword=${encodeURIComponent(kw)}`, {
      headers: token ? { 'Authorization': `Bearer ${token}` } : {}
    })
    const data = await res.json()
    if (data.code === 0) {
      results.value = data.data?.results || []
    } else {
      // API 不可用时，在 FAQ 中本地搜索
      results.value = searchLocalFaq(kw)
    }
  } catch {
    // 网络错误时，本地 FAQ 搜索
    results.value = searchLocalFaq(kw)
  } finally {
    loading.value = false
  }
}

function searchLocalFaq(kw) {
  const matches = faqList.filter(f =>
    f.q.includes(kw) || f.a.includes(kw)
  )
  return matches.map((f, i) => ({
    type: 'faq',
    id: i,
    typeName: '常见问题',
    title: f.q,
    subtitle: f.a,
    route: null
  }))
}

function quickSearch(text) {
  q.value = text
  onInput()
}

// ========== 导航 ==========
function navigateByType(item) {
  // 如果 API 返回了路由，就直接跳
  // 否则按类型映射到经理端 Tab
  const routeMap = {
    owner: '/dashboard?tab=owner',
    bill: '/dashboard?tab=bill',
    repair_order: '/dashboard?tab=repair',
    complaint: '/dashboard?tab=complaint',
    vote: '/dashboard?tab=vote',
    activity: '/dashboard?tab=activity',
    room: '/dashboard?tab=owner',
    building: '/dashboard',  
    community: '/dashboard',
    staff: '/dashboard',
    notice: '/dashboard',
    supplier: '/dashboard',
    payment: '/dashboard?tab=bill',
    vehicle: '/dashboard',
    access_card: '/dashboard',
    faq: null
  }
  return routeMap[item.type] || '/dashboard'
}

function openResult(item) {
  if (!item.type || item.type === 'faq') {
    window.open('/answer/#q=' + encodeURIComponent(item.title || q.value), '_blank')
    return
  }
  const route = navigateByType(item)
  if (route) {
    window.location.hash = '#' + route
    isOpen.value = false
  }
}

function askQuestion(faq) {
  window.open('/answer/#q=' + encodeURIComponent(faq.q), '_blank')
}

// ========== 面板控制 ==========
function togglePanel() {
  isOpen.value = !isOpen.value
  if (isOpen.value) {
    q.value = ''
    results.value = []
    nextTick(() => searchRef.value?.focus())
  }
}

function onKeydown(e) {
  if (e.key === 'Escape' && isOpen.value) {
    isOpen.value = false
  }
}

onMounted(() => document.addEventListener('keydown', onKeydown))
onUnmounted(() => document.removeEventListener('keydown', onKeydown))
</script>

<style scoped>
/* ===== Root ===== */
.sg-root { position: fixed; right: 20px; bottom: 80px; z-index: 5000; }

/* ===== Floating Button ===== */
.sg-fab {
  width: 52px; height: 52px;
  border-radius: 50%;
  background: linear-gradient(135deg, #C68642, #8B5E3C);
  border: 2px solid rgba(255,255,255,.15);
  cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  box-shadow: 0 4px 18px rgba(139,94,60,0.45);
  transition: all .3s cubic-bezier(.4,0,.2,1);
  position: relative; overflow: visible;
  -webkit-tap-highlight-color: transparent;
}
.sg-fab:hover { transform: scale(1.1); box-shadow: 0 6px 24px rgba(139,94,60,0.55); }
.sg-fab.active { transform: scale(.9); opacity: 0; pointer-events: none; }

.sg-avatar {
  font-size: 28px; line-height: 1;
  filter: drop-shadow(0 1px 2px rgba(0,0,0,.3));
  z-index: 1;
}

/* 尾巴动画 */
.sg-tail {
  position: absolute;
  right: -5px; bottom: 2px;
  width: 16px; height: 16px;
  border: 2.5px solid transparent;
  border-right-color: #8B5E3C;
  border-bottom-color: #8B5E3C;
  border-radius: 0 0 60% 0;
  transform-origin: left top;
  animation: tailWag .7s ease-in-out infinite;
}
@keyframes tailWag {
  0%, 100% { transform: rotate(-10deg); }
  50% { transform: rotate(30deg); }
}

/* ===== Panel ===== */
.sg-panel {
  position: absolute;
  bottom: 64px; right: 0;
  width: 400px; max-height: 560px;
  background: var(--bg-card);
  border: 1px solid var(--border-1);
  border-radius: var(--r-lg);
  box-shadow: var(--shadow-modal), 0 0 0 1px var(--border-2);
  display: flex; flex-direction: column;
  overflow: hidden;
  backdrop-filter: var(--glass-blur-lg);
  -webkit-backdrop-filter: var(--glass-blur-lg);
}

/* Panel transitions */
.sg-slide-enter-active { animation: sgIn .25s cubic-bezier(.4,0,.2,1); }
.sg-slide-leave-active { animation: sgOut .2s cubic-bezier(.4,0,.2,1); }
@keyframes sgIn {
  from { opacity: 0; transform: translateY(16px) scale(.96); }
  to { opacity: 1; transform: translateY(0) scale(1); }
}
@keyframes sgOut {
  from { opacity: 1; transform: translateY(0) scale(1); }
  to { opacity: 0; transform: translateY(12px) scale(.97); }
}

/* ===== Header ===== */
.sg-hd {
  display: flex; align-items: center; gap: 8px;
  padding: 13px 16px;
  background: linear-gradient(135deg, #8B5E3C, #C68642);
  color: #fff; flex-shrink: 0;
}
.sg-hd-icon { font-size: 20px; }
.sg-hd-title { font-size: 15px; font-weight: 700; flex: 1; }
.sg-hd-close {
  width: 26px; height: 26px; border-radius: 50%;
  background: rgba(255,255,255,.2); border: none;
  color: #fff; font-size: 13px; cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  transition: background .2s;
}
.sg-hd-close:hover { background: rgba(255,255,255,.35); }

/* ===== Search ===== */
.sg-search { padding: 12px 14px; border-bottom: 1px solid var(--border-2); flex-shrink: 0; }
.sg-input-wrap {
  display: flex; align-items: center;
  background: var(--bg-input); border: 1px solid var(--border-input);
  border-radius: var(--r-sm); overflow: hidden;
  transition: border-color .3s;
}
.sg-input-wrap:focus-within {
  border-color: var(--border-input-focus);
  box-shadow: 0 0 0 3px rgba(var(--accent-rgb),.1);
}
.sg-input-icon { padding: 0 0 0 12px; font-size: 14px; opacity: .5; flex-shrink: 0; }
.sg-input {
  flex: 1; background: transparent; border: none; outline: none;
  padding: 10px 8px; font-size: 14px; color: var(--text-1);
  font-family: inherit;
}
.sg-input::placeholder { color: var(--text-5); }
.sg-clear {
  background: none; border: none; color: var(--text-4);
  padding: 0 12px; cursor: pointer; font-size: 14px;
  line-height: 1;
}
.sg-clear:hover { color: var(--text-2); }

.sg-tags { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 8px; }
.sg-tag {
  font-size: 11px; padding: 4px 11px; border-radius: 12px;
  background: var(--bg-input); color: var(--text-3);
  cursor: pointer; transition: all .2s;
  border: 1px solid var(--border-3); font-weight: 500;
}
.sg-tag:hover {
  background: var(--accent); color: #fff; border-color: var(--accent);
}

/* ===== Body ===== */
.sg-body { flex: 1; overflow-y: auto; padding: 10px 14px 14px; }

.sg-body::-webkit-scrollbar { width: 4px; }
.sg-body::-webkit-scrollbar-thumb { background: var(--border-2); border-radius: 2px; }

.sg-sec-label {
  display: flex; align-items: center; gap: 6px;
  font-size: 12px; color: var(--text-3); font-weight: 600;
  margin-bottom: 8px;
}
.sg-sec-label span { font-size: 14px; }

/* ===== States ===== */
.sg-state {
  display: flex; flex-direction: column; align-items: center;
  justify-content: center; padding: 36px 0; gap: 4px;
  color: var(--text-4); font-size: 13px;
}
.sg-spin { font-size: 22px; animation: spin 1s linear infinite; display: inline-block; }
@keyframes spin { from { transform: rotate(0deg) } to { transform: rotate(360deg) } }
.sg-hint { font-size: 11px; color: var(--text-5); margin-top: 4px; text-align: center; line-height: 1.6; }

/* ===== Results ===== */
.sg-list { display: flex; flex-direction: column; gap: 5px; }
.sg-item {
  display: flex; align-items: center; gap: 10px;
  padding: 10px 12px; border-radius: 10px;
  cursor: pointer; transition: all .2s;
  border: 1px solid transparent;
  -webkit-tap-highlight-color: transparent;
}
.sg-item:hover { background: rgba(var(--accent-rgb),.06); border-color: var(--border-2); }
.sg-item:active { background: rgba(var(--accent-rgb),.1); }

.sg-badge {
  font-size: 10px; padding: 3px 9px; border-radius: 10px;
  font-weight: 700; white-space: nowrap; flex-shrink: 0;
  letter-spacing: .3px;
}
.bd-owner { background: rgba(59,130,246,.15); color: #3b82f6; }
.bd-room { background: rgba(16,185,129,.15); color: #10b981; }
.bd-building { background: rgba(139,92,246,.15); color: #8b5cf6; }
.bd-community { background: rgba(245,158,11,.15); color: #f59e0b; }
.bd-repair_order { background: rgba(239,68,68,.15); color: #ef4444; }
.bd-bill { background: rgba(14,165,233,.15); color: #0ea5e9; }
.bd-payment { background: rgba(99,102,241,.15); color: #6366f1; }
.bd-vehicle { background: rgba(236,72,153,.15); color: #ec4899; }
.bd-staff { background: rgba(20,184,166,.15); color: #14b8a6; }
.bd-notice { background: rgba(249,115,22,.15); color: #f97316; }
.bd-supplier { background: rgba(168,85,247,.15); color: #a855f7; }
.bd-access_card { background: rgba(6,182,212,.15); color: #06b6d4; }
.bd-faq { background: rgba(var(--accent-rgb),.15); color: var(--accent); }

.sg-info { flex: 1; min-width: 0; }
.sg-info .sg-title {
  font-size: 14px; color: var(--text-2); font-weight: 500;
  overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
}
.sg-info .sg-sub {
  font-size: 11px; color: var(--text-4); margin-top: 2px;
  overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
}
.sg-info .sg-sub :deep(em) {
  font-style: normal; color: var(--accent); font-weight: 600;
}
.sg-arr {
  font-size: 18px; color: var(--text-5); flex-shrink: 0;
  transition: transform .2s;
}
.sg-item:hover .sg-arr { transform: translateX(2px); color: var(--accent); }

/* ===== FAQ ===== */
.sg-faq { display: flex; flex-direction: column; gap: 3px; }
.sg-faq-item {
  display: flex; align-items: center; justify-content: space-between;
  padding: 10px 12px; border-radius: 8px;
  cursor: pointer; transition: all .2s;
  -webkit-tap-highlight-color: transparent;
}
.sg-faq-item:hover { background: rgba(var(--accent-rgb),.06); }
.sg-faq-item:active { background: rgba(var(--accent-rgb),.1); }
.sg-faq-q { font-size: 13px; color: var(--text-2); flex: 1; min-width: 0; }
.sg-faq-item:hover .sg-faq-q { color: var(--accent-light); }
.sg-faq-arr { font-size: 14px; color: var(--text-5); flex-shrink: 0; transition: transform .2s; }
.sg-faq-item:hover .sg-faq-arr { transform: translateX(2px); color: var(--accent); }

.sg-btn-more {
  display: flex; align-items: center; justify-content: center;
  gap: 6px; width: 100%; margin-top: 10px;
  padding: 11px; border-radius: var(--r-sm);
  background: linear-gradient(135deg, #8B5E3C, #C68642);
  border: none; color: #fff; font-size: 13px;
  font-weight: 600; cursor: pointer;
  transition: all .25s; font-family: inherit;
  letter-spacing: .3px;
}
.sg-btn-more:hover { transform: translateY(-1px); box-shadow: 0 4px 16px rgba(139,94,60,.35); }
.sg-btn-more:active { transform: translateY(0); }

/* ===== Overlay ===== */
.sg-overlay {
  position: fixed; top: 0; left: 0; width: 100%; height: 100%;
  background: rgba(0,0,0,.2); z-index: 4999;
}
.sg-fade-enter-active { animation: fIn .2s; }
.sg-fade-leave-active { animation: fOut .2s; }
@keyframes fIn { from { opacity: 0 } to { opacity: 1 } }
@keyframes fOut { from { opacity: 1 } to { opacity: 0 } }

/* ===== Responsive ===== */
@media (max-width: 480px) {
  .sg-root { right: 12px; bottom: 70px; }
  .sg-fab { width: 46px; height: 46px; }
  .sg-avatar { font-size: 24px; }
  .sg-panel {
    position: fixed; top: 50%; left: 50%;
    bottom: auto; right: auto;
    width: calc(100vw - 24px); max-width: 400px;
    max-height: 70vh;
    transform: translate(-50%, -50%);
  }
  .sg-slide-enter-active { animation: sgInMb .25s cubic-bezier(.4,0,.2,1); }
  .sg-slide-leave-active { animation: sgOutMb .2s cubic-bezier(.4,0,.2,1); }
  @keyframes sgInMb {
    from { opacity: 0; transform: translate(-50%,-50%) scale(.94); }
    to { opacity: 1; transform: translate(-50%,-50%) scale(1); }
  }
  @keyframes sgOutMb {
    from { opacity: 1; transform: translate(-50%,-50%) scale(1); }
    to { opacity: 0; transform: translate(-50%,-50%) scale(.94); }
  }
}
</style>
