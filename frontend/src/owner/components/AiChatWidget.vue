<template>
  <div class="ai-chat-widget">
    <button class="ai-btn" :class="{ glow: isOpen }" @click="toggle" :title="isOpen ? '关闭助手' : 'AI智能报修助手'">
      🤖
      <span class="pulse-dot" v-if="!isOpen"></span>
    </button>
    <div class="ai-panel" :class="{ show: isOpen }">
      <div class="ai-header">
        <span>🤖 AI 智能报修助手</span>
        <span class="ai-close" @click="isOpen = false">✕</span>
      </div>
      <div class="ai-messages" ref="msgContainer">
        <div v-for="(m, i) in messages" :key="i" class="msg-bubble" :class="'msg-' + m.type">{{ m.text }}</div>
        <div v-if="typing" class="typing-indicator"><span></span><span></span><span></span></div>
      </div>
      <div class="quick-row" v-if="quickTypes.length && !pendingRepair">
        <span v-for="t in quickTypes" :key="t.type" class="quick-tag" @click="quickSend(t)">{{ t.icon }} {{ t.name }}</span>
      </div>
      <!-- 确认提交按钮 -->
      <div class="confirm-bar" v-if="pendingRepair">
        <button class="btn-confirm" @click="doConfirm" :disabled="loading">
          <span v-if="!loading">✅ 确认提交报修单</span>
          <span v-else>提交中...</span>
        </button>
        <button class="btn-cancel" @click="cancelConfirm" :disabled="loading">取消</button>
      </div>
      <div class="ai-input">
        <input v-model="inputText" placeholder="描述您遇到的问题..." @keydown.enter="send" />
        <button :disabled="!inputText.trim() || loading" @click="send">▶</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, nextTick, onMounted, watch } from 'vue'

const AI_BASE = '/api/ai/'

const isOpen = ref(false)
const inputText = ref('')
const loading = ref(false)
const typing = ref(false)
const messages = reactive([])
const quickTypes = reactive([])
const msgContainer = ref(null)

let pendingRepair = null
let localHistory = []

function toggle() {
  isOpen.value = !isOpen.value
  if (isOpen.value) {
    nextTick(() => scrollBottom())
  }
}

function scrollBottom() {
  nextTick(() => {
    if (msgContainer.value) msgContainer.value.scrollTop = msgContainer.value.scrollHeight
  })
}

function addMsg(type, text) {
  messages.push({ type, text })
  scrollBottom()
}

async function send() {
  const msg = inputText.value.trim()
  if (!msg || loading.value) return

  // 确认提交
  if (msg === '确认' && pendingRepair) {
    inputText.value = ''
    addMsg('user', '确认提交')
    loading.value = true
    typing.value = true
    await doSubmit()
    return
  }

  inputText.value = ''
  addMsg('user', msg)
  localHistory.push({ role: 'user', content: msg })
  loading.value = true
  typing.value = true
  await doChat(msg)
}

async function doConfirm() {
  if (!pendingRepair || loading.value) return
  inputText.value = ''
  addMsg('user', '确认提交')
  loading.value = true
  typing.value = true
  await doSubmit()
}

function cancelConfirm() {
  pendingRepair = null
  addMsg('ai', '已取消报修提交。您可以继续描述其他问题。')
}

async function doChat(msg) {
  try {
    const r = await fetch(AI_BASE + 'chat', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ message: msg, history: localHistory })
    })
    const d = await r.json()
    typing.value = false
    loading.value = false

    if (d.code === 0 && d.data) {
      addMsg('ai', d.data.reply)
      localHistory.push({ role: 'assistant', content: d.data.reply })
      if (d.data.action === 'confirm') {
        pendingRepair = {
          repairType: d.data.repairType,
          isUrgent: d.data.isUrgent,
          location: d.data.location,
          title: msg
        }
      }
    } else {
      addMsg('ai', '抱歉，系统暂时无法处理，请稍后重试。')
    }
  } catch {
    typing.value = false
    loading.value = false
    addMsg('ai', '网络异常，请检查连接后重试。')
  }
}

