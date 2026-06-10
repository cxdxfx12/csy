<template>
  <div class="mam-page">
    <div class="mam-empty" v-if="!messages.length">
      <div style="font-size:48px;margin-bottom:12px;">📭</div>
      <div style="color:#a0aec0;">暂无消息通知</div>
    </div>
    <div class="mam-list" v-else>
      <div class="maml-item" v-for="(m, i) in messages" :key="i" :class="{ unread: !m.read }" @click="readMsg(m, i)">
        <div class="mamli-dot" v-if="!m.read"></div>
        <div class="mamli-content">
          <div class="mamli-title">{{ m.title }}</div>
          <div class="mamli-body">{{ m.content }}</div>
          <div class="mamli-time">{{ m.time }}</div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { useUserStore } from '@/stores/user'

const userStore = useUserStore()
const messages = ref<any[]>([])

onMounted(async () => {
  try {
    const r = await fetch('/api/admin/notification?pageSize=20', {
      headers: { Authorization: `Bearer ${userStore.token}` },
    })
    const d = await r.json()
    if (d.code === 0 && d.data?.list) {
      messages.value = d.data.list.map((item: any) => ({
        id: item.id,
        title: item.title || '系统通知',
        content: item.content || '',
        time: item.create_time || '',
        read: item.is_read === 1,
      }))
    }
  } catch {}
})

function readMsg(m: any, idx: number) {
  messages.value[idx].read = true
  ElMessage.info(m.title)
}
</script>

<style scoped>
.mam-page { padding: 12px 0; }
.mam-empty { padding: 80px 20px; text-align: center; }
.mam-list { background: #fff; }
.maml-item { display: flex; align-items: flex-start; gap: 10px; padding: 14px 16px; border-bottom: 1px solid #f7f8fc; cursor: pointer; position: relative; }
.maml-item:active { background: #f7f8fc; }
.maml-item.unread { background: #ebf8ff; }
.mamli-dot { width: 8px; height: 8px; border-radius: 50%; background: #3182ce; margin-top: 6px; flex-shrink: 0; }
.mamli-content { flex: 1; }
.mamli-title { font-size: 14px; font-weight: 600; color: #1a202c; }
.mamli-body { font-size: 12px; color: #718096; margin-top: 4px; line-height: 1.5; }
.mamli-time { font-size: 11px; color: #a0aec0; margin-top: 6px; }
</style>
