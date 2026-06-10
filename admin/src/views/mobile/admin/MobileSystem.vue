<template>
  <div class="ms-page">
    <!-- 顶部可滚动Tabs -->
    <div class="ms-tabs-scroll">
      <div class="ms-tabs">
        <div v-for="t in tabs" :key="t.key" class="mst-item" :class="{ active: tab === t.key }" @click="switchTab(t.key)">
          <Icon :icon="t.icon" /> {{ t.label }}
        </div>
      </div>
    </div>

    <!-- ==================== Tab 1: 用户管理 ==================== -->
    <template v-if="tab === 'user'">
      <div class="ms-search">
        <Icon icon="ph:magnifying-glass" class="mss-icon" />
        <input v-model="uq.keyword" placeholder="用户名/昵称/手机..." @keyup.enter="loadUsers" />
      </div>
      <div class="ms-filter-row">
        <select v-model="uq.role_id" @change="loadUsers"><option value="">全部角色</option><option v-for="r in roles" :key="r.id" :value="r.id">{{ r.name }}</option></select>
        <select v-model="uq.status" @change="loadUsers"><option value="">全部状态</option><option :value="1">正常</option><option :value="0">禁用</option></select>
        <button class="ms-fab-mini" @click="openUserForm()"><Icon icon="ph:plus" /></button>
      </div>
      <div class="ms-list">
        <div class="ms-card" v-for="row in userList" :key="row.id" @click="openUserForm(row)" v-vo-click>
          <div class="msc-top">
            <div class="msc-icon" :style="{ background: row.status===1 ? '#05966914' : '#dc262614', color: row.status===1 ? '#059669' : '#dc2626' }">
              <Icon icon="ph:user-circle-duotone" />
            </div>
            <div class="msc-info">
              <span class="msc-name">{{ row.nickname || row.username }} <i v-if="row.id===1" style="color:#f59e0b;font-size:10px;">●超级管理员</i></span>
              <span class="msc-sub">{{ row.username }} · {{ row.role_name || '—' }}</span>
            </div>
            <Icon icon="ph:caret-right" class="msc-arrow" />
          </div>
          <div class="msc-meta">
            <span><Icon icon="ph:device-mobile" /> {{ row.phone || '—' }}</span>
            <span :style="{ color: row.status===1 ? '#059669' : '#dc2626' }">● {{ row.status===1 ? '正常' : '禁用' }}</span>
            <span v-if="row.openid" style="color:#059669"><Icon icon="ph:wechat-logo" /> 微信</span>
          </div>
        </div>
      </div>
      <!-- 用户底部弹窗 -->
      <Teleport to="body"><div class="ms-overlay" v-if="userSheet" @click.self="userSheet=null"><div class="ms-sheet">
        <div class="ms-sh-top"><span>{{ userForm.id ? '编辑管理员' : '添加管理员' }}</span><Icon icon="ph:x" class="ms-sh-close" @click="userSheet=null" /></div>
        <div class="ms-sh-body">
          <label>用户名</label><input v-model="userForm.username" placeholder="登录用户名" :disabled="!!userForm.id" />
          <label>昵称</label><input v-model="userForm.nickname" placeholder="显示昵称" />
          <label>手机号</label><input v-model="userForm.phone" placeholder="手机号" maxlength="11" />
          <label>角色</label><select v-model="userForm.role_id"><option value="">选择角色</option><option v-for="r in roles" :key="r.id" :value="r.id">{{ r.name }}</option></select>
          <label v-if="!userForm.id">密码</label><input v-if="!userForm.id" v-model="userForm.password" placeholder="密码" type="password" />
          <label>状态</label>
          <div class="ms-sh-radio"><span :class="{ on: userForm.status===1 }" @click="userForm.status=1">正常</span><span :class="{ on: userForm.status===0 }" @click="userForm.status=0">禁用</span></div>
        </div>
        <div class="ms-sh-actions">
          <button v-if="userForm.id && userForm.id!==1" class="ms-btn-danger" @click="toggleUserStatus(userForm)">{{ userForm.status===1 ? '禁用' : '启用' }}</button>
          <button v-if="userForm.id && userForm.openid" class="ms-btn-warn" @click="unbindWechat(userForm)">解绑微信</button>
          <button v-if="userForm.id" class="ms-btn-outline" @click="changeUserPwd(userForm)">改密</button>
          <button class="ms-btn-primary" @click="submitUser" :disabled="userSubmitting">{{ userSubmitting ? '保存中...' : '保存' }}</button>
        </div>
      </div></div></Teleport>
    </template>

    <!-- ==================== Tab 2: 角色管理 ==================== -->
    <template v-if="tab === 'role'">
      <div class="ms-search">
        <Icon icon="ph:magnifying-glass" class="mss-icon" />
        <input v-model="rq.keyword" placeholder="搜索角色名称/编码..." @keyup.enter="loadRoles" />
      </div>
      <div class="ms-filter-row">
        <span class="msf-count">共 {{ roleTotal }} 个角色</span>
        <button class="ms-fab-mini" @click="openRoleForm()"><Icon icon="ph:plus" /></button>
      </div>
      <div class="ms-list">
        <div class="ms-card" v-for="row in roleList" :key="row.id" @click="openRoleForm(row)" v-vo-click>
          <div class="msc-top">
            <div class="msc-icon" :style="{ background: row.status===1 ? '#6366f114' : '#dc262614', color: row.status===1 ? '#6366f1' : '#dc2626' }">
              <Icon icon="ph:user-gear-duotone" />
            </div>
            <div class="msc-info">
              <span class="msc-name">{{ row.name }} <code style="font-size:10px;color:#94a3b8">{{ row.code }}</code></span>
              <span class="msc-sub">{{ row.description || '—' }}</span>
            </div>
            <Icon icon="ph:caret-right" class="msc-arrow" />
          </div>
          <div class="msc-meta">
            <span :style="{ color: row.status===1 ? '#059669' : '#dc2626' }">● {{ row.status===1 ? '正常' : '禁用' }}</span>
          </div>
        </div>
      </div>
      <!-- 角色弹窗 -->
      <Teleport to="body"><div class="ms-overlay" v-if="roleSheet" @click.self="roleSheet=null"><div class="ms-sheet">
        <div class="ms-sh-top"><span>{{ roleForm.id ? '编辑角色' : '添加角色' }}</span><Icon icon="ph:x" class="ms-sh-close" @click="roleSheet=null" /></div>
        <div class="ms-sh-body">
          <label>角色名称</label><input v-model="roleForm.name" placeholder="角色名称" />
          <label>编码</label><input v-model="roleForm.code" placeholder="角色编码" />
          <label>描述</label><textarea v-model="roleForm.description" rows="2" placeholder="角色描述"></textarea>
          <label>状态</label>
          <div class="ms-sh-radio"><span :class="{ on: roleForm.status===1 }" @click="roleForm.status=1">正常</span><span :class="{ on: roleForm.status===0 }" @click="roleForm.status=0">禁用</span></div>
        </div>
        <div class="ms-sh-actions">
          <button v-if="roleForm.id" class="ms-btn-danger" @click="deleteRole(roleForm)">删除</button>
          <button v-if="roleForm.id" class="ms-btn-outline" @click="openPermDialog(roleForm)">权限设置</button>
          <button class="ms-btn-primary" @click="submitRole" :disabled="roleSubmitting">保存</button>
        </div>
      </div></div></Teleport>
      <!-- 权限弹窗 -->
      <Teleport to="body"><div class="ms-overlay" v-if="permDialog" @click.self="permDialog=null"><div class="ms-sheet">
        <div class="ms-sh-top"><span>权限设置 - {{ permRoleName }}</span><Icon icon="ph:x" class="ms-sh-close" @click="permDialog=null" /></div>
        <div class="ms-sh-body" style="max-height:50vh;overflow-y:auto">
          <div v-for="m in permMenus" :key="m.id" class="ms-perm-item" :style="{ paddingLeft: (m._level||0)*16 + 12 + 'px' }">
            <label class="ms-perm-label">
              <input type="checkbox" :value="m.id" v-model="permChecked" @change="onPermChange(m)" />
              <span>{{ m.name }}</span>
            </label>
            <div v-if="m.children?.length">
              <div v-for="c in m.children" :key="c.id" class="ms-perm-item" style="padding-left:12px">
                <label class="ms-perm-label">
                  <input type="checkbox" :value="c.id" v-model="permChecked" @change="onPermChange(c)" />
                  <span>{{ c.name }}</span>
                </label>
              </div>
            </div>
          </div>
        </div>
        <div class="ms-sh-actions">
          <button class="ms-btn-primary" @click="submitPerm" :disabled="permSubmitting">保存权限</button>
        </div>
      </div></div></Teleport>
    </template>

    <!-- ==================== Tab 3: 菜单管理 ==================== -->
    <template v-if="tab === 'menu'">
      <div class="ms-search">
        <Icon icon="ph:magnifying-glass" class="mss-icon" />
        <input v-model="mq.keyword" placeholder="搜索菜单名称..." @keyup.enter="loadMenus" />
      </div>
      <div class="ms-filter-row">
        <button class="ms-fab-mini" @click="openMenuForm()"><Icon icon="ph:plus" /></button>
      </div>
      <div class="ms-list">
        <div class="ms-card" v-for="row in flatMenuList" :key="row.id" @click="openMenuForm(row)" v-vo-click :style="{ marginLeft: (row._level||0)*12 + 'px', borderLeft: '3px solid ' + ['#6366f1','#0891b2','#059669','#ea580c'][Math.min(row._level||0,3)] }">
          <div class="msc-top">
            <div class="msc-info">
              <span class="msc-name">{{ row.name }} <code style="font-size:10px;color:#94a3b8">{{ row.route || '—' }}</code></span>
              <span class="msc-sub">{{ row.permission || '—' }} · 排序 {{ row.sort }}</span>
            </div>
            <span class="msc-badge" :style="{ background: row.status===1 ? '#05966914' : '#dc262614', color: row.status===1 ? '#059669' : '#dc2626' }">{{ row.status===1 ? '正常' : '禁用' }}</span>
          </div>
        </div>
      </div>
      <!-- 菜单弹窗 -->
      <Teleport to="body"><div class="ms-overlay" v-if="menuSheet" @click.self="menuSheet=null"><div class="ms-sheet">
        <div class="ms-sh-top"><span>{{ menuForm.id ? '编辑菜单' : '添加菜单' }}</span><Icon icon="ph:x" class="ms-sh-close" @click="menuSheet=null" /></div>
        <div class="ms-sh-body">
          <label>上级菜单</label><select v-model="menuForm.parent_id"><option :value="undefined">顶级菜单</option><option v-for="m in flatMenuList" :key="m.id" :value="m.id">{{ m.name }}</option></select>
          <label>菜单名称</label><input v-model="menuForm.name" placeholder="菜单名称" />
          <label>路由</label><input v-model="menuForm.route" placeholder="/system/admin" />
          <label>权限标识</label><input v-model="menuForm.permission" placeholder="system:admin" />
          <label>图标</label><input v-model="menuForm.icon" placeholder="图标类名" />
          <label>排序</label><input v-model.number="menuForm.sort" type="number" min="0" />
          <label>状态</label>
          <div class="ms-sh-radio"><span :class="{ on: menuForm.status===1 }" @click="menuForm.status=1">正常</span><span :class="{ on: menuForm.status===0 }" @click="menuForm.status=0">禁用</span></div>
        </div>
        <div class="ms-sh-actions">
          <button v-if="menuForm.id" class="ms-btn-danger" @click="deleteMenu(menuForm)">删除</button>
          <button class="ms-btn-primary" @click="submitMenu" :disabled="menuSubmitting">保存</button>
        </div>
      </div></div></Teleport>
    </template>

    <!-- ==================== Tab 4: 系统配置 ==================== -->
    <template v-if="tab === 'config'">
      <div class="ms-search" style="margin-bottom:12px">
        <Icon icon="ph:sliders" class="mss-icon" />
        <select v-model="cq.group" @change="loadConfig" style="flex:1;border:none;background:transparent;font-size:14px;outline:none">
          <option value="system">系统配置</option>
          <option value="charge">收费配置</option>
          <option value="parking">停车配置</option>
        </select>
      </div>
      <div class="ms-config-form">
        <div v-for="item in configList" :key="item.key" class="mscf-item">
          <label>{{ item.name }}</label>
          <input v-if="item.type==='input' || !item.type" v-model="configForm[item.key]" />
          <textarea v-else-if="item.type==='textarea'" v-model="configForm[item.key]" rows="3"></textarea>
          <div v-else-if="item.type==='switch'" class="ms-sh-radio">
            <span :class="{ on: configForm[item.key]==='1' }" @click="configForm[item.key]='1'">开</span>
            <span :class="{ on: configForm[item.key]==='0' }" @click="configForm[item.key]='0'">关</span>
          </div>
          <input v-else-if="item.type==='number'" v-model.number="configForm[item.key]" type="number" />
          <input v-else v-model="configForm[item.key]" />
          <small v-if="item.remark" style="color:#94a3b8">{{ item.remark }}</small>
        </div>
        <button class="ms-btn-primary" style="width:100%;margin-top:12px" @click="saveConfig" :disabled="configSubmitting">{{ configSubmitting ? '保存中...' : '保存配置' }}</button>
      </div>
    </template>

    <!-- ==================== Tab 5: 推送管理 ==================== -->
    <template v-if="tab === 'push'">
      <div class="ms-search">
        <Icon icon="ph:magnifying-glass" class="mss-icon" />
        <input v-model="pq.keyword" placeholder="搜索小区名称..." @keyup.enter="loadPushConfig" />
      </div>
      <div class="ms-stats-row">
        <div class="ms-stat"><b>{{ pushList.length }}</b> 小区</div>
        <div class="ms-stat"><b style="color:#059669">{{ pushSmsOn }}</b> 已配短信</div>
        <div class="ms-stat"><b style="color:#6366f1">{{ pushSseOn }}</b> SSE开启</div>
      </div>
      <div class="ms-list">
        <div class="ms-card" v-for="row in pushList" :key="row.id" @click="openPushDetail(row)" v-vo-click>
          <div class="msc-top">
            <div class="msc-icon" style="background:#6366f114;color:#6366f1"><Icon icon="ph:buildings-duotone" /></div>
            <div class="msc-info">
              <span class="msc-name">{{ row.name }}</span>
              <span class="msc-sub">{{ row.code }}</span>
            </div>
            <Icon icon="ph:caret-right" class="msc-arrow" />
          </div>
          <div class="msc-meta">
            <span :style="{ color: row.sse===1 ? '#6366f1' : '#94a3b8' }">{{ row.sse===1 ? '● SSE' : '○ SSE' }}</span>
            <span :style="{ color: row.wechat===1 ? '#059669' : '#94a3b8' }">{{ row.wechat===1 ? '● 微信' : '○ 微信' }}</span>
            <span :style="{ color: row.sms===1 ? '#ea580c' : '#94a3b8' }">{{ row.sms===1 ? '● 短信' : '○ 短信' }}</span>
            <span v-if="row.sms_status===1" style="color:#059669">{{ row.sms_provider==='tencent'?'腾讯云':'阿里云' }}</span>
          </div>
        </div>
      </div>
      <!-- 推送配置详情弹窗 -->
      <Teleport to="body"><div class="ms-overlay" v-if="pushDetail" @click.self="pushDetail=null"><div class="ms-sheet">
        <div class="ms-sh-top"><span>推送配置 - {{ pushDetail.name }}</span><Icon icon="ph:x" class="ms-sh-close" @click="pushDetail=null" /></div>
        <div class="ms-sh-body">
          <h4 style="margin:0 0 8px;color:#6366f1">推送渠道</h4>
          <div class="ms-sh-radio-row"><span>SSE实时推送</span><span :class="{ on: pf.sse_enable===1 }" @click="pf.sse_enable=pf.sse_enable===1?0:1">{{ pf.sse_enable===1 ? '开' : '关' }}</span></div>
          <div class="ms-sh-radio-row"><span>微信模板消息</span><span :class="{ on: pf.wechat_enable===1 }" @click="pf.wechat_enable=pf.wechat_enable===1?0:1">{{ pf.wechat_enable===1 ? '开' : '关' }}</span></div>
          <div class="ms-sh-radio-row"><span>短信通知</span><span :class="{ on: pf.sms_enable===1 }" @click="pf.sms_enable=pf.sms_enable===1?0:1">{{ pf.sms_enable===1 ? '开' : '关' }}</span></div>
          <h4 style="margin:12px 0 8px;color:#6366f1">事件类型</h4>
          <div class="ms-sh-radio-row"><span>新报修通知</span><span :class="{ on: pf.repair_new_enable===1 }" @click="pf.repair_new_enable=pf.repair_new_enable===1?0:1">{{ pf.repair_new_enable===1 ? '开' : '关' }}</span></div>
          <div class="ms-sh-radio-row"><span>派单通知</span><span :class="{ on: pf.repair_assign_enable===1 }" @click="pf.repair_assign_enable=pf.repair_assign_enable===1?0:1">{{ pf.repair_assign_enable===1 ? '开' : '关' }}</span></div>
          <div class="ms-sh-radio-row"><span>催缴通知</span><span :class="{ on: pf.dunning_enable===1 }" @click="pf.dunning_enable=pf.dunning_enable===1?0:1">{{ pf.dunning_enable===1 ? '开' : '关' }}</span></div>
        </div>
        <div class="ms-sh-actions">
          <button class="ms-btn-primary" @click="submitPushConfig" :disabled="pushSubmitting">保存推送配置</button>
        </div>
      </div></div></Teleport>
    </template>

    <!-- ==================== Tab 6: 服务商管理 ==================== -->
    <template v-if="tab === 'vendor'">
      <div class="ms-search">
        <Icon icon="ph:magnifying-glass" class="mss-icon" />
        <input v-model="vq.keyword" placeholder="公司名称/联系人/电话..." @keyup.enter="loadVendors" />
      </div>
      <div class="ms-chips">
        <span v-for="vt in vendorTypes" :key="vt.key" class="ms-chip" :class="{ on: vq.vendor_type===vt.key }" @click="vq.vendor_type = vq.vendor_type===vt.key ? '' : vt.key; loadVendors()">{{ vt.label }}</span>
      </div>
      <div class="ms-filter-row">
        <span class="msf-count">共 {{ vendorTotal }} 个</span>
        <button class="ms-fab-mini" @click="openVendorForm()"><Icon icon="ph:plus" /></button>
      </div>
      <div class="ms-list">
        <div class="ms-card" v-for="row in vendorList" :key="row.id" @click="openVendorForm(row)" v-vo-click>
          <div class="msc-top">
            <div class="msc-icon" :style="{ background: vtColor(row.vendor_type)+'14', color: vtColor(row.vendor_type) }"><Icon icon="ph:buildings-duotone" /></div>
            <div class="msc-info">
              <span class="msc-name">{{ row.company_name }}</span>
              <span class="msc-sub">{{ row.contact_person || '—' }} · {{ row.mobile || '—' }}</span>
            </div>
            <Icon icon="ph:caret-right" class="msc-arrow" />
          </div>
          <div class="msc-meta">
            <span class="msc-badge" style="background:#6366f114;color:#6366f1">{{ vtLabel(row.vendor_type) }}</span>
            <span :style="{ color: vendorStatus(row).color }">● {{ vendorStatus(row).text }}</span>
          </div>
        </div>
      </div>
      <!-- 服务商弹窗 -->
      <Teleport to="body"><div class="ms-overlay" v-if="vendorSheet" @click.self="vendorSheet=null"><div class="ms-sheet">
        <div class="ms-sh-top"><span>{{ vendorForm.id ? '编辑服务商' : '新增服务商' }}</span><Icon icon="ph:x" class="ms-sh-close" @click="vendorSheet=null" /></div>
        <div class="ms-sh-body">
          <label>公司名称</label><input v-model="vendorForm.company_name" placeholder="公司名称" />
          <label>服务类别</label><select v-model="vendorForm.vendor_type"><option v-for="vt in vendorTypes" :key="vt.key" :value="vt.key">{{ vt.label }}</option></select>
          <label>联系人</label><input v-model="vendorForm.contact_person" placeholder="姓名" />
          <label>手机</label><input v-model="vendorForm.mobile" placeholder="手机号" />
          <label>电话</label><input v-model="vendorForm.phone" placeholder="座机" />
          <label>邮箱</label><input v-model="vendorForm.email" placeholder="Email" />
          <label>服务范围</label><textarea v-model="vendorForm.service_scope" rows="2" placeholder="服务内容描述"></textarea>
          <label>合同截止</label><input v-model="vendorForm.contract_end" type="date" />
          <label>状态</label>
          <div class="ms-sh-radio"><span :class="{ on: vendorForm.status===1 }" @click="vendorForm.status=1">合作中</span><span :class="{ on: vendorForm.status===0 }" @click="vendorForm.status=0">已到期</span><span :class="{ on: vendorForm.status===2 }" @click="vendorForm.status=2">已终止</span></div>
        </div>
        <div class="ms-sh-actions">
          <button v-if="vendorForm.id" class="ms-btn-danger" @click="deleteVendor(vendorForm)">删除</button>
          <button class="ms-btn-primary" @click="submitVendor" :disabled="vendorSubmitting">保存</button>
        </div>
      </div></div></Teleport>
    </template>

    <!-- ==================== Tab 7: 操作日志 ==================== -->
    <template v-if="tab === 'log'">
      <div class="ms-search">
        <Icon icon="ph:magnifying-glass" class="mss-icon" />
        <input v-model="lq.keyword" placeholder="操作人/模块/操作..." @keyup.enter="loadLogs" />
      </div>
      <div class="ms-list">
        <div class="ms-card" v-for="row in logList" :key="row.id">
          <div class="msc-top">
            <div class="msc-icon" style="background:#64748b14;color:#64748b"><Icon icon="ph:scroll-duotone" /></div>
            <div class="msc-info">
              <span class="msc-name">{{ row.admin_name || '—' }} · {{ row.module }}</span>
              <span class="msc-sub">{{ row.action }} — {{ row.url }}</span>
            </div>
          </div>
          <div class="msc-meta">
            <span><Icon icon="ph:globe" /> {{ row.ip }}</span>
            <span><Icon icon="ph:git-branch" /> {{ row.method }}</span>
            <span><Icon icon="ph:timer" /> {{ row.duration }}ms</span>
            <span style="color:#94a3b8">{{ row.create_time }}</span>
          </div>
        </div>
      </div>
      <div class="ms-loadmore" v-if="logTotal > logList.length" @click="lq.page++;loadLogs()">加载更多...</div>
    </template>

    <!-- 分页 -->
    <div class="ms-pager" v-if="tab !== 'log' && tab !== 'config' && tab !== 'menu' && currentTotal > currentLimit">
      <button :disabled="currentPage<=1" @click="goPage(-1)">←</button>
      <span>{{ currentPage }} / {{ Math.ceil(currentTotal/currentLimit) }}</span>
      <button :disabled="currentPage >= Math.ceil(currentTotal/currentLimit)" @click="goPage(1)">→</button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { Icon } from '@iconify/vue'
