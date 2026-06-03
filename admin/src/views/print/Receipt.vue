<template>
  <div class="page-container">
    <div class="search-bar">
      <el-form :model="query" inline>
        <el-form-item><el-input v-model="query.keyword" placeholder="账单号/业主" clearable style="width:200px;" /></el-form-item>
        <el-form-item><el-select v-model="query.community_id" placeholder="小区" clearable style="width:160px;"><el-option v-for="c in communities" :key="c.id" :label="c.name" :value="c.id" /></el-select></el-form-item>
        <el-form-item>
          <el-button type="primary" @click="loadData">搜索</el-button>
          <el-button @click="resetQuery">重置</el-button>
        </el-form-item>
      </el-form>
    </div>
    <el-card shadow="never" class="table-card">
      <el-table :data="list" v-loading="loading" stripe border>
        <el-table-column type="index" label="#" width="50" />
        <el-table-column prop="bill_no" label="账单号" width="160" />
        <el-table-column prop="owner_name" label="业主" width="100" />
        <el-table-column prop="room_number" label="房间" width="100" />
        <el-table-column prop="community_name" label="小区" width="120" />
        <el-table-column prop="charge_item_name" label="收费项目" width="120" />
        <el-table-column prop="total_amount" label="金额" width="100"><template #default="{row}">¥{{ row.total_amount }}</template></el-table-column>
        <el-table-column prop="paid_amount" label="已缴" width="100"><template #default="{row}">¥{{ row.paid_amount }}</template></el-table-column>
        <el-table-column prop="status" label="状态" width="90"><template #default="{row}"><el-tag :type="row.status===3?'success':row.status===2?'warning':'info'">{{ row.status===3?'已缴':row.status===2?'部分缴纳':'待缴' }}</el-tag></template></el-table-column>
        <el-table-column label="操作" width="140" fixed="right">
          <template #default="{row}">
            <el-button size="small" type="primary" @click="printReceipt(row)">打印收据</el-button>
          </template>
        </el-table-column>
      </el-table>
      <div class="pagination">
        <el-pagination v-model:current-page="query.page" v-model:page-size="query.limit" :total="total" :page-sizes="[15,30,50,100]" layout="total,sizes,prev,pager,next" @current-change="loadData" @size-change="loadData" />
      </div>
    </el-card>

    <el-dialog v-model="printVisible" title="收据预览" width="500px" destroy-on-close>
      <div id="receipt-print" style="padding:20px;border:1px dashed #ccc;background:#fff;" v-if="printData">
        <h3 style="text-align:center;margin-bottom:20px;">物业收费收据</h3>
        <p><strong>收据编号：</strong>{{ printData.bill_no }}</p>
        <p><strong>业主姓名：</strong>{{ printData.owner_name }}</p>
        <p><strong>房间号：</strong>{{ printData.room_number }}</p>
        <p><strong>收费项目：</strong>{{ printData.charge_item_name }}</p>
        <p><strong>账期：</strong>{{ printData.bill_period }}</p>
        <p><strong>应收金额：</strong>¥{{ printData.total_amount }}</p>
        <p><strong>实收金额：</strong>¥{{ printData.paid_amount }}</p>
        <p><strong>收款日期：</strong>{{ printData.pay_date||'-' }}</p>
        <p style="margin-top:20px;text-align:center;color:#999;">大圣物业管理系统</p>
      </div>
      <template #footer>
        <el-button @click="printVisible = false">关闭</el-button>
        <el-button type="primary" @click="doPrint">打印</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { apiGet } from '@/utils/request'

const list = ref<any[]>([])
const total = ref(0)
const loading = ref(false)
const printVisible = ref(false)
const printData = ref<any>(null)
const communities = ref<any[]>([])

const query = reactive({ keyword: '', community_id: undefined as any, page: 1, limit: 15 })

function resetQuery() { query.keyword = ''; query.community_id = undefined; query.page = 1; loadData() }

async function loadData() {
  loading.value = true
  try {
    const r = await apiGet('/admin/charge/billList', { ...query })
    list.value = r.data?.list || r.data || []
    total.value = r.count || r.data?.total || list.value.length
  } catch { list.value = []; total.value = 0 }
  finally { loading.value = false }
}

async function printReceipt(row: any) {
  try {
    const r = await apiGet('/admin/charge/billDetail', { id: row.id })
    printData.value = r.data
    printVisible.value = true
  } catch {}
}

function doPrint() {
  const el = document.getElementById('receipt-print')
  if (!el) return
  const w = window.open('', '_blank')
  if (!w) return
  w.document.write('<html><head><title>收据</title></head><body>' + el.innerHTML + '</body></html>')
  w.document.close()
  w.print()
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
