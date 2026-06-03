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
.tabs-bar { margin-top: 60px; background: #fff; border-bottom: 1px solid #e2e8f0; padding: 0 8px; }
.tabs-bar :deep(.el-tabs__header) { margin: 0; border: none; }
.tabs-bar :deep(.el-tabs__nav) { border: none; }
.tabs-bar :deep(.el-tabs__item) { height: 40px; line-height: 40px; border: none !important; font-size: 13px; }
.tabs-bar :deep(.el-tabs__item.is-active) { color: #2b6cb0; border-bottom: 2px solid #2b6cb0 !important; font-weight: 600; }
</style>
