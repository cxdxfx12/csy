<template>
  <div class="page-container">
    <!-- 搜索栏 -->
    <div class="search-bar">
      <el-form :model="query" inline>
        <el-form-item><el-input v-model="query.keyword" placeholder="搜索小区名称/编码" clearable style="width:220px;" @keyup.enter="loadData" /></el-form-item>
        <el-form-item>
          <el-button type="primary" @click="loadData">搜索</el-button>
          <el-button @click="resetQuery">重置</el-button>
        </el-form-item>
      </el-form>
    </div>

    <!-- 小区列表 -->
    <el-card shadow="never" class="table-card">
      <el-table :data="list" v-loading="loading" stripe border highlight-current-row>
        <el-table-column type="index" label="#" width="55" />
        <el-table-column prop="name" label="小区名称" min-width="160">
          <template #default="{row}"><el-tag effect="plain">{{ row.name }}</el-tag> <span style="color:#a0aec0;font-size:12px;">{{ row.code }}</span></template>
        </el-table-column>
        <el-table-column prop="address" label="地址" min-width="200" show-overflow-tooltip />
        <el-table-column prop="wx_app_id" label="已绑定公众号" width="200" align="center">
          <template #default="{row}">
            <el-tag v-if="row.wx_status===1" type="success" effect="plain">{{ row.wx_app_id }}</el-tag>
            <el-tag v-else type="info" effect="plain">未绑定</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="wx_status" label="状态" width="100" align="center">
          <template #default="{row}">
            <el-switch :model-value="row.wx_status===1" disabled size="small" />
          </template>
        </el-table-column>
        <el-table-column label="操作" width="160" align="center" fixed="right">
          <template #default="{row}">
            <el-button size="small" type="primary" @click="openConfig(row)">配置</el-button>
            <el-button v-if="row.wx_status===1" size="small" type="success" @click="testConfig(row)">测试</el-button>
          </template>
        </el-table-column>
      </el-table>
      <div class="pagination">
        <el-pagination v-model:current-page="query.page" v-model:page-size="query.limit" :total="total" :page-sizes="[15,30,50]" layout="total,sizes,prev,pager,next" @change="loadData" />
      </div>
    </el-card>

    <!-- 配置抽屉 -->
    <el-drawer v-model="drawerVisible" :title="'公众号配置 - ' + currentCommunity.name" size="580px" :close-on-click-modal="false" destroy-on-close>
      <!-- 基础配置 -->
      <el-alert title="公众号基础配置" type="success" :closable="false" show-icon style="margin-bottom:16px;">
        <template #default>
          请前往 <b>微信公众平台 (mp.weixin.qq.com)</b> → 设置与开发 → 基本配置 获取以下参数
        </template>
      </el-alert>

      <el-form :model="form" label-width="130px" label-position="top">
        <el-row :gutter="16">
          <el-col :span="12">
            <el-form-item label="AppID（开发者ID）">
              <el-input v-model="form.app_id" placeholder="wx开头，如 wx1234567890abcdef" clearable />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="原始ID">
              <el-input v-model="form.original_id" placeholder="gh_ 开头" clearable />
            </el-form-item>
          </el-col>
        </el-row>

        <el-row :gutter="16">
          <el-col :span="24">
            <el-form-item label="AppSecret（开发者密码）">
              <el-input v-model="form.app_secret" placeholder="32位字符串，留空则不修改" clearable show-password />
              <div class="tip">若已设置，留空不修改原有密钥</div>
            </el-form-item>
          </el-col>
        </el-row>

        <el-row :gutter="16">
          <el-col :span="12">
            <el-form-item label="消息校验Token">
              <el-input v-model="form.token" placeholder="3-32位字符，留空不修改" clearable show-password />
              <div class="tip">服务器配置中的 Token，用于验证消息</div>
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="消息加密密钥">
              <el-input v-model="form.encoding_aes_key" placeholder="43位，留空不修改" clearable show-password />
              <div class="tip">EncodingAESKey，安全模式下使用</div>
            </el-form-item>
          </el-col>
        </el-row>
      </el-form>

      <el-divider content-position="left">模板消息</el-divider>
      <el-alert title="用于给业主推送通知" type="info" :closable="false" show-icon style="margin-bottom:16px;">
        <template #default>
          在公众号后台 <b>功能 → 模板消息</b> 中添加模板后获取ID。两模板留空可绑定 AppID 后再补填。
        </template>
      </el-alert>

      <el-form :model="form" label-width="140px" label-position="top">
        <el-form-item label="缴费成功通知模板ID">
          <el-input v-model="form.template_pay_success" placeholder="模板消息ID，如 XXXXXXXXXXXXXXXXXXXXXXXXX" clearable />
          <div class="tip">业主缴费成功后推送的模板消息。模板需包含：缴费金额、缴费时间、账单编号等字段</div>
        </el-form-item>
        <el-form-item label="催缴通知模板ID">
          <el-input v-model="form.template_arrears" placeholder="模板消息ID，如 XXXXXXXXXXXXXXXXXXXXXXXXX" clearable />
          <div class="tip">欠费催缴通知模板。模板需包含：欠费金额、账单周期、缴费截止日期等字段</div>
        </el-form-item>
        <el-form-item label="关联微信支付商户号">
          <el-input v-model="form.mch_id" placeholder="微信支付商户号（可选，用于模板消息跳转）" clearable />
          <div class="tip">如果已在支付配置中设置，此处可留空。用于模板消息点击后跳转缴费页面</div>
        </el-form-item>
      </el-form>

      <!-- 底部操作 -->
      <div class="drawer-footer">
        <el-button @click="handleTest" :disabled="!form.app_id" :loading="testing">
          测试公众号接口
        </el-button>
        <div style="flex:1;" />
        <el-button @click="drawerVisible = false">取消</el-button>
        <el-button type="primary" @click="handleSave" :loading="saving">保存配置</el-button>
      </div>
    </el-drawer>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { apiGet, apiPost } from '@/utils/request'

