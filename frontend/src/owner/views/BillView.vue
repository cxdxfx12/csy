<template>
  <div class="page">
    <header><button class="back" @click="$router.back()">←</button><h1>物业账单</h1></header>
    <div class="tabs">
      <button :class="{active:tab==='all'}" @click="tab='all';loadBills()">全部</button>
      <button :class="{active:tab==='unpaid'}" @click="tab='unpaid';loadUnpaid()">待缴费</button>
      <button :class="{active:tab==='paid'}" @click="tab='paid';loadBills()">已缴费</button>
    </div>
    <div v-if="!bills.length" class="empty">暂无账单</div>
    <div v-else class="list">
      <div v-for="item in bills" :key="item.id" class="item">
        <div class="item-hd">
          <span class="title">{{ item.charge_item_name || item.item || '物业费' }}</span>
          <span class="tag" :class="item.status==='paid'||item.is_paid||item.status===3?'paid':'unpaid'">
            {{ item.status==='paid'||item.is_paid||item.status===3?'已缴':'未缴' }}
          </span>
        </div>
        <div class="item-body">
          <p>📅 {{ item.bill_month || item.month || item.bill_period || item.create_time || '--' }}</p>
          <p>🏠 {{ item.room_no || '--' }}</p>
        </div>
        <div class="item-ft">
          <span class="amount">¥{{ item.amount || item.total_amount || 0 }}</span>
          <button v-if="!(item.status==='paid'||item.is_paid||item.status===3)" class="btn-pay" @click="openPay(item)">去缴费</button>
        </div>
      </div>
    </div>

    <!-- 支付方式选择弹窗 -->
    <div v-if="showPay" class="modal-mask" @click.self="showPay=false">
      <div class="modal-card">
        <div class="modal-header">
          <h3>选择支付方式</h3>
          <button class="modal-close" @click="showPay=false">✕</button>
        </div>
        <div class="modal-body">
          <p class="pay-amount">应付金额：<strong>¥{{ currentBill.amount || currentBill.total_amount || 0 }}</strong></p>
          <p class="pay-desc">{{ currentBill.charge_item_name || '物业费' }} · {{ currentBill.bill_period || currentBill.bill_month || '' }}</p>

          <div v-if="!payChannels.length" class="loading-channels">加载支付方式中...</div>

          <div v-else class="channel-list">
            <div
              v-for="ch in payChannels"
              :key="ch.id"
              class="channel-item"
              :class="{selected: selectedChannel === ch.id}"
              @click="selectedChannel = ch.id"
            >
              <span class="ch-icon">{{ ch.icon }}</span>
              <span class="ch-name">{{ ch.name }}</span>
              <span class="ch-check">{{ selectedChannel === ch.id ? '✅' : '○' }}</span>
            </div>
          </div>

          <div v-if="payError" class="pay-error">{{ payError }}</div>

          <button class="btn-confirm" :disabled="paying || !selectedChannel" @click="doPay">
            {{ paying ? '处理中...' : '💳 确认支付' }}
          </button>

          <!-- 微信支付提示 -->
          <div v-if="wechatParams" class="wechat-notice">
            <p>正在跳转微信支付...</p>
            <p class="small">如果未自动跳转，请确认微信已配置或选择其他支付方式</p>
          </div>
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
const tab = ref('all')
const bills = ref([])
const showPay = ref(false)
const currentBill = ref({})
const payChannels = ref([])
const selectedChannel = ref(0)
const paying = ref(false)
const payError = ref('')
const wechatParams = ref(null)

onMounted(loadBills)

async function loadBills() {
  const res = await api('/bill/list')
  if (res.code === 0) {
    const all = Array.isArray(res.data) ? res.data : (res.data?.list || [])
    if (tab.value === 'paid') bills.value = all.filter(b => b.status === 'paid' || b.is_paid || b.status === 3)
    else bills.value = all
  }
}
async function loadUnpaid() {
  const res = await api('/bill/unpaid')
  if (res.code === 0) bills.value = Array.isArray(res.data) ? res.data : (res.data?.list || [])
}

