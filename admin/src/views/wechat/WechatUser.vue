<template>
  <div class="wechat-user-page">
    <!-- ===== 统计卡片 ===== -->
    <div class="stat-cards">
      <div class="stat-card total">
        <div class="stat-icon"><el-icon size="28"><UserFilled /></el-icon></div>
        <div class="stat-body">
          <div class="stat-label">微信用户总数</div>
          <div class="stat-value">{{ stats.total }}</div>
          <div class="stat-sub">人</div>
        </div>
      </div>
      <div class="stat-card today">
        <div class="stat-icon"><el-icon size="28"><Plus /></el-icon></div>
        <div class="stat-body">
          <div class="stat-label">今日新增</div>
          <div class="stat-value">{{ stats.today_new }}</div>
          <div class="stat-sub">本周 {{ stats.week_new }} · 本月 {{ stats.month_new }}</div>
        </div>
      </div>
      <div class="stat-card registered">
        <div class="stat-icon"><el-icon size="28"><CircleCheckFilled /></el-icon></div>
        <div class="stat-body">
          <div class="stat-label">已注册用户</div>
          <div class="stat-value">{{ stats.registered }}</div>
          <div class="stat-sub">{{ regPercent }}% 转化率</div>
        </div>
      </div>
      <div class="stat-card unreg">
        <div class="stat-icon"><el-icon size="28"><WarningFilled /></el-icon></div>
        <div class="stat-body">
          <div class="stat-label">待完善信息</div>
          <div class="stat-value">{{ stats.unregistered }}</div>
          <div class="stat-sub">未绑定手机号</div>
        </div>
      </div>
    </div>

    <!-- ===== 小区分布 + 转化率 ===== -->
    <div class="second-row">
      <div class="community-section">
        <div class="section-title">
          <el-icon><HomeFilled /></el-icon> 各小区分布
        </div>
        <div class="community-list" v-if="stats.communities?.length">
          <div
            class="community-bar-item"
            v-for="(c, idx) in stats.communities"
            :key="c.community_id"
            :style="{ '--bar-color': barColors[idx % barColors.length] }"
          >
            <span class="c-name">{{ c.community_name || '未归属' }}</span>
            <span class="c-bar-wrap">
              <span class="c-bar" :style="{ width: (c.cnt / maxCount * 100) + '%' }"></span>
            </span>
            <span class="c-count">{{ c.cnt }}人</span>
          </div>
        </div>
        <el-empty v-else description="暂无数据" :image-size="60" />
      </div>

      <div class="convert-section">
        <div class="section-title">
          <el-icon><TrendCharts /></el-icon> 注册转化
        </div>
        <div class="donut-wrap">
          <div class="donut-ring">
            <svg viewBox="0 0 120 120">
              <circle cx="60" cy="60" r="50" fill="none" stroke="#e5e7eb" stroke-width="12" />
              <circle
                cx="60" cy="60" r="50"
                fill="none"
                stroke="url(#grad)"
                stroke-width="12"
                stroke-linecap="round"
                :stroke-dasharray="dashArray"
                :stroke-dashoffset="0"
                transform="rotate(-90 60 60)"
              />
              <defs>
                <linearGradient id="grad" x1="0%" y1="0%" x2="100%" y2="0%">
                  <stop offset="0%" stop-color="#6366f1" />
                  <stop offset="100%" stop-color="#8b5cf6" />
                </linearGradient>
              </defs>
            </svg>
            <div class="donut-center">
              <span class="donut-pct">{{ regPercent }}%</span>
              <span class="donut-label">已注册</span>
            </div>
          </div>
          <div class="convert-legend">
            <div class="legend-item"><span class="dot dot-reg"></span>已注册 {{ stats.registered }}</div>
            <div class="legend-item"><span class="dot dot-unreg"></span>待完善 {{ stats.unregistered }}</div>
          </div>
        </div>
      </div>
    </div>

    <!-- ===== 筛选栏 ===== -->
    <div class="filter-bar">
      <div class="filter-left">
        <el-select v-model="filters.community_id" placeholder="全部小区" clearable style="width:160px" @change="loadList">
          <el-option v-for="c in communities" :key="c.id" :label="c.name" :value="c.id" />
        </el-select>
        <el-select v-model="filters.status" placeholder="注册状态" clearable style="width:140px" @change="loadList">
          <el-option label="全部" value="" />
          <el-option label="已注册" value="1" />
          <el-option label="未注册" value="0" />
        </el-select>
        <el-input v-model="filters.keyword" placeholder="搜索姓名/手机号/OpenID" clearable style="width:260px" @keyup.enter="loadList" @clear="loadList">
          <template #prefix><el-icon><Search /></el-icon></template>
        </el-input>
        <el-button type="primary" @click="loadList"><el-icon><Search /></el-icon> 查询</el-button>
      </div>
      <div class="filter-right">
        <el-button type="success" @click="doExport"><el-icon><Download /></el-icon> 导出</el-button>
        <el-button @click="resetFilters">重置</el-button>
      </div>
    </div>

    <!-- ===== 数据表格 ===== -->
    <div class="table-wrap">
      <el-table :data="tableData" v-loading="loading" stripe size="default" @row-click="showDetail" style="cursor:pointer">
        <el-table-column prop="id" label="ID" width="70" align="center" />
        <el-table-column prop="realname" label="昵称" width="130">
          <template #default="{ row }">
            <div class="user-cell">
              <el-avatar :size="32" :style="{ background: avatarBg(row.id) }">
                {{ row.realname?.charAt(0) || '微' }}
              </el-avatar>
              <span class="user-name">{{ row.realname || '微信用户' }}</span>
            </div>
          </template>
        </el-table-column>
        <el-table-column prop="phone" label="手机号" width="130">
          <template #default="{ row }">
            <span v-if="row.phone">{{ row.phone }}</span>
            <el-tag v-else type="warning" size="small">未填写</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="community_name" label="所属小区" width="130" show-overflow-tooltip />
        <el-table-column prop="rooms" label="绑定房间" min-width="160" show-overflow-tooltip>
          <template #default="{ row }">
            <span v-if="row.rooms">{{ row.rooms }}</span>
            <span v-else class="text-muted">未绑定</span>
          </template>
        </el-table-column>
        <el-table-column prop="openid" label="OpenID" width="180" show-overflow-tooltip>
          <template #default="{ row }">
            <el-tooltip :content="row.openid" placement="top">
              <span class="openid-text">{{ row.openid?.slice(0,12) }}...</span>
            </el-tooltip>
          </template>
        </el-table-column>
        <el-table-column label="注册状态" width="90" align="center">
          <template #default="{ row }">
            <el-tag :type="row.phone ? 'success' : 'warning'" size="small" effect="plain">
              {{ row.phone ? '已注册' : '待完善' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="last_login_time" label="最近登录" width="160" align="center">
          <template #default="{ row }">
            <span v-if="row.last_login_time">{{ row.last_login_time }}</span>
            <span v-else class="text-muted">-</span>
          </template>
        </el-table-column>
        <el-table-column prop="create_time" label="关注时间" width="160" align="center" show-overflow-tooltip />
        <el-table-column label="操作" width="100" align="center" fixed="right">
          <template #default="{ row }">
            <el-button link type="primary" size="small" @click.stop="showDetail(row)">详情</el-button>
          </template>
        </el-table-column>
      </el-table>

      <div class="pagination-wrap">
        <el-pagination
          v-model:current-page="pagination.page"
          v-model:page-size="pagination.limit"
          :total="pagination.total"
          :page-sizes="[15, 30, 50, 100]"
          layout="total, sizes, prev, pager, next, jumper"
          background
          @size-change="loadList"
          @current-change="loadList"
        />
      </div>
    </div>

    <!-- ===== 用户详情抽屉 ===== -->
    <el-drawer v-model="drawerVisible" :title="detailUser?.realname || '用户详情'" size="480px" direction="rtl">
      <template v-if="detailUser">
        <div class="drawer-header">
          <el-avatar :size="64" :style="{ background: avatarBg(detailUser.id), fontSize: '24px' }">
            {{ detailUser.realname?.charAt(0) || '微' }}
          </el-avatar>
          <div class="dh-info">
            <div class="dh-name">{{ detailUser.realname || '微信用户' }}</div>
            <el-tag :type="detailUser.phone ? 'success' : 'warning'" size="small" effect="plain">
              {{ detailUser.phone ? '已注册' : '待完善信息' }}
            </el-tag>
          </div>
        </div>

        <el-descriptions :column="1" border class="detail-desc">
          <el-descriptions-item label="OpenID">
            <span class="mono">{{ detailUser.openid }}</span>
          </el-descriptions-item>
          <el-descriptions-item v-if="detailUser.wechat_unionid" label="UnionID">
            <span class="mono">{{ detailUser.wechat_unionid }}</span>
          </el-descriptions-item>
          <el-descriptions-item label="手机号">{{ detailUser.phone || '未填写' }}</el-descriptions-item>
          <el-descriptions-item label="邮箱">{{ detailUser.email || '未填写' }}</el-descriptions-item>
          <el-descriptions-item label="性别">{{ detailUser.gender == 1 ? '男' : detailUser.gender == 2 ? '女' : '未知' }}</el-descriptions-item>
          <el-descriptions-item label="所属小区">{{ detailUser.community_name || '未归属' }}</el-descriptions-item>
          <el-descriptions-item label="账号状态">
            <el-tag :type="detailUser.status == 1 ? 'success' : 'danger'" size="small">
              {{ detailUser.status == 1 ? '启用' : '禁用' }}
            </el-tag>
          </el-descriptions-item>
          <el-descriptions-item label="关注时间">{{ detailUser.create_time || '-' }}</el-descriptions-item>
          <el-descriptions-item label="最近登录">{{ detailUser.last_login_time || '-' }}</el-descriptions-item>
          <el-descriptions-item label="注册时间">{{ detailUser.register_time || '-' }}</el-descriptions-item>
        </el-descriptions>

        <div v-if="detailUser.rooms?.length" class="drawer-section">
          <div class="section-title"><el-icon><House /></el-icon> 绑定房间</div>
          <el-tag v-for="r in detailUser.rooms" :key="r.id" type="info" effect="plain" style="margin:4px">
            {{ r.building_name }}-{{ r.room_number }}
            <span v-if="r.relation" class="tag-extra">({{ r.relation }})</span>
          </el-tag>
        </div>

        <div v-if="detailUser.payments?.length" class="drawer-section">
          <div class="section-title"><el-icon><CreditCard /></el-icon> 最近缴费 ({{ detailUser.payments.length }})</div>
          <div class="mini-list">
            <div v-for="p in detailUser.payments" :key="p.id" class="mini-item">
              <span class="mini-date">{{ p.create_time?.slice(0,10) }}</span>
              <span class="mini-amount">¥{{ p.amount }}</span>
            </div>
          </div>
        </div>

        <div v-if="detailUser.repairs?.length" class="drawer-section">
          <div class="section-title"><el-icon><Tools /></el-icon> 最近报修 ({{ detailUser.repairs.length }})</div>
          <div class="mini-list">
            <div v-for="rp in detailUser.repairs" :key="rp.id" class="mini-item">
              <span class="mini-date">{{ rp.create_time?.slice(0,10) }}</span>
              <span>{{ rp.description?.slice(0, 20) || '报修工单' }}</span>
              <el-tag size="small" :type="rp.status == 3 ? 'success' : rp.status == 2 ? '' : 'warning'">
                {{ ['待处理','处理中','已完成','已关闭'][rp.status] || rp.status }}
              </el-tag>
            </div>
          </div>
        </div>
      </template>
    </el-drawer>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import {
  Plus, UserFilled, CircleCheckFilled, WarningFilled,
  Search, Download, HomeFilled, TrendCharts, House, CreditCard, Tools
} from '@element-plus/icons-vue'
import service, { apiGet } from '@/utils/request'

const loading = ref(false)
const drawerVisible = ref(false)
const detailUser = ref<any>(null)

// 统计
const stats = reactive({
  total: 0, registered: 0, unregistered: 0,
  today_new: 0, week_new: 0, month_new: 0,
  communities: [] as any[],
})

const regPercent = computed(() => {
  if (stats.total === 0) return 0
  return Math.round((stats.registered / stats.total) * 100)
})

const maxCount = computed(() => {
  if (!stats.communities?.length) return 1
  return Math.max(...stats.communities.map((c: any) => c.cnt), 1)
})

// 环形图
const dashArray = computed(() => {
  const circumference = 2 * Math.PI * 50
  const pct = regPercent.value / 100
  return `${circumference * pct} ${circumference * (1 - pct)}`
})

// 筛选
const filters = reactive({ community_id: '', status: '', keyword: '' })
const communities = ref<any[]>([])
const tableData = ref<any[]>([])
const pagination = reactive({ page: 1, limit: 15, total: 0 })

// 颜色
const barColors = ['#6366f1', '#8b5cf6', '#06b6d4', '#10b981', '#f59e0b', '#ef4444', '#ec4899', '#14b8a6']
const avatarColors = ['#6366f1','#8b5cf6','#06b6d4','#10b981','#f59e0b','#ef4444','#ec4899','#f97316']
function avatarBg(id: number) { return avatarColors[id % avatarColors.length] }

async function loadStats() {
  try {
    const params: any = {}
    if (filters.community_id) params.community_id = filters.community_id
    const res = await apiGet('/admin/wechat/userStatistics', params)
    Object.assign(stats, res.data)
  } catch (e) { /* ignore */ }
}

async function loadCommunities() {
  try {
    const res = await service.get('/admin/community/list')
    communities.value = res.data?.data?.list || res.data?.data || []
  } catch (e) { /* ignore */ }
}

async function loadList() {
  loading.value = true
  try {
    const params: any = { page: pagination.page, limit: pagination.limit }
    if (filters.community_id) params.community_id = filters.community_id
    if (filters.status !== '') params.status = filters.status
    if (filters.keyword) params.keyword = filters.keyword

    const res = await apiGet('/admin/wechat/userList', params)
    tableData.value = res.data || []
    pagination.total = res.count || 0
  } catch {
    tableData.value = []
    pagination.total = 0
  } finally {
    loading.value = false
  }
}

async function showDetail(row: any) {
  try {
    const res = await apiGet('/admin/wechat/userDetail', { id: row.id })
    detailUser.value = res.data
    drawerVisible.value = true
  } catch (e) { /* ignore */ }
}

function doExport() {
  const params = new URLSearchParams()
  if (filters.community_id) params.set('community_id', filters.community_id as string)
  if (filters.status !== '') params.set('status', filters.status as string)
  window.open('/admin/wechat/userExport?' + params.toString())
}

function resetFilters() {
  filters.community_id = ''
  filters.status = ''
  filters.keyword = ''
  pagination.page = 1
  loadList()
  loadStats()
}

onMounted(() => {
  loadStats()
  loadCommunities()
  loadList()
})
</script>

<style scoped>
.wechat-user-page {
  padding: 20px;
  background: #f5f6fa;
  min-height: 100%;
}

/* ===== 统计卡片 ===== */
.stat-cards {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
  margin-bottom: 16px;
}
.stat-card {
  background: #fff;
  border-radius: 12px;
  padding: 20px 24px;
  display: flex;
  align-items: center;
  gap: 16px;
  box-shadow: 0 1px 3px rgba(0,0,0,.06);
  transition: all .25s;
}
.stat-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 16px rgba(0,0,0,.1);
}
.stat-icon {
  width: 56px; height: 56px;
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  flex-shrink: 0;
}
.stat-card.total .stat-icon { background: linear-gradient(135deg, #6366f1, #818cf8); }
.stat-card.today .stat-icon { background: linear-gradient(135deg, #06b6d4, #22d3ee); }
.stat-card.registered .stat-icon { background: linear-gradient(135deg, #10b981, #34d399); }
.stat-card.unreg .stat-icon { background: linear-gradient(135deg, #f59e0b, #fbbf24); }
.stat-body { flex: 1; min-width: 0; }
.stat-label { font-size: 13px; color: #9ca3af; margin-bottom: 4px; }
.stat-value { font-size: 28px; font-weight: 700; color: #1f2937; line-height: 1.2; }
.stat-sub { font-size: 12px; color: #9ca3af; margin-top: 2px; }

/* ===== 第二行 ===== */
.second-row {
  display: grid;
  grid-template-columns: 1.4fr 0.8fr;
  gap: 16px;
  margin-bottom: 16px;
}
.community-section, .convert-section {
  background: #fff;
  border-radius: 12px;
  padding: 20px 24px;
  box-shadow: 0 1px 3px rgba(0,0,0,.06);
}
.section-title {
  font-size: 14px;
  font-weight: 600;
  color: #374151;
  margin-bottom: 16px;
  display: flex;
  align-items: center;
  gap: 6px;
}

/* 小区分布条 */
.community-list { display: flex; flex-direction: column; gap: 10px; }
.community-bar-item {
  display: flex; align-items: center; gap: 10px; font-size: 13px;
}
.c-name { width: 80px; flex-shrink: 0; color: #4b5563; text-align: right; }
.c-bar-wrap { flex: 1; height: 10px; background: #f3f4f6; border-radius: 5px; overflow: hidden; }
.c-bar {
  display: block; height: 100%; border-radius: 5px;
  background: var(--bar-color, #6366f1);
  transition: width .5s ease;
}
.c-count { width: 40px; flex-shrink: 0; color: #6b7280; font-weight: 500; text-align: right; }

/* 转化率图 */
.donut-wrap { display: flex; flex-direction: column; align-items: center; gap: 14px; }
.donut-ring { position: relative; width: 120px; height: 120px; }
.donut-ring svg { width: 100%; height: 100%; }
.donut-center {
  position: absolute; top: 0; left: 0; right: 0; bottom: 0;
  display: flex; flex-direction: column; align-items: center; justify-content: center;
}
.donut-pct { font-size: 22px; font-weight: 700; color: #6366f1; line-height: 1.2; }
.donut-label { font-size: 11px; color: #9ca3af; }
.convert-legend { display: flex; gap: 20px; font-size: 13px; }
.legend-item { display: flex; align-items: center; gap: 6px; color: #6b7280; }
.dot { width: 10px; height: 10px; border-radius: 50%; display: inline-block; }
.dot-reg { background: #6366f1; }
.dot-unreg { background: #f59e0b; }

/* ===== 筛选栏 ===== */
.filter-bar {
  display: flex; justify-content: space-between; align-items: center;
  background: #fff; border-radius: 12px; padding: 14px 20px;
  margin-bottom: 16px; box-shadow: 0 1px 3px rgba(0,0,0,.06);
  gap: 12px; flex-wrap: wrap;
}
.filter-left { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
.filter-right { display: flex; gap: 8px; flex-shrink: 0; }

/* ===== 表格 ===== */
.table-wrap {
  background: #fff; border-radius: 12px; padding: 0;
  box-shadow: 0 1px 3px rgba(0,0,0,.06); overflow: hidden;
}
.user-cell { display: flex; align-items: center; gap: 8px; }
.user-name { font-weight: 500; color: #1f2937; }
.openid-text { font-family: 'Courier New', monospace; font-size: 12px; color: #6b7280; }
.text-muted { color: #9ca3af; font-size: 12px; }
.pagination-wrap {
  display: flex; justify-content: flex-end;
  padding: 14px 20px; border-top: 1px solid #f3f4f6;
}

/* ===== 抽屉 ===== */
.drawer-header {
  display: flex; align-items: center; gap: 14px;
  padding-bottom: 16px; margin-bottom: 16px; border-bottom: 1px solid #f3f4f6;
}
.dh-info { display: flex; flex-direction: column; gap: 4px; }
.dh-name { font-size: 16px; font-weight: 600; color: #1f2937; }
.detail-desc { margin-bottom: 16px; }
.drawer-section {
  margin-top: 16px; padding-top: 16px; border-top: 1px solid #f3f4f6;
}
.tag-extra { font-size: 11px; opacity: .7; }
.mono { font-family: 'Courier New', monospace; font-size: 12px; word-break: break-all; }
.mini-list { display: flex; flex-direction: column; gap: 8px; margin-top: 8px; }
.mini-item {
  display: flex; align-items: center; gap: 10px;
  padding: 8px 10px; background: #f9fafb; border-radius: 8px; font-size: 13px;
}
.mini-date { color: #9ca3af; flex-shrink: 0; }
.mini-amount { color: #ef4444; font-weight: 600; margin-left: auto; }

/* 表格行悬停 */
:deep(.el-table__row:hover) { background: #f8fafc; }
</style>
