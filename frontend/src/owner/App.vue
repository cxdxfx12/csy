<template>
  <div id="owner-app">
    <router-view />
    <GlobalToast />
    <nav class="tab-bar" v-if="$route.path !== '/login' && $route.path !== '/register'">
      <router-link to="/home" class="tab"><span>🏠</span><em>首页</em></router-link>
      <router-link to="/room" class="tab"><span>🏢</span><em>房产</em></router-link>
      <router-link to="/bill" class="tab"><span>💰</span><em>账单</em></router-link>
      <router-link to="/repair" class="tab"><span>🔧</span><em>报修</em></router-link>
      <router-link to="/notice" class="tab"><span>📢</span><em>公告</em></router-link>
      <router-link to="/visitor" class="tab"><span>👤</span><em>访客</em></router-link>
    </nav>
  </div>
</template>
<script setup>
import { onBeforeMount } from 'vue'
import { useRoute, useRouter } from 'vue-router'
const route = useRoute()
const router = useRouter()
onBeforeMount(() => {
  if (localStorage.getItem('owner_token') && (route.path === '/login' || route.path === '/register')) router.replace('/home')
  // 处理微信OAuth回调带回来的token
  const q = new URLSearchParams(window.location.search)
  const wcToken = q.get('wechat_token')
  if (wcToken) {
    localStorage.setItem('owner_token', wcToken)
    // 清理URL参数后跳转
    const cleanUrl = window.location.origin + window.location.pathname + window.location.hash
    window.history.replaceState({}, '', cleanUrl)
    router.replace('/home')
  }
})
</script>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:#f5f7fa;color:#333;-webkit-tap-highlight-color:transparent}
#owner-app{min-height:100vh;padding-bottom:68px}
.tab-bar{position:fixed;bottom:0;left:0;right:0;height:60px;background:#fff;display:flex;justify-content:space-around;align-items:center;border-top:1px solid #e5e7eb;z-index:100;padding-bottom:env(safe-area-inset-bottom)}
.tab{display:flex;flex-direction:column;align-items:center;text-decoration:none;color:#999;font-size:10px;min-width:48px}
.tab span{font-size:22px;line-height:1.3}
.tab em{font-style:normal;margin-top:1px}
.tab.router-link-active{color:#2563eb}
.tab.router-link-active span{transform:scale(1.1)}
</style>
