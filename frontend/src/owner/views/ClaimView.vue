<template>
  <div class="page">
    <header>
      <button class="btn-back" @click="$router.back()">← 返回</button>
      <h2>认领房产</h2>
    </header>

    <div class="info-card">
      <div class="info-icon">🏠</div>
      <h3>绑定您的房产</h3>
      <p>输入您在物业登记的<b>手机号</b>，系统将自动匹配您的房产信息并完成绑定。</p>
    </div>

    <div class="form-card">
      <label>登记手机号</label>
      <input
        v-model="phone"
        type="tel"
        placeholder="请输入物业登记的手机号"
        maxlength="11"
        @keyup.enter="doClaim"
      />
      <button class="btn-primary" @click="doClaim" :disabled="loading || !phone">
        {{ loading ? '认领中...' : '立即认领' }}
      </button>
    </div>

    <div class="tips">
      <p>📌 认领成功后，该手机号对应的房产、账单、报修等信息将自动关联到您的微信账号。</p>
      <p>💡 如手机号有变更，请联系物业管理处更新登记信息。</p>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { createApi } from '@/shared/api.js'
import { showToast } from '@/shared/utils.js'

const router = useRouter()
const api = createApi('/api/api', 'owner_token')
const phone = ref('')
const loading = ref(false)

async function doClaim() {
  const v = phone.value.trim()
  if (!v) return showToast('请输入手机号')
  if (!/^1[3-9]\d{9}$/.test(v)) return showToast('手机号格式不正确')

  loading.value = true
  try {
    const res = await api('/claimProperty', {
      method: 'POST',
      body: JSON.stringify({ phone: v })
    })
    if (res.code === 0) {
      showToast(res.msg || '认领成功')
      setTimeout(() => router.replace('/home'), 1200)
    } else {
      showToast(res.msg || '认领失败')
    }
  } catch (e) {
    showToast('网络请求失败')
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.page{padding:16px}
header{display:flex;align-items:center;gap:12px;margin-bottom:20px}
.btn-back{background:none;border:1px solid #e5e7eb;padding:6px 14px;border-radius:8px;font-size:14px;color:#6b7280;cursor:pointer}
header h2{font-size:18px;font-weight:600}
.info-card{background:linear-gradient(135deg,#f0f9ff,#e0f2fe);border-radius:12px;padding:24px 18px;text-align:center;margin-bottom:20px}
.info-icon{font-size:40px;margin-bottom:8px}
.info-card h3{font-size:16px;margin-bottom:6px;color:#1e3a5f}
.info-card p{font-size:13px;color:#64748b;line-height:1.6}
.info-card b{color:#2563eb}
.form-card{background:#fff;border-radius:12px;padding:20px;box-shadow:0 1px 3px rgba(0,0,0,.06)}
.form-card label{display:block;font-size:13px;font-weight:500;color:#374151;margin-bottom:8px}
input{width:100%;height:48px;border:1px solid #e5e7eb;border-radius:10px;padding:0 16px;font-size:16px;margin-bottom:16px;outline:none}
input:focus{border-color:#2563eb}
.btn-primary{width:100%;height:48px;background:#2563eb;color:#fff;border:none;border-radius:10px;font-size:16px;font-weight:600;cursor:pointer}
.btn-primary:disabled{opacity:.5}
.tips{margin-top:24px;font-size:12px;color:#9ca3af}
.tips p{margin-bottom:6px;line-height:1.5}
</style>