async function doSubmit() {
  if (!pendingRepair) {
    typing.value = false
    loading.value = false
    return
  }
  try {
    const r = await fetch(AI_BASE + 'submit', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        title: pendingRepair.title,
        content: 'AI智能报修：' + pendingRepair.title,
        repair_type: pendingRepair.repairType,
        is_urgent: pendingRepair.isUrgent,
        location: pendingRepair.location,
        name: '业主',
        phone: ''
      })
    })
    const d = await r.json()
    typing.value = false
    loading.value = false
    if (d.code === 0 && d.data) {
      addMsg('ai', d.data.reply)
      pendingRepair = null
    } else {
      addMsg('ai', '提交失败：' + (d.msg || '未知错误'))
    }
  } catch {
    typing.value = false
    loading.value = false
    addMsg('ai', '提交失败，请稍后重试。')
  }
}

async function loadQuickTypes() {
  try {
    const r = await fetch(AI_BASE + 'quickTypes')
    const d = await r.json()
    if (d.code === 0 && d.data) {
      quickTypes.splice(0, quickTypes.length, ...d.data)
    }
  } catch { /* silent */ }
}

function quickSend(t) {
  if (loading.value) return
  const example = t.examples?.split('、')[0] || t.name + '出现故障'
  inputText.value = example
  send()
}

onMounted(() => {
  loadQuickTypes()
  addMsg('ai', '您好！我是大圣智慧物业的AI报修助手 🤖\n请直接告诉我您遇到的问题，比如：\n• "厨房水龙头漏水"\n• "客厅空调不制冷"\n我会自动帮您生成报修单！')
})

watch(isOpen, v => {
  if (v) {
    nextTick(() => {
      const inp = document.querySelector('.ai-input input')
      if (inp) inp.focus()
    })
  }
})
</script>

<style scoped>
.ai-chat-widget {
  position: fixed;
  bottom: 80px; /* 在 tab bar 上方 */
  right: 16px;
  z-index: 500;
  font-family: -apple-system, BlinkMacSystemFont, 'PingFang SC', 'Microsoft YaHei', sans-serif;
}

.ai-btn {
  width: 50px;
  height: 50px;
  border-radius: 50%;
  background: linear-gradient(135deg, #667eea, #764ba2);
  border: none;
  cursor: pointer;
  box-shadow: 0 4px 15px rgba(102,126,234,0.45);
  font-size: 22px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.3s;
  position: relative;
}
.ai-btn.glow {
  box-shadow: 0 6px 24px rgba(102,126,234,0.7);
  transform: scale(1.05);
}
.ai-btn .pulse-dot {
  position: absolute;
  top: 4px;
  right: 4px;
  width: 10px;
  height: 10px;
  border-radius: 50%;
  background: #ef4444;
  animation: pd 1.5s infinite;
}
@keyframes pd {
  0%,100% { transform: scale(0.8); opacity: 0.8; }
  50% { transform: scale(1.4); opacity: 1; }
}

.ai-panel {
  position: absolute;
  bottom: 60px;
  right: 0;
  width: calc(100vw - 32px);
  max-width: 360px;
  height: 420px;
  background: rgba(22,24,30,0.97);
  border: 1px solid rgba(102,126,234,0.3);
  border-radius: 14px;
  display: none;
  flex-direction: column;
  box-shadow: 0 8px 40px rgba(0,0,0,0.55);
  overflow: hidden;
}
.ai-panel.show { display: flex; }

.ai-header {
  padding: 12px 14px;
  background: linear-gradient(135deg, #667eea, #764ba2);
  color: #fff;
  font-weight: 600;
  font-size: 14px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-shrink: 0;
}
.ai-close { cursor: pointer; font-size: 16px; opacity: 0.85; }

.ai-messages {
  flex: 1;
  overflow-y: auto;
  padding: 10px 12px;
  display: flex;
  flex-direction: column;
  gap: 6px;
}
.ai-messages::-webkit-scrollbar { width: 3px; }
.ai-messages::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.15); border-radius: 2px; }

