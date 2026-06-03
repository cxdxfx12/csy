<template>
  <div class="page-container">
    <div class="search-bar">
      <el-form :model="query" inline>
        <el-form-item><el-select v-model="query.group" placeholder="配置分组" @change="loadData"><el-option label="系统配置" value="system" /><el-option label="收费配置" value="charge" /><el-option label="停车配置" value="parking" /></el-select></el-form-item>
      </el-form>
    </div>
    <el-card shadow="never" class="table-card">
      <el-form :model="formData" label-width="180px">
        <el-form-item v-for="item in list" :key="item.key" :label="item.name">
          <el-input v-if="item.type==='input'" v-model="formData[item.key]" />
          <el-input v-else-if="item.type==='textarea'" v-model="formData[item.key]" type="textarea" rows="3" />
          <el-switch v-else-if="item.type==='switch'" v-model="formData[item.key]" active-value="1" inactive-value="0" />
          <el-input-number v-else-if="item.type==='number'" v-model="formData[item.key]" />
          <el-input v-else v-model="formData[item.key]" />
          <div style="font-size:12px;color:#a0aec0;margin-top:4px;">{{ item.remark }}</div>
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="saveConfig" :loading="submitting">保存配置</el-button>
        </el-form-item>
      </el-form>
    </el-card>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { apiGet, apiPost } from '@/utils/request'

const list = ref<any[]>([])
const formData = reactive<Record<string, any>>({})
const submitting = ref(false)

const query = reactive({ group: 'system' })

async function loadData() {
  try {
    const r = await apiGet('/admin/config/list', { group: query.group })
    list.value = r.data || []
    list.value.forEach((item: any) => { formData[item.key] = item.value })
  } catch { list.value = [] }
}

async function saveConfig() {
  submitting.value = true
  try {
    await apiPost('/admin/config/save', { data: { ...formData } })
    ElMessage.success('保存成功')
  } finally { submitting.value = false }
}

onMounted(loadData)
</script>

<style scoped>
.search-bar { background:#fff;border-radius:8px;padding:16px 20px;margin-bottom:16px;border:1px solid #e2e8f0; }
.table-card { border-radius:8px;border:1px solid #e2e8f0; }
</style>
