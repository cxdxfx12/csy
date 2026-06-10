<template>
  <div class="mp-page">
    <!-- 顶部Tabs -->
    <div class="mp-tabs">
      <div class="mpt-item" :class="{ active: tab === 'community' }" @click="switchTab('community')">
        <Icon icon="ph:buildings-duotone" /> 小区
      </div>
      <div class="mpt-item" :class="{ active: tab === 'building' }" @click="switchTab('building')">
        <Icon icon="ph:building-office-duotone" /> 楼栋
      </div>
      <div class="mpt-item" :class="{ active: tab === 'room' }" @click="switchTab('room')">
        <Icon icon="ph:house-duotone" /> 房间
      </div>
    </div>

    <!-- 搜索栏 -->
    <div class="mp-search">
      <Icon icon="ph:magnifying-glass" class="mps-icon" />
      <input v-model="query.keyword" :placeholder="searchPlaceholder" @keyup.enter="loadData" />
      <select v-model="query.community_id" @change="loadData" v-if="tab !== 'community'">
        <option value="">全部小区</option>
        <option v-for="c in communities" :key="c.id" :value="c.id">{{ c.name }}</option>
      </select>
    </div>

    <!-- 卡片列表 -->
    <div class="mp-list">
      <div class="mp-card" v-for="item in list" :key="item.id" @click="showDetail(item)" v-vo-click>
        <!-- 小区 -->
        <template v-if="tab === 'community'">
          <div class="mpc-top">
            <div class="mpc-icon" style="background:#0891b214;color:#0891b2"><Icon icon="ph:buildings-duotone" /></div>
            <div class="mpc-info">
              <span class="mpc-name">{{ item.name }}</span>
              <span class="mpc-sub">{{ item.address || '—' }}</span>
            </div>
            <Icon icon="ph:caret-right" class="mpc-arrow" />
          </div>
          <div class="mpc-meta">
            <span><Icon icon="ph:building-office" /> {{ item.building_count || 0 }}栋</span>
            <span><Icon icon="ph:house" /> {{ item.room_count || 0 }}间</span>
            <span><Icon icon="ph:users" /> {{ item.owner_count || 0 }}户</span>
          </div>
        </template>
        <!-- 楼栋 -->
        <template v-if="tab === 'building'">
          <div class="mpc-top">
            <div class="mpc-icon" style="background:#0891b214;color:#0891b2"><Icon icon="ph:building-office-duotone" /></div>
            <div class="mpc-info">
              <span class="mpc-name">{{ item.name }}</span>
              <span class="mpc-sub">{{ item.community_name }} · {{ item.floors }}层{{ item.units }}单元</span>
            </div>
            <Icon icon="ph:caret-right" class="mpc-arrow" />
          </div>
          <div class="mpc-meta">
            <span><Icon icon="ph:house" /> {{ item.total_rooms || 0 }}间</span>
            <span v-if="item.has_commercial"><el-tag size="small" type="warning">底商{{ item.commercial_floors }}层</el-tag></span>
            <span v-if="item.manager_name"><Icon icon="ph:user-circle" /> {{ item.manager_name }}</span>
          </div>
        </template>
        <!-- 房间 -->
        <template v-if="tab === 'room'">
          <div class="mpc-top">
            <div class="mpc-icon" :style="{ background: item.owner_name ? '#05966914' : '#64748b14', color: item.owner_name ? '#059669' : '#64748b' }"><Icon icon="ph:house-duotone" /></div>
            <div class="mpc-info">
              <span class="mpc-name">{{ item.building_name }} - {{ item.room_number }}</span>
              <span class="mpc-sub">{{ item.community_name }} · {{ item.area }}㎡</span>
            </div>
            <Icon icon="ph:caret-right" class="mpc-arrow" />
          </div>
          <div class="mpc-meta">
            <span v-if="item.owner_name"><Icon icon="ph:user" /> {{ item.owner_name }}</span>
            <span v-else style="color:#94a3b8">空置</span>
          </div>
        </template>
      </div>
      <div class="mp-loading" v-if="loading"><Icon icon="ph:spinner" class="spin" /> 加载中...</div>
      <div class="mp-empty" v-if="!loading && !list.length">
        <Icon icon="ph:buildings-duotone" />
        <span>{{ tab === 'community' ? '暂无小区' : tab === 'building' ? '暂无楼栋' : '暂无房间' }}</span>
      </div>
    </div>

    <!-- 底部加载更多 -->
    <div class="mp-more" v-if="list.length < total" @click="loadMore">
      <Icon icon="ph:arrows-down" /> 加载更多 ({{ list.length }}/{{ total }})
    </div>

    <!-- 详情弹窗 -->
    <Teleport to="body">
      <Transition name="slide-up">
        <div class="mp-detail-overlay" v-if="detail" @click.self="detail = null">
          <div class="mp-detail-panel">
            <div class="mpd-hdl">
              <span class="mpd-title">{{ tab === 'community' ? '小区详情' : tab === 'building' ? '楼栋详情' : '房间详情' }}</span>
              <Icon icon="ph:x" class="mpd-close" @click="detail = null" />
            </div>
            <div class="mpd-body">
              <div class="mpd-row" v-if="detail.name"><label>名称</label><span>{{ detail.name }}</span></div>
              <div class="mpd-row" v-if="detail.address"><label>地址</label><span>{{ detail.address }}</span></div>
              <div class="mpd-row" v-if="detail.community_name"><label>小区</label><span>{{ detail.community_name }}</span></div>
              <div class="mpd-row" v-if="detail.building_name"><label>楼栋</label><span>{{ detail.building_name }}</span></div>
              <div class="mpd-row" v-if="detail.room_number"><label>房间号</label><span>{{ detail.room_number }}</span></div>
              <div class="mpd-row" v-if="detail.area"><label>面积</label><span>{{ detail.area }}㎡</span></div>
              <div class="mpd-row" v-if="detail.floors !== undefined"><label>层数</label><span>{{ detail.floors }}层 / {{ detail.units }}单元</span></div>
              <div class="mpd-row" v-if="detail.total_rooms !== undefined"><label>房间数</label><span>{{ detail.total_rooms }}</span></div>
              <div class="mpd-row" v-if="detail.building_count !== undefined"><label>楼栋数</label><span>{{ detail.building_count }}</span></div>
              <div class="mpd-row" v-if="detail.room_count !== undefined && tab==='community'"><label>房间数</label><span>{{ detail.room_count }}</span></div>
              <div class="mpd-row" v-if="detail.owner_count !== undefined"><label>业主数</label><span>{{ detail.owner_count }}</span></div>
              <div class="mpd-row" v-if="detail.manager_name"><label>管理员</label><span>{{ detail.manager_name }}</span></div>
              <div class="mpd-row" v-if="detail.owner_name"><label>业主</label><span>{{ detail.owner_name }}</span></div>
            </div>
            <div class="mpd-actions">
              <button class="mpd-btn edit" @click="openForm(detail);detail=null"><Icon icon="ph:pencil" /> 编辑</button>
              <button class="mpd-btn danger" @click="handleDelete(detail);detail=null"><Icon icon="ph:trash" /> 删除</button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- 编辑弹窗 -->
    <Teleport to="body">
      <Transition name="slide-up">
        <div class="mp-detail-overlay" v-if="formVisible" @click.self="formVisible = false">
          <div class="mp-detail-panel">
            <div class="mpd-hdl">
              <span class="mpd-title">{{ form.id ? ('编辑' + tabLabel) : ('添加' + tabLabel) }}</span>
              <Icon icon="ph:x" class="mpd-close" @click="formVisible = false" />
            </div>
            <div class="mpd-body">
              <div class="mpd-field" v-if="tab !== 'community'">
                <label>小区</label>
                <select v-model="form.community_id"><option value="">选择小区</option><option v-for="c in communities" :key="c.id" :value="c.id">{{ c.name }}</option></select>
              </div>
              <div class="mpd-field">
                <label>{{ tab === 'building' ? '名称' : tab === 'room' ? '房间号' : '名称' }}</label>
                <input v-model="form.name" :placeholder="tab === 'building' ? '楼栋名称' : tab === 'room' ? '如 101' : '小区名称'" />
              </div>
              <div class="mpd-field" v-if="tab === 'community'">
                <label>地址</label>
                <input v-model="form.address" placeholder="详细地址" />
              </div>
              <div class="mpd-field" v-if="tab === 'room'">
                <label>面积(㎡)</label>
                <input v-model.number="form.area" type="number" placeholder="建筑面积" />
              </div>
              <div class="mpd-row-2" v-if="tab === 'building' && !form.id">
                <div class="mpd-field"><label>层数</label><input v-model.number="form.floors" type="number" /></div>
                <div class="mpd-field"><label>单元数</label><input v-model.number="form.units" type="number" /></div>
              </div>
            </div>
            <div class="mpd-actions">
              <button class="mpd-btn cancel" @click="formVisible = false">取消</button>
              <button class="mpd-btn edit" @click="submitForm" :disabled="submitting">
                <Icon icon="ph:spinner" class="spin" v-if="submitting" /> {{ submitting ? '提交中' : '确定' }}
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- 浮动添加按钮 -->
    <button class="mp-fab" @click="openForm()">
      <Icon icon="ph:plus-bold" />
    </button>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { Icon } from '@iconify/vue'
