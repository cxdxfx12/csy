<template>
  <transition name="toast">
    <div class="global-toast" v-if="toastShow">{{ toastMsg }}</div>
  </transition>
</template>
<script setup>
import { toastMsg, toastShow } from '@/shared/utils.js'
</script>
<style scoped>
.global-toast {
  position: fixed; top: 50%; left: 50%; transform: translate(-50%,-50%);
  background: rgba(0,0,0,.75); color: #fff; padding: 12px 28px;
  border-radius: 8px; font-size: 15px; z-index: 9999; white-space: nowrap;
}
.toast-enter-active, .toast-leave-active { transition: opacity .3s; }
.toast-enter-from, .toast-leave-to { opacity: 0; }
</style>