import { apiGet, apiPost } from '@/utils/request'

// ===== Tabs =====
const tab = ref('user')
const tabs = [
  { key: 'user', label: '用户', icon: 'ph:user-duotone' },
  { key: 'role', label: '角色', icon: 'ph:user-gear-duotone' },
  { key: 'menu', label: '菜单', icon: 'ph:list-bullets-duotone' },
  { key: 'config', label: '配置', icon: 'ph:sliders-duotone' },
  { key: 'push', label: '推送', icon: 'ph:bell-ringing-duotone' },
  { key: 'vendor', label: '服务商', icon: 'ph:handshake-duotone' },
  { key: 'log', label: '日志', icon: 'ph:scroll-duotone' },
]
function switchTab(k: string) { tab.value = k; initTab(k) }

// ===== Common pager =====
const currentPage = ref(1)
const currentTotal = ref(0)
const currentLimit = ref(15)
function goPage(dir: number) { currentPage.value += dir; reloadTab() }
function reloadTab() { initTab(tab.value) }

// ===== Tab 1: 用户管理 =====
const userList = ref<any[]>([])
const userTotal = ref(0)
const roles = ref<any[]>([])
const userSheet = ref(false)
const userSubmitting = ref(false)
const uq = reactive({ keyword: '', role_id: '' as any, status: '' as any, page: 1, limit: 15 })
const userForm = reactive<any>({ id: 0, username: '', nickname: '', phone: '', role_id: '', password: '', status: 1, openid: '' })