import { apiGet, apiPost } from '@/utils/request'

const tab = ref('community')
const list = ref<any[]>([])
const total = ref(0)
const loading = ref(false)
const communities = ref<any[]>([])
const detail = ref<any>(null)
const formVisible = ref(false)
const submitting = ref(false)

const query = reactive({ keyword: '', community_id: '' as any, page: 1, limit: 20 })

const tabLabel = computed(() => tab.value === 'community' ? '小区' : tab.value === 'building' ? '楼栋' : '房间')
const searchPlaceholder = computed(() => tab.value === 'community' ? '搜索小区名称...' : tab.value === 'building' ? '搜索楼栋名称...' : '搜索房间号...')

const form = reactive<any>({ id: 0, name: '', community_id: '', address: '', floors: 1, units: 1, area: 0 })

function switchTab(t: string) {
  tab.value = t
  query.keyword = ''
  query.community_id = ''
  query.page = 1
  loadData()
}

async function loadCommunities() {
  try { const r = await apiGet('/admin/community/list', { limit: 999 }); communities.value = r.data?.list || r.data || [] } catch {}
}

async function loadData() {
  loading.value = true
  try {
    let url = ''
    if (tab.value === 'community') url = '/admin/community/list'
    else if (tab.value === 'building') url = '/admin/building/list'
    else url = '/admin/room/list'
    const r = await apiGet(url, { ...query })
    list.value = r.data?.list || r.data || []
    total.value = r.count || r.data?.total || list.value.length
  } catch { list.value = []; total.value = 0 }
  finally { loading.value = false }
}

