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

    <!-- 小区列表卡片 -->
    <el-card shadow="never" class="table-card">
      <el-table :data="list" v-loading="loading" stripe border highlight-current-row>
        <el-table-column type="index" label="#" width="55" />
        <el-table-column prop="name" label="小区名称" min-width="160">
          <template #default="{row}"><el-tag effect="plain" type="">{{ row.name }}</el-tag> <span style="color:#a0aec0;font-size:12px;">{{ row.code }}</span></template>
        </el-table-column>
        <el-table-column prop="address" label="地址" min-width="200" show-overflow-tooltip />
        <el-table-column prop="pay_channel_label" label="支付渠道" width="140" align="center">
          <template #default="{row}">
            <el-tag v-if="row.pay_channel==='wechat'" type="success" effect="plain">微信支付</el-tag>
            <el-tag v-else-if="row.pay_channel==='alipay'" type="primary" effect="plain">支付宝</el-tag>
            <el-tag v-else-if="row.pay_channel==='both'" effect="plain" style="background:linear-gradient(90deg,#07c160,#1677ff);color:#fff;border:none;">微信+支付宝</el-tag>
            <el-tag v-else type="info" effect="plain">未配置</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="pay_status" label="状态" width="100" align="center">
          <template #default="{row}">
            <el-switch :model-value="row.pay_status===1" disabled size="small" />
          </template>
        </el-table-column>
        <el-table-column label="操作" width="160" align="center" fixed="right">
          <template #default="{row}">
            <el-button size="small" type="primary" @click="openConfig(row)">配置</el-button>
            <el-button v-if="row.pay_status===1" size="small" type="success" @click="testConfig(row)">测试</el-button>
          </template>
        </el-table-column>
      </el-table>
      <div class="pagination">
        <el-pagination v-model:current-page="query.page" v-model:page-size="query.limit" :total="total" :page-sizes="[15,30,50]" layout="total,sizes,prev,pager,next" @current-change="loadData" @size-change="loadData" />
      </div>
    </el-card>

    <!-- 配置抽屉 -->
    <el-drawer v-model="drawerVisible" :title="'支付配置 - ' + currentCommunity.name" size="580px" :close-on-click-modal="false" destroy-on-close>
      <el-tabs v-model="activeTab" type="border-card">
        <!-- 微信支付 Tab -->
        <el-tab-pane label="微信支付" name="wechat">
          <template #label>
            <span style="color:#07c160;">微信支付</span>
          </template>
          <el-form :model="form" label-width="120px" label-position="top">
            <el-alert title="微信商户支付配置" type="success" :closable="false" show-icon style="margin-bottom:16px;">
              <template #default>请前往 <b>微信支付商户平台 (pay.weixin.qq.com)</b> 获取以下参数</template>
            </el-alert>
            <el-form-item label="AppID（应用ID）">
              <el-input v-model="form.wechat_app_id" placeholder="wx开头，如 wx1234567890abcdef" clearable />
            </el-form-item>
            <el-form-item label="商户号（MchID）">
              <el-input v-model="form.wechat_mch_id" placeholder="10位数字商户号" clearable />
            </el-form-item>
            <el-form-item label="APIv2密钥">
              <el-input v-model="form.wechat_api_key" placeholder="32位密钥，留空则不修改" clearable show-password />
              <div class="tip">若已设置，留空则不修改原有密钥</div>
            </el-form-item>
            <el-form-item label="APIv3密钥">
              <el-input v-model="form.wechat_api_v3_key" placeholder="32位APIv3密钥，留空则不修改" clearable show-password />
              <div class="tip">若已设置，留空则不修改</div>
            </el-form-item>
            <el-form-item label="证书序列号">
              <el-input v-model="form.wechat_serial_no" placeholder="商户API证书序列号" clearable />
            </el-form-item>
          </el-form>
        </el-tab-pane>

        <!-- 支付宝 Tab -->
        <el-tab-pane label="支付宝" name="alipay">
          <template #label>
            <span style="color:#1677ff;">支付宝</span>
          </template>
          <el-form :model="form" label-width="120px" label-position="top">
            <el-alert title="支付宝商户支付配置" type="primary" :closable="false" show-icon style="margin-bottom:16px;">
              <template #default>请前往 <b>支付宝开放平台 (open.alipay.com)</b> 获取以下参数</template>
            </el-alert>
            <el-form-item label="AppID（应用ID）">
              <el-input v-model="form.alipay_app_id" placeholder="如 2021001xxxxxxxxx" clearable />
            </el-form-item>
            <el-form-item label="商户PID">
              <el-input v-model="form.alipay_merchant_id" placeholder="16位商户PID" clearable />
            </el-form-item>
            <el-form-item label="应用私钥">
              <el-input v-model="form.alipay_private_key" type="textarea" :rows="4" placeholder="RSA2/SHA256 应用私钥 -----BEGIN RSA PRIVATE KEY----- ... 留空不修改" />
              <div class="tip">若已设置，留空则不修改原有密钥</div>
            </el-form-item>
            <el-form-item label="支付宝公钥">
              <el-input v-model="form.alipay_public_key" type="textarea" :rows="4" placeholder="支付宝公钥 -----BEGIN PUBLIC KEY----- ... 留空不修改" />
              <div class="tip">若已设置，留空则不修改</div>
            </el-form-item>
          </el-form>
        </el-tab-pane>
      </el-tabs>

      <!-- 通用设置 -->
      <el-divider content-position="left">通用设置</el-divider>
      <el-form :model="form" label-width="100px">
        <el-form-item label="支付渠道">
          <el-radio-group v-model="form.pay_channel">
            <el-radio value="wechat">微信支付</el-radio>
            <el-radio value="alipay">支付宝</el-radio>
            <el-radio value="both">微信 + 支付宝</el-radio>
            <el-radio value="">暂不启用</el-radio>
          </el-radio-group>
        </el-form-item>
        <el-form-item label="回调地址">
          <el-input v-model="form.notify_url" placeholder="支付异步通知地址，如 https://your-domain.com/api/pay/notify" clearable />
          <div class="tip">支付平台回调通知地址，用于接收支付结果</div>
        </el-form-item>
      </el-form>

      <!-- 底部操作 -->
      <div class="drawer-footer">
        <el-button @click="handleTest('wechat')" :disabled="form.pay_channel!=='wechat'&&form.pay_channel!=='both'" :loading="testing === 'wechat'">
          测试微信接口
        </el-button>
        <el-button @click="handleTest('alipay')" :disabled="form.pay_channel!=='alipay'&&form.pay_channel!=='both'" :loading="testing === 'alipay'">
          测试支付宝接口
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
const testing = ref('')
const drawerVisible = ref(false)
const activeTab = ref('wechat')
const currentCommunity = ref<{id:number;name:string;code:string}>({id:0,name:'',code:''})