async function loadUsers() {
  try {
    const r = await apiGet('/admin/AdminUser/lists', { ...uq })
    userList.value = r.data?.list || r.data || []
    userTotal.value = r.count || r.data?.total || userList.value.length
    currentPage.value = uq.page; currentTotal.value = userTotal.value; currentLimit.value = uq.limit
  } catch { userList.value = []; userTotal.value = 0 }
}
function openUserForm(row?: any) {
  if (row) Object.assign(userForm, { id: row.id, username: row.username, nickname: row.nickname, phone: row.phone || '', role_id: row.role_id, status: row.status, password: '', openid: row.openid || '' })
  else Object.assign(userForm, { id: 0, username: '', nickname: '', phone: '', role_id: '', password: '', status: 1, openid: '' })
  userSheet.value = true
}
async function submitUser() {
  if (!userForm.username || !userForm.nickname || !userForm.role_id) return alert('请填写必填项')
  if (!userForm.id && !userForm.password) return alert('请输入密码')
  userSubmitting.value = true
  try {
    const url = userForm.id ? '/admin/user/edit' : '/admin/user/add'
    const p: any = { ...userForm }
    if (userForm.id && !p.password) delete p.password
    await apiPost(url, p)
    userSheet.value = false; loadUsers()
  } finally { userSubmitting.value = false }
}
async function toggleUserStatus(row: any) {
  const s = row.status === 1 ? 0 : 1
  await apiPost('/admin/AdminUser/status', { id: row.id, status: s })
  loadUsers(); userSheet.value = false
}
async function unbindWechat(row: any) {
  if (!confirm('确定解绑该用户的微信吗？')) return
  await apiPost('/admin/AdminUser/unbindWechat', { id: row.id })
  loadUsers(); userSheet.value = false
}
async function changeUserPwd(row: any) {
  const pwd = prompt('请输入新密码（至少6位）')
  if (!pwd || pwd.length < 6) return alert('密码至少6位')
  await apiPost('/admin/AdminUser/changePassword', { id: row.id, password: pwd })
  alert('密码修改成功')
}