function loadMore() { query.page++; loadData() }

function showDetail(item: any) { detail.value = item }

function openForm(row?: any) {
  Object.assign(form, row ? { id: row.id, name: row.name || row.room_number || '', community_id: row.community_id || '', address: row.address || '', floors: row.floors || 1, units: row.units || 1, area: row.area || 0 } : { id: 0, name: '', community_id: '', address: '', floors: 1, units: 1, area: 0 })
  formVisible.value = true
}

async function submitForm() {
  if (!form.name) { alert('请输入名称'); return }
  submitting.value = true
  try {
    let url = ''
    if (tab.value === 'community') url = form.id ? '/admin/community/edit' : '/admin/community/add'
    else if (tab.value === 'building') url = form.id ? '/admin/building/edit' : '/admin/building/add'
    else url = form.id ? '/admin/room/edit' : '/admin/room/add'
    await apiPost(url, { ...form })
    formVisible.value = false
    query.page = 1
    loadData()
  } catch {} finally { submitting.value = false }
}

async function handleDelete(row: any) {
  if (!confirm(`确定删除${tabLabel}"${row.name || row.room_number}"吗？`)) return
  try {
    let url = ''
    if (tab.value === 'community') url = '/admin/community/delete'
    else if (tab.value === 'building') url = '/admin/building/delete'
    else url = '/admin/room/delete'
    await apiPost(url, { id: row.id })
    loadData()
  } catch {}
}

onMounted(async () => { await loadCommunities(); loadData() })
</script>

