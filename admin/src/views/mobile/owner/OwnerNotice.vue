<template>
  <MobileLayout title="公告通知" showBack>
    <div class="pd">
      <div v-for="n in notices" class="so-card" @click="showDetail(n)">
        <div class="so-header">
          <span>{{ n.title }}</span>
          <span style="font-size:12px;color:#a0aec0;">{{ formatDate(n.create_time) }}</span>
        </div>
        <div class="so-body">{{ truncate(n.content, 100) }}{{ n.content?.length > 100 ? '...' : '' }}</div>
        <div class="so-footer">
          <span>{{ n.type_name || '通知' }}</span>
          <span style="color:#2b6cb0;">查看详情 →</span>
        </div>
      </div>
      <div v-if="!notices.length" style="text-align:center;color:#a0aec0;padding:40px;">暂无公告</div>
    </div>
  </MobileLayout>
</template>
<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import MobileLayout from '../MobileLayout.vue'

const notices = ref<any[]>([])

function formatDate(dateStr: string) {
  return dateStr?.substring(0, 10)
}

function truncate(str: string, len: number) {
  return str?.substring(0, len)
}

function showDetail(n: any) {
  ElMessage.info(n.title + '\n' + n.content)
}

onMounted(async () => {
  try {
    const r = await fetch('/api/notice/list', {
      headers: { Authorization: 'Bearer ' + localStorage.getItem('owner_token') },
    })
    const d = await r.json()
    notices.value = d.data?.list || d.data || []
  } catch (e) {}

  // 进入公告页即标记已读：获取最新公告总数并存储
  try {
    const badgeRes = await fetch('/api/badge/counts', {
      headers: { Authorization: 'Bearer ' + localStorage.getItem('owner_token') },
    })
    const badgeData = await badgeRes.json()
    if (badgeData.code === 0) {
      localStorage.setItem('owner_notice_last_read', String(badgeData.data.notice || 0))
    }
  } catch (e) {}
})
</script>
<style scoped>
.pd { padding: 4px 0; }
.so-card { background: #fff; border-radius: 12px; padding: 14px 16px; margin-bottom: 10px; border: 1px solid #e2e8f0; cursor: pointer; }
.so-header { display: flex; justify-content: space-between; margin-bottom: 6px; }
.so-header span:first-child { font-weight: 600; color: #1a202c; font-size: 14px; }
.so-body { font-size: 13px; color: #4a5568; margin-bottom: 8px; line-height: 1.5; }
.so-footer { display: flex; justify-content: space-between; font-size: 12px; color: #a0aec0; }
</style>