// ===== Tab 2: 角色管理 =====
const roleList = ref<any[]>([])
const roleTotal = ref(0)
const roleSheet = ref(false)
const roleSubmitting = ref(false)
const rq = reactive({ keyword: '', page: 1, limit: 15 })
const roleForm = reactive<any>({ id: 0, name: '', code: '', description: '', status: 1 })

// 权限弹窗
const permDialog = ref(false)
const permRoleName = ref('')
const permRoleId = ref(0)
const permMenus = ref<any[]>([])
const permChecked = ref<number[]>([])
const permSubmitting = ref(false)

async function loadRoles() {
  try {
    const r = await apiGet('/admin/role/list', { ...rq })
    roleList.value = r.data?.list || r.data || []
    roleTotal.value = r.count || r.data?.total || roleList.value.length
    currentPage.value = rq.page; currentTotal.value = roleTotal.value; currentLimit.value = rq.limit
  } catch { roleList.value = []; roleTotal.value = 0 }
}
function openRoleForm(row?: any) {
  Object.assign(roleForm, row || { id: 0, name: '', code: '', description: '', status: 1 })
  roleSheet.value = true
}
async function submitRole() {
  if (!roleForm.name || !roleForm.code) return alert('请填写角色名称和编码')
  roleSubmitting.value = true
  try {
    const url = roleForm.id ? '/admin/role/edit' : '/admin/role/add'
    await apiPost(url, { ...roleForm })
    roleSheet.value = false; loadRoles()
  } finally { roleSubmitting.value = false }
}
async function deleteRole(row: any) {
  if (!confirm(`确定删除角色"${row.name}"吗？`)) return
  await apiPost('/admin/role/delete', { id: row.id })
  roleSheet.value = false; loadRoles()
}
async function openPermDialog(row: any) {
  permRoleId.value = row.id; permRoleName.value = row.name
  try {
    const r = await apiGet('/admin/role/permission', { role_id: row.id })
    permMenus.value = r.data?.menus || []
    permChecked.value = r.data?.checkedMenuIds || []
    permDialog.value = true
  } catch {}
}
function onPermChange(_m: any) { /* handled by v-model */ }
async function submitPerm() {
  permSubmitting.value = true
  try {
    await apiPost('/admin/role/savePermission', { role_id: permRoleId.value, menu_ids: [...permChecked.value] })
    permDialog.value = false; alert('权限保存成功')
  } finally { permSubmitting.value = false }
}