const query = reactive({ keyword: '', page: 1, limit: 15 })
const form = reactive<Record<string, any>>({
  community_id: 0,
  pay_channel: '',
  wechat_app_id: '', wechat_mch_id: '', wechat_api_key: '', wechat_api_v3_key: '', wechat_serial_no: '',
  alipay_app_id: '', alipay_merchant_id: '', alipay_private_key: '', alipay_public_key: '',
  notify_url: '',
})

function resetQuery() { query.keyword = ''; query.page = 1; loadData() }

async function loadData() {
  loading.value = true
  try {
    const r = await apiGet('/admin/payment/configList', { ...query })
    list.value = r.data?.list || r.data || []
    total.value = r.count || r.data?.total || list.value.length
  } catch { list.value = []; total.value = 0 }
  finally { loading.value = false }
}

function resetForm() {
  form.community_id = 0
  form.pay_channel = ''
  form.wechat_app_id = ''; form.wechat_mch_id = ''; form.wechat_api_key = ''; form.wechat_api_v3_key = ''; form.wechat_serial_no = ''
  form.alipay_app_id = ''; form.alipay_merchant_id = ''; form.alipay_private_key = ''; form.alipay_public_key = ''
  form.notify_url = ''
}

async function openConfig(row: any) {
  resetForm()
  currentCommunity.value = { id: row.id, name: row.name, code: row.code }
  try {
    const r = await apiGet('/admin/payment/configDetail', { community_id: row.id })
    const cfg = r.data?.config || null
    form.community_id = row.id
    if (cfg) {
      form.pay_channel = cfg.pay_channel || ''
      form.wechat_app_id = cfg.wechat_app_id || ''
      form.wechat_mch_id = cfg.wechat_mch_id || ''
      form.wechat_api_key = cfg.wechat_api_key || ''       // 已脱敏，留空时不更新
      form.wechat_api_v3_key = cfg.wechat_api_v3_key || ''
      form.wechat_serial_no = cfg.wechat_serial_no || ''
      form.alipay_app_id = cfg.alipay_app_id || ''
      form.alipay_merchant_id = cfg.alipay_merchant_id || ''
      form.alipay_private_key = cfg.alipay_private_key || ''
      form.alipay_public_key = cfg.alipay_public_key || ''
      form.notify_url = cfg.notify_url || ''
    }
  } catch {}
  drawerVisible.value = true
  activeTab.value = form.pay_channel === 'alipay' ? 'alipay' : 'wechat'
}

