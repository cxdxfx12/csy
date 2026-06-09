<template>
  <div class="login-page">
    <div class="login-card">
      <div class="logo">🐒</div>
      <h1>大圣物业管理平台</h1>
      <p class="sub">小区经理工作台</p>

      <!-- 用户名密码登录 -->
      <div v-if="!showRegister">
        <select v-model="communityId" class="comm-select">
          <option :value="0">请选择小区（可选）</option>
          <option v-for="c in communities" :key="c.id" :value="c.id">{{ c.name }}</option>
        </select>
        <input v-model="username" type="text" placeholder="请输入用户名" @keyup.enter="doLogin" />
        <input v-model="password" type="password" placeholder="请输入密码" @keyup.enter="doLogin" />
        <button class="btn-primary" @click="doLogin" :disabled="loading">
          {{ loading ? '登录中...' : '登 录' }}
        </button>
        <div class="divider"><span>或</span></div>
        <button class="btn-wechat" @click="doWechatLogin" :disabled="loading">
          <span class="wx-icon">💬</span> {{ loading ? '跳转中...' : '微信一键登录' }}
        </button>
      </div>

      <!-- 微信注册 -->
      <div v-else>
        <p class="reg-title">微信注册 · 小区经理</p>
        <input v-model="regForm.realname" type="text" placeholder="真实姓名" />
        <input v-model="regForm.phone" type="text" placeholder="手机号（选填）" />
        <input v-model="regForm.username" type="text" placeholder="设置用户名" />
        <input v-model="regForm.password" type="password" placeholder="设置密码" />
        <button class="btn-primary" @click="doRegister" :disabled="loading">
          {{ loading ? '注册中...' : '注 册' }}
        </button>
        <a class="back-link" @click="showRegister = false">← 返回登录</a>
      </div>
    </div>

    <!-- 浮动主题切换器 -->
    <div class="theme-switcher-float">
      <button class="theme-trigger-float" @click.stop="themeOpen = !themeOpen" title="切换主题">🎨</button>
      <div class="theme-panel-float" v-if="themeOpen" @click.stop>
        <div class="theme-panel-title">选择主题</div>
        <div v-for="t in themes" :key="t.id" class="theme-item" :class="{active: current === t.id}" @click="applyTheme(t.id); themeOpen = false">
          <span class="theme-swatch" :style="{background: `linear-gradient(135deg, ${t.preview[0]}, ${t.preview[1]})`}"></span>
          <span class="theme-name">{{ t.icon }} {{ t.name }}</span>
          <span v-if="current === t.id" class="theme-check">✓</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { createApi, createAuth } from '@/shared/api.js'
import { showToast } from '@/shared/utils.js'
import { useTheme } from '../stores/useTheme.js'

const { themes, current, applyTheme } = useTheme()
const themeOpen = ref(false)

function closeThemePanel(e) {
  if (themeOpen.value && !e.target.closest('.theme-switcher-float')) {
    themeOpen.value = false
  }
}
onMounted(() => document.addEventListener('click', closeThemePanel))
onUnmounted(() => document.removeEventListener('click', closeThemePanel))

const route = useRoute()
const router = useRouter()
const api = createApi('/api/manager', 'manager_token')
const auth = createAuth('manager_token')

const username = ref('')
const password = ref('')
const loading = ref(false)
const showRegister = ref(false)
const communities = ref([])
const communityId = ref(0)

const regForm = ref({
  openid: '',
  community_id: 0,
  realname: '',
  phone: '',
  username: '',
  password: '',
})

// 检测 URL 上的 wechat_token（OAuth 回调带回）
onMounted(() => {
  const q = route.query
  // 已有绑定 → OAuth 回调带 token
  if (q.wechat_token) {
    auth.setToken(q.wechat_token)
    router.replace('/dashboard')
    return
  }
  // 未绑定 → 进入注册流程
  if (q.action === 'wx_register' && q.wx_openid) {
    regForm.value.openid = q.wx_openid
    regForm.value.community_id = parseInt(q.wx_cid) || communityId.value || 0
    showRegister.value = true
  }
  fetchCommunities()
})

async function doLogin() {
  if (!username.value || !password.value) return showToast('请输入用户名和密码')
  loading.value = true
  const res = await api('/login', { method: 'POST', body: JSON.stringify({ username: username.value, password: password.value }) })
  loading.value = false
  if (res.code === 0) {
    auth.setToken(res.data.token)
    if (communityId.value) localStorage.setItem('manager_community_id', communityId.value)
    router.replace('/dashboard')
  } else {
    showToast(res.msg || '登录失败')
  }
}