async function openPay(item) {
  currentBill.value = item
  payError.value = ''
  wechatParams.value = null
  selectedChannel.value = 0
  showPay.value = true

  // 加载支付方式
  if (!payChannels.value.length) {
    const res = await api('/bill/payConfig')
    if (res.code === 0 && res.data?.channels) {
      payChannels.value = res.data.channels
    } else {
      payChannels.value = [
        { id: 1, name: '现金缴费', icon: '💵' },
        { id: 2, name: '微信支付', icon: '💚' },
        { id: 4, name: '银行转账', icon: '🏦' },
        { id: 6, name: '其他方式', icon: '📋' },
      ]
    }
  }
  // 默认选中第一个
  if (payChannels.value.length && !selectedChannel.value) {
    selectedChannel.value = payChannels.value[0].id
  }
}

async function doPay() {
  if (!selectedChannel.value) {
    payError.value = '请选择支付方式'
    return
  }
  paying.value = true
  payError.value = ''
  wechatParams.value = null

  try {
    const res = await api('/bill/pay', {
      method: 'POST',
      body: JSON.stringify({
        bill_id: currentBill.value.id,
        pay_method: selectedChannel.value,
      }),
    })

    if (res.code === 0) {
      const data = res.data || {}

      // 微信支付：需要跳转或调用JSAPI
      if (data.need_wechat && data.wechat_params) {
        wechatParams.value = data.wechat_params
        // 调用微信JSAPI支付
        callWechatPay(data.wechat_params, data.payment_no)
        return
      }

      // 支付宝：跳转支付链接
      if (data.need_alipay && data.alipay_url) {
        showPay.value = false
        window.location.href = data.alipay_url
        return
      }

      // 线下支付/降级：直接完成
      showPay.value = false
      showToast(getPayMethodName(selectedChannel.value) + '缴费确认成功')
      tab.value = 'all'
      loadBills()
    } else {
      payError.value = res.msg || '支付失败，请重试'
    }
  } catch (e) {
    payError.value = '网络异常，请重试'
  } finally {
    paying.value = false
  }
}

function callWechatPay(params, paymentNo) {
  if (typeof WeixinJSBridge === 'undefined') {
    // 非微信环境，降级处理
    payError.value = '请在微信中打开进行支付，或选择其他支付方式'
    // 查询支付状态
    pollPayStatus(paymentNo)
    return
  }

  WeixinJSBridge.invoke('getBrandWCPayRequest', {
    'appId': params.appId,
    'timeStamp': params.timeStamp,
    'nonceStr': params.nonceStr,
    'package': params.package,
    'signType': params.signType || 'MD5',
    'paySign': params.paySign,
  }, function (res) {
    if (res.err_msg === 'get_brand_wcpay_request:ok') {
      showPay.value = false
      showToast('微信支付成功')
      tab.value = 'all'
      loadBills()
    } else if (res.err_msg === 'get_brand_wcpay_request:cancel') {
      payError.value = '已取消支付'
      pollPayStatus(paymentNo)
    } else {
      payError.value = '支付失败：' + (res.err_msg || '未知错误')
      pollPayStatus(paymentNo)
    }
  })
}

// 轮询支付状态（降级场景）
function pollPayStatus(paymentNo) {
  let count = 0
  const timer = setInterval(async () => {
    count++
    if (count > 30) { clearInterval(timer); return }
    const res = await api('/bill/payStatus?payment_no=' + paymentNo)
    if (res.code === 0 && res.data?.is_paid) {
      clearInterval(timer)
      showPay.value = false
      showToast('支付成功')
      tab.value = 'all'
      loadBills()
    }
  }, 2000)
}

function getPayMethodName(id) {
  const map = { 1: '现金', 2: '微信', 3: '支付宝', 4: '银行转账', 5: 'POS刷卡', 6: '其他' }
  return map[id] || ''
}
</script>

