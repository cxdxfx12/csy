<template>
  <div class="page">
    <header>
      <button class="back" @click="$router.back()">←</button>
      <h1>社区投票</h1>
    </header>

    <!-- 投票详情弹窗 -->
    <div class="overlay" v-if="currentVote" @click.self="currentVote=null">
      <div class="modal">
        <h2>{{ currentVote.title }}</h2>
        <p class="vote-desc">{{ currentVote.content || '' }}</p>
        <div class="vote-meta">
          <span>{{ formatDate(currentVote.start_time) }} ~ {{ formatDate(currentVote.end_time) }}</span>
          <span class="tag" :style="{background:voteStatusColor(currentVote.status)}">
            {{ voteStatusLabel(currentVote.status) }}
          </span>
        </div>

        <p class="vote-type-hint">
          {{ currentVote.type === 2 ? '☑ 多选投票（可选择多个选项）' : '🔘 单选投票（请选择其中一项）' }}
        </p>

        <div class="options">
          <div
            v-for="opt in currentVote.options"
            :key="opt.id"
            class="opt-bar"
            :class="{selected: selectedOptions.has(opt.id)}"
            @click="selectOption(opt.id)"
          >
            <div class="opt-row">
              <span class="opt-check">
                <span class="checkbox-icon" :class="{checked:selectedOptions.has(opt.id)}">
                  {{ selectedOptions.has(opt.id) ? '☑' : '☐' }}
                </span>
              </span>
              <span class="opt-name">{{ opt.title }}</span>
              <span class="opt-count">{{ opt.count }} 票</span>
            </div>
            <div class="bar-bg">
              <div class="bar-fill" :style="{width:opt.percent+'%'}"></div>
            </div>
            <span class="opt-pct">{{ opt.percent }}%</span>
          </div>
        </div>

        <div class="modal-actions">
          <button v-if="!currentVote.has_voted && currentVote.status===2" class="btn-primary" @click="doVote" :disabled="!selectedOptions.size||voting">
            {{ voting ? '投票中...' : '提交投票' }}
          </button>
          <button v-if="currentVote.has_voted" class="btn-done" disabled>✓ 已投票</button>
          <button class="btn-cancel" @click="currentVote=null">关闭</button>
        </div>
      </div>
    </div>

    <div v-if="!list.length" class="empty">暂无投票活动</div>
    <div v-else class="list">
      <div v-for="item in list" :key="item.id" class="item" @click="openDetail(item)">
        <div class="item-hd">
          <span class="title">{{ item.title }}</span>
          <span class="tag" :style="{background:voteStatusColor(item.status),color:'#fff'}">
            {{ voteStatusLabel(item.status) }}
          </span>
        </div>
        <div class="item-body">
          <p>📋 {{ item.option_count }} 个选项 | 🗳 {{ item.total_votes }} 人参与</p>
          <p>🕐 {{ formatDate(item.start_time) }} ~ {{ formatDate(item.end_time) }}</p>
        </div>
        <div class="item-ft">
          <span class="vote-badge" v-if="item.has_voted" style="color:#10b981">✓ 已投票</span>
          <span class="vote-badge" v-else-if="item.status===2" style="color:#2563eb">→ 去投票</span>
          <span class="vote-badge" v-else style="color:#9ca3af">已结束</span>
        </div>
      </div>
    </div>
  </div>
</template>
<script setup>
import { ref, computed, onMounted } from 'vue'
import { createApi } from '@/shared/api.js'
import { showToast, formatDate } from '@/shared/utils.js'

const api = createApi('/api/api', 'owner_token')
const list = ref([])
const currentVote = ref(null)
const selectedOptions = ref(new Set())
const voting = ref(false)
const isMulti = computed(() => currentVote.value?.type === 2)

const statusLabels = { 1: '草稿', 2: '进行中', 3: '已结束' }
const statusColors = { 1: '#6b7280', 2: '#10b981', 3: '#ef4444' }
function voteStatusLabel(s) { return statusLabels[s] || '未知' }
function voteStatusColor(s) { return statusColors[s] || '#6b7280' }

onMounted(async () => {
  await loadList()
})

async function loadList() {
  const res = await api('/vote/list')
  if (res.code === 0) list.value = Array.isArray(res.data) ? res.data : (res.data?.list || [])
}

