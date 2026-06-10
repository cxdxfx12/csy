<template>
  <div class="mo-page">
    <!-- Search -->
    <div class="mo-search">
      <Icon icon="ph:magnifying-glass" class="mos-icon" />
      <input v-model="query.keyword" placeholder="搜索姓名/手机号..." @keyup.enter="loadData" />
      <select v-model="query.community_id" @change="loadData">
        <option value="">全部小区</option>
        <option v-for="c in communities" :key="c.id" :value="c.id">{{ c.name }}</option>
      </select>
    </div>

    <!-- Card List -->
    <div class="mo-list">
      <div class="mo-card" v-for="item in list" :key="item.id" @click="showDetail(item)">
        <div class="moc-avatar" :style="{ background: item.gender===2 ? '#ec489914' : '#3b82f614', color: item.gender===2 ? '#ec4899' : '#3b82f6' }">
          <Icon :icon="item.gender===2 ? 'ph:woman-duotone' : 'ph:man-duotone'" />
        </div>
        <div class="moc-info">
          <div class="moc-name-row">
            <span class="moc-name">{{ item.realname }}</span>
            <el-tag v-if="item.wx_bound" type="success" size="small">微信</el-tag>
          </div>
          <span class="moc-phone"><Icon icon="ph:phone" /> {{ item.phone }}</span>
          <span class="moc-meta">{{ item.community_name }} · {{ item.room_count || 0 }}套房产 · {{ item.create_time }}</span>
        </div>
        <Icon icon="ph:caret-right" class="moc-arrow" />
      </div>
      <div class="mo-loading" v-if="loading"><Icon icon="ph:spinner" class="spin" /> 加载中...</div>
      <div class="mo-empty" v-if="!loading && !list.length">
        <Icon icon="ph:users-duotone" />
        <span>暂无业主数据</span>
      </div>
    </div>

    <div class="mo-more" v-if="list.length < total" @click="loadMore">
      <Icon icon="ph:arrows-down" /> 加载更多 ({{ list.length }}/{{ total }})
    </div>

    <!-- Detail Panel -->
    <Teleport to="body">
      <Transition name="slide-up">
        <div class="mo-overlay" v-if="detail" @click.self="detail = null">
          <div class="mo-panel">
            <div class="mop-hdl"><span class="mop-title">业主详情</span><Icon icon="ph:x" class="mop-close" @click="detail = null" /></div>
            <div class="mop-body">
              <div class="mop-hero">
                <div class="moph-avatar" :style="{ background: detail.gender===2 ? '#ec489914' : '#3b82f614', color: detail.gender===2 ? '#ec4899' : '#3b82f6' }">
                  <Icon :icon="detail.gender===2 ? 'ph:woman-duotone' : 'ph:man-duotone'" />
                </div>
                <div class="moph-info"><span class="moph-name">{{ detail.realname }}</span><span>{{ detail.phone }}</span></div>
              </div>
              <div class="mop-row"><label>手机</label><a :href="'tel:'+detail.phone">{{ detail.phone }}</a></div>
              <div class="mop-row"><label>身份证</label><span>{{ detail.id_card }}</span></div>
              <div class="mop-row"><label>性别</label><span>{{ detail.gender===1?'男':detail.gender===2?'女':'-' }}</span></div>
              <div class="mop-row"><label>注册时间</label><span>{{ detail.register_time }}</span></div>
              <div class="mop-row"><label>微信绑定</label>
                <el-tag v-if="detail.openid" type="success" size="small">已绑定</el-tag>
                <span v-else style="color:#ccc">未绑定</span>
              </div>
              <div class="mop-section" v-if="detail.rooms?.length">
                <div class="mops-title"><Icon icon="ph:house" /> 房产信息</div>
                <div class="mop-row" v-for="r in detail.rooms" :key="r.id"><label>{{ r.building_name }} {{ r.room_number }}</label><span>{{ r.area }}㎡ / {{ r.relation }}</span></div>
              </div>
              <div class="mop-section" v-if="detail.bills?.length">
                <div class="mops-title"><Icon icon="ph:file-text" /> 最近账单</div>
                <div class="mop-row" v-for="b in detail.bills" :key="b.id">
                  <label>{{ b.bill_no }}</label>
                  <span :style="{ color: b.status===3?'#059669':b.status===2?'#ea580c':'#64748b' }">¥{{ b.total_amount }} {{ b.status===3?'已缴':b.status===2?'部分':'待缴' }}</span>
                </div>
              </div>
            </div>
            <div class="mop-actions">
              <button class="mopa-btn edit" @click="openForm(detail);detail=null"><Icon icon="ph:pencil" /> 编辑</button>
              <button class="mopa-btn danger" @click="handleDelete(detail);detail=null"><Icon icon="ph:trash" /> 删除</button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- Edit Panel -->
    <Teleport to="body">
      <Transition name="slide-up">
        <div class="mo-overlay" v-if="formVisible" @click.self="formVisible = false">
          <div class="mo-panel">
            <div class="mop-hdl"><span class="mop-title">{{ form.id ? '编辑业主' : '添加业主' }}</span><Icon icon="ph:x" class="mop-close" @click="formVisible = false" /></div>
            <div class="mop-body">
              <div class="mop-field"><label>小区</label><select v-model="form.community_id" @change="form.room_id=''"><option value="">选择小区</option><option v-for="c in communities" :key="c.id" :value="c.id">{{ c.name }}</option></select></div>
              <div class="mop-field"><label>姓名</label><input v-model="form.realname" placeholder="真实姓名" /></div>
              <div class="mop-field"><label>手机号</label><input v-model="form.phone" placeholder="手机号" maxlength="11" /></div>
              <div class="mop-field"><label>身份证</label><input v-model="form.id_card" placeholder="身份证号" maxlength="18" /></div>
              <div class="mop-field"><label>性别</label>
                <div class="mopf-radio"><button :class="{ active: form.gender===1 }" @click="form.gender=1">男</button><button :class="{ active: form.gender===2 }" @click="form.gender=2">女</button></div>
              </div>
            </div>
            <div class="mop-actions">
              <button class="mopa-btn cancel" @click="formVisible = false">取消</button>
              <button class="mopa-btn edit" @click="submitForm" :disabled="submitting">
                <Icon icon="ph:spinner" class="spin" v-if="submitting" /> {{ submitting ? '提交中' : '确定' }}
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <button class="mo-fab" @click="openForm()"><Icon icon="ph:plus-bold" /></button>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { Icon } from '@iconify/vue'
import { apiGet, apiPost } from '@/utils/request'

