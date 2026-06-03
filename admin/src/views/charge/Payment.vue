<template>
  <div class="page-container">
    <div class="search-bar">
      <el-form :model="query" inline>
        <el-form-item><el-input v-model="query.keyword" placeholder="流水号/姓名/房间" clearable style="width:200px;" /></el-form-item>
        <el-form-item><el-select v-model="query.community_id" placeholder="小区" clearable style="width:160px;"><el-option v-for="c in communities" :key="c.id" :label="c.name" :value="c.id" /></el-select></el-form-item>
        <el-form-item><el-select v-model="query.pay_method" placeholder="支付方式" clearable style="width:130px;"><el-option label="现金" :value="1" /><el-option label="微信" :value="2" /><el-option label="支付宝" :value="3" /><el-option label="银行卡" :value="4" /><el-option label="其他" :value="5" /></el-select></el-form-item>
        <el-form-item>
          <el-button type="primary" @click="loadData">搜索</el-button>
          <el-button @click="resetQuery">重置</el-button>
        </el-form-item>
      </el-form>
    </div>
    <el-card shadow="never" class="table-card">
      <el-table :data="list" v-loading="loading" stripe border>
        <el-table-column type="index" label="#" width="50" />
        <el-table-column prop="payment_no" label="流水号" width="160" />
        <el-table-column prop="owner_name" label="业主" width="100" />
        <el-table-column prop="room_number" label="房间" width="100" />
        <el-table-column prop="community_name" label="小区" width="120" />
        <el-table-column prop="amount" label="金额" width="100"><template #default="{row}">¥{{ row.amount }}</template></el-table-column>
        <el-table-column prop="pay_method" label="方式" width="90"><template #default="{row}">{{ methodMap[row.pay_method]||row.pay_method }}</template></el-table-column>
        <el-table-column prop="pay_time" label="缴费时间" width="160" />
        <el-table-column prop="operator_name" label="操作员" width="100" />
        <el-table-column label="操作" width="120" fixed="right">
          <template #default="{row}">
            <el-button size="small" type="danger" @click="handleDelete(row)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>
      <div class="pagination">
        <el-pagination v-model:current-page="query.page" v-model:page-size="query.limit" :total="total" :page-sizes="[15,30,50,100]" layout="total,sizes,prev,pager,next" @change="loadData" />
      </div>
    </el-card>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { apiGet, apiPost } from '@/utils/request'

const list = ref<any[]>([])
const total = ref(0)
const loading = ref(false)
const communities = ref<any[]>([])

const methodMap: Record<number, string> = { 1: '现金', 2: '微信', 3: '支付宝', 4: '银行卡', 5: '其他' }

const query = reactive({ keyword: '', community_id: undefined as any, pay_method: undefined as any, page: 1, limit: 15 })

function resetQuery() { query.keyword = ''; query.community_id = undefined; query.pay_method = undefined; query.page = 1; loadData() }

async function loadData() {
  loading.value = true
  try {
    const r = await apiGet('/admin/charge/paymentList', { ...query })
    list.value = r.data?.list || r.data || []
    total.value = r.count || r.data?.total || list.value.length
  } catch { list.value = []; total.value = 0 }
  finally { loading.value = false }
}

async function handleDelete(row: any) {
  try {
    await ElMessageBox.confirm(`确定删除缴费记录 "${row.payment_no}" 吗？`, '提示', { type: 'warning' })
    await apiPost('/admin/charge/paymentDelete', { id: row.id })
    ElMessage.success('删除成功')
    loadData()
  } catch {}
}

onMounted(async () => {
  try { const r = await apiGet('/admin/community/list', { limit: 999 }); communities.value = r.data?.list || r.data || [] } catch {}
  loadData()
})
</script>

<style scoped>
.search-bar { background:#fff;border-radius:8px;padding:16px 20px;margin-bottom:16px;border:1px solid #e2e8f0; }
.table-card { border-radius:8px;border:1px solid #e2e8f0; }
.table-toolbar { margin-bottom:16px; }
.pagination { margin-top:16px;display:flex;justify-content:flex-end; }
</style>
