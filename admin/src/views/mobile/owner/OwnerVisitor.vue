<template>
  <MobileLayout title="访客邀请" showBack>
    <div class="pd">
      <button class="or-add" @click="showForm = true">➕ 邀请访客</button>
      <div v-if="showForm" class="or-form">
        <input v-model="form.name" placeholder="访客姓名" class="or-input" />
        <input v-model="form.phone" placeholder="手机号" class="or-input" />
        <input v-model="form.plate" placeholder="车牌号(选填)" class="or-input" />
        <input v-model="form.date" type="date" class="or-input" />
        <button class="or-btn" style="background:#3182ce;" @click="submitVisitor">生成邀请码</button>
      </div>
      <div class="sh-section-title" style="margin-top:16px;">📋 访客记录</div>
      <div v-for="v in visitors" class="so-card">
        <div class="so-header">
          <span>{{ v.visitor_name }}</span>
          <span class="so-status">{{ v.status_name || visitorStatusLabel(v.status) }}</span>
        </div>
        <div class="so-body">手机: {{ v.visitor_phone }} | 来访: {{ v.visit_time }}</div>
      </div>
      <div v-if="!visitors.length" style="text-align:center;color:#a0aec0;padding:40px;">暂无访客记录</div>
    </div>
  </MobileLayout>
</template>
<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import MobileLayout from '../MobileLayout.vue'

const visitors = ref<any[]>([])
const showForm = ref(false)
const form = ref({ name: '', phone: '', plate: '', date: '' })

function visitorStatusLabel(status: number) {
  if (status === 2) return '已入场'
  if (status === 3) return '已离场'
  return '待来访'
}

onMounted(async () => {
  try {
    const r = await fetch('/api/visitor/list', {
      headers: { Authorization: 'Bearer ' + localStorage.getItem('owner_token') },
    })
    const d = await r.json()
    visitors.value = d.data?.list || []
  } catch (e) {}
})

async function submitVisitor() {
  if (!form.value.name || !form.value.phone) {
    ElMessage.warning('请填写完整')
    return
  }
  try {
    const r = await fetch('/api/visitor/add', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Authorization: 'Bearer ' + localStorage.getItem('owner_token'),
      },
      body: JSON.stringify({
        visitor_name: form.value.name,
        visitor_phone: form.value.phone,
        plate_number: form.value.plate,
        visit_time: form.value.date,
      }),
    })
    const d = await r.json()
    if (d.code === 0) {
      ElMessage.success('邀请成功，验证码: ' + d.data.code)
      showForm.value = false
      location.reload()
    } else {
      ElMessage.error(d.msg)
    }
  } catch (e) {
    ElMessage.error('提交失败')
  }
}
</script>
