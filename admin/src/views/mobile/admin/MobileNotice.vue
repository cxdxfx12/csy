<template>
  <div class="mn-page">
    <!-- Search -->
    <div class="mn-search">
      <Icon icon="ph:magnifying-glass" class="mns-icon" />
      <input v-model="query.keyword" placeholder="搜索公告标题..." @keyup.enter="loadData" />
      <select v-model="query.community_id" @change="loadData">
        <option value="">全部小区</option>
        <option v-for="c in communities" :key="c.id" :value="c.id">{{ c.name }}</option>
      </select>
      <select v-model="query.status" @change="loadData" v-show="false">
        <option value="">全部状态</option>
        <option value="1">草稿</option>
        <option value="2">已发布</option>
        <option value="3">已撤回</option>
      </select>
    </div>

    <!-- Filter Chips -->
    <div class="mn-chips">
      <div class="mnc-chip" :class="{ active: query.status === '' }" @click="query.status='';loadData()">全部</div>
      <div class="mnc-chip" :class="{ active: query.status === '1' }" @click="query.status='1';loadData()">草稿</div>
      <div class="mnc-chip" :class="{ active: query.status === '2' }" @click="query.status='2';loadData()">已发布</div>
      <div class="mnc-chip" :class="{ active: query.status === '3' }" @click="query.status='3';loadData()">已撤回</div>
    </div>

    <!-- List -->
    <div class="mn-list">
      <div class="mn-card" v-for="item in list" :key="item.id" @click="showDetail(item)">
        <div class="mnc-hdr">
          <span class="mnc-title">{{ item.title }}</span>
          <div class="mnc-tags">
            <el-tag v-if="item.top_status===1" type="danger" size="small">置顶</el-tag>
            <el-tag :type="statusType[item.status]||'info'" size="small">{{ statusMap[item.status]||'未知' }}</el-tag>
          </div>
        </div>
        <div class="mnc-meta">
          <span><Icon icon="ph:buildings" /> {{ item.community_name }}</span>
          <span v-if="item.category"><Icon icon="ph:tag" /> {{ item.category }}</span>
          <span><Icon icon="ph:user-circle" /> {{ item.published_by || '—' }}</span>
        </div>
        <div class="mnc-content" v-if="item.content">{{ item.content.slice(0, 80) }}{{ item.content.length > 80 ? '...' : '' }}</div>
        <div class="mnc-time">{{ item.publish_time || item.create_time }}</div>
      </div>
      <div class="mn-loading" v-if="loading"><Icon icon="ph:spinner" class="spin" /> 加载中...</div>
      <div class="mn-empty" v-if="!loading && !list.length">
        <Icon icon="ph:newspaper-duotone" />
        <span>暂无公告</span>
      </div>
    </div>

    <div class="mn-more" v-if="list.length < total" @click="loadMore">
      <Icon icon="ph:arrows-down" /> 加载更多 ({{ list.length }}/{{ total }})
    </div>

    <!-- Detail Panel -->
    <Teleport to="body">
      <Transition name="slide-up">
        <div class="mn-overlay" v-if="detail" @click.self="detail = null">
          <div class="mn-panel">
            <div class="mnp-hdl">
              <span class="mnp-title">公告详情</span>
              <Icon icon="ph:x" class="mnp-close" @click="detail = null" />
            </div>
            <div class="mnp-body">
              <h3 class="mnp-ttl">{{ detail.title }}</h3>
              <div class="mnp-tags">
                <el-tag v-if="detail.top_status===1" type="danger" size="small">置顶</el-tag>
                <el-tag :type="statusType[detail.status]||'info'" size="small">{{ statusMap[detail.status] }}</el-tag>
              </div>
              <div class="mnp-meta">
                <span>{{ detail.community_name }}</span>
                <span v-if="detail.category">· {{ detail.category }}</span>
                <span>· {{ detail.published_by || '管理员' }}</span>
                <span>· {{ detail.publish_time || detail.create_time }}</span>
              </div>
              <div class="mnp-content">{{ detail.content }}</div>
            </div>
            <div class="mnp-actions">
              <button v-if="detail.status===1" class="mnpa-btn publish" @click="handlePublish(detail, 2);detail=null"><Icon icon="ph:paper-plane-tilt" /> 发布</button>
              <button v-if="detail.status===2" class="mnpa-btn revoke" @click="handlePublish(detail, 3);detail=null"><Icon icon="ph:arrow-u-up-left" /> 撤回</button>
              <button class="mnpa-btn edit" @click="openForm(detail);detail=null"><Icon icon="ph:pencil" /> 编辑</button>
              <button class="mnpa-btn danger" @click="handleDelete(detail);detail=null"><Icon icon="ph:trash" /> 删除</button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- Edit Panel -->
    <Teleport to="body">
      <Transition name="slide-up">
        <div class="mn-overlay" v-if="formVisible" @click.self="formVisible = false">
          <div class="mn-panel">
            <div class="mnp-hdl"><span class="mnp-title">{{ form.id ? '编辑公告' : '发布公告' }}</span><Icon icon="ph:x" class="mnp-close" @click="formVisible = false" /></div>
            <div class="mnp-body">
              <div class="mnp-field"><label>小区</label><select v-model="form.community_id"><option value="">选择小区</option><option v-for="c in communities" :key="c.id" :value="c.id">{{ c.name }}</option></select></div>
              <div class="mnp-field"><label>标题</label><input v-model="form.title" placeholder="公告标题" /></div>
              <div class="mnp-field"><label>分类</label><input v-model="form.category" placeholder="如 通知、活动" /></div>
              <div class="mnp-field"><label>内容</label><textarea v-model="form.content" rows="5" placeholder="公告内容"></textarea></div>
              <div class="mnp-field-row">
                <label>置顶</label>
                <button class="mnp-toggle" :class="{ on: form.top_status===1 }" @click="form.top_status = form.top_status===1 ? 0 : 1">{{ form.top_status===1 ? '已置顶' : '不置顶' }}</button>
              </div>
            </div>
            <div class="mnp-actions">
              <button class="mnpa-btn cancel" @click="formVisible = false">取消</button>
              <button class="mnpa-btn publish" @click="submitForm" :disabled="submitting">
                <Icon icon="ph:spinner" class="spin" v-if="submitting" /> {{ submitting ? '保存中' : '确定' }}
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <button class="mn-fab" @click="openForm()"><Icon icon="ph:plus-bold" /></button>
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

