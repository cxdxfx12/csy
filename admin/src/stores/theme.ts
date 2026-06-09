import { ref } from 'vue'

const STORAGE_KEY = 'admin_theme'

export const themes = [
  { id: 'classic-blue', name: '经典蓝', icon: '🔷', preview: ['#2563eb', '#4f46e5'] },
  { id: 'liquid-glass', name: '液态玻璃', icon: '💎', preview: ['#6366f1', '#8b5cf6'] },
  { id: 'dark-pro', name: '暗夜', icon: '🌙', preview: ['#6366f1', '#4f46e5'] },
  { id: 'aurora', name: '极光', icon: '🌊', preview: ['#14b8a6', '#0d9488'] },
  { id: 'sunset', name: '暮光', icon: '🌅', preview: ['#f59e0b', '#d97706'] },
]

export const current = ref(localStorage.getItem(STORAGE_KEY) || 'classic-blue')

export function applyTheme(id: string) {
  document.documentElement.setAttribute('data-theme', id)
  localStorage.setItem(STORAGE_KEY, id)
  current.value = id
}

// 立即应用
if (typeof document !== 'undefined') {
  applyTheme(current.value)
}
