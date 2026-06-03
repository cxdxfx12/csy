<template>
  <div id="manager-app">
    <router-view />
    <GlobalToast />
  </div>
</template>
<script setup>
import { onBeforeMount } from 'vue'
import { useRoute, useRouter } from 'vue-router'
const route = useRoute()
const router = useRouter()
onBeforeMount(() => {
  // 处理 OAuth 回调带回的 wechat_token（App.vue 层面兜底）
  const q = route.query
  if (q.wechat_token) {
    localStorage.setItem('manager_token', q.wechat_token)
    router.replace('/dashboard')
    return
  }
  if (localStorage.getItem('manager_token') && route.path === '/login') router.replace('/dashboard')
})
</script>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:#0f172a;color:#e2e8f0}
#manager-app{min-height:100vh}
</style>