const statusMap: Record<number, string> = { 1: '草稿', 2: '已发布', 3: '已撤回' }
const statusType: Record<number, string> = { 1: 'info', 2: 'success', 3: 'danger' }

const query = reactive({ keyword: '', community_id: '' as any, status: '' as any, page: 1, limit: 20 })
const form = reactive<any>({ id: 0, community_id: '', title: '', category: '', content: '', top_status: 0 })

async function loadData() {
  loading.value = true
  try {
    const r = await apiGet('/admin/notice/list', { ...query })
    list.value = r.data?.list || r.data || []
    total.value = r.count || r.data?.total || list.value.length
  } catch { list.value = []; total.value = 0 }
  finally { loading.value = false }
}

function loadMore() { query.page++; loadData() }

function showDetail(item: any) { detail.value = item }

function openForm(row?: any) {
  Object.assign(form, row ? { ...row } : { id: 0, community_id: '', title: '', category: '', content: '', top_status: 0 })
  formVisible.value = true
}

async function submitForm() {
  if (!form.title) { alert('请输入标题'); return }
  submitting.value = true
  try {
    const url = form.id ? '/admin/notice/edit' : '/admin/notice/add'
    await apiPost(url, { ...form })
    formVisible.value = false
    query.page = 1
    loadData()
  } catch {} finally { submitting.value = false }
}

async function handlePublish(row: any, status: number) {
  try { await apiPost('/admin/notice/publish', { id: row.id, status }); loadData() } catch {}
}

async function handleDelete(row: any) {
  if (!confirm(`确定删除公告"${row.title}"吗？`)) return
  try { await apiPost('/admin/notice/delete', { id: row.id }); loadData() } catch {}
}

onMounted(async () => {
  try { const r = await apiGet('/admin/community/list', { limit: 999 }); communities.value = r.data?.list || r.data || [] } catch {}
  loadData()
})
</script>

