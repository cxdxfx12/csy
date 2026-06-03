export { }

declare module '@vue/runtime-core' {
  interface ComponentCustomProperties {
    $filters: { formatTime: (t?: string) => string; formatDate: (t?: string) => string; formatMoney: (v?: number) => string }
  }
}
