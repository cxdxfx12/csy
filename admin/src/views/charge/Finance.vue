<template>
  <div class="page-container">
    <div class="search-bar">
      <el-form :model="query" inline>
        <el-form-item><el-select v-model="query.type" placeholder="收支类型" clearable style="width:130px;"><el-option label="收入" :value="1" /><el-option label="支出" :value="2" /></el-select></el-form-item>
        <el-form-item><el-select v-model="query.community_id" placeholder="小区" clearable style="width:160px;"><el-option v-for="c in communities" :key="c.id" :label="c.name" :value="c.id" /></el-select></el-form-item>
        <el-form-item>
          <el-button type="primary" @click="loadData">搜索</el-button>
          <el-button @click="resetQuery">重置</el-button>
        </el-form-item>
      </el-form>
    </div>
    <el-row :gutter="16" style="margin-bottom:16px;">
      <el-col :span="8"><el-card shadow="hover"><div style="font-size:13px;color:#a0aec0;">收入总额</div><div style="font-size:22px;font-weight:700;color:#38a169;">¥{{ stats.income_total }}</div></el-card></el-col>
      <el-col :span="8"><el-card shadow="hover"><div style="font-size:13px;color:#a0aec0;">支出总额</div><div style="font-size:22px;font-weight:700;color:#e53e3e;">¥{{ stats.expense_total }}</div></el-card></el-col>
      <el-col :span="8"><el-card shadow="hover"><div style="font-size:13px;color:#a0aec0;">结余</div><div style="font-size:22px;font-weight:700;color:#3182ce;">¥{{ stats.income_total - stats.expense_total }}</div></el-card></el-col>
    </el-row>
    <el-card shadow="never" class="table-card">
      <el-table :data="list" v-loading="loading" stripe border>
        <el-table-column type="index" label="#" width="50" />
        <el-table-column prop="id" label="ID" width="60" />
        <el-table-column prop="flow_no" label="流水号" width="160" />
        <el-table-column prop="community_name" label="小区" width="120" />
        <el-table-column prop="type" label="类型" width="80"><template #default="{row}"><el-tag :type="row.type===1?'success':'danger'">{{ row.type===1?'收入':'支出' }}</el-tag></template></el-table-column>
        <el-table-column prop="category" label="分类" width="100" />
        <el-table-column prop="amount" label="金额" width="120"><template #default="{row}">¥{{ row.amount }}</template></el-table-column>
        <el-table-column prop="description" label="摘要" show-overflow-tooltip />
        <el-table-column prop="operator_name" label="操作人" width="100" />
        <el-table-column prop="create_time" label="时间" width="170" />
      </el-table>
      <div class="pagination">
        <el-pagination v-model:current-page="query.page" v-model:page-size="query.limit" :total="total" :page-sizes="[15,30,50,100]" layout="total,sizes,prev,pager,next" @current-change="loadData" @size-change="loadData" />
      </div>
    </el-card>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { apiGet } from '@/utils/request'

const list = ref<any[]>([])
const total = ref(0)
const loading = ref(false)
const communities = ref<any[]>([])
const stats = reactive({ income_total: 0, expense_total: 0 })

const query = reactive({ type: undefined as any, community_id: undefined as any, page: 1, limit: 15 })

function resetQuery() { query.type = undefined; query.community_id = undefined; query.page = 1; loadData() }

async function loadData() {
  loading.value = true
  try {
    const r = await apiGet('/admin/charge/financeList', { ...query })
    list.value = r.data?.list || r.data || []
    total.value = r.data?.total || r.count || list.value.length
    stats.income_total = r.data?.income_total || 0
    stats.expense_total = r.data?.expense_total || 0
  } catch { list.value = []; total.value = 0 }
  finally { loading.value = false }
}

onMounted(async () => {
  try { const r = await apiGet('/admin/community/list', { limit: 999 }); communities.value = r.data?.list || r.data || [] } catch {}
  loadData()
})
</script>

<style scoped>
.search-bar { background:#fff;border-radius:8px;padding:16px 20px;margin-bottom:16px;border:1px solid #e2e8f0; }
.table-card { border-radius:8px;border:1px solid #e2e8f0; }
.pagination { margin-top:16px;display:flex;justify-content:flex-end; }
</style>