// ===== Tab 3: 菜单管理 =====
const menuList = ref<any[]>([])
const menuSheet = ref(false)
const menuSubmitting = ref(false)
const mq = reactive({ keyword: '' })
const menuForm = reactive<any>({ id: 0, parent_id: undefined, name: '', route: '', permission: '', icon: '', sort: 0, status: 1 })

function flattenMenus(arr: any[], level = 0): any[] {
  return arr.reduce((acc: any[], cur: any) => {
    acc.push({ ...cur, _level: level })
    if (cur.children?.length) acc.push(...flattenMenus(cur.children, level + 1))
    return acc
  }, [])
}
const flatMenuList = computed(() => {
  let flat = flattenMenus(menuList.value)
  if (mq.keyword) flat = flat.filter(m => m.name.includes(mq.keyword))
  return flat
})
async function loadMenus() {
  try { const r = await apiGet('/admin/menu/list'); menuList.value = r.data || [] } catch { menuList.value = [] }
}
function openMenuForm(row?: any) {
  if (row) { const { children, ...rest } = row; Object.assign(menuForm, rest) }
  else Object.assign(menuForm, { id: 0, parent_id: undefined, name: '', route: '', permission: '', icon: '', sort: 0, status: 1 })
  menuSheet.value = true
}
async function submitMenu() {
  if (!menuForm.name) return alert('请输入菜单名称')
  menuSubmitting.value = true
  try {
    const url = menuForm.id ? '/admin/menu/edit' : '/admin/menu/add'
    await apiPost(url, { ...menuForm })
    menuSheet.value = false; loadMenus()
  } finally { menuSubmitting.value = false }
}
async function deleteMenu(row: any) {
  if (!confirm(`确定删除菜单"${row.name}"吗？`)) return
  await apiPost('/admin/menu/delete', { id: row.id })
  menuSheet.value = false; loadMenus()
}

