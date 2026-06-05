<template>
  <div class="mobile-wrap">
    <div class="mobile-header" v-if="showHeader">
      <div class="mh-left" @click="goBack" v-if="showBack"><span style="font-size:20px;">‹</span></div>
      <div class="mh-title">{{ title }}</div>
      <div class="mh-right"></div>
    </div>
    <div class="mobile-body" :class="{ 'has-header': showHeader, 'has-tab': showTab }">
      <slot />
    </div>
    <div class="mobile-tab" v-if="showTab">
      <div v-for="t in tabs" :key="t.path" class="mt-item" :class="{ active: $route.path === t.path }" @click="$router.push(t.path)">
        <div class="mt-icon">{{ t.icon }}<span v-if="t.badge && t.badge > 0" class="mt-badge">{{ t.badge > 99 ? '99+' : t.badge }}</span></div>
        <div class="mt-label">{{ t.label }}</div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'

const route = useRoute()
const router = useRouter()

const props = defineProps<{ title?: string; showBack?: boolean; showTab?: boolean; tabs?: { path: string; icon: string; label: string; badge?: number }[] }>()

const showHeader = computed(() => route.meta.title || props.title)
const title = computed(() => (route.meta.title as string) || props.title || '')
function goBack() { router.back() }
</script>

<style scoped>
.mobile-wrap { max-width:480px; margin:0 auto; min-height:100vh; background:#f7f8fc; position:relative; }
.mobile-header { position:fixed; top:0; left:50%; transform:translateX(-50%); width:100%; max-width:480px; height:48px; background:#fff; border-bottom:1px solid #e2e8f0; display:flex; align-items:center; padding:0 12px; z-index:100; }
.mh-left { width:40px; }
.mh-title { flex:1; text-align:center; font-size:16px; font-weight:600; color:#1a202c; }
.mh-right { width:40px; }
.mobile-body { padding:12px; }
.mobile-body.has-header { padding-top:60px; }
.mobile-body.has-tab { padding-bottom:60px; }
.mobile-tab { position:fixed; bottom:0; left:50%; transform:translateX(-50%); width:100%; max-width:480px; height:56px; background:#fff; border-top:1px solid #e2e8f0; display:flex; z-index:100; }
.mt-item { flex:1; display:flex; flex-direction:column; align-items:center; justify-content:center; gap:2px; cursor:pointer; color:#a0aec0; }
.mt-item.active { color:#2b6cb0; }
.mt-icon { font-size:20px; position:relative; }
.mt-label { font-size:11px; }
.mt-badge { position:absolute; top:-8px; right:-12px; min-width:18px; height:18px; line-height:18px; text-align:center; font-size:11px; font-weight:700; color:#fff; background:#e53e3e; border-radius:9px; padding:0 5px; box-sizing:border-box; }
</style>
