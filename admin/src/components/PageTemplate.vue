<template>
  <div class="page-wrapper">
    <!-- 高档渐变横幅 -->
    <div class="page-banner">
      <div class="banner-bg"></div>
      <div class="banner-content">
        <div class="banner-left">
          <h1 class="banner-title">{{ title }}</h1>
          <p class="banner-subtitle" v-if="subtitle">{{ subtitle }}</p>
        </div>
        <div class="banner-right" v-if="$slots['banner-actions']">
          <slot name="banner-actions" />
        </div>
      </div>
      <div class="banner-stats" v-if="$slots.stats">
        <slot name="stats" />
      </div>
    </div>

    <!-- 搜索区域 -->
    <div class="search-card" v-if="$slots.search">
      <div class="search-card-header">
        <el-icon><Search /></el-icon>
        <span>筛选条件</span>
      </div>
      <div class="search-card-body">
        <slot name="search" />
      </div>
    </div>

    <!-- 数据表格卡片 -->
    <el-card shadow="hover" class="table-card-premium" :body-style="{ padding: '0' }">
      <div class="table-toolbar" v-if="$slots.toolbar">
        <slot name="toolbar" />
      </div>
      <div class="table-body">
        <el-table
          :data="data"
          v-loading="loading"
          stripe
          @sort-change="handleSort"
          v-bind="$attrs"
          class="premium-table"
          :header-cell-style="{ background: '#f8fafc', color: '#1e293b', fontWeight: 600, fontSize: '13px' }"
          :cell-style="{ fontSize: '13px' }"
          row-class-name="premium-row"
        >
          <slot />
          <el-table-column label="操作" :width="actionWidth" fixed="right" v-if="$slots.action">
            <template #default="scope">
              <slot name="action" v-bind="scope" />
            </template>
          </el-table-column>
        </el-table>
      </div>
      <div class="pagination-wrap" v-if="showPagination">
        <el-pagination
          v-model:current-page="page"
          v-model:page-size="limit"
          :total="total"
          :page-sizes="[15, 30, 50, 100]"
          layout="total, sizes, prev, pager, next"
          @update:current-page="emit('refresh')"
          @update:page-size="emit('refresh')"
        />
      </div>
    </el-card>
  </div>
</template>

<script setup lang="ts">
import { Search } from '@element-plus/icons-vue'

defineProps({
  title: { type: String, default: '' },
  subtitle: { type: String, default: '' },
  data: { type: Array, default: () => [] },
  loading: Boolean,
  total: Number,
  actionWidth: { type: [String, Number], default: 200 },
  showPagination: { type: Boolean, default: true },
})

const page = defineModel<number>('page', { default: 1 })
const limit = defineModel<number>('limit', { default: 15 })

const emit = defineEmits<{
  sort: [s: { prop: string; order: string }]
  refresh: []
}>()

function handleSort(s: any) {
  emit('sort', {
    prop: s.prop || '',
    order: s.order === 'ascending' ? 'asc' : s.order === 'descending' ? 'desc' : '',
  })
}
</script>

<style scoped>
.page-wrapper {
  animation: fadeIn 0.4s ease;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(8px); }
  to { opacity: 1; transform: translateY(0); }
}

