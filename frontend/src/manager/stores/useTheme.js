import { ref } from 'vue'

const STORAGE_KEY = 'manager_theme'

const themes = [
  { id: 'liquid-glass', name: '液态玻璃', icon: '💎', preview: ['#6366f1','#8b5cf6'] },
  { id: 'classic-light', name: '经典白', icon: '☀️', preview: ['#3b82f6','#2563eb'] },
  { id: 'dark-pro', name: '暗夜', icon: '🌙', preview: ['#6366f1','#4f46e5'] },
  { id: 'aurora', name: '极光', icon: '🌊', preview: ['#14b8a6','#0d9488'] },
  { id: 'sunset', name: '暮光', icon: '🌅', preview: ['#f59e0b','#d97706'] },
]

const current = ref(localStorage.getItem(STORAGE_KEY) || 'liquid-glass')

function applyTheme(id) {
  document.documentElement.setAttribute('data-theme', id)
  localStorage.setItem(STORAGE_KEY, id)
  current.value = id
}

if (typeof document !== 'undefined') {
  applyTheme(current.value)
}

export function useTheme() {
  return { themes, current, applyTheme }
}