const list = ref<any[]>([])
const total = ref(0)
const loading = ref(false)
const saving = ref(false)
const testing = ref(false)
const drawerVisible = ref(false)
const currentCommunity = ref<{id:number;name:string;code:string}>({id:0,name:'',code:''})

const query = reactive({ keyword: '', page: 1, limit: 15 })
const form = reactive<Record<string, any>>({
  community_id: 0,
  app_id: '', app_secret: '', token: '', encoding_aes_key: '', original_id: '',
  mch_id: '', template_pay_success: '', template_arrears: '',
})

function resetQuery() { query.keyword = ''; query.page = 1; loadData() }

async function loadData() {
  loading.value = true
  try {
    const r = await apiGet('/admin/wechat/configList', { ...query })
    list.value = r.data?.list || r.data || []
    total.value = r.count || r.data?.total || list.value.length
  } catch { list.value = []; total.value = 0 }
  finally { loading.value = false }
}

function resetForm() {
  form.community_id = 0
  form.app_id = ''; form.app_secret = ''; form.token = ''; form.encoding_aes_key = ''
  form.original_id = ''; form.mch_id = ''; form.template_pay_success = ''; form.template_arrears = ''
}

async function openConfig(row: any) {
  resetForm()
  currentCommunity.value = { id: row.id, name: row.name, code: row.code }
  try {
    const r = await apiGet('/admin/wechat/configDetail', { community_id: row.id })
    const cfg = r.data?.config || null
    form.community_id = row.id
    if (cfg) {
      form.app_id = cfg.app_id || ''
      form.app_secret = cfg.app_secret || ''
      form.token = cfg.token || ''
      form.encoding_aes_key = cfg.encoding_aes_key || ''
      form.original_id = cfg.original_id || ''
      form.mch_id = cfg.mch_id || ''
      form.template_pay_success = cfg.template_pay_success || ''
      form.template_arrears = cfg.template_arrears || ''
    }
  } catch {}
  drawerVisible.value = true
}

async function handleSave() {
  if (!form.community_id) return ElMessage.warning('请先选择小区')
  if (!form.app_id) return ElMessage.warning('请填写公众号AppID')

  saving.value = true
  try {
    await apiPost('/admin/wechat/configSave', { ...form })
    ElMessage.success('保存成功')
    drawerVisible.value = false
    loadData()
  } finally { saving.value = false }
}

async function handleTest() {
  if (!form.community_id) return ElMessage.warning('请先保存配置')
  testing.value = true
  try {
    const r = await apiGet('/admin/wechat/configTest', { community_id: form.community_id })
    ElMessage.success({ message: r.msg, duration: 3000 })
  } finally { testing.value = false }
}

function testConfig(row: any) {
  ElMessageBox.confirm(`确定测试「${row.name}」的公众号配置吗？`, '测试公众号配置', {
    confirmButtonText: '开始测试',
    type: 'info',
  }).then(async () => {
    try {
      const r = await apiGet('/admin/wechat/configTest', { community_id: row.id })
      ElMessage.success({ message: r.msg, duration: 3000 })
    } catch {}
  }).catch(() => {})
}

onMounted(loadData)
</script>

<style scoped>
.page-container { padding: 0; }
.search-bar { background: #fff; border-radius: 8px; padding: 16px 20px; margin-bottom: 16px; border: 1px solid #e2e8f0; }
.table-card { border-radius: 8px; border: 1px solid #e2e8f0; }
.pagination { margin-top: 16px; display: flex; justify-content: flex-end; }
.drawer-footer { display: flex; align-items: center; gap: 10px; padding: 16px 0 0; border-top: 1px solid #e2e8f0; margin-top: 20px; }
.tip { font-size: 12px; color: #a0aec0; margin-top: 4px; line-height: 1.4; }
</style>
