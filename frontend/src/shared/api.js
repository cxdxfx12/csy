// 共享 API 请求封装
// API路径映射：Vue组件传入的baseURL会自动映射到正确的后端路由
//   /api/staff   → /index.php/staff
//   /api/manager → /index.php/manager
//   /api/api     → /index.php/api
const BASE_HOST = 'http://dasheng.local'

export function createApi(baseURL, tokenKey) {
  // 去掉多余的 /api 前缀，统一走 /index.php/ 入口
  const realBase = '/index.php' + baseURL.replace(/^\/api/, '')

  return async function api(path, options = {}) {
    const token = localStorage.getItem(tokenKey)
    const headers = { 'Content-Type': 'application/json', ...options.headers }
    if (token) headers['Authorization'] = 'Bearer ' + token

    const url = BASE_HOST + realBase + path
    const res = await fetch(url, { ...options, headers })
    const data = await res.json()
    return data
  }
}

// 通用认证
export function createAuth(tokenKey) {
  return {
    getToken() { return localStorage.getItem(tokenKey) },
    setToken(t) { localStorage.setItem(tokenKey, t) },
    removeToken() { localStorage.removeItem(tokenKey) },
    isLogin() { return !!localStorage.getItem(tokenKey) }
  }
}