/* ===== 横幅 ===== */
.page-banner {
  position: relative;
  border-radius: 16px;
  overflow: hidden;
  margin-bottom: 20px;
  padding: 28px 32px 24px;
  color: #fff;
  min-height: 80px;
}
.banner-bg {
  position: absolute;
  inset: 0;
  background: linear-gradient(135deg, #1e3a5f 0%, #2563eb 30%, #7c3aed 70%, #4f46e5 100%);
  z-index: 0;
}
.banner-bg::before {
  content: '';
  position: absolute;
  top: -50%;
  right: -10%;
  width: 400px;
  height: 400px;
  border-radius: 50%;
  background: rgba(255,255,255,0.06);
  animation: float 8s ease-in-out infinite;
}
.banner-bg::after {
  content: '';
  position: absolute;
  bottom: -60%;
  left: 20%;
  width: 300px;
  height: 300px;
  border-radius: 50%;
  background: rgba(255,255,255,0.04);
  animation: float 10s ease-in-out infinite reverse;
}
@keyframes float {
  0%, 100% { transform: translateY(0) scale(1); }
  50% { transform: translateY(-20px) scale(1.05); }
}
.banner-content {
  position: relative;
  z-index: 1;
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
}
.banner-left { flex: 1; min-width: 0; }
.banner-title {
  font-size: 22px;
  font-weight: 700;
  margin: 0 0 4px;
  letter-spacing: 0.5px;
  text-shadow: 0 2px 8px rgba(0,0,0,0.15);
}
.banner-subtitle {
  font-size: 13px;
  opacity: 0.85;
  margin: 0;
}
.banner-right {
  flex-shrink: 0;
  display: flex;
  align-items: center;
  gap: 10px;
}

/* ===== 统计卡片 ===== */
.banner-stats {
  position: relative;
  z-index: 1;
  margin-top: 20px;
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
  gap: 14px;
}
.banner-stats :deep(.stat-card) {
  background: rgba(255,255,255,0.15);
  backdrop-filter: blur(12px);
  border: 1px solid rgba(255,255,255,0.2);
  border-radius: 12px;
  padding: 16px 20px;
  transition: all 0.3s;
  cursor: default;
}
.banner-stats :deep(.stat-card:hover) {
  background: rgba(255,255,255,0.22);
  transform: translateY(-2px);
  box-shadow: 0 8px 24px rgba(0,0,0,0.12);
}
.banner-stats :deep(.stat-label) {
  font-size: 12px;
  opacity: 0.8;
  margin-bottom: 6px;
}
.banner-stats :deep(.stat-value) {
  font-size: 26px;
  font-weight: 700;
  letter-spacing: 1px;
}
.banner-stats :deep(.stat-desc) {
  font-size: 11px;
  opacity: 0.7;
  margin-top: 4px;
}

/* ===== 搜索卡片 ===== */
.search-card {
  background: #fff;
  border-radius: 12px;
  margin-bottom: 16px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.06), 0 1px 2px rgba(0,0,0,0.04);
  overflow: hidden;
}
.search-card-header {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 14px 20px;
  background: #f8fafc;
  border-bottom: 1px solid #f1f5f9;
  font-size: 13px;
  font-weight: 600;
  color: #475569;
}
.search-card-body {
  padding: 16px 20px;
}
.search-card-body :deep(.el-form-item) {
  margin-bottom: 0;
}

/* ===== 表格卡片 ===== */
.table-card-premium {
  border-radius: 12px;
  border: 1px solid #e2e8f0;
  box-shadow: 0 1px 3px rgba(0,0,0,0.06), 0 1px 2px rgba(0,0,0,0.04);
  transition: box-shadow 0.3s;
}
.table-card-premium:hover {
  box-shadow: 0 4px 12px rgba(0,0,0,0.08), 0 2px 4px rgba(0,0,0,0.04);
}
.table-toolbar {
  padding: 16px 20px;
  border-bottom: 1px solid #f1f5f9;
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 10px;
}
.table-body {
  padding: 0;
}
.pagination-wrap {
  padding: 16px 20px;
  border-top: 1px solid #f1f5f9;
  display: flex;
  justify-content: flex-end;
}

/* ===== 表格美化 ===== */
.premium-table :deep(.premium-row) {
  transition: background 0.2s;
}
.premium-table :deep(.el-table__body tr:hover > td) {
  background: #f0f7ff !important;
}
.premium-table :deep(.el-table__header th) {
  border-bottom: 2px solid #e2e8f0;
}
.premium-table :deep(.el-table--striped .el-table__body tr.el-table__row--striped td) {
  background: #fafbfc;
}

/* ===== 响应式 ===== */
@media (max-width: 768px) {
  .page-banner { padding: 20px 16px; }
  .banner-title { font-size: 18px; }
  .banner-stats { grid-template-columns: repeat(2, 1fr); }
}
</style>