const list = ref<any[]>([])
const total = ref(0)
const loading = ref(false)
const communities = ref<any[]>([])
const detail = ref<any>(null)
const formVisible = ref(false)
const submitting = ref(false)

const query = reactive({ keyword: '', community_id: '' as any, page: 1, limit: 20 })
const form = reactive<any>({ id: 0, community_id: '', realname: '', phone: '', id_card: '', gender: 1, password: '' })

async function loadData() {
  loading.value = true
  try {
    const r = await apiGet('/admin/owner/list', { ...query })
    list.value = r.data?.list || r.data || []
    total.value = r.count || r.data?.total || list.value.length
  } catch { list.value = []; total.value = 0 }
  finally { loading.value = false }
}

function loadMore() { query.page++; loadData() }

async function showDetail(row: any) {
  try { const r = await apiGet('/admin/owner/detail', { id: row.id }); detail.value = r.data } catch {}
}

function openForm(row?: any) {
  Object.assign(form, row ? { ...row, password: '' } : { id: 0, community_id: '', realname: '', phone: '', id_card: '', gender: 1, password: '' })
  formVisible.value = true
}

async function submitForm() {
  if (!form.realname || !form.phone) { alert('请填写姓名和手机号'); return }
  submitting.value = true
  try {
    const url = form.id ? '/admin/owner/edit' : '/admin/owner/add'
    const payload = { ...form }
    if (!form.id && !payload.password) delete payload.password
    await apiPost(url, payload)
    formVisible.value = false
    query.page = 1
    loadData()
  } catch {} finally { submitting.value = false }
}

async function handleDelete(row: any) {
  if (!confirm(`确定删除业主"${row.realname}"吗？`)) return
  try { await apiPost('/admin/owner/delete', { id: row.id }); loadData() } catch {}
}

onMounted(async () => {
  try { const r = await apiGet('/admin/community/list', { limit: 999 }); communities.value = r.data?.list || r.data || [] } catch {}
  loadData()
})
</script>