function doWechatLogin() {
  loading.value = true
  // 跳转微信 OAuth，后端自动检测小区（前端先获取 OAuth URL 再直接跳转，避免 302 跨域问题）
  const baseUrl = `${window.location.origin}/index.php/api/manager/wechatOAuth`
  const cid = communityId.value ? `&community_id=${communityId.value}` : ''
  const redirect = '/manager.html#/login'
  fetch(`${baseUrl}?json=1&redirect=${encodeURIComponent(redirect)}${cid}`)
    .then(res => res.json())
    .then(data => {
      if (data.code === 0 && data.data.oauth_url) {
        window.location.href = data.data.oauth_url
      } else {
        loading.value = false
        showToast(data.msg || '获取微信授权链接失败', 4000)
      }
    })
    .catch(() => {
      loading.value = false
      showToast('网络请求失败', 4000)
    })
}

async function doRegister() {
  const f = regForm.value
  if (!f.realname) return showToast('请填写真实姓名')
  if (!f.username) return showToast('请设置用户名')
  if (!f.password || f.password.length < 6) return showToast('密码至少6位')
  loading.value = true
  const res = await api('/wechatRegister', {
    method: 'POST',
    body: JSON.stringify(f),
  })
  loading.value = false
  if (res.code === 0) {
    auth.setToken(res.data.token)
    router.replace('/dashboard')
  } else {
    showToast(res.msg || '注册失败')
  }
}

async function fetchCommunities() {
  try {
    const r = await fetch('/api/communityList')
    const d = await r.json()
    if (d.code === 0) communities.value = d.data
  } catch {}
}
</script>

<style scoped>
.login-page{display:flex;align-items:center;justify-content:center;min-height:100vh;background:var(--bg-page);position:relative;overflow:hidden;transition:background .4s}
/* 动态背景光斑 */
.login-page::before{content:'';position:absolute;top:-20%;left:-10%;width:60%;height:60%;background:var(--blob1);border-radius:50%;animation:floatBlob1 8s ease-in-out infinite;pointer-events:none;opacity:var(--blob-opacity);transition:opacity .3s}
.login-page::after{content:'';position:absolute;bottom:-15%;right:-10%;width:50%;height:50%;background:var(--blob2);border-radius:50%;animation:floatBlob2 10s ease-in-out infinite;pointer-events:none;opacity:var(--blob-opacity);transition:opacity .3s}
@keyframes floatBlob1{0%,100%{transform:translate(0,0) scale(1)}50%{transform:translate(30px,20px) scale(1.08)}}
@keyframes floatBlob2{0%,100%{transform:translate(0,0) scale(1)}50%{transform:translate(-20px,-15px) scale(1.05)}}

/* 液态玻璃卡片 */
.login-card{position:relative;background:var(--bg-card);border:1px solid var(--border-1);border-radius:var(--r-xl);padding:44px 32px;width:90%;max-width:380px;text-align:center;backdrop-filter:var(--glass-blur-lg);-webkit-backdrop-filter:var(--glass-blur-lg);box-shadow:var(--shadow-card);animation:cardAppear .6s cubic-bezier(.4,0,.2,1) both;transition:background .3s,border-color .3s,box-shadow .3s}
/* 顶部高光折射线 */
.login-card::before{content:'';position:absolute;top:0;left:20%;right:20%;height:1px;background:var(--highlight-line);opacity:var(--highlight-opacity);border-radius:1px;transition:opacity .3s}
/* 内部折射光晕 */
.login-card::after{content:'';position:absolute;top:-50%;left:-50%;width:200%;height:200%;background:radial-gradient(ellipse at 30% 20%,rgba(var(--accent-rgb),.06) 0%,transparent 50%);pointer-events:none;opacity:var(--shine-opacity);transition:opacity .3s}
@keyframes cardAppear{from{opacity:0;transform:translateY(20px) scale(.97)}to{opacity:1;transform:translateY(0) scale(1)}}

.logo{font-size:56px;margin-bottom:8px;filter:drop-shadow(0 4px 16px rgba(var(--accent-rgb),.4))}
h1{font-size:22px;background:var(--accent-text-gradient);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;margin-bottom:4px;font-weight:700}
.sub{color:var(--text-3);font-size:13px;margin-bottom:20px}

/* 输入框 */
.comm-select,.login-card input[type="text"],.login-card input[type="password"]{width:100%;height:50px;background:var(--bg-input);border:1px solid var(--border-input);border-radius:var(--r-md);padding:0 16px;font-size:15px;margin-bottom:16px;outline:none;color:var(--text-1);backdrop-filter:var(--glass-blur-xs);-webkit-backdrop-filter:var(--glass-blur-xs);transition:all .3s cubic-bezier(.4,0,.2,1)}
.comm-select{cursor:pointer;appearance:auto}
.comm-select option{background:var(--select-bg);color:var(--text-1)}
.comm-select:focus,.login-card input:focus{border-color:var(--border-input-focus);box-shadow:0 0 0 3px rgba(var(--accent-rgb),.12),var(--shine-top);background:var(--bg-input-focus)}
input::placeholder{color:var(--text-5)}

