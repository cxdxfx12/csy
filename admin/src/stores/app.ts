import { defineStore } from 'pinia'
import { ref } from 'vue'

export interface TabItem {
  title: string
  path: string
  name: string
  closable: boolean
}

export const useAppStore = defineStore('app', () => {
  const sidebarCollapsed = ref(false)
  const tabs = ref<TabItem[]>([{ title: '📊 控制台', path: '/dashboard', name: 'Dashboard', closable: false }])
  const activeTab = ref('/dashboard')

  function toggleSidebar() { sidebarCollapsed.value = !sidebarCollapsed.value }

  function addTab(tab: TabItem) {
    // 只保留控制台 + 当前页，之前打开的自动关闭
    const dashboardTab = tabs.value[0] // 控制台（不可关闭）
    tabs.value = [dashboardTab]
    if (tab.path !== dashboardTab.path) {
      tabs.value.push(tab)
    }
    activeTab.value = tab.path
  }

  function removeTab(path: string) {
    const idx = tabs.value.findIndex((t) => t.path === path)
    if (idx === -1 || !tabs.value[idx].closable) return
    tabs.value.splice(idx, 1)
    if (activeTab.value === path) activeTab.value = tabs.value[Math.min(idx, tabs.value.length - 1)].path
  }

  return { sidebarCollapsed, tabs, activeTab, toggleSidebar, addTab, removeTab }
})
