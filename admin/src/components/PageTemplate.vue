<template>
  <div class="page-container">
    <div class="search-bar" v-if="$slots.search">
      <slot name="search" />
    </div>
    <el-card shadow="never" class="table-card">
      <div class="table-toolbar" v-if="$slots.toolbar">
        <slot name="toolbar" />
      </div>
      <el-table :data="data" v-loading="loading" stripe border @sort-change="handleSort" v-bind="$attrs">
        <slot />
        <el-table-column label="操作" :width="actionWidth" fixed="right" v-if="$slots.action">
          <template #default="scope"><slot name="action" v-bind="scope" /></template>
        </el-table-column>
      </el-table>
      <div class="pagination" v-if="showPagination">
        <el-pagination v-model:current-page="page" v-model:page-size="limit" :total="total" :page-sizes="[15,30,50,100]" layout="total,sizes,prev,pager,next" @change="emit('refresh')" />
      </div>
    </el-card>
  </div>
</template>

<script setup lang="ts">
defineProps({ data: { type: Array, default: () => [] }, loading: Boolean, total: Number, actionWidth: { type: [String, Number], default: 200 }, showPagination: { type: Boolean, default: true } })
const page = defineModel<number>('page', { default: 1 })
const limit = defineModel<number>('limit', { default: 15 })
const emit = defineEmits<{ sort: [s: { prop: string; order: string }]; refresh: [] }>()
function handleSort(s: any) { emit('sort', { prop: s.prop || '', order: s.order === 'ascending' ? 'asc' : s.order === 'descending' ? 'desc' : '' }) }
</script>

<style scoped>
.search-bar { background: #fff; border-radius: 8px; padding: 16px 20px; margin-bottom: 16px; border: 1px solid #e2e8f0; }
.table-card { border-radius: 8px; border: 1px solid #e2e8f0; }
.table-toolbar { margin-bottom: 16px; }
.pagination { margin-top: 16px; display: flex; justify-content: flex-end; }
</style>