<style scoped>
.mo-page { position: relative; min-height: 100vh; background: #f0f2f5; }
.mo-search { display: flex; align-items: center; gap: 8px; background: #fff; border-radius: 12px; padding: 0 12px; height: 42px; margin-bottom: 12px; border: 1.5px solid transparent; transition: border .2s; }
.mo-search:focus-within { border-color: #059669; }
.mos-icon { font-size: 16px; color: #94a3b8; flex-shrink: 0; }
.mo-search input { flex: 1; border: none; outline: none; font-size: 13px; color: #0f172a; background: transparent; height: 42px; }
.mo-search input::placeholder { color: #94a3b8; }
.mo-search select { border: none; background: #f1f5f9; border-radius: 8px; padding: 4px 8px; font-size: 12px; color: #334155; outline: none; max-width: 100px; }

.mo-list { display: flex; flex-direction: column; gap: 8px; }
.mo-card { background: #fff; border-radius: 14px; padding: 14px; display: flex; align-items: center; gap: 12px; cursor: pointer; border: 1px solid rgba(0,0,0,.03); }
.mo-card:active { transform: scale(.98); }
.moc-avatar { width: 42px; height: 42px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0; }
.moc-info { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 2px; }
.moc-name-row { display: flex; align-items: center; gap: 6px; }
.moc-name { font-size: 14px; font-weight: 600; color: #0f172a; }
.moc-phone { font-size: 12px; color: #64748b; display: flex; align-items: center; gap: 4px; }
.moc-meta { font-size: 11px; color: #94a3b8; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.moc-arrow { font-size: 16px; color: #cbd5e1; flex-shrink: 0; }

.mo-loading, .mo-empty { display: flex; align-items: center; justify-content: center; gap: 8px; padding: 40px 0; color: #94a3b8; font-size: 13px; }
.mo-empty { flex-direction: column; font-size: 30px; }
.mo-empty span { font-size: 13px; }
.mo-more { display: flex; align-items: center; justify-content: center; gap: 6px; padding: 16px; color: #059669; font-size: 13px; cursor: pointer; }

.mo-overlay { position: fixed; inset: 0; background: rgba(0,0,0,.4); z-index: 9999; display: flex; align-items: flex-end; }
.mo-panel { background: #fff; border-radius: 20px 20px 0 0; width: 100%; max-height: 80vh; overflow-y: auto; padding-bottom: env(safe-area-inset-bottom); }
.mop-hdl { display: flex; align-items: center; justify-content: space-between; padding: 16px 20px; border-bottom: 1px solid #f1f5f9; }
.mop-title { font-size: 16px; font-weight: 700; color: #0f172a; }
.mop-close { font-size: 20px; color: #94a3b8; cursor: pointer; padding: 4px; }
.mop-body { padding: 16px 20px; }
.mop-hero { display: flex; align-items: center; gap: 14px; margin-bottom: 16px; padding-bottom: 16px; border-bottom: 1px solid #f1f5f9; }
.moph-avatar { width: 56px; height: 56px; border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 28px; }
.moph-info { display: flex; flex-direction: column; gap: 2px; }
.moph-name { font-size: 18px; font-weight: 700; color: #0f172a; }
.moph-info span { font-size: 13px; color: #64748b; }
.mop-row { display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid #f8fafc; }
.mop-row label { font-size: 13px; color: #94a3b8; }
.mop-row span, .mop-row a { font-size: 14px; color: #0f172a; font-weight: 500; text-decoration: none; }
.mop-section { margin-top: 12px; }
.mops-title { font-size: 13px; font-weight: 600; color: #334155; margin-bottom: 8px; display: flex; align-items: center; gap: 6px; }
.mop-field { margin-bottom: 12px; }
.mop-field label { display: block; font-size: 12px; color: #64748b; margin-bottom: 6px; font-weight: 500; }
.mop-field input, .mop-field select { width: 100%; padding: 10px 12px; border: 1.5px solid #e2e8f0; border-radius: 10px; font-size: 14px; outline: none; background: #fff; box-sizing: border-box; }
.mop-field input:focus, .mop-field select:focus { border-color: #059669; }
.mopf-radio { display: flex; gap: 8px; }
.mopf-radio button { flex: 1; padding: 10px; border-radius: 10px; border: 1.5px solid #e2e8f0; background: #fff; font-size: 14px; color: #64748b; cursor: pointer; }
.mopf-radio button.active { border-color: #059669; background: #05966914; color: #059669; font-weight: 600; }
.mop-actions { display: flex; gap: 10px; padding: 16px 20px; border-top: 1px solid #f1f5f9; }
.mopa-btn { flex: 1; padding: 12px; border-radius: 12px; border: none; font-size: 14px; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 6px; }
.mopa-btn.edit { background: #059669; color: #fff; }
.mopa-btn.danger { background: #fef2f2; color: #dc2626; }
.mopa-btn.cancel { background: #f1f5f9; color: #64748b; }

.mo-fab { position: fixed; bottom: 24px; right: 24px; width: 52px; height: 52px; border-radius: 50%; background: linear-gradient(135deg, #059669, #10b981); color: #fff; border: none; font-size: 24px; cursor: pointer; box-shadow: 0 4px 16px rgba(5,150,105,.35); z-index: 100; display: flex; align-items: center; justify-content: center; }
.mo-fab:active { transform: scale(.92); }

.slide-up-enter-active { transition: all .25s ease-out; }
.slide-up-leave-active { transition: all .2s ease-in; }
.slide-up-enter-from .mo-panel { transform: translateY(100%); }
.slide-up-leave-to .mo-panel { transform: translateY(100%); }
.spin { animation: spin 1s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }
</style>
