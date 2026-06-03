<template>
  <div class="page-container">
    <div class="search-bar">
      <el-form :model="query" inline>
        <el-form-item><el-select v-model="query.community_id" placeholder="小区" clearable style="width:160px;"><el-option v-for="c in communities" :key="c.id" :label="c.name" :value="c.id" /></el-select></el-form-item>
        <el-form-item><el-select v-model="query.route_id" placeholder="巡更路线" clearable style="width:160px;"><el-option v-for="r in routes" :key="r.id" :label="r.name" :value="r.id" /></el-select></el-form-item>
        <el-form-item>
          <el-button type="primary" @click="loadData">搜索</el-button>
          <el-button @click="resetQuery">重置</el-button>
        </el-form-item>
      </el-form>
    </div>
    <el-card shadow="never" class="table-card">
      <el-table :data="list" v-loading="loading" stripe border>
        <el-table-column type="index" label="#" width="50" />
        <el-table-column prop="id" label="ID" width="60" />
        <el-table-column prop="route_name" label="路线名称" width="150" />
        <el-table-column prop="community_name" label="小区" width="120" />
        <el-table-column prop="point_name" label="检查点" width="150" />
        <el-table-column prop="staff_name" label="巡检人" width="120" />
        <el-table-column prop="check_time" label="巡检时间" width="170" />
        <el-table-column prop="result" label="结果" width="90"><template #default="{row}"><el-tag :type="row.result===1?'success':'danger'">{{ row.result===1?'正常':'异常' }}</el-tag></template></el-table-column>
        <el-table-column prop="remark" label="备注" show-overflow-tooltip />
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
const routes = ref<any[]>([])

const query = reactive({ community_id: undefined as any, route_id: undefined as any, page: 1, limit: 15 })

function resetQuery() { query.community_id = undefined; query.route_id = undefined; query.page = 1; loadData() }

async function loadData() {
  loading.value = true
  try {
    const r = await apiGet('/admin/security/patrolRecordList', { ...query })
    list.value = r.data?.list || r.data || []
    total.value = r.count || r.data?.total || list.value.length
  } catch { list.value = []; total.value = 0 }
  finally { loading.value = false }
}

onMounted(async () => {
  try {
    const rc = await apiGet('/admin/community/list', { limit: 999 })
    communities.value = rc.data?.list || rc.data || []
    const rr = await apiGet('/admin/security/patrolRouteList', { limit: 999 })
    routes.value = rr.data?.list || rr.data || []
  } catch {}
  loadData()
})
</script>

<style scoped>
.search-bar { background:#fff;border-radius:8px;padding:16px 20px;margin-bottom:16px;border:1px solid #e2e8f0; }
.table-card { border-radius:8px;border:1px solid #e2e8f0; }
.pagination { margin-top:16px;display:flex;justify-content:flex-end; }
</style>