// ===== Tab 4: 系统配置 =====
const configList = ref<any[]>([])
const configForm = reactive<Record<string, any>>({})
const configSubmitting = ref(false)
const cq = reactive({ group: 'system' })
async function loadConfig() {
  try {
    const r = await apiGet('/admin/config/list', { group: cq.group })
    configList.value = r.data || []
    configList.value.forEach((item: any) => { configForm[item.key] = item.value })
  } catch { configList.value = [] }
}
async function saveConfig() {
  configSubmitting.value = true
  try { await apiPost('/admin/config/save', { data: { ...configForm } }); alert('保存成功') }
  finally { configSubmitting.value = false }
}

// ===== Tab 5: 推送管理 =====
const pushList = ref<any[]>([])
const pushDetail = ref<any>(null)
const pushSubmitting = ref(false)
const pq = reactive({ keyword: '', page: 1, limit: 15 })
const pf = reactive({ sse_enable: 1, wechat_enable: 1, sms_enable: 0, app_push_enable: 0, repair_new_enable: 1, repair_assign_enable: 1, dunning_enable: 1, community_id: 0 })
const pushSmsOn = computed(() => pushList.value.filter(r => r.sms_status === 1).length)
const pushSseOn = computed(() => pushList.value.filter(r => r.sse === 1).length)

async function loadPushConfig() {
  try {
    const r = await apiGet('/admin/system/pushConfigList', { ...pq })
    pushList.value = r.data?.list || r.data || []
    currentTotal.value = r.count || r.data?.total || pushList.value.length
  } catch { pushList.value = [] }
}
async function openPushDetail(row: any) {
  pushDetail.value = row
  try {
    const r = await apiGet('/admin/system/pushConfigDetail', { community_id: row.id })
    const push = r.data?.push || null
    pf.community_id = row.id
    if (push) Object.assign(pf, {
      sse_enable: push.sse_enable ?? 1, wechat_enable: push.wechat_enable ?? 1,
      sms_enable: push.sms_enable ?? 0, app_push_enable: push.app_push_enable ?? 0,
      repair_new_enable: push.repair_new_enable ?? 1, repair_assign_enable: push.repair_assign_enable ?? 1,
      dunning_enable: push.dunning_enable ?? 1,
    })
  } catch {}
}
async function submitPushConfig() {
  pushSubmitting.value = true
  try { await apiPost('/admin/system/pushConfigSavePush', { ...pf }); pushDetail.value = null; loadPushConfig() }
  finally { pushSubmitting.value = false }
}

// ===== Tab 6: 服务商 =====
const vendorList = ref<any[]>([])
const vendorTotal = ref(0)
const vendorSheet = ref(false)
const vendorSubmitting = ref(false)
const vq = reactive({ keyword: '', vendor_type: '', page: 1, limit: 15 })
const vendorForm = reactive<any>({ id: 0, company_name: '', vendor_type: 'cleaning', contact_person: '', mobile: '', phone: '', email: '', service_scope: '', contract_end: '', status: 1 })