.msg-bubble {
  max-width: 88%;
  padding: 8px 12px;
  border-radius: 12px;
  font-size: 13px;
  line-height: 1.5;
  white-space: pre-line;
  word-break: break-word;
  animation: mi 0.25s ease;
}
@keyframes mi { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: translateY(0); } }
.msg-ai {
  align-self: flex-start;
  background: rgba(102,126,234,0.18);
  color: #e0e0e0;
  border-bottom-left-radius: 3px;
}
.msg-user {
  align-self: flex-end;
  background: rgba(102,126,234,0.35);
  color: #fff;
  border-bottom-right-radius: 3px;
}

.quick-row {
  display: flex;
  flex-wrap: wrap;
  gap: 5px;
  padding: 6px 10px;
  flex-shrink: 0;
}
.quick-tag {
  padding: 5px 10px;
  border-radius: 12px;
  background: rgba(255,255,255,0.08);
  border: 1px solid rgba(255,255,255,0.1);
  color: #bbb;
  font-size: 11px;
  cursor: pointer;
  transition: all 0.2s;
}
.quick-tag:active {
  background: rgba(102,126,234,0.3);
  border-color: rgba(102,126,234,0.5);
  color: #fff;
}

.ai-input {
  padding: 10px;
  border-top: 1px solid rgba(255,255,255,0.06);
  display: flex;
  gap: 6px;
  flex-shrink: 0;
}
.ai-input input {
  flex: 1;
  background: rgba(255,255,255,0.06);
  border: 1px solid rgba(255,255,255,0.1);
  border-radius: 18px;
  padding: 8px 14px;
  color: #fff;
  font-size: 13px;
  outline: none;
}
.ai-input input:focus { border-color: rgba(102,126,234,0.5); }
.ai-input input::placeholder { color: #555; }

.ai-input button {
  width: 34px;
  height: 34px;
  border-radius: 50%;
  background: linear-gradient(135deg, #667eea, #764ba2);
  border: none;
  cursor: pointer;
  font-size: 14px;
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
}
.ai-input button:disabled { opacity: 0.45; }

/* 确认提交栏 */
.confirm-bar {
  display: flex;
  gap: 8px;
  padding: 8px 10px 4px;
  flex-shrink: 0;
}
.btn-confirm {
  flex: 1;
  padding: 9px 14px;
  border-radius: 18px;
  background: linear-gradient(135deg, #22c55e, #16a34a);
  border: none;
  color: #fff;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 4px;
  transition: all 0.2s;
}
.btn-confirm:active { transform: scale(0.97); }
.btn-confirm:disabled { opacity: 0.6; }
.btn-cancel {
  padding: 9px 16px;
  border-radius: 18px;
  background: rgba(255,255,255,0.08);
  border: 1px solid rgba(255,255,255,0.15);
  color: #aaa;
  font-size: 13px;
  cursor: pointer;
  transition: all 0.2s;
}
.btn-cancel:active { background: rgba(255,255,255,0.15); }
.btn-cancel:disabled { opacity: 0.5; }

.typing-indicator {
  align-self: flex-start;
  padding: 8px 12px;
  background: rgba(102,126,234,0.1);
  border-radius: 10px;
  display: flex;
  gap: 3px;
}
.typing-indicator span {
  width: 5px;
  height: 5px;
  border-radius: 50%;
  background: #667eea;
  animation: td 1.4s infinite;
}
.typing-indicator span:nth-child(2) { animation-delay: 0.2s; }
.typing-indicator span:nth-child(3) { animation-delay: 0.4s; }
@keyframes td {
  0%,60%,100% { opacity: 0.3; transform: translateY(0); }
  30% { opacity: 1; transform: translateY(-3px); }
}
</style>
