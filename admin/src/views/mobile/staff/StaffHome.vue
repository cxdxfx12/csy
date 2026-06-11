<template>
  <MobileLayout title="物业工作台" :showTab="true" :tabs="tabsList">
    <div class="pd">
      <div class="sh-user">
        <span>👤 {{ user?.nickname || '员工' }}</span>
        <span class="sh-logout" @click="handleLogout">退出</span>
      </div>
      <div class="sh-stats">
        <div class="shs-item">
          <div class="shs-num">{{ stats.pending }}</div>
          <div>待接单</div>
        </div>
        <div class="shs-item">
          <div class="shs-num">{{ stats.processing }}</div>
          <div>处理中</div>
        </div>
        <div class="shs-item">
          <div class="shs-num">{{ stats.today }}</div>
          <div>今日完成</div>
        </div>
      </div>
      <div class="sh-section-title">📋 待办事项</div>
      <div v-for="t in todos" class="sh-todo">
        <div class="sht-left">
          <div class="sht-title">{{ t.title }}</div>
          <div class="sht-desc">{{ t.desc }}</div>
        </div>
        <span class="sht-status">{{ t.status }}</span>
      </div>
      <div v-if="!todos.length" style="text-align:center;color:#a0aec0;padding:20px;">暂无待办</div>
    </div>
  </MobileLayout>
</template>
<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessageBox, ElMessage } from 'element-plus'
import MobileLayout from '../MobileLayout.vue'

const router = useRouter()

interface TabItem { path: string; icon: string; label: string }

const user = ref<any>({})
const stats = ref({ pending: 0, processing: 0, today: 0 })
const todos = ref<any[]>([])

const tabsList: TabItem[] = [
  { path: '/mobile/staff/home', icon: '🏠', label: '首页' },
  { path: '/mobile/staff/repair', icon: '🔧', label: '工单' },
  { path: '/mobile/staff/charge', icon: '💰', label: '收费' },
  { path: '/mobile/staff/patrol', icon: '🚔', label: '巡更' },
]

async function handleLogout() {
  try {
    await ElMessageBox.confirm('确定要退出登录吗？', '提示', {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'warning',
    })
  } catch { return }
  localStorage.removeItem('staff_token')
  localStorage.removeItem('staff_community_id')
  ElMessage.success('已退出')
  router.replace('/mobile/staff/login')
}

onMounted(async () => {
  try {
    const r = await fetch('/api/staff/index', {
      headers: { Authorization: 'Bearer ' + localStorage.getItem('staff_token') },
    })
    const d = await r.json()
    if (d.code === 0) {
      user.value = d.data.user
      stats.value = d.data.stats
      todos.value = d.data.todos || []
    }
  } catch (e) {}
})
</script>
<style scoped>
.pd { padding: 4px 0; }
.sh-user { font-size: 18px; font-weight: 700; color: #1a202c; margin-bottom: 16px; padding: 16px; background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; }
.sh-logout { font-size: 13px; font-weight: 500; color: #e53e3e; cursor: pointer; padding: 4px 12px; border: 1px solid #fed7d7; border-radius: 6px; background: #fff5f5; }
.sh-logout:active { background: #fed7d7; }
.sh-stats { display: flex; gap: 10px; margin-bottom: 16px; }
.shs-item { flex: 1; background: #fff; border-radius: 12px; padding: 16px; text-align: center; border: 1px solid #e2e8f0; font-size: 13px; color: #a0aec0; }
.shs-num { font-size: 28px; font-weight: 700; color: #2b6cb0; margin-bottom: 4px; }
.sh-section-title { font-size: 16px; font-weight: 600; color: #1a202c; margin-bottom: 12px; }
.sh-todo { display: flex; align-items: center; background: #fff; border-radius: 10px; padding: 14px 16px; margin-bottom: 10px; border: 1px solid #e2e8f0; }
.sht-left { flex: 1; }
.sht-title { font-weight: 600; color: #1a202c; font-size: 14px; }
.sht-desc { font-size: 12px; color: #a0aec0; margin-top: 4px; }
.sht-status { font-size: 12px; padding: 3px 10px; border-radius: 20px; background: #ebf8ff; color: #2b6cb0; }
</style>