<style scoped>
.mn-page { min-height: 100vh; background: #f0f2f5; position: relative; }
.mn-search { display: flex; align-items: center; gap: 8px; background: #fff; border-radius: 12px; padding: 0 12px; height: 42px; margin-bottom: 10px; border: 1.5px solid transparent; transition: border .2s; }
.mn-search:focus-within { border-color: #8b5cf6; }
.mns-icon { font-size: 16px; color: #94a3b8; flex-shrink: 0; }
.mn-search input { flex: 1; border: none; outline: none; font-size: 13px; color: #0f172a; background: transparent; height: 42px; }
.mn-search input::placeholder { color: #94a3b8; }
.mn-search select { border: none; background: #f1f5f9; border-radius: 8px; padding: 4px 8px; font-size: 12px; color: #334155; outline: none; max-width: 100px; }

.mn-chips { display: flex; gap: 8px; margin-bottom: 12px; overflow-x: auto; padding-bottom: 4px; scrollbar-width: none; }
.mn-chips::-webkit-scrollbar { display: none; }
.mnc-chip { padding: 6px 16px; border-radius: 20px; font-size: 12px; font-weight: 500; background: #fff; color: #64748b; cursor: pointer; white-space: nowrap; border: 1px solid rgba(0,0,0,.04); transition: all .2s; }
.mnc-chip.active { background: linear-gradient(135deg, #8b5cf6, #a78bfa); color: #fff; border-color: transparent; }

.mn-list { display: flex; flex-direction: column; gap: 10px; }
.mn-card { background: #fff; border-radius: 14px; padding: 16px; cursor: pointer; border: 1px solid rgba(0,0,0,.03); transition: all .2s; }
.mn-card:active { transform: scale(.98); }
.mnc-hdr { display: flex; justify-content: space-between; align-items: flex-start; gap: 8px; margin-bottom: 8px; }
.mnc-title { font-size: 15px; font-weight: 600; color: #0f172a; flex: 1; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
.mnc-tags { display: flex; gap: 4px; flex-shrink: 0; }
.mnc-meta { display: flex; gap: 12px; font-size: 11px; color: #94a3b8; flex-wrap: wrap; margin-bottom: 8px; }
.mnc-meta span { display: flex; align-items: center; gap: 3px; }
.mnc-content { font-size: 12px; color: #64748b; line-height: 1.5; margin-bottom: 8px; }
.mnc-time { font-size: 10px; color: #cbd5e1; text-align: right; }

.mn-loading, .mn-empty { display: flex; align-items: center; justify-content: center; gap: 8px; padding: 40px 0; color: #94a3b8; font-size: 13px; }
.mn-empty { flex-direction: column; font-size: 30px; }
.mn-empty span { font-size: 13px; }
.mn-more { display: flex; align-items: center; justify-content: center; gap: 6px; padding: 16px; color: #8b5cf6; font-size: 13px; cursor: pointer; }

.mn-overlay { position: fixed; inset: 0; background: rgba(0,0,0,.4); z-index: 9999; display: flex; align-items: flex-end; }
.mn-panel { background: #fff; border-radius: 20px 20px 0 0; width: 100%; max-height: 80vh; overflow-y: auto; padding-bottom: env(safe-area-inset-bottom); }
.mnp-hdl { display: flex; align-items: center; justify-content: space-between; padding: 16px 20px; border-bottom: 1px solid #f1f5f9; position: sticky; top: 0; background: #fff; z-index: 1; }
.mnp-title { font-size: 16px; font-weight: 700; color: #0f172a; }
.mnp-close { font-size: 20px; color: #94a3b8; cursor: pointer; padding: 4px; }
.mnp-body { padding: 16px 20px; }
.mnp-ttl { font-size: 18px; font-weight: 700; color: #0f172a; margin: 0 0 8px; line-height: 1.4; }
.mnp-tags { margin-bottom: 6px; display: flex; gap: 4px; }
.mnp-meta { font-size: 12px; color: #94a3b8; margin-bottom: 16px; }
.mnp-content { font-size: 14px; color: #334155; line-height: 1.7; white-space: pre-wrap; }
.mnp-field { margin-bottom: 12px; }
.mnp-field label { display: block; font-size: 12px; color: #64748b; margin-bottom: 6px; font-weight: 500; }
.mnp-field input, .mnp-field select, .mnp-field textarea { width: 100%; padding: 10px 12px; border: 1.5px solid #e2e8f0; border-radius: 10px; font-size: 14px; outline: none; background: #fff; box-sizing: border-box; font-family: inherit; }
.mnp-field input:focus, .mnp-field select:focus, .mnp-field textarea:focus { border-color: #8b5cf6; }
.mnp-field-row { display: flex; align-items: center; justify-content: space-between; }
.mnp-field-row label { font-size: 12px; color: #64748b; font-weight: 500; }
.mnp-toggle { padding: 6px 16px; border-radius: 20px; border: 1.5px solid #e2e8f0; background: #fff; font-size: 13px; cursor: pointer; color: #64748b; transition: all .2s; }
.mnp-toggle.on { background: #8b5cf614; border-color: #8b5cf6; color: #8b5cf6; }
.mnp-actions { display: flex; gap: 8px; padding: 16px 20px; border-top: 1px solid #f1f5f9; flex-wrap: wrap; }
.mnpa-btn { flex: 1; padding: 12px; border-radius: 12px; border: none; font-size: 13px; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 6px; min-width: 70px; }
.mnpa-btn.publish { background: linear-gradient(135deg, #8b5cf6, #a78bfa); color: #fff; }
.mnpa-btn.edit { background: #f1f5f9; color: #334155; }
.mnpa-btn.revoke { background: #fef3c7; color: #ea580c; }
.mnpa-btn.danger { background: #fef2f2; color: #dc2626; }
.mnpa-btn.cancel { background: #f1f5f9; color: #64748b; }

.mn-fab { position: fixed; bottom: 24px; right: 24px; width: 52px; height: 52px; border-radius: 50%; background: linear-gradient(135deg, #8b5cf6, #a78bfa); color: #fff; border: none; font-size: 24px; cursor: pointer; box-shadow: 0 4px 16px rgba(139,92,246,.35); z-index: 100; display: flex; align-items: center; justify-content: center; }
.mn-fab:active { transform: scale(.92); }

.slide-up-enter-active { transition: all .25s ease-out; }
.slide-up-leave-active { transition: all .2s ease-in; }
.slide-up-enter-from .mn-panel { transform: translateY(100%); }
.slide-up-leave-to .mn-panel { transform: translateY(100%); }
.spin { animation: spin 1s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }
</style>