<style scoped>
.mp-page { padding: 0; position: relative; min-height: 100vh; background: #f0f2f5; }

/* Tabs */
.mp-tabs { display: flex; background: #fff; border-radius: 12px; padding: 4px; margin-bottom: 12px; gap: 4px; }
.mpt-item { flex: 1; display: flex; align-items: center; justify-content: center; gap: 4px; padding: 10px 0; border-radius: 10px; font-size: 13px; font-weight: 500; color: #64748b; cursor: pointer; transition: all .2s; }
.mpt-item.active { background: linear-gradient(135deg, #0891b2, #06b6d4); color: #fff; box-shadow: 0 2px 8px rgba(8,145,178,.3); }
.mpt-item:active { transform: scale(.96); }

/* Search */
.mp-search { display: flex; align-items: center; gap: 8px; background: #fff; border-radius: 12px; padding: 0 12px; height: 42px; margin-bottom: 12px; border: 1.5px solid transparent; transition: border .2s; }
.mp-search:focus-within { border-color: #0891b2; }
.mps-icon { font-size: 16px; color: #94a3b8; flex-shrink: 0; }
.mp-search input { flex: 1; border: none; outline: none; font-size: 13px; color: #0f172a; background: transparent; height: 42px; }
.mp-search input::placeholder { color: #94a3b8; }
.mp-search select { border: none; background: #f1f5f9; border-radius: 8px; padding: 4px 8px; font-size: 12px; color: #334155; outline: none; max-width: 100px; }

/* Card List */
.mp-list { display: flex; flex-direction: column; gap: 8px; }
.mp-card { background: #fff; border-radius: 14px; padding: 14px; cursor: pointer; border: 1px solid rgba(0,0,0,.03); transition: all .2s; }
.mp-card:active { transform: scale(.98); background: #f8fafc; }
.mpc-top { display: flex; align-items: center; gap: 12px; }
.mpc-icon { width: 42px; height: 42px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0; }
.mpc-info { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 2px; }
.mpc-name { font-size: 14px; font-weight: 600; color: #0f172a; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.mpc-sub { font-size: 12px; color: #94a3b8; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.mpc-arrow { font-size: 16px; color: #cbd5e1; flex-shrink: 0; }
.mpc-meta { display: flex; gap: 14px; margin-top: 10px; padding-top: 10px; border-top: 1px solid #f1f5f9; font-size: 11px; color: #64748b; flex-wrap: wrap; }
.mpc-meta span { display: flex; align-items: center; gap: 4px; }

/* Loading & Empty */
.mp-loading, .mp-empty { display: flex; align-items: center; justify-content: center; gap: 8px; padding: 40px 0; color: #94a3b8; font-size: 13px; }
.mp-empty { flex-direction: column; font-size: 30px; }
.mp-empty span { font-size: 13px; }
.spin { animation: spin 1s linear infinite; }

/* Load More */
.mp-more { display: flex; align-items: center; justify-content: center; gap: 6px; padding: 16px; color: #0891b2; font-size: 13px; cursor: pointer; }

/* Detail Panel */
.mp-detail-overlay { position: fixed; inset: 0; background: rgba(0,0,0,.4); z-index: 9999; display: flex; align-items: flex-end; }
.mp-detail-panel { background: #fff; border-radius: 20px 20px 0 0; width: 100%; max-height: 80vh; overflow-y: auto; padding-bottom: env(safe-area-inset-bottom); }
.mpd-hdl { display: flex; align-items: center; justify-content: space-between; padding: 16px 20px; border-bottom: 1px solid #f1f5f9; }
.mpd-title { font-size: 16px; font-weight: 700; color: #0f172a; }
.mpd-close { font-size: 20px; color: #94a3b8; cursor: pointer; padding: 4px; }
.mpd-body { padding: 16px 20px; }
.mpd-row { display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid #f8fafc; }
.mpd-row label { font-size: 13px; color: #94a3b8; }
.mpd-row span { font-size: 14px; color: #0f172a; font-weight: 500; }
.mpd-field { margin-bottom: 12px; }
.mpd-field label { display: block; font-size: 12px; color: #64748b; margin-bottom: 6px; font-weight: 500; }
.mpd-field input, .mpd-field select { width: 100%; padding: 10px 12px; border: 1.5px solid #e2e8f0; border-radius: 10px; font-size: 14px; outline: none; background: #fff; box-sizing: border-box; }
.mpd-field input:focus, .mpd-field select:focus { border-color: #0891b2; }
.mpd-row-2 { display: flex; gap: 10px; }
.mpd-row-2 .mpd-field { flex: 1; }
.mpd-actions { display: flex; gap: 10px; padding: 16px 20px; border-top: 1px solid #f1f5f9; }
.mpd-btn { flex: 1; padding: 12px; border-radius: 12px; border: none; font-size: 14px; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 6px; }
.mpd-btn.edit { background: #0891b2; color: #fff; }
.mpd-btn.danger { background: #fef2f2; color: #dc2626; }
.mpd-btn.cancel { background: #f1f5f9; color: #64748b; }

/* FAB */
.mp-fab { position: fixed; bottom: 24px; right: 24px; width: 52px; height: 52px; border-radius: 50%; background: linear-gradient(135deg, #0891b2, #06b6d4); color: #fff; border: none; font-size: 24px; cursor: pointer; box-shadow: 0 4px 16px rgba(8,145,178,.35); z-index: 100; display: flex; align-items: center; justify-content: center; }
.mp-fab:active { transform: scale(.92); }

/* Animation */
.slide-up-enter-active { transition: all .25s ease-out; }
.slide-up-leave-active { transition: all .2s ease-in; }
.slide-up-enter-from .mp-detail-panel { transform: translateY(100%); }
.slide-up-leave-to .mp-detail-panel { transform: translateY(100%); }

@keyframes spin { to { transform: rotate(360deg); } }
</style>
