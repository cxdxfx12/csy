export function formatTime(t?: string): string {
  return t ? t.substring(0, 19) : '-'
}
export function formatDate(t?: string): string {
  return t ? t.substring(0, 10) : '-'
}
export function formatMoney(v?: number): string {
  return (v || 0).toFixed(2)
}
