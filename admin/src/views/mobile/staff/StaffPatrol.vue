<template>
  <MobileLayout title="巡更管理" showBack>
    <div class="pd">
      <div class="sh-section-title">🗺️ 巡更路线</div>
      <div v-for="r in routes" class="so-card">
        <div class="so-header">
          <span>{{ r.name }}</span>
          <span class="so-status">{{ r.points || 0 }}个点</span>
        </div>
        <div class="so-body">{{ r.description || '暂无描述' }}</div>
        <div class="so-footer">
          <span>频次: {{ r.frequency || '每日' }}</span>
          <button class="sp-btn" @click="startPatrol(r)">▶ 开始巡更</button>
        </div>
      </div>
      <div v-if="!routes.length" style="text-align:center;color:#a0aec0;padding:40px;">暂无巡更路线</div>
    </div>
  </MobileLayout>
</template>
<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import MobileLayout from '../MobileLayout.vue'

const routes = ref<any[]>([])

async function load() {
  try {
    const r = await fetch('/api/staff/patrol/routes', {
      headers: { Authorization: 'Bearer ' + localStorage.getItem('staff_token') },
    })
    const d = await r.json()
    routes.value = d.data?.list || d.data || []
  } catch (e) {}
}

function startPatrol(r: any) {
  ElMessage.info('开始巡更: ' + r.name)
}

onMounted(load)
</script>
<style scoped>
.pd { padding: 4px 0; }
.sp-btn { background: #2b6cb0; color: #fff; border: none; border-radius: 6px; padding: 4px 14px; font-size: 12px; cursor: pointer; }
.so-card { background: #fff; border-radius: 12px; padding: 14px 16px; margin-bottom: 10px; border: 1px solid #e2e8f0; }
.so-header { display: flex; justify-content: space-between; margin-bottom: 6px; }
.so-header span:first-child { font-weight: 600; color: #1a202c; }
.so-status { font-size: 12px; padding: 2px 10px; border-radius: 10px; background: #ebf8ff; color: #2b6cb0; }
.so-body { font-size: 13px; color: #4a5568; margin-bottom: 6px; }
.so-footer { display: flex; justify-content: space-between; align-items: center; font-size: 12px; color: #a0aec0; }
.sh-section-title { font-size: 16px; font-weight: 600; color: #1a202c; margin-bottom: 12px; }
</style>
