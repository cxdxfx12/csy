// 通用 Toast / Loading 工具
import { ref } from 'vue'

export const toastMsg = ref('')
export const toastShow = ref(false)
let toastTimer = null

export function showToast(msg, dur = 2000) {
  toastMsg.value = msg
  toastShow.value = true
  clearTimeout(toastTimer)
  toastTimer = setTimeout(() => { toastShow.value = false }, dur)
}

// 格式化日期
export function formatDate(str, fmt = 'YYYY-MM-DD') {
  if (!str) return ''
  const d = new Date(str)
  if (isNaN(d.getTime())) return str
  const y = d.getFullYear()
  const m = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  const h = String(d.getHours()).padStart(2, '0')
  const min = String(d.getMinutes()).padStart(2, '0')
  const s = String(d.getSeconds()).padStart(2, '0')
  return fmt
    .replace('YYYY', y).replace('MM', m).replace('DD', day)
    .replace('HH', h).replace('mm', min).replace('ss', s)
}

// 状态标签映射（兼容数字和字符串键）
const _statusMap = {
  1: '待处理', 2: '已派单', 3: '处理中',
  4: '已完成', 5: '已评价', 6: '已关闭',
  pending: '待处理', processing: '处理中', accepted: '已接单',
  finished: '已完成', cancelled: '已取消', closed: '已关闭',
  paid: '已缴费', unpaid: '未缴费', active: '正常'
}
const _colorMap = {
  1: '#f59e0b', 2: '#8b5cf6', 3: '#3b82f6',
  4: '#10b981', 5: '#10b981', 6: '#6b7280',
  pending: '#f59e0b', processing: '#3b82f6', accepted: '#8b5cf6',
  finished: '#10b981', cancelled: '#ef4444', closed: '#6b7280',
  paid: '#10b981', unpaid: '#ef4444', active: '#10b981'
}
export const statusLabels = _statusMap
export const statusColors = _colorMap