const vendorTypes = [
  { key: 'elevator', label: '电梯维保', color: '#6366f1' },
  { key: 'cleaning', label: '保洁', color: '#48bb78' },
  { key: 'security', label: '安保', color: '#1e40af' },
  { key: 'landscaping', label: '绿化', color: '#16a34a' },
  { key: 'fire', label: '消防', color: '#e53e3e' },
  { key: 'plumbing', label: '水电', color: '#0ea5e9' },
  { key: 'hvac', label: '暖通', color: '#8b5cf6' },
  { key: 'lowvoltage', label: '弱电', color: '#db2777' },
  { key: 'pest', label: '消杀', color: '#d97706' },
  { key: 'waste', label: '清运', color: '#78716c' },
  { key: 'parking', label: '停车', color: '#0891b2' },
  { key: 'other', label: '其他', color: '#a0aec0' },
]
function vtLabel(v: string) { return vendorTypes.find(t => t.key === v)?.label || v }
function vtColor(v: string) { return vendorTypes.find(t => t.key === v)?.color || '#a0aec0' }
function vendorStatus(row: any) {
  if (row.status == 2) return { text: '已终止', color: '#94a3b8' }
  if (row.status == 0) return { text: '已到期', color: '#dc2626' }
  if (row.contract_end) {
    const days = Math.ceil((new Date(row.contract_end).getTime() - Date.now()) / 86400000)
    if (days < 0) return { text: '已到期', color: '#dc2626' }
    if (days < 30) return { text: `${days}天到期`, color: '#ea580c' }
  }
  return { text: '合作中', color: '#059669' }
}
async function loadVendors() {
  try {
    const r = await apiGet('/admin/system/serviceVendorList', { ...vq })
    vendorList.value = r.data?.list || r.data || []
    vendorTotal.value = r.data?.total || r.count || vendorList.value.length
    currentPage.value = vq.page; currentTotal.value = vendorTotal.value; currentLimit.value = vq.limit
  } catch { vendorList.value = []; vendorTotal.value = 0 }
}
function openVendorForm(row?: any) {
  if (row) Object.assign(vendorForm, { id: row.id, company_name: row.company_name || '', vendor_type: row.vendor_type || 'cleaning', contact_person: row.contact_person || '', mobile: row.mobile || '', phone: row.phone || '', email: row.email || '', service_scope: row.service_scope || '', contract_end: row.contract_end || '', status: row.status ?? 1 })
  else Object.assign(vendorForm, { id: 0, company_name: '', vendor_type: 'cleaning', contact_person: '', mobile: '', phone: '', email: '', service_scope: '', contract_end: '', status: 1 })
  vendorSheet.value = true
}
async function submitVendor() {
  if (!vendorForm.company_name) return alert('请输入公司名称')
  vendorSubmitting.value = true
  try {
    const url = vendorForm.id ? '/admin/system/serviceVendorEdit' : '/admin/system/serviceVendorAdd'
    await apiPost(url, { ...vendorForm, id: vendorForm.id || undefined })
    vendorSheet.value = false; loadVendors()
  } finally { vendorSubmitting.value = false }
}
async function deleteVendor(row: any) {
  if (!confirm('确定删除该服务商吗？')) return
  await apiPost('/admin/system/serviceVendorDelete', { id: row.id })
  vendorSheet.value = false; loadVendors()
}

// ===== Tab 7: 操作日志 =====
const logList = ref<any[]>([])
const logTotal = ref(0)
const lq = reactive({ keyword: '', page: 1, limit: 15 })
async function loadLogs() {
  try {
    const r = await apiGet('/admin/log/list', { ...lq })
    logList.value = r.data?.list || r.data || []
    logTotal.value = r.count || r.data?.total || logList.value.length
    currentPage.value = lq.page; currentTotal.value = logTotal.value; currentLimit.value = lq.limit
  } catch { logList.value = []; logTotal.value = 0 }
}

// ===== Init =====
function initTab(k: string) {
  switch (k) {
    case 'user': loadUsers(); break
    case 'role': loadRoles(); break
    case 'menu': loadMenus(); break
    case 'config': loadConfig(); break
    case 'push': loadPushConfig(); break
    case 'vendor': loadVendors(); break
    case 'log': loadLogs(); break
  }
}
onMounted(async () => {
  try { const r = await apiGet('/admin/role/list'); roles.value = r.data || [] } catch {}
  loadUsers()
})
</script>

