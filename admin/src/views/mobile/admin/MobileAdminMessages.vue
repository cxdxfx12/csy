<template>
  <div class="msg-page">
    <!-- 空状态 -->
    <div v-if="!messages.length" class="empty-state">
      <div class="es-visual">
        <div class="es-ring">
          <Icon icon="ph:bell-ringing-duotone" class="es-icon" />
        </div>
      </div>
      <span class="es-title">暂无消息通知</span>
      <span class="es-sub">当有新消息时，会在这里显示</span>
    </div>

    <!-- 列表 -->
    <div v-else class="msg-list">
      <div
        class="msg-item"
        :class="{ unread: !m.read }"
        v-for="(m, i) in messages"
        :key="i"
        @click="readMsg(m, i)"
      >
        <div class="mi-left">
          <div class="mi-avatar" :style="{ background: getColor(m.title) }">
            <Icon :icon="getMsgIcon(m.title)" />
          </div>
        </div>
        <div class="mi-main">
          <div class="mi-top">
            <span class="mi-title">{{ m.title }}</span>
            <span class="mi-time">{{ formatTime(m.time) }}</span>
          </div>
          <div class="mi-body">{{ m.content }}</div>
          <div class="mi-bottom">
            <span v-if="m.type" class="mi-tag">{{ m.type }}</span>
          </div>
        </div>
        <div class="mi-right">
          <div v-if="!m.read" class="mi-dot"></div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { Icon } from '@iconify/vue'
import { useUserStore } from '@/stores/user'

const userStore = useUserStore()
const messages = ref<any[]>([])

function getColor(title: string): string {
  const t = (title || '').toLowerCase()
  if (t.includes('通知') || t.includes('公告')) return 'rgba(99,102,241,.12)'
  if (t.includes('缴费') || t.includes('收费') || t.includes('账单')) return 'rgba(234,88,12,.12)'
  if (t.includes('工单') || t.includes('报修') || t.includes('维修')) return 'rgba(220,38,38,.12)'
  if (t.includes('投诉') || t.includes('建议')) return 'rgba(217,70,239,.12)'
  if (t.includes('系统') || t.includes('配置')) return 'rgba(100,116,139,.12)'
  return 'rgba(59,130,246,.12)'
}

function getMsgIcon(title: string): string {
  const t = (title || '').toLowerCase()
  if (t.includes('通知') || t.includes('公告')) return 'ph:megaphone-duotone'
  if (t.includes('缴费') || t.includes('收费') || t.includes('账单')) return 'ph:currency-circle-dollar-duotone'
  if (t.includes('工单') || t.includes('报修') || t.includes('维修')) return 'ph:clipboard-text-duotone'
  if (t.includes('投诉') || t.includes('建议')) return 'ph:chat-centered-text-duotone'
  if (t.includes('系统') || t.includes('配置')) return 'ph:gear-duotone'
  return 'ph:bell-ringing-duotone'
}

function formatTime(t: string): string {
  if (!t) return ''
  // 简单格式化: 2024-01-15 14:30 -> 01-15 14:30
  const parts = t.split(' ')
  if (parts.length === 2) {
    const date = parts[0].split('-')
    const time = parts[1].substring(0, 5)
    return `${date[1]}-${date[2]} ${time}`
  }
  return t.length > 12 ? t.substring(5, 12) + t.substring(11, 16) : t
}

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
        type: item.type || '',
        read: item.is_read === 1,
      }))
    }
  } catch { /* ignore */ }
})

function readMsg(m: any, idx: number) {
  messages.value[idx].read = true
  ElMessage.info(m.title)
}
</script>

<style scoped>
.msg-page { padding: 0; min-height: 100%; }

/* 空状态 */
.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 80px 20px;
}
.es-visual { margin-bottom: 16px; }
.es-ring {
  width: 80px; height: 80px;
  border-radius: 50%;
  background: rgba(59,130,246,.08);
  display: flex;
  align-items: center;
  justify-content: center;
}
.es-icon { font-size: 36px; color: #3b82f6; opacity: .6; }
.es-title { font-size: 16px; font-weight: 600; color: #64748b; }
.es-sub { font-size: 13px; color: #94a3b8; margin-top: 4px; }

/* 消息列表 */
.msg-list { padding-top: 2px; }
.msg-item {
  display: flex;
  gap: 12px;
  padding: 16px;
  background: #fff;
  cursor: pointer;
  border-bottom: 1px solid #f1f5f9;
  transition: background .15s;
}
.msg-item:active { background: #f8fafc; }
.msg-item.unread { background: #f8faff; border-left: 3px solid #3b82f6; }
.mi-left { flex-shrink: 0; }
.mi-avatar {
  width: 44px; height: 44px;
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 22px;
  color: #64748b;
}
.mi-main { flex: 1; min-width: 0; }
.mi-top { display: flex; justify-content: space-between; align-items: flex-start; gap: 8px; }
.mi-title { font-size: 14px; font-weight: 600; color: #0f172a; }
.mi-time { font-size: 11px; color: #94a3b8; flex-shrink: 0; white-space: nowrap; }
.mi-body {
  font-size: 13px; color: #64748b; margin-top: 6px;
  line-height: 1.5;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
.mi-bottom { margin-top: 8px; }
.mi-tag {
  font-size: 10px; color: #3b82f6;
  background: rgba(59,130,246,.08);
  padding: 2px 8px; border-radius: 6px;
  font-weight: 500;
}
.mi-right { flex-shrink: 0; display: flex; align-items: center; }
.mi-dot { width: 8px; height: 8px; border-radius: 50%; background: #3b82f6; }
</style>
