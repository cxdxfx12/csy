<template>
  <MobileLayout title="投诉建议" showBack>
    <div class="pd">
      <button class="or-add" @click="showForm = true">➕ 提交反馈</button>
      <div v-if="showForm" class="or-form">
        <select v-model="form.type" class="or-input">
          <option value="1">投诉</option>
          <option value="2">建议</option>
          <option value="4">咨询</option>
        </select>
        <textarea v-model="form.content" placeholder="描述您的问题或建议" class="or-textarea"></textarea>
        <button class="or-btn" style="background:#3182ce;" @click="submitComplaint">提交</button>
      </div>
      <div class="sh-section-title" style="margin-top:16px;">📋 我的反馈</div>
      <div v-for="c in complaints" class="so-card">
        <div class="so-header">
          <span>{{ c.type_name || '投诉' }}</span>
          <span class="so-status">{{ c.status_name || complaintStatusLabel(c.status) }}</span>
        </div>
        <div class="so-body">{{ c.content }}</div>
        <div class="so-footer">
          <span>{{ c.create_time }}</span>
          <span v-if="c.reply" style="color:#38a169;">回复: {{ c.reply }}</span>
        </div>
      </div>
      <div v-if="!complaints.length" style="text-align:center;color:#a0aec0;padding:40px;">暂无反馈</div>
    </div>
  </MobileLayout>
</template>
<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import MobileLayout from '../MobileLayout.vue'

const complaints = ref<any[]>([])
const showForm = ref(false)
const form = ref({ type: '1', content: '' })

function complaintStatusLabel(status: number) {
  if (status === 3) return '已处理'
  return '处理中'
}

onMounted(async () => {
  try {
    const r = await fetch('/api/complaint/list', {
      headers: { Authorization: 'Bearer ' + localStorage.getItem('owner_token') },
    })
    const d = await r.json()
    complaints.value = d.data || []
  } catch (e) {}
})

async function submitComplaint() {
  if (!form.value.content) {
    ElMessage.warning('请输入内容')
    return
  }
  try {
    const r = await fetch('/api/complaint/add', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Authorization: 'Bearer ' + localStorage.getItem('owner_token'),
      },
      body: JSON.stringify(form.value),
    })
    const d = await r.json()
    if (d.code === 0) {
      ElMessage.success('提交成功')
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
<style scoped>
.pd { padding: 4px 0; }
.or-add { width: 100%; height: 44px; background: linear-gradient(135deg, #3182ce, #2b6cb0); color: #fff; border: none; border-radius: 10px; font-size: 15px; cursor: pointer; margin-bottom: 12px; }
.or-form { background: #fff; border-radius: 12px; padding: 16px; margin-bottom: 16px; border: 1px solid #e2e8f0; }
.or-input, .or-textarea { width: 100%; border: 1px solid #e2e8f0; border-radius: 8px; padding: 0 12px; margin-bottom: 10px; font-size: 14px; box-sizing: border-box; }
.or-input { height: 40px; }
.or-textarea { height: 100px; padding: 10px 12px; resize: none; }
.or-btn { width: 100%; height: 40px; color: #fff; border: none; border-radius: 8px; font-size: 14px; cursor: pointer; }
.sh-section-title { font-size: 16px; font-weight: 600; color: #1a202c; margin-bottom: 12px; }
.so-card { background: #fff; border-radius: 12px; padding: 14px 16px; margin-bottom: 10px; border: 1px solid #e2e8f0; }
.so-header { display: flex; justify-content: space-between; margin-bottom: 6px; }
.so-header span:first-child { font-weight: 600; color: #1a202c; font-size: 14px; }
.so-status { font-size: 12px; padding: 2px 10px; border-radius: 10px; background: #ebf8ff; color: #2b6cb0; }
.so-body { font-size: 13px; color: #4a5568; margin-bottom: 6px; }
.so-footer { display: flex; justify-content: space-between; font-size: 12px; color: #a0aec0; }
</style>
