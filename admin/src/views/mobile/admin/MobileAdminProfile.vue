<template>
  <div class="profile-page">
    <!-- 用户头部卡片 -->
    <div class="profile-hero">
      <div class="ph-bg"></div>
      <div class="ph-content">
        <div class="ph-avatar">
          <img :src="monkeyLogo" class="pha-img" />
        </div>
        <div class="ph-name">{{ userStore.userInfo?.nickname || userStore.userInfo?.username || '管理员' }}</div>
        <div class="ph-badge">
          <Icon icon="ph:shield-check-fill" class="phb-icon" />
          {{ userStore.userInfo?.role || '系统管理员' }}
        </div>
      </div>
    </div>

    <!-- 信息面板 -->
    <div class="panel">
      <div class="panel-title">账号信息</div>
      <div class="pa-row" v-for="item in infoList" :key="item.label">
        <Icon :icon="item.icon" class="par-icon" />
        <span class="par-label">{{ item.label }}</span>
        <span class="par-value">{{ item.value || '-' }}</span>
      </div>
    </div>

    <!-- 功能入口 -->
    <div class="panel">
      <div class="panel-title">常用功能</div>
      <div class="pa-row clickable" @click="router.push('/bigscreen')">
        <Icon icon="ph:presentation-chart-duotone" class="par-icon c-blue" />
        <span class="par-label">数据大屏</span>
        <Icon icon="ph:caret-right" class="par-arrow" />
      </div>
      <div class="pa-row clickable" @click="router.push('/profile')">
        <Icon icon="ph:gear-duotone" class="par-icon c-slate" />
        <span class="par-label">PC版个人中心</span>
        <Icon icon="ph:caret-right" class="par-arrow" />
      </div>
      <div class="pa-row clickable" @click="router.push('/dashboard')">
        <Icon icon="ph:desktop-duotone" class="par-icon c-indigo" />
        <span class="par-label">切换到PC版</span>
        <Icon icon="ph:caret-right" class="par-arrow" />
      </div>
      <div class="pa-row clickable" @click="about">
        <Icon icon="ph:info-duotone" class="par-icon c-cyan" />
        <span class="par-label">关于我们</span>
        <Icon icon="ph:caret-right" class="par-arrow" />
      </div>
    </div>

    <!-- 退出按钮 -->
    <button class="logout-btn" @click="handleLogout">
      <Icon icon="ph:sign-out-duotone" class="lb-icon" />
      退出登录
    </button>

    <div class="version-info">v2.0 · 大圣智慧物业</div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Icon } from '@iconify/vue'
import { useUserStore } from '@/stores/user'
import monkeyLogo from '@/assets/images/monkey-ico.png'

const router = useRouter()
const userStore = useUserStore()

const infoList = computed(() => [
  { icon: 'ph:user-duotone', label: '用户名', value: userStore.userInfo?.username },
  { icon: 'ph:identification-badge-duotone', label: '昵称', value: userStore.userInfo?.nickname },
  { icon: 'ph:shield-check-duotone', label: '角色', value: userStore.userInfo?.role },
  { icon: 'ph:buildings-duotone', label: '管辖小区', value: userStore.userInfo?.community_name || '全部' },
])

async function handleLogout() {
  try {
    await ElMessageBox.confirm('确定要退出登录吗？', '提示', {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'warning',
    })
  } catch { return }
  userStore.logout()
  ElMessage.success('已退出')
  router.replace('/mobile/admin/login')
}

function about() {
  ElMessage.info('大圣智慧物业 · 管理后台 v2.0')
}
</script>

<style scoped>
.profile-page { padding-bottom: 20px; }

/* 头部卡片 */
.profile-hero {
  margin: 14px;
  border-radius: 20px;
  overflow: hidden;
  position: relative;
}
.ph-bg {
  position: absolute;
  inset: 0;
  background: linear-gradient(160deg, #1e293b, #334155);
}
.ph-bg::after {
  content: '';
  position: absolute;
  right: -20px; bottom: -20px;
  width: 100px; height: 100px;
  border-radius: 50%;
  background: rgba(99,102,241,.08);
}
.ph-content {
  position: relative;
  text-align: center;
  padding: 30px 20px 24px;
}
.ph-avatar { margin-bottom: 12px; }
.pha-img {
  width: 72px; height: 72px;
  border-radius: 18px;
  object-fit: contain;
  background: rgba(255,255,255,.12);
  padding: 10px;
  border: 2px solid rgba(255,255,255,.15);
}
.ph-name { font-size: 20px; font-weight: 800; color: #f1f5f9; }
.ph-badge {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  margin-top: 8px;
  font-size: 12px;
  color: rgba(255,255,255,.6);
  background: rgba(255,255,255,.08);
  padding: 4px 12px;
  border-radius: 20px;
}
.phb-icon { font-size: 14px; }

/* 面板 */
.panel {
  margin: 0 14px 14px;
  background: #fff;
  border-radius: 18px;
  overflow: hidden;
  border: 1px solid rgba(0,0,0,.03);
}
.panel-title {
  font-size: 13px; font-weight: 700; color: #64748b;
  padding: 14px 16px 8px;
  text-transform: uppercase;
  letter-spacing: 1px;
}

.pa-row {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 14px 16px;
  border-bottom: 1px solid #f1f5f9;
}
.pa-row:last-child { border-bottom: none; }
.pa-row.clickable { cursor: pointer; }
.pa-row.clickable:active { background: #f8fafc; }
.par-icon { font-size: 20px; color: #94a3b8; flex-shrink: 0; }
.par-icon.c-blue { color: #3b82f6; }
.par-icon.c-slate { color: #64748b; }
.par-icon.c-indigo { color: #6366f1; }
.par-icon.c-cyan { color: #06b6d4; }
.par-label { flex: 1; font-size: 14px; color: #334155; }
.par-value { font-size: 14px; color: #0f172a; font-weight: 500; }
.par-arrow { font-size: 14px; color: #cbd5e1; flex-shrink: 0; }

/* 退出按钮 */
.logout-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  width: calc(100% - 28px);
  margin: 0 14px;
  height: 50px;
  border: 1px solid #fee2e2;
  border-radius: 16px;
  background: #fff;
  color: #ef4444;
  font-size: 15px;
  font-weight: 600;
  cursor: pointer;
  transition: all .2s;
}
.logout-btn:active { background: #fef2f2; transform: scale(.98); }
.lb-icon { font-size: 20px; }

.version-info {
  text-align: center;
  color: #cbd5e1;
  font-size: 11px;
  padding: 20px;
}
</style>