<style scoped>
.page{padding:16px;padding-bottom:40px}
header{display:flex;align-items:center;gap:12px;margin-bottom:16px}
header h1{font-size:18px}
.back{background:none;border:none;font-size:20px;cursor:pointer;padding:4px 8px}
.tabs{display:flex;gap:8px;margin-bottom:16px}
.tabs button{flex:1;padding:10px;border:1px solid #e5e7eb;border-radius:8px;background:#fff;font-size:13px;cursor:pointer}
.tabs button.active{background:#2563eb;color:#fff;border-color:#2563eb}
.empty{text-align:center;padding:40px;color:#9ca3af}
.list{display:flex;flex-direction:column;gap:12px}
.item{background:#fff;border-radius:12px;padding:16px;box-shadow:0 1px 3px rgba(0,0,0,.06)}
.item-hd{display:flex;justify-content:space-between;align-items:center;margin-bottom:8px}
.title{font-size:15px;font-weight:600}
.tag{padding:2px 10px;border-radius:12px;font-size:11px;font-weight:600}
.tag.unpaid{background:#fef2f2;color:#ef4444}
.tag.paid{background:#ecfdf5;color:#10b981}
.item-body p{margin:2px 0;font-size:12px;color:#6b7280}
.item-ft{display:flex;justify-content:space-between;align-items:center;margin-top:10px}
.amount{font-size:20px;font-weight:700;color:#ef4444}
.btn-pay{padding:8px 20px;background:#10b981;color:#fff;border:none;border-radius:8px;font-size:14px;cursor:pointer}

/* 弹窗 */
.modal-mask{position:fixed;inset:0;background:rgba(0,0,0,.5);display:flex;align-items:flex-end;justify-content:center;z-index:1000}
.modal-card{background:#fff;border-radius:16px 16px 0 0;width:100%;max-width:500px;max-height:80vh;overflow-y:auto;padding:0;animation:slideUp .3s}
@keyframes slideUp{from{transform:translateY(100%)}to{transform:translateY(0)}}
.modal-header{display:flex;justify-content:space-between;align-items:center;padding:20px 20px 0}
.modal-header h3{font-size:18px;margin:0}
.modal-close{background:none;border:none;font-size:22px;cursor:pointer;color:#999;padding:4px}
.modal-body{padding:20px}
.pay-amount{text-align:center;font-size:16px;margin-bottom:4px}
.pay-amount strong{font-size:28px;color:#ef4444}
.pay-desc{text-align:center;font-size:13px;color:#999;margin-bottom:20px}

.loading-channels{text-align:center;padding:20px;color:#999}

.channel-list{display:flex;flex-direction:column;gap:10px;margin-bottom:20px}
.channel-item{display:flex;align-items:center;padding:14px 16px;border:2px solid #e5e7eb;border-radius:12px;cursor:pointer;transition:all .2s}
.channel-item.selected{border-color:#2563eb;background:#eff6ff}
.channel-item:hover{border-color:#93c5fd}
.ch-icon{font-size:24px;margin-right:12px}
.ch-name{flex:1;font-size:15px;font-weight:500}
.ch-check{font-size:18px}

.pay-error{color:#e53e3e;font-size:13px;text-align:center;margin-bottom:12px}
.wechat-notice{text-align:center;margin-top:12px;padding:12px;background:#f0fdf4;border-radius:8px}
.wechat-notice p{margin:4px 0;font-size:13px;color:#065f46}
.wechat-notice .small{font-size:11px;color:#6b7280}

.btn-confirm{width:100%;height:48px;background:linear-gradient(135deg,#2563eb,#1d4ed8);color:#fff;border:none;border-radius:12px;font-size:16px;font-weight:600;cursor:pointer;margin-top:4px}
.btn-confirm:hover{box-shadow:0 4px 14px rgba(37,99,235,.3)}
.btn-confirm:disabled{opacity:.5;cursor:not-allowed}
</style>
