<template>
  <div class="sms-page">
    <!-- 页面头部 -->
    <div class="page-header">
      <div class="header-left">
        <div class="header-icon"><el-icon :size="26"><ChatLineSquare /></el-icon></div>
        <div class="header-info">
          <h2>短信发送</h2>
          <p>批量或单条发送短信通知，支持选择模板与目标业主</p>
        </div>
      </div>
    </div>

    <!-- 发送表单 -->
    <el-card shadow="never" class="form-card">
      <!-- 步骤条 -->
      <el-steps :active="step" align-center finish-status="success" style="margin-bottom:32px">
        <el-step title="选择目标" />
        <el-step title="编辑内容" />
        <el-step title="确认发送" />
      </el-steps>

      <!-- 步骤1：选择目标 -->
      <div v-show="step === 0">
        <h3 class="step-title"><el-icon><User /></el-icon> 选择发送目标</h3>
        <el-form label-position="top">
          <el-form-item label="所属小区">
            <el-select v-model="sendForm.community_id" placeholder="选择小区（必选）" clearable style="width:100%" @change="onCommunityChange">
              <el-option v-for="c in communities" :key="c.id" :label="c.name" :value="c.id" />
            </el-select>
          </el-form-item>
          <el-form-item label="发送方式">
            <el-radio-group v-model="sendMode">
              <el-radio-button value="manual">手动输入</el-radio-button>
              <el-radio-button value="owners">选择业主</el-radio-button>
            </el-radio-group>
          </el-form-item>

          <!-- 手动输入 -->
          <div v-if="sendMode === 'manual'" class="phone-input-area">
            <el-form-item label="手机号码">
              <el-input v-model="phoneInput" type="textarea" :rows="4"
                placeholder="请输入手机号码，每行一个或用逗号分隔&#10;例如：&#10;13800138000&#10;13900139000&#10;13700137000" />
            </el-form-item>
            <el-alert v-if="parsedPhones.length > 0" type="success" :closable="false" show-icon style="margin-bottom:12px">
              <template #title>已识别 <b>{{ parsedPhones.length }}</b> 个有效手机号</template>
            </el-alert>
          </div>

          <!-- 选择业主 -->
          <div v-else-if="sendForm.community_id" v-loading="loadingOwners">
            <el-form-item label="搜索业主">
              <el-input v-model="ownerKeyword" placeholder="按姓名或房号搜索..." clearable prefix-icon="Search" style="max-width:400px" @input="filterOwners" />
            </el-form-item>
            <el-checkbox-group v-model="selectedOwnerIds" class="owner-checkbox-group">
              <el-checkbox v-for="o in filteredOwners" :key="o.id" :value="o.id" :label="o.id" border class="owner-checkbox">
                <div class="owner-item">
                  <el-icon><User /></el-icon>
                  <span class="owner-name">{{ o.name || '未命名' }}</span>
                  <span class="owner-phone">{{ o.phone }}</span>
                  <span class="owner-room">{{ o.room_number || '-' }}</span>
                </div>
              </el-checkbox>
            </el-checkbox-group>
            <el-alert v-if="selectedOwnerIds.length > 0" type="success" :closable="false" show-icon style="margin-top:12px">
              <template #title>已选择 <b>{{ selectedOwnerIds.length }}</b> 位业主</template>
            </el-alert>
          </div>
        </el-form>

        <el-form-item style="margin-top:20px">
          <el-button type="primary" @click="step = 1" :disabled="!canProceedStep1">
            下一步 <el-icon><ArrowRight /></el-icon>
          </el-button>
        </el-form-item>
      </div>

      <!-- 步骤2：编辑内容 -->
      <div v-show="step === 1">
        <h3 class="step-title"><el-icon><EditPen /></el-icon> 编辑短信内容</h3>
        <el-form label-position="top">
          <el-form-item label="选择模板（可选）">
            <el-select v-model="sendForm.template_id" placeholder="不选则手动输入内容" clearable style="width:100%" @change="onTemplateChange">
              <el-option v-for="t in templates" :key="t.id" :label="t.name" :value="t.id">
                <span>{{ t.name }}</span>
                <span style="float:right;color:#a0aec0;font-size:12px">{{ typeLabel(t.type) }}</span>
              </el-option>
            </el-select>
          </el-form-item>
          <el-form-item v-if="selectedTemplate" class="template-params">
            <div class="param-fields">
              <template v-if="paramFields.length > 0">
                <div v-for="(p, i) in paramFields" :key="i" style="margin-bottom:8px">
                  <el-input v-model="paramValues[i]" :placeholder="p" size="small" style="max-width:300px">
                    <template #prepend>{{ p }}</template>
                  </el-input>
                </div>
              </template>
            </div>
          </el-form-item>
          <el-form-item label="短信内容">
            <el-input v-model="sendForm.content" type="textarea" :rows="4" placeholder="请输入短信内容。注意：短信最多70个字符为一条，超过按多条计费。" maxlength="500" show-word-limit />
          </el-form-item>
          <el-alert type="info" :closable="false" show-icon style="margin-bottom:16px">
            <template #default>
              <span>预计发送 <b>{{ totalRecipients }}</b> 条短信，预估字数 <b>{{ sendForm.content.length }}</b> 字</span>
            </template>
          </el-alert>

          <el-form-item>
            <el-button @click="step = 0"><el-icon><ArrowLeft /></el-icon> 上一步</el-button>
            <el-button type="primary" @click="step = 2" :disabled="!sendForm.content.trim()">
              下一步 <el-icon><ArrowRight /></el-icon>
            </el-button>
          </el-form-item>
        </el-form>
      </div>

      <!-- 步骤3：确认发送 -->
      <div v-show="step === 2">
        <h3 class="step-title"><el-icon><CircleCheck /></el-icon> 确认发送</h3>
        <el-descriptions :column="2" border size="large" class="confirm-desc">
          <el-descriptions-item label="目标小区">{{ communityName }}</el-descriptions-item>
          <el-descriptions-item label="接收人数">{{ totalRecipients }} 人</el-descriptions-item>
          <el-descriptions-item label="短信模板">{{ selectedTemplate?.name || '自定义内容' }}</el-descriptions-item>
          <el-descriptions-item label="预估条数">{{ smsCount }} 条</el-descriptions-item>
          <el-descriptions-item label="短信内容" :span="2">
            <div class="content-preview-box">{{ previewContent }}</div>
          </el-descriptions-item>
        </el-descriptions>

        <div class="confirm-actions">
          <el-button @click="step = 1"><el-icon><ArrowLeft /></el-icon> 返回修改</el-button>
          <el-button type="primary" size="large" :loading="sending" @click="doSend">
            <el-icon><Promotion /></el-icon> 确认发送
          </el-button>
        </div>
      </div>
    </el-card>

    <!-- 发送记录（最近5条） -->
    <el-card shadow="never" class="table-card" style="margin-top:16px">
      <template #header>
        <div class="card-header">
          <span><el-icon><Clock /></el-icon> 最近发送记录</span>
          <el-button text type="primary" @click="$router.push('/sms/log')">查看全部 →</el-button>
        </div>
      </template>
      <el-table :data="recentLogs" v-loading="logLoading" stripe border size="small" class="modern-table">
        <el-table-column prop="phone" label="手机号" width="130" />
        <el-table-column prop="content" label="内容" min-width="220" show-overflow-tooltip />
        <el-table-column label="状态" width="90" align="center">
          <template #default="{ row }">
            <el-tag :type="row.status == 1 ? 'success' : 'danger'" size="small">{{ row.status == 1 ? '成功' : '失败' }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="create_time" label="时间" width="160" />
      </el-table>
    </el-card>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { apiGet, apiPost } from '@/utils/request'
import { ChatLineSquare, User, EditPen, CircleCheck, Promotion, ArrowRight, ArrowLeft, Clock, Search } from '@element-plus/icons-vue'

const step = ref(0)
const sendMode = ref<'manual' | 'owners'>('manual')
const sending = ref(false)
const loadingOwners = ref(false)
const logLoading = ref(false)

const communities = ref<any[]>([])
const templates = ref<any[]>([])
const allOwners = ref<any[]>([])
const filteredOwners = ref<any[]>([])
const selectedOwnerIds = ref<number[]>([])
const ownerKeyword = ref('')
const recentLogs = ref<any[]>([])

const phoneInput = ref('')
const paramValues = ref<string[]>([])

const sendForm = reactive({
  community_id: null as number | null,
  template_id: null as number | null,
  content: '',
})

const parsedPhones = computed(() => {
  return phoneInput.value
    .split(/[\n,，;；]+/)
    .map(s => s.replace(/\s/g, ''))
    .filter(s => /^1[3-9]\d{9}$/.test(s))
})

const totalRecipients = computed(() => {
  if (sendMode.value === 'manual') return parsedPhones.value.length
  return selectedOwnerIds.value.length
})

const canProceedStep1 = computed(() => {
  if (!sendForm.community_id) return false
  if (sendMode.value === 'manual') return parsedPhones.value.length > 0
  return selectedOwnerIds.value.length > 0
})

const smsCount = computed(() => Math.ceil(sendForm.content.length / 67))

const communityName = computed(() => {
  const c = communities.value.find((c: any) => c.id === sendForm.community_id)
  return c?.name || '-'
})

const selectedTemplate = computed(() => {
  return templates.value.find((t: any) => t.id === sendForm.template_id) || null
})

const paramFields = computed(() => {
  if (!selectedTemplate.value?.params) return []
  return selectedTemplate.value.params.split(',').map((s: string) => s.trim()).filter(Boolean)
})

const previewContent = computed(() => {
  let c = sendForm.content
  if (paramValues.value.length && paramFields.value.length) {
    paramFields.value.forEach((p: any, i: number) => {
      c = c.replace(new RegExp(`\\{${p}\\}`, 'g'), paramValues.value[i] || `{${p}}`)
    })
  }
  return c
})

function typeLabel(v: string) { const m: Record<string, string> = { code: '验证码', notice: '通知', marketing: '营销' }; return m[v] || v }

async function loadCommunities() {
  try {
    const r = await apiGet('/admin/community/list', { limit: 999 })
    communities.value = r.data?.list || r.data || []
  } catch { communities.value = [] }
}

async function loadTemplates() {
  try {
    const r = await apiGet('/admin/sms/smsTemplateList', { page: 1, limit: 500, status: 1 })
    const tplList = r.data?.list || r.data || []
    templates.value = tplList.filter((t: any) => t.status == 1)
  } catch { templates.value = [] }
}

async function onCommunityChange() {
  selectedOwnerIds.value = []
  allOwners.value = []
  if (!sendForm.community_id) return
  loadingOwners.value = true
  try {
    const r = await apiGet('/admin/owner/list', { community_id: sendForm.community_id, page: 1, limit: 500 })
    allOwners.value = (r.data?.list || r.data || []).filter((o: any) => o.phone && /^1\d{10}$/.test(o.phone))
    filteredOwners.value = [...allOwners.value]
  } finally { loadingOwners.value = false }
}

function filterOwners() {
  const kw = ownerKeyword.value.toLowerCase()
  filteredOwners.value = allOwners.value.filter((o: any) =>
    !kw || (o.name || '').includes(kw) || (o.room_number || '').includes(kw) || (o.phone || '').includes(kw))
}

function onTemplateChange() {
  if (selectedTemplate.value) {
    sendForm.content = selectedTemplate.value.content || ''
    paramValues.value = []
  }
}

async function loadRecentLogs() {
  logLoading.value = true
  try {
    const r = await apiGet('/admin/sms/smsLogList', { page: 1, limit: 5 })
    recentLogs.value = r.data?.list || r.data || []
  } finally { logLoading.value = false }
}

async function doSend() {
  const phones: string[] = sendMode.value === 'manual'
    ? parsedPhones.value
    : filteredOwners.value.filter((o: any) => selectedOwnerIds.value.includes(o.id)).map((o: any) => o.phone)

  if (phones.length === 0) return ElMessage.warning('没有可发送的手机号')

  sending.value = true
  try {
    const res = await apiPost('/admin/sms/send', {
      community_id: sendForm.community_id,
      template_id: sendForm.template_id,
      phones,
      content: previewContent.value,
    })
    if (res.code === 0) {
      ElMessage.success(`发送成功！共发送 ${res.data?.count || phones.length} 条`)
      step.value = 0
      phoneInput.value = ''
      sendForm.content = ''
      sendForm.template_id = null
      selectedOwnerIds.value = []
      loadRecentLogs()
    }
  } finally { sending.value = false }
}

onMounted(() => { loadCommunities(); loadTemplates(); loadRecentLogs() })
</script>

<style scoped>
.sms-page { max-width: 1400px; margin: 0 auto; }
.page-header {
  display: flex; align-items: center; justify-content: space-between;
  background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
  border-radius: 12px; padding: 24px 28px; margin-bottom: 20px; color: #fff; flex-wrap: wrap; gap:16px;
}
.header-left { display: flex; align-items: center; gap: 16px; }
.header-icon { width: 48px; height: 48px; background: rgba(255,255,255,.2); border-radius: 12px; display: flex; align-items: center; justify-content: center; }
.header-info h2 { margin: 0 0 4px; font-size: 20px; font-weight: 700; }
.header-info p { margin: 0; font-size: 13px; opacity: .85; }
.form-card { border-radius: 10px; border: 1px solid #e2e8f0; overflow: hidden; }
.step-title { display: flex; align-items: center; gap: 8px; margin: 0 0 20px; font-size: 16px; color: #2d3748; }
.phone-input-area { margin-bottom: 12px; }
.owner-checkbox-group { display: flex; flex-wrap: wrap; gap: 8px; max-height: 300px; overflow-y: auto; }
.owner-checkbox { margin-right: 0 !important; width: 240px; }
.owner-item { display: flex; align-items: center; gap: 6px; font-size: 13px; }
.owner-name { font-weight: 600; }
.owner-phone { color: #409EFF; }
.owner-room { color: #a0aec0; margin-left: auto; }
.template-params { background: #f7fafc; padding: 12px; border-radius: 8px; }
.param-fields { margin-top: 4px; }
.confirm-desc { margin-bottom: 24px; }
.content-preview-box { background: #f7fafc; padding: 12px 16px; border-radius: 6px; font-size: 14px; white-space: pre-wrap; line-height: 1.6; color: #2d3748; }
.confirm-actions { display: flex; gap: 12px; justify-content: flex-end; margin-top: 24px; }
.table-card { border-radius: 10px; border: 1px solid #e2e8f0; overflow: hidden; }
.card-header { display: flex; align-items: center; justify-content: space-between; }
.modern-table :deep(th) { background: #f8fafc !important; font-weight: 600; color: #2d3748; }
</style>