<style scoped>
.ms-page { padding: 0 0 80px; min-height: 100vh; background: #f0f2f5; }

/* Tabs */
.ms-tabs-scroll { position: sticky; top: 0; z-index: 100; background: #fff; border-bottom: 1px solid #e8ecf1; overflow-x: auto; -webkit-overflow-scrolling: touch; scrollbar-width: none; }
.ms-tabs-scroll::-webkit-scrollbar { display: none; }
.ms-tabs { display: flex; gap: 4px; padding: 8px 12px; white-space: nowrap; min-width: max-content; }
.mst-item { display: inline-flex; align-items: center; gap: 5px; padding: 7px 14px; border-radius: 20px; font-size: 13px; font-weight: 500; color: #64748b; cursor: pointer; transition: all .2s; flex-shrink: 0; }
.mst-item.active { background: #6366f1; color: #fff; font-weight: 700; box-shadow: 0 2px 8px rgba(99,102,241,.3); }
.mst-item:active { transform: scale(.95); }

/* Search */
.ms-search { display: flex; align-items: center; gap: 8px; background: #fff; margin: 10px 12px; border-radius: 12px; padding: 0 12px; height: 42px; border: 1px solid #e8ecf1; }
.mss-icon { font-size: 17px; color: #94a3b8; flex-shrink: 0; }
.ms-search input { flex: 1; border: none; outline: none; font-size: 13px; color: #0f172a; background: transparent; }
.ms-search select { border: none; outline: none; font-size: 12px; color: #64748b; background: #f8fafc; border-radius: 6px; padding: 4px 8px; }

/* Filter row */
.ms-filter-row { display: flex; align-items: center; gap: 8px; padding: 0 12px 8px; }
.ms-filter-row select { flex: 1; border: 1px solid #e8ecf1; border-radius: 8px; padding: 6px 10px; font-size: 12px; background: #fff; color: #334155; max-width: 140px; }
.msf-count { font-size: 12px; color: #94a3b8; flex: 1; }
.ms-fab-mini { width: 36px; height: 36px; border-radius: 50%; background: #6366f1; color: #fff; border: none; font-size: 18px; display: flex; align-items: center; justify-content: center; cursor: pointer; box-shadow: 0 2px 8px rgba(99,102,241,.3); flex-shrink: 0; }
.ms-fab-mini:active { transform: scale(.9); }

/* List */
.ms-list { padding: 0 12px; }
.ms-card { background: #fff; border-radius: 14px; padding: 14px; margin-bottom: 10px; cursor: pointer; border: 1px solid rgba(0,0,0,.03); transition: all .15s; }
.ms-card:active { background: #f8fafc; transform: scale(.98); }
.msc-top { display: flex; align-items: center; gap: 10px; }
.msc-icon { width: 40px; height: 40px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0; }
.msc-info { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 2px; }
.msc-name { font-size: 14px; font-weight: 600; color: #0f172a; }
.msc-sub { font-size: 12px; color: #94a3b8; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.msc-arrow { font-size: 14px; color: #cbd5e1; flex-shrink: 0; }
.msc-badge { font-size: 10px; padding: 2px 8px; border-radius: 10px; font-weight: 600; white-space: nowrap; }
.msc-meta { display: flex; gap: 10px; margin-top: 10px; padding-top: 10px; border-top: 1px solid #f1f5f9; font-size: 11px; color: #64748b; flex-wrap: wrap; }
.msc-meta span { display: inline-flex; align-items: center; gap: 3px; }

/* Stats row */
.ms-stats-row { display: flex; gap: 12px; padding: 0 12px 8px; }
.ms-stat { flex: 1; background: #fff; border-radius: 10px; padding: 10px; text-align: center; font-size: 12px; color: #64748b; border: 1px solid #e8ecf1; }
.ms-stat b { display: block; font-size: 20px; font-weight: 800; }

/* Chips */
.ms-chips { display: flex; gap: 6px; padding: 0 12px 8px; flex-wrap: wrap; }
.ms-chip { padding: 4px 10px; border-radius: 14px; font-size: 11px; background: #f1f5f9; color: #64748b; cursor: pointer; border: 1px solid transparent; transition: all .15s; }
.ms-chip.on { background: #6366f114; color: #6366f1; border-color: #6366f1; font-weight: 600; }

/* Config form */
.ms-config-form { padding: 0 12px; }
.mscf-item { background: #fff; border-radius: 12px; padding: 14px; margin-bottom: 10px; border: 1px solid #e8ecf1; }
.mscf-item label { display: block; font-size: 12px; font-weight: 600; color: #64748b; margin-bottom: 6px; }
.mscf-item input, .mscf-item textarea, .mscf-item select { width: 100%; border: 1px solid #e8ecf1; border-radius: 8px; padding: 8px 10px; font-size: 13px; color: #0f172a; background: #f8fafc; outline: none; box-sizing: border-box; }
.mscf-item textarea { resize: vertical; min-height: 60px; }
.mscf-item small { display: block; margin-top: 4px; font-size: 11px; }

/* Sheet overlay */
.ms-overlay { position: fixed; inset: 0; z-index: 9000; background: rgba(0,0,0,.4); display: flex; align-items: flex-end; justify-content: center; animation: fadeIn .2s; }
@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
.ms-sheet { background: #fff; width: 100%; max-width: 480px; max-height: 85vh; border-radius: 20px 20px 0 0; display: flex; flex-direction: column; animation: slideUp .25s ease-out; }
@keyframes slideUp { from { transform: translateY(100%); } to { transform: translateY(0); } }
.ms-sh-top { display: flex; align-items: center; justify-content: space-between; padding: 16px 20px; border-bottom: 1px solid #f1f5f9; font-size: 16px; font-weight: 700; color: #0f172a; flex-shrink: 0; }
.ms-sh-close { font-size: 22px; color: #94a3b8; cursor: pointer; }
.ms-sh-body { padding: 16px 20px; overflow-y: auto; flex: 1; }
.ms-sh-body label { display: block; font-size: 12px; font-weight: 600; color: #64748b; margin-bottom: 4px; margin-top: 10px; }
.ms-sh-body label:first-child { margin-top: 0; }
.ms-sh-body input, .ms-sh-body textarea, .ms-sh-body select { width: 100%; border: 1px solid #e8ecf1; border-radius: 10px; padding: 10px 12px; font-size: 14px; color: #0f172a; background: #f8fafc; outline: none; box-sizing: border-box; }
.ms-sh-body textarea { resize: vertical; min-height: 60px; }
.ms-sh-radio { display: flex; gap: 8px; }
.ms-sh-radio span { padding: 8px 16px; border-radius: 8px; border: 1px solid #e8ecf1; font-size: 13px; cursor: pointer; transition: all .15s; }
.ms-sh-radio span.on { background: #6366f1; color: #fff; border-color: #6366f1; }
.ms-sh-radio-row { display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid #f8fafc; font-size: 14px; color: #334155; }
.ms-sh-radio-row span:last-child { padding: 6px 14px; border-radius: 8px; border: 1px solid #e8ecf1; font-size: 12px; cursor: pointer; }
.ms-sh-radio-row span.on { background: #6366f1; color: #fff; border-color: #6366f1; }
.ms-sh-actions { display: flex; gap: 8px; padding: 12px 20px 20px; border-top: 1px solid #f1f5f9; flex-wrap: wrap; flex-shrink: 0; }
.ms-sh-actions button { flex: 1; min-width: 60px; padding: 12px; border-radius: 12px; border: none; font-size: 14px; font-weight: 600; cursor: pointer; transition: all .15s; }
.ms-sh-actions button:active { transform: scale(.96); }
.ms-btn-primary { background: #6366f1; color: #fff; }
.ms-btn-danger { background: #fef2f2; color: #dc2626; flex: 0 !important; }
.ms-btn-warn { background: #fffbeb; color: #d97706; flex: 0 !important; }
.ms-btn-outline { background: #f1f5f9; color: #64748b; flex: 0 !important; }

/* Perm dialog */
.ms-perm-item { padding: 2px 0; }
.ms-perm-label { display: flex; align-items: center; gap: 8px; font-size: 13px; color: #334155; cursor: pointer; padding: 4px 0; }
.ms-perm-label input { width: auto; accent-color: #6366f1; }

/* Pager */
.ms-pager { display: flex; align-items: center; justify-content: center; gap: 16px; padding: 16px; }
.ms-pager button { width: 40px; height: 40px; border-radius: 50%; border: 1px solid #e8ecf1; background: #fff; font-size: 16px; color: #0f172a; cursor: pointer; }
.ms-pager button:disabled { opacity: .3; }
.ms-pager span { font-size: 13px; color: #64748b; }

/* Load more */
.ms-loadmore { text-align: center; padding: 16px; font-size: 13px; color: #6366f1; cursor: pointer; }
</style>