async function handleSave() {
  if (!form.community_id) return ElMessage.warning('请先选择小区')
  if (!form.pay_channel) return ElMessage.warning('请选择支付渠道')

  // 按渠道校验必填
  const isWx = form.pay_channel === 'wechat' || form.pay_channel === 'both'
  const isAli = form.pay_channel === 'alipay' || form.pay_channel === 'both'

  if (isWx && !form.wechat_app_id) return ElMessage.warning('请填写微信AppID')
  if (isWx && !form.wechat_mch_id) return ElMessage.warning('请填写微信商户号')

  // 检查密钥：如果脱敏值含****且没填新的，就不报错（说明之前设置过）
  const wxKeyMasked = form.wechat_api_key.includes('****')
  if (isWx && !form.wechat_api_key) return ElMessage.warning('请填写微信API密钥')
  if (isWx && !form.wechat_api_v3_key) return ElMessage.warning('请填写微信APIv3密钥')

  if (isAli && !form.alipay_app_id) return ElMessage.warning('请填写支付宝AppID')
  if (isAli && !form.alipay_private_key && !form.alipay_private_key.includes('****')) {
    // 如果已有脱敏值，允许留空
    return ElMessage.warning('请填写支付宝应用私钥')
  }
  if (isAli && !form.alipay_public_key && !form.alipay_public_key.includes('****')) {
    return ElMessage.warning('请填写支付宝公钥')
  }

  saving.value = true
  try {
    await apiPost('/admin/payment/configSave', { ...form })
    ElMessage.success('保存成功')
    drawerVisible.value = false
    loadData()
  } finally { saving.value = false }
}

async function handleTest(channel: string) {
  if (!form.community_id) return ElMessage.warning('请先保存配置')
  testing.value = channel
  try {
    const r = await apiGet('/admin/payment/configTest', { community_id: form.community_id, channel })
    ElMessage.success({ message: r.msg, duration: 3000 })
  } finally { testing.value = '' }
}

/** 列表中的测试按钮 */
function testConfig(row: any) {
  ElMessageBox.confirm(`确定测试「${row.name}」的支付接口连通性吗？`, '测试支付配置', {
    confirmButtonText: '开始测试',
    type: 'info',
  }).then(async () => {
    try {
      const r = await apiGet('/admin/payment/configTest', { community_id: row.id, channel: row.pay_channel })
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