/* 主按钮 */
.btn-primary{width:100%;height:50px;background:var(--accent-gradient);color:#fff;border:1px solid rgba(255,255,255,.15);border-radius:var(--r-md);font-size:16px;font-weight:600;cursor:pointer;transition:all .25s cubic-bezier(.4,0,.2,1);box-shadow:0 4px 20px var(--accent-shadow),var(--shine-top);backdrop-filter:var(--glass-blur-xs);-webkit-backdrop-filter:var(--glass-blur-xs);position:relative;overflow:hidden}
.btn-primary::before{content:'';position:absolute;top:0;left:0;right:0;height:50%;background:var(--inner-fade);opacity:var(--shine-opacity);border-radius:var(--r-md) var(--r-md) 0 0;pointer-events:none;transition:opacity .3s}
.btn-primary:hover{transform:translateY(-2px);box-shadow:0 8px 30px var(--accent-shadow),var(--shine-top)}
.btn-primary:active{transform:translateY(0);box-shadow:0 2px 10px var(--accent-shadow)}
.btn-primary:disabled{opacity:.5;transform:none}

.divider{display:flex;align-items:center;margin:20px 0;color:var(--text-5);font-size:12px}
.divider::before,.divider::after{content:'';flex:1;height:1px;background:linear-gradient(90deg,transparent,var(--border-2),transparent)}
.divider span{padding:0 12px}

/* 微信按钮 */
.btn-wechat{width:100%;height:50px;background:var(--wechat-gradient);color:#fff;border:1px solid rgba(255,255,255,.15);border-radius:var(--r-md);font-size:15px;font-weight:600;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:6px;transition:all .25s cubic-bezier(.4,0,.2,1);box-shadow:0 4px 16px var(--wechat-shadow),var(--shine-top);position:relative;overflow:hidden}
.btn-wechat::before{content:'';position:absolute;top:0;left:0;right:0;height:50%;background:var(--inner-fade);opacity:var(--shine-opacity);border-radius:var(--r-md) var(--r-md) 0 0;pointer-events:none;transition:opacity .3s}
.btn-wechat:hover{transform:translateY(-2px);box-shadow:0 8px 24px var(--wechat-shadow),var(--shine-top)}
.btn-wechat:active{transform:translateY(0)}
.btn-wechat:disabled{opacity:.5;transform:none}
.wx-icon{font-size:18px}
.reg-title{color:var(--text-2);font-size:15px;margin-bottom:16px;font-weight:600}
.back-link{display:inline-block;margin-top:16px;color:var(--accent-light);font-size:13px;cursor:pointer;text-decoration:none;transition:all .2s}
.back-link:hover{color:var(--accent-lighter)}

/* ===== 浮动主题切换器 ===== */
.theme-switcher-float{position:fixed;bottom:24px;right:24px;z-index:999}
.theme-trigger-float{width:48px;height:48px;background:var(--switcher-bg);border:1px solid var(--switcher-border);border-radius:50%;cursor:pointer;font-size:22px;display:flex;align-items:center;justify-content:center;transition:all .3s;backdrop-filter:var(--glass-blur-xs);-webkit-backdrop-filter:var(--glass-blur-xs);box-shadow:0 4px 16px rgba(0,0,0,.2)}
.theme-trigger-float:hover{transform:scale(1.1);border-color:var(--accent);box-shadow:0 4px 20px var(--accent-shadow)}
.theme-panel-float{position:absolute;bottom:calc(100% + 8px);right:0;width:200px;background:var(--switcher-panel-bg);border:1px solid var(--border-1);border-radius:var(--r-md);padding:8px;box-shadow:var(--shadow-modal);backdrop-filter:var(--glass-blur-lg);-webkit-backdrop-filter:var(--glass-blur-lg);animation:panelIn .2s ease-out}
@keyframes panelIn{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:translateY(0)}}
.theme-panel-title{font-size:12px;color:var(--text-4);padding:6px 10px 8px;font-weight:600;text-transform:uppercase;letter-spacing:.5px}
.theme-item{display:flex;align-items:center;gap:10px;padding:8px 10px;border-radius:8px;cursor:pointer;transition:all .2s}
.theme-item:hover{background:var(--switcher-item-hover)}
.theme-item.active{background:var(--switcher-item-active)}
.theme-swatch{width:24px;height:24px;border-radius:6px;flex-shrink:0;border:2px solid rgba(255,255,255,.1)}
.theme-name{flex:1;font-size:13px;color:var(--text-2);font-weight:500}
.theme-check{color:var(--accent);font-weight:700;font-size:14px}
</style>
