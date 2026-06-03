<template>
  <div class="profile-page">
    <h2 class="page-title">👤 个人中心</h2>

    <el-row :gutter="20">
      <!-- 左侧：头像卡片 -->
      <el-col :span="8">
        <el-card shadow="hover" class="avatar-card">
          <div class="avatar-section">
            <el-avatar :size="100" :src="userInfo.avatar || undefined" class="avatar-img">
              <span style="font-size:40px;">{{ (userInfo.nickname || 'A').charAt(0).toUpperCase() }}</span>
            </el-avatar>
            <h3 class="avatar-name">{{ userInfo.nickname || '管理员' }}</h3>
            <el-tag type="primary" effect="plain" size="small">{{ userInfo.role || '超级管理员' }}</el-tag>
          </div>
          <el-divider />
          <div class="info-list">
            <div class="info-item">
              <el-icon><User /></el-icon>
              <span class="info-label">用户名</span>
              <span class="info-val">{{ userInfo.username }}</span>
            </div>
            <div class="info-item">
              <el-icon><Clock /></el-icon>
              <span class="info-label">最后登录</span>
              <span class="info-val">{{ userInfo.last_login_time || '-' }}</span>
            </div>
            <div class="info-item">
              <el-icon><Position /></el-icon>
              <span class="info-label">登录IP</span>
              <span class="info-val">{{ userInfo.last_login_ip || '-' }}</span>
            </div>
            <div class="info-item">
              <el-icon><TrendCharts /></el-icon>
              <span class="info-label">登录次数</span>
              <span class="info-val">{{ userInfo.login_count ?? 0 }} 次</span>
            </div>
            <div class="info-item">
              <el-icon><Calendar /></el-icon>
              <span class="info-label">注册时间</span>
              <span class="info-val">{{ userInfo.create_time || '-' }}</span>
            </div>
          </div>
        </el-card>
      </el-col>

      <!-- 右侧：编辑表单 -->
      <el-col :span="16">
        <el-card shadow="hover" class="form-card">
          <el-tabs v-model="activeTab" class="profile-tabs">
            <el-tab-pane label="修改资料" name="info">
              <el-form
                ref="infoFormRef"
                :model="infoForm"
                :rules="infoRules"
                label-width="80px"
                label-position="top"
                style="max-width:480px;"
              >
                <el-form-item label="头像">
                  <div class="avatar-upload">
                    <el-avatar :size="64" :src="infoForm.avatar || undefined">
                      <span style="font-size:28px;">{{ (userInfo.nickname || 'A').charAt(0).toUpperCase() }}</span>
                    </el-avatar>
                    <el-input v-model="infoForm.avatar" placeholder="输入头像URL" class="avatar-url" clearable />
                  </div>
                </el-form-item>
                <el-row :gutter="16">
                  <el-col :span="12">
                    <el-form-item label="昵称" prop="nickname">
                      <el-input v-model="infoForm.nickname" placeholder="请输入昵称" maxlength="20" show-word-limit />
                    </el-form-item>
                  </el-col>
                  <el-col :span="12">
                    <el-form-item label="手机号">
                      <el-input v-model="infoForm.phone" placeholder="请输入手机号" />
                    </el-form-item>
                  </el-col>
                </el-row>
                <el-form-item label="邮箱">
                  <el-input v-model="infoForm.email" placeholder="请输入邮箱" />
                </el-form-item>
                <el-form-item>
                  <el-button type="primary" :loading="infoLoading" @click="submitInfo">保存修改</el-button>
                  <el-button @click="resetInfo">重置</el-button>
                </el-form-item>
              </el-form>
            </el-tab-pane>

            <el-tab-pane label="修改密码" name="pwd">
              <el-form
                ref="pwdFormRef"
                :model="pwdForm"
                :rules="pwdRules"
                label-width="100px"
                style="max-width:420px;"
              >
                <el-form-item label="原密码" prop="old_password">
                  <el-input v-model="pwdForm.old_password" type="password" placeholder="请输入原密码" show-password />
                </el-form-item>
                <el-form-item label="新密码" prop="new_password">
                  <el-input v-model="pwdForm.new_password" type="password" placeholder="请输入新密码" show-password />
                </el-form-item>
                <el-form-item label="确认新密码" prop="confirm_password">
                  <el-input v-model="pwdForm.confirm_password" type="password" placeholder="请再次输入新密码" show-password />
                </el-form-item>
                <el-form-item>
                  <el-button type="primary" :loading="pwdLoading" @click="submitPassword">修改密码</el-button>
                </el-form-item>
              </el-form>
            </el-tab-pane>
          </el-tabs>
        </el-card>
      </el-col>
    </el-row>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { apiGet, apiPost } from '@/utils/request'
import { ElMessage, type FormInstance, type FormRules } from 'element-plus'
import { useUserStore } from '@/stores/user'

