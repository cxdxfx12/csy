<template>
  <div id="staff-app">
    <router-view />
    <GlobalToast />
    <nav class="tab-bar" v-if="$route.path !== '/login'">
      <router-link to="/home" class="tab"><span>🏠</span><em>首页</em></router-link>
      <router-link to="/repair" class="tab"><span>🔧</span><em>报修</em></router-link>
      <router-link to="/meter" class="tab"><span>📊</span><em>抄表</em></router-link>
      <router-link to="/charge" class="tab"><span>💰</span><em>收费</em></router-link>
      <router-link to="/patrol" class="tab"><span>🛡️</span><em>巡更</em></router-link>
      <router-link to="/visitor" class="tab"><span>👤</span><em>访客</em></router-link>
      <router-link to="/order" class="tab"><span>📋</span><em>工单</em></router-link>
    </nav>
  </div>
</template>
<script setup>
import { onBeforeMount, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
const route = useRoute()
const router = useRouter()
onBeforeMount(() => {
  if (localStorage.getItem('staff_token') && route.path === '/login') router.replace('/home')
})
</script>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background:#f5f7fa; color:#333; -webkit-tap-highlight-color:transparent}
#staff-app{min-height:100vh;padding-bottom:68px}
.tab-bar{position:fixed;bottom:0;left:0;right:0;height:60px;background:#fff;display:flex;justify-content:space-around;align-items:center;border-top:1px solid #e5e7eb;z-index:100;padding-bottom:env(safe-area-inset-bottom)}
.tab{display:flex;flex-direction:column;align-items:center;text-decoration:none;color:#999;font-size:10px;min-width:48px}
.tab span{font-size:22px;line-height:1.3}
.tab em{font-style:normal;margin-top:1px}
.tab.router-link-active{color:#2563eb}
.tab.router-link-active span{transform:scale(1.1)}
</style>
