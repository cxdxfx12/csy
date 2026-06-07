<template>
  <svg :width="size" :height="size" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
    <!-- ===== 1. 超级管理员 - 皇冠 + 盾牌 ===== -->
    <template v-if="roleId === 1">
      <defs><linearGradient id="g1" x1="0" y1="0" x2="48" y2="48"><stop offset="0%" stop-color="#F59E0B"/><stop offset="100%" stop-color="#D97706"/></linearGradient></defs>
      <circle cx="24" cy="24" r="20" fill="url(#g1)" opacity="0.12"/>
      <circle cx="24" cy="24" r="16" fill="url(#g1)" opacity="0.08"/>
      <path d="M12 30V22a4 4 0 014-4h3l5-8 5 8h3a4 4 0 014 4v8a2 2 0 01-2 2H14a2 2 0 01-2-2z" fill="url(#g1)"/>
      <path d="M14 30h20" stroke="url(#g1)" stroke-width="2.5" stroke-linecap="round"/>
      <path d="M20 30v4a2 2 0 002 2h4a2 2 0 002-2v-4" stroke="url(#g1)" stroke-width="2" stroke-linecap="round"/>
      <circle cx="20" cy="22" r="1.5" fill="#FFFBEB"/>
      <circle cx="28" cy="22" r="1.5" fill="#FFFBEB"/>
      <path d="M24 8l-1.5 3h3L24 8z" fill="#FCD34D"/>
    </template>

    <!-- ===== 2. 系统管理员 - 齿轮 + 节点 ===== -->
    <template v-else-if="roleId === 2">
      <defs><linearGradient id="g2" x1="0" y1="0" x2="48" y2="48"><stop offset="0%" stop-color="#3B82F6"/><stop offset="100%" stop-color="#2563EB"/></linearGradient></defs>
      <circle cx="24" cy="24" r="20" fill="url(#g2)" opacity="0.12"/>
      <circle cx="24" cy="24" r="16" fill="url(#g2)" opacity="0.08"/>
      <path d="M24 8l2.5 5.2 5.8.8-4.2 4.1 1 5.9L24 21l-5.1 3 1-5.9-4.2-4.1 5.8-.8L24 8z" fill="url(#g2)" transform="scale(.5) translate(24,20)"/>
      <circle cx="24" cy="22" r="2.5" fill="url(#g2)"/>
      <path d="M24 6v2M24 40v2M8 24h2M38 24h2M13.5 13.5l1.4 1.4M33.1 33.1l1.4 1.4M13.5 34.5l1.4-1.4M33.1 14.9l1.4-1.4" stroke="url(#g2)" stroke-width="2" stroke-linecap="round"/>
      <circle cx="38" cy="12" r="4" fill="#DBEAFE" stroke="url(#g2)" stroke-width="1.5"/>
      <path d="M36.5 12l1 1 2-2.5" stroke="url(#g2)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
    </template>

    <!-- ===== 3. 小区物管经理 - 建筑 ===== -->
    <template v-else-if="roleId === 3">
      <defs><linearGradient id="g3" x1="0" y1="0" x2="48" y2="48"><stop offset="0%" stop-color="#10B981"/><stop offset="100%" stop-color="#059669"/></linearGradient></defs>
      <circle cx="24" cy="24" r="20" fill="url(#g3)" opacity="0.12"/>
      <circle cx="24" cy="24" r="16" fill="url(#g3)" opacity="0.08"/>
      <path d="M10 36V22l14-10 14 10v14a2 2 0 01-2 2H12a2 2 0 01-2-2z" fill="url(#g3)"/>
      <rect x="14" y="24" width="5" height="5" rx="1" fill="#D1FAE5"/>
      <rect x="21" y="24" width="6" height="5" rx="1" fill="#D1FAE5"/>
      <rect x="29" y="24" width="5" height="5" rx="1" fill="#D1FAE5"/>
      <rect x="21" y="18" width="6" height="6" rx="1" fill="#A7F3D0" stroke="url(#g3)" stroke-width=".5"/>
      <path d="M22 38h4v-6h-4z" fill="#A7F3D0"/>
    </template>

    <!-- ===== 4. 客服主管 - 耳机 + 聊天气泡 ===== -->
    <template v-else-if="roleId === 4">
      <defs><linearGradient id="g4" x1="0" y1="0" x2="48" y2="48"><stop offset="0%" stop-color="#8B5CF6"/><stop offset="100%" stop-color="#7C3AED"/></linearGradient></defs>
      <circle cx="24" cy="24" r="20" fill="url(#g4)" opacity="0.12"/>
      <circle cx="24" cy="24" r="16" fill="url(#g4)" opacity="0.08"/>
      <path d="M14 18a10 10 0 0120 0v4a3 3 0 01-6 0v-4a4 4 0 00-8 0v4a3 3 0 01-6 0z" fill="url(#g4)"/>
      <circle cx="14" cy="18" r="3" fill="#EDE9FE"/>
      <circle cx="34" cy="18" r="3" fill="#EDE9FE"/>
      <rect x="16" y="28" width="16" height="4.5" rx="2.25" fill="url(#g4)"/>
      <path d="M28 32.5l4 4h-1.6a1 1 0 01-.7-.3L28 34.5" fill="url(#g4)"/>
    </template>

    <!-- ===== 5. 财务专员 - 计算器 + 金币 ===== -->
    <template v-else-if="roleId === 5">
      <defs><linearGradient id="g5" x1="0" y1="0" x2="48" y2="48"><stop offset="0%" stop-color="#F59E0B"/><stop offset="100%" stop-color="#D97706"/></linearGradient></defs>
      <circle cx="24" cy="24" r="20" fill="url(#g5)" opacity="0.12"/>
      <circle cx="24" cy="24" r="16" fill="url(#g5)" opacity="0.08"/>
      <rect x="14" y="12" width="20" height="26" rx="3" fill="url(#g5)"/>
      <rect x="14" y="12" width="20" height="7" rx="3" fill="#FCD34D" opacity="0.6"/>
      <text x="24" y="17.5" text-anchor="middle" font-size="6" fill="#fff" font-weight="bold">$</text>
      <circle cx="17.5" cy="24" r="3" fill="#FEF3C7"/>
      <circle cx="24" cy="24" r="3" fill="#FEF3C7"/>
      <circle cx="30.5" cy="24" r="3" fill="#FEF3C7"/>
      <circle cx="17.5" cy="31" r="3" fill="#FEF3C7"/>
      <circle cx="24" cy="31" r="3" fill="#FEF3C7"/>
      <circle cx="30.5" cy="31" r="3" fill="#FEF3C7"/>
      <circle cx="34" cy="8" r="4" fill="#FCD34D"/>
      <text x="34" y="9.5" text-anchor="middle" font-size="5" fill="#92400E" font-weight="bold">¢</text>
    </template>

    <!-- ===== 6. 安保主管 - 盾牌 ===== -->
    <template v-else-if="roleId === 6">
      <defs><linearGradient id="g6" x1="0" y1="0" x2="48" y2="48"><stop offset="0%" stop-color="#EF4444"/><stop offset="100%" stop-color="#DC2626"/></linearGradient></defs>
      <circle cx="24" cy="24" r="20" fill="url(#g6)" opacity="0.12"/>
      <circle cx="24" cy="24" r="16" fill="url(#g6)" opacity="0.08"/>
      <path d="M24 6L10 12v10c0 8 5 13 14 17 9-4 14-9 14-17V12L24 6z" fill="url(#g6)"/>
      <circle cx="24" cy="24" r="10" fill="#FEE2E2" opacity="0.4"/>
      <path d="M20 24.5l3 3 6-6" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
      <path d="M24 12l-.5 1h1L24 12z" fill="#FCA5A5"/>
    </template>

    <!-- ===== 7. 工程主管 - 扳手 + 蓝图 ===== -->
    <template v-else-if="roleId === 7">
      <defs><linearGradient id="g7" x1="0" y1="0" x2="48" y2="48"><stop offset="0%" stop-color="#06B6D4"/><stop offset="100%" stop-color="#0891B2"/></linearGradient></defs>
      <circle cx="24" cy="24" r="20" fill="url(#g7)" opacity="0.12"/>
      <circle cx="24" cy="24" r="16" fill="url(#g7)" opacity="0.08"/>
      <rect x="10" y="8" width="18" height="20" rx="2" fill="url(#g7)"/>
      <rect x="12" y="10" width="14" height="16" rx="1" fill="#CFFAFE" opacity="0.5"/>
      <line x1="12" y1="15" x2="26" y2="15" stroke="url(#g7)" stroke-width="1"/>
      <line x1="12" y1="19" x2="26" y2="19" stroke="url(#g7)" stroke-width="1"/>
      <line x1="12" y1="23" x2="21" y2="23" stroke="url(#g7)" stroke-width="1"/>
      <path d="M30 18l12-12a3 3 0 014.242 4.242L40 16.5A5 5 0 0134 22l-8-4z" fill="url(#g7)" transform="translate(0,8) scale(.55) translate(-48,-20)"/>
      <circle cx="21" cy="38" r="4" fill="url(#g7)" transform="translate(12,-20) scale(.55)"/>
    </template>

    <!-- ===== 8. 小区管理员 - 社群/人物 ===== -->
    <template v-else-if="roleId === 8">
      <defs><linearGradient id="g8" x1="0" y1="0" x2="48" y2="48"><stop offset="0%" stop-color="#14B8A6"/><stop offset="100%" stop-color="#0D9488"/></linearGradient></defs>
      <circle cx="24" cy="24" r="20" fill="url(#g8)" opacity="0.12"/>
      <circle cx="24" cy="24" r="16" fill="url(#g8)" opacity="0.08"/>
      <rect x="10" y="22" width="28" height="18" rx="3" fill="url(#g8)"/>
      <rect x="14" y="24" width="8" height="7" rx="1.5" fill="#CCFBF1" opacity="0.6"/>
      <rect x="24" y="24" width="8" height="7" rx="1.5" fill="#CCFBF1" opacity="0.6"/>
      <path d="M15 38h6v-4a2 2 0 00-2-2h-2a2 2 0 00-2 2z" fill="#CCFBF1" opacity="0.5"/>
      <path d="M25 38h6v-4a2 2 0 00-2-2h-2a2 2 0 00-2 2z" fill="#CCFBF1" opacity="0.5"/>
      <circle cx="18" cy="17" r="5" fill="url(#g8)"/>
      <circle cx="30" cy="17" r="5" fill="url(#g8)"/>
      <circle cx="18" cy="15" r="2" fill="#CCFBF1"/>
      <circle cx="30" cy="15" r="2" fill="#CCFBF1"/>
      <path d="M15 20a2 2 0 012-2h2a2 2 0 012 2" fill="url(#g8)"/>
      <path d="M27 20a2 2 0 012-2h2a2 2 0 012 2" fill="url(#g8)"/>
    </template>

    <!-- ===== 默认 - 问号 ===== -->
    <template v-else>
      <circle cx="24" cy="24" r="20" fill="#E5E7EB"/>
      <path d="M24 14a6 6 0 00-2 11.6V30a2 2 0 004 0v-4.4A6 6 0 0024 14z" fill="#9CA3AF"/>
      <circle cx="24" cy="35" r="1.5" fill="#9CA3AF"/>
    </template>
  </svg>
</template>

<script setup lang="ts">
withDefaults(defineProps<{
  roleId: number
  size?: number | string
}>(), { size: 48 })
</script>