const userStore = useUserStore()
const activeTab = ref('info')
const infoFormRef = ref<FormInstance>()
const pwdFormRef = ref<FormInstance>()
const infoLoading = ref(false)
const pwdLoading = ref(false)

interface ProfileInfo {
  id: number; username: string; nickname: string; avatar: string; email: string; phone: string
  role_id: number; role: string; last_login_time: string; last_login_ip: string; login_count: number; create_time: string
}
const userInfo = ref<Partial<ProfileInfo>>({
  nickname: userStore.userInfo?.nickname || '管理员',
  avatar: userStore.userInfo?.avatar || '',
  role: userStore.userInfo?.role || '超级管理员',
  username: userStore.userInfo?.username || '',
})

const infoForm = reactive({ nickname: '', email: '', phone: '', avatar: '' })
const infoRules: FormRules = {
  nickname: [{ required: true, message: '请输入昵称', trigger: 'blur' }],
}

const pwdForm = reactive({ old_password: '', new_password: '', confirm_password: '' })
const validateConfirmPwd = (_rule: any, value: string, cb: any) => {
  if (value !== pwdForm.new_password) cb(new Error('两次密码输入不一致'))
  else cb()
}
const pwdRules: FormRules = {
  old_password: [{ required: true, message: '请输入原密码', trigger: 'blur' }],
  new_password: [
    { required: true, message: '请输入新密码', trigger: 'blur' },
    { min: 6, message: '密码长度至少6位', trigger: 'blur' },
  ],
  confirm_password: [
    { required: true, message: '请确认新密码', trigger: 'blur' },
    { validator: validateConfirmPwd, trigger: 'blur' },
  ],
}

async function loadInfo() {
  try {
    const res = await apiGet<ProfileInfo>('/admin/profile/info')
    if (res.code === 0) {
      userInfo.value = res.data
      infoForm.nickname = res.data.nickname || ''
      infoForm.email = res.data.email || ''
      infoForm.phone = res.data.phone || ''
      infoForm.avatar = res.data.avatar || ''
    }
  } catch { /* ignore */ }
}

function resetInfo() {
  infoForm.nickname = userInfo.value.nickname || ''
  infoForm.email = userInfo.value.email || ''
  infoForm.phone = userInfo.value.phone || ''
  infoForm.avatar = userInfo.value.avatar || ''
}

async function submitInfo() {
  if (!infoFormRef.value) return
  await infoFormRef.value.validate(async (valid) => {
    if (!valid) return
    infoLoading.value = true
    try {
      const res = await apiPost('/admin/profile/edit', { ...infoForm })
      if (res.code === 0) {
        ElMessage.success(res.msg || '修改成功')
        userStore.fetchInfo()
        loadInfo()
      }
    } finally { infoLoading.value = false }
  })
}

async function submitPassword() {
  if (!pwdFormRef.value) return
  await pwdFormRef.value.validate(async (valid) => {
    if (!valid) return
    pwdLoading.value = true
    try {
      const res = await apiPost('/admin/profile/password', {
        old_password: pwdForm.old_password,
        new_password: pwdForm.new_password,
      })
      if (res.code === 0) {
        ElMessage.success(res.msg || '密码修改成功，请重新登录')
        pwdForm.old_password = ''
        pwdForm.new_password = ''
        pwdForm.confirm_password = ''
      }
    } finally { pwdLoading.value = false }
  })
}

onMounted(loadInfo)
</script>

<style scoped>
.profile-page { animation: fadeIn 0.3s ease; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
.page-title { margin-bottom: 20px; font-size: 22px; font-weight: 700; color: #1a202c; }

.avatar-card { border-radius: 12px; border: 1px solid #e2e8f0; }
.avatar-section { display: flex; flex-direction: column; align-items: center; padding: 16px 0 8px; }
.avatar-img { box-shadow: 0 4px 12px rgba(0,0,0,0.1); margin-bottom: 12px; }
.avatar-name { font-size: 18px; font-weight: 700; color: #1a202c; margin: 0 0 8px 0; }

.info-list { padding: 0 8px; }
.info-item { display: flex; align-items: center; padding: 10px 0; border-bottom: 1px solid #f7f8fc; font-size: 13px; }
.info-item:last-child { border-bottom: none; }
.info-item .el-icon { color: #a0aec0; margin-right: 8px; font-size: 16px; flex-shrink: 0; }
.info-label { color: #a0aec0; min-width: 60px; }
.info-val { color: #2d3748; margin-left: auto; font-weight: 500; text-align: right; word-break: break-all; }

.form-card { border-radius: 12px; border: 1px solid #e2e8f0; min-height: 460px; }
.profile-tabs { --el-tabs-header-height: 44px; }

.avatar-upload { display: flex; align-items: center; gap: 16px; }
.avatar-url { flex: 1; }
</style>
