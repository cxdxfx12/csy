<template>
  <div class="tabs-bar">
    <el-tabs v-model="appStore.activeTab" type="border-card" closable @tab-remove="handleTabRemove" @tab-click="handleTabClick">
      <el-tab-pane v-for="tab in appStore.tabs" :key="tab.path" :label="tab.title" :name="tab.path" :closable="tab.closable" />
    </el-tabs>
  </div>
</template>

<script setup lang="ts">
import { useRouter } from 'vue-router'
import { useAppStore } from '@/stores/app'

const router = useRouter()
const appStore = useAppStore()

function handleTabRemove(path: string) {
  appStore.removeTab(path)
  router.push(appStore.activeTab)
}

function handleTabClick(pane: { paneName: string | number }) {
  router.push(pane.paneName as string)
}
</script>

<style scoped>
.tabs-bar { margin-top: 60px; background: #fff; border-bottom: 1px solid #e8ecf1; padding: 0 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.03); }
.tabs-bar :deep(.el-tabs__header) { margin: 0; border: none; }
.tabs-bar :deep(.el-tabs__nav) { border: none; }
.tabs-bar :deep(.el-tabs__item) {
  height: 40px; line-height: 40px; border: none !important;
  font-size: 13px; border-radius: 8px 8px 0 0; margin-right: 2px;
  padding: 0 16px !important; transition: all 0.2s;
}
.tabs-bar :deep(.el-tabs__item:hover) { background: #f1f5f9; color: #2563eb; }
.tabs-bar :deep(.el-tabs__item.is-active) {
  color: #2563eb; font-weight: 700;
  background: linear-gradient(180deg, rgba(37,99,235,0.06) 0%, transparent 100%);
  border-bottom: 2px solid #2563eb !important;
}
</style>