async function openDetail(item) {
  const res = await api('/vote/detail?id=' + item.id)
  if (res.code === 0) {
    currentVote.value = res.data
    selectedOptions.value = new Set()
  } else {
    showToast(res.msg || '获取详情失败')
  }
}

function selectOption(optId) {
  const set = selectedOptions.value
  if (set.has(optId)) {
    set.delete(optId)
  } else {
    if (!isMulti.value) {
      set.clear()
    }
    set.add(optId)
  }
  selectedOptions.value = new Set(set)
}

async function doVote() {
  if (!selectedOptions.value.size) return showToast('请至少选择一个选项')
  voting.value = true
  const body = { vote_id: currentVote.value.id, option_ids: [...selectedOptions.value] }
  const res = await api('/vote/vote', {
    method: 'POST',
    body: JSON.stringify(body)
  })
  voting.value = false
  showToast(res.code === 0 ? '投票成功' : (res.msg || '投票失败'))
  if (res.code === 0) {
    currentVote.value = null
    await loadList()
  }
}
</script>
<style scoped>
.page{padding:16px}
header{display:flex;align-items:center;gap:12px;margin-bottom:16px}
header h1{font-size:18px;flex:1}
.back{background:none;border:none;font-size:20px;cursor:pointer;padding:4px 8px}
.empty{text-align:center;padding:40px;color:#9ca3af}
.list{display:flex;flex-direction:column;gap:10px}
.item{background:#fff;border-radius:12px;padding:16px;box-shadow:0 1px 3px rgba(0,0,0,.06);cursor:pointer;transition:transform .15s}
.item:active{transform:scale(.98)}
.item-hd{display:flex;justify-content:space-between;margin-bottom:8px}
.title{font-size:15px;font-weight:600;flex:1;margin-right:8px}
.tag{font-size:11px;padding:2px 10px;border-radius:12px;white-space:nowrap}
.item-body p{margin:3px 0;font-size:13px;color:#6b7280}
.item-ft{margin-top:8px}
.vote-badge{font-size:12px;font-weight:500}
/* 弹窗 */
.overlay{position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,.45);z-index:200;display:flex;align-items:flex-end;justify-content:center}
.modal{background:#fff;width:100%;max-width:500px;border-radius:16px 16px 0 0;padding:24px 18px 20px;max-height:80vh;overflow-y:auto}
.modal h2{font-size:18px;margin-bottom:10px}
.vote-desc{color:#6b7280;font-size:14px;margin-bottom:12px;line-height:1.5}
.vote-meta{display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;font-size:12px;color:#9ca3af}
.vote-type-hint{font-size:12px;color:#2563eb;margin-bottom:16px;padding:6px 10px;background:#eff6ff;border-radius:8px}
.options{display:flex;flex-direction:column;gap:12px;margin-bottom:20px}
.opt-bar{padding:12px 14px;border-radius:10px;background:#f9fafb;border:2px solid #f3f4f6;cursor:pointer;transition:all .2s}
.opt-bar.selected{border-color:#2563eb;background:#eff6ff}
.opt-row{display:flex;justify-content:space-between;align-items:center;margin-bottom:6px}
.opt-check{width:24px;flex-shrink:0}
.checkbox-icon,.radio-icon{font-size:18px;line-height:1}
.checkbox-icon.checked,.radio-icon.checked{color:#2563eb}
.radio-icon{color:#d1d5db}
.checkbox-icon{color:#d1d5db}
.opt-name{font-size:14px;font-weight:500;flex:1}
.opt-count{font-size:12px;color:#6b7280}
.bar-bg{height:6px;background:#e5e7eb;border-radius:3px;overflow:hidden;margin-bottom:4px}
.bar-fill{height:100%;background:#2563eb;border-radius:3px;transition:width .3s}
.opt-pct{font-size:11px;color:#2563eb;font-weight:500}
.modal-actions{display:flex;flex-direction:column;gap:10px}
.btn-primary{width:100%;height:44px;background:#2563eb;color:#fff;border:none;border-radius:10px;font-size:15px;cursor:pointer}
.btn-primary:disabled{opacity:.6}
.btn-done{width:100%;height:44px;background:#ecfdf5;color:#10b981;border:none;border-radius:10px;font-size:15px;cursor:default}
.btn-cancel{width:100%;height:44px;background:#f3f4f6;color:#6b7280;border:none;border-radius:10px;font-size:15px;cursor:pointer}
</style>
